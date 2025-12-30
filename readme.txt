=== SLIMPress – SLIM Compliance Mode ===
Contributors: cqueern
Tags: performance, accessibility, minimal, text-only, security
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.1.22
License: MIT
License URI: https://opensource.org/license/mit/

Serve your WordPress site in SLIM mode — single-request, text-first, and network-resilient.

== Description ==
SLIMPress helps your WordPress site serve a simplified, script-free, text-first representation of posts and pages intended for slow, unreliable, or constrained networks.

It adds a Settings → SLIMPress page where administrators can enable SLIM mode and configure a small set of options.

SLIMPress is provided "as is", without warranty of any kind. See the included LICENSE file for details.

Project homepage: https://github.com/cqueern/SLIMPress

No Warranty: This plugin is provided "AS IS", without warranty of any kind, express or implied. Use at your own risk.

== Installation ==
1. Upload the plugin ZIP via Plugins → Add New → Upload Plugin, or upload the `slimpress` folder to `/wp-content/plugins/`.
2. Activate SLIMPress through the Plugins menu.
3. Go to Settings → SLIMPress and enable SLIM mode.

== Changelog ==
= 0.1.12 =
* Fix: show post/page titles in SLIM singular views.
* Add Home link + SLIM notice to singular views.

= 0.1.10 =
* Enhancement: Add an extra blank line between homepage entries in SLIM mode.

== Upgrade Notice ==
= 0.1.10 =
Minor formatting improvement in SLIM mode output.