<?php
class SCME_Booking_Form_Metabox {
    public static function add() {
        add_meta_box(
            'scme_form_fields',
            'Form Fields',
            [self::class, 'render'],
            'scme_booking_form',
            'normal',
            'default'
        );
    }

    public static function render($post) {
        $services = get_post_meta($post->ID, '_scme_services', true) ?: '';
        ?>
        <label for="scme_services">Services (comma separated):</label>
        <input type="text" name="scme_services" id="scme_services" value="<?php echo esc_attr($services); ?>" style="width:100%;" />
        <?php
    }

    public static function save($post_id) {
        if (isset($_POST['scme_services'])) {
            update_post_meta($post_id, '_scme_services', sanitize_text_field($_POST['scme_services']));
        }
    }
}
add_action('add_meta_boxes', ['SCME_Booking_Form_Metabox', 'add']);
add_action('save_post_scme_booking_form', ['SCME_Booking_Form_Metabox', 'save']);