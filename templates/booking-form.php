<?php
if (empty($form_id)) {
    echo '<p>No form found.</p>';
    return;
}
$fields = get_post_meta($form_id, '_scme_form_fields', true);
$fields = $fields ? json_decode($fields, true) : [];
$style = get_post_meta($form_id, '_scme_form_style', true);
if ($style) {
    echo '<style>' . $style . '</style>';
}
$steps = [];
foreach ($fields as $f) {
    $step = isset($f['step']) ? intval($f['step']) : 1;
    if (!isset($steps[$step])) $steps[$step] = [];
    $steps[$step][] = $f;
}
ksort($steps);
$step_count = count($steps);
?>
<div id="scme-booking-form-wrapper" class="scme-multi-step-form">
    <div class="scme-form-progress">
        <?php foreach (range(1, $step_count) as $s): ?>
            <span class="scme-step-indicator<?php if($s==1) echo ' active'; ?>" data-step="<?php echo $s; ?>"><?php echo $s; ?></span>
        <?php endforeach; ?>
    </div>
    <form id="scme-booking-form">
        <?php foreach ($steps as $step => $fields): ?>
        <div class="scme-form-step<?php if($step==1) echo ' active'; ?>" data-step="<?php echo $step; ?>">
            <?php foreach ($fields as $f): ?>
                <div class="scme-form-field">
                    <?php if ($f['show_label'] !== false): // Show label unless explicitly set to false ?>
                        <label for="scme-<?php echo esc_attr($f['name']); ?>">
                            <?php echo esc_html($f['label']); ?>
                            <?php if (!empty($f['required'])) echo ' *'; ?>
                        </label>
                    <?php endif; ?>
                    <?php
                    $type = $f['type'];
                    $name = esc_attr($f['name']);
                    $placeholder = esc_attr($f['placeholder'] ?? '');
                    $required = !empty($f['required']) ? 'required' : '';
                    $regex = !empty($f['regex']) ? esc_attr($f['regex']) : '';
                    $field_id = 'scme-' . $name;
                    switch ($type) {
                        case 'textarea':
                            echo "<textarea id='$field_id' name='$name' placeholder='$placeholder' $required></textarea>";
                            break;
                        case 'select':
                            echo "<select id='$field_id' name='$name' $required>";
                            echo "<option value=''>-- Select --</option>";
                            $opts = explode(',', $f['options'] ?? '');
                            foreach ($opts as $opt) {
                                $opt = trim($opt);
                                echo "<option value='" . esc_attr($opt) . "'>$opt</option>";
                            }
                            echo "</select>";
                            break;
                        case 'radio':
                            $opts = explode(',', $f['options'] ?? '');
                            foreach ($opts as $opt) {
                                $opt = trim($opt);
                                echo "<label><input type='radio' name='$name' value='" . esc_attr($opt) . "' $required> $opt</label> ";
                            }
                            break;
                        case 'checkbox':
                            $opts = explode(',', $f['options'] ?? '');
                            if (count($opts) > 1) {
                                foreach ($opts as $opt) {
                                    $opt = trim($opt);
                                    echo "<label><input type='checkbox' name='{$name}[]' value='" . esc_attr($opt) . "'> $opt</label> ";
                                }
                            } else {
                                echo "<input type='checkbox' id='$field_id' name='$name' value='1' $required>";
                            }
                            break;
                        case 'heading':
                            $level = in_array($f['level'], ['h1','h2','h3','h4','h5','h6']) ? $f['level'] : 'h2';
                            echo '<' . $level . '>' . esc_html($f['text']) . '</' . $level . '>';
                            break;
                        case 'recaptcha_v2':
                            // Output reCAPTCHA v2 widget
                            echo '<div class="scme-recaptcha-v2"></div>';
                            break;
                        case 'recaptcha_v3':
                            // Output reCAPTCHA v3 widget
                            echo '<div class="scme-recaptcha-v3"></div>';
                            break;
                        default:
                            echo "<input type='$type' id='$field_id' name='$name' placeholder='$placeholder' $required" .
                                ($regex ? " pattern='$regex'" : "") . ">";
                    }
                    ?>
                </div>
            <?php endforeach; ?>
            <div class="scme-form-step-buttons">
                <?php if ($step > 1): ?>
                    <button type="button" class="scme-prev-step">Previous</button>
                <?php endif; ?>
                <?php if ($step < $step_count): ?>
                    <button type="button" class="scme-next-step">Next</button>
                <?php else: ?>
                    <button type="submit" id="scme-submit-booking">Submit</button>
                <?php endif; ?>
            </div>
            <div id="scme-form-message" style="margin-top: 15px; color: red;"></div>
        </div>
        <?php endforeach; ?>
    </form>
</div>
<script>
jQuery(function($){
    let currentStep = 1;
    const $form = $('#scme-booking-form');
    const $formSteps = $('.scme-form-step');
    const $stepIndicators = $('.scme-step-indicator');
    function showStep(step) {
        $formSteps.removeClass('active').hide();
        $(`.scme-form-step[data-step="${step}"]`).addClass('active').show();
        $stepIndicators.removeClass('active');
        $(`.scme-step-indicator[data-step="${step}"]`).addClass('active');
        currentStep = step;
        $('html, body').animate({ scrollTop: $form.offset().top - 50 }, 300);
    }
    $('.scme-next-step').on('click', function() {
        showStep(currentStep + 1);
    });
    $('.scme-prev-step').on('click', function() {
        showStep(currentStep - 1);
    });
    showStep(1);
    // Add custom validation if needed
    $form.on('submit', function(e){
        let valid = true;
        $form.find('[required]').each(function(){
            if (!$(this).val()) valid = false;
        });
        if (!valid) {
            e.preventDefault();
            $('.scme-form-step.active #scme-form-message').text('Please fill all required fields.');
        }
    });
});
</script>
<?php
$keys = scme_get_recaptcha_keys('v2'); // or 'v3'
?>

