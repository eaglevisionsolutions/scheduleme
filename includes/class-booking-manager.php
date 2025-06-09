<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class scme_Booking_Manager {

    private static $table_name;

    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'scme_bookings';
    }

    /**
     * Creates the custom database table(s) on plugin activation.
     */
    public static function create_tables() {
        global $wpdb;
        self::init(); // Ensure table name is set

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . self::$table_name . " (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT 0 NOT NULL,
            client_name varchar(255) NOT NULL,
            client_email varchar(255) NOT NULL,
            client_phone varchar(50),
            service_id bigint(20) DEFAULT 0 NOT NULL,
            service_name varchar(255),
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            price decimal(10,2) NOT NULL,
            paypal_transaction_id varchar(255),
            payment_status varchar(50) DEFAULT 'pending' NOT NULL, -- 'pending', 'paid', 'failed', 'refunded'
            booking_status varchar(50) DEFAULT 'tentative' NOT NULL, -- 'tentative', 'confirmed', 'canceled'
            google_calendar_event_id varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY start_time (start_time),
            KEY payment_status (payment_status),
            KEY booking_status (booking_status)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Creates a new tentative booking record.
     *
     * @param array $data Booking data.
     * @return int|bool Insert ID on success, false on failure.
     */
    public static function create_tentative_booking( $data ) {
        global $wpdb;
        self::init();

        $booking_data = array(
            'user_id'       => get_current_user_id(), // Or 0 if not logged in
            'client_name'   => sanitize_text_field( $data['client_name'] ),
            'client_email'  => sanitize_email( $data['client_email'] ),
            'client_phone'  => sanitize_text_field( $data['client_phone'] ?? '' ),
            'service_id'    => absint( $data['service_id'] ?? 0 ),
            'service_name'  => sanitize_text_field( $data['service_name'] ?? '' ),
            'start_time'    => sanitize_text_field( $data['start_time'] ), // Ensure datetime format
            'end_time'      => sanitize_text_field( $data['end_time'] ),   // Ensure datetime format
            'price'         => floatval( $data['price'] ),
            'payment_status'=> 'pending',
            'booking_status'=> 'tentative',
            // paypal_transaction_id and google_calendar_event_id will be added later
        );

        $inserted = $wpdb->insert( self::$table_name, $booking_data );

        if ( $inserted ) {
            return $wpdb->insert_id;
        }
        return false;
    }

    /**
     * Updates the status of a booking.
     *
     * @param int|string $booking_id The ID of the booking or PayPal transaction ID.
     * @param string $payment_status 'paid', 'failed', 'refunded'.
     * @param string $booking_status 'confirmed', 'canceled'.
     * @param string $paypal_transaction_id PayPal transaction ID.
     * @param string $google_calendar_event_id Google Calendar event ID.
     * @return bool True on success, false on failure.
     */
    public static function update_booking_status( $booking_id, $payment_status = null, $booking_status = null, $paypal_transaction_id = null, $google_calendar_event_id = null ) {
        global $wpdb;
        self::init();

        $update_data = array();
        $where = array();

        if ( is_numeric( $booking_id ) ) {
            $where['id'] = absint( $booking_id );
        } else {
            // Assume it's a paypal_transaction_id if not numeric.
            // This is a simplification; a more robust system might have separate lookups.
            $where['paypal_transaction_id'] = sanitize_text_field( $booking_id );
        }

        if ( $payment_status ) {
            $update_data['payment_status'] = sanitize_text_field( $payment_status );
        }
        if ( $booking_status ) {
            $update_data['booking_status'] = sanitize_text_field( $booking_status );
        }
        if ( $paypal_transaction_id ) {
            $update_data['paypal_transaction_id'] = sanitize_text_field( $paypal_transaction_id );
        }
        if ( $google_calendar_event_id ) {
            $update_data['google_calendar_event_id'] = sanitize_text_field( $google_calendar_event_id );
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        return $wpdb->update( self::$table_name, $update_data, $where );
    }

    /**
     * Retrieves a booking by its ID.
     *
     * @param int $booking_id The booking ID.
     * @return object|null Booking object on success, null if not found.
     */
    public static function get_booking_by_id( $booking_id ) {
        global $wpdb;
        self::init();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$table_name . " WHERE id = %d", absint( $booking_id ) ) );
    }

    /**
     * Retrieves a booking by PayPal Transaction ID.
     *
     * @param string $paypal_txn_id The PayPal transaction ID.
     * @return object|null Booking object on success, null if not found.
     */
    public static function get_booking_by_paypal_txn_id( $paypal_txn_id ) {
        global $wpdb;
        self::init();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . self::$table_name . " WHERE paypal_transaction_id = %s", sanitize_text_field( $paypal_txn_id ) ) );
    }

    /**
     * Gets existing confirmed or tentative bookings within a time range.
     * Useful for checking real-time availability.
     *
     * @param string $start_time Datetime string.
     * @param string $end_time Datetime string.
     * @return array Array of booking objects.
     */
    public static function get_existing_bookings_in_range( $start_time, $end_time ) {
        global $wpdb;
        self::init();

        // Consider 'tentative' bookings to avoid race conditions during the payment process
        // Also include 'confirmed' bookings. Exclude 'canceled' bookings.
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::$table_name . "
             WHERE start_time < %s AND end_time > %s
             AND booking_status IN ('tentative', 'confirmed')",
            $end_time,
            $start_time
        ) );
    }
}

// Initialize the class to ensure static properties are set (like table name)
scme_Booking_Manager::init();