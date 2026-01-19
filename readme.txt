=== SLIM Low Bandwidth Mode ===
Contributors: cqueern
Tags: performance, accessibility, minimal, text-only, security
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.1.25
License: MIT
License URI: https://opensource.org/license/mit/

Serve your WordPress site in SLIM mode — single-request, text-first, and network-resilient.

== Description ==
SLIM Low Bandwidth Mode helps your WordPress site serve a simplified, script-free, text-first representation of posts and pages intended for slow, unreliable, or constrained networks.

It adds a Settings → SLIM Low Bandwidth Mode page where administrators can enable SLIM mode and configure a small set of options.

SLIM Low Bandwidth Mode is provided "as is", without warranty of any kind. See the included LICENSE file for details.

Project homepage: https://github.com/cqueern/slim-low-bandwidth-mode

No Warranty: This plugin is provided "AS IS", without warranty of any kind, express or implied. Use at your own risk.

== Frequently Asked Questions ==

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
3. Go to Settings → SLIM Low Bandwidth Mode and enable SLIM mode.

== Changelog ==
= 0.1.12 =
* Fix: show post/page titles in SLIM singular views.
* Add Home link + SLIM notice to singular views.

= 0.1.10 =
* Enhancement: Add an extra blank line between homepage entries in SLIM mode.

== Upgrade Notice ==
= 0.1.10 =
Minor formatting improvement in SLIM mode output.

= 0.1.23 =
* Added sanitization callbacks for settings registered via register_setting().

= 0.1.24 =
* Fix: add sanitize_callback for registered settings and make uninstall/delete safe.
* Fix: load plugin textdomain from /languages.
