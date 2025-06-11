// --- ADMIN FORM BUILDER LOGIC ---
window.SCMEFormBuilderInit = function(initialFields) {
    jQuery(function($){
        let $dropzone = $('#scme-form-builder-dropzone');
        let $input = $('#scme_form_fields_input');
        let fields = initialFields || [];

        // Render fields in dropzone
        function renderFields() {
            $dropzone.empty();
            if(fields.length === 0) {
                $dropzone.append('<div style="color:#888;">Drag widgets here to build your form</div>');
            }
            fields.forEach(function(field, idx){
                let label = field.label || field.type.charAt(0).toUpperCase() + field.type.slice(1);
                let html = `<div class="scme-form-builder-field" data-idx="${idx}">
                    <strong>${label}</strong> <span style="color:#888;">[${field.type}]</span>
                    <br><small>${field.name || ''}</small>
                    <div class="scme-field-actions">
                        <button type="button" class="scme-edit-field button button-small">Edit</button>
                        <button type="button" class="scme-remove-field button button-small">Remove</button>
                    </div>
                </div>`;
                $dropzone.append(html);
            });
            $input.val(JSON.stringify(fields));
            $dropzone.sortable({
                items: '.scme-form-builder-field',
                update: function(event, ui) {
                    let newOrder = [];
                    $dropzone.children('.scme-form-builder-field').each(function(){
                        let idx = $(this).data('idx');
                        newOrder.push(fields[idx]);
                    });
                    fields = newOrder;
                    renderFields();
                }
            });
        }
        renderFields();

        // Make widgets draggable
        $('.scme-widget').draggable({
            helper: "clone",
            connectToSortable: "#scme-form-builder-dropzone",
            revert: "invalid"
        });

        // Dropzone accepts widgets
        $dropzone.droppable({
            accept: ".scme-widget",
            drop: function(event, ui) {
                let type = ui.draggable.data('type');
                let label = type.charAt(0).toUpperCase() + type.slice(1);
                let name = type + '_' + (fields.length+1);
                let step = 1;
                let required = false;
                let regex = '';
                let options = '';
                if(['select','radio','checkbox'].includes(type)) {
                    options = 'Option 1,Option 2';
                }
                let newField = {type, label, name, placeholder:'', step, required, regex, options};
                fields.push(newField);
                renderFields();
                editField(fields.length-1); // Open edit dialog immediately
            }
        });

        // Remove Field
        $dropzone.on('click', '.scme-remove-field', function(){
            let idx = $(this).closest('.scme-form-builder-field').data('idx');
            if (confirm('Remove this field?')) {
                fields.splice(idx,1);
                renderFields();
            }
        });

        // Edit Field
        $dropzone.on('click', '.scme-edit-field', function(){
            let idx = $(this).closest('.scme-form-builder-field').data('idx');
            editField(idx);
        });

        function editField(idx) {
            let f = fields[idx];
            let label = prompt('Label:', f.label);
            let name = prompt('Field name:', f.name);
            let placeholder = prompt('Placeholder:', f.placeholder);
            let required = confirm('Required? (OK = Yes, Cancel = No)');
            let regex = prompt('Custom validation regex (optional):', f.regex||'');
            let step = prompt('Step number (for multi-step):', f.step||1);
            let options = f.options || '';
            if(['select','radio','checkbox'].includes(f.type)) {
                options = prompt('Options (comma separated):', options);
            }
            f.label = label; f.name = name; f.placeholder = placeholder;
            f.required = required; f.regex = regex; f.step = parseInt(step)||1; f.options = options;
            fields[idx] = f;
            renderFields();
        }
    });
};
jQuery(document).ready(function($) {
    let currentStep = 1;
    const $form = $('#scme-booking-form');
    const $formSteps = $('.scme-form-step');
    const $stepIndicators = $('.scme-step-indicator');
    const $timeSlotsContainer = $('#scme-time-slots');
    const $datePicker = $('#scme-date-picker');
    const $serviceSelect = $('#scme-service-select');
    const $selectedStartTime = $('#scme-selected-start-time');
    const $selectedEndTime = $('#scme-selected-end-time');
    const $timeSelectNextButton = $('#scme-time-select-next');
    const $formMessage = $('#scme-form-message');

    // Function to show a specific step
    function showStep(step) {
        $formSteps.removeClass('active').hide();
        $(`.scme-form-step[data-step="${step}"]`).addClass('active').show();
        $stepIndicators.removeClass('active');
        for (let i = 1; i <= step; i++) {
            $(`.scme-step-indicator[data-step="${i}"]`).addClass('active');
        }
        currentStep = step;
        // Scroll to top of form when changing steps
        $('html, body').animate({
            scrollTop: $form.offset().top - 50 // Adjust offset as needed
        }, 300);
    }

    // Function to validate current step fields
    function validateStep(step) {
        let isValid = true;
        $(`.scme-form-step[data-step="${step}"]`).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error'); // Add error class for styling
            } else {
                $(this).removeClass('error');
            }
        });

        if (step === 2) { // Specific validation for time selection step
            if (!$selectedStartTime.val()) {
                isValid = false;
                $timeSlotsContainer.addClass('error'); // Indicate error on time slots
            } else {
                $timeSlotsContainer.removeClass('error');
            }
        }
         if (step === 3) { // Specific validation for terms
            if (!$('#scme-agree-terms').is(':checked')) {
                isValid = false;
                $('#scme-agree-terms').closest('.scme-form-field').addClass('error');
            } else {
                 $('#scme-agree-terms').closest('.scme-form-field').removeClass('error');
            }
        }


        return isValid;
    }

    // Function to display form messages
    function showFormMessage(message, type = 'error') {
        $formMessage.removeClass('error success').addClass(type).text(message).fadeIn();
        // Automatically hide after 5 seconds
        setTimeout(() => {
            $formMessage.fadeOut();
        }, 5000);
    }


    // Next Step button click
    $('.scme-next-step').on('click', function() {
        if (validateStep(currentStep)) {
            // Populate summary before going to final step
            if (currentStep === 2) {
                const selectedServiceOption = $serviceSelect.find('option:selected');
                const serviceName = selectedServiceOption.data('name');
                const price = selectedServiceOption.data('price');
                const selectedDate = $datePicker.val();
                const selectedStartTime = $selectedStartTime.val();

                $('#summary-service-name').text(serviceName);
                $('#summary-date').text(new Date(selectedDate + 'T00:00:00').toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }));
                $('#summary-time').text(new Date(selectedStartTime).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit', hour12: true }));
                $('#summary-price').text(parseFloat(price).toFixed(2));

                // Set hidden fields for submission
                $('#scme-service-name-hidden').val(serviceName);
                $('#scme-price-hidden').val(price);
            }
            showStep(currentStep + 1);
        } else {
            showFormMessage('Please fill in all required fields.', 'error');
        }
    });

    // Previous Step button click
    $('.scme-prev-step').on('click', function() {
        showStep(currentStep - 1);
    });

    // Initial step display
    showStep(1);

    // --- Date Picker and Time Slot Logic ---

    // Set today's date as min date
    const today = new Date();
    const minDate = today.toISOString().split('T')[0];
    $datePicker.attr('min', minDate);

    // Fetch available slots when date or service changes
    function fetchAvailableSlots() {
        const selectedDate = $datePicker.val();
        const serviceDuration = $serviceSelect.find('option:selected').data('duration'); // in minutes

        if (!selectedDate || !serviceDuration) {
            $timeSlotsContainer.html('<p>Please select a date and a service to see available times.</p>');
            $timeSelectNextButton.prop('disabled', true);
            $selectedStartTime.val('');
            $selectedEndTime.val('');
            return;
        }

        $timeSlotsContainer.html('<p>Loading available times...</p>');
        $timeSelectNextButton.prop('disabled', true);
        $selectedStartTime.val('');
        $selectedEndTime.val('');

        $.ajax({
            url: scme_ajax_obj.rest_url + 'get-available-slots', // Using REST API endpoint
            method: 'POST',
            data: JSON.stringify({
                selected_date: selectedDate,
                service_duration: serviceDuration,
                nonce: scme_ajax_obj.nonce // For custom nonce validation
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', scme_ajax_obj.nonce); // For WP REST API nonce
            },
            success: function(response) {
                if (response.success && response.available_slots.length > 0) {
                    $timeSlotsContainer.empty();
                    response.available_slots.forEach(slot => {
                        const $slotDiv = $(`<div class="scme-time-slot" data-start="${slot.start}" data-end="${slot.end}">${slot.display_time}</div>`);
                        $timeSlotsContainer.append($slotDiv);
                    });
                    showFormMessage('Available slots loaded.', 'success');
                } else {
                    $timeSlotsContainer.html('<p>No available slots found for this date. Please try another date.</p>');
                    showFormMessage(response.message || 'No available slots found.', 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $timeSlotsContainer.html('<p>Error loading times. Please try again.</p>');
                showFormMessage('Error loading times: ' + (jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : errorThrown), 'error');
                console.error('AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
            }
        });
    }

    $datePicker.on('change', fetchAvailableSlots);
    $serviceSelect.on('change', fetchAvailableSlots);

    // Handle time slot selection
    $timeSlotsContainer.on('click', '.scme-time-slot', function() {
        $('.scme-time-slot').removeClass('selected');
        $(this).addClass('selected');
        $selectedStartTime.val($(this).data('start'));
        $selectedEndTime.val($(this).data('end'));
        $timeSelectNextButton.prop('disabled', false); // Enable Next button
    });

    // --- Final Form Submission ---
    $form.on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        if (!validateStep(currentStep)) {
            showFormMessage('Please fill in all required fields and agree to the terms.', 'error');
            return;
        }

        $('#scme-submit-booking').prop('disabled', true).text('Processing...');
        $formMessage.hide(); // Hide previous messages

        const formData = {
            client_name: $('#scme-client-name').val(),
            client_email: $('#scme-client-email').val(),
            client_phone: $('#scme-client-phone').val(),
            service_id: $('#scme-service-select').val(),
            service_name: $('#scme-service-name-hidden').val(),
            selected_start_time: $selectedStartTime.val(),
            selected_end_time: $selectedEndTime.val(),
            price: $('#scme-price-hidden').val(),
            nonce: scme_ajax_obj.nonce // For custom nonce validation
        };

        $.ajax({
            url: scme_ajax_obj.rest_url + 'initiate-booking', // Using REST API endpoint
            method: 'POST',
            data: JSON.stringify(formData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', scme_ajax_obj.nonce); // For WP REST API nonce
            },
            success: function(response) {
                if (response.success && response.redirect_url) {
                    showFormMessage('Redirecting to PayPal...', 'success');
                    window.location.href = response.redirect_url; // Redirect to PayPal
                } else {
                    showFormMessage(response.message || 'Booking initiation failed.', 'error');
                    $('#scme-submit-booking').prop('disabled', false).text('Pay Now with PayPal');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                showFormMessage('An error occurred: ' + (jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : errorThrown), 'error');
                $('#scme-submit-booking').prop('disabled', false).text('Pay Now with PayPal');
                console.error('AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
            }
        });
    });

});