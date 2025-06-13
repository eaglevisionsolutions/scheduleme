<?php
if (!defined( 'ABSPATH' )){
    exit; // Exit if accessed directly.
}

class SCME_Admin_Settings {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'scme_register_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register the admin menu and submenu pages.
     */
    function scme_register_admin_menu() {
          // Top-level menu
        add_menu_page(
            __( 'Schedule Me Settings', 'your-custom-booking-plugin' ),
            __( 'Schedule Me', 'your-custom-booking-plugin' ),
            'manage_options',
            'schedule-me',
            array( $this, 'settings_page_content' ),
            'dashicons-calendar-alt',
            80
        );

        // Settings submenu (points to the same page as the top-level menu)
        add_submenu_page(
            'schedule-me', // Parent slug
            __( 'Settings', 'your-custom-booking-plugin' ),
            __( 'Settings', 'your-custom-booking-plugin' ),
            'manage_options',
            'schedule-me', // Must match the top-level menu slug to show the same page
            array( $this, 'settings_page_content' )
        );

        // Bookings submenu
        add_submenu_page(
            'schedule-me',
            __( 'Bookings', 'your-custom-booking-plugin' ),
            __( 'Bookings', 'your-custom-booking-plugin' ),
            'manage_options',
            'scme-submissions',
            'scme_submissions_page'
        );
        /* add_menu_page(
            __( 'Schedule Me Settings', 'your-custom-booking-plugin' ),
            __( 'Schedule Me', 'your-custom-booking-plugin' ),
            'manage_options', // Capability required
            'SCME-settings',
            array( $this, 'settings_page_content' ),
            'dashicons-calendar-alt', // Icon
            80 // Position
        );
        /* add_menu_page(
            'schedule-me',
            'Schedule Me',
            'manage_options',
            'schedule-me',
            'scme_settings_page_callback',
            'dashicons-calendar-alt'
        ); 
        
       // Add Booking Submissions submenu
        add_submenu_page(
            'SCME-settings', // <-- Parent slug matches CPT
            'Bookings',
            'Bookings',
            'manage_options',
            'scme-submissions',
            'scme_submissions_page'
        ); */

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

        // Register reCAPTCHA Settings
        register_setting( 'SCME_settings_group', 'scme_recaptcha_v2_site_key' );
        register_setting( 'SCME_settings_group', 'scme_recaptcha_v2_secret' );
        register_setting( 'SCME_settings_group', 'scme_recaptcha_v3_site_key' );
        register_setting( 'SCME_settings_group', 'scme_recaptcha_v3_secret' );

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

        add_settings_section(
            'SCME_recaptcha_section',
            __( 'reCAPTCHA Settings', 'your-custom-booking-plugin' ),
            array( $this, 'recaptcha_section_callback' ),
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

        // Add settings fields for reCAPTCHA
        add_settings_field( 'scme_recaptcha_v2_site_key', 'reCAPTCHA v2 Site Key', array( $this, 'text_input_callback' ), 'SCME-settings', 'SCME_recaptcha_section', array( 'name' => 'scme_recaptcha_v2_site_key' ) );
        add_settings_field( 'scme_recaptcha_v2_secret', 'reCAPTCHA v2 Secret', array( $this, 'text_input_callback' ), 'SCME-settings', 'SCME_recaptcha_section', array( 'name' => 'scme_recaptcha_v2_secret', 'type' => 'password' ) );
        add_settings_field( 'scme_recaptcha_v3_site_key', 'reCAPTCHA v3 Site Key', array( $this, 'text_input_callback' ), 'SCME-settings', 'SCME_recaptcha_section', array( 'name' => 'scme_recaptcha_v3_site_key' ) );
        add_settings_field( 'scme_recaptcha_v3_secret', 'reCAPTCHA v3 Secret', array( $this, 'text_input_callback' ), 'SCME-settings', 'SCME_recaptcha_section', array( 'name' => 'scme_recaptcha_v3_secret', 'type' => 'password' ) );
    }

    public function google_section_callback() {
        echo '<p>Enter your Google Calendar API credentials. This is where you\'ll manage the connection to your Google Calendar.</p>';
    }

    public function paypal_section_callback() {
        echo '<p>Configure your PayPal Business account details for processing payments.</p>';
    }

    public function recaptcha_section_callback() {
        echo '<p>Enter your reCAPTCHA keys for v2 and v3. These are used to protect your forms from spam and abuse.</p>';
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
            <h1><?php esc_html_e( 'Schedule Me System Settings', 'your-custom-booking-plugin' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'SCME_settings_group' );
                do_settings_sections('SCME-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new SCME_Admin_Settings();