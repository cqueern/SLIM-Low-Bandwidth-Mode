<?php
/**
 * Uninstall handler for SLIM Low Bandwidth Mode.
 *
 * Keep this file self-contained. WordPress may execute it without loading the main plugin file.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Options.
delete_option( 'slim_force_sitewide' );
delete_option( 'slim_noindex' );

// If you ever add network-wide options, also delete_site_option() here.
