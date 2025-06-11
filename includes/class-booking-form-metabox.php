<?php
class SCME_Booking_Form_Metabox {
    public static function add() {
        add_meta_box(
            'scme_form_fields',
            'Form Builder',
            [self::class, 'render'],
            'scme_booking_form',
            'normal',
            'default'
        );
        add_meta_box(
            'scme_form_style',
            'Form Style',
            [self::class, 'render_style'],
            'scme_booking_form',
            'side',
            'default'
        );
    }

    public static function render($post) {
        $fields = get_post_meta($post->ID, '_scme_form_fields', true);
        $fields = $fields ? json_decode($fields, true) : [];
        ?>
        <style>
        .scme-widget-palette { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
        .scme-widget { background: #f5f5f5; border: 1px solid #ccc; border-radius: 4px; padding: 8px 12px; cursor: grab; margin-bottom: 0; }
        .scme-form-builder-dropzone { min-height: 60px; border: 2px dashed #0073aa; padding: 10px; background: #fafdff; margin-bottom: 10px; }
        .scme-form-builder-field { border:1px solid #ddd; padding:10px; margin-bottom:8px; background:#fafafa; cursor:move; position:relative; }
        .scme-form-builder-field .scme-field-actions { position:absolute; top:5px; right:5px; }
        .scme-form-builder-field.selected { border-color:#0073aa; background:#eaf6fb; }
        </style>
        <div class="scme-widget-palette">
            <div class="scme-widget" data-type="text">Text</div>
            <div class="scme-widget" data-type="email">Email</div>
            <div class="scme-widget" data-type="number">Number</div>
            <div class="scme-widget" data-type="textarea">Textarea</div>
            <div class="scme-widget" data-type="select">Select</div>
            <div class="scme-widget" data-type="radio">Radio</div>
            <div class="scme-widget" data-type="checkbox">Checkbox</div>
            <div class="scme-widget" data-type="date">Date</div>
            <div class="scme-widget" data-type="file">File</div>
            <div class="scme-widget" data-type="password">Password</div>
            <div class="scme-widget" data-type="url">URL</div>
            <div class="scme-widget" data-type="tel">Tel</div>
            <div class="scme-widget" data-type="color">Color</div>
            <div class="scme-widget" data-type="range">Range</div>
            <div class="scme-widget" data-type="hidden">Hidden</div>
        </div>
        <div id="scme-form-builder-dropzone" class="scme-form-builder-dropzone"></div>
        <input type="hidden" id="scme_form_fields_input" name="scme_form_fields" value='<?php echo esc_attr(json_encode($fields)); ?>' />
        <script>
        jQuery(function($){
            let $dropzone = $('#scme-form-builder-dropzone');
            let $input = $('#scme_form_fields_input');
            let fields = <?php echo json_encode($fields); ?> || [];

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
        </script>
        <p><em>Drag widgets from above into the form area. Click "Edit" to configure each field. Drag fields to reorder. For production, use a full-featured JS form builder.</em></p>
        <?php
    }

    public static function render_style($post) {
        $style = get_post_meta($post->ID, '_scme_form_style', true) ?: '';
        ?>
        <label for="scme_form_style">Custom CSS for this form:</label>
        <textarea name="scme_form_style" id="scme_form_style" style="width:100%;min-height:80px;"><?php echo esc_textarea($style); ?></textarea>
        <p><em>You can add custom CSS here to style your form fields.</em></p>
        <?php
    }

    public static function save($post_id) {
        if (isset($_POST['scme_form_fields'])) {
            update_post_meta($post_id, '_scme_form_fields', wp_unslash($_POST['scme_form_fields']));
        }
        if (isset($_POST['scme_form_style'])) {
            update_post_meta($post_id, '_scme_form_style', wp_unslash($_POST['scme_form_style']));
        }
    }
}
add_action('add_meta_boxes', ['SCME_Booking_Form_Metabox', 'add']);
add_action('save_post_scme_booking_form', ['SCME_Booking_Form_Metabox', 'save']);