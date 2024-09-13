<?php
/**
 * Plugin Name: CoolAssist
 * Plugin URI: https://github.com/yourusername/coolassist
 * Description: An AI-powered assistant for AC repair technicians
 * Version: 1.0.0
 * Author: Thomas Pinnola
 * Author URI: https://pinnola.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coolassist
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('COOLASSIST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COOLASSIST_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once COOLASSIST_PLUGIN_DIR . 'includes/class-coolassist.php';

// Initialize the plugin
function coolassist_init() {
    $coolassist = new CoolAssist();
    $coolassist->init();
}
add_action('plugins_loaded', 'coolassist_init');

// Activation hook
register_activation_hook(__FILE__, 'coolassist_activate');

function coolassist_activate() {
    // Create AC technician role
    add_role(
        'ac_technician',
        'AC Technician',
        array(
            'read' => true,
            'access_coolassist' => true,
        )
    );

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
    // Remove AC technician role
    remove_role('ac_technician');

    // Flush rewrite rules
    flush_rewrite_rules();
}
