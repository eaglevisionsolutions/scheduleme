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
                if(field.type === 'step') {
                    // Render step header with edit
                    let html = `<div class="scme-form-builder-item scme-form-builder-step" data-idx="${idx}" style="background:#f1f1f1;padding:8px 10px;margin:10px 0 0 0;border-left:4px solid #0073aa;">
                        <strong>Step: <span class="scme-step-label">${field.label || 'Step'}</span></strong>
                        <button type="button" class="scme-edit-step button button-small" style="margin-left:10px;">Edit</button>
                        <button type="button" class="scme-remove-field button button-small" style="float:right;">Remove</button>
                        <form class="scme-edit-step-form" style="display:none;margin-top:8px;">
                            <input type="text" name="step_label" value="${field.label||''}" style="width:70%;" />
                            <button type="submit" class="button button-primary button-small">Save</button>
                            <button type="button" class="button button-secondary button-small scme-cancel-edit-step">Cancel</button>
                        </form>
                    </div>`;
                    $dropzone.append(html);
                } else if(field.type === 'heading') {
                    // Render heading with edit
                    let html = `<div class="scme-form-builder-item scme-form-builder-heading" data-idx="${idx}">
                        <strong>Heading: ${field.text || 'Heading'}</strong>
                        <button type="button" class="scme-edit-heading button button-small" style="margin-left:10px;">Edit</button>
                        <button type="button" class="scme-remove-field button button-small" style="float:right;">Remove</button>
                        <form class="scme-edit-heading-form" style="display:none;margin-top:8px;">
                            <input type="text" name="heading_text" value="${field.text||''}" style="width:70%;" />
                             <label>Heading Level:
        <select name="heading_level">
            <option value="h1" ${field.level === 'h1' ? 'selected' : ''}>H1</option>
            <option value="h2" ${field.level === 'h2' ? 'selected' : ''}>H2</option>
            <option value="h3" ${field.level === 'h3' ? 'selected' : ''}>H3</option>
            <option value="h4" ${field.level === 'h4' ? 'selected' : ''}>H4</option>
            <option value="h5" ${field.level === 'h5' ? 'selected' : ''}>H5</option>
            <option value="h6" ${field.level === 'h6' ? 'selected' : ''}>H6</option>
        </select>
    </label><br>
                            <button type="submit" class="button button-primary button-small">Save</button>
                            <button type="button" class="button button-secondary button-small scme-cancel-edit-heading">Cancel</button>
                        </form>
                    </div>`;
                    $dropzone.append(html);
                } else if(field.type === 'recaptcha_v2' || field.type === 'recaptcha_v3') {
                    let html = `<div class="scme-form-builder-item scme-form-builder-recaptcha" data-idx="${idx}">
                        <strong>${field.label}</strong>
                        <span style="color:#888;">[${field.type === 'recaptcha_v2' ? 'reCAPTCHA v2' : 'reCAPTCHA v3'}]</span>
                        <button type="button" class="scme-remove-field button button-small" style="float:right;">Remove</button>
                    </div>`;
                    $dropzone.append(html);
                    return;
                } else {
                    // Render normal field
                    let label = field.label || field.type.charAt(0).toUpperCase() + field.type.slice(1);
                    let optionsDisplay = '';
                    if(['radio','select','checkbox'].includes(field.type) && Array.isArray(field.options)) {
                        optionsDisplay = '<br><small>Options: ' + field.options.map(opt => `${opt.label} (${opt.value})`).join(', ') + '</small>';
                    }
                    let html = `<div class="scme-form-builder-item scme-form-builder-field" data-idx="${idx}">
                        <strong>${label}</strong> <span style="color:#888;">[${field.type}]</span>
                        <br><small>${field.name || ''}</small>
                        ${optionsDisplay}
                        <div class="scme-field-actions">
                            <button type="button" class="scme-edit-field button button-small">Edit</button>
                            <button type="button" class="scme-remove-field button button-small">Remove</button>
                        </div>
                        <form class="scme-edit-field-form" style="display:none; margin-top:10px; background:#f9f9f9; border:1px solid #eee; padding:10px; border-radius:4px;">
                            <label>Label: <input type="text" name="label" value="${field.label||''}" /></label><br>
                            <label>Show Label: <input type="checkbox" name="show_label" ${field.show_label !== false ? 'checked' : ''} /></label><br>
                            <label>Field Name: <input type="text" name="name" value="${field.name||''}" /></label><br>
                            <label>Placeholder: <input type="text" name="placeholder" value="${field.placeholder||''}" /></label><br>
                            <label>Required: <input type="checkbox" name="required" ${field.required ? 'checked' : ''} /></label><br>
                            <label>Regex: <input type="text" name="regex" value="${field.regex||''}" /></label><br>
                            ${(['radio','select','checkbox'].includes(field.type)) ? `
                                <label>
                                    Options:<br>
                                    <textarea name="options" rows="4" style="width:98%;" placeholder="Label 1|value1\nLabel 2|value2">${field.options_raw || ''}</textarea>
                                    <small>
                                        Enter one option per line as <code>Label|value</code>.<br>
                                        ${field.type === 'checkbox' ? 'Checkboxes are limited to 2 options.' : ''}
                                    </small>
                                </label><br>
                            ` : ''}
                            <div style="margin-top:8px;">
                                <button type="submit" class="button button-primary button-small">Save</button>
                                <button type="button" class="button button-secondary button-small scme-cancel-edit">Cancel</button>
                            </div>
                        </form>
                    </div>`;
                    $dropzone.append(html);
                }
            });
            $input.val(JSON.stringify(fields));
        }
        renderFields();

        // Make widgets draggable
        $('.scme-widget').draggable({
            helper: "clone",
            connectToSortable: "#scme-form-builder-dropzone",
            revert: "invalid",
            appendTo: 'body',
            zIndex: 10000,
            start: function(e, ui) {
                ui.helper.width($(this).width());
            }
        });

        // Make dropzone sortable and accept external widgets
        $dropzone.sortable({
            items: '.scme-form-builder-item',
            placeholder: 'scme-form-builder-placeholder',
            handle: null, // or specify a handle if you want (e.g., '.scme-drag-handle')
            receive: function(event, ui) {
                if (ui.item.hasClass('scme-widget')) {
                    let type = ui.item.data('type');
                    if(type === 'step') {
                        let newStep = {type: 'step', label: 'Step'};
                        let idx = ui.item.index();
                        fields.splice(idx, 0, newStep);
                        renderFields();
                        showEditStepForm(idx); // Open step edit form immediately
                    } else {
                        let label = type.charAt(0).toUpperCase() + type.slice(1);
                        let name = type + '_' + (fields.length+1);
                        let required = false;
                        let regex = '';
                        let options = '';
                        let options_raw = '';
                        if(['select','radio','checkbox'].includes(type)) {
                            options_raw = 'Option 1|option1\nOption 2|option2';
                        }
                        let newField = {type, label, name, placeholder:'', required, regex, options, options_raw};
                        let idx = ui.item.index();
                        fields.splice(idx, 0, newField);
                        renderFields();
                        showEditForm(idx); // Open inline edit form immediately
                    }
                    setTimeout(function(){ $dropzone.find('.scme-widget').remove(); }, 10);
                } else {
                    let newOrder = [];
                    $dropzone.children('.scme-form-builder-item').each(function(){
                        let idx = $(this).data('idx');
                        newOrder.push(fields[idx]);
                    });
                    fields = newOrder;
                    renderFields();
                }
            },
            update: function(event, ui) {
                if (!ui.item.hasClass('scme-widget')) {
                    let newOrder = [];
                    $dropzone.children('.scme-form-builder-item').each(function(){
                        let idx = $(this).data('idx');
                        newOrder.push(fields[idx]);
                    });
                    fields = newOrder;
                    renderFields();
                }
            }
        });

        // Remove Field or Step
        $dropzone.on('click', '.scme-remove-field', function(){
            let idx = $(this).closest('[data-idx]').data('idx');
            if (confirm('Remove this item?')) {
                fields.splice(idx,1);
                renderFields();
            }
        });

        // Edit Field (show inline form)
        $dropzone.on('click', '.scme-edit-field', function(){
            let $field = $(this).closest('.scme-form-builder-field');
            $dropzone.find('.scme-edit-field-form').hide();
            $field.find('.scme-edit-field-form').slideDown(150);
        });

        // Cancel Edit Field
        $dropzone.on('click', '.scme-cancel-edit', function(e){
            e.preventDefault();
            $(this).closest('.scme-edit-field-form').slideUp(150);
        });

        // Save Edit Field
        $dropzone.on('submit', '.scme-edit-field-form', function(e){
            e.preventDefault();
            let $form = $(this);
            let $field = $form.closest('.scme-form-builder-field');
            let idx = $field.data('idx');
            let f = fields[idx];
            f.label = $form.find('[name="label"]').val();
            f.name = $form.find('[name="name"]').val();
            f.placeholder = $form.find('[name="placeholder"]').val();
            f.required = $form.find('[name="required"]').is(':checked');
            f.regex = $form.find('[name="regex"]').val();
            f.show_label = $form.find('[name="show_label"]').is(':checked');
            if(['radio','select','checkbox'].includes(f.type)) {
                f.options_raw = $form.find('[name="options"]').val();
                let optionsArr = f.options_raw
                    .split('\n')
                    .map(line => {
                        const [label, value] = line.split('|');
                        return { label: (label||'').trim(), value: (value||'').trim() };
                    })
                    .filter(opt => opt.label && opt.value);
                if(f.type === 'checkbox') {
                    optionsArr = optionsArr.slice(0, 2); // Limit to 2 options for checkboxes
                }
                f.options = optionsArr;
            }
            fields[idx] = f;
            renderFields();
        });

        // Edit Step (show inline form)
        $dropzone.on('click', '.scme-edit-step', function(){
            let $step = $(this).closest('.scme-form-builder-step');
            $dropzone.find('.scme-edit-step-form').hide();
            $step.find('.scme-edit-step-form').slideDown(150);
        });

        // Cancel Edit Step
        $dropzone.on('click', '.scme-cancel-edit-step', function(e){
            e.preventDefault();
            $(this).closest('.scme-edit-step-form').slideUp(150);
        });

        // Save Edit Step
        $dropzone.on('submit', '.scme-edit-step-form', function(e){
            e.preventDefault();
            let $form = $(this);
            let $step = $form.closest('.scme-form-builder-step');
            let idx = $step.data('idx');
            let f = fields[idx];
            f.label = $form.find('[name="step_label"]').val();
            fields[idx] = f;
            renderFields();
        });

        // Edit Heading (show inline form)
        $dropzone.on('click', '.scme-edit-heading', function(){
            let $heading = $(this).closest('.scme-form-builder-heading');
            $dropzone.find('.scme-edit-heading-form').hide();
            $heading.find('.scme-edit-heading-form').slideDown(150);
        });

        // Cancel Edit Heading
        $dropzone.on('click', '.scme-cancel-edit-heading', function(e){
            e.preventDefault();
            $(this).closest('.scme-edit-heading-form').slideUp(150);
        });

        // Save Edit Heading
        $dropzone.on('submit', '.scme-edit-heading-form', function(e){
            e.preventDefault();
            let $form = $(this);
            let $heading = $form.closest('.scme-form-builder-heading');
            let idx = $heading.data('idx');
            let f = fields[idx];
            f.text = $form.find('[name="heading_text"]').val();
            f.level = $form.find('[name="heading_level"]').val() || 'h2';
            fields[idx] = f;
            renderFields();
        });

        // Helper to open edit form for a field
        function showEditForm(idx) {
            let $field = $dropzone.find(`.scme-form-builder-field[data-idx="${idx}"]`);
            $dropzone.find('.scme-edit-field-form').hide();
            $field.find('.scme-edit-field-form').slideDown(150);
        }
        // Helper to open edit form for a step
        function showEditStepForm(idx) {
            let $step = $dropzone.find(`.scme-form-builder-step[data-idx="${idx}"]`);
            $dropzone.find('.scme-edit-step-form').hide();
            $step.find('.scme-edit-step-form').slideDown(150);
        }
    });
};