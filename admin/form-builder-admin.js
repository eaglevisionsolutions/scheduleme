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
                    <form class="scme-edit-field-form" style="display:none; margin-top:10px; background:#f9f9f9; border:1px solid #eee; padding:10px; border-radius:4px;">
                        <label>Label: <input type="text" name="label" value="${field.label||''}" /></label><br>
                        <label>Field Name: <input type="text" name="name" value="${field.name||''}" /></label><br>
                        <label>Placeholder: <input type="text" name="placeholder" value="${field.placeholder||''}" /></label><br>
                        <label>Step: <input type="number" name="step" value="${field.step||1}" min="1" style="width:60px;" /></label><br>
                        <label>Required: <input type="checkbox" name="required" ${field.required ? 'checked' : ''} /></label><br>
                        <label>Regex: <input type="text" name="regex" value="${field.regex||''}" /></label><br>
                        ${( ['select','radio','checkbox'].includes(field.type) ? `<label>Options (comma separated): <input type="text" name="options" value="${field.options||''}" /></label><br>` : '' )}
                        <div style="margin-top:8px;">
                            <button type="submit" class="button button-primary button-small">Save</button>
                            <button type="button" class="button button-secondary button-small scme-cancel-edit">Cancel</button>
                        </div>
                    </form>
                </div>`;
                $dropzone.append(html);
            });
            $input.val(JSON.stringify(fields));
        }
        renderFields();

        // Make widgets draggable (but not removable from palette)
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
            items: '.scme-form-builder-field',
            placeholder: 'scme-form-builder-placeholder',
            receive: function(event, ui) {
                // Only add new field if coming from palette
                if (ui.item.hasClass('scme-widget')) {
                    let type = ui.item.data('type');
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
                    // Insert at correct position
                    let idx = ui.item.index();
                    fields.splice(idx, 0, newField);
                    renderFields();
                    showEditForm(idx); // Open inline edit form immediately
                    // Remove the palette widget clone
                    setTimeout(function(){ $dropzone.find('.scme-widget').remove(); }, 10);
                } else {
                    // Reorder existing fields
                    let newOrder = [];
                    $dropzone.children('.scme-form-builder-field').each(function(){
                        let idx = $(this).data('idx');
                        newOrder.push(fields[idx]);
                    });
                    fields = newOrder;
                    renderFields();
                }
            },
            update: function(event, ui) {
                // Only reorder if not from palette
                if (!ui.item.hasClass('scme-widget')) {
                    let newOrder = [];
                    $dropzone.children('.scme-form-builder-field').each(function(){
                        let idx = $(this).data('idx');
                        newOrder.push(fields[idx]);
                    });
                    fields = newOrder;
                    renderFields();
                }
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

        // Edit Field (show inline form)
        $dropzone.on('click', '.scme-edit-field', function(){
            let $field = $(this).closest('.scme-form-builder-field');
            $dropzone.find('.scme-edit-field-form').hide(); // Hide any open forms
            $field.find('.scme-edit-field-form').slideDown(150);
        });

        // Cancel Edit
        $dropzone.on('click', '.scme-cancel-edit', function(e){
            e.preventDefault();
            $(this).closest('.scme-edit-field-form').slideUp(150);
        });

        // Save Edit
        $dropzone.on('submit', '.scme-edit-field-form', function(e){
            e.preventDefault();
            let $form = $(this);
            let $field = $form.closest('.scme-form-builder-field');
            let idx = $field.data('idx');
            let f = fields[idx];
            f.label = $form.find('[name="label"]').val();
            f.name = $form.find('[name="name"]').val();
            f.placeholder = $form.find('[name="placeholder"]').val();
            f.step = parseInt($form.find('[name="step"]').val()) || 1;
            f.required = $form.find('[name="required"]').is(':checked');
            f.regex = $form.find('[name="regex"]').val();
            if(['select','radio','checkbox'].includes(f.type)) {
                f.options = $form.find('[name="options"]').val();
            }
            fields[idx] = f;
            renderFields();
        });

        // Helper to open edit form for a field
        function showEditForm(idx) {
            let $field = $dropzone.find(`.scme-form-builder-field[data-idx="${idx}"]`);
            $dropzone.find('.scme-edit-field-form').hide();
            $field.find('.scme-edit-field-form').slideDown(150);
        }
    });
};