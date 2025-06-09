<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Assume Google Client Library is loaded, e.g., via Composer autoload
// require_once SCME_PLUGIN_DIR . 'vendor/autoload.php'; // Adjust path if using Composer

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;

class SCME_Google_Calendar_API {

    private $client;
    private $calendar_service;
    private $calendar_id;

    public function __construct() {
        // Load Google Client Library if not already loaded (e.g., via Composer)
        // If you're not using Composer, you'd manually include the Google library files here.
        if ( ! class_exists( 'Google\Client' ) ) {
            // Fallback for manual inclusion (less recommended):
            // require_once SCME_PLUGIN_DIR . 'path/to/google-api-php-client/vendor/autoload.php';
            // You MUST ensure the Google API Client Library is available.
            error_log('Google Client Library not found. Please ensure it is installed and loaded.');
            return;
        }

        $this->client = new Client();
        $this->client->setApplicationName('Your Custom Booking System');
        $this->client->setRedirectUri(admin_url( 'admin.php?page=SCME-settings' )); // Matches admin settings
        $this->client->setAuthConfig(array(
            'client_id' => get_option('SCME_google_client_id'),
            'client_secret' => get_option('SCME_google_client_secret'),
        ));
        $this->client->setScopes(Calendar::CALENDAR); // Or Calendar::CALENDAR_EVENTS
        $this->client->setAccessType('offline'); // To get refresh token

        $access_token = get_option('SCME_google_access_token');
        $refresh_token = get_option('SCME_google_refresh_token');

        if ( $access_token && $refresh_token ) {
            $this->client->setAccessToken( $access_token );

            // If the token is expired, refresh it
            if ( $this->client->isAccessTokenExpired() ) {
                try {
                    $this->client->fetchAccessTokenWithRefreshToken( $refresh_token );
                    $new_access_token = $this->client->getAccessToken();
                    if ( $new_access_token ) {
                        update_option( 'SCME_google_access_token', $new_access_token );
                    }
                } catch ( Exception $e ) {
                    error_log( 'SCME: Failed to refresh Google Access Token: ' . $e->getMessage() );
                    // Clear tokens if refresh fails, forcing re-authentication
                    delete_option( 'SCME_google_access_token' );
                    delete_option( 'SCME_google_refresh_token' );
                }
            }
        } elseif ( ! $access_token && $refresh_token ) {
            // Attempt to fetch new access token if only refresh token exists
             try {
                $this->client->fetchAccessTokenWithRefreshToken( $refresh_token );
                $new_access_token = $this->client->getAccessToken();
                if ( $new_access_token ) {
                    update_option( 'SCME_google_access_token', $new_access_token );
                }
            } catch ( Exception $e ) {
                error_log( 'SCME: Failed to fetch Google Access Token with refresh token: ' . $e->getMessage() );
                delete_option( 'SCME_google_refresh_token' ); // Refresh token is likely invalid
            }
        }

        $this->calendar_service = new Calendar( $this->client );
        $this->calendar_id = get_option('SCME_google_calendar_id');
    }

    /**
     * Checks if the Google Calendar API is ready for use (authenticated).
     * @return bool
     */
    public function is_ready() {
        return $this->client->getAccessToken() && $this->calendar_id;
    }

    /**
     * Fetches busy times from Google Calendar for a given date range.
     *
     * @param string $start_datetime Start of the period (ISO 8601, e.g., '2025-06-10T09:00:00-04:00').
     * @param string $end_datetime End of the period (ISO 8601).
     * @return array An array of busy time periods (start, end), or empty array on error.
     */
    public function get_free_busy_times( $start_datetime, $end_datetime ) {
        if ( ! $this->is_ready() ) {
            error_log('SCME: Google Calendar API not ready for free/busy query.');
            return array();
        }

        try {
            $query = new Google\Service\Calendar\FreeBusyRequest();
            $query->setTimeMin($start_datetime);
            $query->setTimeMax($end_datetime);
            $query->setItems([['id' => $this->calendar_id]]);

            $free_busy_response = $this->calendar_service->freebusy->query($query);
            $calendars = $free_busy_response->getCalendars();

            if (isset($calendars[$this->calendar_id])) {
                return $calendars[$this->calendar_id]->getBusy();
            }
        } catch ( Exception $e ) {
            error_log( 'SCME Google Calendar Error (get_free_busy_times): ' . $e->getMessage() );
        }
        return array();
    }

    /**
     * Creates an event in Google Calendar.
     *
     * @param array $event_data {
     * @type string $summary Event title.
     * @type string $description Event description.
     * @type string $start_datetime Start datetime (ISO 8601).
     * @type string $end_datetime End datetime (ISO 8601).
     * @type array  $attendees Array of attendee emails.
     * }
     * @return string|bool Event ID on success, false on failure.
     */
    public function create_event( $event_data ) {
        if ( ! $this->is_ready() ) {
            error_log('SCME: Google Calendar API not ready for event creation.');
            return false;
        }

        try {
            $event = new Event(array(
                'summary'     => sanitize_text_field( $event_data['summary'] ),
                'description' => sanitize_textarea_field( $event_data['description'] ?? '' ),
                'start'       => new EventDateTime(array(
                    'dateTime' => sanitize_text_field( $event_data['start_datetime'] ),
                    'timeZone' => wp_timezone_string(), // WordPress timezone
                )),
                'end'         => new EventDateTime(array(
                    'dateTime' => sanitize_text_field( $event_data['end_datetime'] ),
                    'timeZone' => wp_timezone_string(),
                )),
                'attendees'   => array_map(function($email) {
                    return ['email' => sanitize_email($email)];
                }, $event_data['attendees'] ?? []),
                'reminders' => array(
                    'useDefault' => FALSE,
                    'overrides' => array(
                        array('method' => 'email', 'minutes' => 30),
                        array('method' => 'popup', 'minutes' => 10),
                    ),
                ),
            ));

            $created_event = $this->calendar_service->events->insert($this->calendar_id, $event);
            return $created_event->getId();

        } catch ( Exception $e ) {
            error_log( 'SCME Google Calendar Error (create_event): ' . $e->getMessage() );
        }
        return false;
    }
}