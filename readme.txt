=== SLIM Low Bandwidth Mode ===
Contributors: cqueern
Tags: performance, accessibility, minimal, text-only, security
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.27
License: MIT
License URI: https://opensource.org/license/mit/

Serve your WordPress site in SLIM mode — single-request, text-first, and network-resilient.

== Description ==
SLIM Low Bandwidth Mode helps your WordPress site serve a simplified, script-free, text-first representation of posts and pages intended for slow, unreliable, or constrained networks.

SLIM can be applied automatically in two ways:

* Honor Save-Data requests (enabled by default): serve SLIM only for eligible requests that include Save-Data: on, reflecting the visitor's preference for reduced data use.
* Force SLIM mode site-wide (disabled by default): serve SLIM for all eligible front-end requests, whether or not they include the header.

Manage both options under Settings → SLIM Low Bandwidth Mode. Each automatic mode respects the post/page editor's "Disable SLIM for this content" setting. The separate "Noindex SLIM views" option is enabled by default and controls whether SLIM documents include noindex,follow.

Save-Data support works per request over HTTP or HTTPS, without redirects or visitor-preference cookies. Saved administrator choices are preserved through updates and deactivation/reactivation. See the caching FAQ before deploying to a site with a page cache or CDN.

SLIM Low Bandwidth Mode is provided "as is", without warranty of any kind. See the included LICENSE file for details.

Project homepage: https://github.com/cqueern/slim-low-bandwidth-mode

No Warranty: This plugin is provided "AS IS", without warranty of any kind, express or implied. Use at your own risk.

== Frequently Asked Questions ==

= How does Save-Data support work? =

While the plugin is active, visitors sending Save-Data: on receive the same SLIM representation as site-wide mode, without redirects. The header value is case-insensitive. Requests without the header, or with any other value, use the normal site unless site-wide mode or explicit SLIM selection applies. The decision is made for every request over HTTP or HTTPS; no cookie or remembered visitor preference is used.

The "Honor Save-Data requests" setting defaults to enabled on both fresh installations and upgrades that have no saved setting. Saved on/off choices survive updates and deactivation/reactivation. Disabling it only disables header-triggered SLIM; site-wide mode remains independent. Per-content "Disable SLIM" exclusions apply to both automatic modes. Explicit SLIM endpoint behavior is unchanged. Admin, login, AJAX, cron, REST, feeds, trackbacks, robots, and favicon requests are excluded from automatic SLIM rendering.

= What if my site uses caching? =

With Save-Data support enabled, eligible front-end responses include Vary: Save-Data on both the regular and SLIM versions, preserving existing Vary values. Your page cache, hosting cache, reverse proxy, or CDN must forward Save-Data to WordPress and keep separate cached versions based on that header. If it cannot, bypass page caching for these pages. Some caches serve responses before WordPress runs or ignore Vary; the plugin cannot configure every external cache automatically.

Purge existing cached pages when installing this update, when toggling either automatic mode, or when changing per-content exclusions. Verify the same URL with and without Save-Data: on in both request orders to ensure the versions do not get mixed. Browser support for sending Save-Data varies; no header does not necessarily mean a visitor has no data-saving preference.

= When would I use this? =

When your readers have limited bandwidth and it is important they can access your writing, SLIM Low Bandwidth Mode makes it much more likely they can read what you publish.

= What about all my embedded media like images or videos? =

This plugin is most useful when embedded media is nice to have, not must have. 

= Will search engines index my regular content or the SLIM versions? =

You can decide whether search engines index SLIM versions of your content in the Settings.

== Screenshots ==

1. The admin panel for SLIM Low Bandwidth Mode under Settings
2. Example of a WordPress blog post before SLIM Low Bandwidth Mode is enabled
2. Example of the same WordPress blog post after SLIM Low Bandwidth Mode is enabled

== Installation ==
1. Upload the plugin ZIP via Plugins → Add New → Upload Plugin, or upload the `slim-low-bandwidth-mode` folder to `/wp-content/plugins/`.
2. Activate SLIM Low Bandwidth Mode through the Plugins menu.
3. Save-Data support defaults to enabled while the plugin is active, including after an upgrade from a version without this setting. A previously saved on/off choice is preserved.
4. Go to Settings → SLIM Low Bandwidth Mode to change "Honor Save-Data requests", enable site-wide SLIM, or configure noindex behavior.
5. If a page cache or CDN is in use, follow the caching FAQ and purge existing cached pages before verifying the regular and SLIM versions.

== Changelog ==
= 0.1.27 =
* Add enabled-by-default, per-request Save-Data support with an independent administrator setting and per-content exclusions.
* Preserve saved preferences across updates and reactivation; remove the new option on uninstall.
* Vary regular and SLIM responses by Save-Data while preserving existing Vary values; document cache requirements.
* Exclude non-page requests from automatic SLIM and defer head cleanup until the query is resolved.

= 0.1.12 =
* Fix: show post/page titles in SLIM singular views.
* Add Home link + SLIM notice to singular views.

= 0.1.10 =
* Enhancement: Add an extra blank line between homepage entries in SLIM mode.

== Upgrade Notice ==
= 0.1.27 =
Save-Data support defaults to enabled for new installations and upgrades without a saved setting. Existing on/off choices are preserved. Review Settings → SLIM Low Bandwidth Mode and the caching FAQ; purge cached pages when deploying this update.

= 0.1.10 =
Minor formatting improvement in SLIM mode output.

= 0.1.23 =
* Added sanitization callbacks for settings registered via register_setting().

= 0.1.24 =
* Fix: add sanitize_callback for registered settings and make uninstall/delete safe.
* Fix: load plugin textdomain from /languages.
