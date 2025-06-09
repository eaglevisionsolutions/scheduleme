<?php
class SCME_Booking_Submission_Manager {
    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'scme_submissions';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            form_id BIGINT NOT NULL,
            user_name VARCHAR(255),
            user_email VARCHAR(255),
            service VARCHAR(255),
            booking_time DATETIME,
            payment_status VARCHAR(50),
            payment_id VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function insert($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'scme_submissions';
        $wpdb->insert($table, $data);
    }

    public static function get_all() {
        global $wpdb;
        $table = $wpdb->prefix . 'scme_submissions';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }
}