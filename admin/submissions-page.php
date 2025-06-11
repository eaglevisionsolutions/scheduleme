<?php
function scme_submissions_page() {
    $submissions = SCME_Booking_Submission_Manager::get_all();
    ?>
    <div class="wrap">
        <h1>Bookings</h1>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th><th>Form</th><th>Name</th><th>Time</th><th>Status</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($submissions as $s): ?>
                <tr>
                    <td><?php echo $s->id; ?></td>
                    <td><?php echo get_the_title($s->form_id); ?></td>
                    <td><?php echo esc_html($s->user_name); ?></td>
                    <td><?php echo esc_html($s->booking_time); ?></td>
                    <td><?php echo esc_html($s->payment_status); ?></td>
                    <td><?php echo esc_html($s->created_at); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}