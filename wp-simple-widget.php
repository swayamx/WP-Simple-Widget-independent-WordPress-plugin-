<?php
/**
 * Plugin Name: WP Simple Widget
 * Description: Minimal plugin that stores mini items in its own table and offers a shortcode [simple_widget_recent].
 * Version: 0.1
 * Author: You
 */

defined('ABSPATH') or die('No script kiddies please');

global $wpdb;
define('WSW_TABLE', $wpdb->prefix . 'wsw_items');

// Activation: create table
function wsw_activate() {
    global $wpdb;
    $table = WSW_TABLE;
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(200) NOT NULL,
        body text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'wsw_activate');

// Admin menu
function wsw_admin_menu() {
    add_menu_page('WSW Items', 'WSW Items', 'manage_options', 'wsw-items', 'wsw_items_page');
}
add_action('admin_menu', 'wsw_admin_menu');

// Admin page callback
function wsw_items_page() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;
    $table = WSW_TABLE;

    if (isset($_POST['wsw_action']) && $_POST['wsw_action'] === 'add') {
        check_admin_referer('wsw_add_item');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $body = wp_kses_post($_POST['body'] ?? '');
        if ($title && $body) {
            $wpdb->insert($table, ['title' => $title, 'body' => $body]);
            echo '<div class="updated"><p>Item added.</p></div>';
        } else {
            echo '<div class="error"><p>Title and body are required.</p></div>';
        }
    }

    // fetch last 20 items
    $items = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 20");

    ?>
    <div class="wrap">
      <h1>WSW Items</h1>
      <form method="post">
        <?php wp_nonce_field('wsw_add_item'); ?>
        <table class="form-table">
          <tr><th>Title</th><td><input name="title" style="width:100%" required></td></tr>
          <tr><th>Body</th><td><textarea name="body" rows="5" style="width:100%" required></textarea></td></tr>
        </table>
        <input type="hidden" name="wsw_action" value="add">
        <button class="button button-primary">Add Item</button>
      </form>

      <h2>Recent items</h2>
      <table class="widefat">
        <thead><tr><th>Title</th><th>Created</th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr><td><?= esc_html($it->title) ?></td><td><?= esc_html($it->created_at) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
}

// Shortcode to render recent items
function wsw_shortcode($atts) {
    global $wpdb;
    $table = WSW_TABLE;
    $atts = shortcode_atts(['limit' => 5], $atts, 'simple_widget_recent');
    $limit = (int)$atts['limit'];
    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit));

    if (!$rows) return '<p>No items found.</p>';
    $out = '<ul class="wsw-list">';
    foreach ($rows as $r) {
        $out .= '<li><strong>' . esc_html($r->title) . '</strong><div>' . wp_kses_post($r->body) . '</div></li>';
    }
    $out .= '</ul>';
    return $out;
}
add_shortcode('simple_widget_recent', 'wsw_shortcode');
