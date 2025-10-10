<?php
/**
 * Booking Public Class
 * Developed by Archeus Catalyst
 */

if (!defined('ABSPATH')) {
    exit;
}

class Booking_Public {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('wp_ajax_get_available_time_slots', array($this, 'handle_get_available_time_slots'));
        add_action('wp_ajax_nopriv_get_available_time_slots', array($this, 'handle_get_available_time_slots'));
        // Add action for booking flow submission
        add_action('wp_ajax_submit_booking_flow', array($this, 'handle_submit_booking_flow'));
        add_action('wp_ajax_nopriv_submit_booking_flow', array($this, 'handle_submit_booking_flow'));

        // Add Elementor support
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'enqueue_elementor_scripts'));
        add_action('elementor/frontend/before_enqueue_scripts', array($this, 'enqueue_elementor_scripts'));
    }

    // Note: auto-translation of labels to field keys removed by request.

    /**
     * Enqueue public scripts
     */
    public function enqueue_public_scripts() {
        global $post;

        // Check if we need to enqueue scripts in various contexts
        $should_enqueue = false;

        // Check in normal post context
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'archeus_booking') ||
            has_shortcode($post->post_content, 'archeus_booking_calendar')
        )) {
            $should_enqueue = true;
        }

        // Check in Elementor editor context
        if (defined('ELEMENTOR_VERSION') && (
            (isset($_GET['action']) && $_GET['action'] === 'elementor') ||
            (isset($_GET['elementor-preview']) && $_GET['elementor-preview'] > 0) ||
            (isset($_GET['elementor-mode']) && $_GET['elementor-mode'] === 'preview')
        )) {
            $should_enqueue = true;
        }

        // Check in WordPress editor context
        if (is_admin() && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen) {
                // Check if we're in post/page editor or block editor
                if (in_array($screen->base, array('post', 'page')) ||
                    $screen->base === 'widgets' ||
                    (method_exists($screen, 'is_block_editor') && $screen->is_block_editor())) {
                    $should_enqueue = true;
                }
            }
        }

        // Always enqueue for preview contexts
        if (isset($_GET['preview']) || isset($_GET['preview_id'])) {
            $should_enqueue = true;
        }

        // Enqueue scripts if needed
        if ($should_enqueue) {
            // Enqueue scripts needed for calendar and booking flow
            wp_enqueue_script('booking-calendar-js', ARCHEUS_BOOKING_URL . 'assets/js/calendar.js', array('jquery'), ARCHEUS_BOOKING_VERSION . '.1', true);
            wp_enqueue_script('booking-flow-js', ARCHEUS_BOOKING_URL . 'assets/js/booking-flow.js', array('jquery'), ARCHEUS_BOOKING_VERSION, true);

            // Enqueue public styles (explicit files to preserve original cascade/order)
            wp_enqueue_style('booking-calendar-css', ARCHEUS_BOOKING_URL . 'assets/css/calendar.css', array(), ARCHEUS_BOOKING_VERSION);
            wp_enqueue_style('booking-flow-css', ARCHEUS_BOOKING_URL . 'assets/css/booking-flow.css', array(), ARCHEUS_BOOKING_VERSION);
            wp_enqueue_style('services-css', ARCHEUS_BOOKING_URL . 'assets/css/services.css', array(), ARCHEUS_BOOKING_VERSION);
            wp_enqueue_style('time-slots-css', ARCHEUS_BOOKING_URL . 'assets/css/time-slots.css', array(), ARCHEUS_BOOKING_VERSION);

            // Build AJAX URL with correct scheme to avoid mixed-content issues
            $ajax_url = admin_url('admin-ajax.php');
            if (function_exists('is_ssl') && is_ssl()) {
                $ajax_url = admin_url('admin-ajax.php', 'https');
            }
            if (function_exists('wp_make_link_relative')) {
                $rel = wp_make_link_relative($ajax_url);
                if (!empty($rel) && strpos($rel, '/wp-admin/admin-ajax.php') === 0) {
                    $ajax_url = $rel;
                }
            }

            // Localizations

            // Localize scripts with AJAX URL and nonces
            wp_localize_script('booking-calendar-js', 'calendar_ajax', array(
                'ajax_url' => $ajax_url,
                'nonce' => wp_create_nonce('calendar_nonce'),
                'max_months' => get_option('booking_calendar_max_months', 6)
            ));

            wp_localize_script('booking-flow-js', 'booking_flow_ajax', array(
                'ajax_url' => $ajax_url,
                'nonce' => wp_create_nonce('booking_flow_nonce')
            ));
        }
    }
    
    /**
     * Get booked dates from database
     */
    private function get_booked_dates() {
        // Aggregate booked dates across all per-flow tables
        $booking_db = new Booking_Database();
        global $wpdb;
        $today = date('Y-m-d');
        $dates = array();
        $flows = $booking_db->get_booking_flows();
        foreach ($flows as $flow) {
            $table = $booking_db->get_flow_table_name($flow->name);
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($exists !== $table) { continue; }
            $statuses = get_option('booking_blocking_statuses', array('approved', 'completed'));
            if (!is_array($statuses) || empty($statuses)) { continue; }
            $ph = implode(',', array_fill(0, count($statuses), '%s'));
            $args = array_merge(array($today), $statuses);
            $sql = "SELECT DISTINCT booking_date FROM {$table} WHERE booking_date >= %s AND status IN ($ph)";
            $rows = $wpdb->get_col($wpdb->prepare($sql, $args));
            if (is_array($rows)) {
                foreach ($rows as $d) { $dates[$d] = true; }
            }
        }
        return array_keys($dates);
    }
    
    /**
     * Handle booked dates request via AJAX
     */
    public function handle_get_booked_dates() {
        $booked_dates = $this->get_booked_dates();
        wp_send_json_success($booked_dates);
    }

    /**
     * Handle booking submission via AJAX
     */
    
    
    /**
     * Send confirmation email to customer
     */
    private function send_confirmation_email($booking_data) {
        // Get email settings - use centralized defaults from admin
        $email_settings = get_option('booking_email_settings', array(
            'enable_customer_confirmation' => 1,
            'customer_confirmation_subject' => __('Konfirmasi Reservasi #{booking_id} - {service_type}', 'archeus-booking'),
            'customer_confirmation_body' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #54b335;">Konfirmasi Reservasi Anda</h2>
        <p>{greeting}</p>
        <p>Terima kasih telah melakukan reservasi dengan kami. Berikut adalah detail reservasi Anda:</p>

        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #54b335;">Detail Reservasi</h3>
            <p><strong>ID Reservasi:</strong> {booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time} {time_slot}</p>
            <p><strong>Email:</strong> {customer_email}</p>
        </div>

        <p>Kami akan menghubungi Anda segera untuk mengkonfirmasi reservasi ini.</p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">Hormat kami,<br>{company_name}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Email ini dikirim pada {current_date} pukul {current_time}
            </p>
        </div>
    </div>
</body>
</html>', 'archeus-booking')
        ));
        
        // Check if customer confirmation is enabled
        if (!$email_settings['enable_customer_confirmation']) {
            return;
        }
        
        $to = isset($booking_data['customer_email']) ? $booking_data['customer_email'] : '';
        $subject = $this->build_email_subject($booking_data, $email_settings['customer_confirmation_subject']);
        $message = $this->build_custom_email_content($booking_data, $email_settings['customer_confirmation_body'], 'customer');
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send notification email to admin
     */
    private function send_admin_notification($booking_data) {
        // Get email settings
        $email_settings = get_option('booking_email_settings', array(
            'enable_admin_notification' => 1,
            'admin_notification_subject' => __('Reservasi Baru #{booking_id} - {service_type}', 'archeus-booking'),
            'admin_notification_body' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #54b335;">Reservasi Baru Diterima</h2>
        <p>{greeting}</p>
        <p>Reservasi baru telah masuk dan membutuhkan perhatian Anda. Berikut adalah detail reservasi:</p>

        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #54b335;">Detail Reservasi</h3>
            <p><strong>ID Reservasi:</strong> {booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time} {time_slot}</p>
            <p><strong>Email Pelanggan:</strong> {customer_email}</p>
        </div>

        <p>Segera hubungi pelanggan untuk mengkonfirmasi reservasi ini.</p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">Salam admin,<br>{company_name}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Email ini dikirim pada {current_date} pukul {current_time}
            </p>
        </div>
    </div>
</body>
</html>', 'archeus-booking')
        ));
        
        // Check if admin notification is enabled
        if (!$email_settings['enable_admin_notification']) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $subject = $this->build_email_subject($booking_data, $email_settings['admin_notification_subject']);
        $message = $this->build_custom_email_content($booking_data, $email_settings['admin_notification_body'], 'admin');
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Build custom email content with full HTML template support
     */
    private function build_custom_email_content($booking_data, $template, $recipient_type) {
        // Replace all available tags in the template
        $message = $template;

        // Get customer information
        $display_name = isset($booking_data['customer_name']) ? $booking_data['customer_name'] : '';
        $customer_email = isset($booking_data['customer_email']) ? $booking_data['customer_email'] : '';

        // Basic booking information
        $booking_date = isset($booking_data['booking_date']) ? $booking_data['booking_date'] : '';
        $booking_time = isset($booking_data['booking_time']) ? $booking_data['booking_time'] : '';
        $service_type = isset($booking_data['service_type']) ? $booking_data['service_type'] : '';
        $time_slot = isset($booking_data['time_slot']) ? $booking_data['time_slot'] : '';

        // Replace basic tags
        $message = str_replace('{customer_name}', $display_name, $message);
        $message = str_replace('{customer_email}', $customer_email, $message);
        $message = str_replace('{booking_date}', $booking_date, $message);
        $message = str_replace('{booking_time}', $booking_time, $message);
        $message = str_replace('{service_type}', $service_type, $message);
        $message = str_replace('{time_slot}', $time_slot, $message);

        // Company information
        $message = str_replace('{company_name}', get_bloginfo('name'), $message);
        $message = str_replace('{company_url}', get_bloginfo('url'), $message);
        $message = str_replace('{admin_email}', get_option('admin_email'), $message);

        // Recipient-specific tags
        if ($recipient_type === 'customer') {
            $message = str_replace('{email_title}', __('Konfirmasi Reservasi Anda', 'archeus-booking'), $message);
            $message = str_replace('{greeting}', sprintf(__('Halo %s,', 'archeus-booking'), $display_name), $message);
        } else {
            $message = str_replace('{email_title}', __('Reservasi Baru Diterima', 'archeus-booking'), $message);
            $message = str_replace('{greeting}', __('Halo Admin,', 'archeus-booking'), $message);
        }

        // Current date/time
        $message = str_replace('{current_date}', date_i18n(get_option('date_format')), $message);
        $message = str_replace('{current_time}', date_i18n(get_option('time_format')), $message);
        $message = str_replace('{current_datetime}', date_i18n(get_option('date_format') . ' ' . get_option('time_format')), $message);

        // Replace additional fields dynamically
        if (!empty($booking_data['additional_fields'])) {
            $extra = is_array($booking_data['additional_fields']) ? $booking_data['additional_fields'] : @maybe_unserialize($booking_data['additional_fields']);
            if (is_array($extra)) {
                foreach ($extra as $key => $value) {
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                    $message = str_replace('{' . $key . '}', $value, $message);
                }
            }
        }

        // Replace any remaining fields from booking data
        foreach ($booking_data as $key => $value) {
            if (!in_array($key, array('customer_name', 'customer_email', 'booking_date', 'booking_time', 'service_type', 'time_slot', 'additional_fields'))) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $message = str_replace('{' . $key . '}', $value, $message);
            }
        }

        // If template doesn't contain HTML structure, wrap it with default template
        if (strpos($message, '<html') === false && strpos($message, '<!DOCTYPE') === false) {
            $message = $this->wrap_email_template($message, $recipient_type);
        }

        return $message;
    }

    /**
     * Wrap plain text content with HTML email template
     */
    private function wrap_email_template($content, $recipient_type) {
        $title = ($recipient_type === 'customer') ? __('Konfirmasi Reservasi', 'archeus-booking') : __('Reservasi Baru', 'archeus-booking');

        $html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $title . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 30px;">
                            <h1 style="color: #333; margin: 0 0 20px 0; font-size: 24px;">' . $title . '</h1>
                            <div style="color: #666; line-height: 1.6; font-size: 16px;">
                                ' . wpautop($content) . '
                            </div>
                            <table style="margin: 30px 0; width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 15px; background-color: #f8f9fa; border-radius: 4px;">
                                        <p style="margin: 0; color: #666; font-size: 14px;">
                                            <strong>' . get_bloginfo('name') . '</strong><br>
                                            ' . get_bloginfo('description') . '<br>
                                            <a href="' . get_bloginfo('url') . '" style="color: #54b335;">' . get_bloginfo('url') . '</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        return $html;
    }

    /**
     * Resolve customer email from booking data.
     * With the new auto-detection system, email fields should always use customer_email key.
     */
    private function resolve_customer_email($booking_data) {
        return isset($booking_data['customer_email']) ? $booking_data['customer_email'] : '';
    }
    
    /**
     * Handle available time slots request via AJAX
     */
    public function handle_get_available_time_slots() {
        // Verify nonce using calendar nonce to match calendar_ajax localization
        if (!wp_verify_nonce($_POST['nonce'], 'calendar_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'archeus-booking')));
        }
        
        $date = sanitize_text_field($_POST['date']);
        // Service selection is independent from time slots. Ignore service filter for time slots listing.
        $service_name = '';
        
        // Validate inputs
        if (empty($date)) {
            wp_send_json_error(array('message' => __('Invalid parameters: date is required.', 'archeus-booking')));
        }
        
        $booking_db = new Booking_Database();
        $booking_calendar = new Booking_Calendar();
        
        // Check if the date is generally available (not a holiday or manually disabled)
        $date_availability = $booking_calendar->get_availability_with_bookings($date);
        if (!$date_availability || in_array($date_availability['availability']->availability_status, ['unavailable', 'holiday'])) {
            wp_send_json_success(array()); // Return empty array for unavailable dates
        }
        
        // Always use service-agnostic schedules
        $available_schedules = $booking_db->get_available_schedules($date, null);

        // If no specific service is selected, deduplicate slots by time window
        // to avoid showing the same HH:MM-HH:MM range multiple times (one per service)
        if (is_array($available_schedules)) {
            $unique = array();
            $deduped = array();
            foreach ($available_schedules as $slot) {
                $key = $slot->start_time . '|' . $slot->end_time;
                if (!isset($unique[$key])) {
                    $unique[$key] = true;
                    $deduped[] = $slot;
                }
            }
            $available_schedules = $deduped;
        }
        
        // Service name is not relevant for slot listing
        $service_display_name = '';
        
        // Format the data for JavaScript
        $formatted_slots = array();
        foreach ($available_schedules as $slot) {
            $formatted_slots[] = array(
                'date' => $slot->date,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'max_capacity' => $slot->max_capacity,
                'current_bookings' => $slot->current_bookings,
                'service_name' => $service_display_name
            );
        }
        
        wp_send_json_success($formatted_slots);
    }
    
    /**
     * Handle booking flow submission via AJAX
     */
    public function handle_submit_booking_flow() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'booking_flow_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        $flow_id = intval($_POST['flow_id']);
        $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();
        if (is_string($form_data)) {
            $decoded = json_decode(stripslashes($form_data), true);
            if (is_array($decoded)) { $form_data = $decoded; }
        }
        
        if (!$flow_id) {
            wp_send_json_error(array(
                'message' => __('Invalid booking flow', 'archeus-booking')
            ));
        }

        $booking_db = new Booking_Database();
        $flow = $booking_db->get_booking_flow($flow_id);
        
        if (!$flow) {
            wp_send_json_error(array(
                'message' => __('Booking flow not found', 'archeus-booking')
            ));
        }

        // Combine all form data from different steps
        $combined_data = array();
        $date = '';
        $time_slot = '';
        $service_type = '';
        
        foreach ($form_data as $step_key => $step_data) {
            if (is_array($step_data)) {
                foreach ($step_data as $field => $value) {
                    // Extract date, time slot and service from various possible field names
                    if (strpos($field, 'booking_date') !== false || $field === 'booking_date') {
                        $date = sanitize_text_field($value);
                        $combined_data['booking_date'] = $date;
                    } elseif ($field === 'time_slot') {
                        $time_slot = sanitize_text_field($value);
                        $combined_data['time_slot'] = $time_slot;
                    } elseif (strpos($field, 'service') !== false || $field === 'service_type') {
                        $service_type = sanitize_text_field($value);
                        $combined_data['service_type'] = $service_type;
                    } else {
                        // Handle other fields normally
                        $combined_data[$field] = $value;
                    }
                }
            }
        }

        // Determine which service was selected (if any)
        $service_type = isset($combined_data['service_type']) ? $combined_data['service_type'] : '';
        $booking_date = isset($combined_data['booking_date']) ? $combined_data['booking_date'] : '';
        $time_slot = isset($combined_data['time_slot']) ? $combined_data['time_slot'] : '';

        // Auto-detect customer_name and customer_email from form data
        // This ensures that fields detected as name/email are stored in standardized keys

        $detected_customer_name = '';
        $detected_customer_email = '';

        // Look for fields that should be mapped to customer_name
        $name_fields = array('nama_lengkap', 'nama', 'name', 'full_name', 'customer_name', 'nama lengkap', 'full name');
        foreach ($name_fields as $name_field) {
            if (isset($combined_data[$name_field]) && !empty($combined_data[$name_field])) {
                $detected_customer_name = sanitize_text_field($combined_data[$name_field]);
                break;
            }
        }

        // Look for fields that should be mapped to customer_email
        $email_fields = array('email', 'customer_email', 'email_address', 'alamat_email', 'e-mail');
        foreach ($email_fields as $email_field) {
            if (isset($combined_data[$email_field]) && !empty($combined_data[$email_field])) {
                $detected_customer_email = sanitize_email($combined_data[$email_field]);
                break;
            }
        }

        // Prepare booking data with auto-detected customer information
        $booking_data = array(
            'customer_name' => $detected_customer_name,
            'customer_email' => $detected_customer_email,
            'booking_date' => $booking_date,
            'booking_time' => isset($combined_data['booking_time']) ? sanitize_text_field($combined_data['booking_time']) : '',
            'service_type' => $service_type,
        );

        // Also update combined_data to ensure consistency
        if (!empty($detected_customer_name)) {
            $combined_data['customer_name'] = $detected_customer_name;
        }
        if (!empty($detected_customer_email)) {
            $combined_data['customer_email'] = $detected_customer_email;
        }

        // Keep time_slot for validation but will be removed before database storage
        $time_slot_for_validation = isset($combined_data['time_slot']) ? $combined_data['time_slot'] : null;

        // Merge all non-core fields directly into combined_data (including files)
        foreach ($combined_data as $key => $value) {
            if (!in_array($key, ['customer_name', 'customer_email', 'booking_date', 'booking_time', 'service_type'])) {
                if (isset($_FILES[$key])) {
                    $file = $this->handle_file_upload($_FILES[$key]);
                    if ($file) { $combined_data[$key] = $file; }
                    else { $combined_data[$key] = sanitize_text_field($value); }
                } else {
                    $combined_data[$key] = sanitize_text_field($value);
                }
            }
        }
        // Ensure file-only fields (not present in combined_data) are captured as well by scanning $_FILES
        foreach (array_keys($_FILES ?: array()) as $k) {
            if (!isset($combined_data[$k])) {
                $file = $this->handle_file_upload($_FILES[$k]);
                if ($file) { $combined_data[$k] = $file; }
            }
        }

        // Handle time slot if provided
        if (!empty($time_slot_for_validation)) {
            $time_slot = $time_slot_for_validation; // Use the validation variable
            // Parse the time slot format "YYYY-MM-DD HH:MM-HH:MM"
            $time_slot_parts = explode(' ', sanitize_text_field($time_slot));
            $date = $time_slot_parts[0];
            $time_range = explode('-', $time_slot_parts[1]);
            $start_time = $time_range[0];
            $end_time = $time_range[1];

            // Use global schedules independent of selected service
            $service_id = 0;

            if ($service_id === 0) {
                // Ensure the time slot exists in database by generating it if needed
                if (class_exists('Time_Slots_Manager')) {
                    $time_slots_manager = new Time_Slots_Manager();
                    $time_slots_manager->generate_schedules_for_date($date);
                }

                // Check calendar daily limit to ensure we don't exceed it
                $booking_calendar = new Booking_Calendar();
                $date_availability = $booking_calendar->get_availability_with_bookings($date);

                // If the date doesn't have specific availability settings, use defaults
                $daily_limit = isset($date_availability['availability']->daily_limit) ? $date_availability['availability']->daily_limit : 5;
                $booked_count = isset($date_availability['booked_count']) ? $date_availability['booked_count'] : 0;

                // Check if we've reached the daily limit before processing
                if ($booked_count >= $daily_limit) {
                    wp_send_json_error(array(
                        'message' => __('The daily booking limit has been reached for this date. Please select a different date.', 'archeus-booking')
                    ));
                }

                // Check if the specific schedule exists
                global $wpdb;
                $schedules_table = $wpdb->prefix . 'archeus_booking_schedules';

                $schedule = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$schedules_table} WHERE date = %s AND start_time = %s AND end_time = %s AND is_available = 1",
                    $date, $start_time, $end_time
                ));

                // If schedule doesn't exist, create it automatically to ensure availability
                if (!$schedule) {
                    $wpdb->insert(
                        $schedules_table,
                        array(
                            'service_id' => 0,
                            'date' => $date,
                            'start_time' => $start_time,
                            'end_time' => $end_time,
                            'max_capacity' => 1,
                            'current_bookings' => 0,
                            'is_available' => 1
                        ),
                        array('%d', '%s', '%s', '%s', '%d', '%d', '%d')
                    );

                    // Get the newly created schedule
                    $schedule = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$schedules_table} WHERE date = %s AND start_time = %s AND end_time = %s AND is_available = 1",
                        $date, $start_time, $end_time
                    ));
                }

                // Check if there are any existing bookings with blocking statuses for this time slot
                $blocking_statuses = get_option('booking_blocking_statuses', array('approved', 'completed'));
                $existing_blocking_bookings = 0;

                if (class_exists('Booking_Database')) {
                    $db = new Booking_Database();
                    $flows = $db->get_booking_flows();

                    foreach ($flows as $flow) {
                        $table = $db->get_flow_table_name($flow->name);
                        // Check if table exists
                        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
                        if ($table_exists !== $table) continue;

                        // Check for bookings with blocking statuses for this specific time slot
                        $placeholders = implode(',', array_fill(0, count($blocking_statuses), '%s'));
                        $sql = "SELECT COUNT(*) FROM {$table} WHERE booking_date = %s AND booking_time = %s AND status IN ($placeholders)";
                        $params = array_merge(array($date, $start_time . '-' . $end_time), $blocking_statuses);

                        $count = $wpdb->get_var($wpdb->prepare($sql, $params));
                        $existing_blocking_bookings += intval($count);
                    }
                }

                // Check schedule capacity AND no blocking bookings
                $schedule_available = ($schedule && ($schedule->current_bookings < $schedule->max_capacity));
                $no_blocking_bookings = ($existing_blocking_bookings == 0);

                if ($schedule_available && $no_blocking_bookings) {
                    // Add schedule information to booking data
                    $booking_data['schedule_id'] = $schedule->id;
                    $booking_data['booking_date'] = $date; // Ensure booking_date is set
                    $booking_data['booking_time'] = $start_time . '-' . $end_time; // Store time range in booking_time

                    // Save to per-flow table only (no global bookings table)
                    // Ensure columns for file fields use VARCHAR(255)
                    $file_columns = array();
                    foreach (array_keys($_FILES ?: array()) as $fk) { $file_columns[$fk] = 'VARCHAR(255)'; }
                    if (method_exists($booking_db, 'ensure_columns_for_flow')) {
                        // Build full spec: default LONGTEXT for all non-core keys
                        $all_spec = array();
                        foreach ($combined_data as $kk => $_vv) {
                            if (!in_array($kk, array('customer_name','customer_email','booking_date','booking_time','service_type'))) {
                                $all_spec[$kk] = 'LONGTEXT';
                            }
                        }
                        // Override file keys as VARCHAR(255)
                        foreach ($file_columns as $fk => $type) { $all_spec[$fk] = $type; }
                        if (!empty($all_spec)) { $booking_db->ensure_columns_for_flow($flow->name, $all_spec); }
                    }

                    // Build payload with all inputs as flat columns (no additional_fields)
                    $flow_payload = $booking_data;

                    // Remove time_slot from combined_data before storing to avoid creating a separate field
                    $combined_data_without_time_slot = $combined_data;
                    if (isset($combined_data_without_time_slot['time_slot'])) {
                        unset($combined_data_without_time_slot['time_slot']);
                    }

                    foreach ($combined_data_without_time_slot as $k => $v) {
                        if (is_array($v)) { $v = wp_json_encode($v); }
                        $flow_payload[$k] = $v;
                    }

                    // Add time_slot to payload for reference but not as a separate database field
                    $flow_payload['time_slot'] = $time_slot_for_validation;
                    $flow_payload['flow_id'] = $flow_id;
                    $flow_payload['flow_name'] = $flow->name;
                    $insert_id = $booking_db->insert_flow_submission($flow->name, $flow_payload);

                    if ($insert_id !== false) {
                        // Do not increment capacity on pending; will increment when approved by admin
                        // Send email notifications
                        $this->send_confirmation_email($booking_data);
                        $this->send_admin_notification($booking_data);

                        // Send success response
                        wp_send_json_success(array(
                            'message' => __('Reservasi Anda berhasil dikirim. Silakan cek email; kami akan mengabarkan hasil reservasi melalui email.', 'archeus-booking')
                        ));
                    } else {
                        wp_send_json_error(array(
                            'message' => __('There was an error processing your booking', 'archeus-booking')
                        ));
                    }
                } else {
                    // Provide more specific error message
                    if (!$schedule_available) {
                        $error_message = __('Selected time slot is no longer available. Please select another time slot.', 'archeus-booking');
                    } else {
                        $error_message = __('Time slot has been reserved. Please select another time slot.', 'archeus-booking');
                    }

                    wp_send_json_error(array(
                        'message' => $error_message
                    ));
                }
            } else {
                wp_send_json_error(array(
                    'message' => __('Selected time slot is not available.', 'archeus-booking')
                ));
            }
        } else {
            // Regular booking without time slot
            if (!empty($booking_date)) {
                $booking_calendar = new Booking_Calendar();
                $date_availability = $booking_calendar->get_availability_with_bookings($booking_date);

                // If the date doesn't have specific availability settings, use defaults
                $daily_limit = isset($date_availability['availability']->daily_limit) ? $date_availability['availability']->daily_limit : 5;
                $booked_count = isset($date_availability['booked_count']) ? $date_availability['booked_count'] : 0;

                // Check if we've reached the daily limit before processing
                if ($booked_count >= $daily_limit) {
                    wp_send_json_error(array(
                        'message' => __('The daily booking limit has been reached for this date. Please select a different date.', 'archeus-booking')
                    ));
                }
            }

            // Save to per-flow table only (without time slot)
            // Ensure columns for file fields use VARCHAR(255)
            $file_columns = array();
            foreach (array_keys($_FILES ?: array()) as $fk) { $file_columns[$fk] = 'VARCHAR(255)'; }
            if (method_exists($booking_db, 'ensure_columns_for_flow')) {
                $all_spec = array();
                foreach ($combined_data as $kk => $_vv) {
                    if (!in_array($kk, array('customer_name','customer_email','booking_date','booking_time','service_type'))) {
                        $all_spec[$kk] = 'LONGTEXT';
                    }
                }
                foreach ($file_columns as $fk => $type) { $all_spec[$fk] = $type; }
                if (!empty($all_spec)) { $booking_db->ensure_columns_for_flow($flow->name, $all_spec); }
            }

            // Build payload with all inputs as flat columns (no additional_fields)
            $flow_payload = $booking_data;
            foreach ($combined_data as $k => $v) {
                if (is_array($v)) { $v = wp_json_encode($v); }
                $flow_payload[$k] = $v;
            }
            $flow_payload['time_slot'] = $time_slot;
            $flow_payload['flow_id'] = $flow_id;
            $flow_payload['flow_name'] = $flow->name;
            $insert_id = $booking_db->insert_flow_submission($flow->name, $flow_payload);

            if ($insert_id !== false) {
                // Send email notifications
                $this->send_confirmation_email($booking_data);
                $this->send_admin_notification($booking_data);

                // Send success response
            wp_send_json_success(array(
                'message' => __('Reservasi Anda berhasil dikirim. Silakan cek email; kami akan mengabarkan hasil reservasi melalui email.', 'archeus-booking')
            ));
            } else {
                wp_send_json_error(array(
                    'message' => __('There was an error processing your booking', 'archeus-booking')
                ));
            }
        }
    }
    
    /**
     * Handle file upload
     */
    private function handle_file_upload($file) {
        if (!isset($file['name']) || $file['name'] == '') {
            return false;
        }
        
        // Check if file upload was successful
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Validate file type (you can extend this as needed)
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf');
        $file_type = $file['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            return false;
        }
        
        // Validate file size (5MB limit)
        if ($file['size'] > 5 * 1024 * 1024) {
            return false;
        }
        
        // Generate unique filename
        $upload_dir = wp_upload_dir();
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '_' . sanitize_file_name($file['name']);
        $file_path = $upload_dir['path'] . '/' . $file_name;
        
        // Move uploaded file to WordPress uploads directory
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Return the file URL for storage in database
            return $upload_dir['url'] . '/' . $file_name;
        }
        
        return false;
    }

    /**
     * Enqueue scripts for Elementor editor and frontend
     */
    public function enqueue_elementor_scripts() {
        // Enqueue the same scripts as in enqueue_public_scripts
        wp_enqueue_script('booking-calendar-js', ARCHEUS_BOOKING_URL . 'assets/js/calendar.js', array('jquery'), ARCHEUS_BOOKING_VERSION . '.1', true);
        wp_enqueue_script('booking-flow-js', ARCHEUS_BOOKING_URL . 'assets/js/booking-flow.js', array('jquery'), ARCHEUS_BOOKING_VERSION, true);

        // Enqueue public styles
        wp_enqueue_style('booking-calendar-css', ARCHEUS_BOOKING_URL . 'assets/css/calendar.css', array(), ARCHEUS_BOOKING_VERSION);
        wp_enqueue_style('booking-flow-css', ARCHEUS_BOOKING_URL . 'assets/css/booking-flow.css', array(), ARCHEUS_BOOKING_VERSION);
        wp_enqueue_style('services-css', ARCHEUS_BOOKING_URL . 'assets/css/services.css', array(), ARCHEUS_BOOKING_VERSION);
        wp_enqueue_style('time-slots-css', ARCHEUS_BOOKING_URL . 'assets/css/time-slots.css', array(), ARCHEUS_BOOKING_VERSION);

        // Build AJAX URL with correct scheme to avoid mixed-content issues
        $ajax_url = admin_url('admin-ajax.php');
        if (function_exists('is_ssl') && is_ssl()) {
            $ajax_url = admin_url('admin-ajax.php', 'https');
        }
        if (function_exists('wp_make_link_relative')) {
            $rel = wp_make_link_relative($ajax_url);
            if (!empty($rel) && strpos($rel, '/wp-admin/admin-ajax.php') === 0) {
                $ajax_url = $rel;
            }
        }

        // Localize scripts with AJAX URL and nonces
        wp_localize_script('booking-calendar-js', 'calendar_ajax', array(
            'ajax_url' => $ajax_url,
            'nonce' => wp_create_nonce('calendar_nonce'),
            'max_months' => get_option('booking_calendar_max_months', 6)
        ));

        wp_localize_script('booking-flow-js', 'booking_flow_ajax', array(
            'ajax_url' => $ajax_url,
            'nonce' => wp_create_nonce('booking_flow_nonce')
        ));
    }

    /**
     * Build email subject with tag replacement
     */
    private function build_email_subject($booking_data, $subject_template) {
        // Prepare booking data for tag replacement
        $data = array(
            'booking_id' => isset($booking_data['booking_id']) ? $booking_data['booking_id'] : '',
            'customer_name' => isset($booking_data['customer_name']) ? $booking_data['customer_name'] : '',
            'customer_email' => isset($booking_data['customer_email']) ? $booking_data['customer_email'] : '',
            'booking_date' => isset($booking_data['booking_date']) ? $booking_data['booking_date'] : '',
            'booking_time' => isset($booking_data['booking_time']) ? $booking_data['booking_time'] : '',
            'service_type' => isset($booking_data['service_type']) ? $booking_data['service_type'] : '',
            'time_slot' => isset($booking_data['booking_time']) ? $booking_data['booking_time'] : '',
            'company_name' => get_bloginfo('name'),
            'company_url' => get_bloginfo('url'),
            'admin_email' => get_option('admin_email'),
            'current_date' => date('Y-m-d'),
            'current_time' => date('H:i:s')
        );

        // Replace all available tags in the subject
        $subject = $subject_template;

        // Replace standard tags
        foreach ($data as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value, $subject);
        }

        // Clean up any remaining tags (replace with empty string)
        $subject = preg_replace('/\{[^}]+\}/', '', $subject);

        return trim($subject);
    }
}


