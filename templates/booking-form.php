<div id="scme-booking-form-wrapper" class="scme-multi-step-form">
    <div class="scme-form-progress">
        <span class="scme-step-indicator active" data-step="1">1. Your Info</span>
        <span class="scme-step-indicator" data-step="2">2. Select Time</span>
        <span class="scme-step-indicator" data-step="3">3. Confirm & Pay</span>
    </div>

    <form id="scme-booking-form">

        <div class="scme-form-step active" data-step="1">
            <h2>Your Details</h2>
            <div class="scme-form-field">
                <label for="scme-client-name">Full Name *</label>
                <input type="text" id="scme-client-name" name="client_name" required>
            </div>
            <div class="scme-form-field">
                <label for="scme-client-email">Email *</label>
                <input type="email" id="scme-client-email" name="client_email" required>
            </div>
            <div class="scme-form-field">
                <label for="scme-client-phone">Phone Number</label>
                <input type="tel" id="scme-client-phone" name="client_phone">
            </div>
            <div class="scme-form-field">
                <label for="scme-service-select">Select Service *</label>
                <select id="scme-service-select" name="service_id" required>
                    <option value="">-- Select a Service --</option>
                    <option value="1" data-duration="60" data-price="100.00" data-name="Standard Consultation">Standard Consultation (1 hour - $100)</option>
                    <option value="2" data-duration="30" data-price="50.00" data-name="Quick Chat">Quick Chat (30 min - $50)</option>
                    <option value="3" data-duration="120" data-price="200.00" data-name="Deep Dive Session">Deep Dive Session (2 hours - $200)</option>
                </select>
            </div>
            <button type="button" class="scme-next-step">Next</button>
        </div>

        <div class="scme-form-step" data-step="2">
            <h2>Select Date & Time</h2>
            <div class="scme-form-field">
                <label for="scme-date-picker">Select Date *</label>
                <input type="date" id="scme-date-picker" name="selected_date" required>
            </div>
            <div class="scme-form-field">
                <label>Available Times *</label>
                <div id="scme-time-slots" class="scme-time-slots-grid">
                    <p>Select a date to see available times.</p>
                    </div>
                <input type="hidden" id="scme-selected-start-time" name="selected_start_time" required>
                <input type="hidden" id="scme-selected-end-time" name="selected_end_time" required>
            </div>
            <button type="button" class="scme-prev-step">Previous</button>
            <button type="button" class="scme-next-step" id="scme-time-select-next" disabled>Next</button>
        </div>

        <div class="scme-form-step" data-step="3">
            <h2>Confirm Your Booking</h2>
            <div id="scme-booking-summary">
                <p><strong>Service:</strong> <span id="summary-service-name"></span></p>
                <p><strong>Date:</strong> <span id="summary-date"></span></p>
                <p><strong>Time:</strong> <span id="summary-time"></span></p>
                <p><strong>Price:</strong> $<span id="summary-price"></span></p>
            </div>
            <div class="scme-form-field">
                <input type="checkbox" id="scme-agree-terms" name="agree_terms" required>
                <label for="scme-agree-terms">I agree to the <a href="#" target="_blank">terms and conditions</a> *</label>
            </div>
            <input type="hidden" name="service_name_hidden" id="scme-service-name-hidden">
            <input type="hidden" name="price_hidden" id="scme-price-hidden">
            <button type="button" class="scme-prev-step">Previous</button>
            <button type="submit" id="scme-submit-booking">Pay Now with PayPal</button>
            <div id="scme-form-message" style="margin-top: 15px; color: red;"></div>
        </div>
    </form>
</div>