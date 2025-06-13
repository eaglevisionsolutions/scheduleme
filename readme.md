# Schedule Me Booking System for WordPress

This is a custom-built, multi-step booking system designed for WordPress websites. It allows users to book appointments, dynamically displaying only available slots from your Google Calendar, and confirms the booking on Google Calendar *only* after a successful PayPal payment.

---

## Features

* **Multi-Step Form:** Guides users through the booking process step-by-step.
* **Dynamic Google Calendar Availability:** Fetches real-time busy/free slots from your Google Calendar and displays only the available times to the user, preventing double bookings.
* **PayPal Integration:** Securely processes payments via PayPal.
* **Payment-Gated Calendar Events:** A booking is only added to your Google Calendar once the PayPal payment is confirmed as successful.
* **Customizable Services & Pricing:** Define different services with specific durations and prices.
* **WordPress Admin Settings:** Easy configuration of Google Calendar and PayPal API credentials directly within your WordPress dashboard.
* **Elementor Compatibility:** Designed to be easily embedded into Elementor pages using a shortcode.

---

## Technologies Used

* **WordPress:** The core CMS platform.
* **PHP:** Server-side logic for handling form submissions, API integrations, and database interactions.
* **JavaScript (jQuery):** Front-end logic for multi-step navigation, AJAX calls, and dynamic time slot display.
* **HTML & CSS:** Structure and styling of the booking form.
* **Google Calendar API:** For managing calendar events and checking availability.
* **PayPal IPN (Instant Payment Notification):** For receiving payment confirmations from PayPal.
* **WordPress Database:** Custom tables to manage booking records.
* **Google API Client Library for PHP:** Composer package for interacting with Google APIs.

---

## Setup Instructions

Follow these steps to set up and configure the Custom Booking System on your WordPress site.

### 1. Plugin Installation

1.  **Clone or Download:** Clone this repository or download the ZIP file.
2.  **Upload to WordPress:**
    * If you downloaded the ZIP, extract it.
    * Upload the `scheduleme` folder to your WordPress installation's `wp-content/plugins/` directory.
3.  **Activate Plugin:** Go to your WordPress admin dashboard, navigate to **Plugins**, find "Your Custom Booking System," and click **Activate**.

### 2. Composer Dependencies

This plugin uses the Google API Client Library for PHP, which is managed via Composer.

1.  **Access Terminal:** Open your terminal or command prompt.
2.  **Navigate to Plugin Directory:** Change directory to `wp-content/plugins/scheduleme/`.
3.  **Install Dependencies:** Run the following Composer command:
    ```bash
    composer install
    ```
    *If you don't have Composer installed, please follow the instructions on [getcomposer.org](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos).*

### 3. Google Cloud Project Setup

1.  **Go to Google Cloud Console:** Visit [console.cloud.google.com](https://console.cloud.google.com/).
2.  **Create a New Project:** If you don't have one, create a new project.
3.  **Enable Google Calendar API:**
    * In the Google Cloud Console, navigate to **APIs & Services > Library**.
    * Search for "Google Calendar API" and enable it.
4.  **Create OAuth Client ID Credentials:**
    * Go to **APIs & Services > Credentials**.
    * Click **+ CREATE CREDENTIALS** and select **OAuth client ID**.
    * Choose "Web application" as the Application type.
    * Enter a name (e.g., "Your Booking System Web App").
    * **Crucially, set the "Authorized redirect URIs" to:** `https://yourwebsite.com/wp-admin/admin.php?page=scme-settings` (Replace `yourwebsite.com` with your actual domain). This exact URI is displayed in your plugin settings page after installation.
    * Click **Create**. You will be presented with your **Client ID** and **Client Secret**. Keep these secure.

### 4. PayPal Business Account Setup

1.  **Access PayPal:** Log in to your [PayPal Business account](https://www.paypal.com/signin?locale.x=en_US&country.x=CA).
2.  **Configure IPN Listener:**
    * Go to **Account Settings > Website payments > Instant Payment Notifications**.
    * Click **Update**.
    * Click **Choose IPN Settings**.
    * Set the **Notification URL** to: `https://yourwebsite.com/scheduleme-paypal-listener/` (Replace `yourwebsite.com` with your actual domain). This URL is displayed in your plugin settings.
    * Select **Receive IPN messages (Enabled)**.
    * Click **Save**.
    * **Important:** After activating the plugin, you might need to go to your WordPress admin, navigate to **Settings > Permalinks**, and simply click **Save Changes** without making any modifications. This will flush your rewrite rules and ensure the PayPal listener URL is correctly recognized.

### 5. Plugin Configuration in WordPress

1.  **Navigate to Settings:** In your WordPress admin dashboard, go to **Schedule Me > Settings**.
2.  **Google Calendar API Settings:**
    * Enter the **Client ID** obtained from Google Cloud.
    * Enter the **Client Secret** obtained from Google Cloud.
    * The "Authorized Redirect URI" field will show the exact URI to use in Google Cloud.
    * Enter your **Google Calendar ID** (you can find this in your Google Calendar settings under "Integrate calendar").
    * Click **"Authenticate with Google"**. You'll be redirected to Google to grant permissions. Accept, and you'll be brought back to your settings page with an authentication success message.
3.  **PayPal Settings:**
    * Enter your **PayPal Business Email**.
    * Check the **Sandbox Mode** box for testing purposes (highly recommended during development). Uncheck it when ready for live payments.
    * The "PayPal IPN/Webhook URL" field will show the URL you need to configure in PayPal.

### 6. Create Booking Success/Cancellation Pages

1.  In your WordPress admin, go to **Pages > Add New**.
2.  Create a page titled "Booking Success" with the permalink `/booking-success/`.
3.  Create another page titled "Booking Cancelled" with the permalink `/booking-cancelled/`.
    * These pages will be the landing spots after PayPal payment completion or cancellation. You can customize their content to provide appropriate messages to your users.

### 7. Embed the Form into Elementor

1.  Go to the Elementor page where you want the booking form to appear.
2.  Drag and drop a **Shortcode** widget onto your page.
3.  In the shortcode field, enter:
    ```
    [scme_booking_form id="{post id of form here}"]
    ```
4.  Update your Elementor page.

---

## Usage

1.  **Define Services:** Currently, services are hardcoded in `templates/booking-form.php`. You can modify the `<select>` options to define your services, their durations (`data-duration`), and prices (`data-price`). For a more robust solution, you would typically manage services in the WordPress admin and fetch them dynamically from the database.
2.  **Test Thoroughly:**
    * Perform a test booking using PayPal Sandbox (ensure sandbox mode is enabled in plugin settings).
    * Verify that only available slots are displayed.
    * Check your Google Calendar to confirm that events are *only* created after successful PayPal payment and *not* for cancelled/failed payments.
    * Test both successful payments and cancelled payments.

---

## Important Considerations

* **Security:** While basic security measures are included, a custom payment and API integration requires constant vigilance. Ensure all data is properly validated, sanitized, and escaped. Implement robust error logging.
* **Error Handling:** The provided code includes basic error logging. Monitor your WordPress error logs for any issues with API calls or payment processing.
* **Timezone:** Ensure your WordPress timezone (Settings > General) and Google Calendar timezone settings are consistent to avoid booking discrepancies.
* **Scalability:** For high-volume bookings, consider advanced database optimization and potential rate limits from Google Calendar and PayPal APIs.
* **Maintenance:** You are responsible for all updates, security patches, and compatibility checks.
* **Google API Quotas:** Be mindful of Google Calendar API daily quotas. Exceeding them can temporarily block access.

---

## Contributing

Feel free to fork this repository, contribute improvements, or report issues.

---

## License

This project is open-source and available under the [GPL-2.0+ License](http://www.gnu.org/li
