<?php
/**
 * Booking Admin Class
 * Developed by Archeus Catalyst
 */

if (!defined('ABSPATH')) {
    exit;
}

class Booking_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_update_booking_status', array($this, 'handle_booking_status_update'));
        add_action('wp_ajax_get_bookings', array($this, 'handle_get_bookings'));
        add_action('wp_ajax_get_booking_details', array($this, 'handle_get_booking_details'));
        add_action('admin_post_save_booking_settings', array($this, 'save_booking_settings'));
        add_action('wp_ajax_delete_booking', array($this, 'handle_booking_deletion'));
        add_action('wp_ajax_delete_form', array($this, 'handle_form_deletion'));
        add_action('wp_ajax_create_form', array($this, 'handle_form_creation'));
        add_action('wp_ajax_update_form', array($this, 'handle_form_update'));
        add_action('wp_ajax_delete_service', array($this, 'handle_service_deletion'));
        add_action('wp_ajax_delete_time_slot', array($this, 'handle_time_slot_deletion'));
        add_action('wp_ajax_delete_flow', array($this, 'handle_flow_deletion'));
        add_action('wp_ajax_create_service', array($this, 'handle_service_creation'));
        add_action('wp_ajax_update_service', array($this, 'handle_service_update'));
        add_action('wp_ajax_create_time_slot', array($this, 'handle_time_slot_creation'));
        add_action('wp_ajax_update_time_slot', array($this, 'handle_time_slot_update'));
        add_action('wp_ajax_get_admin_calendar_data', array($this, 'handle_get_admin_calendar_data'));


        add_action('admin_post_save_form_settings', array($this, 'save_form_settings'));
                add_action('admin_post_test_email_notification', array($this, 'test_email_notification'));

        // Add debugging page
        add_action('admin_menu', array($this, 'add_debug_menu'));
        add_action('wp_ajax_clear_email_logs', array($this, 'clear_email_logs'));

  
        // Add language initialization
        add_action('init', array($this, 'load_plugin_textdomain'));

        // Add confirmation dialog to admin footer
        add_action('admin_footer', array($this, 'render_confirmation_dialog'));

        // Add action to handle the time slots page
        add_action('admin_menu', array($this, 'add_time_slots_menu'));
        
        // Tambahkan menu Booking Flow
        add_action('admin_menu', array($this, 'add_booking_flow_menu'));
        // Global notice on plugin pages
        add_action('admin_notices', array($this, 'booking_flow_usage_notice'));
    }

    // Note: auto-translation of labels to field keys removed by request.

    /**
     * Get email content with proper logic - no defaults if custom content exists or empty
     */
    private function get_email_content($email_settings, $content_key, $default_callback = null) {
        // Check if content exists and is not empty
        if (isset($email_settings[$content_key])) {
            $content = $email_settings[$content_key];
            // Trim and check if it's not empty (not just whitespace)
            if (trim($content) !== '') {
                return $content;
            }
        }

        // If no content exists or it's empty, return empty string
        // Don't use default templates unless explicitly requested
        return '';
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_plugin_textdomain() {
        $locale = get_option('archeus_booking_locale', get_locale());
        $locale = apply_filters('plugin_locale', $locale, 'archeus-booking');
        load_plugin_textdomain('archeus-booking', false, dirname(plugin_basename(ARCHEUS_BOOKING_PATH)) . '/languages');
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu - Archeus Bookings (Dashboard)
        add_menu_page(
            __('Dashboard', 'archeus-booking'),
            __('Archeus Booking', 'archeus-booking'),
            'manage_options',
            'archeus-booking-management',
            array($this, 'admin_page'),
            'dashicons-calendar',
            30
        );
        
        // Calendar submenu
        add_submenu_page(
            'archeus-booking-management',
            __('Ketersediaan Kalender', 'archeus-booking'),
            __('Calendar', 'archeus-booking'),
            'manage_options',
            'archeus-booking-calendar',
            array($this, 'calendar_page')
        );
        
        // Forms submenu
        add_submenu_page(
            'archeus-booking-management',
            __('Formulir', 'archeus-booking'),
            __('Forms', 'archeus-booking'),
            'manage_options',
            'archeus-booking-forms',
            array($this, 'forms_page')
        );
        
        // Email submenu
        add_submenu_page(
            'archeus-booking-management',
            __('Email', 'archeus-booking'),
            __('Email', 'archeus-booking'),
            'manage_options',
            'archeus-booking-email',
            array($this, 'email_page')
        );
        
        // Services submenu
        add_submenu_page(
            'archeus-booking-management',
            __('Kelola Layanan', 'archeus-booking'),
            __('Services', 'archeus-booking'),
            'manage_options',
            'archeus-booking-services',
            array($this, 'services_page')
        );
        

        
        // Settings submenu
        add_submenu_page(
            'archeus-booking-management',
            __('Pengaturan Plugin', 'archeus-booking'),
            __('Settings', 'archeus-booking'),
            'manage_options',
            'archeus-booking-settings',
            array($this, 'settings_page')
        );

    }

    /**
     * Admin notice reminding to configure booking flow for frontend usage.
     */
    public function booking_flow_usage_notice() {
        if (!current_user_can('manage_options')) return;
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen) return;
        $allowed = array(
            'toplevel_page_archeus-booking-management',
            'booking_page_archeus-booking-calendar',
            'booking_page_archeus-booking-email',
            'booking_page_archeus-booking-settings',
        );
        if (in_array($screen->id, $allowed, true)) {
            $flow_url = admin_url('admin.php?page=archeus-booking-flow');
            $db = class_exists('Booking_Database') ? new Booking_Database() : null;
            $flows = $db && method_exists($db, 'get_booking_flows') ? (array) $db->get_booking_flows() : array();
            $first_id = !empty($flows) && isset($flows[0]->id) ? intval($flows[0]->id) : 1;
            // Prefer smallest ID with data if possible
            if (!empty($flows)) {
                usort($flows, function($a,$b){ return intval($a->id) - intval($b->id); });
                if (method_exists($db, 'get_booking_counts')) {
                    foreach ($flows as $f) {
                        $c = $db->get_booking_counts(intval($f->id));
                        if (is_array($c) && intval($c['total']) > 0) { $first_id = intval($f->id); break; }
                    }
                } else {
                    $first_id = intval($flows[0]->id);
                }
            }

            echo '<div class="notice notice-info ab-callout is-dismissible" style="padding:0;border-left-width:0;">'
               . '<div class="ab-callout-inner">'
               . '  <div class="ab-callout-icon" aria-hidden="true">'
               . '    <span class="dashicons dashicons-shortcode"></span>'
               . '  </div>'
               . '  <div class="ab-callout-body">'
               . '    <h3 class="ab-callout-title">' . esc_html__('Tampilkan di Sisi Pengguna', 'archeus-booking') . '</h3>'
               . '    <p class="ab-callout-desc">' . esc_html__('Gunakan shortcode dengan ID flow (contoh: [archeus_booking id="1"]).', 'archeus-booking') . '</p>'

               . '    <div class="ab-shortcode-row">'
               . '      <label for="ab-flow-select" style="margin-right:6px;">' . esc_html__('Pilih Flow:', 'archeus-booking') . '</label>'
               . '      <select id="ab-flow-select" class="ab-select ab-dropdown">';

            if (!empty($flows)) {
                foreach ($flows as $f) {
                    $id = intval($f->id);
                    $name = !empty($f->name) ? $f->name : ('Flow #' . $id);
                    echo '<option value="' . esc_attr($id) . '"' . selected($id, $first_id, false) . '>' . esc_html($name) . '</option>';
                }
            } else {
                echo '<option value="1">' . esc_html__('Flow #1', 'archeus-booking') . '</option>';
            }

            echo    '</select>'
               . '      <code class="ab-shortcode-code" id="ab-sc-with-id">[archeus_booking id="' . esc_attr($first_id) . '"]</code>'
               . '      <button type="button" class="button ab-copy-btn" id="ab-copy-with-id" data-copy="[archeus_booking id=\"' . esc_attr($first_id) . '\"]" aria-label="' . esc_attr__('Salin shortcode', 'archeus-booking') . '"><span class="dashicons dashicons-clipboard"></span><span>' . esc_html__('Salin', 'archeus-booking') . '</span></button>'
               . '      <a class="button button-primary" href="' . esc_url($flow_url) . '">' . esc_html__('Konfigurasi Booking Flow', 'archeus-booking') . '</a>'
               . '    </div>'

               . '  </div>'
               . '</div>'
               . '</div>'
               . '<script>(function(){
                    var sel = document.getElementById("ab-flow-select");
                    if (!sel) return;
                    sel.addEventListener("change", function(){
                        var id = this.value || "1";
                        var code = "[archeus_booking id=\""+id+"\"]";
                        var el = document.getElementById("ab-sc-with-id"); if (el) el.textContent = code;
                        var btn = document.getElementById("ab-copy-with-id"); if (btn) btn.setAttribute("data-copy", code);
                    });
                })();</script>';
        }
    }
    

  
    /**
     * Auto-detect field type based on label
     *
     * @param string $label The field label
     * @param string $current_key The current field key
     * @param array $used_keys Array of already used keys
     * @return string The detected/appropriate field key
     */
    private function auto_detect_field_key($label, $current_key, &$used_keys) {
        $label_lower = strtolower($label);

        // Primary name detection patterns (must match exactly or contain specific full phrases)
        $primary_name_patterns = array(
            'nama lengkap', 'full name', 'complete name', 'customer name',
            'nama lengkap anda', 'your full name', 'nama customer', 'nama pelanggan',
            'nama pengunjung', 'visitor name', 'guest name', 'nama anda', 'your name'
        );

        // Exact match patterns for single words (only if the entire label matches)
        $exact_name_patterns = array('nama', 'name');

        // Email detection patterns
        $email_patterns = array(
            'email', 'email address', 'e-mail', 'email anda', 'your email',
            'alamat email', 'email customer', 'email pelanggan',
            'surat elektronik', 'electronic mail'
        );

        // Check primary name patterns first (these are phrases that should always be detected)
        foreach ($primary_name_patterns as $pattern) {
            if (strpos($label_lower, $pattern) !== false) {
                // If customer_name is not used, use it
                if (!isset($used_keys['customer_name'])) {
                    return 'customer_name';
                }
                // If customer_name is used but this is not the current key, don't change
                if ($current_key !== 'customer_name') {
                    return $current_key;
                }
                break;
            }
        }

        // Check exact match for single words (more restrictive)
        foreach ($exact_name_patterns as $pattern) {
            if ($label_lower === $pattern) {
                // If customer_name is not used, use it
                if (!isset($used_keys['customer_name'])) {
                    return 'customer_name';
                }
                // If customer_name is used but this is not the current key, don't change
                if ($current_key !== 'customer_name') {
                    return $current_key;
                }
                break;
            }
        }

        // Check email patterns
        foreach ($email_patterns as $pattern) {
            if (strpos($label_lower, $pattern) !== false) {
                // If customer_email is not used, use it
                if (!isset($used_keys['customer_email'])) {
                    return 'customer_email';
                }
                // If customer_email is used but this is not the current key, don't change
                if ($current_key !== 'customer_email') {
                    return $current_key;
                }
                break;
            }
        }

        return $current_key;
    }

    /**
     * Forms page content - Multi-form management
     */
    public function forms_page() {
        $booking_db = new Booking_Database();
        $forms = $booking_db->get_forms();

        // Show success message if form was saved
        if (isset($_GET['form_saved']) && $_GET['form_saved'] === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Formulir berhasil disimpan!', 'archeus-booking') . '</p></div>';
        }

        // Handle form creation/update (fallback for non-AJAX requests)
        if (isset($_POST['save_form']) && wp_verify_nonce($_POST['booking_forms_nonce'], 'save_booking_forms') && !isset($_POST['action'])) {
            $name = sanitize_text_field($_POST['form_name']);
            $slug = '';
            $description = '';
            
            $fields = array();
            $rename_map = array();
            if (isset($_POST['field_keys'])) {
                $used_keys = array();
                foreach ($_POST['field_keys'] as $index => $field_key) {
                    $label = sanitize_text_field($_POST['field_labels'][$field_key]);

                    // First, process the user-provided key as before
                    $new_key_raw = isset($_POST['field_keys_input'][$field_key]) ? $_POST['field_keys_input'][$field_key] : $field_key;
                    $new_key = strtolower($new_key_raw);
                    $new_key = preg_replace('/[^a-z0-9]+/u', '_', $new_key);
                    $new_key = trim($new_key, '_');
                    if ($new_key === '') { $new_key = $field_key; }
                    if (ctype_digit(substr($new_key, 0, 1))) { $new_key = 'field_' . $new_key; }

                    // Apply auto-detection based on label
                    $new_key = $this->auto_detect_field_key($label, $new_key, $used_keys);

                    // Handle key conflicts
                    $base = $new_key; $i = 2; while (isset($used_keys[$new_key])) { $new_key = $base . '_' . $i++; }
                    $used_keys[$new_key] = true;

                    if ($new_key !== $field_key) {
                        $rename_map[$field_key] = $new_key;
                    }

                    $type = sanitize_text_field($_POST['field_types'][$field_key]);
                    $required = isset($_POST['field_required'][$field_key]) ? 1 : 0;
                    $placeholder = isset($_POST['field_placeholders'][$field_key]) ? sanitize_text_field($_POST['field_placeholders'][$field_key]) : '';

                    $options = array();
                    if ($type === 'select' && isset($_POST['field_options'][$field_key])) {
                        $raw = wp_unslash($_POST['field_options'][$field_key]);
                        $lines = preg_split('/\r\n|\r|\n/', (string)$raw);
                        foreach ($lines as $line) {
                            $opt = trim($line);
                            if ($opt !== '') { $options[] = $opt; }
                        }
                    }

                    $fields[$new_key] = array(
                        'label' => $label,
                        'type' => $type,
                        'required' => $required,
                        'placeholder' => $placeholder,
                        'options' => $options
                    );
                }
            }
            
            if (isset($_POST['form_id']) && !empty($_POST['form_id'])) {
                $form_id = intval($_POST['form_id']);
                $existing = $booking_db->get_form($form_id);
                $slug_to_use = ($existing && !empty($existing->slug)) ? $existing->slug : ('form-' . uniqid());
                $result = $booking_db->update_form($form_id, $name, $slug_to_use, $description, $fields);
                $message = $result ? __('Formulir berhasil diperbarui.', 'archeus-booking') : __('Gagal memperbarui formulir.', 'archeus-booking');
            } else {
                $auto_slug = 'form-' . uniqid();
                $form_id = $booking_db->create_form($name, $auto_slug, $description, $fields);
                $message = $form_id ? __('Formulir berhasil dibuat.', 'archeus-booking') : __('Gagal membuat formulir.', 'archeus-booking');
            }
            
            if (isset($message)) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            }
            
            try {
                if (method_exists($booking_db, 'get_booking_flows')) {
                    $flows = $booking_db->get_booking_flows();
                    foreach ($flows as $flow) {
                        $sections_for_form = !empty($flow->sections) ? (is_string($flow->sections) ? json_decode($flow->sections, true) : $flow->sections) : (is_string($flow->steps) ? json_decode($flow->steps, true) : $flow->steps);
                        if (is_array($sections_for_form)) {
                            foreach ($sections_for_form as $st) {
                                if (isset($st['type']) && $st['type'] === 'form' && !empty($st['form_id']) && intval($st['form_id']) === intval($form_id)) {
                                    if (!empty($rename_map) && method_exists($booking_db, 'rename_columns_for_flow')) {
                                        $booking_db->rename_columns_for_flow($flow->id, $rename_map);
                                    }
                                    if (method_exists($booking_db, 'normalize_columns_by_keys')) {
                                        $booking_db->normalize_columns_by_keys($flow->id);
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {}
            $forms = $booking_db->get_forms();
        }
        
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['form_id'])) {
            $form_id = intval($_GET['form_id']);
            $result = $booking_db->delete_form($form_id);
            $message = $result ? __('Formulir berhasil dihapus.', 'archeus-booking') : __('Gagal menghapus formulir.', 'archeus-booking');
            if (isset($message)) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            }
            $forms = $booking_db->get_forms();
        }
        
        $edit_form = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['form_id'])) {
            $form_id = intval($_GET['form_id']);
            $edit_form = $booking_db->get_form($form_id);
        }
        ?>
        <div class="wrap booking-admin-page">
            <h1 class="title-page"><?php _e('Booking Form (Formulir Reservasi)', 'archeus-booking'); ?></h1>
            <?php
            $flows = method_exists($booking_db, 'get_booking_flows') ? (array) $booking_db->get_booking_flows() : array();
            $first_id = 1;
            if (!empty($flows)) {
                usort($flows, function($a,$b){ return intval($a->id) - intval($b->id); });
                $first_id = isset($flows[0]->id) ? intval($flows[0]->id) : 1;
            }
            $flow_url = admin_url('admin.php?page=archeus-booking-flow');
            echo '<div class="notice notice-info ab-callout is-dismissible" style="padding:0;border-left-width:0;">'
               . '<div class="ab-callout-inner">'
               . '  <div class="ab-callout-icon" aria-hidden="true">'
               . '    <span class="dashicons dashicons-shortcode"></span>'
               . '  </div>'
               . '  <div class="ab-callout-body">'
               . '    <h3 class="ab-callout-title">' . esc_html__('Tampilkan di Sisi Pengguna', 'archeus-booking') . '</h3>'
               . '    <p class="ab-callout-desc">' . esc_html__('Gunakan shortcode dengan ID flow (contoh: [archeus_booking id="1"]).', 'archeus-booking') . '</p>'
               . '    <div class="ab-shortcode-row">'
               . '      <label for="ab-flow-select" style="margin-right:6px;">' . esc_html__('Pilih Flow:', 'archeus-booking') . '</label>'
               . '      <select id="ab-flow-select" class="ab-select ab-dropdown">';
            if (!empty($flows)) {
                foreach ($flows as $f) {
                    $id = intval($f->id);
                    $name = !empty($f->name) ? $f->name : ('Flow #' . $id);
                    echo '<option value="' . esc_attr($id) . '"' . selected($id, $first_id, false) . '>' . esc_html($name) . '</option>';
                }
            } else {
                echo '<option value="1">' . esc_html__('Flow #1', 'archeus-booking') . '</option>';
            }
            echo    '</select>'
               . '      <code class="ab-shortcode-code" id="ab-sc-with-id">[archeus_booking id="' . esc_attr($first_id) . '"]</code>'
               . '      <button type="button" class="button ab-copy-btn" id="ab-copy-with-id" data-copy="[archeus_booking id=\"' . esc_attr($first_id) . '\"]" aria-label="' . esc_attr__('Salin shortcode', 'archeus-booking') . '"><span class="dashicons dashicons-clipboard"></span><span>' . esc_html__('Salin', 'archeus-booking') . '</span></button>'
               . '      <a class="button button-primary" href="' . esc_url($flow_url) . '">' . esc_html__('Konfigurasi Booking Flow', 'archeus-booking') . '</a>'
               . '    </div>'
               . '  </div>'
               . '</div>'
               . '</div>';
            ?>
            
            <div class="admin-card" style="margin-top: 24px;">
                <div class="admin-card-header">
                    <h2><?php echo $edit_form ? __('Ubah Formulir', 'archeus-booking') : __('Buat Formulir Baru', 'archeus-booking'); ?></h2>
                </div>
                <div class="admin-card-body">
                    <form method="post" action="" class="settings-form" data-ajax-form="true">
                        <?php wp_nonce_field('archeus_booking_admin', 'archeus_booking_admin_nonce'); ?>
                        <?php wp_nonce_field('save_booking_forms', 'booking_forms_nonce'); ?>
                        <input type="hidden" name="form_id" value="<?php echo $edit_form ? esc_attr($edit_form->id) : ''; ?>">
                        
                        <div class="form-row">
                            <label for="form_name"><?php _e('Nama Formulir', 'archeus-booking'); ?></label>
                            <input type="text" id="form_name" name="form_name" value="<?php echo $edit_form ? esc_attr($edit_form->name) : ''; ?>" class="regular-text" required>
                            <p class="description"><?php _e('Masukkan nama deskriptif untuk formulir ini', 'archeus-booking'); ?></p>
                        </div>

                        <h3><?php _e('Field Formulir', 'archeus-booking'); ?></h3>
                        <div class="form-fields-builder">
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Key', 'archeus-booking'); ?><span class="description"><?php _e('Identifier unik', 'archeus-booking'); ?></span></th>
                                        <th><?php _e('Label', 'archeus-booking'); ?><span class="description"><?php _e('Teks yang ditampilkan', 'archeus-booking'); ?></span></th>
                                        <th><?php _e('Tipe', 'archeus-booking'); ?></th>
                                        <th class="col-required"><?php _e('Required', 'archeus-booking'); ?><span class="description"><?php _e('Harus/Tidak', 'archeus-booking'); ?></span></th>
                                        <th><?php _e('Placeholder', 'archeus-booking'); ?></th>
                                        <th><?php _e('Pilihan (untuk Select)', 'archeus-booking'); ?></th>
                                        <th class="col-actions"><?php _e('Tindakan', 'archeus-booking'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="form-fields-container">
                                    <?php 
                                    $form_fields = $edit_form ? ($edit_form->fields ? maybe_unserialize($edit_form->fields) : array()) : array(
                                        'customer_name' => array('label' => 'Nama Lengkap', 'type' => 'text', 'required' => 0, 'placeholder' => ''),
                                        'customer_email' => array('label' => 'Email', 'type' => 'email', 'required' => 0, 'placeholder' => ''),
                                    );
                                    foreach ($form_fields as $field_key => $field_data):
                                        // Check if this is an auto-detected field
                                        $is_auto_detected = ($field_key === 'customer_name' || $field_key === 'customer_email');
                                        $auto_type = '';
                                        if ($field_key === 'customer_name') $auto_type = 'name';
                                        if ($field_key === 'customer_email') $auto_type = 'email';
                                        ?>
                                        <tr class="form-field-row" data-auto-detected="<?php echo $is_auto_detected ? 'true' : 'false'; ?>" data-auto-type="<?php echo esc_attr($auto_type); ?>">
                                            <td>
                                                <input type="hidden" name="field_keys[]" value="<?php echo esc_attr($field_key); ?>">
                                                <?php if ($is_auto_detected): ?>
                                                    <input type="text" name="field_keys_input[<?php echo esc_attr($field_key); ?>]" value="<?php echo esc_attr($field_key); ?>" class="regular-text auto-detected-key" placeholder="contoh: nama_hewan" readonly>
                                                <?php else: ?>
                                                    <input type="text" name="field_keys_input[<?php echo esc_attr($field_key); ?>]" value="<?php echo esc_attr($field_key); ?>" class="regular-text" placeholder="contoh: nama_hewan">
                                                <?php endif; ?>
                                            </td>
                                            <td><input type="text" name="field_labels[<?php echo esc_attr($field_key); ?>]" value="<?php echo esc_attr($field_data['label']); ?>"></td>
                                            <td>
                                                <select name="field_types[<?php echo esc_attr($field_key); ?>]" class="ab-select ab-dropdown field-type-select">
                                                    <option value="text" <?php selected($field_data['type'], 'text'); ?>>Text</option>
                                                    <option value="email" <?php selected($field_data['type'], 'email'); ?>>Email</option>
                                                    <option value="number" <?php selected($field_data['type'], 'number'); ?>>Number</option>
                                                    <option value="date" <?php selected($field_data['type'], 'date'); ?>>Date</option>
                                                    <option value="time" <?php selected($field_data['type'], 'time'); ?>>Time</option>
                                                    <option value="select" <?php selected($field_data['type'], 'select'); ?>>Select</option>
                                                    <option value="textarea" <?php selected($field_data['type'], 'textarea'); ?>>Textarea</option>
                                                    <option value="file" <?php selected($field_data['type'], 'file'); ?>>File Upload</option>
                                                </select>
                                            </td>
                                            <td class="col-required"><input type="checkbox" name="field_required[<?php echo esc_attr($field_key); ?>]" <?php echo !empty($field_data['required']) ? 'checked' : ''; ?> value="1"></td>
                                            <td><input type="text" name="field_placeholders[<?php echo esc_attr($field_key); ?>]" value="<?php echo esc_attr($field_data['placeholder']); ?>"></td>
                                            <td class="options-cell">
                                                <?php $opts = isset($field_data['options']) && is_array($field_data['options']) ? implode("\n", $field_data['options']) : ''; ?>
                                                <textarea name="field_options[<?php echo esc_attr($field_key); ?>]" rows="2" class="large-text field-options" placeholder="Satu nilai per baris" style="<?php echo $field_data['type'] === 'select' ? '' : 'display:none;'; ?>"><?php echo esc_textarea($opts); ?></textarea>
                                                <!-- <p class="description select-only" style="<?php echo $field_data['type'] === 'select' ? '' : 'display:none;'; ?>"><?php _e('Isi hanya untuk tipe Select.', 'archeus-booking'); ?></p> -->
                                            </td>
                                            <td class="col-actions"><button type="button" class="button remove-field" title="<?php esc_attr_e('Hapus Field', 'archeus-booking'); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text"><?php _e('Hapus', 'archeus-booking'); ?></span></button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7" class="col-add-field">
                                            <button type="button" id="add-field-btn" class="button button-secondary"><span class="dashicons dashicons-plus"></span><?php _e('Tambah Field', 'archeus-booking'); ?></button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="form-actions">
                            <?php submit_button($edit_form ? __('Submit', 'archeus-booking') : __('Submit', 'archeus-booking'), 'primary', 'save_form', true, array('id' => 'submit-form-builder')); ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="admin-card" style="margin-top: 24px;">
                <div class="admin-card-header">
                    <h2><?php _e('Daftar Formulir', 'archeus-booking'); ?></h2>
                </div>
                <div class="admin-card-body">
                    <?php if (empty($forms)): ?>
                        <p><?php _e('Belum ada formulir. Buat formulir baru di atas.', 'archeus-booking'); ?></p>
                    <?php else: ?>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('Nama', 'archeus-booking'); ?></th>
                                    <th><?php _e('Jumlah Field', 'archeus-booking'); ?></th>
                                    <!-- <th><?php _e('Penggunaan', 'archeus-booking'); ?></th> -->
                                    <th><?php _e('Tindakan', 'archeus-booking'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forms as $form): ?>
                                    <tr>
                                        <td><?php echo esc_html($form->name); ?></td>
                                        <td><?php 
                                            $fields = $form->fields ? maybe_unserialize($form->fields) : array();
                                            echo count($fields);
                                        ?></td>
                                        <!-- <td><span class="description"><?php _e('Add this form as a “Form” section inside a Booking Flow. Then embed the flow using [archeus_booking id="<flow_id>"] on a page.', 'archeus-booking'); ?></span></td> -->
                                        <td class="col-actions">
                                            <div class="action-buttons">
                                                <a href="<?php echo admin_url('admin.php?page=archeus-booking-forms&action=edit&form_id=' . $form->id); ?>" class="button button-warning edit-button" title="<?php esc_attr_e('Ubah Formulir', 'archeus-booking'); ?>">
                                                    <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                                                    <span class="screen-reader-text"><?php _e('Ubah', 'archeus-booking'); ?></span>
                                                </a>
                                                <a href="#" class="button button-danger delete-form" data-form-id="<?php echo $form->id; ?>" title="<?php esc_attr_e('Hapus Formulir', 'archeus-booking'); ?>">
                                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                                    <span class="screen-reader-text"><?php _e('Hapus', 'archeus-booking'); ?></span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
          <?php
    }
    
    /**
     * Email settings page content
     */
    public function email_page() {
        // Get saved email settings
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
            <p><strong>ID Reservasi:</strong> #{booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time}</p>
            <p><strong>Email:</strong> {customer_email}</p>
        </div>
        <p>Kami akan segera mengkonfirmasi reservasi Anda. Mohon tunggu konfirmasi dari kami.</p>
        <p>Terima kasih,<br>{company_name}</p>
    </div>
</body>
</html>', 'archeus-booking'),
            'enable_admin_notification' => 1,
            'admin_email_address' => get_option('admin_email'),
            'admin_notification_subject' => __('Reservasi Baru #{booking_id} - {service_type}', 'archeus-booking'),
            'admin_notification_body' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #54b335;">Reservasi Baru Diterima</h2>
        <p>{greeting}</p>
        <p>Reservasi baru telah masuk dan membutuhkan perhatian Anda. Berikut adalah detail reservasi:</p>
        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>ID Reservasi:</strong> #{booking_id}</p>
            <p><strong>Pelanggan:</strong> {customer_name}</p>
            <p><strong>Email:</strong> {customer_email}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time}</p>
        </div>
        <p>Silakan login ke dashboard untuk mengelola reservasi ini.</p>
        <p>Terima kasih,<br>{company_name}</p>
    </div>
</body>
</html>', 'archeus-booking'),
            // Status change email settings
            'enable_status_change_emails' => 1,
            'pending_email_subject' => __('Menunggu Konfirmasi Reservasi #{booking_id}', 'archeus-booking'),
            'pending_email_body' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #54b335;">Reservasi Sedang Diproses</h2>
        <p>{greeting}</p>
        <p>Terima kasih telah melakukan reservasi dengan kami. Reservasi Anda sedang dalam proses peninjauan.</p>

        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #54b335;">Detail Reservasi</h3>
            <p><strong>ID Reservasi:</strong> {booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time} {time_slot}</p>
            <p><strong>Email:</strong> {customer_email}</p>
        </div>

        <p>Kami akan segera menghubungi Anda untuk mengkonfirmasi reservasi ini.</p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">Hormat kami,<br>{company_name}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Email ini dikirim pada {current_date} pukul {current_time}
            </p>
        </div>
    </div>
</body>
</html>', 'archeus-booking'),
            'approved_email_subject' => __('Reservasi #{booking_id} Telah Diterima', 'archeus-booking'),
            'approved_email_body' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #54b335;">Reservasi Diterima!</h2>
        <p>{greeting}</p>
        <p>Selamat! Reservasi Anda telah <strong>DISETUJUI</strong>. Kami sangat menantikan kedatangan Anda sesuai dengan jadwal yang telah dipilih.</p>

        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #54b335;">Detail Reservasi</h3>
            <p><strong>ID Reservasi:</strong> {booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time} {time_slot}</p>
            <p><strong>Email:</strong> {customer_email}</p>
        </div>

        <p>Jika ada perubahan jadwal, kami akan menghubungi Anda segera.</p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">Hormat kami,<br>{company_name}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Email ini dikirim pada {current_date} pukul {current_time}
            </p>
        </div>
    </div>
</body>
</html>', 'archeus-booking'),
            'rejected_email_subject' => __('Reservasi #{booking_id} Ditolak', 'archeus-booking'),
            'rejected_email_body' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #e74c3c;">Reservasi Ditolak</h2>
        <p>{greeting}</p>
        <p>Maaf, reservasi Anda telah <strong>DITOLAK</strong>. Jika Anda memiliki pertanyaan atau membutuhkan bantuan, silakan hubungi kami.</p>

        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #e74c3c;">Detail Reservasi</h3>
            <p><strong>ID Reservasi:</strong> {booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time} {time_slot}</p>
            <p><strong>Email:</strong> {customer_email}</p>
        </div>

        <p>Anda dapat melakukan reservasi kembali dengan jadwal yang berbeda jika tersedia.</p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">Hormat kami,<br>{company_name}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Email ini dikirim pada {current_date} pukul {current_time}
            </p>
        </div>
    </div>
</body>
</html>', 'archeus-booking'),
            'completed_email_subject' => __('Reservasi #{booking_id} Selesai', 'archeus-booking'),
            'completed_email_body' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #27ae60;">Reservasi Selesai</h2>
        <p>{greeting}</p>
        <p>Reservasi Anda telah ditandai sebagai selesai. Terima kasih telah menggunakan layanan kami!</p>

        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #27ae60;">Detail Reservasi</h3>
            <p><strong>ID Reservasi:</strong> {booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time} {time_slot}</p>
            <p><strong>Email:</strong> {customer_email}</p>
        </div>

        <p>Kami berharap Anda puas dengan layanan kami. Jangan ragu untuk melakukan reservasi kembali di kemudian hari.</p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">Hormat kami,<br>{company_name}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Email ini dikirim pada {current_date} pukul {current_time}
            </p>
        </div>
    </div>
</body>
</html>', 'archeus-booking'),
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
</html>', 'archeus-booking'),
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
        
        // Save settings if form was submitted
        if (isset($_POST['save_email_settings']) && wp_verify_nonce($_POST['email_settings_nonce'], 'save_email_settings')) {
            // Save email settings
            if (isset($_POST['email_settings'])) {
                $email_settings = array(
                    'enable_customer_confirmation' => isset($_POST['email_settings']['enable_customer_confirmation']) ? 1 : 0,
                    'enable_admin_notification' => isset($_POST['email_settings']['enable_admin_notification']) ? 1 : 0,
                    'admin_email_address' => sanitize_email($_POST['email_settings']['admin_email_address']),
                    'enable_status_change_emails' => isset($_POST['email_settings']['enable_status_change_emails']) ? 1 : 0,
                    'customer_confirmation_subject' => sanitize_text_field($_POST['email_settings']['customer_confirmation_subject']),
                    'customer_confirmation_body' => wp_kses_post($_POST['email_settings']['customer_confirmation_body']),
                    'admin_notification_subject' => sanitize_text_field($_POST['email_settings']['admin_notification_subject']),
                    'admin_notification_body' => wp_kses_post($_POST['email_settings']['admin_notification_body']),
                    // Status email settings
                    'pending_email_subject' => sanitize_text_field($_POST['email_settings']['pending_email_subject']),
                    'pending_email_body' => wp_kses_post($_POST['email_settings']['pending_email_body']),
                    'approved_email_subject' => sanitize_text_field($_POST['email_settings']['approved_email_subject']),
                    'approved_email_body' => wp_kses_post($_POST['email_settings']['approved_email_body']),
                    'rejected_email_subject' => sanitize_text_field($_POST['email_settings']['rejected_email_subject']),
                    'rejected_email_body' => wp_kses_post($_POST['email_settings']['rejected_email_body']),
                    'completed_email_subject' => sanitize_text_field($_POST['email_settings']['completed_email_subject']),
                    'completed_email_body' => wp_kses_post($_POST['email_settings']['completed_email_body'])
                );

                update_option('booking_email_settings', $email_settings);
            }

            echo '<script>
                (function() {
                    var checkShowToast = setInterval(function() {
                        if (typeof showToast === "function") {
                            clearInterval(checkShowToast);
                            showToast("' . esc_js(__('Pengaturan email berhasil diperbarui.', 'archeus-booking')) . '", "success");
                        }
                    }, 100);

                    // Timeout fallback after 3 seconds
                    setTimeout(function() {
                        clearInterval(checkShowToast);
                        if (typeof showToast !== "function") {
                            alert("' . esc_js(__('Pengaturan email berhasil diperbarui.', 'archeus-booking')) . '");
                        }
                    }, 3000);
                })();
            </script>';
        }
        ?>
        <div class="wrap booking-admin-page">
            <h1 class="title-page"><?php _e('Email Setting (Pengaturan Email)', 'archeus-booking'); ?></h1>


            <form method="post" action="" class="settings-form">
                <?php wp_nonce_field('save_email_settings', 'email_settings_nonce'); ?>
                
                <!-- Customer Confirmation Email Card -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <span class="icon">✉️</span>
                            <?php _e('Customer Confirmation Email', 'archeus-booking'); ?>
                            <span class="email-type-badge"><?php _e('Customer', 'archeus-booking'); ?></span>
                        </h3>
                        <div class="admin-card-status">
                            <span class="status-indicator-simple <?php echo isset($email_settings['enable_customer_confirmation']) && $email_settings['enable_customer_confirmation'] ? 'active' : 'inactive'; ?>"></span>
                            <?php echo isset($email_settings['enable_customer_confirmation']) && $email_settings['enable_customer_confirmation'] ? __('Enabled', 'archeus-booking') : __('Disabled', 'archeus-booking'); ?>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <p class="description"><?php _e('Anda dapat menggunakan HTML template lengkap untuk email. Jika template tidak mengandung HTML, sistem akan secara otomatis membungkusnya dengan template standar.', 'archeus-booking'); ?></p>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Confirmation Email', 'archeus-booking'); ?></th>
                                <td>
                                    <label class="toggle-switch-simple">
                                        <input type="checkbox" name="email_settings[enable_customer_confirmation]" value="1" <?php checked($email_settings['enable_customer_confirmation'], 1); ?>>
                                        <span class="toggle-slider-simple"></span>
                                    </label>
                                    <span class="toggle-label"><?php _e('Send confirmation email to customer after booking', 'archeus-booking'); ?></span>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('Email Subject', 'archeus-booking'); ?></th>
                                <td>
                                    <input type="text" name="email_settings[customer_confirmation_subject]" value="<?php echo esc_attr(isset($email_settings['customer_confirmation_subject']) ? $email_settings['customer_confirmation_subject'] : __('Konfirmasi Reservasi #{booking_id} - {service_type}', 'archeus-booking')); ?>">
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('Email Content', 'archeus-booking'); ?></th>
                                <td>
                                    <?php
                                    $customer_confirmation_content = $this->get_email_content($email_settings, 'customer_confirmation_body');

                                    $editor_id = 'customer_confirmation_body';
                                    $settings = array(
                                        'textarea_name' => 'email_settings[customer_confirmation_body]',
                                        'media_buttons' => true,
                                        'textarea_rows' => 15,
                                        'teeny' => false,
                                        'wpautop' => true,
                                        'editor_css' => '<style>
                                            .wp-editor-wrap { max-width: 100%; }
                                            .wp-editor-area {
                                                font-family: monospace;
                                                font-size: 14px;
                                                line-height: 1.5;
                                            }
                                            .mce-content-body {
                                                font-family: inherit;
                                            }
                                        </style>'
                                    );
                                    wp_editor($customer_confirmation_content, $editor_id, $settings);
                                    ?>
                                    <p class="description"><strong><?php _e('Tags yang tersedia:', 'archeus-booking'); ?></strong><br>
                                    {customer_name}, {customer_email}, {booking_date}, {booking_time}, {service_type}, {time_slot}, {company_name}, {company_url}, {admin_website}, {greeting}, {email_title}, {current_date}, {current_time}</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Admin Notification Email Card -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <span class="icon">📋</span>
                            <?php _e('Admin Notification Email', 'archeus-booking'); ?>
                            <span class="email-type-badge"><?php _e('Admin', 'archeus-booking'); ?></span>
                        </h3>
                        <div class="admin-card-status">
                            <span class="status-indicator-simple <?php echo isset($email_settings['enable_admin_notification']) && $email_settings['enable_admin_notification'] ? 'active' : 'inactive'; ?>"></span>
                            <?php echo isset($email_settings['enable_admin_notification']) && $email_settings['enable_admin_notification'] ? __('Enabled', 'archeus-booking') : __('Disabled', 'archeus-booking'); ?>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Admin Notification', 'archeus-booking'); ?></th>
                                <td>
                                    <label class="toggle-switch-simple">
                                        <input type="checkbox" name="email_settings[enable_admin_notification]" value="1" <?php checked($email_settings['enable_admin_notification'], 1); ?>>
                                        <span class="toggle-slider-simple"></span>
                                    </label>
                                    <span class="toggle-label"><?php _e('Send notification email to admin when new booking is received', 'archeus-booking'); ?></span>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('Admin Email Address', 'archeus-booking'); ?></th>
                                <td>
                                    <input type="email" name="email_settings[admin_email_address]" value="<?php echo esc_attr(isset($email_settings['admin_email_address']) ? $email_settings['admin_email_address'] : get_option('admin_email')); ?>" style="width: 100%;">
                                    <p class="description"><?php _e('Email address where admin notifications will be sent. Leave blank to use default WordPress admin email.', 'archeus-booking'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('Email Subject', 'archeus-booking'); ?></th>
                                <td>
                                    <input type="text" name="email_settings[admin_notification_subject]" value="<?php echo esc_attr(isset($email_settings['admin_notification_subject']) ? $email_settings['admin_notification_subject'] : __('Reservasi Baru #{booking_id} - {service_type}', 'archeus-booking')); ?>">
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('Email Content', 'archeus-booking'); ?></th>
                                <td>
                                    <?php
                                    $admin_notification_content = $this->get_email_content($email_settings, 'admin_notification_body');

                                    $editor_id = 'admin_notification_body';
                                    $settings = array(
                                        'textarea_name' => 'email_settings[admin_notification_body]',
                                        'media_buttons' => true,
                                        'textarea_rows' => 15,
                                        'teeny' => false,
                                        'wpautop' => true,
                                        'editor_css' => '<style>.wp-editor-wrap { max-width: 100%; }</style>'
                                    );
                                    wp_editor($admin_notification_content, $editor_id, $settings);
                                    ?>
                                    <p class="description"><strong><?php _e('Tags yang tersedia:', 'archeus-booking'); ?></strong><br>
                                    {customer_name}, {customer_email}, {booking_date}, {booking_time}, {service_type}, {time_slot}, {company_name}, {admin_website}, {admin_email}, {booking_id}, {greeting}, {email_title}, {current_date}, {current_time}</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Status Change Emails Card -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">
                            <span class="icon">🔄</span>
                            <?php _e('Status Change Emails', 'archeus-booking'); ?>
                            <span class="email-type-badge"><?php _e('Status', 'archeus-booking'); ?></span>
                        </h3>
                        <div class="admin-card-status">
                            <span class="status-indicator-simple <?php echo isset($email_settings['enable_status_change_emails']) && $email_settings['enable_status_change_emails'] ? 'active' : 'inactive'; ?>"></span>
                            <?php echo isset($email_settings['enable_status_change_emails']) && $email_settings['enable_status_change_emails'] ? __('Enabled', 'archeus-booking') : __('Disabled', 'archeus-booking'); ?>
                        </div>
                    </div>
                    <div class="admin-card-body">
                        <p class="description"><?php _e('Kustomisasi email yang dikirim ke pelanggan ketika status reservasi berubah. Setiap status dapat memiliki subjek dan konten email yang berbeda.', 'archeus-booking'); ?></p>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Status Change Emails', 'archeus-booking'); ?></th>
                                <td>
                                    <label class="toggle-switch-simple">
                                        <input type="checkbox" name="email_settings[enable_status_change_emails]" value="1" <?php checked(isset($email_settings['enable_status_change_emails']) ? $email_settings['enable_status_change_emails'] : 1, 1); ?>>
                                        <span class="toggle-slider-simple"></span>
                                    </label>
                                    <span class="toggle-label"><?php _e('Kirim email notifikasi ketika status reservasi berubah', 'archeus-booking'); ?></span>
                                </td>
                            </tr>
                        </table>

                        <div class="admin-card-section">
                            <h4 class="admin-card-section-title"><?php _e('Pending Status Email', 'archeus-booking'); ?></h4>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Email Subject', 'archeus-booking'); ?></th>
                                    <td>
                                        <input type="text" name="email_settings[pending_email_subject]" value="<?php echo esc_attr(isset($email_settings['pending_email_subject']) ? $email_settings['pending_email_subject'] : __('Menunggu Konfirmasi Reservasi #{booking_id}', 'archeus-booking')); ?>" style="width: 100%;">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Email Content', 'archeus-booking'); ?></th>
                                    <td>
                                        <?php
                                        $pending_email_content = $this->get_email_content($email_settings, 'pending_email_body');

                                        $editor_id = 'pending_email_body';
                                        $settings = array(
                                            'textarea_name' => 'email_settings[pending_email_body]',
                                            'media_buttons' => true,
                                            'textarea_rows' => 15,
                                            'teeny' => false,
                                            'wpautop' => true,
                                            'editor_css' => '<style>.wp-editor-wrap { max-width: 100%; }</style>'
                                        );
                                        wp_editor($pending_email_content, $editor_id, $settings);
                                        ?>
                                        <p class="description"><strong><?php _e('Tags yang tersedia:', 'archeus-booking'); ?></strong><br>
                                        {customer_name}, {customer_email}, {booking_id}, {booking_date}, {booking_time}, {service_type}, {time_slot}, {company_name}, {company_url}, {admin_website}, {greeting}, {email_title}, {current_date}, {current_time}</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="admin-card-section">
                            <h4 class="admin-card-section-title"><?php _e('Approved Status Email', 'archeus-booking'); ?></h4>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Email Subject', 'archeus-booking'); ?></th>
                                    <td>
                                        <input type="text" name="email_settings[approved_email_subject]" value="<?php echo esc_attr(isset($email_settings['approved_email_subject']) ? $email_settings['approved_email_subject'] : __('Reservasi #{booking_id} Telah Diterima', 'archeus-booking')); ?>" style="width: 100%;">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Email Content', 'archeus-booking'); ?></th>
                                    <td>
                                        <?php
                                        $approved_email_content = $this->get_email_content($email_settings, 'approved_email_body');

                                        $editor_id = 'approved_email_body';
                                        $settings = array(
                                            'textarea_name' => 'email_settings[approved_email_body]',
                                            'media_buttons' => true,
                                            'textarea_rows' => 15,
                                            'teeny' => false,
                                            'wpautop' => true,
                                            'editor_css' => '<style>.wp-editor-wrap { max-width: 100%; }</style>'
                                        );
                                        wp_editor($approved_email_content, $editor_id, $settings);
                                        ?>
                                        <p class="description"><strong><?php _e('Tags yang tersedia:', 'archeus-booking'); ?></strong><br>
                                        {customer_name}, {customer_email}, {booking_id}, {booking_date}, {booking_time}, {service_type}, {time_slot}, {company_name}, {company_url}, {admin_website}, {greeting}, {email_title}, {current_date}, {current_time}</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="admin-card-section">
                            <h4 class="admin-card-section-title"><?php _e('Rejected Status Email', 'archeus-booking'); ?></h4>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Email Subject', 'archeus-booking'); ?></th>
                                    <td>
                                        <input type="text" name="email_settings[rejected_email_subject]" value="<?php echo esc_attr(isset($email_settings['rejected_email_subject']) ? $email_settings['rejected_email_subject'] : __('Reservasi #{booking_id} Ditolak', 'archeus-booking')); ?>" style="width: 100%;">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Email Content', 'archeus-booking'); ?></th>
                                    <td>
                                        <?php
                                        $rejected_email_content = $this->get_email_content($email_settings, 'rejected_email_body');

                                        $editor_id = 'rejected_email_body';
                                        $settings = array(
                                            'textarea_name' => 'email_settings[rejected_email_body]',
                                            'media_buttons' => true,
                                            'textarea_rows' => 15,
                                            'teeny' => false,
                                            'wpautop' => true,
                                            'editor_css' => '<style>.wp-editor-wrap { max-width: 100%; }</style>'
                                        );
                                        wp_editor($rejected_email_content, $editor_id, $settings);
                                        ?>
                                        <p class="description"><strong><?php _e('Tags yang tersedia:', 'archeus-booking'); ?></strong><br>
                                        {customer_name}, {customer_email}, {booking_id}, {booking_date}, {booking_time}, {service_type}, {time_slot}, {company_name}, {company_url}, {admin_website}, {greeting}, {email_title}, {current_date}, {current_time}</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="admin-card-section">
                            <h4 class="admin-card-section-title"><?php _e('Completed Status Email', 'archeus-booking'); ?></h4>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Email Subject', 'archeus-booking'); ?></th>
                                    <td>
                                        <input type="text" name="email_settings[completed_email_subject]" value="<?php echo esc_attr(isset($email_settings['completed_email_subject']) ? $email_settings['completed_email_subject'] : __('Reservasi #{booking_id} Selesai', 'archeus-booking')); ?>" style="width: 100%;">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Email Content', 'archeus-booking'); ?></th>
                                    <td>
                                        <?php
                                        $completed_email_content = $this->get_email_content($email_settings, 'completed_email_body');

                                        $editor_id = 'completed_email_body';
                                        $settings = array(
                                            'textarea_name' => 'email_settings[completed_email_body]',
                                            'media_buttons' => true,
                                            'textarea_rows' => 15,
                                            'teeny' => false,
                                            'wpautop' => true,
                                            'editor_css' => '<style>.wp-editor-wrap { max-width: 100%; }</style>'
                                        );
                                        wp_editor($completed_email_content, $editor_id, $settings);
                                        ?>
                                        <p class="description"><strong><?php _e('Tags yang tersedia:', 'archeus-booking'); ?></strong><br>
                                        {customer_name}, {customer_email}, {booking_id}, {booking_date}, {booking_time}, {service_type}, {time_slot}, {company_name}, {company_url}, {admin_website}, {greeting}, {email_title}, {current_date}, {current_time}</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <?php submit_button(__('Simpan Pengaturan Email', 'archeus-booking'), 'primary', 'save_email_settings'); ?>
                </div>

            </form>

            <!-- Test Email Section -->
            <div class="admin-card" style="margin-top: 30px;">
                <h2><?php _e('Test Email Configuration', 'archeus-booking'); ?></h2>
                <p><?php _e('Send a test email to verify that your email configuration is working correctly.', 'archeus-booking'); ?></p>

                <!-- Email Configuration Info -->
                <div class="email-config-info" style="background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa;">
                    <h4><?php _e('Current Email Configuration', 'archeus-booking'); ?></h4>
                    <table class="widefat" style="margin-top: 10px;">
                        <tr>
                            <td><strong><?php _e('From Email', 'archeus-booking'); ?></strong></td>
                            <td><?php echo esc_html(wp_get_current_user()->user_email); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('Site Name', 'archeus-booking'); ?></strong></td>
                            <td><?php echo esc_html(get_bloginfo('name')); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('WordPress Mail Function', 'archeus-booking'); ?></strong></td>
                            <td><?php echo function_exists('mail') ? __('Available', 'archeus-booking') : __('Not Available', 'archeus-booking'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('PHPMailer', 'archeus-booking'); ?></strong></td>
                            <td><?php echo class_exists('PHPMailer\PHPMailer\PHPMailer') || class_exists('PHPMailer') ? __('Available', 'archeus-booking') : __('Not Available', 'archeus-booking'); ?></td>
                        </tr>
                    </table>
                </div>

                <?php if (isset($_GET['message'])) : ?>
                    <?php if ($_GET['message'] === 'test_success') : ?>
                        <div class="notice notice-success is-dismissible">
                            <p><?php _e('Test email sent successfully! Please check your inbox.', 'archeus-booking'); ?></p>
                        </div>
                    <?php elseif ($_GET['message'] === 'test_failed') : ?>
                        <div class="notice notice-error is-dismissible">
                            <p><?php _e('Failed to send test email. Please check your email configuration and server logs.', 'archeus-booking'); ?></p>
                            <p><strong><?php _e('Debug Info:', 'archeus-booking'); ?></strong></p>
                            <ul>
                                <li><?php _e('Check your server\'s email configuration', 'archeus-booking'); ?></li>
                                <li><?php _e('Verify that your hosting allows outgoing emails', 'archeus-booking'); ?></li>
                                <li><?php _e('Check error logs for more details', 'archeus-booking'); ?></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="post" action="" class="settings-form">
                    <?php wp_nonce_field('test_email_notification', 'test_email_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Test Email Address', 'archeus-booking'); ?></th>
                            <td>
                                <input type="email" name="test_email" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text" required>
                                <p class="description"><?php _e('Enter the email address where you want to receive the test email.', 'archeus-booking'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Send Test Email', 'archeus-booking'), 'secondary', 'test_email_notification'); ?>
                </form>
        </div>
        </div>
        <?php
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Muat aset pada semua halaman plugin (toplevel + submenus)
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $is_plugin_page = (
            $hook === 'toplevel_page_archeus-booking-management' ||
            strpos($hook, 'archeus-booking-management_page_') === 0 ||
            $page === 'archeus-booking-management' ||
            (strpos($page, 'archeus-booking-') === 0)
        );
        if (!$is_plugin_page) { return; }

        wp_enqueue_script('booking-admin-js', ARCHEUS_BOOKING_URL . 'assets/js/admin.js', array('jquery'), ARCHEUS_BOOKING_VERSION, true);
        wp_enqueue_style('booking-admin-css', ARCHEUS_BOOKING_URL . 'assets/css/admin.css', array(), ARCHEUS_BOOKING_VERSION);
        // Styles consolidated into admin.css
        
        wp_localize_script('booking-admin-js', 'archeus_booking_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('archeus_booking_admin_nonce')
        ));
    }

    /**
     * Admin page content
     */
    public function admin_page() {
        $booking_db = new Booking_Database();
        // Determine default flow: prefer smallest ID with data; fallback to smallest ID; else 0 (all)
        $flows = method_exists($booking_db, 'get_booking_flows') ? (array)$booking_db->get_booking_flows() : array();
        $default_flow_id = 0; $default_flow_name = '';
        if (!empty($flows)) {
            // sort by id asc
            usort($flows, function($a,$b){ return intval($a->id) - intval($b->id); });
            // try pick non-empty
            if (method_exists($booking_db, 'get_booking_counts')) {
                foreach ($flows as $f) {
                    $c = $booking_db->get_booking_counts(intval($f->id));
                    if (is_array($c) && intval($c['total']) > 0) { $default_flow_id = intval($f->id); $default_flow_name = $f->name; break; }
                }
            }
            if ($default_flow_id === 0) { $default_flow_id = intval($flows[0]->id); $default_flow_name = $flows[0]->name; }
        }

        $args = array('limit' => 50);
        if ($default_flow_id) { $args['flow_id'] = $default_flow_id; }
        $bookings = $booking_db->get_bookings($args);
        // Stats for default flow
        if (method_exists($booking_db, 'get_booking_counts')) {
            $counts = $booking_db->get_booking_counts($default_flow_id);
            $total_bookings = intval($counts['total']);
            $pending_bookings = intval($counts['pending']);
            $approved_bookings = intval($counts['approved']);
            $completed_bookings = intval($counts['completed']);
            $rejected_bookings = intval($counts['rejected']);
        } else {
            $total_bookings = $booking_db->get_booking_count_by_status('');
            $pending_bookings = $booking_db->get_booking_count_by_status('pending');
            $approved_bookings = $booking_db->get_booking_count_by_status('approved');
            $completed_bookings = $booking_db->get_booking_count_by_status('completed');
            $rejected_bookings = $booking_db->get_booking_count_by_status('rejected');
        }
        ?>
        <div class="wrap booking-admin-page">
            <h1 class="title-page"><?php _e('Dashboard (Daftar Pemesanan)', 'archeus-booking'); ?></h1>
            
            <div class="booking-stats-meta">
                <span class="flow-badge">
                    <?php _e('Flow aktif:', 'archeus-booking'); ?>
                    <strong id="ab-flow-active"><?php echo !empty($default_flow_name) ? esc_html($default_flow_name) : __('Semua Flow','archeus-booking'); ?></strong>
                </span>
            </div>

            <!-- Booking Statistics -->
            <div class="booking-stats">
                <div class="stat-box default">
                    <h3 id="ab-count-total" class="stat-value"><?php echo $total_bookings; ?></h3>
                    <p><?php _e('Total Pemesanan', 'archeus-booking'); ?></p>
                </div>
                <div class="stat-box pending">
                    <h3 id="ab-count-pending" class="stat-value"><?php echo $pending_bookings; ?></h3>
                    <p><?php _e('Menunggu', 'archeus-booking'); ?></p>
                </div>
                <div class="stat-box success">
                    <h3 id="ab-count-approved" class="stat-value"><?php echo $approved_bookings; ?></h3>
                    <p><?php _e('Disetujui', 'archeus-booking'); ?></p>
                </div>
                <div class="stat-box done">
                    <h3 id="ab-count-completed" class="stat-value"><?php echo $completed_bookings; ?></h3>
                    <p><?php _e('Selesai', 'archeus-booking'); ?></p>
                </div>
                <div class="stat-box danger">
                    <h3 id="ab-count-rejected" class="stat-value"><?php echo $rejected_bookings; ?></h3>
                    <p><?php _e('Ditolak', 'archeus-booking'); ?></p>
                </div>
            </div>
            
            <div class="booking-filters">
                <select id="booking-status-filter" class="ab-dropdown filter-dashboard">
                    <option disabled selected><?php _e('-- Pilih Status--', 'archeus-booking'); ?></option>
                    <option value=""><?php _e('Semua Status', 'archeus-booking'); ?></option>
                    <option value="pending"><?php _e('Menunggu (Pending)', 'archeus-booking'); ?></option>
                    <option value="approved"><?php _e('Disetujui (Approved)', 'archeus-booking'); ?></option>
                    <option value="completed"><?php _e('Selesai (Completed)', 'archeus-booking'); ?></option>
                    <option value="rejected"><?php _e('Ditolak (Rejected)', 'archeus-booking'); ?></option>
                </select>
                <!-- Flow filter disembunyikan sesuai permintaan -->
                
                <button id="refresh-bookings" class="button ab-icon-btn" aria-label="<?php esc_attr_e('Muat Ulang', 'archeus-booking'); ?>" title="<?php esc_attr_e('Muat Ulang', 'archeus-booking'); ?>">
                    <span class="dashicons dashicons-update" aria-hidden="true"></span>
                    <span class="screen-reader-text"><?php _e('Muat Ulang', 'archeus-booking'); ?></span>
                </button>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="col-id"><?php _e('ID', 'archeus-booking'); ?></th>
                        <th class="col-name"><?php _e('Nama', 'archeus-booking'); ?></th>
                        <th><?php _e('Tanggal', 'archeus-booking'); ?></th>
                        <th><?php _e('Waktu', 'archeus-booking'); ?></th>
                        <th><?php _e('Layanan', 'archeus-booking'); ?></th>
                        <th><?php _e('Status', 'archeus-booking'); ?></th>
                        <th><?php _e('Tanggal Dibuat', 'archeus-booking'); ?></th>
                        <th><?php _e('Tindakan', 'archeus-booking'); ?></th>
                    </tr>
                </thead>
                <tbody id="bookings-table-body">
                    <?php if (empty($bookings)): ?>
                        <tr class="no-data"><td colspan="8" class="no-data-cell"><?php _e('Data tidak tersedia atau data kosong.', 'archeus-booking'); ?></td></tr>
                    <?php else: foreach ($bookings as $booking): 
                    ?>
                    <tr data-id="<?php echo $booking->id; ?>">
                        <td class="col-id"><?php echo $booking->id; ?></td>
                        <td class="col-name" title="<?php echo esc_attr($booking->customer_name ?? ''); ?>"><?php echo esc_html($booking->customer_name ?? ''); ?></td>
                        <td><?php echo date('M j, Y', strtotime($booking->booking_date)); ?></td>
                        <td><?php echo esc_html($booking->booking_time); ?></td>
                        <td><?php echo esc_html($booking->service_type); ?></td>
                        <td>
                            <select class="booking-status ab-select ab-dropdown" data-id="<?php echo $booking->id; ?>">
                                <option value="pending" <?php selected($booking->status, 'pending'); ?>><?php _e('Pending', 'archeus-booking'); ?></option>
                                <option value="approved" <?php selected($booking->status, 'approved'); ?>><?php _e('Approved', 'archeus-booking'); ?></option>
                                <?php if ($booking->status === 'approved' || $booking->status === 'completed'): ?>
                                    <option value="completed" <?php selected($booking->status, 'completed'); ?>><?php _e('Completed', 'archeus-booking'); ?></option>
                                <?php endif; ?>
                                <option value="rejected" <?php selected($booking->status, 'rejected'); ?>><?php _e('Rejected', 'archeus-booking'); ?></option>
                            </select>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($booking->created_at)); ?></td>
                        <td>
                            <button class="view-details-btn button" data-id="<?php echo $booking->id; ?>" title="<?php esc_attr_e('Lihat Detail', 'archeus-booking'); ?>">
                                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                                <span class="screen-reader-text"><?php _e('Lihat Detail', 'archeus-booking'); ?></span>
                            </button>
                            <button class="delete-booking button" data-id="<?php echo $booking->id; ?>" title="<?php esc_attr_e('Hapus Booking', 'archeus-booking'); ?>">
                                <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                <span class="screen-reader-text"><?php _e('Hapus', 'archeus-booking'); ?></span>
                            </button>
                        </td>
                    </tr>
                    <!-- Details row (initially hidden) -->
                    <tr class="booking-details-row" data-id="<?php echo $booking->id; ?>" style="display: none;">
                        <td colspan="8">
                            <div class="booking-details">
                                <h4><?php _e('Additional Information', 'archeus-booking'); ?></h4>
                                <p><?php _e('Tidak ada detail tambahan.', 'archeus-booking'); ?></p>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Settings page content
     */
    public function settings_page() {
        // Handle language settings
        $current_locale = get_locale();
        $supported_locales = array(
            'en_US' => 'English',
            'id_ID' => 'Bahasa Indonesia'
        );
        
        $message = '';
        
        // Save language settings if form was submitted
        if (isset($_POST['save_language_settings']) && wp_verify_nonce($_POST['language_settings_nonce'], 'save_language_settings')) {
            $selected_locale = sanitize_text_field($_POST['plugin_locale']);
            if (array_key_exists($selected_locale, $supported_locales)) {
                update_option('archeus_booking_locale', $selected_locale);
                $message = __('Pengaturan bahasa berhasil diperbarui. Silakan segarkan halaman untuk melihat perubahan.', 'archeus-booking');
            } else {
                $message = __('Bahasa yang dipilih tidak valid.', 'archeus-booking');
            }
        }
        
        $selected_locale = get_option('archeus_booking_locale', $current_locale);
        ?>
        <div class="wrap booking-admin-page">
            <h1><?php _e('Pengaturan', 'archeus-booking'); ?></h1>
            
            <?php if (!empty($message)): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>
            <div class="admin-card">

                <form method="post" action="" class="settings-form">
                    <?php wp_nonce_field('save_language_settings', 'language_settings_nonce'); ?>
                    
                    <h2><?php _e('Pengaturan Bahasa', 'archeus-booking'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Bahasa Plugin', 'archeus-booking'); ?></th>
                            <td>
                                <select name="plugin_locale" class="ab-select">
                                <?php foreach ($supported_locales as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($selected_locale, $code); ?>>
                                        <?php echo esc_html($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Pilih bahasa untuk antarmuka plugin pemesanan.', 'archeus-booking'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Submit', 'archeus-booking'), 'primary', 'save_language_settings'); ?>
            </form>
            </div>
            
            <h2><?php _e('About Archeus Booking System', 'archeus-booking'); ?></h2>
            <p><?php _e('Version: 1.0.2', 'archeus-booking'); ?></p>
            <p><?php _e('Developed by: Archeus Catalyst', 'archeus-booking'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Save booking settings
     */
    public function save_booking_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'archeus-booking'));
        }
        
        if (!isset($_POST['booking_settings_nonce']) || !wp_verify_nonce($_POST['booking_settings_nonce'], 'save_booking_settings')) {
            wp_die(__('Security check failed', 'archeus-booking'));
        }
        
        $fields = array();
        if (isset($_POST['field_keys'])) {
            foreach ($_POST['field_keys'] as $index => $field_key) {
                $label = sanitize_text_field($_POST['field_labels'][$field_key]);
                $type = sanitize_text_field($_POST['field_types'][$field_key]);
                $required = isset($_POST['field_required'][$field_key]) ? 1 : 0;
                
                $fields[$field_key] = array(
                    'label' => $label,
                    'type' => $type,
                    'required' => $required
                );
            }
        }
        
        update_option('booking_form_fields', $fields);
        
        // Save email settings
        if (isset($_POST['email_settings'])) {
            $email_settings = array(
                'enable_customer_confirmation' => isset($_POST['email_settings']['enable_customer_confirmation']) ? 1 : 0,
                'enable_admin_notification' => isset($_POST['email_settings']['enable_admin_notification']) ? 1 : 0,
                'customer_confirmation_subject' => sanitize_text_field($_POST['email_settings']['customer_confirmation_subject']),
                'customer_confirmation_body' => sanitize_textarea_field($_POST['email_settings']['customer_confirmation_body']),
                'admin_notification_subject' => sanitize_text_field($_POST['email_settings']['admin_notification_subject']),
                'admin_notification_body' => sanitize_textarea_field($_POST['email_settings']['admin_notification_body'])
            );
            
            update_option('booking_email_settings', $email_settings);
        }
        
        wp_redirect(add_query_arg(array('page' => 'archeus-booking-management', 'updated' => 'true'), admin_url('admin.php')));
        exit;
    }

    /**
     * Save form settings
     */
    public function save_form_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'archeus-booking'));
        }
        
        if (!isset($_POST['booking_settings_nonce']) || !wp_verify_nonce($_POST['booking_settings_nonce'], 'save_booking_settings')) {
            wp_die(__('Security check failed', 'archeus-booking'));
        }
        
        $fields = array();
        if (isset($_POST['field_keys'])) {
            foreach ($_POST['field_keys'] as $index => $field_key) {
                $label = sanitize_text_field($_POST['field_labels'][$field_key]);
                $type = sanitize_text_field($_POST['field_types'][$field_key]);
                $required = isset($_POST['field_required'][$field_key]) ? 1 : 0;
                
                $fields[$field_key] = array(
                    'label' => $label,
                    'type' => $type,
                    'required' => $required
                );
            }
        }
        
        update_option('booking_form_fields', $fields);

        // Redirect to clear form data and show success message
        wp_redirect(add_query_arg(array(
            'page' => 'archeus-booking-forms',
            'form_saved' => 'true'
        ), admin_url('admin.php')));
        exit;
    }

  
    /**
     * Add debug menu
     */
    public function add_debug_menu() {
        add_submenu_page(
            'archeus-booking-management',
            __('Email Debug Log', 'archeus-booking'),
            __('Email Debug', 'archeus-booking'),
            'manage_options',
            'archeus-booking-debug',
            array($this, 'debug_page')
        );
    }

    /**
     * Clear email logs
     */
    public function clear_email_logs() {
        check_ajax_referer('clear_email_logs_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'archeus-booking'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'archeus_email_logs';
        $wpdb->query("TRUNCATE TABLE $table_name");

        wp_send_json_success(array('message' => __('Email logs cleared successfully', 'archeus-booking')));
    }

    /**
     * Debug page
     */
    public function debug_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'archeus_email_logs';

        // Create table if not exists
        $this->create_email_logs_table();

        // Get logs
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100");

        ?>
        <div class="wrap">
            <h1 class="title-page"><?php _e('Email Debug Log', 'archeus-booking'); ?></h1>

            <div class="notice notice-info">
                <h4><?php _e('How to Debug Email Issues', 'archeus-booking'); ?></h4>
                <ol>
                    <li><?php _e('Open browser developer tools (F12) and go to Console tab', 'archeus-booking'); ?></li>
                    <li><?php _e('Try to approve/reject a booking', 'archeus-booking'); ?></li>
                    <li><?php _e('Look for "Archeus:" messages in console', 'archeus-booking'); ?></li>
                    <li><?php _e('Check this page for email activity logs', 'archeus-booking'); ?></li>
                    <li><?php _e('Test email configuration in Email Settings page', 'archeus-booking'); ?></li>
                </ol>
            </div>

            <div class="tablenav top">
                <div class="alignleft actions">
                    <button type="button" id="clear-logs-btn" class="button action">
                        <?php _e('Clear All Logs', 'archeus-booking'); ?>
                    </button>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'archeus-booking'); ?></th>
                        <th><?php _e('Booking ID', 'archeus-booking'); ?></th>
                        <th><?php _e('Status', 'archeus-booking'); ?></th>
                        <th><?php _e('Email', 'archeus-booking'); ?></th>
                        <th><?php _e('Result', 'archeus-booking'); ?></th>
                        <th><?php _e('Details', 'archeus-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)) : ?>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><?php echo $log->created_at; ?></td>
                                <td><?php echo $log->booking_id; ?></td>
                                <td><span class="status-<?php echo $log->status; ?>"><?php echo $log->status; ?></span></td>
                                <td><?php echo $log->email; ?></td>
                                <td>
                                    <?php if ($log->success) : ?>
                                        <span class="success"><?php _e('Success', 'archeus-booking'); ?></span>
                                    <?php else : ?>
                                        <span class="error"><?php _e('Failed', 'archeus-booking'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log->message); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6"><?php _e('No email logs found', 'archeus-booking'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#clear-logs-btn').click(function() {
                showDeleteConfirm('<?php _e('Are you sure you want to clear all email logs?', 'archeus-booking'); ?>', '', function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'clear_email_logs',
                            nonce: '<?php echo wp_create_nonce('clear_email_logs_nonce'); ?>'
                        },
                        success: function() {
                            location.reload();
                        }
                    });
                });
            });
        });
        </script>

        <style>
        .status-approved { color: #28a745; font-weight: bold; }
        .status-rejected { color: #dc3545; font-weight: bold; }
        .success { color: #28a745; }
        .error { color: #dc3545; }

        /* Auto-detected field styles */
        .auto-detected-key {
            background-color: #f8f9fa !important;
            border-color: #dee2e6 !important;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .form-field-row[data-auto-detected="true"] .remove-field {
            display: none !important;
        }

        .form-field-row[data-auto-detected="true"] {
            background-color: #f8f9fa;
        }

        .auto-detected-row {
            background-color: #f8f9fa !important;
        }
        </style>
        <?php
    }

    /**
     * Create email logs table
     */
    private function create_email_logs_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'archeus_email_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            booking_id int(11) NOT NULL,
            status varchar(20) NOT NULL,
            email varchar(255) NOT NULL,
            success tinyint(1) NOT NULL,
            message text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Log email activity to database
     */
    private function log_email_activity($booking_id, $status, $email, $success, $message = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'archeus_email_logs';

        $data = array(
            'booking_id' => $booking_id,
            'status' => $status,
            'email' => $email,
            'success' => $success ? 1 : 0,
            'message' => $message
        );

        $wpdb->insert($table_name, $data);
    }

    /**
     * Test email notification
     */
    public function test_email_notification() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'archeus-booking'));
        }

        if (!isset($_POST['test_email_nonce']) || !wp_verify_nonce($_POST['test_email_nonce'], 'test_email_notification')) {
            wp_die(__('Security check failed', 'archeus-booking'));
        }

        $test_email = sanitize_email($_POST['test_email']);
        if (!is_email($test_email)) {
            wp_die(__('Invalid email address', 'archeus-booking'));
        }

        $subject = __('Test Email Notification', 'archeus-booking');
        $message = '<html><body>';
        $message .= '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; padding: 20px;">';
        $message .= '<h2 style="color: #333;">' . __('Test Email Successful', 'archeus-booking') . '</h2>';
        $message .= '<p>' . __('If you receive this email, it means the email notification system is working correctly.', 'archeus-booking') . '</p>';
        $message .= '<p>' . __('This is a test email from your Archeus Booking plugin.', 'archeus-booking') . '</p>';
        $message .= '<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
        $message .= '<tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">' . __('Sent Time', 'archeus-booking') . '</td>';
        $message .= '<td style="padding: 8px; border: 1px solid #ddd;">' . current_time('mysql') . '</td></tr>';
        $message .= '<tr><td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">' . __('Site', 'archeus-booking') . '</td>';
        $message .= '<td style="padding: 8px; border: 1px solid #ddd;">' . get_bloginfo('name') . '</td></tr>';
        $message .= '</table>';
        $message .= '<p>' . __('Salam hormat,', 'archeus-booking') . '</p>';
        $message .= '<p><strong>' . get_bloginfo('name') . '</strong></p>';
        $message .= '</div></body></html>';

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Create email logs table if not exists
        $this->create_email_logs_table();

        $result = wp_mail($test_email, $subject, $message, $headers);

        if ($result) {
            $this->log_email_activity(0, 'test', $test_email, true, 'Test email sent successfully');
            wp_redirect(admin_url('admin.php?page=archeus-booking-email-settings&message=test_success'));
        } else {
            $error_info = 'Unknown error';
            global $phpmailer;
            if (isset($phpmailer) && is_object($phpmailer) && method_exists($phpmailer, 'ErrorInfo')) {
                $error_info = $phpmailer->ErrorInfo;
            }
            $this->log_email_activity(0, 'test', $test_email, false, 'Test email failed: ' . $error_info);
            wp_redirect(admin_url('admin.php?page=archeus-booking-email-settings&message=test_failed'));
        }
        exit;
    }

    
    /**
     * Handle booking status update via AJAX
     */
    public function handle_booking_status_update() {
        // Create email logs table if not exists
        $this->create_email_logs_table();

        // Log the function call
        $this->log_email_activity(0, 'debug', 'system', true, 'handle_booking_status_update called with POST data: ' . json_encode($_POST));

        // Verify nonce
        if (!isset($_POST['nonce']) || (!wp_verify_nonce($_POST['nonce'], 'booking_admin_nonce') && !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce'))) {
            $this->log_email_activity(0, 'debug', 'system', false, 'Security check failed');
            wp_die(__('Security check failed', 'archeus-booking'));
        }

        if (!current_user_can('manage_options')) {
            $this->log_email_activity(0, 'debug', 'system', false, 'Permission denied');
            wp_die(__('You do not have permission to perform this action', 'archeus-booking'));
        }

        $booking_id = intval($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);

        $this->log_email_activity($booking_id, 'debug', 'system', true, 'Processing status update to: ' . $status);

        $booking_db = new Booking_Database();
        $booking = $booking_db->get_booking($booking_id);

        if (!$booking) {
            error_log('Archeus Booking: Booking not found for ID: ' . $booking_id);
            wp_send_json_error(array(
                'message' => __('Booking not found', 'archeus-booking')
            ));
        }

        // Debug: Log booking data structure
        error_log('Archeus Booking: Booking data for ID ' . $booking_id . ': ' . json_encode($booking));

        // Get the old status before updating
        $old_status = $booking->status;
        
        $result = $booking_db->update_booking_status($booking_id, $status);

        if ($result) {
            // Handle schedule bookings count based on status change
            // Use the configurable blocking statuses from settings
            if ($booking->schedule_id) {
                // Get blocking statuses from settings (default: approved)
                $blocking_statuses = get_option('booking_blocking_statuses', array('approved', 'completed'));
                $was = in_array($old_status, $blocking_statuses, true);
                $now = in_array($status, $blocking_statuses, true);
                if ($was && !$now) {
                    // Leaving blocking state
                    $booking_db->update_schedule_bookings($booking->schedule_id, -1);
                } elseif (!$was && $now) {
                    // Entering blocking state
                    global $wpdb;
                    $schedule = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM " . $wpdb->prefix . "archeus_booking_schedules WHERE id = %d",
                        $booking->schedule_id
                    ));
                    if ($schedule && $schedule->current_bookings < $schedule->max_capacity) {
                        $booking_db->update_schedule_bookings($booking->schedule_id, 1);
                    } else {
                        // Revert update if over capacity
                        $booking_db->update_booking_status($booking_id, $old_status);
                        wp_send_json_error(array('message' => __('Tidak dapat mengubah status: kapasitas jadwal sudah penuh.', 'archeus-booking')));
                    }
                }
            }

            // Send notification email to customer for all status changes (if enabled in settings)
            if ($status !== $old_status) {
                error_log('Archeus Booking: Status changed from ' . $old_status . ' to ' . $status . ', sending email notification...');
                $this->send_status_change_notification($booking, $status);
            } else {
                error_log('Archeus Booking: Email not sent. Status: ' . $status . ', Old Status: ' . $old_status);
            }

            wp_send_json_success(array(
                'message' => __('Booking status updated successfully', 'archeus-booking')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to update booking status', 'archeus-booking')
            ));
        }
    }
    
    /**
     * Send notification email when booking status is changed
     */
    private function send_status_change_notification($booking, $new_status) {
        // Get email settings
        $email_settings = get_option('booking_email_settings', array());

        // Check if status change emails are enabled
        if (!isset($email_settings['enable_status_change_emails']) || !$email_settings['enable_status_change_emails']) {
            error_log('Archeus Booking: Status change emails are disabled in settings');
            return;
        }

        // Create email logs table if not exists
        $this->create_email_logs_table();

        // Check if customer email is available
        if (empty($booking->customer_email) || !is_email($booking->customer_email)) {
            // Try to find email in additional fields
            $email = $this->find_customer_email($booking);
            if (!$email) {
                $this->log_email_activity($booking->id, $new_status, 'not_found', false, 'No email address found in booking data');
                return; // Skip email sending if no email found
            }
            $to = $email;
        } else {
            $to = $booking->customer_email;
        }

        // Get email template from settings
        $subject_key = $new_status . '_email_subject';
        $body_key = $new_status . '_email_body';

        $subject_template = isset($email_settings[$subject_key]) ? $email_settings[$subject_key] : sprintf(__('Pembaruan Status Booking - %s', 'archeus-booking'), $new_status);
        $body_template = $this->get_email_content($email_settings, $body_key);

        // Check if body template is empty - if so, don't send email
        if (empty(trim($body_template))) {
            $this->log_email_activity($booking->id, $new_status, $to, false, 'Email not sent: no email content configured');
            return;
        }

        // Process subject template for tag replacement
        $subject = $this->build_status_email_subject($booking, $subject_template, $new_status);

        // Build email content using the existing build_custom_email_content function
        $message = $this->build_status_email_content($booking, $body_template, $new_status);
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Send email and log result
        $result = wp_mail($to, $subject, $message, $headers);

        if ($result) {
            $this->log_email_activity($booking->id, $new_status, $to, true, 'Email sent successfully');
        } else {
            $error_info = 'Unknown error';
            global $phpmailer;
            if (isset($phpmailer) && is_object($phpmailer) && method_exists($phpmailer, 'ErrorInfo')) {
                $error_info = $phpmailer->ErrorInfo;
            }
            // Additional error debugging
            if (function_exists('error_get_last')) {
                $last_error = error_get_last();
                if ($last_error) {
                    $error_info .= ' | PHP Error: ' . $last_error['message'];
                }
            }
            $this->log_email_activity($booking->id, $new_status, $to, false, 'Failed to send email: ' . $error_info);
        }
    }

    /**
     * Find customer email from booking data
     */
    private function find_customer_email($booking) {
        // First check if customer_email is already available
        if (!empty($booking->customer_email) && is_email($booking->customer_email)) {
            return $booking->customer_email;
        }

        // Check payload data
        if (!empty($booking->payload)) {
            $payload_data = json_decode($booking->payload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($payload_data)) {
                // Check common email field names
                $email_fields = array('customer_email', 'email', 'email_address', 'alamat_email', 'e-mail');
                foreach ($email_fields as $field) {
                    if (isset($payload_data[$field]) && !empty($payload_data[$field]) && is_email($payload_data[$field])) {
                        return $payload_data[$field];
                    }
                }

                // Also check all values in payload for email pattern
                foreach ($payload_data as $key => $value) {
                    if (is_string($value) && is_email($value)) {
                        return $value;
                    }
                }
            }
        }

        // Check additional fields if available
        if (!empty($booking->additional_fields)) {
            $additional_fields = maybe_unserialize($booking->additional_fields);
            if (is_array($additional_fields)) {
                foreach ($additional_fields as $key => $value) {
                    if (is_email($value)) {
                        return $value;
                    }
                }
            }
        }

        // Check dynamic fields for email
        foreach ($booking as $key => $value) {
            if (is_string($value) && is_email($value)) {
                return $value;
            }
        }

        return false;
    }
    
    
    /**
     * Build status email subject with tag replacement
     */
    private function build_status_email_subject($booking, $subject_template, $status) {
        // Prepare booking data for tag replacement
        $booking_data = array(
            'booking_id' => $booking->id,
            'customer_name' => !empty($booking->customer_name) ? $booking->customer_name : __('Pelanggan', 'archeus-booking'),
            'customer_email' => !empty($booking->customer_email) ? $booking->customer_email : '',
            'booking_date' => !empty($booking->booking_date) ? date('M j, Y', strtotime($booking->booking_date)) : '',
            'booking_time' => !empty($booking->booking_time) ? $this->format_time($booking->booking_time) : '',
            'service_type' => !empty($booking->service_type) ? $booking->service_type : '',
            'time_slot' => !empty($booking->booking_time) ? $booking->booking_time : '',
            'status' => $status,
            'company_name' => get_bloginfo('name'),
            'company_url' => get_bloginfo('url'),
            'admin_website' => admin_url(),
            'admin_email' => get_option('admin_email'),
            'current_date' => date('Y-m-d'),
            'current_time' => date('H:i:s'),
            'current_datetime' => date('Y-m-d H:i:s'),
            'email_title' => $this->get_status_email_title($status)
        );

        // Replace all available tags in the subject
        $subject = $subject_template;

        // Replace standard tags
        foreach ($booking_data as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value, $subject);
        }

        // Replace Indonesian language tags (aliases for English tags)
        $subject = str_replace('{nama_lengkap}', $booking_data['customer_name'], $subject);
        $subject = str_replace('{nama}', $booking_data['customer_name'], $subject);
        $subject = str_replace('{email_pelanggan}', $booking_data['customer_email'], $subject);
        $subject = str_replace('{alamat_email}', $booking_data['customer_email'], $subject);
        $subject = str_replace('{tanggal_reservasi}', $booking_data['booking_date'], $subject);
        $subject = str_replace('{waktu_reservasi}', $booking_data['booking_time'], $subject);
        $subject = str_replace('{layanan}', $booking_data['service_type'], $subject);
        $subject = str_replace('{jenis_layanan}', $booking_data['service_type'], $subject);
        $subject = str_replace('{slot_waktu}', $booking_data['time_slot'], $subject);
        $subject = str_replace('{nama_perusahaan}', $booking_data['company_name'], $subject);
        $subject = str_replace('{url_perusahaan}', $booking_data['company_url'], $subject);
        $subject = str_replace('{url_admin}', $booking_data['admin_website'], $subject);
        $subject = str_replace('{admin_website}', $booking_data['admin_website'], $subject);
        $subject = str_replace('{email_admin}', $booking_data['admin_email'], $subject);
        $subject = str_replace('{current_datetime}', $booking_data['current_datetime'], $subject);

        // Clean up any remaining tags (replace with empty string)
        $subject = preg_replace('/\{[^}]+\}/', '', $subject);

        return trim($subject);
    }

    /**
     * Build status email content using custom template from admin settings
     */
    private function build_status_email_content($booking, $template, $status) {
        // Prepare booking data for tag replacement
        $booking_data = array(
            'booking_id' => $booking->id,
            'customer_name' => !empty($booking->customer_name) ? $booking->customer_name : __('Pelanggan', 'archeus-booking'),
            'customer_email' => !empty($booking->customer_email) ? $booking->customer_email : '',
            'booking_date' => !empty($booking->booking_date) ? date('M j, Y', strtotime($booking->booking_date)) : '',
            'booking_time' => !empty($booking->booking_time) ? $this->format_time($booking->booking_time) : '',
            'service_type' => !empty($booking->service_type) ? $booking->service_type : '',
            'time_slot' => !empty($booking->booking_time) ? $booking->booking_time : '',
            'status' => $status,
            'company_name' => get_bloginfo('name'),
            'company_url' => get_bloginfo('url'),
            'admin_website' => admin_url(),
            'admin_email' => get_option('admin_email'),
            'current_date' => date('Y-m-d'),
            'current_time' => date('H:i:s'),
            'current_datetime' => date('Y-m-d H:i:s'),
            'email_title' => $this->get_status_email_title($status)
        );

        // Generate greeting based on customer name
        if (!empty($booking->customer_name)) {
            $booking_data['greeting'] = sprintf(__('Dear %s,', 'archeus-booking'), $booking->customer_name);
        } else {
            $booking_data['greeting'] = __('Dear Pelanggan,', 'archeus-booking');
        }

        // Replace all available tags in the template
        $message = $template;

        // Replace standard tags
        foreach ($booking_data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        // Replace Indonesian language tags (aliases for English tags)
        $message = str_replace('{nama_lengkap}', $booking_data['customer_name'], $message);
        $message = str_replace('{nama}', $booking_data['customer_name'], $message);
        $message = str_replace('{email_pelanggan}', $booking_data['customer_email'], $message);
        $message = str_replace('{alamat_email}', $booking_data['customer_email'], $message);
        $message = str_replace('{tanggal_reservasi}', $booking_data['booking_date'], $message);
        $message = str_replace('{waktu_reservasi}', $booking_data['booking_time'], $message);
        $message = str_replace('{layanan}', $booking_data['service_type'], $message);
        $message = str_replace('{jenis_layanan}', $booking_data['service_type'], $message);
        $message = str_replace('{slot_waktu}', $booking_data['time_slot'], $message);
        $message = str_replace('{nama_perusahaan}', $booking_data['company_name'], $message);
        $message = str_replace('{url_perusahaan}', $booking_data['company_url'], $message);
        $message = str_replace('{url_admin}', $booking_data['admin_website'], $message);
        $message = str_replace('{admin_website}', $booking_data['admin_website'], $message);
        $message = str_replace('{email_admin}', $booking_data['admin_email'], $message);
        $message = str_replace('{current_datetime}', $booking_data['current_datetime'], $message);

        // Auto-wrap HTML if not present
        if (strpos($message, '<html') === false) {
            $message = $this->wrap_email_template($message, 'customer');
        }

        return $message;
    }

    /**
     * Get default status email template as fallback
     */
    private function get_default_status_email_template($status) {
        $templates = array(
            'pending' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #54b335;">Reservasi Sedang Diproses</h2>
        <p>{greeting}</p>
        <p>Terima kasih telah melakukan reservasi dengan kami. Reservasi Anda sedang dalam proses peninjauan.</p>
        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #54b335;">Detail Reservasi</h3>
            <p><strong>ID Reservasi:</strong> {booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time}</p>
        </div>
        <p>Kami akan segera menghubungi Anda untuk mengkonfirmasi reservasi ini.</p>
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">Hormat kami,<br>{company_name}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Email ini dikirim pada {current_date} pukul {current_time}
            </p>
        </div>
    </div>
</body>
</html>', 'archeus-booking'),
            'approved' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #27ae60;">Reservasi Diterima!</h2>
        <p>{greeting}</p>
        <p>Selamat! Reservasi Anda telah <strong>DISETUJUI</strong>. Kami sangat menantikan kedatangan Anda.</p>
        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #27ae60;">Detail Reservasi</h3>
            <p><strong>ID Reservasi:</strong> {booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time}</p>
        </div>
        <p>Jika ada perubahan jadwal, kami akan menghubungi Anda segera.</p>
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">Hormat kami,<br>{company_name}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Email ini dikirim pada {current_date} pukul {current_time}
            </p>
        </div>
    </div>
</body>
</html>', 'archeus-booking'),
            'rejected' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #e74c3c;">Reservasi Ditolak</h2>
        <p>{greeting}</p>
        <p>Maaf, reservasi Anda telah <strong>DITOLAK</strong>. Jika Anda memiliki pertanyaan, silakan hubungi kami.</p>
        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #e74c3c;">Detail Reservasi</h3>
            <p><strong>ID Reservasi:</strong> {booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time}</p>
        </div>
        <p>Anda dapat melakukan reservasi kembali dengan jadwal yang berbeda jika tersedia.</p>
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">Hormat kami,<br>{company_name}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Email ini dikirim pada {current_date} pukul {current_time}
            </p>
        </div>
    </div>
</body>
</html>', 'archeus-booking'),
            'completed' => __('<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #27ae60;">Reservasi Selesai</h2>
        <p>{greeting}</p>
        <p>Reservasi Anda telah ditandai sebagai selesai. Terima kasih telah menggunakan layanan kami!</p>
        <div style="background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #27ae60;">Detail Reservasi</h3>
            <p><strong>ID Reservasi:</strong> {booking_id}</p>
            <p><strong>Layanan:</strong> {service_type}</p>
            <p><strong>Tanggal:</strong> {booking_date}</p>
            <p><strong>Waktu:</strong> {booking_time}</p>
        </div>
        <p>Kami berharap Anda puas dengan layanan kami. Jangan ragu untuk melakukan reservasi kembali.</p>
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p style="margin: 0; color: #666;">Hormat kami,<br>{company_name}</p>
            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                Email ini dikirim pada {current_date} pukul {current_time}
            </p>
        </div>
    </div>
</body>
</html>', 'archeus-booking')
        );

        return isset($templates[$status]) ? $templates[$status] : $templates['pending'];
    }

    /**
     * Get status email title
     */
    private function get_status_email_title($status) {
        $titles = array(
            'pending' => __('Reservasi Sedang Diproses', 'archeus-booking'),
            'approved' => __('Reservasi Diterima', 'archeus-booking'),
            'rejected' => __('Reservasi Ditolak', 'archeus-booking'),
            'completed' => __('Reservasi Selesai', 'archeus-booking')
        );

        return isset($titles[$status]) ? $titles[$status] : __('Pembaruan Status Reservasi', 'archeus-booking');
    }

    /**
     * Wrap email template with HTML structure
     */
    private function wrap_email_template($content, $recipient_type) {
        $html = '<html><body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">';
        $html .= '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">';
        $html .= $content;
        $html .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">';
        $html .= '<p style="margin: 0; color: #666;">' . __('Hormat kami,', 'archeus-booking') . '<br><strong>' . get_bloginfo('name') . '</strong></p>';
        $html .= '<p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">';
        $html .= __('Email ini dikirim pada', 'archeus-booking') . ' ' . date('Y-m-d') . ' ' . __('pukul', 'archeus-booking') . ' ' . date('H:i:s');
        $html .= '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Format time for display
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
                return $time_value;
        }
    }

    /**
     * Handle getting bookings via AJAX
     */
    public function handle_get_bookings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || (!wp_verify_nonce($_POST['nonce'], 'booking_admin_nonce') && !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce'))) {
            wp_die(__('Security check failed', 'archeus-booking'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action', 'archeus-booking'));
        }

        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;

        $booking_db = new Booking_Database();
        $flow_id = isset($_POST['flow_id']) ? intval($_POST['flow_id']) : 0;
        $args = array('status' => $status, 'limit' => $limit);
        if ($flow_id) { $args['flow_id'] = $flow_id; }
        $bookings = $booking_db->get_bookings($args);

        // Process bookings array for table output
        $processed_bookings = array();
        foreach ($bookings as $booking) {
            $booking_array = (array) $booking;
            
            // Unserialize additional fields if they exist
            // No additional_fields serialization
            
            $processed_bookings[] = $booking_array;
        }

        // Aggregate stats for selected flow (ignoring status filter)
        $stats = method_exists($booking_db, 'get_booking_counts') ? $booking_db->get_booking_counts($flow_id) : array();

        wp_send_json_success(array(
            'bookings' => $processed_bookings,
            'stats' => $stats
        ));
    }

    /**
     * Return full booking details (all columns) for a given booking ID.
     */
    public function handle_get_booking_details() {
        if (!isset($_POST['nonce']) || (!wp_verify_nonce($_POST['nonce'], 'booking_admin_nonce') && !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce'))) {
            wp_send_json_error(array('message' => __('Security check failed', 'archeus-booking')));
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action', 'archeus-booking')));
        }
        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        if (!$booking_id) { wp_send_json_error(array('message' => __('Invalid booking ID', 'archeus-booking'))); }
        $db = new Booking_Database();
        $row = $db->get_booking($booking_id);
        if (!$row) { wp_send_json_error(array('message' => __('Booking not found', 'archeus-booking'))); }
        $data = (array)$row;

        // Extract customer data from payload if not already in database fields
        if (!empty($data['payload'])) {
            $payload_data = json_decode($data['payload'], true);
            if (is_array($payload_data)) {
                // Try to get customer_name from payload if not in database fields
                if (empty($data['customer_name'])) {
                    $name_fields = array('nama_lengkap', 'nama', 'name', 'full_name', 'customer_name', 'nama lengkap', 'full name');
                    foreach ($name_fields as $name_field) {
                        if (isset($payload_data[$name_field]) && !empty($payload_data[$name_field])) {
                            $data['customer_name'] = sanitize_text_field($payload_data[$name_field]);
                            break;
                        }
                    }
                }

                // Try to get customer_email from payload if not in database fields
                if (empty($data['customer_email'])) {
                    $email_fields = array('email', 'customer_email', 'email_address', 'alamat_email', 'e-mail');
                    foreach ($email_fields as $email_field) {
                        if (isset($payload_data[$email_field]) && !empty($payload_data[$email_field])) {
                            $data['customer_email'] = sanitize_email($payload_data[$email_field]);
                            break;
                        }
                    }
                }
            }
        }

        // Remove heavy/internal fields
        unset($data['payload']);
        wp_send_json_success($data);
    }
    
    /**
     * Handle booking deletion via AJAX
     */
    public function handle_booking_deletion() {
        // Verify nonce (accept legacy and current nonces)
        if (!isset($_POST['nonce']) || (!wp_verify_nonce($_POST['nonce'], 'booking_admin_nonce') && !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce'))) {
            wp_die(__('Security check failed', 'archeus-booking'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action', 'archeus-booking'));
        }

        $booking_id = intval($_POST['booking_id']);

        $booking_db = new Booking_Database();
        
        // Get the booking before deleting to access its schedule_id
        $booking = $booking_db->get_booking($booking_id);
        
        $result = $booking_db->delete_booking($booking_id);

        if ($result) {
            // If booking had a schedule, reduce the schedule bookings count
            if ($booking && !empty($booking->schedule_id)) {
                $booking_db->update_schedule_bookings($booking->schedule_id, -1);
            }
            
            wp_send_json_success(array(
                'message' => __('Booking deleted successfully', 'archeus-booking')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete booking', 'archeus-booking')
            ));
        }
    }

    /**
     * Handle form deletion via AJAX
     */
    public function handle_form_deletion() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'archeus-booking')
            ));
        }

        $form_id = intval($_POST['form_id']);

        $booking_db = new Booking_Database();
        $result = $booking_db->delete_form($form_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Formulir berhasil dihapus.', 'archeus-booking')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Gagal menghapus formulir.', 'archeus-booking')
            ));
        }
    }

    /**
     * Handle service deletion via AJAX
     */
    public function handle_service_deletion() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'archeus-booking')
            ));
        }

        $service_id = intval($_POST['service_id']);

        $booking_db = new Booking_Database();
        $result = $booking_db->delete_service($service_id);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Service deleted successfully.', 'archeus-booking')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error deleting service.', 'archeus-booking')
            ));
        }
    }

    /**
     * Handle time slot deletion via AJAX
     */
    public function handle_time_slot_deletion() {
        error_log('handle_time_slot_deletion called with POST data: ' . print_r($_POST, true));

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce')) {
            error_log('Nonce verification failed for time slot deletion');
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        if (!current_user_can('manage_options')) {
            error_log('Permission denied for time slot deletion');
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'archeus-booking')
            ));
        }

        $slot_id = intval($_POST['slot_id']);
        error_log('Processing deletion for time slot ID: ' . $slot_id);

        // Use direct database operations instead of class method to avoid potential loading issues
        global $wpdb;
        $table_name = $wpdb->prefix . 'archeus_booking_time_slots';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            error_log('Time slots table does not exist: ' . $table_name);
            wp_send_json_error(array('message' => 'Tabel time slots tidak ditemukan. Silakan aktivasi ulang plugin.'));
        }

        // Check if the slot exists
        $existing_slot = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $slot_id
        ));

        if (!$existing_slot) {
            error_log('Time slot not found for deletion: ' . $slot_id);
            wp_send_json_error(array('message' => 'Slot waktu tidak ditemukan.'));
        }

        // Delete the time slot
        error_log('Attempting to delete time slot ID: ' . $slot_id);

        $result = $wpdb->delete(
            $table_name,
            array('id' => $slot_id),
            array('%d')
        );

        // Check for database errors
        if ($result === false) {
            error_log('Database delete failed: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Gagal menghapus data: ' . $wpdb->last_error));
        }

        error_log('Time slot deleted successfully: ' . $slot_id);

        wp_send_json_success(array(
            'message' => 'Slot waktu berhasil dihapus.',
            'slot_id' => $slot_id
        ));
    }

    /**
     * Handle flow deletion via AJAX
     */
    public function handle_flow_deletion() {
        error_log('handle_flow_deletion called with POST data: ' . print_r($_POST, true));

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce')) {
            error_log('Nonce verification failed for flow deletion');
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        if (!current_user_can('manage_options')) {
            error_log('Permission denied for flow deletion');
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'archeus-booking')
            ));
        }

        $flow_id = intval($_POST['flow_id']);
        error_log('Processing deletion for flow ID: ' . $flow_id);

        // Use direct database operations to avoid potential loading issues
        global $wpdb;
        $table_name = $wpdb->prefix . 'archeus_booking_flows';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            error_log('Flows table does not exist: ' . $table_name);
            wp_send_json_error(array('message' => 'Tabel flows tidak ditemukan. Silakan aktivasi ulang plugin.'));
        }

        // Check if the flow exists
        $existing_flow = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $flow_id
        ));

        if (!$existing_flow) {
            error_log('Flow not found for deletion: ' . $flow_id);
            wp_send_json_error(array('message' => 'Booking flow tidak ditemukan.'));
        }

        // Delete the flow
        error_log('Attempting to delete flow ID: ' . $flow_id);

        $result = $wpdb->delete(
            $table_name,
            array('id' => $flow_id),
            array('%d')
        );

        // Check for database errors
        if ($result === false) {
            error_log('Database delete failed: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Gagal menghapus data: ' . $wpdb->last_error));
        }

        error_log('Flow deleted successfully: ' . $flow_id);

        wp_send_json_success(array(
            'message' => 'Booking flow berhasil dihapus.',
            'flow_id' => $flow_id
        ));
    }

    /**
     * Handle form creation via AJAX
     */
    public function handle_form_creation() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'archeus-booking')
            ));
        }

        $name = sanitize_text_field($_POST['form_name']);
        $description = sanitize_textarea_field($_POST['form_description']);

        // Process fields
        $fields = array();
        if (isset($_POST['field_keys']) && is_array($_POST['field_keys'])) {
            foreach ($_POST['field_keys'] as $index => $key) {
                if (empty($key)) continue;

                $label = isset($_POST['field_labels'][$key]) ? sanitize_text_field($_POST['field_labels'][$key]) : $key;
                $type = isset($_POST['field_types'][$key]) ? sanitize_text_field($_POST['field_types'][$key]) : 'text';
                $required = isset($_POST['field_required'][$key]) ? 1 : 0;
                $placeholder = isset($_POST['field_placeholders'][$key]) ? sanitize_text_field($_POST['field_placeholders'][$key]) : '';

                $options = array();
                if ($type === 'select' && isset($_POST['field_options'][$key])) {
                    $raw = wp_unslash($_POST['field_options'][$key]);
                    $lines = preg_split('/\r\n|\r|\n/', (string)$raw);
                    foreach ($lines as $line) {
                        $opt = trim($line);
                        if ($opt !== '') { $options[] = $opt; }
                    }
                }

                $fields[$key] = array(
                    'label' => $label,
                    'type' => $type,
                    'required' => $required,
                    'placeholder' => $placeholder,
                    'options' => $options
                );
            }
        }

        $booking_db = new Booking_Database();
        $auto_slug = 'form-' . uniqid();
        $result = $booking_db->create_form($name, $auto_slug, $description, $fields);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Formulir berhasil dibuat.', 'archeus-booking'),
                'form_id' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Gagal membuat formulir.', 'archeus-booking')
            ));
        }
    }

    /**
     * Handle form update via AJAX
     */
    public function handle_form_update() {
        // Debug log incoming data
        error_log('Form Update Handler Called');
        error_log('POST data: ' . print_r($_POST, true));

        // Verify nonce - try both nonces
        $nonce_valid = false;
        if (isset($_POST['nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce');
        }
        if (!$nonce_valid && isset($_POST['booking_forms_nonce'])) {
            $nonce_valid = wp_verify_nonce($_POST['booking_forms_nonce'], 'save_booking_forms');
        }

        if (!$nonce_valid) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'archeus-booking')
            ));
        }

        $form_id = intval($_POST['form_id']);
        $name = sanitize_text_field($_POST['form_name']);
        $slug = isset($_POST['form_slug']) ? sanitize_title($_POST['form_slug']) : '';
        $description = sanitize_textarea_field($_POST['form_description']);

        // Process fields
        $fields = array();
        if (isset($_POST['field_keys']) && is_array($_POST['field_keys'])) {
            foreach ($_POST['field_keys'] as $index => $key) {
                if (empty($key)) continue;

                $label = isset($_POST['field_labels'][$key]) ? sanitize_text_field($_POST['field_labels'][$key]) : $key;
                $type = isset($_POST['field_types'][$key]) ? sanitize_text_field($_POST['field_types'][$key]) : 'text';
                $required = isset($_POST['field_required'][$key]) ? 1 : 0;
                $placeholder = isset($_POST['field_placeholders'][$key]) ? sanitize_text_field($_POST['field_placeholders'][$key]) : '';

                $options = array();
                if ($type === 'select' && isset($_POST['field_options'][$key])) {
                    $raw = wp_unslash($_POST['field_options'][$key]);
                    $lines = preg_split('/\r\n|\r|\n/', (string)$raw);
                    foreach ($lines as $line) {
                        $opt = trim($line);
                        if ($opt !== '') { $options[] = $opt; }
                    }
                }

                $fields[$key] = array(
                    'label' => $label,
                    'type' => $type,
                    'required' => $required,
                    'placeholder' => $placeholder,
                    'options' => $options
                );
            }
        }

        $booking_db = new Booking_Database();
        $slug_to_use = !empty($slug) ? $slug : 'form-' . uniqid();
        $result = $booking_db->update_form($form_id, $name, $slug_to_use, $description, $fields);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Formulir berhasil diperbarui.', 'archeus-booking')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Gagal memperbarui formulir.', 'archeus-booking')
            ));
        }
    }

    /**
     * Handle time slot creation via AJAX
     */
    public function handle_time_slot_creation() {
        global $wpdb;

        error_log('Time slot creation handler called');

        // Check basic requirements
        if (!isset($_POST['nonce'])) {
            error_log('Nonce not provided');
            wp_send_json_error(array('message' => 'Nonce not provided'));
        }

        if (!wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce')) {
            error_log('Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        if (!current_user_can('manage_options')) {
            error_log('User does not have permission');
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        // Get and sanitize input data
        $time_label = sanitize_text_field($_POST['time_label']);
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = sanitize_text_field($_POST['end_time']);
        $max_capacity = intval($_POST['max_capacity']);
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

        // Log received data
        error_log('Received data: ' . print_r($_POST, true));

        // Validate required fields
        if (empty($time_label) || empty($start_time) || empty($end_time)) {
            error_log('Missing required fields');
            wp_send_json_error(array('message' => 'Semua field wajib diisi'));
        }

        // Validate time format and convert to HH:MM:SS
        $time_pattern_hhmm = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/';
        $time_pattern_hhmmss = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';

        if (preg_match($time_pattern_hhmm, $start_time)) {
            $start_time .= ':00';
        }

        if (preg_match($time_pattern_hhmm, $end_time)) {
            $end_time .= ':00';
        }

        if (!preg_match($time_pattern_hhmmss, $start_time) || !preg_match($time_pattern_hhmmss, $end_time)) {
            error_log('Invalid time format: ' . $start_time . ' - ' . $end_time);
            wp_send_json_error(array('message' => 'Format waktu tidak valid. Gunakan format JJ:MM.'));
        }

        // Validate time range
        $start_timestamp = strtotime('1970-01-01 ' . $start_time);
        $end_timestamp = strtotime('1970-01-01 ' . $end_time);

        if ($end_timestamp <= $start_timestamp) {
            error_log('Invalid time range: ' . $start_time . ' - ' . $end_time);
            wp_send_json_error(array('message' => 'Waktu selesai harus setelah waktu mulai.'));
        }

        // Define table name
        $table_name = $wpdb->prefix . 'archeus_booking_time_slots';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            error_log('Time slots table does not exist: ' . $table_name);
            wp_send_json_error(array('message' => 'Tabel time slots tidak ditemukan. Silakan aktivasi ulang plugin.'));
        }

        // Check for duplicate time slots
        $existing_slot = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE start_time = %s AND end_time = %s",
            $start_time,
            $end_time
        ));

        if ($existing_slot) {
            error_log('Duplicate time slot found: ' . $start_time . ' - ' . $end_time);
            wp_send_json_error(array('message' => 'Slot waktu dengan rentang waktu yang sama sudah ada.'));
        }

        // Insert new time slot
        error_log('Attempting to insert time slot: ' . $time_label . ' (' . $start_time . ' - ' . $end_time . ')');

        $result = $wpdb->insert(
            $table_name,
            array(
                'time_label' => $time_label,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'max_capacity' => $max_capacity,
                'is_active' => $is_active
            ),
            array('%s', '%s', '%s', '%d', '%d')
        );

        // Check for database errors
        if ($result === false) {
            error_log('Database insert failed: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Gagal menyimpan data ke database: ' . $wpdb->last_error));
        }

        $slot_id = $wpdb->insert_id;
        error_log('Time slot created successfully with ID: ' . $slot_id);

        wp_send_json_success(array(
            'message' => 'Slot waktu berhasil dibuat.',
            'slot_id' => $slot_id
        ));
    }

    /**
     * Handle time slot update via AJAX
     */
    public function handle_time_slot_update() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'archeus-booking')
            ));
        }

        $slot_id = intval($_POST['slot_id']);
        $time_label = sanitize_text_field($_POST['time_label']);
        $start_time = sanitize_text_field($_POST['start_time']);
        $end_time = sanitize_text_field($_POST['end_time']);
        $max_capacity = intval($_POST['max_capacity']);
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
        $sort_order = 0; // Not used anymore, but kept for parameter compatibility

        // Validate time format - accept both HH:MM and HH:MM:SS formats
        $time_pattern_hhmm = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/';
        $time_pattern_hhmmss = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';

        // If time is in HH:MM format, convert to HH:MM:SS format
        if (preg_match($time_pattern_hhmm, $start_time)) {
            $start_time .= ':00';
        }

        if (preg_match($time_pattern_hhmm, $end_time)) {
            $end_time .= ':00';
        }

        // Validate the final format
        if (!preg_match($time_pattern_hhmmss, $start_time) || !preg_match($time_pattern_hhmmss, $end_time)) {
            wp_send_json_error(array(
                'message' => __('Format waktu tidak valid. Gunakan format JJ:MM.', 'archeus-booking')
            ));
        }

        // Validate that end time is after start time
        $start_timestamp = strtotime('1970-01-01 ' . $start_time);
        $end_timestamp = strtotime('1970-01-01 ' . $end_time);

        if ($end_timestamp <= $start_timestamp) {
            wp_send_json_error(array(
                'message' => __('Waktu selesai harus setelah waktu mulai.', 'archeus-booking')
            ));
        }

        // Use direct database operations instead of class method to avoid potential loading issues
        global $wpdb;
        $table_name = $wpdb->prefix . 'archeus_booking_time_slots';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            error_log('Time slots table does not exist: ' . $table_name);
            wp_send_json_error(array('message' => 'Tabel time slots tidak ditemukan. Silakan aktivasi ulang plugin.'));
        }

        // Check if the slot exists
        $existing_slot = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE id = %d",
            $slot_id
        ));

        if (!$existing_slot) {
            error_log('Time slot not found for update: ' . $slot_id);
            wp_send_json_error(array('message' => 'Slot waktu tidak ditemukan.'));
        }

        // Check for duplicate time slots (excluding current slot)
        $duplicate_slot = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE start_time = %s AND end_time = %s AND id != %d",
            $start_time,
            $end_time,
            $slot_id
        ));

        if ($duplicate_slot) {
            error_log('Duplicate time slot found for update: ' . $start_time . ' - ' . $end_time);
            wp_send_json_error(array('message' => 'Slot waktu dengan rentang waktu yang sama sudah ada.'));
        }

        // Update the time slot
        error_log('Attempting to update time slot ID ' . $slot_id . ': ' . $time_label . ' (' . $start_time . ' - ' . $end_time . ')');

        $result = $wpdb->update(
            $table_name,
            array(
                'time_label' => $time_label,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'max_capacity' => $max_capacity,
                'is_active' => $is_active,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $slot_id),
            array('%s', '%s', '%s', '%d', '%d', '%s'),
            array('%d')
        );

        // Check for database errors
        if ($result === false) {
            error_log('Database update failed: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => 'Gagal memperbarui data: ' . $wpdb->last_error));
        }

        error_log('Time slot updated successfully: ' . $slot_id);

        wp_send_json_success(array(
            'message' => 'Slot waktu berhasil diperbarui.',
            'slot_id' => $slot_id
        ));
    }

    /**
     * Handle service creation via AJAX
     */
    public function handle_service_creation() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'archeus-booking')
            ));
        }

        $name = sanitize_text_field($_POST['service_name']);
        $description = sanitize_textarea_field($_POST['service_description']);
        $price = floatval($_POST['service_price']);
        $duration = intval($_POST['service_duration']);
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

        $booking_db = new Booking_Database();
        $result = $booking_db->create_service($name, $description, $price, $duration, $is_active);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Service created successfully.', 'archeus-booking'),
                'service_id' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error creating service.', 'archeus-booking')
            ));
        }
    }

    /**
     * Handle service update via AJAX
     */
    public function handle_service_update() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'archeus_booking_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'archeus-booking')
            ));
        }

        $service_id = intval($_POST['service_id']);
        $name = sanitize_text_field($_POST['service_name']);
        $description = sanitize_textarea_field($_POST['service_description']);
        $price = floatval($_POST['service_price']);
        $duration = intval($_POST['service_duration']);
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

        $booking_db = new Booking_Database();
        $result = $booking_db->update_service($service_id, $name, $description, $price, $duration, $is_active);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Service updated successfully.', 'archeus-booking')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error updating service.', 'archeus-booking')
            ));
        }
    }

    /**
     * Add calendar menu
     */

    
    /**
     * Calendar page content
     */
    public function calendar_page() {
        $booking_calendar = new Booking_Calendar();
        
        // Handle form submission
        if (isset($_POST['save_availability']) && wp_verify_nonce($_POST['calendar_nonce'], 'save_calendar_availability')) {
            $date = sanitize_text_field($_POST['calendar_date']);
            $status = sanitize_text_field($_POST['availability_status']);
            $limit = intval($_POST['daily_limit']);
            
            $result = $booking_calendar->set_availability($date, $status, $limit);
            
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Availability updated successfully.', 'archeus-booking') . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error updating availability.', 'archeus-booking') . '</p></div>';
            }
        }
        
        // Handle batch updates
        if (isset($_POST['batch_update']) && wp_verify_nonce($_POST['batch_calendar_nonce'], 'batch_calendar_availability')) {
            $start_date = sanitize_text_field($_POST['batch_start_date']);
            $end_date = sanitize_text_field($_POST['batch_end_date']);
            $status = sanitize_text_field($_POST['batch_availability_status']);
            $limit = intval($_POST['batch_daily_limit']);
            
            $booking_calendar->batch_set_availability($start_date, $end_date, $status, $limit);
            
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Batch availability updated successfully.', 'archeus-booking') . '</p></div>';
        }
        
        // Handle calendar display settings
        if (isset($_POST['save_calendar_settings']) && wp_verify_nonce($_POST['calendar_settings_nonce'], 'save_calendar_settings')) {
            $max_months = intval($_POST['max_calendar_months']);
            $booking_calendar->set_max_months_display($max_months);
            // Save blocking statuses option
            $allowed = array('pending','approved','completed','rejected');
            $blocking = isset($_POST['blocking_statuses']) && is_array($_POST['blocking_statuses']) ? array_values(array_intersect($allowed, array_map('sanitize_text_field', $_POST['blocking_statuses']))) : array();
            if (empty($blocking)) { $blocking = array('approved', 'completed'); }
            update_option('booking_blocking_statuses', $blocking);
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Calendar settings updated successfully.', 'archeus-booking') . '</p></div>';
        }
        
        // Enqueue calendar assets for admin
        wp_enqueue_style('booking-calendar-css', ARCHEUS_BOOKING_URL . 'assets/css/calendar.css', array(), ARCHEUS_BOOKING_VERSION);
        wp_enqueue_script('booking-calendar-js', ARCHEUS_BOOKING_URL . 'assets/js/calendar.js', array('jquery'), ARCHEUS_BOOKING_VERSION, true);
        
        $booking_calendar_public = new Booking_Calendar_Public();
        wp_localize_script('booking-calendar-js', 'calendar_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('calendar_nonce'),
            'max_months' => $booking_calendar->get_max_months_display()
        ));
        ?>
        <div class="wrap booking-admin-page">
            <h1 class="title-page"><?php _e('Calendar Management (Kelola Kalendar)', 'archeus-booking'); ?></h1>
            
            <div class="calendar-availability-container">
                <!-- Calendar View -->
                <!-- <div class="calendar-view admin-card"> -->
                    <div class="notice notice-info ab-callout is-dismissible" style="padding:0;border-left-width:0;">
                        <div class="ab-callout-inner">
                            <div class="ab-callout-icon" aria-hidden="true">
                                <span class="dashicons dashicons-calendar"></span>
                            </div>
                            <div class="ab-callout-body">
                                <h3 class="ab-callout-title"><?php echo esc_html__('Tampilkan di Sisi Pengguna', 'archeus-booking'); ?></h3>
                                <p class="ab-callout-desc"><?php echo esc_html__('Gunakan shortcode berikut untuk menampilkan kalender kepada pengunjung (untuk alur reservasi gunakan shortcode pada Booking Flow).', 'archeus-booking'); ?></p>

                                <div class="ab-shortcode-row">
                                    <code class="ab-shortcode-code" id="ab-sc-calendar">[archeus_booking_calendar]</code>
                                    <button type="button" class="button ab-copy-btn" id="ab-copy-calendar" data-copy="[archeus_booking_calendar]" aria-label="<?php echo esc_attr__('Salin shortcode', 'archeus-booking'); ?>">
                                        <span class="dashicons dashicons-clipboard"></span><span><?php echo esc_html__('Salin', 'archeus-booking'); ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <!-- </div> -->
                    
                    <!-- Admin Calendar View - now using same structure as public calendar -->
                    <div class="booking-calendar-container">
                        <div class="booking-calendar-header">
                            <button class="calendar-nav-btn prev-month" data-month="<?php echo date('n')-1; ?>" data-year="<?php echo date('Y'); ?>">&laquo; <?php echo esc_html__('Sebelumnya', 'archeus-booking'); ?></button>
                            <h3 class="current-month"><?php $__m = intval(date('n')); $__y = date('Y'); $__names = array('Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'); echo esc_html($__names[$__m-1] . ' ' . $__y); ?></h3>
                            <button class="calendar-nav-btn next-month" data-month="<?php echo date('n')+1; ?>" data-year="<?php echo date('Y'); ?>"><?php echo esc_html__('Berikutnya', 'archeus-booking'); ?> &raquo;</button>
                        </div>
                        
                        <div class="booking-calendar">
                            <div class="calendar-weekdays">
                                <div><?php echo esc_html__('Min', 'archeus-booking'); ?></div>
                                <div><?php echo esc_html__('Sen', 'archeus-booking'); ?></div>
                                <div><?php echo esc_html__('Sel', 'archeus-booking'); ?></div>
                                <div><?php echo esc_html__('Rab', 'archeus-booking'); ?></div>
                                <div><?php echo esc_html__('Kam', 'archeus-booking'); ?></div>
                                <div><?php echo esc_html__('Jum', 'archeus-booking'); ?></div>
                                <div><?php echo esc_html__('Sab', 'archeus-booking'); ?></div>
                            </div>
                            <div class="calendar-days">
                                <?php echo $this->generate_admin_calendar(date('n'), date('Y')); ?>
                            </div>
                        </div>
                        
                        <div class="calendar-legend">
                            <h4><?php echo esc_html__('Keterangan', 'archeus-booking'); ?></h4>
                            <ul>
                                <li><span class="legend-color available"></span> <?php _e('Tersedia', 'archeus-booking'); ?></li>
                                <li><span class="legend-color unavailable"></span> <?php _e('Tidak Tersedia', 'archeus-booking'); ?></li>
                                <li><span class="legend-color holiday"></span> <?php _e('Libur', 'archeus-booking'); ?></li>
                                <li><span class="legend-color full"></span> <?php _e('Penuh', 'archeus-booking'); ?></li>
                                <li><span class="legend-color limited"></span> <?php _e('Tersedia Terbatas', 'archeus-booking'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- Individual Date Setting -->
                <div class="calendar-availability-form admin-card">
                    <h2><?php _e('Atur Ketersediaan Tanggal', 'archeus-booking'); ?></h2>
                    <form method="post" class="settings-form">
                        <?php wp_nonce_field('save_calendar_availability', 'calendar_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Tanggal', 'archeus-booking'); ?></th>
                                <td>
                                    <input type="date" name="calendar_date" value="<?php echo esc_attr(date('Y-m-d')); ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Status Ketersediaan', 'archeus-booking'); ?></th>
                                <td>
                                    <select name="availability_status" class="ab-select">
                                        <option value="available"><?php _e('Tersedia', 'archeus-booking'); ?></option>
                                        <option value="unavailable"><?php _e('Tidak Tersedia', 'archeus-booking'); ?></option>
                                        <option value="holiday"><?php _e('Libur', 'archeus-booking'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Batas Harian', 'archeus-booking'); ?></th>
                                <td>
                                    <input type="number" name="daily_limit" value="3" min="0">
                                    <p class="description"><?php _e('Jumlah maksimum pemesanan yang diizinkan untuk tanggal ini (ditetapkan ke 0 jika tidak ada batasan)', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Submit', 'archeus-booking'), 'primary', 'save_availability'); ?>
                    </form>
                </div>
                
                <!-- Batch Update Section -->
                <div class="calendar-batch-form admin-card">
                    <h2><?php _e('Perbarui Ketersediaan Massal', 'archeus-booking'); ?></h2>
                    <form method="post" class="settings-form">
                        <?php wp_nonce_field('batch_calendar_availability', 'batch_calendar_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Tanggal Mulai', 'archeus-booking'); ?></th>
                                <td>
                                    <input type="date" name="batch_start_date" value="<?php echo esc_attr(date('Y-m-d')); ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Tanggal Selesai', 'archeus-booking'); ?></th>
                                <td>
                                    <input type="date" name="batch_end_date" value="<?php echo esc_attr(date('Y-m-d', strtotime('+7 days'))); ?>" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Status Ketersediaan', 'archeus-booking'); ?></th>
                                <td>
                                    <select name="batch_availability_status" class="ab-select">
                                        <option value="available"><?php _e('Tersedia', 'archeus-booking'); ?></option>
                                        <option value="unavailable"><?php _e('Tidak Tersedia', 'archeus-booking'); ?></option>
                                        <option value="holiday"><?php _e('Libur', 'archeus-booking'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Batas Harian', 'archeus-booking'); ?></th>
                                <td>
                                    <input type="number" name="batch_daily_limit" value="3" min="0">
                                    <p class="description"><?php _e('Jumlah maksimum pemesanan yang diizinkan untuk tanggal ini (ditetapkan ke 0 jika tidak ada batasan)', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Submit', 'archeus-booking'), 'primary', 'batch_update'); ?>
                    </form>
                </div>
                
                <!-- Calendar Display Settings -->
                <div class="calendar-display-settings admin-card">
                    <h2><?php _e('Pengaturan Tampilan Kalender', 'archeus-booking'); ?></h2>
                    <form method="post" class="settings-form">
                        <?php wp_nonce_field('save_calendar_settings', 'calendar_settings_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Jumlah Bulan yang Ditampilkan', 'archeus-booking'); ?></th>
                                <td>
                                    <input type="number" name="max_calendar_months" value="<?php echo esc_attr($booking_calendar->get_max_months_display()); ?>" min="1" max="24">
                                    <p class="description"><?php _e('Jumlah bulan yang ditampilkan pada kalender (default: 3, maks: 24)', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Status yang Memblokir Slot', 'archeus-booking'); ?></th>
                                <td>
                                    <?php $current_blocking = get_option('booking_blocking_statuses', array('approved', 'completed')); ?>
                            <label><input type="checkbox" name="blocking_statuses[]" value="pending" <?php checked(in_array('pending', $current_blocking, true)); ?>> <?php _e('Menunggu (pending)', 'archeus-booking'); ?></label><br>
                            <label><input type="checkbox" name="blocking_statuses[]" value="approved" <?php checked(in_array('approved', $current_blocking, true)); ?>> <?php _e('Disetujui', 'archeus-booking'); ?></label><br>
                            <!-- <label><input type="checkbox" name="blocking_statuses[]" value="ready" <?php checked(in_array('ready', $current_blocking, true)); ?>> <?php _e('Siap', 'archeus-booking'); ?></label><br> -->
                            <label><input type="checkbox" name="blocking_statuses[]" value="completed" <?php checked(in_array('completed', $current_blocking, true)); ?>> <?php _e('Selesai', 'archeus-booking'); ?></label><br>
                            <label><input type="checkbox" name="blocking_statuses[]" value="rejected" <?php checked(in_array('rejected', $current_blocking, true)); ?>> <?php _e('Ditolak', 'archeus-booking'); ?></label>
                                    <p class="description"><?php _e('Pilih status pemesanan yang dihitung untuk memblokir tanggal/slot pada kalender.', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Submit', 'archeus-booking'), 'primary', 'save_calendar_settings'); ?>
                    </form>
                </div>
                

                
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                // Handle admin calendar navigation - using same logic as public calendar
                $(document).on('click', '.calendar-nav-btn', function() {
                    var month = parseInt($(this).attr('data-month'), 10);
                    var year = parseInt($(this).attr('data-year'), 10);
                    
                    // Check if the month is within allowed range
                    var today = new Date();
                    var currentMonth = today.getMonth() + 1;
                    var currentYear = today.getFullYear();
                    
                    var maxMonths = <?php echo $booking_calendar->get_max_months_display(); ?>;
                    var maxDate = new Date();
                    maxDate.setMonth(maxDate.getMonth() + maxMonths - 1);
                    var maxMonth = maxDate.getMonth() + 1;
                    var maxYear = maxDate.getFullYear();
                    
                    // Calculate the month and year as a single value for comparison
                    var clickedMonthValue = year * 12 + month;
                    var minMonthValue = currentYear * 12 + currentMonth;
                    var maxMonthValue = maxYear * 12 + maxMonth;
                    
                    if (clickedMonthValue < minMonthValue) {
                        alert('<?php _e('You cannot navigate to months before the current month.', 'archeus-booking'); ?>');
                        return;
                    }
                    
                    if (clickedMonthValue > maxMonthValue) {
                        alert('<?php printf(__('You cannot navigate more than %d months ahead.', 'archeus-booking'), $booking_calendar->get_max_months_display()); ?>');
                        return;
                    }
                    
                    // Update calendar display
                    updateCalendarDisplay(month, year);
                });
                
                // Function to update calendar display via AJAX (same as public calendar)
                function updateCalendarDisplay(month, year) {
                    // Show loading indicator
                    $('.booking-calendar').addClass('loading');
                    
                    $.ajax({
                        url: calendar_ajax.ajax_url,
                        type: 'POST',
                        dataType: 'text',
                        data: {
                            action: 'get_admin_calendar_data',
                            month: month,
                            year: year,
                            nonce: calendar_ajax.nonce
                        },
                        success: function(response) {
                            try {
                                if (typeof response === 'string') {
                                    var firstBrace = response.indexOf('{');
                                    if (firstBrace > 0) {
                                        response = response.slice(firstBrace);
                                    }
                                    response = JSON.parse(response);
                                }
                                if (response && response.success) {
                                    updateCalendarView(month, year, response.data || {});
                                } else {
                                    console.error('Error loading calendar data:', response && response.data);
                                    $('.booking-calendar').removeClass('loading');
                                }
                            } catch (e) {
                                console.error('JSON parse error for calendar data', e, response);
                                $('.booking-calendar').removeClass('loading');
                            }
                        },
                        error: function(xhr, status, err) {
                            console.error('Error loading calendar data', status, err, xhr && xhr.responseText);
                            $('.booking-calendar').removeClass('loading');
                        }
                    });
                }
                
                // Function to update the calendar view (same as public calendar)
                function updateCalendarView(month, year, data) {
                    // Update the month/year header (scoped to this calendar)
                    $('.booking-calendar-header .current-month').text(getMonthName(month) + ' ' + year);
                    
                    // Update navigation buttons
                    var prevMonth = month - 1;
                    var prevYear = year;
                    if (prevMonth < 1) {
                        prevMonth = 12;
                        prevYear = year - 1;
                    }
                    
                    var nextMonth = month + 1;
                    var nextYear = year;
                    if (nextMonth > 12) {
                        nextMonth = 1;
                        nextYear = year + 1;
                    }
                    
                    // Check if previous month is within range
                    var today = new Date();
                    var currentMonth = today.getMonth() + 1;
                    var currentYear = today.getFullYear();
                    var minMonthValue = currentYear * 12 + currentMonth;
                    var prevMonthValue = prevYear * 12 + prevMonth;
                    
                    if (prevMonthValue < minMonthValue) {
                        $('.prev-month').prop('disabled', true).addClass('disabled');
                    } else {
                        $('.prev-month').prop('disabled', false).removeClass('disabled')
                                        .attr('data-month', prevMonth).attr('data-year', prevYear);
                    }
                    
                    // Check if next month is within range
                    var maxMonths = <?php echo $booking_calendar->get_max_months_display(); ?>;
                    var maxDate = new Date();
                    maxDate.setMonth(maxDate.getMonth() + maxMonths - 1);
                    var maxMonthNum = maxDate.getMonth() + 1;
                    var maxYearNum = maxDate.getFullYear();
                    var maxMonthValue = maxYearNum * 12 + maxMonthNum;
                    var nextMonthValue = nextYear * 12 + nextMonth;
                    
                    if (nextMonthValue > maxMonthValue) {
                        $('.next-month').prop('disabled', true).addClass('disabled');
                    } else {
                        $('.next-month').prop('disabled', false).removeClass('disabled')
                                         .attr('data-month', nextMonth).attr('data-year', nextYear);
                    }
                    
                    // Generate new calendar days for the selected month (same as public calendar)
                    generateCalendarDays(month, year, data);
                    
                    // Remove loading indicator
                    $('.booking-calendar').removeClass('loading');
                }
                
                // Function to generate calendar days for a specific month (same as public calendar)
                function generateCalendarDays(month, year, availabilityData) {
                    var firstDay = new Date(year, month - 1, 1);
                    var daysInMonth = new Date(year, month, 0).getDate();
                    var startDay = firstDay.getDay(); // 0 = Sunday, 1 = Monday, etc.
                    
                    // Clear existing calendar days
                    var $calendarDays = $('.booking-calendar .calendar-days');
                    $calendarDays.empty();
                    
                    // Add empty cells for the days before the first day of the month
                    for (var i = 0; i < startDay; i++) {
                        $calendarDays.append('<div class="calendar-day empty"></div>');
                    }
                    
                    // Add cells for each day of the month
                    for (var day = 1; day <= daysInMonth; day++) {
                        var dateStr = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                        var $dayElement = $('<div class="calendar-day"></div>');
                        var $dayNumberSpan = $('<span class="day-number"></span>');
                        
                        // Set date attribute
                        $dayElement.attr('data-date', dateStr);
                        
                        // Determine status and class
                        var currentDate = new Date();
                        var currentDateString = currentDate.getFullYear() + '-' + 
                                                String(currentDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                                String(currentDate.getDate()).padStart(2, '0');
                        
                        if (dateStr < currentDateString) {
                            // Past date
                            $dayElement.addClass('past');
                            $dayElement.attr('data-status', 'past');
                            $dayNumberSpan.text(day);
                        } else if (availabilityData[dateStr]) {
                            // Date with availability data
                            var dayData = availabilityData[dateStr];
                            var status = dayData.status;
                            var bookedCount = dayData.booked_count;
                            var dailyLimit = dayData.daily_limit;
                            
                            // Add status class
                            $dayElement.addClass(status);
                            $dayElement.attr('data-status', status);
                            
                            // Format day number with booking count if available
                            if (status === 'available' || status === 'limited') {
                                if (bookedCount > 0) {
                                    $dayNumberSpan.html(day + '<span class="booking-count">(' + bookedCount + '/' + dailyLimit + ')</span>');
                                } else {
                                    $dayNumberSpan.text(day);
                                }
                            } else {
                                $dayNumberSpan.text(day);
                            }
                        } else {
                            // Default availability
                            $dayElement.addClass('available');
                            $dayElement.attr('data-status', 'available');
                            $dayNumberSpan.text(day);
                        }
                        
                        $dayElement.append($dayNumberSpan);
                        $calendarDays.append($dayElement);
                    }
                }
                
                // Helper function to get month name
                function getMonthName(month) {
                    const monthNames = [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ];
                    return monthNames[month - 1];
                }
            });
        </script>
        
        <?php /* admin-calendar.css consolidated into admin.css */ ?>
        <?php
    }
    
    /**
     * Generate admin calendar HTML for a specific month
     */
    private function generate_admin_calendar($month, $year) {
        $booking_calendar = new Booking_Calendar();
        $availability_data = $booking_calendar->get_month_availability($year, $month);

        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $day_of_week = date('w', $first_day);

        $calendar_html = '';

        // Get today's date
        $today = date('Y-m-d');

        // Add empty cells for the days before the first day of the month
        for ($i = 0; $i < $day_of_week; $i++) {
            $calendar_html .= '<div class="calendar-day empty"></div>';
        }

        // Add cells for each day of the month
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $date_obj = new DateTime($date);
            $day_of_week_num = $date_obj->format('w');
            
            // Get availability for this day
            $day_availability = isset($availability_data[$date]) ? $availability_data[$date] : array(
                'date' => $date,
                'availability_status' => 'available',
                'daily_limit' => 5,
                'booked_count' => 0
            );

            // Determine class based on availability
            $classes = array('calendar-day');
            $status = $day_availability['availability_status'];
            $booked_count = $day_availability['booked_count'];
            $daily_limit = $day_availability['daily_limit'];

            // Determine availability status
            if ($status === 'unavailable' || $status === 'holiday') {
                $classes[] = $status;
                $status_class = $status;
            } else {
                if ($booked_count >= $daily_limit) {
                    $status_class = 'full';
                    $classes[] = 'full';
                } elseif ($booked_count > 0) {
                    $status_class = 'limited';
                    $classes[] = 'limited';
                } else {
                    $status_class = 'available';
                    $classes[] = 'available';
                }
            }

            // Disable past dates
            if ($date < $today) {
                $classes[] = 'past';
                $status_class = 'past';
            }

            $classes_str = implode(' ', $classes);
            
            $day_label = $day;
            
            // Check if this date has any bookings
            if ($booked_count > 0) {
                $day_label = $day . '<span class="booking-count">(' . $booked_count . '/' . $daily_limit . ')</span>';
            }

            $calendar_html .= '<div class="' . $classes_str . '" data-date="' . $date . '" data-status="' . $status_class . '">';
            $calendar_html .= '<span class="day-number">' . $day_label . '</span>';
            $calendar_html .= '</div>';
        }

        return $calendar_html;
    }
    
    /**
     * Handle AJAX request to get admin calendar data
     */
    public function handle_get_admin_calendar_data() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'calendar_nonce')) {
            wp_send_json_error(array('message' => __('Pemeriksaan keamanan gagal.', 'archeus-booking')), 403);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Anda tidak memiliki izin untuk melakukan aksi ini.', 'archeus-booking')), 403);
        }

        $month = isset($_POST['month']) ? intval($_POST['month']) : date('n');
        $year = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

        $booking_calendar = new Booking_Calendar();
        $availability_data = $booking_calendar->get_month_availability($year, $month);
        
        // Format the data for JavaScript
        $formatted_data = array();
        foreach ($availability_data as $date => $data) {
            $status = $data['availability_status'];
            $booked_count = $data['booked_count'];
            $daily_limit = $data['daily_limit'];
            
            // Determine display status
            if ($status === 'unavailable' || $status === 'holiday') {
                $display_status = $status;
            } elseif ($booked_count >= $daily_limit) {
                $display_status = 'full';
            } elseif ($booked_count > 0) {
                $display_status = 'limited';
            } else {
                $display_status = 'available';
            }
            
            // Check if date is in the past
            if ($date < date('Y-m-d')) {
                $display_status = 'past';
            }
            
            $formatted_data[$date] = array(
                'status' => $display_status,
                'booked_count' => $booked_count,
                'daily_limit' => $daily_limit
            );
        }

        wp_send_json_success($formatted_data);
    }
    
    /**
     * Services management page
     */
    public function services_page() {
        $booking_db = new Booking_Database();
        
        // Handle form submission for adding/updating services
        if (isset($_POST['save_service']) && wp_verify_nonce($_POST['service_nonce'], 'save_service_action')) {
            $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
            $name = sanitize_text_field($_POST['service_name']);
            $description = sanitize_textarea_field($_POST['service_description']);
            $price = floatval($_POST['service_price']);
            $duration = intval($_POST['service_duration']);
            $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
            
            if ($service_id > 0) {
                // Update existing service
                $result = $booking_db->update_service($service_id, $name, $description, $price, $duration, $is_active);
                $message = $result ? __('Service updated successfully.', 'archeus-booking') : __('Error updating service.', 'archeus-booking');
            } else {
                // Create new service
                $result = $booking_db->create_service($name, $description, $price, $duration, $is_active);
                $message = $result ? __('Service created successfully.', 'archeus-booking') : __('Error creating service.', 'archeus-booking');
            }
            
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
            }
        }
        
        // Handle service deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['service_id'])) {
            $service_id = intval($_GET['service_id']);
            $result = $booking_db->delete_service($service_id);
            $message = $result ? __('Service deleted successfully.', 'archeus-booking') : __('Error deleting service.', 'archeus-booking');
            
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
            }
        }
        
        // Get service data for editing
        $edit_service = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['service_id'])) {
            $service_id = intval($_GET['service_id']);
            $edit_service = $booking_db->get_service($service_id);
        }
        
        // Get all services
        $services = $booking_db->get_services();
        
        ?>
        <div class="wrap booking-admin-page">
            <h1 class="title-page"><?php _e('Service Management (Kelola Layanan)', 'archeus-booking'); ?></h1>
            
            <?php
            // Admin notice/callout for Services page (same as dashboard)
            $booking_db = new Booking_Database();
            $flows = method_exists($booking_db, 'get_booking_flows') ? (array) $booking_db->get_booking_flows() : array();
            $first_id = 1;
            if (!empty($flows)) {
                usort($flows, function($a,$b){ return intval($a->id) - intval($b->id); });
                $first_id = isset($flows[0]->id) ? intval($flows[0]->id) : 1;
            }
            $flow_url = admin_url('admin.php?page=archeus-booking-flow');
            echo '<div class="notice notice-info ab-callout is-dismissible" style="padding:0;border-left-width:0;">'
               . '<div class="ab-callout-inner">'
               . '  <div class="ab-callout-icon" aria-hidden="true">'
               . '    <span class="dashicons dashicons-shortcode"></span>'
               . '  </div>'
               . '  <div class="ab-callout-body">'
               . '    <h3 class="ab-callout-title">' . esc_html__('Tampilkan di Sisi Pengguna', 'archeus-booking') . '</h3>'
               . '    <p class="ab-callout-desc">' . esc_html__('Gunakan shortcode dengan ID flow (contoh: [archeus_booking id="1"]).', 'archeus-booking') . '</p>'
               . '    <div class="ab-shortcode-row">'
               . '      <label for="ab-flow-select" style="margin-right:6px;">' . esc_html__('Pilih Flow:', 'archeus-booking') . '</label>'
               . '      <select id="ab-flow-select" class="ab-select ab-dropdown">';
            if (!empty($flows)) {
                foreach ($flows as $f) {
                    $id = intval($f->id);
                    $name = !empty($f->name) ? $f->name : ('Flow #' . $id);
                    echo '<option value="' . esc_attr($id) . '"' . selected($id, $first_id, false) . '>' . esc_html($name) . '</option>';
                }
            } else {
                echo '<option value="1">' . esc_html__('Flow #1', 'archeus-booking') . '</option>';
            }
            echo    '</select>'
               . '      <code class="ab-shortcode-code" id="ab-sc-with-id">[archeus_booking id="' . esc_attr($first_id) . '"]</code>'
               . '      <button type="button" class="button ab-copy-btn" id="ab-copy-with-id" data-copy="[archeus_booking id=\"' . esc_attr($first_id) . '\"]" aria-label="' . esc_attr__('Salin shortcode', 'archeus-booking') . '"><span class="dashicons dashicons-clipboard"></span><span>' . esc_html__('Salin', 'archeus-booking') . '</span></button>'
               . '      <a class="button button-primary" href="' . esc_url($flow_url) . '">' . esc_html__('Konfigurasi Booking Flow', 'archeus-booking') . '</a>'
               . '    </div>'
               . '  </div>'
               . '</div>'
               . '</div>';
            ?>
            
            <div class="service-management-container">
                <!-- Service Form -->
                <div class="service-form admin-card">
                    <h2><?php echo $edit_service ? __('Ubah Layanan', 'archeus-booking') : __('Tambah Layanan', 'archeus-booking'); ?></h2>
                    
                    <form method="post" action="" class="settings-form">
                        <?php wp_nonce_field('save_service_action', 'service_nonce'); ?>
                        <input type="hidden" name="service_id" value="<?php echo $edit_service ? esc_attr($edit_service->id) : ''; ?>">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="service_name"><?php _e('Nama Layanan', 'archeus-booking'); ?></label></th>
                                <td>
                                    <input type="text" id="service_name" name="service_name" value="<?php echo $edit_service ? esc_attr($edit_service->name) : ''; ?>" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="service_description"><?php _e('Deskripsi', 'archeus-booking'); ?></label></th>
                                <td>
                                    <textarea id="service_description" name="service_description" rows="4" class="large-text"><?php echo $edit_service ? esc_textarea($edit_service->description) : ''; ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="service_price"><?php _e('Harga', 'archeus-booking'); ?></label></th>
                                <td>
                                    <input type="number" id="service_price" name="service_price" value="<?php echo $edit_service ? esc_attr($edit_service->price) : '0'; ?>" step="0.01" min="0" class="regular-text">
                                    <p class="description"><?php _e('Masukkan harga layanan (dapat 0 untuk layanan gratis)', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="service_duration"><?php _e('Durasi (menit)', 'archeus-booking'); ?></label></th>
                                <td>
                                    <input type="number" id="service_duration" name="service_duration" value="<?php echo $edit_service ? esc_attr($edit_service->duration) : '30'; ?>" min="1" class="regular-text">
                                    <p class="description"><?php _e('Masukkan durasi layanan (dalam menit)', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="is_active"><?php _e('Status', 'archeus-booking'); ?></label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $edit_service ? checked($edit_service->is_active, 1, false) : 'checked'; ?>>
                                        <?php _e('Active', 'archeus-booking'); ?>
                                    </label>
                                    <p class="description"><?php _e('Hapus centang untuk menonaktifkan layanan ini', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button($edit_service ? __('Submit', 'archeus-booking') : __('Submit', 'archeus-booking'), 'primary', 'save_service'); ?>
                    </form>
                </div>
                
                <!-- Services List -->
                <div class="services-list admin-card">
                    <h2><?php _e('Daftar Layanan', 'archeus-booking'); ?></h2>
                    
                    <?php if (empty($services)): ?>
                        <p><?php _e('Tidak ada layanan yang ditemukan. Tambahkan layanan pertama di atas.', 'archeus-booking'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('ID', 'archeus-booking'); ?></th>
                                    <th><?php _e('Nama Layanan', 'archeus-booking'); ?></th>
                                    <th><?php _e('Deskripsi', 'archeus-booking'); ?></th>
                                    <th><?php _e('Harga', 'archeus-booking'); ?></th>
                                    <th><?php _e('Durasi', 'archeus-booking'); ?></th>
                                    <th><?php _e('Status', 'archeus-booking'); ?></th>
                                    <th><?php _e('Aksi', 'archeus-booking'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo esc_html($service->id); ?></td>
                                        <td><strong><?php echo esc_html($service->name); ?></strong></td>
                                        <td><?php echo wp_trim_words(esc_html($service->description), 10); ?></td>
                                        <td><?php echo $service->price > 0 ? 'Rp ' . number_format($service->price, 0, ',', '.') : __('Gratis', 'archeus-booking'); ?></td>
                                        <td><?php echo esc_html($service->duration); ?> <?php _e('minutes', 'archeus-booking'); ?></td>
                                        <td>
                                            <?php if ($service->is_active): ?>
                                                <span class="status-active"><?php _e('Aktif', 'archeus-booking'); ?></span>
                                            <?php else: ?>
                                                <span class="status-inactive"><?php _e('Nonaktif', 'archeus-booking'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-actions">
                                            <div class="action-buttons">
                                                <a href="<?php echo admin_url('admin.php?page=archeus-booking-services&action=edit&service_id=' . $service->id); ?>" class="button button-warning edit-button" title="<?php esc_attr_e('Ubah Layanan', 'archeus-booking'); ?>">
                                                    <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                                                    <span class="screen-reader-text"><?php _e('Edit', 'archeus-booking'); ?></span>
                                                </a>
                                                <a href="#" class="button button-danger delete-service" data-service-id="<?php echo $service->id; ?>" title="<?php esc_attr_e('Hapus Layanan', 'archeus-booking'); ?>">
                                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                                    <span class="screen-reader-text"><?php _e('Hapus', 'archeus-booking'); ?></span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php wp_enqueue_style('admin-services-css', ARCHEUS_BOOKING_URL . 'assets/css/admin-services.css', array(), ARCHEUS_BOOKING_VERSION); ?>
            
            </div>
        <?php
    }
    

    
    /**
     * Handle AJAX request to check schedule limit for a date
     */
    public function handle_check_date_schedule_limit() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'booking_admin_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'archeus-booking')
            ));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action', 'archeus-booking')
            ));
        }

        $date = sanitize_text_field($_POST['date']);

        if (empty($date)) {
            wp_send_json_error(array(
                'message' => __('Invalid date', 'archeus-booking')
            ));
        }

        $booking_db = new Booking_Database();
        $booking_calendar = new Booking_Calendar();
        
        // Get calendar daily limit
        $date_availability = $booking_calendar->get_availability_with_bookings($date);
        $daily_limit = isset($date_availability['availability']->daily_limit) ? $date_availability['availability']->daily_limit : 5;
        
        // Get current number of schedule slots for this date
        global $wpdb;
        $schedules_table = $wpdb->prefix . 'archeus_booking_schedules';
        $current_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$schedules_table} WHERE date = %s",
            $date
        ));
        
        $current_count = intval($current_count);
        
        wp_send_json_success(array(
            'current_count' => $current_count,
            'daily_limit' => $daily_limit
        ));
    }
    
    /**
     * Add time slots menu
     */
    public function add_time_slots_menu() {
        // Add time slots submenu under the main booking menu
        add_submenu_page(
            'archeus-booking-management',
            __('Time Slots Management', 'archeus-booking'),
            __('Time Slots', 'archeus-booking'),
            'manage_options',
            'archeus-booking-time-slots',
            array($this, 'time_slots_page')
        );
    }
    
    /**
     * Add booking flow menu
     */
    public function add_booking_flow_menu() {
        // Add booking flow submenu under the main booking menu
        add_submenu_page(
            'archeus-booking-management',
            __('Booking Flow Management', 'archeus-booking'),
            __('Booking Flow', 'archeus-booking'),
            'manage_options',
            'archeus-booking-flow',
            array($this, 'booking_flow_page')
        );
    }
    
    /**
     * Time slots management page
     */
    public function time_slots_page() {
        $time_slots_manager = new Time_Slots_Manager();
        
        // Handle form submission for adding/updating time slots (fallback for non-AJAX requests)
        if (isset($_POST['save_time_slot']) && wp_verify_nonce($_POST['time_slot_nonce'], 'save_time_slot_action') && !isset($_POST['action'])) {
            $slot_id = isset($_POST['slot_id']) ? intval($_POST['slot_id']) : 0;
            $time_label = sanitize_text_field($_POST['time_label']);
            $start_time = sanitize_text_field($_POST['start_time']);
            $end_time = sanitize_text_field($_POST['end_time']);
            $max_capacity = intval($_POST['max_capacity']);
            $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;
            $sort_order = 0; // Not used anymore, but kept for parameter compatibility
            
            // Validate time format - accept both HH:MM and HH:MM:SS formats
            $time_pattern_hhmm = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/';
            $time_pattern_hhmmss = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';
            
            // If time is in HH:MM format, convert to HH:MM:SS format
            if (preg_match($time_pattern_hhmm, $start_time)) {
                $start_time .= ':00';
            }
            
            if (preg_match($time_pattern_hhmm, $end_time)) {
                $end_time .= ':00';
            }
            
            // Validate the final format
            if (!preg_match($time_pattern_hhmmss, $start_time) || !preg_match($time_pattern_hhmmss, $end_time)) {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Format waktu tidak valid. Gunakan format JJ:MM.', 'archeus-booking') . '</p></div>';
            } else {
                // Validate that end time is after start time
                $start_timestamp = strtotime('1970-01-01 ' . $start_time);
                $end_timestamp = strtotime('1970-01-01 ' . $end_time);
                
                if ($end_timestamp <= $start_timestamp) {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __('Waktu selesai harus setelah waktu mulai.', 'archeus-booking') . '</p></div>';
                } else {
                    if ($slot_id > 0) {
                        // Update existing time slot - use 0 for sort_order to maintain parameter compatibility
                        $result = $time_slots_manager->update_time_slot($slot_id, $time_label, $start_time, $end_time, $max_capacity, $is_active, 0);
                        $message = $result ? __('Slot waktu berhasil diperbarui.', 'archeus-booking') : __('Gagal memperbarui slot waktu.', 'archeus-booking');
                    } else {
                        // Create new time slot
                        $result = $time_slots_manager->create_time_slot($time_label, $start_time, $end_time, $max_capacity, $sort_order);
                        $message = $result ? __('Slot waktu berhasil dibuat.', 'archeus-booking') : __('Gagal membuat slot waktu.', 'archeus-booking');
                    }
                    
                    if ($result) {
                        echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
                    } else {
                        echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
                    }
                }
            }
        }
        
        // Handle time slot deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['slot_id'])) {
            $slot_id = intval($_GET['slot_id']);
            $result = $time_slots_manager->delete_time_slot($slot_id);
            $message = $result ? __('Slot waktu berhasil dihapus.', 'archeus-booking') : __('Gagal menghapus slot waktu.', 'archeus-booking');
            
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
            }
        }
        
        // Get time slot data for editing
        $edit_slot = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['slot_id'])) {
            $slot_id = intval($_GET['slot_id']);
            $edit_slot = $time_slots_manager->get_time_slot($slot_id);
        }
        
        // Get all time slots
        $time_slots = $time_slots_manager->get_time_slots(false); // Get all, including inactive
        
        ?>
        <div class="wrap booking-admin-page time-slots-page">
            <h1 class="title-page"><?php _e('Time Slot Management (Kelola Slot Waktu)', 'archeus-booking'); ?></h1>
            <?php
            // Admin notice/callout for Time Slots page
            $booking_db = new Booking_Database();
            $flows = method_exists($booking_db, 'get_booking_flows') ? (array) $booking_db->get_booking_flows() : array();
            $first_id = 1;
            if (!empty($flows)) {
                usort($flows, function($a,$b){ return intval($a->id) - intval($b->id); });
                $first_id = isset($flows[0]->id) ? intval($flows[0]->id) : 1;
            }
            $flow_url = admin_url('admin.php?page=archeus-booking-flow');
            echo '<div class="notice notice-info ab-callout is-dismissible" style="padding:0;border-left-width:0;">'
               . '<div class="ab-callout-inner">'
               . '  <div class="ab-callout-icon" aria-hidden="true">'
               . '    <span class="dashicons dashicons-shortcode"></span>'
               . '  </div>'
               . '  <div class="ab-callout-body">'
               . '    <h3 class="ab-callout-title">' . esc_html__('Tampilkan di Sisi Pengguna', 'archeus-booking') . '</h3>'
               . '    <p class="ab-callout-desc">' . esc_html__('Gunakan shortcode dengan ID flow (contoh: [archeus_booking id="1"]).', 'archeus-booking') . '</p>'
               . '    <div class="ab-shortcode-row">'
               . '      <label for="ab-flow-select" style="margin-right:6px;">' . esc_html__('Pilih Flow:', 'archeus-booking') . '</label>'
               . '      <select id="ab-flow-select" class="ab-select ab-dropdown">';
            if (!empty($flows)) {
                foreach ($flows as $f) {
                    $id = intval($f->id);
                    $name = !empty($f->name) ? $f->name : ('Flow #' . $id);
                    echo '<option value="' . esc_attr($id) . '"' . selected($id, $first_id, false) . '>' . esc_html($name) . '</option>';
                }
            } else {
                echo '<option value="1">' . esc_html__('Flow #1', 'archeus-booking') . '</option>';
            }
            echo    '</select>'
               . '      <code class="ab-shortcode-code" id="ab-sc-with-id">[archeus_booking id="' . esc_attr($first_id) . '"]</code>'
               . '      <button type="button" class="button ab-copy-btn" id="ab-copy-with-id" data-copy="[archeus_booking id=\"' . esc_attr($first_id) . '\"]" aria-label="' . esc_attr__('Salin shortcode', 'archeus-booking') . '"><span class="dashicons dashicons-clipboard"></span><span>' . esc_html__('Salin', 'archeus-booking') . '</span></button>'
               . '      <a class="button button-primary" href="' . esc_url($flow_url) . '">' . esc_html__('Konfigurasi Booking Flow', 'archeus-booking') . '</a>'
               . '    </div>'
               . '  </div>'
               . '</div>'
               . '</div>';
            ?>
                        
            <div class="time-slot-management-container">
                <!-- Time Slot Form -->
                <div class="admin-card">
                    <h2><?php echo $edit_slot ? __('Ubah Slot Waktu', 'archeus-booking') : __('Tambah Slot Waktu', 'archeus-booking'); ?></h2>
                    
                    <form method="post" action="" class="settings-form" data-ajax-form="true">
                        <?php wp_nonce_field('archeus_booking_admin', 'archeus_booking_admin_nonce'); ?>
                        <input type="hidden" name="slot_id" value="<?php echo $edit_slot ? esc_attr($edit_slot->id) : ''; ?>">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="time_label"><?php _e('Label Slot Waktu', 'archeus-booking'); ?></label></th>
                                <td>
                                    <input type="text" id="time_label" name="time_label" value="<?php echo $edit_slot ? esc_attr($edit_slot->time_label) : ''; ?>" class="regular-text" required>
                                    <p class="description"><?php _e('Label deskriptif untuk slot waktu ini (mis. "Sesi Pagi")', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="start_time"><?php _e('Waktu Mulai', 'archeus-booking'); ?></label></th>
                                <td>
                                    <input type="time" id="start_time" name="start_time" value="<?php echo $edit_slot ? esc_attr(substr($edit_slot->start_time, 0, 5)) : '09:00'; ?>" required>
                                    <p class="description"><?php _e('Gunakan format JJ:MM (contoh 09:00)', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="end_time"><?php _e('Waktu Selesai', 'archeus-booking'); ?></label></th>
                                <td>
                                    <input type="time" id="end_time" name="end_time" value="<?php echo $edit_slot ? esc_attr(substr($edit_slot->end_time, 0, 5)) : '10:00'; ?>" required>
                                    <p class="description"><?php _e('Gunakan format JJ:MM (contoh 10:00)', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="max_capacity"><?php _e('Kapasitas Maksimum', 'archeus-booking'); ?></label></th>
                                <td>
                                    <input type="number" id="max_capacity" name="max_capacity" value="<?php echo $edit_slot ? esc_attr($edit_slot->max_capacity) : '1'; ?>" min="1" required>
                                    <p class="description"><?php _e('Jumlah maksimal pemesanan yang diizinkan untuk slot waktu ini', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Pengurutan', 'archeus-booking'); ?></th>
                                <td>
                                    <p class="description"><?php _e('Slot waktu diurutkan berdasarkan waktu mulai. Fitur seret-untuk-mengurutkan akan ditambahkan pada pembaruan berikutnya.', 'archeus-booking'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="is_active"><?php _e('Status', 'archeus-booking'); ?></label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $edit_slot ? checked($edit_slot->is_active, 1, false) : 'checked'; ?>>
                                        <?php _e('Active', 'archeus-booking'); ?>
                                    </label>
                                    <p class="description"><?php _e('Hapus centang untuk menonaktifkan slot waktu ini', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button($edit_slot ? __('Submit', 'archeus-booking') : __('Submit', 'archeus-booking'), 'primary', 'save_time_slot'); ?>
                    </form>
                </div>
                
                <!-- Time Slots List -->
                <div class="admin-card">
                    <h2><?php _e('Daftar Slot Waktu', 'archeus-booking'); ?></h2>
                    
                    <?php if (empty($time_slots)): ?>
                        <p><?php _e('Belum ada slot waktu. Tambahkan slot waktu pertama Anda di atas.', 'archeus-booking'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('ID', 'archeus-booking'); ?></th>
                                    <th><?php _e('Label', 'archeus-booking'); ?></th>
                                    <th><?php _e('Waktu', 'archeus-booking'); ?></th>
                                    <th><?php _e('Kapasitas', 'archeus-booking'); ?></th>
                                    <th><?php _e('Status', 'archeus-booking'); ?></th>
                                    <th><?php _e('Tindakan', 'archeus-booking'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($time_slots as $slot): ?>
                                    <tr>
                                        <td><?php echo esc_html($slot->id); ?></td>
                                        <td><strong><?php echo esc_html($slot->time_label); ?></strong></td>
                                        <td><?php echo esc_html($slot->start_time . ' - ' . $slot->end_time); ?></td>
                                        <td><?php echo esc_html($slot->max_capacity); ?></td>
                                        <td>
                                            <?php if ($slot->is_active): ?>
                                                <span class="status-active" style="color: green;"><?php _e('Aktif', 'archeus-booking'); ?></span>
                                            <?php else: ?>
                                                <span class="status-inactive" style="color: red;"><?php _e('Tidak Aktif', 'archeus-booking'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-actions">
                                            <div class="action-buttons">
                                                <a href="<?php echo admin_url('admin.php?page=archeus-booking-time-slots&action=edit&slot_id=' . $slot->id); ?>" class="button button-warning edit-button" title="<?php esc_attr_e('Ubah Slot Waktu', 'archeus-booking'); ?>">
                                                    <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                                                    <span class="screen-reader-text"><?php _e('Ubah', 'archeus-booking'); ?></span>
                                                </a>
                                                <a href="<?php echo admin_url('admin.php?page=archeus-booking-time-slots&action=delete&slot_id=' . $slot->id); ?>" class="button button-danger delete-time-slot" data-slot-id="<?php echo esc_attr($slot->id); ?>" title="<?php esc_attr_e('Hapus Slot Waktu', 'archeus-booking'); ?>">
                                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                                    <span class="screen-reader-text"><?php _e('Hapus', 'archeus-booking'); ?></span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Booking flow management page
     */
    public function booking_flow_page() {
        $booking_db = new Booking_Database();
        
        // Handle form submission for adding/updating booking flows
        if (isset($_POST['save_booking_flow']) && wp_verify_nonce($_POST['booking_flow_nonce'], 'save_booking_flow_action')) {
            $flow_id = isset($_POST['flow_id']) ? intval($_POST['flow_id']) : 0;
            $flow_name = sanitize_text_field($_POST['flow_name']);
            $flow_description = sanitize_textarea_field($_POST['flow_description']);
            
            // Process sections (replaces legacy steps)
            $sections = array();
            $types_arr = isset($_POST['section_types']) ? $_POST['section_types'] : (isset($_POST['step_types']) ? $_POST['step_types'] : array());
            $names_arr = isset($_POST['section_names']) ? $_POST['section_names'] : (isset($_POST['step_names']) ? $_POST['step_names'] : array());
            $req_arr   = isset($_POST['section_required']) ? $_POST['section_required'] : (isset($_POST['step_required']) ? $_POST['step_required'] : array());
            $desc_arr  = isset($_POST['section_descriptions']) ? $_POST['section_descriptions'] : (isset($_POST['step_descriptions']) ? $_POST['step_descriptions'] : array());
            $form_ids  = isset($_POST['section_form_ids']) ? $_POST['section_form_ids'] : (isset($_POST['step_form_ids']) ? $_POST['step_form_ids'] : array());

            if (is_array($types_arr)) {
                foreach ($types_arr as $index => $type) {
                    if (!empty($type)) {
                        $section = array(
                            'type' => sanitize_text_field($type),
                            'name' => sanitize_text_field($names_arr[$index] ?? ''),
                            'required' => isset($req_arr[$index]) ? 1 : 0
                        );
                        if (isset($desc_arr[$index])) { $section['section_description'] = sanitize_textarea_field($desc_arr[$index]); }
                        if ($type === 'form') { $section['form_id'] = intval($form_ids[$index] ?? 0); }
                        $section['section_name'] = $section['name'];
                        $sections[] = $section;
                    }
                }
            }
            
            // Save to database
            if ($flow_id > 0) {
                // Update existing flow
                $result = $booking_db->update_booking_flow($flow_id, $flow_name, $flow_description, $sections);
                $message = $result ? __('Booking flow updated successfully.', 'archeus-booking') : __('Error updating booking flow.', 'archeus-booking');
            } else {
                // Create new flow
                $result = $booking_db->create_booking_flow($flow_name, $flow_description, $sections);
                $message = $result ? __('Booking flow created successfully.', 'archeus-booking') : __('Error creating booking flow.', 'archeus-booking');
            }
            
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
            }
        }
        
        // Handle flow deletion
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['flow_id'])) {
            $flow_id = intval($_GET['flow_id']);
            $result = $booking_db->delete_booking_flow($flow_id);
            $message = $result ? __('Booking flow deleted successfully.', 'archeus-booking') : __('Error deleting booking flow.', 'archeus-booking');
            
            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
            }
        }
        
        // Get flow data for editing
        $edit_flow = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['flow_id'])) {
            $flow_id = intval($_GET['flow_id']);
            $edit_flow = $booking_db->get_booking_flow($flow_id);
        }
        
        // Get all booking flows
        $booking_flows = $booking_db->get_booking_flows();
        
        // Get all forms for form section type
        $forms = $booking_db->get_forms();
        ?>
        <div class="wrap booking-admin-page">
            <h1 class="title-page"><?php _e('Booking Flow Management (Kelola Alur Pemesanan)', 'archeus-booking'); ?></h1>
            
            <?php
            // Admin notice/callout for Booking Flow page
            $flows = method_exists($booking_db, 'get_booking_flows') ? (array) $booking_db->get_booking_flows() : array();
            $first_id = 1;
            if (!empty($flows)) {
                usort($flows, function($a,$b){ return intval($a->id) - intval($b->id); });
                $first_id = isset($flows[0]->id) ? intval($flows[0]->id) : 1;
            }
            $flow_url = admin_url('admin.php?page=archeus-booking-flow');
            echo '<div class="notice notice-info ab-callout is-dismissible" style="padding:0;border-left-width:0;">'
               . '<div class="ab-callout-inner">'
               . '  <div class="ab-callout-icon" aria-hidden="true">'
               . '    <span class="dashicons dashicons-shortcode"></span>'
               . '  </div>'
               . '  <div class="ab-callout-body">'
               . '    <h3 class="ab-callout-title">' . esc_html__('Tampilkan di Sisi Pengguna', 'archeus-booking') . '</h3>'
               . '    <p class="ab-callout-desc">' . esc_html__('Gunakan shortcode dengan ID flow (contoh: [archeus_booking id="1"]).', 'archeus-booking') . '</p>'
               . '    <div class="ab-shortcode-row">'
               . '      <label for="ab-flow-select" style="margin-right:6px;">' . esc_html__('Pilih Flow:', 'archeus-booking') . '</label>'
               . '      <select id="ab-flow-select" class="ab-select ab-dropdown">';
            if (!empty($flows)) {
                foreach ($flows as $f) {
                    $id = intval($f->id);
                    $name = !empty($f->name) ? $f->name : ('Flow #' . $id);
                    echo '<option value="' . esc_attr($id) . '"' . selected($id, $first_id, false) . '>' . esc_html($name) . '</option>';
                }
            } else {    
                echo '<option value="1">' . esc_html__('Flow #1', 'archeus-booking') . '</option>';
            }
            echo    '</select>'
               . '      <code class="ab-shortcode-code" id="ab-sc-with-id">[archeus_booking id="' . esc_attr($first_id) . '"]</code>'
               . '      <button type="button" class="button ab-copy-btn" id="ab-copy-with-id" data-copy="[archeus_booking id=\"' . esc_attr($first_id) . '\"]" aria-label="' . esc_attr__('Salin shortcode', 'archeus-booking') . '"><span class="dashicons dashicons-clipboard"></span><span>' . esc_html__('Salin', 'archeus-booking') . '</span></button>'
            //    . '      <a class="button button-primary" href="' . esc_url($flow_url) . '">' . esc_html__('Konfigurasi Booking Flow', 'archeus-booking') . '</a>'
               . '    </div>'
               . '  </div>'
               . '</div>'
               . '</div>';
            ?>
            
            <div class="booking-flow-management-container">
                <!-- Booking Flow Form -->
                <div class="booking-flow-form admin-card">
                    <h2><?php echo $edit_flow ? __('Ubah Booking Flow', 'archeus-booking') : __('Tambah Booking Flow', 'archeus-booking'); ?></h2>
                    
                    <form method="post" action="" class="settings-form">
                        <?php wp_nonce_field('save_booking_flow_action', 'booking_flow_nonce'); ?>
                        <input type="hidden" name="flow_id" value="<?php echo $edit_flow ? esc_attr($edit_flow->id) : ''; ?>">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="flow_name"><?php _e('Nama Flow', 'archeus-booking'); ?></label></th>
                                <td>
                                    <input type="text" id="flow_name" name="flow_name" value="<?php echo $edit_flow ? esc_attr($edit_flow->name) : ''; ?>" class="regular-text" required>
                                    <p class="description"><?php _e('Nama flow pemesanan', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="flow_description"><?php _e('Deskripsi', 'archeus-booking'); ?></label></th>
                                <td>
                                    <textarea id="flow_description" name="flow_description" rows="3" class="large-text"><?php echo $edit_flow ? esc_textarea($edit_flow->description) : ''; ?></textarea>
                                    <p class="description"><?php _e('Deskripsi singkat flow pemesanan', 'archeus-booking'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <h3><?php _e('Bagian Flow', 'archeus-booking'); ?></h3>
                        <div id="sections-container" class="section-container">
                            <?php
                            $section_count = 0;
                            $has_sections = false;

                            if ($edit_flow) {
                                $sections = !empty($edit_flow->sections) ? (is_string($edit_flow->sections) ? json_decode($edit_flow->sections, true) : $edit_flow->sections) : (is_string($edit_flow->steps) ? json_decode($edit_flow->steps, true) : $edit_flow->steps);

                                if (!empty($sections) && is_array($sections)) {
                                    $has_sections = true;
                                    foreach ($sections as $section) {
                                        $this->render_section_form($section, $section_count, $forms);
                                        $section_count++;
                                    }
                                }
                            }

                            if (!$has_sections) {
                                // Tampilkan pesan instruksi alih-alih bagian default
                                echo '<div id="no-sections-message" class="no-sections-message">';
                                echo '<p>' . esc_html__('Belum ada bagian yang dibuat. Tekan tombol "Tambah Bagian" untuk mulai membuat bagian flow.', 'archeus-booking') . '</p>';
                                echo '</div>';
                                $section_count = 0;
                            }
                            ?>
                        </div>
                        
                        <div class="button-container-flex">
                            <button type="button" id="add-section-btn" class="button button-secondary add-section"><span class="dashicons dashicons-plus"></span><?php _e('Tambah Bagian', 'archeus-booking'); ?></button>
                        </div>
                        
                        <?php submit_button($edit_flow ? __('Submit', 'archeus-booking') : __('Submit', 'archeus-booking'), 'primary', 'save_booking_flow'); ?>
                    </form>
                    
                    <script>
                        var sectionIndex = <?php echo $section_count; ?>;
                        
                        function updateSectionNumbers() {
                            jQuery('.section-item').each(function(index) {
                                jQuery(this).find('h4').html(
                                    '<div class="section-title">' +
                                        '<span class="section-number">' + (index + 1) + '</span>' +
                                        '<span><?php _e('Bagian', 'archeus-booking'); ?></span>' +
                                    '</div>' +
                                    '<button type="button" class="button remove-step"><?php _e('Remove', 'archeus-booking'); ?></button>'
                                );
                            });
                        }
                        
                        function attachRemoveEvents() {
                            // Use event delegation for dynamically added elements
                            jQuery(document).on('click', '.remove-step', function() {
                                jQuery(this).closest('.section-item').remove();
                                updateSectionNumbers();

                                // Show no sections message if no sections remain
                                if (jQuery('.section-item').length === 0) {
                                    jQuery('#no-sections-message').show();
                                }
                            });
                        }
                        
                        jQuery(document).ready(function($) {
                            // Add section button functionality
                            $('#add-section-btn').click(function() {
                                // Hide the no sections message if it exists
                                $('#no-sections-message').hide();

                                // Create section HTML
                                var currentSectionCount = jQuery('.section-item').length;
                                var sectionHtml = '<div class="section-item" style="border: 1px solid #ccc; padding: 15px; margin: 10px 0; background: #f9f9f9;">' +
                            '<h4><div class="section-title">' +
                                '<span class="section-number">' + (currentSectionCount + 1) + '</span>' +
                                '<span><?php _e('Bagian', 'archeus-booking'); ?></span>' +
                            '</div>' +
                            '<button type="button" class="button remove-step"><?php _e('Remove', 'archeus-booking'); ?></button></h4>' +
                                    '<table class="form-table">' +
                                        '<tr>' +
                                            '<th scope="row"><label><?php _e('Tipe Bagian', 'archeus-booking'); ?></label></th>' +
                                            '<td>' +
                                                '<select name="section_types[]" class="section-type-select ab-select ab-dropdown" style="width: 100%;">' +
                                                    '<option value=""><?php _e('-- Pilih Bagian--', 'archeus-booking'); ?></option>' +
                                                    '<option value="calendar"><?php _e('Calendar (Date Selection)', 'archeus-booking'); ?></option>' +
                                                    '<option value="services"><?php _e('Services Selection', 'archeus-booking'); ?></option>' +
                                                    '<option value="time_slot"><?php _e('Time Slot Selection', 'archeus-booking'); ?></option>' +
                                                    '<option value="form"><?php _e('Custom Form', 'archeus-booking'); ?></option>' +
                                                '</select>' +
                                            '</td>' +
                                        '</tr>' +
                                        '<tr class="section-name-row">' +
                                            '<th scope="row"><label><?php _e('Nama Bagian', 'archeus-booking'); ?></label></th>' +
                                            '<td><input type="text" name="section_names[]" class="regular-text" placeholder="<?php _e('Masukkan nama bagian', 'archeus-booking'); ?>"></td>' +
                                        '</tr>' +
                                        '<tr class="section-description-row">' +
                                            '<th scope="row"><label><?php _e('Deskripsi Bagian', 'archeus-booking'); ?></label></th>' +
                                            '<td><textarea name="section_descriptions[]" rows="2" class="large-text" placeholder="<?php _e('Deskripsi opsional untuk bagian ini', 'archeus-booking'); ?>"></textarea></td>' +
                                        '</tr>' +
                                        '<tr class="form-id-row" style="display: none;">' +
                                            '<th scope="row"><label><?php _e('Form', 'archeus-booking'); ?></label></th>' +
                                            '<td>' +
                                                '<select name="section_form_ids[]" class="regular-text ab-select ab-dropdown">' +
                                                    <?php foreach ($forms as $form): ?>
                                                    '<option value="<?php echo esc_attr($form->id); ?>"><?php echo esc_html($form->name); ?></option>' +
                                                    <?php endforeach; ?>
                                                '</select>' +
                                            '</td>' +
                                        '</tr>' +

                                        '<tr>' +
                                            '<th scope="row"><label><?php _e('Wajib?', 'archeus-booking'); ?></label></th>' +
                                            '<td><input type="checkbox" name="section_required[]" value="1"> <?php _e('Apakah bagian ini diwajibkan?', 'archeus-booking'); ?></td>' +
                                        '</tr>' +
                                    '</table>' +
                                '</div>';

                                $('#sections-container').append(sectionHtml);

                                // Initialize custom dropdowns for the newly added section
                                enhanceAbDropdowns($('#sections-container').find('.section-item').last());

                                // Event delegation handles all elements (existing and new), no need to attach events individually
                                // Update section numbers to ensure proper numbering
                                updateSectionNumbers();

                                sectionIndex++;
                            });
                            
                            // Initialize custom dropdowns for existing sections
                            enhanceAbDropdowns();

                            // Attach events for existing sections
                            attachRemoveEvents();
                            attachSectionTypeEvents();
                        });
                        
                        function attachSectionTypeEvents() {
                            // Use event delegation for dynamically added elements
                            jQuery(document).on('change', '.section-type-select', function() {
                                var selectedType = jQuery(this).val();
                                var stepContainer = jQuery(this).closest('.section-item');

                                // Hide all conditional rows
                                stepContainer.find('.form-id-row').hide();

                                // Show relevant rows based on selection
                                if (selectedType === 'form') {
                                    stepContainer.find('.form-id-row').show();
                                }
                            });

                            // Trigger change event to show correct rows for existing selections
                            jQuery('.section-type-select').each(function() {
                                if (jQuery(this).val()) {
                                    jQuery(this).trigger('change');
                                }
                            });
                        }
                    </script>
                </div>
                
                <!-- Booking Flows List -->
                <div class="admin-card">
                    <h2><?php _e('Daftar Flow Pemesanan', 'archeus-booking'); ?></h2>
                    
                    <?php if (empty($booking_flows)): ?>
                        <p><?php _e('Tidak ada flow pemesanan yang dikonfigurasi. Tambahkan flow pemesanan pertama di atas.', 'archeus-booking'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('ID', 'archeus-booking'); ?></th>
                                    <th colspan="2"><?php _e('Nama Flow', 'archeus-booking'); ?></th>
                                    <th colspan="4"><?php _e('Deskripsi', 'archeus-booking'); ?></th>
                                    <th colspan="2"><?php _e('Bagian', 'archeus-booking'); ?></th>
                                    <th colspan="3"><?php _e('Shortcode', 'archeus-booking'); ?></th>
                                    <th colspan="2"><?php _e('Aksi', 'archeus-booking'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($booking_flows as $flow): 
                                    $sections = !empty($flow->sections) ? (is_string($flow->sections) ? json_decode($flow->sections, true) : $flow->sections) : array();
                                    if (empty($sections) && !empty($flow->steps)) { $sections = is_string($flow->steps) ? json_decode($flow->steps, true) : $flow->steps; }
                                    $section_count = is_array($sections) ? count($sections) : 0;
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($flow->id); ?></td>
                                        <td colspan="2"><strong><?php echo esc_html($flow->name); ?></strong></td>
                                        <td colspan="4"><?php echo esc_html($flow->description); ?></td>
                                        <td colspan="2"><?php echo $section_count; ?><?php echo _n('bagian', '', $section_count, 'archeus-booking'); ?></td>
                                        <td colspan="3"><code>[archeus_booking id="<?php echo esc_attr($flow->id); ?>"]</code></td>
                                        <td class="col-actions"  colspan="2">
                                            <div class="action-buttons">
                                                <a href="<?php echo admin_url('admin.php?page=archeus-booking-flow&action=edit&flow_id=' . $flow->id); ?>" class="button button-warning edit-button" title="<?php esc_attr_e('Ubah Booking Flow', 'archeus-booking'); ?>">
                                                    <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                                                    <span class="screen-reader-text"><?php _e('Edit', 'archeus-booking'); ?></span>
                                                </a>
                                                <a href="<?php echo admin_url('admin.php?page=archeus-booking-flow&action=delete&flow_id=' . $flow->id); ?>" class="button button-danger delete-flow" data-flow-id="<?php echo esc_attr($flow->id); ?>" title="<?php esc_attr_e('Hapus Booking Flow', 'archeus-booking'); ?>">
                                                    <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                                                    <span class="screen-reader-text"><?php _e('Hapus', 'archeus-booking'); ?></span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Helper function to render individual section form
     */
    private function render_section_form($section, $index, $forms) {
        $step_type = $section ? $section['type'] : '';
        $step_name = $section ? $section['name'] : '';
        $step_desc = $section && isset($section['section_description']) ? $section['section_description'] : '';
        $step_required = $section ? $section['required'] : 0;
        $step_form_id = $section && isset($section['form_id']) ? $section['form_id'] : '';
        $step_label = $section && isset($section['label']) ? $section['label'] : '';
        ?>
            <!-- <div class="drag-handle">⋮⋮</div> -->
        <div class="section-item">
            <h4>
                <div class="section-title">
                    <span class="section-number"><?php echo $index + 1; ?></span>
                    <span><?php _e('Bagian', 'archeus-booking'); ?></span>
                    <!-- <?php if ($step_type): ?>
                        <span class="section-type-icon <?php echo esc_attr($step_type); ?>" title="<?php echo esc_attr($step_type); ?>">
                            <?php
                            switch ($step_type) {
                                case 'calendar': echo '📅'; break;
                                case 'services': echo '🔧'; break;
                                case 'time_slot': echo '⏰'; break;
                                case 'form': echo '📝'; break;
                                default: echo '❓';
                            }
                            ?>
                        </span>
                    <?php endif; ?> -->
                </div>
                <button type="button" class="button remove-step"><?php _e('Remove', 'archeus-booking'); ?></button>
            </h4>
            <table class="form-table">
                <tr>
                    <th scope="row"><label><?php _e('Section Type', 'archeus-booking'); ?></label></th>
                    <td>
                        <select name="section_types[]" class="section-type-select ab-select" style="width: 100%;">
                            <option value="" <?php selected($step_type, ''); ?>><?php _e('Pilih tipe bagian', 'archeus-booking'); ?></option>
                            <option value="calendar" <?php selected($step_type, 'calendar'); ?>><?php _e('Kalender (Pilihan Tanggal)', 'archeus-booking'); ?></option>
                            <option value="services" <?php selected($step_type, 'services'); ?>><?php _e('Pilihan Layanan', 'archeus-booking'); ?></option>
                            <option value="time_slot" <?php selected($step_type, 'time_slot'); ?>><?php _e('Pilihan Slot Waktu', 'archeus-booking'); ?></option>
                            <option value="form" <?php selected($step_type, 'form'); ?>><?php _e('Formulir Kustom', 'archeus-booking'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr class="section-name-row">
                    <th scope="row"><label><?php _e('Section Name', 'archeus-booking'); ?></label></th>
                    <td><input type="text" name="section_names[]" value="<?php echo esc_attr($step_name); ?>" class="regular-text" placeholder="<?php _e('Masukkan nama bagian', 'archeus-booking'); ?>"></td>
                </tr>
                <tr class="section-description-row">
                    <th scope="row"><label><?php _e('Section Description', 'archeus-booking'); ?></label></th>
                    <td><textarea name="section_descriptions[]" rows="2" class="large-text" placeholder="<?php _e('Deskripsi opsional untuk bagian ini', 'archeus-booking'); ?>"><?php echo esc_textarea($step_desc); ?></textarea></td>
                </tr>
                <tr class="form-id-row" style="<?php echo $step_type === 'form' ? '' : 'display: none;'; ?>">
                    <th scope="row"><label><?php _e('Form', 'archeus-booking'); ?></label></th>
                    <td>
                        <select name="section_form_ids[]" class="regular-text ab-select">
                            <option value=""><?php _e('Select a form', 'archeus-booking'); ?></option>
                            <?php foreach ($forms as $form): ?>
                                <option value="<?php echo esc_attr($form->id); ?>" <?php selected($step_form_id, $form->id); ?>><?php echo esc_html($form->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label><?php _e('Required?', 'archeus-booking'); ?></label></th>
                    <td><input type="checkbox" name="section_required[]" value="1" <?php checked($step_required, 1); ?>> <?php _e('Apakah bagian ini diwajibkan?', 'archeus-booking'); ?></td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Migration tools page for per-flow tables
     */
    public function migration_page() {
        if (!current_user_can('manage_options')) { wp_die(__('You do not have permission.', 'archeus-booking')); }
        $db = new Booking_Database();
        $flows = $db->get_booking_flows();
        $message = '';
        if (isset($_POST['run_migration']) && check_admin_referer('archeus_migrate_flow', 'archeus_migrate_nonce')) {
            $flow_id = intval($_POST['flow_id'] ?? 0);
            $flow = $flow_id ? $db->get_booking_flow($flow_id) : null;
            if ($flow) {
                $ok = $db->migrate_existing_flow_table($flow->name);
                $message = $ok ? __('Migrasi berhasil untuk tabel per-flow.', 'archeus-booking') : __('Migrasi gagal atau tabel tidak ditemukan.', 'archeus-booking');
            } else {
                $message = __('Flow tidak ditemukan.', 'archeus-booking');
            }
        } elseif (isset($_POST['normalize_columns']) && check_admin_referer('archeus_migrate_flow', 'archeus_migrate_nonce')) {
            $flow_id = intval($_POST['flow_id'] ?? 0);
            if ($flow_id) {
                $ok = $db->normalize_custom_columns_by_labels($flow_id);
                $message = $ok ? __('Normalisasi kolom berdasarkan label berhasil.', 'archeus-booking') : __('Normalisasi gagal.', 'archeus-booking');
            } else {
                $message = __('Flow tidak ditemukan.', 'archeus-booking');
            }
        } elseif (isset($_POST['normalize_by_key']) && check_admin_referer('archeus_migrate_flow', 'archeus_migrate_nonce')) {
            $flow_id = intval($_POST['flow_id'] ?? 0);
            if ($flow_id) {
                $ok = $db->normalize_columns_by_keys($flow_id);
                $message = $ok ? __('Normalisasi kolom berdasarkan key berhasil.', 'archeus-booking') : __('Normalisasi gagal.', 'archeus-booking');
            } else {
                $message = __('Flow tidak ditemukan.', 'archeus-booking');
            }
        }
        ?>
        <div class="wrap booking-admin-page">
            <h1><?php _e('Migrasi Tabel Per-Flow', 'archeus-booking'); ?></h1>
            <?php if (!empty($message)): ?><div class="notice notice-info"><p><?php echo esc_html($message); ?></p></div><?php endif; ?>
            <form method="post">
                <?php wp_nonce_field('archeus_migrate_flow', 'archeus_migrate_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Pilih Booking Flow', 'archeus-booking'); ?></th>
                        <td>
                            <select name="flow_id" class="ab-select">
                                <?php foreach ($flows as $f): ?>
                                    <option value="<?php echo esc_attr($f->id); ?>"><?php echo esc_html($f->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Jalankan migrasi untuk memindahkan kolom legacy (custom_*, name, date, special_requests) ke additional_fields, mengisi email jika kosong, dan menghapus kolom legacy.', 'archeus-booking'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Jalankan Migrasi', 'archeus-booking'), 'primary', 'run_migration'); ?>
                <?php submit_button(__('Normalisasi Kolom Berdasarkan Label', 'archeus-booking'), 'secondary', 'normalize_columns'); ?>
                <?php submit_button(__('Normalisasi Kolom Berdasarkan Key', 'archeus-booking'), 'secondary', 'normalize_by_key'); ?>
            </form>
        </div>
        <?php
    }

    // Custom Confirmation Dialog HTML
    public function render_confirmation_dialog() {
        ?>
        <!-- Custom Confirmation Dialog -->
        <div id="booking-confirm-dialog" class="booking-confirm-dialog">
            <div class="booking-confirm-content">
                <div class="booking-confirm-icon">!</div>
                <h3 class="booking-confirm-title">Konfirmasi Hapus</h3>
                <p class="booking-confirm-message">Apakah Anda yakin ingin menghapus item ini?</p>
                <div class="booking-confirm-buttons">
                    <button type="button" class="booking-confirm-btn booking-confirm-cancel" id="confirm-cancel">Batal</button>
                    <button type="button" class="booking-confirm-btn booking-confirm-delete" id="confirm-delete">Hapus</button>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var pendingDeleteUrl = '';
            var pendingDeleteCallback = null;

            // Custom confirmation dialog function
            window.showDeleteConfirm = function(message, deleteUrl, callback) {
                $('#booking-confirm-dialog .booking-confirm-message').text(message);
                pendingDeleteUrl = deleteUrl;
                pendingDeleteCallback = callback;
                $('#booking-confirm-dialog').addClass('active');
                return false;
            };

            // Cancel button
            $('#confirm-cancel').click(function() {
                $('#booking-confirm-dialog').removeClass('active');
                pendingDeleteUrl = '';
                pendingDeleteCallback = null;
            });

            // Delete button
            $('#confirm-delete').click(function() {
                if (pendingDeleteCallback) {
                    pendingDeleteCallback();
                    $('#booking-confirm-dialog').removeClass('active');
                    pendingDeleteCallback = null;
                } else if (pendingDeleteUrl) {
                    window.location.href = pendingDeleteUrl;
                }
            });

            // Close on backdrop click
            $('#booking-confirm-dialog').click(function(e) {
                if (e.target === this) {
                    $(this).removeClass('active');
                    pendingDeleteUrl = '';
                    pendingDeleteCallback = null;
                }
            });

            // Close on ESC key
            $(document).keydown(function(e) {
                if (e.key === 'Escape' && $('#booking-confirm-dialog').hasClass('active')) {
                    $('#booking-confirm-dialog').removeClass('active');
                    pendingDeleteUrl = '';
                    pendingDeleteCallback = null;
                }
            });

            // Replace all delete confirmation links
            $('.delete-form').each(function() {
                // Only apply to forms list, not form builder fields
                if (!$(this).hasClass('remove-field')) {
                    var deleteUrl = $(this).attr('href');
                    $(this).removeAttr('onclick');
                    $(this).attr('href', '#');
                    $(this).click(function(e) {
                        e.preventDefault();
                        showDeleteConfirm('<?php _e('Yakin ingin menghapus formulir ini?', 'archeus-booking'); ?>', deleteUrl);
                    });
                }
            });

            $('.delete-service').each(function() {
                var deleteUrl = $(this).attr('href');
                $(this).removeAttr('onclick');
                $(this).attr('href', '#');
                $(this).click(function(e) {
                    e.preventDefault();
                    showDeleteConfirm('<?php _e('Yakin ingin menghapus layanan ini?', 'archeus-booking'); ?>', deleteUrl);
                });
            });

          });
        </script>
        <?php
    }
}












