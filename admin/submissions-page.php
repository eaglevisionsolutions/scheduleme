<?php
function scme_submissions_page() {
    $submissions = SCME_Booking_Submission_Manager::get_all();
    ?>
    <div class="wrap">
        <h1>Booking Submissions</h1>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th><th>Form</th><th>Name</th><th>Email</th><th>Service</th><th>Time</th><th>Status</th><th>Payment ID</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($submissions as $s): ?>
                <tr>
                    <td><?php echo $s->id; ?></td>
                    <td><?php echo get_the_title($s->form_id); ?></td>
                    <td><?php echo esc_html($s->user_name); ?></td>
                    <td><?php echo esc_html($s->user_email); ?></td>
                    <td><?php echo esc_html($s->service); ?></td>
                    <td><?php echo esc_html($s->booking_time); ?></td>
                    <td><?php echo esc_html($s->payment_status); ?></td>
                    <td><?php echo esc_html($s->payment_id); ?></td>
                    <td><?php echo esc_html($s->created_at); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
function scme_register_submissions_page() {
    add_menu_page('Booking Submissions', 'Booking Submissions', 'manage_options', 'scme-submissions', 'scme_submissions_page', 'dashicons-list-view', 26);
}
add_action('admin_menu', 'scme_register_submissions_page');