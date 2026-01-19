<?php
/**
 * Plugin Name: SLIM Low Bandwidth Mode
 * Plugin URI: https://github.com/cqueern/slim-low-bandwidth-mode
 * Description: Serve your WordPress site in SLIM mode — single-request, text-first, and network-resilient.
 * Version: 0.1.25
 * Author: SLIM Project
 * Author URI: https://github.com/cqueern
 * License: MIT
 * License URI: https://opensource.org/license/mit/
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Text Domain: slim-low-bandwidth-mode
 * Domain Path: /languages
 */
function slim_low_bandwidth_mode_sanitize_bool( $value ) {
	if ( function_exists( 'rest_sanitize_boolean' ) ) {
		return rest_sanitize_boolean( $value );
	}

	// Common checkbox / form values.
	if ( is_string( $value ) ) {
		$value = strtolower( trim( $value ) );
		if ( in_array( $value, array( '1', 'true', 'on', 'yes' ), true ) ) {
			return true;
		}
		if ( in_array( $value, array( '0', 'false', 'off', 'no', '' ), true ) ) {
			return false;
		}
	}

	return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

if (!defined('ABSPATH')) { exit; }
define('SLIMPRESS_VERSION', '0.1.10');
define('SLIMPRESS_PATH', plugin_dir_path(__FILE__));

// ---------------- Helpers ----------------
function slim_low_bandwidth_mode_notice_html() {
  return '<p><em>' . esc_html__('This content has been temporarily modified so it is easier to access when network conditions are not ideal.', 'slim-low-bandwidth-mode') . '</em></p>';
}

function slim_low_bandwidth_mode_home_link_html() {
  return '<p><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'slim-low-bandwidth-mode') . '</a></p>';
}

function slim_low_bandwidth_mode_published_date_html($post_id = null) {
  $post_id = $post_id ?: get_the_ID();
  $date = get_the_date('F j, Y', $post_id);
  if (!$date) return '';
  return '<p><small>' . esc_html__('Published:', 'slim-low-bandwidth-mode') . ' ' . esc_html($date) . '</small></p>';
}

// ---------------- Activation / Deactivation ----------------
function slim_low_bandwidth_mode_register_endpoint() {
  add_rewrite_endpoint('slim', EP_ALL);
}

register_activation_hook( __FILE__, function () {
  slim_low_bandwidth_mode_register_endpoint();
  flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
  flush_rewrite_rules();
} );
// ---------------- Settings (options) ----------------
add_action('admin_init', function () {
  register_setting( 'slim-low-bandwidth-mode', 'slim_force_sitewide', ['type' => 'boolean', 'default' => false, 'sanitize_callback' => 'slim_low_bandwidth_mode_sanitize_bool'] );
  register_setting( 'slim-low-bandwidth-mode', 'slim_noindex', ['type' => 'boolean', 'default' => true, 'sanitize_callback' => 'slim_low_bandwidth_mode_sanitize_bool'] );
});

// ---------------- Dedicated Admin Page ----------------
add_action('admin_menu', function () {
  add_options_page(
    __('SLIM Low Bandwidth Mode', 'slim-low-bandwidth-mode'),
    __('SLIM Low Bandwidth Mode', 'slim-low-bandwidth-mode'),
    'manage_options',
    'slim-low-bandwidth-mode',
    'slim_low_bandwidth_mode_render_admin_page'
  );
});

function slim_low_bandwidth_mode_render_admin_page() {
  if (!current_user_can('manage_options')) return;
  $repo = esc_url('https://github.com/cqueern/slim-low-bandwidth-mode');
  ?>
  <div class="wrap">
    <h1><?php echo esc_html__('SLIM Low Bandwidth Mode Settings', 'slim-low-bandwidth-mode'); ?></h1>

    <p>
      <?php echo esc_html__('SLIM (Structured Low-bandwidth Information Markup) is a text-first publishing profile intended to keep content usable when networks are slow, unreliable, or constrained.', 'slim-low-bandwidth-mode'); ?>
      <?php echo esc_html__('SLIM Low Bandwidth Mode helps your WordPress site serve a simplified, single-request, script-free version of your pages when SLIM mode is enabled.', 'slim-low-bandwidth-mode'); ?>
      <a href="<?php echo esc_url( $repo ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Project homepage', 'slim-low-bandwidth-mode'); ?></a>
    </p>

    <form method="post" action="options.php">
      <?php settings_fields('slim-low-bandwidth-mode'); ?>

      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><?php echo esc_html__('Force SLIM mode site-wide', 'slim-low-bandwidth-mode'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="slim_force_sitewide" value="1" <?php checked((bool) get_option('slim_force_sitewide', false), true); ?>>
              <?php echo esc_html__('Render all front-end pages with SLIM (no redirects)', 'slim-low-bandwidth-mode'); ?>
            </label>
            <p class="description"><?php echo esc_html__('Applies to posts, pages, archives, search, and 404s. wp-admin, login, and the REST API are not affected.', 'slim-low-bandwidth-mode'); ?></p>
          </td>
        </tr>

        <tr>
          <th scope="row"><?php echo esc_html__('Noindex SLIM views', 'slim-low-bandwidth-mode'); ?></th>
          <td>
            <label>
              <input type="checkbox" name="slim_noindex" value="1" <?php checked((bool) get_option('slim_noindex', true), true); ?>>
              <?php echo esc_html__('Add noindex,follow meta to SLIM-rendered pages', 'slim-low-bandwidth-mode'); ?>
            </label>
            <p class="description"><?php echo esc_html__('Recommended to avoid duplicate indexing while keeping canonical links.', 'slim-low-bandwidth-mode'); ?></p>
          </td>
        </tr>
      </table>

      <?php submit_button(); ?>
    </form>

    <hr>
    <h2><?php echo esc_html__('Per-post override', 'slim-low-bandwidth-mode'); ?></h2>
    <p><?php echo esc_html__('In the post/page editor sidebar, use the “SLIM” box to disable SLIM for that specific content.', 'slim-low-bandwidth-mode'); ?></p>
  </div>
  <?php
}

// ---------------- Endpoint and View Detection ----------------
add_action('init', 'slim_low_bandwidth_mode_register_endpoint');

function slim_low_bandwidth_mode_is_frontend() {
  $request_uri = ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) ?? '';
  return !is_admin() && !wp_doing_ajax() && !wp_doing_cron()
         && (empty($request_uri) || strpos($request_uri, '/wp-json/') !== 0)
         && !defined('REST_REQUEST');
}

function slim_low_bandwidth_mode_is_request_opted_out() {
  if (is_singular()) {
    $post_id = get_queried_object_id();
    if ($post_id) {
      return (bool) get_post_meta($post_id, '_slim_disable', true);
    }
  }
  return false;
}

function slim_low_bandwidth_mode_is_slim_view() {
  if (get_query_var('slim')) return true;
  if (get_option('slim_force_sitewide') && slim_low_bandwidth_mode_is_frontend() && !slim_low_bandwidth_mode_is_request_opted_out()) return true;
  return false;
}

// ---------------- Include pages on the home list in SLIM mode ----------------
add_action('pre_get_posts', function ($query) {
  if ( ! function_exists( 'slim_low_bandwidth_mode_is_slim_view' ) || ! slim_low_bandwidth_mode_is_slim_view() ) return;
  if (is_admin()) return;
  if (!$query->is_main_query()) return;

  if ($query->is_home()) {
    $query->set('post_type', ['post', 'page']);
    $query->set('orderby', 'date');
    $query->set('order', 'DESC');
  }
});

// ---------------- Per-post override ----------------
add_action('init', function () {
  register_post_meta('', '_slim_disable', [
    'type'              => 'boolean',
    'single'            => true,
    'show_in_rest'      => true,
    'auth_callback'     => function() { return current_user_can('edit_posts'); },
    'sanitize_callback' => 'rest_sanitize_boolean',
    'default'           => false,
  ]);
});

add_action('add_meta_boxes', function () {
  $post_types = get_post_types(['public' => true], 'names');
  foreach ($post_types as $pt) {
    add_meta_box('slim_disable_box', __('SLIM', 'slim-low-bandwidth-mode'), 'slim_low_bandwidth_mode_disable_box_render', $pt, 'side', 'high');
  }
});

function slim_low_bandwidth_mode_disable_box_render( $post ) {
  wp_nonce_field( 'slim_disable_save', 'slim_disable_nonce' );
  $slim_low_bandwidth_mode_disabled = get_post_meta( $post->ID, '_slim_disable', true ) ? 1 : 0;

  echo '<label><input type="checkbox" name="slim_disable" value="1" ';
  checked( $slim_low_bandwidth_mode_disabled, 1, true );
  echo '> ' . esc_html__( 'Disable SLIM for this content', 'slim-low-bandwidth-mode' ) . '</label>';
}

add_action( 'save_post', function ( $post_id ) {
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
    return;
  }

  if ( ! isset( $_POST['slim_disable_nonce'] ) ) {
    return;
  }

  $slim_low_bandwidth_mode_nonce = sanitize_text_field( wp_unslash( $_POST['slim_disable_nonce'] ) );
  if ( ! wp_verify_nonce( $slim_low_bandwidth_mode_nonce, 'slim_disable_save' ) ) {
    return;
  }

  if ( ! current_user_can( 'edit_post', $post_id ) ) {
    return;
  }

  $slim_low_bandwidth_mode_disable = isset( $_POST['slim_disable'] ) ? '1' : '0';
  update_post_meta( $post_id, '_slim_disable', $slim_low_bandwidth_mode_disable );
} );
// ---------------- KSES Allowlist (no images/media) ----------------
function slim_low_bandwidth_mode_allowed_html() {
  return [
    'a'      => ['href' => true, 'title' => true],
    'p'      => ['style' => true],
    'div'    => ['style' => true],
    'em'     => ['style' => true],
    'strong' => ['style' => true],
    'span'   => ['style' => true],
    'small'  => [],
    'nav'    => [],
    'h1' => ['style' => true], 'h2' => ['style' => true], 'h3' => ['style' => true],
    'h4' => ['style' => true], 'h5' => ['style' => true], 'h6' => ['style' => true],
    'ul' => [], 'ol' => [], 'li' => [],
    'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
    'form'  => ['action' => true, 'method' => true],
    'input' => ['type' => true, 'name' => true, 'value' => true],
  ];
}

function slim_low_bandwidth_mode_kses_sanitize($html) {
  return wp_kses($html, apply_filters('slim_low_bandwidth_mode_allowed_html', slim_low_bandwidth_mode_allowed_html()));
}

// ---------------- CSS Filter ----------------
function slim_low_bandwidth_mode_allowed_css_properties() {
  return apply_filters('slim_low_bandwidth_mode_allowed_css_properties', ['color','font-family','text-align','font-weight','font-style']);
}

function slim_low_bandwidth_mode_filter_inline_style($style) {
  $allowed = array_fill_keys(slim_low_bandwidth_mode_allowed_css_properties(), true);
  $out = [];
  foreach (explode(';', (string)$style) as $decl) {
    $decl = trim($decl);
    if ($decl === '') continue;
    $parts = explode(':', $decl, 2);
    if (count($parts) != 2) continue;
    $prop = strtolower(trim($parts[0]));
    $val  = trim($parts[1]);
    if (strpos($val, 'url(') !== false) continue;
    $val = preg_replace('/!important\b/i', '', $val);
    if (isset($allowed[$prop])) {
      $out[] = $prop . ':' . $val;
    }
  }
  return implode(';', $out);
}

// ---------------- DOM Cleanup (strip scripts + all media) ----------------
function slim_low_bandwidth_mode_dom_cleanup($html) {
  if ($html === '') return $html;

  $html = preg_replace('#<(script|iframe|embed|object|link|style|img|video|audio|source|picture|svg|canvas)\b[^>]*>.*?</\1>#is', '', $html);
  $html = preg_replace('#</?(script|iframe|embed|object|link|style|img|video|audio|source|picture|svg|canvas)\b[^>]*>#i', '', $html);

  $dom = new DOMDocument();
  libxml_use_internal_errors(true);
  $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
  libxml_clear_errors();
  $xpath = new DOMXPath($dom);

  foreach (['script','iframe','embed','object','link','style','video','audio','source','img','picture','svg','canvas'] as $tag) {
    foreach ($xpath->query('//' . $tag) as $node) {
      $node->parentNode->removeChild($node);
    }
  }

  foreach ($xpath->query('//*[@style or name()="a" or name()="form" or name()="input"]') as $el) {
    if ($el->hasAttribute('style')) {
      $clean = slim_low_bandwidth_mode_filter_inline_style($el->getAttribute('style'));
      if ($clean === '') $el->removeAttribute('style');
      else $el->setAttribute('style', $clean);
    }

    if ($el->nodeName === 'a') {
      $href = $el->getAttribute('href');
      if (stripos($href, 'javascript:') === 0) {
        $el->removeAttribute('href');
      } else {
        $p = wp_parse_url($href);
        if (!empty($p['query'])) {
          parse_str($p['query'], $q);
          foreach (array_keys($q) as $k) {
            if (preg_match('/^(utm_|fbclid|gclid|mc_eid|vero_id)/i', $k)) unset($q[$k]);
          }
          $new = (isset($p['scheme']) ? $p['scheme'].'://' : '')
               . (isset($p['host']) ? $p['host'] : '')
               . (isset($p['path']) ? $p['path'] : '')
               . (empty($q) ? '' : '?'.http_build_query($q))
               . (isset($p['fragment']) ? '#'.$p['fragment'] : '');
          if ($new !== '') $el->setAttribute('href', $new);
        }
      }
    }

    if ($el->nodeName === 'form') {
      $method = strtolower($el->getAttribute('method'));
      if ($method && !in_array($method, ['get','post'], true)) {
        $el->setAttribute('method', 'get');
      }
      if ($el->hasAttribute('action')) $el->removeAttribute('action');
    }

    if ($el->nodeName === 'input') {
      if (!$el->hasAttribute('type') || trim($el->getAttribute('type')) === '') {
        $el->setAttribute('type', 'text');
      }
    }
  }

  $out = $dom->saveHTML();
  $out = preg_replace('/^<\?xml.*?\?>/i', '', $out);
  return $out;
}

// ---------------- Archive/search excerpt helper ----------------
function slim_low_bandwidth_mode_plain_excerpt($post_id, $max_chars = 220) {
  $raw = get_the_excerpt($post_id);
  if (!$raw) $raw = get_post_field('post_content', $post_id);
  $text = strip_shortcodes(wp_strip_all_tags($raw, true));
  $text = preg_replace('/\s+/', ' ', trim($text));
  if (mb_strlen($text) > $max_chars) {
    $text = mb_substr($text, 0, $max_chars - 1) . '…';
  }
  return $text;
}

function slim_low_bandwidth_mode_context_title() {
  if (is_search())    return 'Search results for: ' . esc_html(get_search_query());
  if (is_category())  return 'Category: ' . single_cat_title('', false);
  if (is_tag())       return 'Tag: ' . single_tag_title('', false);
  if (is_author())    return 'Author: ' . get_the_author_meta('display_name', get_queried_object_id());
  if (is_year())      return 'Year: ' . get_query_var('year');
  if (is_month())     return 'Month: ' . get_the_date('F Y');
  if (is_day())       return 'Day: ' . get_the_date('F j, Y');
  if (is_post_type_archive()) return post_type_archive_title('', false);
  if (is_home())      return get_bloginfo('name');
  if (is_archive())   return 'Archive';
  if (is_404())       return 'Not found';
  return get_bloginfo('name');
}

// ---------------- Template routing ----------------
add_filter('template_include', function ($template) {
  if ( function_exists( 'slim_low_bandwidth_mode_is_slim_view' ) && slim_low_bandwidth_mode_is_slim_view() ) {
    return SLIMPRESS_PATH . 'templates/slim.php';
  }
  return $template;
}, 999);

// ---------------- Headers / Head hardening ----------------
add_action('send_headers', function () {
  if ( ! function_exists( 'slim_low_bandwidth_mode_is_slim_view' ) || ! slim_low_bandwidth_mode_is_slim_view() ) return;
  header("Content-Security-Policy: default-src 'none'; img-src 'none'; media-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'self'");
  header('X-Content-Type-Options: nosniff');
});

add_action('init', function () {
  if ( ! function_exists( 'slim_low_bandwidth_mode_is_slim_view' ) || ! slim_low_bandwidth_mode_is_slim_view() ) return;
  remove_action('wp_head', 'print_emoji_detection_script', 7);
  remove_action('wp_print_styles', 'print_emoji_styles');
  remove_action('wp_head', 'wp_oembed_add_discovery_links');
  remove_action('wp_head', 'rest_output_link_wp_head');
  remove_action('wp_head', 'wp_generator');
  remove_action('wp_head', 'wlwmanifest_link');
  remove_action('wp_head', 'rsd_link');
});

// ---------------- Content Filter (KSES → DOM → KSES + Notice + Home) ----------------
add_filter('the_content', function ($html) {
  if ( ! function_exists( 'slim_low_bandwidth_mode_is_slim_view' ) || ! slim_low_bandwidth_mode_is_slim_view() ) return $html;
  $html = slim_low_bandwidth_mode_kses_sanitize($html);
  $html = slim_low_bandwidth_mode_dom_cleanup($html);
  $html = slim_low_bandwidth_mode_kses_sanitize($html);
  // IMPORTANT: Date is printed by the SLIM template to avoid duplicates.
  return $html;
}, 20);

// (i18n loader is registered near the top of this file.)
