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
     * Get default email templates - CRITICAL for fallback
     */
    private function get_default_email_templates() {
        return array(
            'customer_confirmation_body' => '<div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; padding: 30px; color: #333;"><div style="text-align: center; margin-bottom: 25px;"><h2 style="color: #54b335; margin: 0; font-size: 24px;">Reservasi Berhasil Diterima</h2><p style="color: #555; font-size: 15px; margin-top: 5px;">{greeting}</p></div><p style="font-size: 15px; line-height: 1.6;">Terima kasih telah melakukan reservasi dengan <strong style="color: #54b335;">ID #{booking_id}</strong> untuk layanan <strong>{service_type}</strong>. Reservasi Anda telah berhasil diterima dan saat ini sedang <strong>menunggu konfirmasi</strong> dari tim kami.</p><div style="background-color: #f8fafc; border-left: 4px solid #54b335; padding: 15px 20px; border-radius: 6px; margin: 25px 0;"><h3 style="margin-top: 0; color: #54b335; font-size: 18px;">Detail Reservasi Anda</h3><p style="margin: 8px 0;"><strong>ID Reservasi:</strong> {booking_id}</p><p style="margin: 8px 0;"><strong>Layanan:</strong> {service_type}</p><p style="margin: 8px 0;"><strong>Tanggal:</strong> {booking_date}</p><p style="margin: 8px 0;"><strong>Waktu:</strong> {booking_time}</p><p style="margin: 8px 0;"><strong>Nama:</strong> {customer_name}</p><p style="margin: 8px 0;"><strong>Email:</strong> {customer_email}</p><p style="margin: 8px 0;"><strong>Status:</strong> <span style="color: #f59e0b;">Menunggu Konfirmasi</span></p></div><p style="font-size: 15px; line-height: 1.6;">Kami akan segera menghubungi Anda melalui email atau telepon untuk memberikan <strong>konfirmasi lebih lanjut</strong>. Mohon untuk tetap memantau email Anda untuk pembaruan status reservasi.</p><p style="font-size: 14px; line-height: 1.6; color: #666; margin-top: 20px;">Jika Anda memiliki pertanyaan, silakan hubungi kami di <a href="mailto:{admin_email}" style="color: #54b335; text-decoration: none;">{admin_email}</a></p><div style="margin-top: 35px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center;"><p style="margin: 0; color: #444; font-weight: 600;">Hormat kami,</p><p style="margin: 5px 0 15px 0; color: #54b335; font-weight: bold;">{company_name}</p><p style="font-size: 12px; color: #999;">Email ini dikirim pada {current_date} pukul {current_time}.<br>Mohon untuk tidak membalas email ini secara langsung.</p></div></div>',
            'admin_notification_body' => '<div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; padding: 30px; color: #333;"><div style="text-align: center; margin-bottom: 25px;"><h2 style="color: #54b335; margin: 0; font-size: 24px;">Reservasi Baru Diterima</h2><p style="color: #555; font-size: 15px; margin-top: 5px;">Halo Admin,</p></div><p style="font-size: 15px; line-height: 1.6;">Telah masuk <strong>reservasi baru</strong> dari pengguna. Mohon segera ditinjau dan dikonfirmasi agar pengguna mendapatkan pembaruan status secepatnya.</p><div style="background-color: #f8fafc; border-left: 4px solid #54b335; padding: 15px 20px; border-radius: 6px; margin: 25px 0;"><h3 style="margin-top: 0; color: #54b335; font-size: 18px;">Detail Reservasi</h3><p style="margin: 8px 0;"><strong>ID Reservasi:</strong> {booking_id}</p><p style="margin: 8px 0;"><strong>Nama:</strong> {customer_name}</p><p style="margin: 8px 0;"><strong>Email:</strong> <a href="mailto:{customer_email}" style="color: #54b335; text-decoration: none;">{customer_email}</a></p><p style="margin: 8px 0;"><strong>Layanan:</strong> {service_type}</p><p style="margin: 8px 0;"><strong>Tanggal:</strong> {booking_date}</p><p style="margin: 8px 0;"><strong>Waktu:</strong> {booking_time}</p></div><div style="text-align: center; margin: 30px 0;"><a href="{admin_website}" style="display: inline-block; background-color: #54b335; color: #fff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; font-size: 15px;">Konfirmasi Reservasi Sekarang</a></div><div style="margin-top: 35px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center;"><p style="margin: 0; color: #444; font-weight: 600;">Sistem Reservasi</p><p style="margin: 5px 0 15px 0; color: #54b335; font-weight: bold;">{company_name}</p><p style="font-size: 12px; color: #999;">Email ini dikirim pada {current_date} pukul {current_time}.<br>Mohon tindak lanjuti reservasi baru secepatnya.</p></div></div>',
            'pending_email_body' => '<div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; padding: 30px; color: #333;"><div style="text-align: center; margin-bottom: 25px;"><h2 style="color: #f59e0b; margin: 0; font-size: 24px;">Reservasi Sedang Diproses</h2><p style="color: #555; font-size: 15px; margin-top: 5px;">{greeting}</p></div><p style="font-size: 15px; line-height: 1.6;">Terima kasih telah melakukan reservasi dengan kami. Reservasi Anda dengan <strong style="color: #f59e0b;">ID #{booking_id}</strong> sedang dalam <strong>proses peninjauan</strong>.</p><div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px 20px; border-radius: 6px; margin: 25px 0;"><h3 style="margin-top: 0; color: #f59e0b; font-size: 18px;">Detail Reservasi</h3><p style="margin: 8px 0;"><strong>ID Reservasi:</strong> {booking_id}</p><p style="margin: 8px 0;"><strong>Layanan:</strong> {service_type}</p><p style="margin: 8px 0;"><strong>Tanggal:</strong> {booking_date}</p><p style="margin: 8px 0;"><strong>Waktu:</strong> {booking_time}</p><p style="margin: 8px 0;"><strong>Status:</strong> <span style="color: #f59e0b; font-weight: 600;">Menunggu Konfirmasi</span></p></div><p style="font-size: 15px; line-height: 1.6;">Kami akan segera menghubungi Anda untuk mengkonfirmasi reservasi ini. Mohon pastikan email dan nomor telepon Anda aktif.</p><div style="margin-top: 35px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center;"><p style="margin: 0; color: #444; font-weight: 600;">Hormat kami,</p><p style="margin: 5px 0 15px 0; color: #54b335; font-weight: bold;">{company_name}</p><p style="font-size: 12px; color: #999;">Email ini dikirim pada {current_date} pukul {current_time}.</p></div></div>',
            'approved_email_body' => '<div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; padding: 30px; color: #333;"><div style="text-align: center; margin-bottom: 25px;"><h2 style="color: #10b981; margin: 0; font-size: 24px;">🎉 Reservasi Disetujui!</h2><p style="color: #555; font-size: 15px; margin-top: 5px;">{greeting}</p></div><p style="font-size: 15px; line-height: 1.6;">Selamat! Reservasi Anda dengan <strong style="color: #10b981;">ID #{booking_id}</strong> telah <strong>DISETUJUI</strong>. Kami sangat menantikan kedatangan Anda!</p><div style="background-color: #ecfdf5; border-left: 4px solid #10b981; padding: 15px 20px; border-radius: 6px; margin: 25px 0;"><h3 style="margin-top: 0; color: #10b981; font-size: 18px;">Detail Reservasi yang Disetujui</h3><p style="margin: 8px 0;"><strong>ID Reservasi:</strong> {booking_id}</p><p style="margin: 8px 0;"><strong>Layanan:</strong> {service_type}</p><p style="margin: 8px 0;"><strong>Tanggal:</strong> {booking_date}</p><p style="margin: 8px 0;"><strong>Waktu:</strong> {booking_time}</p><p style="margin: 8px 0;"><strong>Status:</strong> <span style="color: #10b981; font-weight: 600;">✓ Disetujui</span></p></div><div style="background-color: #f8fafc; padding: 15px; border-radius: 6px; margin: 20px 0;"><h4 style="margin-top: 0; color: #333; font-size: 16px;">Yang Perlu Anda Lakukan:</h4><ul style="margin: 0; padding-left: 20px; color: #555;"><li style="margin: 8px 0;">Harap datang <strong>15 menit sebelum</strong> waktu reservasi</li><li style="margin: 8px 0;">Bawa <strong>ID Reservasi #{booking_id}</strong> untuk konfirmasi</li><li style="margin: 8px 0;">Jika ada perubahan, hubungi kami segera</li></ul></div><p style="font-size: 15px; line-height: 1.6;">Jika Anda memiliki pertanyaan atau perlu membatalkan, silakan hubungi kami di <a href="mailto:{admin_email}" style="color: #10b981; text-decoration: none;">{admin_email}</a></p><div style="margin-top: 35px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center;"><p style="margin: 0; color: #444; font-weight: 600;">Sampai jumpa!</p><p style="margin: 5px 0 15px 0; color: #54b335; font-weight: bold;">{company_name}</p><p style="font-size: 12px; color: #999;">Email ini dikirim pada {current_date} pukul {current_time}.</p></div></div>',
            'rejected_email_body' => '<div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; padding: 30px; color: #333;"><div style="text-align: center; margin-bottom: 25px;"><h2 style="color: #ef4444; margin: 0; font-size: 24px;">Pemberitahuan Reservasi</h2><p style="color: #555; font-size: 15px; margin-top: 5px;">{greeting}</p></div><p style="font-size: 15px; line-height: 1.6;">Mohon maaf, reservasi Anda dengan <strong>ID #{booking_id}</strong> untuk layanan <strong>{service_type}</strong> tidak dapat kami proses saat ini.</p><div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px 20px; border-radius: 6px; margin: 25px 0;"><h3 style="margin-top: 0; color: #ef4444; font-size: 18px;">Detail Reservasi</h3><p style="margin: 8px 0;"><strong>ID Reservasi:</strong> {booking_id}</p><p style="margin: 8px 0;"><strong>Layanan:</strong> {service_type}</p><p style="margin: 8px 0;"><strong>Tanggal:</strong> {booking_date}</p><p style="margin: 8px 0;"><strong>Waktu:</strong> {booking_time}</p><p style="margin: 8px 0;"><strong>Status:</strong> <span style="color: #ef4444; font-weight: 600;">✗ Ditolak</span></p></div><p style="font-size: 15px; line-height: 1.6;">Anda dapat melakukan <strong>reservasi ulang</strong> dengan memilih tanggal atau layanan lain yang tersedia. Atau hubungi kami di <a href="mailto:{admin_email}" style="color: #54b335; text-decoration: none;">{admin_email}</a> untuk informasi lebih lanjut.</p><div style="margin-top: 35px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center;"><p style="margin: 0; color: #444; font-weight: 600;">Terima kasih atas pengertian Anda,</p><p style="margin: 5px 0 15px 0; color: #54b335; font-weight: bold;">{company_name}</p><p style="font-size: 12px; color: #999;">Email ini dikirim pada {current_date} pukul {current_time}.</p></div></div>',
            'completed_email_body' => '<div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; padding: 30px; color: #333;"><div style="text-align: center; margin-bottom: 25px;"><h2 style="color: #8b5cf6; margin: 0; font-size: 24px;">✨ Reservasi Selesai</h2><p style="color: #555; font-size: 15px; margin-top: 5px;">{greeting}</p></div><p style="font-size: 15px; line-height: 1.6;">Terima kasih telah menggunakan layanan kami! Reservasi Anda dengan <strong style="color: #8b5cf6;">ID #{booking_id}</strong> telah <strong>selesai</strong>.</p><div style="background-color: #faf5ff; border-left: 4px solid #8b5cf6; padding: 15px 20px; border-radius: 6px; margin: 25px 0;"><h3 style="margin-top: 0; color: #8b5cf6; font-size: 18px;">Ringkasan Layanan</h3><p style="margin: 8px 0;"><strong>ID Reservasi:</strong> {booking_id}</p><p style="margin: 8px 0;"><strong>Layanan:</strong> {service_type}</p><p style="margin: 8px 0;"><strong>Tanggal:</strong> {booking_date}</p><p style="margin: 8px 0;"><strong>Waktu:</strong> {booking_time}</p><p style="margin: 8px 0;"><strong>Status:</strong> <span style="color: #8b5cf6; font-weight: 600;">✓ Selesai</span></p></div><div style="background-color: #f8fafc; padding: 20px; border-radius: 6px; margin: 20px 0; text-align: center;"><h4 style="margin-top: 0; color: #333; font-size: 16px;">Bagaimana Pengalaman Anda?</h4><p style="margin: 10px 0; color: #666;">Kami sangat menghargai feedback Anda untuk meningkatkan kualitas layanan.</p><p style="margin: 15px 0 0 0;"><a href="mailto:{admin_email}?subject=Feedback untuk Reservasi {booking_id}" style="display: inline-block; background-color: #8b5cf6; color: #fff; text-decoration: none; padding: 10px 24px; border-radius: 6px; font-weight: 600;">Kirim Feedback</a></p></div><p style="font-size: 15px; line-height: 1.6;">Kami berharap dapat melayani Anda kembali di masa mendatang. Jika ada pertanyaan, jangan ragu untuk menghubungi kami.</p><div style="margin-top: 35px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center;"><p style="margin: 0; color: #444; font-weight: 600;">Sampai jumpa lagi!</p><p style="margin: 5px 0 15px 0; color: #54b335; font-weight: bold;">{company_name}</p><p style="font-size: 12px; color: #999;">Email ini dikirim pada {current_date} pukul {current_time}.</p></div></div>'
        );
    }

    /**
     * Get email content with FALLBACK to default templates
     * CRITICAL: Always returns content, never empty - ensures emails always sent
     */
    private function get_email_content($email_settings, $content_key) {
        // Check if content exists and is not empty
        if (isset($email_settings[$content_key])) {
            $content = $email_settings[$content_key];
            // Trim and check if it's not empty (not just whitespace)
            if (trim($content) !== '') {
                return $content;
            }
        }

        // FALLBACK: Return default template to ensure email always sent
        // This prevents "no email sent" issue when admin deletes content
        $defaults = $this->get_default_email_templates();
        if (isset($defaults[$content_key])) {
            return $defaults[$content_key];
        }

        // Final fallback: return empty (should never reach here)
        return '';
    }

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
        // Get email settings - don't use hardcoded defaults
        $email_settings = get_option('booking_email_settings', array());

        // Check if customer confirmation is enabled
        if (empty($email_settings['enable_customer_confirmation'])) {
            return;
        }

        // Get email content using helper function (no defaults)
        $email_body = $this->get_email_content($email_settings, 'customer_confirmation_body');

        // Check if email body is empty - if so, don't send email
        if (empty(trim($email_body))) {
            return;
        }

        $to = isset($booking_data['customer_email']) ? $booking_data['customer_email'] : '';
        $subject = $this->build_email_subject($booking_data, isset($email_settings['customer_confirmation_subject']) ? $email_settings['customer_confirmation_subject'] : __('Konfirmasi Reservasi #{booking_id} - {service_type}', 'archeus-booking'));
        $message = $this->build_custom_email_content($booking_data, $email_body, 'customer');
        $headers = array('Content-Type: text/html; charset=UTF-8');

        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Send notification email to admin
     */
    private function send_admin_notification($booking_data) {
        // Get email settings - don't use hardcoded defaults
        $email_settings = get_option('booking_email_settings', array());

        // Check if admin notification is enabled
        if (empty($email_settings['enable_admin_notification'])) {
            return;
        }

        // Get email content using helper function (no defaults)
        $email_body = $this->get_email_content($email_settings, 'admin_notification_body');

        // Check if email body is empty - if so, don't send email
        if (empty(trim($email_body))) {
            return;
        }

        // Use custom admin email if set, otherwise use default
        $admin_email = !empty($email_settings['admin_email_address']) ? $email_settings['admin_email_address'] : get_option('admin_email');
        $subject = $this->build_email_subject($booking_data, isset($email_settings['admin_notification_subject']) ? $email_settings['admin_notification_subject'] : __('Reservasi Baru #{booking_id} - {service_type}', 'archeus-booking'));
        $message = $this->build_custom_email_content($booking_data, $email_body, 'admin');
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

        // Basic booking information - FORMAT THEM PROPERLY
        $booking_date_raw = isset($booking_data['booking_date']) ? $booking_data['booking_date'] : '';
        $booking_time_raw = isset($booking_data['booking_time']) ? $booking_data['booking_time'] : '';
        $service_type = isset($booking_data['service_type']) ? $booking_data['service_type'] : '';
        $time_slot = isset($booking_data['time_slot']) ? $booking_data['time_slot'] : '';
        
        // Format date and time for email display
        $booking_date = !empty($booking_date_raw) ? date_i18n(get_option('date_format'), strtotime($booking_date_raw)) : '';
        $booking_time = !empty($booking_time_raw) ? $this->format_time($booking_time_raw) : '';

        // Replace basic tags
        $booking_id = isset($booking_data['booking_id']) ? $booking_data['booking_id'] : '';
        $message = str_replace('{booking_id}', $booking_id, $message);
        $message = str_replace('{customer_name}', $display_name, $message);
        $message = str_replace('{customer_email}', $customer_email, $message);
        $message = str_replace('{booking_date}', $booking_date, $message);
        $message = str_replace('{booking_time}', $booking_time, $message);
        $message = str_replace('{service_type}', $service_type, $message);
        $message = str_replace('{time_slot}', $time_slot, $message);

        // Replace Indonesian language tags (aliases for English tags)
        $message = str_replace('{nama_lengkap}', $display_name, $message);
        $message = str_replace('{nama}', $display_name, $message);
        $message = str_replace('{email_pelanggan}', $customer_email, $message);
        $message = str_replace('{alamat_email}', $customer_email, $message);
        $message = str_replace('{tanggal_reservasi}', $booking_date, $message);
        $message = str_replace('{waktu_reservasi}', $booking_time, $message);
        $message = str_replace('{layanan}', $service_type, $message);
        $message = str_replace('{jenis_layanan}', $service_type, $message);
        $message = str_replace('{slot_waktu}', $time_slot, $message);

        // Company information
        $message = str_replace('{company_name}', get_bloginfo('name'), $message);
        $message = str_replace('{company_url}', trailingslashit(get_bloginfo('url')) . 'wp-login.php', $message);
        $message = str_replace('{admin_website}', trailingslashit(get_bloginfo('url')) . 'wp-login.php', $message);
        $message = str_replace('{admin_email}', get_option('admin_email'), $message);

        // Indonesian language tags (aliases)
        $message = str_replace('{nama_perusahaan}', get_bloginfo('name'), $message);
        $message = str_replace('{url_perusahaan}', trailingslashit(get_bloginfo('url')) . 'wp-login.php', $message);
        $message = str_replace('{url_admin}', trailingslashit(get_bloginfo('url')) . 'wp-login.php', $message);
        $message = str_replace('{email_admin}', get_option('admin_email'), $message);

        // Status change specific tags (for consistency, though primarily used in admin)
        if (isset($booking_data['new_status'])) {
            $message = str_replace('{new_status}', $booking_data['new_status'], $message);
        }
        if (isset($booking_data['status'])) {
            $message = str_replace('{status}', $booking_data['status'], $message);
        }

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
        // Don't wrap if template already contains HTML div structure (indicates custom HTML template)
        if (strpos($message, '<html') === false && strpos($message, '<!DOCTYPE') === false && strpos($message, '<div') === false) {
            $message = $this->wrap_email_template($message, $recipient_type);
        }

        return $message;
    }

    /**
     * Format time for display (hardcoded HH:MM format)
     * Handles both single time (HH:MM:SS) and time range (HH:MM:SS-HH:MM:SS)
     */
    private function format_time($time_value) {
        switch ($time_value) {
            case 'morning':
                return __('Morning (9:00 AM - 12:00 PM)', 'archeus-booking');
            case 'afternoon':
                return __('Afternoon (1:00 PM - 5:00 PM)', 'archeus-booking');
            case 'evening':
                return __('Evening (6:00 PM - 9:00 PM)', 'archeus-booking');
            default:
                // Check if it's a time range (HH:MM:SS-HH:MM:SS)
                if (strpos($time_value, '-') !== false) {
                    $parts = explode('-', $time_value);
                    if (count($parts) === 2) {
                        $start = trim($parts[0]);
                        $end = trim($parts[1]);
                        // Remove seconds from both parts
                        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $start)) {
                            $start = substr($start, 0, 5);
                        }
                        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $end)) {
                            $end = substr($end, 0, 5);
                        }
                        return $start . ' - ' . $end;  // 08:30 - 09:00
                    }
                }
                
                // Check if it's a single time (HH:MM:SS)
                if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time_value)) {
                    return substr($time_value, 0, 5);  // HH:MM:SS -> HH:MM
                }
                
                // If already formatted or other format, return as is
                return $time_value;
        }
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
        
        $date = sanitize_text_field(wp_unslash($_POST['date']));
        // Service selection is independent from time slots. Ignore service filter for time slots listing.
        $service_name = '';
        
        // Validate inputs
        if (empty($date)) {
            wp_send_json_error(array('message' => __('Invalid parameters: date is required.', 'archeus-booking')));
        }
        
        $booking_db = new Booking_Database();
        $booking_calendar = new Booking_Calendar();
        
        // Check if the date is generally available (not manually disabled)
        $date_availability = $booking_calendar->get_availability_with_bookings($date);
        if (!$date_availability || in_array($date_availability['availability']->availability_status, ['unavailable'])) {
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
        $form_data = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : array();
        // Debug: Log received form data
        error_log('Booking Flow Debug - Received form_data: ' . print_r($form_data, true));

        if (is_string($form_data)) {
            $decoded = json_decode(stripslashes($form_data), true);
            if (is_array($decoded)) {
                $form_data = $decoded;
                error_log('Booking Flow Debug - Decoded form_data: ' . print_r($form_data, true));
            }
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

        error_log('Booking Flow Debug - Processing form_data steps: ' . print_r($form_data, true));

        foreach ($form_data as $step_key => $step_data) {
            if (is_array($step_data)) {
                error_log('Booking Flow Debug - Processing step: ' . $step_key . ' with data: ' . print_r($step_data, true));
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
                        // Handle other fields normally with sanitization
                        $combined_data[$field] = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
                    }
                }
            }
        }

        error_log('Booking Flow Debug - Combined data before processing: ' . print_r($combined_data, true));

        // Determine which service was selected (if any)
        $service_type = isset($combined_data['service_type']) ? $combined_data['service_type'] : '';
        $booking_date = isset($combined_data['booking_date']) ? $combined_data['booking_date'] : '';
        $time_slot = isset($combined_data['time_slot']) ? $combined_data['time_slot'] : '';

        // Only use customer_name and customer_email if they exist as actual field keys
        // This removes aggressive auto-detection and only uses explicitly defined fields
        $detected_customer_name = '';
        $detected_customer_email = '';

        // Only use customer_name if it exists as a field key in the form
        if (isset($combined_data['customer_name']) && !empty($combined_data['customer_name'])) {
            $value = $combined_data['customer_name'];
            $detected_customer_name = sanitize_text_field($value);
            error_log('Booking Flow Debug - Using explicit customer_name field: ' . $detected_customer_name);
        }

        // Only use customer_email if it exists as a field key in the form
        if (isset($combined_data['customer_email']) && !empty($combined_data['customer_email'])) {
            $value = $combined_data['customer_email'];
            if (is_email($value)) {
                $detected_customer_email = sanitize_email($value);
                error_log('Booking Flow Debug - Using explicit customer_email field: ' . $detected_customer_email);
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

        // Only update combined_data with auto-detected values if they are valid
        // This preserves original field data while also providing standardized customer_name/customer_email
        if (!empty($detected_customer_name)) {
            // Keep the original field data and add customer_name as a separate field
            $combined_data['customer_name'] = $detected_customer_name;
            error_log('Booking Flow Debug - Added customer_name to combined_data: ' . $detected_customer_name);
        }
        if (!empty($detected_customer_email)) {
            // Keep the original field data and add customer_email as a separate field
            $combined_data['customer_email'] = $detected_customer_email;
            error_log('Booking Flow Debug - Added customer_email to combined_data: ' . $detected_customer_email);
        }

        // Keep time_slot for validation but will be removed before database storage
        $time_slot_for_validation = isset($combined_data['time_slot']) ? $combined_data['time_slot'] : null;

        // Merge all non-core fields directly into combined_data (including files)
        foreach ($combined_data as $key => $value) {
            if (!in_array($key, ['customer_name', 'customer_email', 'booking_date', 'booking_time', 'service_type'])) {
                if (isset($_FILES[$key])) {
                    // Check if file was actually uploaded (not empty)
                    $file_uploaded = !empty($_FILES[$key]['name']);
                    
                    if ($file_uploaded) {
                        $customer_name = isset($combined_data['customer_name']) ? $combined_data['customer_name'] : '';
                        $service_type = isset($combined_data['service_type']) ? $combined_data['service_type'] : '';
                        $file = $this->handle_file_upload($_FILES[$key], $customer_name, $service_type);
                        
                        if ($file) {
                            $combined_data[$key] = $file;
                        } else {
                            // File upload failed validation - return error
                            wp_send_json_error(array(
                                'message' => __('File upload gagal. Format yang diterima: JPG, PNG, PDF. Maksimal ukuran: 5MB.', 'archeus-booking')
                            ));
                            return;
                        }
                    } else {
                        // No file uploaded - keep empty or text value
                        $combined_data[$key] = sanitize_text_field($value);
                    }
                } else {
                    $combined_data[$key] = sanitize_text_field($value);
                }
            }
        }
        // Ensure file-only fields (not present in combined_data) are captured as well by scanning $_FILES
        foreach (array_keys($_FILES ?: array()) as $k) {
            if (!isset($combined_data[$k])) {
                // Check if file was actually uploaded
                $file_uploaded = !empty($_FILES[$k]['name']);
                
                if ($file_uploaded) {
                    $customer_name = isset($combined_data['customer_name']) ? $combined_data['customer_name'] : '';
                    $service_type = isset($combined_data['service_type']) ? $combined_data['service_type'] : '';
                    $file = $this->handle_file_upload($_FILES[$k], $customer_name, $service_type);
                    
                    if ($file) {
                        $combined_data[$k] = $file;
                    } else {
                        // File upload failed validation - return error
                        wp_send_json_error(array(
                            'message' => __('File upload gagal. Format yang diterima: JPG, PNG, PDF. Maksimal ukuran: 5MB.', 'archeus-booking')
                        ));
                        return;
                    }
                }
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

                // Query UNIFIED TABLE (v1.3.0+) instead of deprecated per-flow tables
                $unified_table = $wpdb->prefix . 'archeus_booking';
                $placeholders = implode(',', array_fill(0, count($blocking_statuses), '%s'));
                $sql = "SELECT COUNT(*) FROM {$unified_table} 
                        WHERE booking_date = %s 
                        AND booking_time = %s 
                        AND status IN ($placeholders)";
                $params = array_merge(
                    array($date, $start_time . '-' . $end_time), 
                    $blocking_statuses
                );

                $existing_blocking_bookings = intval($wpdb->get_var($wpdb->prepare($sql, $params)));

                // Check schedule capacity AND no blocking bookings
                $schedule_available = ($schedule && ($schedule->current_bookings < $schedule->max_capacity));
                $no_blocking_bookings = ($existing_blocking_bookings == 0);

                if ($schedule_available && $no_blocking_bookings) {
                    // Add schedule information to booking data
                    $booking_data['schedule_id'] = $schedule->id;
                    $booking_data['booking_date'] = $date; // Ensure booking_date is set
                    $booking_data['booking_time'] = $start_time . '-' . $end_time; // Store time range in booking_time

                    // NOTE: Bookings are saved to unified table (wp_archeus_booking)
                    // DEPRECATED: Per-flow table column creation removed in v1.3.0
                    // All custom fields are stored in JSON 'fields' and 'payload' columns
                    
                    // $file_columns = array();
                    // foreach (array_keys($_FILES ?: array()) as $fk) { $file_columns[$fk] = 'VARCHAR(255)'; }
                    // DEPRECATED (v1.3.0): ensure_columns_for_flow() no longer needed with unified table
                    // if (method_exists($booking_db, 'ensure_columns_for_flow')) {
                    //     $all_spec = array();
                    //     foreach ($combined_data as $kk => $_vv) {
                    //         if (!in_array($kk, array('customer_name','customer_email','booking_date','booking_time','service_type'))) {
                    //             $all_spec[$kk] = 'LONGTEXT';
                    //         }
                    //     }
                    //     foreach ($file_columns as $fk => $type) { $all_spec[$fk] = $type; }
                    //     if (!empty($all_spec)) { $booking_db->ensure_columns_for_flow($flow->name, $all_spec); }
                    // }

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

                    error_log('Booking Flow Debug - Final flow_payload (with time slot): ' . print_r($flow_payload, true));
                    error_log('Booking Flow Debug - Flow name (with time slot): ' . $flow->name);

                    $insert_id = $booking_db->insert_flow_submission($flow->name, $flow_payload);
                    error_log('Booking Flow Debug - Insert result (with time slot): ' . ($insert_id !== false ? 'Success ID: ' . $insert_id : 'Failed'));

                    if ($insert_id !== false) {
                        // Do not increment capacity on pending; will increment when approved by admin
                        
                        // Add booking ID to booking data for email notifications
                        $booking_data['booking_id'] = $insert_id;
                        
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

            // NOTE: Bookings are saved to unified table (wp_archeus_booking)
            // DEPRECATED: Per-flow table column creation removed in v1.3.0
            // All custom fields are stored in JSON 'fields' and 'payload' columns
            
            // $file_columns = array();
            // foreach (array_keys($_FILES ?: array()) as $fk) { $file_columns[$fk] = 'VARCHAR(255)'; }
            // DEPRECATED (v1.3.0): ensure_columns_for_flow() no longer needed with unified table
            // if (method_exists($booking_db, 'ensure_columns_for_flow')) {
            //     $all_spec = array();
            //     foreach ($combined_data as $kk => $_vv) {
            //         if (!in_array($kk, array('booking_date','booking_time','service_type'))) {
            //             $all_spec[$kk] = 'LONGTEXT';
            //         }
            //     }
            //     foreach ($file_columns as $fk => $type) { $all_spec[$fk] = $type; }
            //     if (!empty($all_spec)) {
            //         error_log('Booking Flow Debug - Creating columns for flow: ' . print_r($all_spec, true));
            //         $booking_db->ensure_columns_for_flow($flow->name, $all_spec);
            //     }
            // }

            // Build payload with all inputs as flat columns (no additional_fields)
            $flow_payload = $booking_data;
            foreach ($combined_data as $k => $v) {
                if (is_array($v)) { $v = wp_json_encode($v); }
                $flow_payload[$k] = $v;
            }
            $flow_payload['time_slot'] = $time_slot;
            $flow_payload['flow_id'] = $flow_id;
            $flow_payload['flow_name'] = $flow->name;

            error_log('Booking Flow Debug - Final flow_payload (no time slot): ' . print_r($flow_payload, true));
            error_log('Booking Flow Debug - Flow name: ' . $flow->name);

            $insert_id = $booking_db->insert_flow_submission($flow->name, $flow_payload);
            error_log('Booking Flow Debug - Insert result (no time slot): ' . ($insert_id !== false ? 'Success ID: ' . $insert_id : 'Failed'));

            if ($insert_id !== false) {
                // Add booking ID to booking data for email notifications
                $booking_data['booking_id'] = $insert_id;
                
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
    private function handle_file_upload($file, $customer_name = '', $service_type = '') {
        if (!isset($file['name']) || $file['name'] == '') {
            return false;
        }
        
        // Check if file upload was successful
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        // Validate file type - only accept JPG, PNG, PDF for vaccine proof documents
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'application/pdf');
        $file_type = sanitize_mime_type($file['type']);

        // Additional security: Check file extension matches type
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'pdf');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Check file extension
        if (!in_array($file_extension, $allowed_extensions)) {
            error_log('File upload validation failed: Extension not allowed - ' . $file_extension . '. Allowed: jpg, jpeg, png, pdf');
            return false;
        }

        // Check file type (MIME type)
        if (!in_array($file_type, $allowed_types)) {
            error_log('File upload validation failed: MIME type not allowed - ' . $file_type . '. Allowed: image/jpeg, image/png, application/pdf');
            return false;
        }

        // Validate file size (5MB limit)
        if ($file['size'] > 5 * 1024 * 1024) {
            error_log('File upload validation failed: File too large - ' . ($file['size'] / 1024 / 1024) . 'MB');
            return false;
        }
        
        // Generate filename based on customer name, service type, and current date
        $upload_dir = wp_upload_dir();
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $original_filename = pathinfo($file['name'], PATHINFO_FILENAME);

        // Clean and prepare components for filename
        $customer_name_clean = !empty($customer_name) ? sanitize_title($customer_name) : '';
        $service_type_clean = !empty($service_type) ? sanitize_title($service_type) : '';
        $current_date = date('Y-m-d_H-i-s');

        // Build filename based on available data
        if (!empty($customer_name_clean) && !empty($service_type_clean)) {
            // Format: customer_name_service_type_date.ext
            $file_base = $customer_name_clean . '_' . $service_type_clean . '_' . $current_date;
        } else {
            // Fallback format: original_filename_date.ext
            $file_base = $original_filename . '_' . $current_date;
        }

        // Sanitize and ensure unique filename
        $file_name = sanitize_file_name($file_base . '.' . $file_extension);
        $file_path = $upload_dir['path'] . '/' . $file_name;

        // Ensure filename is unique (add counter if file exists)
        $counter = 1;
        while (file_exists($file_path)) {
            $file_name = sanitize_file_name($file_base . '_' . $counter . '.' . $file_extension);
            $file_path = $upload_dir['path'] . '/' . $file_name;
            $counter++;
        }
        
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
            'company_url' => trailingslashit(get_bloginfo('url')) . 'wp-login.php',
            'admin_website' => trailingslashit(get_bloginfo('url')) . 'wp-login.php',
            'admin_email' => get_option('admin_email'),
            'current_date' => date('Y-m-d'),
            'current_time' => date('H:i:s'),
            'current_datetime' => date('Y-m-d H:i:s')
        );

        // Replace all available tags in the subject
        $subject = $subject_template;

        // Replace standard tags
        foreach ($data as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value, $subject);
        }

        // Replace Indonesian language tags (aliases for English tags)
        $subject = str_replace('{nama_lengkap}', $data['customer_name'], $subject);
        $subject = str_replace('{nama}', $data['customer_name'], $subject);
        $subject = str_replace('{email_pelanggan}', $data['customer_email'], $subject);
        $subject = str_replace('{alamat_email}', $data['customer_email'], $subject);
        $subject = str_replace('{tanggal_reservasi}', $data['booking_date'], $subject);
        $subject = str_replace('{waktu_reservasi}', $data['booking_time'], $subject);
        $subject = str_replace('{layanan}', $data['service_type'], $subject);
        $subject = str_replace('{jenis_layanan}', $data['service_type'], $subject);
        $subject = str_replace('{slot_waktu}', $data['time_slot'], $subject);
        $subject = str_replace('{nama_perusahaan}', $data['company_name'], $subject);
        $subject = str_replace('{url_perusahaan}', $data['company_url'], $subject);
        $subject = str_replace('{company_url}', $data['company_url'], $subject);
        $subject = str_replace('{url_admin}', $data['admin_website'], $subject);
        $subject = str_replace('{admin_website}', $data['admin_website'], $subject);
        $subject = str_replace('{email_admin}', $data['admin_email'], $subject);
        $subject = str_replace('{current_datetime}', $data['current_datetime'], $subject);

        // Clean up any remaining tags (replace with empty string)
        $subject = preg_replace('/\{[^}]+\}/', '', $subject);

        return trim($subject);
    }
}


