<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class SCME_PayPal_IPN {

    public function __construct() {
        // We'll set up rewrite rules in add_rewrite_rules
    }

    /**
     * Add custom rewrite rules for the PayPal IPN listener.
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^your-custom-booking-paypal-listener/?$',
            'index.php?scme_paypal_ipn=1',
            'top'
        );
    }

    /**
     * Add custom query variables.
     *
     * @param array $vars
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'scme_paypal_ipn';
        return $vars;
    }

    /**
     * Handle the PayPal IPN request when the custom URL is hit.
     */
    public function handle_request() {
        if ( isset( $_GET['scme_paypal_ipn'] ) && $_GET['scme_paypal_ipn'] == '1' ) {
            // Silence WordPress's output
            define( 'DOING_AJAX', true );
            @header( 'Status: 200 OK' );
            @header( 'Content-Type: text/plain' );

            // Process the IPN
            $this->process_ipn_request();

            // Exit cleanly
            exit();
        }
    }

    /**
     * Process the incoming PayPal IPN.
     * This is where the magic happens for payment confirmation.
     */
    private function process_ipn_request() {
        // Read POST data
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        foreach ( $raw_post_array as $keyval ) {
            $keyval = explode ('=', $keyval);
            if ( count( $keyval ) == 2 ) {
                $myPost[$keyval[0]] = urldecode( $keyval[1] );
            }
        }

        // Build request to PayPal for IPN verification
        $req = 'cmd=_notify-validate';
        foreach ( $myPost as $key => $value ) {
            $value = urlencode( stripslashes( $value ) );
            $req .= "&$key=$value";
        }

        // Determine PayPal URL based on sandbox mode
        $paypal_url = get_option('scme_paypal_sandbox_mode') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

        $response = wp_remote_post( $paypal_url, array(
            'method'      => 'POST',
            'timeout'     => 45,
            'sslverify'   => true,
            'body'        => $req,
        ));

        if ( is_wp_error( $response ) ) {
            error_log( 'SCME PayPal IPN Error (HTTP request): ' . $response->get_error_message() );
            return;
        }

        $body = wp_remote_retrieve_body( $response );

        if ( strcmp ( $body, "VERIFIED" ) == 0 ) {
            // Payment is VERIFIED!
            $payer_email        = sanitize_email( $myPost['payer_email'] );
            $mc_gross           = floatval( $myPost['mc_gross'] );
            $txn_id             = sanitize_text_field( $myPost['txn_id'] );
            $payment_status     = sanitize_text_field( $myPost['payment_status'] );
            $receiver_email     = sanitize_email( $myPost['receiver_email'] );
            $custom_booking_id  = absint( $myPost['custom'] ); // This is crucial: the ID you passed to PayPal

            // Validate the receiver_email matches your business email
            if ( strtolower( $receiver_email ) != strtolower( get_option('scme_paypal_email') ) ) {
                error_log( 'SCME PayPal IPN Error: Receiver email mismatch. Received: ' . $receiver_email );
                return;
            }

            $booking = SCME_Booking_Manager::get_booking_by_id( $custom_booking_id );

            if ( ! $booking ) {
                error_log( 'SCME PayPal IPN Error: Booking ID ' . $custom_booking_id . ' not found.' );
                return;
            }

            // Ensure amount matches (simple check for now)
            if ( $mc_gross < $booking->price ) { // Use < for flexibility, or == for strict
                error_log( 'SCME PayPal IPN Warning: Amount mismatch for booking ' . $custom_booking_id . '. Expected: ' . $booking->price . ', Received: ' . $mc_gross );
                // You might handle this with a 'pending review' status
            }

            // Process based on payment status
            if ( $payment_status == 'Completed' ) {
                if ( $booking->payment_status === 'pending' && $booking->booking_status === 'tentative' ) {
                    // Update booking status
                    SCME_Booking_Manager::update_booking_status(
                        $booking->id,
                        'paid',
                        'confirmed',
                        $txn_id
                    );

                    // Store paid/verified submission
            if ( class_exists( 'SCME_Booking_Submission_Manager' ) ) {
                SCME_Booking_Submission_Manager::insert([
                    'form_id'        => $booking->form_id ?? 0, // Adjust if your booking object uses a different property
                    'user_name'      => $booking->client_name,
                    'user_email'     => $booking->client_email,
                    'service'        => $booking->service_name,
                    'booking_time'   => $booking->start_time,
                    'payment_status' => 'paid',
                    'payment_id'     => $txn_id,
                ]);
            }   
                    // Create Google Calendar Event
                    $google_calendar_api = new SCME_Google_Calendar_API();
                    if ( $google_calendar_api->is_ready() ) {
                        $event_summary = $booking->service_name . ' booking for ' . $booking->client_name;
                        $event_description = "Client: " . $booking->client_name . "\nEmail: " . $booking->client_email . "\nPhone: " . $booking->client_phone . "\nService: " . $booking->service_name;

                        $event_id = $google_calendar_api->create_event( array(
                            'summary'        => $event_summary,
                            'description'    => $event_description,
                            'start_datetime' => (new DateTime($booking->start_time))->format(DateTime::RFC3339),
                            'end_datetime'   => (new DateTime($booking->end_time))->format(DateTime::RFC3339),
                            'attendees'      => array( $booking->client_email ),
                        ) );

                        if ( $event_id ) {
                            SCME_Booking_Manager::update_booking_status( $booking->id, null, null, null, $event_id );
                            error_log( 'SCME: Google Calendar event created for booking ' . $booking->id . ': ' . $event_id );
                        } else {
                            error_log( 'SCME: Failed to create Google Calendar event for booking ' . $booking->id );
                            // Optionally, revert booking status or send admin alert
                        }
                    } else {
                        error_log( 'SCME: Google Calendar API not ready during IPN processing for booking ' . $booking->id );
                        // Handle case where GCal API is not authenticated (e.g., send admin notification)
                    }

                    // Send confirmation emails to client and admin (using wp_mail)
                    $this->send_confirmation_emails( $booking );

                } elseif ( $booking->payment_status === 'paid' && $booking->booking_status === 'confirmed' ) {
                    // Already processed, could be a duplicate IPN, ignore or log.
                    error_log( 'SCME PayPal IPN: Duplicate IPN for booking ' . $booking->id . '. Ignoring.' );
                }
            } elseif ( $payment_status == 'Refunded' || $payment_status == 'Reversed' ) {
                SCME_Booking_Manager::update_booking_status( $booking->id, $payment_status, 'canceled' );
                // Potentially delete Google Calendar event here
                error_log( 'SCME PayPal IPN: Booking ' . $booking->id . ' refunded/reversed.' );
            } else {
                // Other payment statuses (e.g., Pending, Failed, Denied)
                SCME_Booking_Manager::update_booking_status( $booking->id, $payment_status, 'canceled' );
                error_log( 'SCME PayPal IPN: Booking ' . $booking->id . ' has status: ' . $payment_status );
            }

        } else if ( strcmp ( $body, "INVALID" ) == 0 ) {
            // Payment is INVALID! Log for investigation.
            error_log( 'SCME PayPal IPN Error: Invalid IPN received. Raw data: ' . $raw_post_data );
        }
    }

    /**
     * Sends booking confirmation emails.
     * @param object $booking The booking object.
     */
    private function send_confirmation_emails( $booking ) {
        // Send email to client
        $client_subject = 'Your Booking Confirmation - ' . $booking->service_name;
        $client_message = "Hi " . $booking->client_name . ",\n\n";
        $client_message .= "Your booking for " . $booking->service_name . " on " . date('F j, Y, g:i a', strtotime($booking->start_time)) . " has been confirmed!\n\n";
        $client_message .= "Total Price: $" . number_format($booking->price, 2) . "\n\n";
        $client_message .= "Thank you!";
        wp_mail( $booking->client_email, $client_subject, $client_message );

        // Send email to admin
        $admin_email = get_option('admin_email');
        $admin_subject = 'New Booking Confirmation - ' . $booking->service_name;
        $admin_message = "A new booking has been confirmed:\n\n";
        $admin_message .= "Client: " . $booking->client_name . " (" . $booking->client_email . ")\n";
        $admin_message .= "Service: " . $booking->service_name . "\n";
        $admin_message .= "Date/Time: " . date('F j, Y, g:i a', strtotime($booking->start_time)) . "\n";
        $admin_message .= "Price: $" . number_format($booking->price, 2) . "\n";
        $admin_message .= "PayPal Transaction ID: " . $booking->paypal_transaction_id . "\n";
        wp_mail( $admin_email, $admin_subject, $admin_message );
    }
}