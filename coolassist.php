<?php
/**
 * Plugin Name: CoolAssist
 * Plugin URI: https://github.com/tpfragger/CoolAssistWP
 * Description: An AI-powered assistant for AC repair technicians
 * Version: 1.0.0
 * Author: Thomas Pinnola
 * Author URI: https://pinnola.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coolassist
 */

function coolassist_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'coolassist_start_session');

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('COOLASSIST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COOLASSIST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once COOLASSIST_PLUGIN_DIR . 'includes/class-coolassist.php';
require_once COOLASSIST_PLUGIN_DIR . 'includes/class-coolassist-user.php';
require_once COOLASSIST_PLUGIN_DIR . 'includes/class-coolassist-manual.php';

// Initialize the plugin
function coolassist_init() {
    $coolassist = new CoolAssist();
    $coolassist->init();
}
add_action('plugins_loaded', 'coolassist_init');

// Activation hook
register_activation_hook(__FILE__, 'coolassist_activate');

function coolassist_activate() {
    global $wpdb;
    
    // Create users table
    $table_name = $wpdb->prefix . 'coolassist_users';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username varchar(50) NOT NULL,
        password varchar(255) NOT NULL,
        name varchar(100) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY username (username)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Create AC manuals table
    $table_name = $wpdb->prefix . 'coolassist_manuals';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        model_number varchar(100) NOT NULL,
        file_name varchar(255) NOT NULL,
        file_path varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($sql);

    // Create CoolAssist page
    coolassist_create_pages();

    // Flush rewrite rules
    flush_rewrite_rules();
}

function coolassist_create_pages() {
    $coolassist_page = array(
        'post_title'    => 'CoolAssist',
        'post_content'  => '[coolassist_page]',
        'post_status'   => 'publish',
        'post_author'   => 1,
        'post_type'     => 'page',
    );

    if (null === get_page_by_path('coolassist')) {
        wp_insert_post($coolassist_page);
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'coolassist_deactivate');

function coolassist_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
