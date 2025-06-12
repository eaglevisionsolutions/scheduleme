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
                    editField(idx); // Open edit dialog immediately
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