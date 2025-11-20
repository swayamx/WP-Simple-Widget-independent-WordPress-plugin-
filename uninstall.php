<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
global $wpdb;
$table = $wpdb->prefix . 'wsw_items';
$wpdb->query("DROP TABLE IF EXISTS {$table}");
