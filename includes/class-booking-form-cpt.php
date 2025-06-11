<?php
class SCME_Booking_Form_CPT {
    public static function register() {
        register_post_type('scme_booking_form', array(
            'labels' => array(
                'name' => 'Booking Forms',
                'singular_name' => 'Booking Form',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Booking Form',
                'edit_item' => 'Edit Booking Form',
                'new_item' => 'New Booking Form',
                'view_item' => 'View Booking Form',
                'search_items' => 'Search Booking Forms',
                'not_found' => 'No Booking Forms found',
                'not_found_in_trash' => 'No Booking Forms found in Trash',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'schedule-me',
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title'),
        ));
    }
}
add_action('init', ['SCME_Booking_Form_CPT', 'register']);