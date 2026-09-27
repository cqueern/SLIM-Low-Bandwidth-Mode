# Working on SLIM Low Bandwidth Mode

## Purpose and scope

This file guides coding agents working anywhere in this repository. Read it alongside
`readme.txt`, `security.md`, and the code affected by the task. Keep this guide current
when architecture, behavior, or verification procedures change.

SLIM means **Structured Low-bandwidth Information Markup**. This WordPress plugin
provides a text-first, script-free representation of a site's content for slow,
unreliable, or constrained networks. The intended rendering profile is a single
document request with no external assets. Preserve useful text, semantic structure,
and navigation while keeping bandwidth and dependencies small.

This guide was established from version 0.1.26, commit
`e8d7bddf500149f89b64679b4f340524832c20c7`. Recheck the current source before relying on
version-specific observations below. The project is MIT licensed. The declared
minimums are WordPress 6.5 and PHP 8.0; the readme declares testing through WordPress
7.0, which is metadata rather than evidence of a test run in your environment.

## Repository map

- `slim-low-bandwidth-mode.php`: plugin bootstrap, constants, rewrite endpoint,
  settings page, request detection, post metadata/editor box, HTML/CSS cleanup,
  excerpt helpers, template routing, and response headers.
- `templates/slim.php`: complete SLIM HTML document and view-specific rendering.
  It deliberately omits `wp_head()` and `wp_footer()` to avoid injected assets.
- `uninstall.php`: self-contained uninstall handler; currently deletes the two
  plugin options and leaves post metadata in place.
- `readme.txt`: WordPress plugin metadata, installation instructions, and changelog.
- `security.md`: security support, private reporting, and privacy policy.
- `languages/index.php`: placeholder; no translation catalogs are currently present.
- `assets/`: plugin listing banner, icon, and screenshots, not front-end dependencies.
- `LICENSE`: MIT license.

There is currently no Composer/npm manifest, build pipeline, CI workflow, or
automated test suite in the repository. Do not invent commands for nonexistent
tooling. The PHP implementation uses DOM/libxml and mbstring functionality; verify
those extensions are available when setting up a runtime.

## Current behavior and interfaces

- Settings live under **Settings → SLIM Low Bandwidth Mode** and require
  `manage_options`. The WordPress Settings API supplies form protection.
- `slim_force_sitewide` is a boolean option, default false. When enabled, eligible
  front-end requests use the SLIM template without redirects.
- `slim_noindex` is a boolean option, default true. SLIM documents emit
  `noindex,follow` when enabled and include a canonical link.
- The `slim` rewrite endpoint is registered with `EP_ALL`. Activation registers
  it and flushes rewrite rules; deactivation also flushes rules.
- `slim_low_bandwidth_mode_is_slim_view()` first checks a truthy `slim` query
  variable, then the site-wide option, front-end eligibility, and singular opt-out.
  Explicit endpoint selection currently precedes the opt-out check.
- `_slim_disable` is boolean post metadata, exposed through REST, with an editor
  box on public post types. The normal save handler checks a nonce, autosave state,
  and `edit_post` capability. Opt-out is evaluated for singular requests.
- The posts index uses separate queries for up to 50 published posts and 50
  published pages, newest first. A static front page follows singular rendering.
- Singular views display title, publication date, and filtered content.
  Archive/search views display titles, dates, and plain excerpts capped at 220
  characters. Empty and 404 views have minimal messages.
- Home navigation and an explanatory SLIM notice are rendered by the template.
  An administrator dashboard link appears on the posts index for authorized users.
- The `the_content` filter at priority 20 runs **KSES → DOM cleanup → KSES**.
  Cleanup removes scripts/media, restricts inline styles, removes selected tracking
  query parameters, and normalizes forms. The template additionally wraps the
  filtered content in `wp_kses_post()`.
- The HTML and CSS extension hooks are
  `slim_low_bandwidth_mode_allowed_html` and
  `slim_low_bandwidth_mode_allowed_css_properties`.
  Default CSS properties are color, font-family, text-align, font-weight, and
  font-style.
- SLIM responses attempt to send a restrictive Content-Security-Policy and
  `X-Content-Type-Options: nosniff`. Verify actual headers when changing routing;
  request detection runs at several different WordPress lifecycle stages.

## Implementation guidance

- Preserve the lightweight rendering profile. Do not add front-end JavaScript,
  remote fonts, stylesheets, media fetches, analytics, telemetry, or new dependencies
  as incidental changes. Discuss any requested change that alters this profile.
- Keep normal theme rendering and administrative/API flows working when SLIM does
  not apply. Exercise per-content opt-outs when changing routing.
- Retain PHP 8.0 compatibility unless the task explicitly changes the minimum.
  Use WordPress APIs and follow the surrounding style; avoid unrelated reformatting.
- Prefix new global functions and hooks consistently with
  `slim_low_bandwidth_mode_`. Existing `SLIMPRESS_*` constants are legacy public
  names; avoid renaming them casually.
- Sanitize incoming settings and metadata, check appropriate capabilities and
  nonces for writes, and escape output for its HTML, attribute, or URL context.
  Preserve WordPress content access restrictions, including password protection.
- Keep the KSES/DOM cleanup boundary and restrictive response policy intact.
  Changes to allowlists must account for scripts, unsafe URL schemes, event
  attributes, CSS resource loading, and third-party content filters.
- Wrap new user-facing strings in translation functions using the
  `slim-low-bandwidth-mode` text domain.
- Keep uninstall self-contained behind `WP_UNINSTALL_PLUGIN`; do not assume the
  main plugin is loaded. Explicitly decide data retention when introducing options
  or metadata.
- Flush rewrite rules only during appropriate lifecycle/configuration changes,
  not on ordinary requests.
- For releases, synchronize the plugin header version, `SLIMPRESS_VERSION`, and
  readme Stable tag, and add an accurate changelog entry. Documentation-only edits
  do not require a release bump.
- Follow `security.md` for vulnerability disclosure; do not publish vulnerability
  details in public issues.

## Verification

For documentation-only changes, verify statements against source and review the
diff. Runtime changes need checks appropriate to the affected behavior.

From the repository root, syntax-check changed PHP files, or all current PHP files:

```sh
php -l slim-low-bandwidth-mode.php
php -l templates/slim.php
php -l uninstall.php
php -l languages/index.php
```

Syntax checks do not replace WordPress integration testing. On a disposable local
WordPress installation with DOM and mbstring available, exercise relevant cases:

1. Activate/deactivate the plugin; save both settings; confirm normal rendering
   with site-wide mode off and SLIM rendering with it on.
2. Test a posts index, static front page, post, page, archive, search (including no
   results), and 404. Check titles, dates, Unicode text, excerpts, links, and notice.
3. Test singular opt-out and explicit endpoint requests separately, with both
   pretty and plain permalinks as applicable. Check navigation between views.
4. Test administrator and anonymous views, protected/private content, and relevant
   custom post types. Confirm admin, login, AJAX, cron, and REST remain usable.
5. With content containing blocks, shortcodes, media, inline styles, links, and
   forms, inspect the generated HTML and browser Network panel for unwanted assets
   or executable content. Check actual response headers and browser CSP behavior.
6. Check noindex on/off, canonical URLs, archive pagination, and link query strings.
   If caches/CDNs are present, verify that mode changes and opt-outs are reflected.
7. For storage/lifecycle changes, verify uninstall cleanup on a disposable site.

If adding regression tests, make them cover meaningful externally observable
behavior and document their setup and invocation. Report exactly which checks ran
and which could not run; do not claim runtime validation from source inspection.

## Baseline caveats to investigate when relevant

These are source observations, not a mandate to fix unrelated code or a claim of
validated vulnerabilities:

- Empty rewrite endpoint values can be falsey. Confirm that a bare `/slim/`
  request is recognized; registration alone does not prove view detection works.
- Explicit endpoint selection bypasses the site-wide branch's front-end and
  opt-out checks. Mode detection also occurs before all query state is available.
- Archive pagination currently strips all tags from `paginate_links()`, leaving
  text rather than clickable navigation. The posts index is capped at 50 entries
  per section and has no pagination.
- Home and content links use regular URLs, so endpoint-only navigation may leave
  SLIM mode. Tracking-query cleanup reconstructs URLs without all original URL
  components; test protocol-relative URLs and explicit ports before modifying it.
- A closing comment claims a textdomain loader exists, but no loader is registered
  in the current main file. The document language is hard-coded to `en`, and some
  strings are not translated. Verify localization rather than trusting comments.
- Content filters after priority 20 can modify already-cleaned output. Treat the
  full filter chain, template escaping, and delivered CSP as one behavior to test.
- Uninstall deletes options but retains `_slim_disable`. Do not silently broaden
  deletion without considering existing user data.
- The readme changelog is incomplete and has entries under Upgrade Notice.
  Source and current metadata take precedence over stale narrative descriptions.
