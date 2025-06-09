<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class SCME_Form_Handler {

    private $google_calendar_api;
    private $booking_manager;

    public function __construct() {
        $this->google_calendar_api = new SCME_Google_Calendar_API();
        $this->booking_manager = new SCME_Booking_Manager(); // Access static methods directly or initialize
    }

    /**
     * Handle AJAX request to get available time slots.
     * REST API endpoint callback: /wp-json/your-plugin/v1/get-available-slots
     * Request method: POST
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_get_available_slots( WP_REST_Request $request ) {
        // Verify nonce
        if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) && ! wp_verify_nonce( $request->get_param( 'nonce' ), 'scme_form_nonce' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Nonce verification failed.' ), 403 );
        }

        $date_str = $request->get_param( 'selected_date' );
        $service_duration_minutes = absint( $request->get_param( 'service_duration' ) ); // Assume this comes from the form

        if ( ! $date_str || ! $service_duration_minutes ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Missing date or service duration.' ), 400 );
        }

        // Define your general working hours for the day
        $start_of_day = new DateTime( $date_str . ' 09:00:00', wp_timezone() ); // Example: 9 AM
        $end_of_day = new DateTime( $date_str . ' 17:00:00', wp_timezone() );   // Example: 5 PM

        if ( $start_of_day > $end_of_day ) { // Edge case for invalid date logic
             return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid date range.' ), 400 );
        }

        // Get busy times from Google Calendar
        $busy_times = $this->google_calendar_api->get_free_busy_times(
            $start_of_day->format( DateTime::RFC3339 ),
            $end_of_day->format( DateTime::RFC3339 )
        );

        // Get tentative/confirmed bookings from your own DB to avoid race conditions
        $internal_busy_times = SCME_Booking_Manager::get_existing_bookings_in_range(
            $start_of_day->format('Y-m-d H:i:s'),
            $end_of_day->format('Y-m-d H:i:s')
        );

        // Merge internal busy times with Google Calendar busy times
        foreach ( $internal_busy_times as $booking ) {
            $busy_times[] = (object) [ // Cast to object to match Google API response structure
                'start' => (new DateTime($booking->start_time))->format(DateTime::RFC3339),
                'end'   => (new DateTime($booking->end_time))->format(DateTime::RFC3339)
            ];
        }

        // Sort busy times by start for easier processing
        usort($busy_times, function($a, $b) {
            return strtotime($a->start) - strtotime($b->start);
        });

        $available_slots = [];
        $current_slot_start = clone $start_of_day;

        // Calculate available slots based on working hours and busy periods
        while ( $current_slot_start->getTimestamp() + ($service_duration_minutes * 60) <= $end_of_day->getTimestamp() ) {
            $slot_end_time = clone $current_slot_start;
            $slot_end_time->modify( '+' . $service_duration_minutes . ' minutes' );

            $is_busy = false;
            foreach ( $busy_times as $busy_period ) {
                $busy_start = new DateTime( $busy_period->start );
                $busy_end = new DateTime( $busy_period->end );

                // Check for overlap: [slot_start, slot_end) overlaps with [busy_start, busy_end)
                if ( ( $current_slot_start < $busy_end ) && ( $slot_end_time > $busy_start ) ) {
                    $is_busy = true;
                    // Move current_slot_start past the busy period + some buffer (e.g., 5 min buffer)
                    $current_slot_start = clone $busy_end;
                    $current_slot_start->modify( '+5 minutes' ); // Add a small buffer
                    break; // Break inner loop, re-evaluate from new start time
                }
            }

            if ( ! $is_busy ) {
                // If no overlap, add this slot to available_slots
                if ( $slot_end_time->getTimestamp() <= $end_of_day->getTimestamp() ) {
                     $available_slots[] = array(
                        'start' => $current_slot_start->format('Y-m-d H:i:s'),
                        'end'   => $slot_end_time->format('Y-m-d H:i:s'),
                        'display_time' => $current_slot_start->format('g:i A') . ' - ' . $slot_end_time->format('g:i A')
                    );
                }
                $current_slot_start->modify( '+' . $service_duration_minutes . ' minutes' ); // Move to next potential slot
            }
            // If it was busy, current_slot_start was already moved past the busy period, so the while loop continues.
        }

        return new WP_REST_Response( array( 'success' => true, 'available_slots' => $available_slots ), 200 );
    }

    /**
     * Handle booking initiation (create tentative booking, prepare PayPal redirect).
     * REST API endpoint callback: /wp-json/your-plugin/v1/initiate-booking
     * Request method: POST
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle_initiate_booking( WP_REST_Request $request ) {
        // Verify nonce
        if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) && ! wp_verify_nonce( $request->get_param( 'nonce' ), 'scme_form_nonce' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Nonce verification failed.' ), 403 );
        }

        // Sanitize and validate input
        $client_name = sanitize_text_field( $request->get_param( 'client_name' ) );
        $client_email = sanitize_email( $request->get_param( 'client_email' ) );
        $client_phone = sanitize_text_field( $request->get_param( 'client_phone' ) );
        $service_name = sanitize_text_field( $request->get_param( 'service_name' ) ); // e.g., from a dropdown
        $service_id = absint( $request->get_param( 'service_id' ) ); // numeric ID if you have a services table
        $start_time = sanitize_text_field( $request->get_param( 'selected_start_time' ) ); // Y-m-d H:i:s
        $end_time = sanitize_text_field( $request->get_param( 'selected_end_time' ) );   // Y-m-d H:i:s
        $price = floatval( $request->get_param( 'price' ) );

        if ( ! $client_name || ! is_email( $client_email ) || ! $service_name || ! $start_time || ! $end_time || $price <= 0 ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Missing or invalid form data.' ), 400 );
        }

        // Basic check for future time
        if ( strtotime( $start_time ) < current_time( 'timestamp' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Selected time is in the past.' ), 400 );
        }

        // Optional: Re-check availability at the last moment to prevent race conditions
        // This is a simplified check. A robust system would require locking mechanisms.
        $existing_bookings = SCME_Booking_Manager::get_existing_bookings_in_range( $start_time, $end_time );
        if ( ! empty( $existing_bookings ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Selected time is no longer available. Please refresh and try again.' ), 409 ); // 409 Conflict
        }

        // Create tentative booking
        $booking_id = SCME_Booking_Manager::create_tentative_booking( array(
            'client_name'   => $client_name,
            'client_email'  => $client_email,
            'client_phone'  => $client_phone,
            'service_id'    => $service_id,
            'service_name'  => $service_name,
            'start_time'    => $start_time,
            'end_time'      => $end_time,
            'price'         => $price,
        ) );

        if ( ! $booking_id ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Failed to create tentative booking.' ), 500 );
        }

        // Prepare PayPal redirect URL
        $paypal_email = get_option('scme_paypal_email');
        $sandbox_mode = get_option('scme_paypal_sandbox_mode');
        $paypal_action_url = $sandbox_mode ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
        $ipn_listener_url = home_url( '/your-custom-booking-paypal-listener/' ); // Matches the rewrite rule

        $paypal_args = array(
            'cmd'           => '_xclick',
            'business'      => $paypal_email,
            'item_name'     => $service_name . ' Booking (' . date('M j, Y, g:i A', strtotime($start_time)) . ')',
            'amount'        => number_format( $price, 2, '.', '' ), // Format price for PayPal
            'currency_code' => 'CAD', // Assuming Canadian dollars based on location
            'custom'        => $booking_id, // Pass your internal booking ID here for IPN matching
            'return'        => home_url( '/booking-success/' ), // Redirect after successful payment (create this page)
            'cancel_return' => home_url( '/booking-cancelled/' ), // Redirect after cancelled payment (create this page)
            'notify_url'    => $ipn_listener_url, // IPN listener URL
            'no_shipping'   => '1',
            'no_note'       => '1',
            'charset'       => 'utf-8',
        );

        $paypal_redirect_url = add_query_arg( $paypal_args, $paypal_action_url );

        return new WP_REST_Response( array(
            'success'        => true,
            'booking_id'     => $booking_id,
            'redirect_url'   => $paypal_redirect_url,
            'message'        => 'Booking initiated, redirecting to PayPal.'
        ), 200 );
    }
}