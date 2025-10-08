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
    }

    // Note: auto-translation of labels to field keys removed by request.

    /**
     * Enqueue public scripts
     */
    public function enqueue_public_scripts() {
        global $post;
        // Ensure $post is an object before accessing its properties
        if (!is_a($post, 'WP_Post')) {
            return;
        }

        // Enqueue scripts only when booking flow or calendar shortcode is present
        if (has_shortcode($post->post_content, 'archeus_booking') || has_shortcode($post->post_content, 'archeus_booking_calendar')) {
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
            $statuses = get_option('booking_blocking_statuses', array('approved'));
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
        // Get email settings
        $email_settings = get_option('booking_email_settings', array(
            'enable_customer_confirmation' => 1,
            'customer_confirmation_subject' => __('Booking Confirmation', 'archeus-booking'),
            'customer_confirmation_body' => __('Thank you for your booking. We will contact you shortly to confirm.', 'archeus-booking')
        ));
        
        // Check if customer confirmation is enabled
        if (!$email_settings['enable_customer_confirmation']) {
            return;
        }
        
        $to = $this->resolve_customer_email($booking_data);
        $subject = $email_settings['customer_confirmation_subject'];
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
            'admin_notification_subject' => __('Reservasi Baru Diterima', 'archeus-booking'),
            'admin_notification_body' => __('Reservasi baru telah diterima, tolong segera dicek.', 'archeus-booking')
        ));
        
        // Check if admin notification is enabled
        if (!$email_settings['enable_admin_notification']) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $subject = $email_settings['admin_notification_subject'];
        $message = $this->build_custom_email_content($booking_data, $email_settings['admin_notification_body'], 'admin');
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Build custom email content
     */
    private function build_custom_email_content($booking_data, $template, $recipient_type) {
        // Replace tags in the template
        $message = $template;
        
        // Replace customer-specific tags (resolve display name from known custom keys)
        $display_name = isset($booking_data['customer_name']) ? $booking_data['customer_name'] : '';
        if ($display_name === '') {
            foreach (array('nama_lengkap','nama','full_name','name') as $ck) {
                if (!empty($booking_data[$ck])) { $display_name = $booking_data[$ck]; break; }
            }
        }
        $message = str_replace('{customer_name}', $display_name, $message);
        $message = str_replace('{customer_email}', $this->resolve_customer_email($booking_data), $message);
        $message = str_replace('{booking_date}', $booking_data['booking_date'], $message);
        $message = str_replace('{booking_time}', $booking_data['booking_time'], $message);
        $message = str_replace('{service_type}', $booking_data['service_type'], $message);
        // Special requests may exist as a custom field
        $special = '';
        if (isset($booking_data['special_requests'])) { $special = $booking_data['special_requests']; }
        $message = str_replace('{special_requests}', $special, $message);
        
        // For HTML email, we'll use the more detailed table format
        if ($recipient_type === 'customer') {
            $title = __('Konfirmasi Reservasi Anda', 'archeus-booking');
            $greeting = sprintf(__('Dear %s,', 'archeus-booking'), $display_name);
        } else {
            $title = __('Reservasi Baru Diterima', 'archeus-booking');
            $greeting = __('Halo,', 'archeus-booking');
        }
        
        $html = '<html><body>';
        $html .= '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; padding: 20px;">';
        $html .= '<h2 style="color: #333;">' . $title . '</h2>';
        $html .= '<p>' . $greeting . '</p>';
        $html .= '<p>' . $message . '</p>';
        
        $html .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">' . __('Field', 'archeus-booking') . '</td>';
        $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">' . __('Value', 'archeus-booking') . '</td></tr>';
        
        $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;">' . __('Name', 'archeus-booking') . '</td>';
        $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($display_name) . '</td></tr>';
        
        $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;">' . __('Email', 'archeus-booking') . '</td>';
        $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($this->resolve_customer_email($booking_data)) . '</td></tr>';
        
        $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;">' . __('Date', 'archeus-booking') . '</td>';
        $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($booking_data['booking_date']) . '</td></tr>';
        
        if (!empty($booking_data['booking_time'])) {
            $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;">' . __('Time', 'archeus-booking') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($booking_data['booking_time']) . '</td></tr>';
        }
        
        if (!empty($booking_data['service_type'])) {
            $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;">' . __('Service Type', 'archeus-booking') . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($booking_data['service_type']) . '</td></tr>';
        }
        
        // Render special requests if present in additional fields
        if (!empty($booking_data['additional_fields'])) {
            $extra = is_array($booking_data['additional_fields']) ? $booking_data['additional_fields'] : @maybe_unserialize($booking_data['additional_fields']);
            if (is_array($extra) && !empty($extra['special_requests'])) {
                $html .= '<tr><td style="padding: 8px; border: 1px solid #ddd;">' . __('Special Requests', 'archeus-booking') . '</td>';
                $html .= '<td style="padding: 8px; border: 1px solid #ddd;">' . esc_html($extra['special_requests']) . '</td></tr>';
            }
        }
        
        $html .= '</table>';
        
        $html .= '<p>' . __('Salam hormat,', 'archeus-booking') . '</p>';
        $html .= '<p><strong>' . get_bloginfo('name') . '</strong></p>';
        $html .= '</div>';
        $html .= '</body></html>';
        
        return $html;
    }

    /**
     * Resolve customer email from additional fields without relying on reserved key.
     */
    private function resolve_customer_email($booking_data) {
        $email = isset($booking_data['customer_email']) ? $booking_data['customer_email'] : '';
        if (!empty($email) && is_string($email)) {
            return $email;
        }
        if (!empty($booking_data['additional_fields'])) {
            $extra = is_array($booking_data['additional_fields']) ? $booking_data['additional_fields'] : @maybe_unserialize($booking_data['additional_fields']);
            if (is_array($extra)) {
                $candidates = array('alamat_email','email','e_mail','e-mail','surat_elektronik','mail');
                foreach ($candidates as $ck) {
                    if (!empty($extra[$ck]) && is_string($extra[$ck])) {
                        $val = trim($extra[$ck]);
                        if (filter_var($val, FILTER_VALIDATE_EMAIL)) { return $val; }
                    }
                }
            }
        }
        return '';
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

        // No dependency on form definitions; rely on submitted keys and files

        // Do not infer customer_email into reserved key; respect custom email fields like 'alamat_email' or 'email'

        // Intentionally do not infer 'customer_name'; respect custom fields like 'nama_lengkap'

        // Do not remap keys based on labels; keep submitted keys as-is

        // Prepare booking data, collecting all possible fields from all steps
        $booking_data = array(
            'customer_name' => isset($combined_data['customer_name']) ? sanitize_text_field($combined_data['customer_name']) : '',
            'customer_email' => isset($combined_data['customer_email']) ? sanitize_email($combined_data['customer_email']) : '',
            'booking_date' => $booking_date,
            'booking_time' => isset($combined_data['booking_time']) ? sanitize_text_field($combined_data['booking_time']) : '',
            'service_type' => $service_type,
        );

        // Merge all non-core fields directly into combined_data (including files)
        foreach ($combined_data as $key => $value) {
            if (!in_array($key, ['customer_name', 'customer_email', 'booking_date', 'booking_time', 'service_type', 'time_slot'])) {
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
        if (!empty($time_slot)) {
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

                if ($schedule && ($schedule->current_bookings < $schedule->max_capacity)) {
                    // Add schedule information to booking data
                    $booking_data['schedule_id'] = $schedule->id;
                    $booking_data['booking_time'] = $start_time; // Use start time as booking time

                    // Save to per-flow table only (no global bookings table)
                    // Ensure columns for file fields use VARCHAR(255)
                    $file_columns = array();
                    foreach (array_keys($_FILES ?: array()) as $fk) { $file_columns[$fk] = 'VARCHAR(255)'; }
                    if (method_exists($booking_db, 'ensure_columns_for_flow')) {
                        // Build full spec: default LONGTEXT for all non-core keys
                        $all_spec = array();
                        foreach ($combined_data as $kk => $_vv) {
                            if (!in_array($kk, array('customer_name','customer_email','booking_date','booking_time','service_type','time_slot'))) {
                                $all_spec[$kk] = 'LONGTEXT';
                            }
                        }
                        // Override file keys as VARCHAR(255)
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
                    wp_send_json_error(array(
                        'message' => __('Selected time slot is no longer available. Please select another time slot.', 'archeus-booking')
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
                    if (!in_array($kk, array('customer_name','customer_email','booking_date','booking_time','service_type','time_slot'))) {
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
}


