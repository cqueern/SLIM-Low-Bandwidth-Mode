<?php
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }
delete_option('slim_force_sitewide');
delete_option('slim_noindex');
// Keep _slim_disable post meta so intent persists if plugin is reinstalled.
