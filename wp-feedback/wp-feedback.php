<?php
/*
Plugin Name: WP Feedback
Plugin URI: https://example.com/wp-feedback
Description: Allow users to vote on articles.
Version: 1.0
Author: Zeeshan Rasool
Author URI: https://example.com/author-website
Text Domain: wp-feedback
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Enqueue JavaScript and pass variables
function wp_feedback_enqueue_scripts() {
    wp_enqueue_script('wp-feedback', plugin_dir_url(__FILE__) . 'js/wp-feedback.js', array('jquery'), '1.0', true);

    // Pass variables to JavaScript
    wp_localize_script('wp-feedback', 'wp_feedback_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp-feedback-nonce')
    ));
}

add_action('wp_enqueue_scripts', 'wp_feedback_enqueue_scripts');

// Include necessary files and create instances
require_once(plugin_dir_path(__FILE__) . 'class-wp-feedback.php');
$wp_feedback = new WP_Feedback();
