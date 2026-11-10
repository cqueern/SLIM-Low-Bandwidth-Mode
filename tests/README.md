# Save-Data integration checks

Run only on a disposable WordPress installation. The script changes plugin settings,
creates/deletes published fixtures whose titles contain `SLIM`, changes permalink
and home-page settings, and finishes by uninstalling SLIM without deleting its files.
It verifies that uninstall removes options while retaining existing post metadata.

Prerequisites:

- PHP with DOM and mbstring, Python 3, and WP-CLI (`wp-cli.phar`).
- A working WordPress installation with any supported database. SQLite Database
  Integration can be used for a disposable installation without a MySQL server.
- This checkout installed or symlinked as
  `wp-content/plugins/slim-low-bandwidth-mode`.
- `WP_ENVIRONMENT_TYPE` set to `local`, the site URL set to
  `http://127.0.0.1:8765`, and port 8765 free.
- A disposable administrator account named `slimtest`, password
  `disposable-only-password`. Never reuse these credentials outside the test site.

From the plugin checkout:

```sh
python3 tests/save-data.py /absolute/path/to/wordpress /absolute/path/to/wp-cli.phar
```

The script starts and stops PHP's local web server. It tests actual HTTP responses
and authenticated Settings API submissions: missing/invalid/case-varied headers,
posts/pages/home/search/archive/404, static home, plain query URLs, content exclusions,
password protection, no added preference cookies, consecutive requests with different
headers, existing Vary values, exclusions for admin/login/REST/AJAX/cron/feeds/robots/
favicon, independent site-wide mode, default-on upgrade behavior, stored choices across
reactivation, saving unchecked and checked settings, and uninstall cleanup.

This is origin-side integration coverage. It does not prove compatibility with an
external cache/CDN or browser-specific header generation. Verify both versions in both
request orders with any cache used in production. The HTTP tests intentionally exercise
plain HTTP; the implementation makes no scheme-dependent decision.

## Validation record

On November 9, 2026, the HTTP/settings suite passed on WordPress 6.5 and 7.1.4
with PHP 8.5.12 and SQLite Database Integration 1.8.0. All four plugin PHP files
also passed `php -l`. PHP 8.0 and external hosting caches were not exercised.
