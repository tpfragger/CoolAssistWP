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

    // Create necessary pages
    coolassist_create_pages();
}

function coolassist_create_pages() {
    $pages = array(
        'coolassist-login' => array(
            'title' => 'CoolAssist Login',
            'content' => '[coolassist_login]'
        ),
        'coolassist-ai' => array(
            'title' => 'CoolAssist AI',
            'content' => '[coolassist_ai]'
        )
    );

    foreach ($pages as $slug => $page) {
        if (null === get_page_by_path($slug)) {
            wp_insert_post(array(
                'post_title' => $page['title'],
                'post_content' => $page['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $slug
            ));
        }
    }
}
