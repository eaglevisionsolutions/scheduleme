<?php
/**
 * Plugin Name: Schedule Me Booking Plugin
 * Plugin URI:  https://eaglevisionsolutions.ca/schedule-me-booking-plugin
 * Description: A custom multi-step booking form with Google Calendar and PayPal integration.
 * Version:     1.0.8
 * Author:      Eagle Vision Solutions
 * Author URI:  https://eaglevisionsolutions.ca/
 * Text Domain: schedule-me-booking
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'SCME_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCME_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SCME_VERSION', '1.0.8' );

// Include necessary classes and files
require_once SCME_PLUGIN_DIR . 'includes/class-booking-manager.php';
require_once SCME_PLUGIN_DIR . 'includes/class-google-calendar-api.php';
require_once SCME_PLUGIN_DIR . 'includes/class-paypal-ipn.php';
require_once SCME_PLUGIN_DIR . 'includes/class-form-handler.php';
require_once SCME_PLUGIN_DIR . 'includes/class-booking-submission-manager.php';
require_once SCME_PLUGIN_DIR . 'includes/class-booking-form-cpt.php';
require_once SCME_PLUGIN_DIR . 'includes/class-booking-form-metabox.php';
require_once SCME_PLUGIN_DIR . 'admin/settings-page.php';
require_once SCME_PLUGIN_DIR . 'admin/submissions-page.php';

/**
 * Plugin Activation Hook
 */
function scme_activate_plugin() {
    SCME_Booking_Manager::create_tables(); // Create custom database tables
    SCME_Booking_Submission_Manager::create_table();
    // Optionally, set default options
}
register_activation_hook( __FILE__, 'scme_activate_plugin' );

/**
 * Plugin Deactivation Hook
 */
function scme_deactivate_plugin() {
    // Optionally, clean up data or flush rewrite rules
}
register_deactivation_hook( __FILE__, 'scme_deactivate_plugin' );

/**
 * Enqueue scripts and styles for the front-end form.
 */
function scme_enqueue_scripts() {
    // Enqueue your custom CSS
    wp_enqueue_style( 'SCME-style', SCME_PLUGIN_URL . 'public/css/style.css', array(), SCME_VERSION );

    // Enqueue your custom JavaScript
    wp_enqueue_script( 'SCME-script', SCME_PLUGIN_URL . 'public/js/script.js', array( 'jquery' ), SCME_VERSION, true );

    // Pass data to JavaScript (e.g., AJAX URL, nonces)
    wp_localize_script( 'SCME-script', 'SCME_ajax_obj', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ), // For simple AJAX
        // OR, for REST API:
        // 'rest_url' => rest_url( 'your-plugin/v1/'),
        'nonce'    => wp_create_nonce( 'SCME_form_nonce' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'scme_enqueue_scripts' );

/**
 * Register custom REST API endpoints (preferred over admin-ajax.php for modern development)
 */
function scme_register_rest_routes() {
    $form_handler = new SCME_Form_Handler();
    // Endpoint for getting available slots
    register_rest_route( 'your-plugin/v1', '/get-available-slots', array(
        'methods'             => 'POST',
        'callback'            => array( $form_handler, 'handle_get_available_slots' ),
        'permission_callback' => '__return_true', // Implement proper permission checks later
    ));

    // Endpoint for initiating booking (creating tentative booking, redirect to PayPal)
    register_rest_route( 'your-plugin/v1', '/initiate-booking', array(
        'methods'             => 'POST',
        'callback'            => array( $form_handler, 'handle_initiate_booking' ),
        'permission_callback' => '__return_true', // Implement proper permission checks later
    ));
}
add_action( 'rest_api_init', 'scme_register_rest_routes' );


/**
 * Register the custom booking form shortcode.
 */
function scme_booking_form_shortcode() {
    ob_start();
    include SCME_PLUGIN_DIR . 'templates/booking-form.php';
    return ob_get_clean();
}
add_shortcode( 'your_custom_booking_form', 'scme_booking_form_shortcode' );

/**
 * Initialize PayPal IPN/Webhook Listener
 */
function scme_init_paypal_listener() {
    $paypal_ipn_handler = new SCME_PayPal_IPN();
    // Hook into WordPress for custom rewrite rules for IPN/Webhook listener
    add_action( 'init', array( $paypal_ipn_handler, 'add_rewrite_rules' ) );
    add_filter( 'query_vars', array( $paypal_ipn_handler, 'add_query_vars' ) );
    add_action( 'template_redirect', array( $paypal_ipn_handler, 'handle_request' ) );
}
add_action( 'plugins_loaded', 'scme_init_paypal_listener' ); // Ensure plugin classes are loaded


function scme_admin_enqueue_form_builder_script($hook) {
    global $post;
    // Only load on the booking form CPT edit/add screen
    $is_booking_form = false;
    if (
        ($hook === 'post.php' || $hook === 'post-new.php') &&
        isset($_GET['post_type']) && $_GET['post_type'] === 'scme_booking_form'
    ) {
        $is_booking_form = true;
    }
    // Also handle the case when editing an existing post (post_type is not in URL)
    if (
        ($hook === 'post.php' || $hook === 'post-new.php') &&
        isset($post) && isset($post->post_type) && $post->post_type === 'scme_booking_form'
    ) {
        $is_booking_form = true;
    }
    if ($is_booking_form) {
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('scme-form-builder-admin', SCME_PLUGIN_URL . 'admin/form-builder-admin.js', array('jquery', 'jquery-ui-draggable', 'jquery-ui-sortable'), SCME_VERSION, true);
        wp_enqueue_style(
            'SCME-style',
            SCME_PLUGIN_URL . 'public/css/style.css',
            array(),
            SCME_VERSION
        );
    }
}
add_action('admin_enqueue_scripts', 'scme_admin_enqueue_form_builder_script');