#!/usr/bin/env python3
"""Save-Data integration checks. Run only against a disposable WordPress installation.
Usage: python3 tests/save-data.py /path/to/wordpress /path/to/wp-cli.phar
Requires PHP (DOM, mbstring), a working WordPress database, and this checkout installed
as wp-content/plugins/slim-low-bandwidth-mode. Starts a local PHP server on port 8765.
"""
import http.cookiejar
import json
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

root = Path(sys.argv[1]).resolve()
cli = Path(sys.argv[2]).resolve()
base = 'http://127.0.0.1:8765'
plugin = 'slim-low-bandwidth-mode'
checks = 0

def wp(*args):
    result = subprocess.run(['php', '-d', 'error_reporting=24575', str(cli),
                             '--path=' + str(root), *args], text=True, capture_output=True)
    if result.returncode:
        raise AssertionError(result.stdout + result.stderr)
    return result.stdout.strip()

def evaluate(code):
    return wp('eval', code)

def check(condition, label):
    global checks
    if not condition:
        raise AssertionError(label)
    checks += 1
    print('PASS ' + label, flush=True)

def option(name, value):
    # WordPress skips update_option(false) when an option is absent; persist an explicit off value.
    wp('option', 'update', name, json.dumps('0' if value is False else value), '--format=json')

def request(path='/', hint=None, client=None, data=None, extra=None):
    headers = dict(extra or {})
    if hint is not None:
        headers['Save-Data'] = hint
    req = urllib.request.Request(base + path, headers=headers, data=data)
    try:
        response = (client.open(req) if client else urllib.request.urlopen(req))
    except urllib.error.HTTPError as error:
        response = error
    with response:
        return response.status, response.headers, response.read().decode('utf-8', 'replace')

def view(path, hint, slim, label, vary=True, status=200):
    code, headers, body = request(path, hint)
    check(code == status, label + ' status')
    check(('name="slim-profile"' in body) == slim, label + ' representation')
    check(('Content-Security-Policy' in headers) == slim, label + ' CSP')
    if vary:
        check('save-data' in headers.get('Vary', '').lower(), label + ' Vary')
    if slim:
        check(not re.search(r'<(?:script|img|iframe|video|audio)\b', body, re.I), label + ' no external media/scripts')
        check('noindex,follow' in body, label + ' noindex')
    return headers, body

# This script intentionally changes options and creates fixtures: disposable sites only.
if evaluate('echo wp_get_environment_type();') != 'local' or wp('option', 'get', 'siteurl') != base:
    raise SystemExit('Requires WP_ENVIRONMENT_TYPE=local and siteurl=' + base)
wp('plugin', 'activate', plugin)
check(evaluate('echo get_bloginfo("version");') != '', 'WordPress boots')
wp('option', 'delete', 'slim_respect_save_data')
option('slim_force_sitewide', False)
option('slim_noindex', True)
wp('rewrite', 'structure', '/%postname%/')
ids = json.loads(evaluate('''
foreach (get_posts(['post_type'=>['post','page'], 'post_status'=>'any', 'posts_per_page'=>-1, 's'=>'SLIM']) as $fixture) {
  wp_delete_post($fixture->ID, true);
}
$ids = [];
foreach (['post', 'page'] as $type) {
  $ids[$type] = wp_insert_post(['post_type'=>$type, 'post_status'=>'publish',
    'post_title'=>'SLIM test ' . $type, 'post_name'=>'slim-test-' . $type,
    'post_content'=>'<p>SLIM fixture café 日本語</p><img src="https://example.test/image.png"><script>bad()</script>']);
}
$ids['excluded'] = wp_insert_post(['post_status'=>'publish', 'post_title'=>'SLIM excluded', 'post_name'=>'slim-excluded', 'post_content'=>'Full content']);
update_post_meta($ids['excluded'], '_slim_disable', true);
$ids['protected'] = wp_insert_post(['post_status'=>'publish', 'post_title'=>'SLIM protected', 'post_name'=>'slim-protected', 'post_password'=>'secret', 'post_content'=>'NEVER-EXPOSE-PROTECTED-CONTENT']);
echo wp_json_encode($ids);
'''))

mu = root / 'wp-content/mu-plugins/slim-test-vary.php'
mu.parent.mkdir(exist_ok=True)
mu.write_text("<?php add_filter('wp_headers', function ($h) { $h['Vary'] = 'Accept-Encoding'; return $h; }, 1);")
router = '''<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && (is_file(__DIR__ . $path) || is_dir(__DIR__ . $path))) return false;
require __DIR__ . '/index.php';
'''
router_path = root / 'slim-test-router.php'
router_path.write_text(router)
log = tempfile.TemporaryFile()
server = subprocess.Popen(['php', '-d', 'display_errors=0', '-d', 'opcache.enable=0', '-S', '127.0.0.1:8765',
                           '-t', str(root), str(router_path)], stdout=log, stderr=log)
try:
    for attempt in range(50):
        if server.poll() is not None:
            log.seek(0)
            raise AssertionError(log.read().decode())
        try:
            request('/')
            break
        except OSError:
            time.sleep(.1)
    view('/', None, False, 'absent header')
    for hint in ['off', 'false', '1', 'onward', 'on, off']:
        view('/', hint, False, 'invalid hint ' + hint)
    for hint in ['on', 'ON', 'On', ' on ']:
        view('/', hint, True, 'default enabled ' + repr(hint))
    for path in ['/', '/slim-test-post/', '/slim-test-page/', '/?s=SLIM', '/?s=nomatches987', '/?year=2026']:
        view(path, 'on', True, 'front end ' + path)
    view('/does-not-exist/', 'on', True, '404', status=404)
    view('/slim-excluded/', 'on', False, 'per-content exclusion')
    view('/?p=' + str(ids['post']), 'on', True, 'plain post URL')
    view('/?p=' + str(ids['excluded']), 'on', False, 'plain URL exclusion')
    # The normal theme must keep its wp_head hooks on excluded pages.
    _, _, excluded = request('/slim-excluded/', 'on')
    check('api.w.org' in excluded, 'excluded theme retains REST discovery head hook')
    headers, body = view('/slim-protected/', 'on', True, 'password protected')
    check('NEVER-EXPOSE-PROTECTED-CONTENT' not in body, 'password protection preserved')
    check('Accept-Encoding' in headers.get('Vary', ''), 'existing Vary preserved')
    check('Set-Cookie' not in headers, 'no visitor preference cookie')
    for path in ['/wp-json/', '/?rest_route=/', '/wp-login.php', '/?feed=rss2', '/robots.txt', '/favicon.ico', '/wp-admin/admin-ajax.php?action=slim_missing', '/wp-cron.php']:
        code, headers, body = request(path, 'on')
        check('name="slim-profile"' not in body and "default-src 'none'" not in headers.get('Content-Security-Policy', ''),
              'non-page request unaffected ' + path)
    # No state persists from an opted-in request to one without the hint.
    view('/slim-test-post/', 'on', True, 'request with hint')
    view('/slim-test-post/', None, False, 'next request without hint')
    option('page_on_front', ids['page'])
    option('show_on_front', 'page')
    view('/', 'on', True, 'static front page')
    option('show_on_front', 'posts')
    option('slim_respect_save_data', False)
    headers, _ = view('/', 'on', False, 'setting off', vary=False)
    check('save-data' not in headers.get('Vary', '').lower(), 'disabled feature does not vary by hint')
    wp('plugin', 'deactivate', plugin)
    wp('plugin', 'activate', plugin)
    view('/', 'on', False, 'saved off survives reactivation', vary=False)
    option('slim_force_sitewide', True)
    view('/', None, True, 'site-wide independent of Save-Data setting', vary=False)
    view('/slim-excluded/', 'on', False, 'site-wide respects exclusion', vary=False)
    view('/?slim=1', None, True, 'explicit selection unchanged', vary=False)
    option('slim_force_sitewide', False)
    wp('option', 'delete', 'slim_respect_save_data')
    view('/', 'on', True, 'upgrade without saved setting defaults on')
    option('slim_respect_save_data', True)
    wp('plugin', 'deactivate', plugin)
    wp('plugin', 'activate', plugin)
    check(evaluate('echo get_option("slim_respect_save_data") ? "on" : "off";') == 'on', 'saved on survives reactivation')
    # Exercise the actual WordPress Settings API form, including saving an unchecked default-on checkbox.
    jar = http.cookiejar.CookieJar()
    client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    request('/wp-login.php', client=client)
    request('/wp-login.php', client=client, data=urllib.parse.urlencode({
        'log': 'slimtest', 'pwd': 'disposable-only-password', 'wp-submit': 'Log In',
        'redirect_to': base + '/wp-admin/', 'testcookie': '1'}).encode())
    code, _, form = request('/wp-admin/options-general.php?page=' + plugin, client=client)
    check(code == 200 and 'Honor Save-Data requests' in form, 'administrator sees new setting')
    _, headers, body = request('/wp-admin/', 'on', client=client)
    check('name="slim-profile"' not in body and "default-src 'none'" not in headers.get('Content-Security-Policy', ''), 'admin with hint unaffected')
    def save_setting(value):
        _, _, form = request('/wp-admin/options-general.php?page=' + plugin, client=client)
        nonce = re.search(r'name="_wpnonce" value="([^"]+)"', form).group(1)
        payload = {'option_page': plugin, 'action': 'update', '_wpnonce': nonce,
                   '_wp_http_referer': '/wp-admin/options-general.php?page=' + plugin,
                   'slim_respect_save_data': str(value), 'slim_force_sitewide': '0', 'slim_noindex': '1'}
        code, _, body = request('/wp-admin/options.php', client=client,
                               data=urllib.parse.urlencode(payload).encode())
        check(code == 200 and 'Error' not in body[:100], 'settings form submission ' + str(value))
    save_setting(0)
    view('/', 'on', False, 'unchecked setting persists', vary=False)
    save_setting(1)
    view('/', 'on', True, 'checked setting persists')
    mu.write_text("<?php add_filter('wp_headers', function ($h) { $h['vary'] = 'Accept-Encoding, sAvE-dAtA'; return $h; }, 1);")
    _, headers, _ = request('/')
    check(headers.get('vary', '').lower().count('save-data') == 1, 'case-insensitive Vary deduplication')
    mu.write_text("<?php add_filter('wp_headers', function ($h) { $h['Vary'] = '*'; return $h; }, 1);")
    _, headers, _ = request('/')
    check(headers.get('Vary') == '*', 'wildcard Vary preserved: ' + repr(headers.get('Vary')))
    # Uninstall removes plugin settings, retaining the established post metadata behavior.
    wp('plugin', 'deactivate', plugin)
    wp('plugin', 'uninstall', plugin, '--skip-delete')
    check(evaluate('echo get_option("slim_respect_save_data", "missing");') == 'missing', 'uninstall cleans new option')
    check(evaluate('echo get_post_meta(' + str(ids['excluded']) + ', "_slim_disable", true);') == '1', 'uninstall retains per-post metadata')
    print(f'All {checks} checks passed.')
finally:
    server.terminate()
    server.wait()
    mu.unlink(missing_ok=True)
    router_path.unlink(missing_ok=True)
    log.close()
