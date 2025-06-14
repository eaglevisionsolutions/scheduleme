<?php
if (!defined( 'ABSPATH' )){
    exit; // Exit if accessed directly.
}

class SCME_Admin_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Custom Booking Settings', 'your-custom-booking-plugin' ),
            __( 'Custom Booking', 'your-custom-booking-plugin' ),
            'manage_options', // Capability required
            'SCME-settings',
            array( $this, 'settings_page_content' ),
            'dashicons-calendar-alt', // Icon
            80 // Position
        );
    }

    public function register_settings() {
        // Register Google Calendar Settings
        register_setting( 'SCME_settings_group', 'SCME_google_client_id' );
        register_setting( 'SCME_settings_group', 'SCME_google_client_secret' );
        register_setting( 'SCME_settings_group', 'SCME_google_redirect_uri' );
        register_setting( 'SCME_settings_group', 'SCME_google_calendar_id' );
        register_setting( 'SCME_settings_group', 'SCME_google_access_token' ); // Store tokens securely!
        register_setting( 'SCME_settings_group', 'SCME_google_refresh_token' ); // Store tokens securely!

        // Register PayPal Settings
        register_setting( 'SCME_settings_group', 'SCME_paypal_email' );
        register_setting( 'SCME_settings_group', 'SCME_paypal_sandbox_mode' ); // Checkbox for sandbox
        register_setting( 'SCME_settings_group', 'SCME_paypal_ipn_url' ); // Auto-generated for IPN, user for webhook endpoint display

        // Add settings sections
        add_settings_section(
            'SCME_google_section',
            __( 'Google Calendar API Settings', 'your-custom-booking-plugin' ),
            array( $this, 'google_section_callback' ),
            'SCME-settings'
        );

        add_settings_section(
            'SCME_paypal_section',
            __( 'PayPal Settings', 'your-custom-booking-plugin' ),
            array( $this, 'paypal_section_callback' ),
            'SCME-settings'
        );

        // Add settings fields for Google Calendar
        add_settings_field( 'SCME_google_client_id', 'Client ID', array( $this, 'text_input_callback' ), 'SCME-settings', 'SCME_google_section', array( 'name' => 'SCME_google_client_id' ) );
        add_settings_field( 'SCME_google_client_secret', 'Client Secret', array( $this, 'text_input_callback' ), 'SCME-settings', 'SCME_google_section', array( 'name' => 'SCME_google_client_secret', 'type' => 'password' ) );
        add_settings_field( 'SCME_google_redirect_uri', 'Authorized Redirect URI', array( $this, 'redirect_uri_callback' ), 'SCME-settings', 'SCME_google_section', array( 'name' => 'SCME_google_redirect_uri' ) );
        add_settings_field( 'SCME_google_calendar_id', 'Calendar ID', array( $this, 'text_input_callback' ), 'SCME-settings', 'SCME_google_section', array( 'name' => 'SCME_google_calendar_id' ) );
        add_settings_field( 'SCME_google_auth_status', 'Authentication Status', array( $this, 'google_auth_status_callback' ), 'SCME-settings', 'SCME_google_section' );


        // Add settings fields for PayPal
        add_settings_field( 'SCME_paypal_email', 'PayPal Business Email', array( $this, 'text_input_callback' ), 'SCME-settings', 'SCME_paypal_section', array( 'name' => 'SCME_paypal_email' ) );
        add_settings_field( 'SCME_paypal_sandbox_mode', 'Sandbox Mode', array( $this, 'checkbox_callback' ), 'SCME-settings', 'SCME_paypal_section', array( 'name' => 'SCME_paypal_sandbox_mode' ) );
        add_settings_field( 'SCME_paypal_ipn_url', 'PayPal IPN/Webhook URL', array( $this, 'ipn_url_callback' ), 'SCME-settings', 'SCME_paypal_section' );
    }

    public function google_section_callback() {
        echo '<p>Enter your Google Calendar API credentials. This is where you\'ll manage the connection to your Google Calendar.</p>';
    }

    public function paypal_section_callback() {
        echo '<p>Configure your PayPal Business account details for processing payments.</p>';
    }

    // Generic text input callback
    public function text_input_callback( $args ) {
        $name = $args['name'];
        $type = isset( $args['type'] ) ? $args['type'] : 'text';
        $value = esc_attr( get_option( $name ) );
        echo "<input type='$type' name='$name' value='$value' class='regular-text' />";
    }

    // Checkbox callback
    public function checkbox_callback( $args ) {
        $name = $args['name'];
        $checked = checked( 1, get_option( $name ), false );
        echo "<input type='checkbox' name='$name' value='1' $checked />";
    }

    // Special callback for Redirect URI (read-only for user)
    public function redirect_uri_callback( $args ) {
        $redirect_uri = admin_url( 'admin.php?page=SCME-settings' ); // Where Google should redirect after OAuth
        echo "<input type='text' value='$redirect_uri' class='regular-text' readonly />";
        echo '<p class="description">Copy this URI into your Google Cloud Platform credentials for your OAuth 2.0 Web application.</p>';
    }

    // Special callback for IPN/Webhook URL (read-only for user)
    public function ipn_url_callback() {
        $ipn_url = home_url( '/your-custom-booking-paypal-listener/' ); // This needs to match your rewrite rule
        echo "<input type='text' value='$ipn_url' class='regular-text' readonly />";
        echo '<p class="description">Configure this URL as your IPN Listener URL or Webhook endpoint in your PayPal account settings.</p>';
    }

    // Google Auth Status and button
    public function google_auth_status_callback() {
        $access_token = get_option('SCME_google_access_token');
        if ( $access_token ) {
            echo '<p style="color: green;">&#10004; Authenticated with Google Calendar. (Token stored securely)</p>';
            // You might add a "Re-authenticate" button here
        } else {
            // This is the simplified authorization URL for demonstration.
            // In a real scenario, you'd generate the full authorization URL with scopes and state.
            $client_id = get_option('SCME_google_client_id');
            $redirect_uri = admin_url( 'admin.php?page=SCME-settings' ); // Your authorized redirect URI

            if ($client_id) {
                $auth_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
                    'client_id' => $client_id,
                    'redirect_uri' => $redirect_uri,
                    'response_type' => 'code',
                    'scope' => 'https://www.googleapis.com/auth/calendar', // Adjust scopes as needed
                    'access_type' => 'offline', // Get a refresh token
                    'prompt' => 'consent', // Always ask for consent
                ]);
                 echo '<p style="color: red;">&#10006; Not authenticated.</p>';
                 echo '<a href="' . esc_url($auth_url) . '" class="button button-primary">Authenticate with Google</a>';
            } else {
                 echo '<p>Please enter your Google Client ID to enable authentication.</p>';
            }
        }

        // Handle OAuth 2.0 callback for Google
        if ( isset( $_GET['code'] ) && isset( $_GET['page'] ) && $_GET['page'] === 'SCME-settings' ) {
            $code = sanitize_text_field( $_GET['code'] );
            $client_id = get_option('SCME_google_client_id');
            $client_secret = get_option('SCME_google_client_secret');
            $redirect_uri = admin_url( 'admin.php?page=SCME-settings' );

            if ( $client_id && $client_secret ) {
                $token_url = 'https://oauth2.googleapis.com/token';
                $response = wp_remote_post( $token_url, array(
                    'body' => array(
                        'code'          => $code,
                        'client_id'     => $client_id,
                        'client_secret' => $client_secret,
                        'redirect_uri'  => $redirect_uri,
                        'grant_type'    => 'authorization_code',
                    ),
                ));

                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['access_token'] ) ) {
                        update_option( 'SCME_google_access_token', $body['access_token'] );
                        if ( isset( $body['refresh_token'] ) ) {
                             update_option( 'SCME_google_refresh_token', $body['refresh_token'] );
                        }
                        echo '<div class="notice notice-success is-dismissible"><p>Google authentication successful!</p></div>';
                        // Redirect to remove code from URL
                        echo '<script>window.location.href = "' . esc_url( remove_query_arg( 'code', admin_url( 'admin.php?page=SCME-settings' ) ) ) . '";</script>';
                    } else {
                         echo '<div class="notice notice-error is-dismissible"><p>Google authentication failed: No access token.</p></div>';
                    }
                } else {
                    $error_message = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response );
                    echo '<div class="notice notice-error is-dismissible"><p>Google authentication failed: ' . esc_html($error_message) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-warning is-dismissible"><p>Please ensure Client ID and Client Secret are set.</p></div>';
            }
        }
    }


    public function settings_page_content() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Your Custom Booking System Settings', 'your-custom-booking-plugin' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'SCME_settings_group' );
                do_settings_sections( 'SCME-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new SCME_Admin_Settings();