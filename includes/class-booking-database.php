<?php
class Booking_Database {

    private $table_name;
    private $table_prefix = 'archeus_';

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . $this->table_prefix . 'booking';

        add_action('plugins_loaded', array($this, 'create_tables'));

        // Run automatic cleanup on initialization (with probability to avoid performance impact)
        $this->run_automatic_cleanup();
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Create main unified bookings table
        $bookings_table = $wpdb->prefix . $this->table_prefix . 'booking';
        $bookings_sql = "CREATE TABLE IF NOT EXISTS {$bookings_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            customer_name varchar(255) NOT NULL,
            customer_email varchar(255) NOT NULL,
            booking_date date NOT NULL,
            booking_time varchar(50) NOT NULL,
            service_type varchar(255) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            flow_id int(11) NULL,
            flow_name varchar(255) NULL,
            fields longtext NULL,
            payload longtext NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_date (booking_date),
            KEY status (status),
            KEY flow_id (flow_id),
            KEY service_type (service_type)
        ) $charset_collate;";

        $forms_table = $wpdb->prefix . $this->table_prefix . 'booking_forms';
        $forms_sql = "CREATE TABLE IF NOT EXISTS {$forms_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(100) NOT NULL,
            description text NULL,
            fields longtext NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY is_active (is_active)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($bookings_sql);
        dbDelta($forms_sql);
        
        // Create services table
        $this->create_services_table();
        
        // Create schedules table
        $this->create_schedules_table();
        
        // Insert default form if none exists (without reserved name/email fields)
        $default_form_count = $wpdb->get_var("SELECT COUNT(*) FROM {$forms_table}");
        if ($default_form_count == 0) {
            $wpdb->insert(
                $forms_table,
                array(
                    'name' => 'Default Booking Form',
                    'slug' => 'default-booking',
                    'description' => 'Default booking form for general appointments',
                    'fields' => maybe_serialize(array(
                        'booking_date' => array('label' => 'Booking Date', 'type' => 'date', 'required' => 1, 'placeholder' => ''),
                        'booking_time' => array('label' => 'Booking Time', 'type' => 'time', 'required' => 0, 'placeholder' => ''),
                        'service_type' => array('label' => 'Service Type', 'type' => 'select', 'required' => 1, 'placeholder' => '-- Select Service --')
                    )),
                    'is_active' => 1
                ),
                array('%s', '%s', '%s', '%s', '%d')
            );
        }
    }

    /**
     * Build sanitized per-flow table name using site prefix and archeus_ prefix.
     */
    public function get_flow_table_name($flow_name) {
        global $wpdb;
        $slug = sanitize_title($flow_name);
        $slug = str_replace('-', '_', $slug);
        if ($slug === '') {
            $slug = 'flow';
        }

        // Special handling for installations with custom WordPress prefix
        $table_name = $wpdb->prefix . $this->table_prefix . $slug;

        // Check if WordPress prefix already contains 'archeus'
        if (strpos($wpdb->prefix, 'archeus') !== false) {
            // Avoid double prefix - use single prefix
            $table_name = $wpdb->prefix . $slug;
        }

        return $table_name;
    }

    /**
     * Ensure per-flow table exists. Stores full submission as JSON plus key fields.
     */
    public function ensure_flow_table($flow_name) {
        global $wpdb;
        $table = $this->get_flow_table_name($flow_name);
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            flow_id int(11) NULL,
            booking_date date NULL,
            booking_time varchar(50) NULL,
            service_type varchar(255) NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            payload longtext NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY flow_id (flow_id),
            KEY booking_date (booking_date),
            KEY service_type (service_type),
            KEY status (status)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Ensure customer fields columns exist
        $this->ensure_customer_columns_exist($table);

        return $table;
    }

    /**
     * Sanitize array key into a safe MySQL column name.
     */
    private function sanitize_column_name($key) {
        $col = strtolower(sanitize_title($key));
        $col = str_replace('-', '_', $col);
        if ($col === '' || ctype_digit(substr($col, 0, 1))) {
            $col = 'field_' . md5($key);
        }
        return $col;
    }

    /**
     * Ensure customer_name and customer_email columns exist in table for legacy data
     * This is only needed for existing tables that might not have these columns
     */
    private function ensure_customer_columns_exist($table) {
        global $wpdb;

        $existing = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
        if (!is_array($existing)) { $existing = array(); }

        $added_columns = false;

        // Add customer_name column if not exists (for legacy support)
        if (!in_array('customer_name', $existing)) {
            $result = $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `customer_name` LONGTEXT NULL DEFAULT NULL");
            error_log("Added customer_name column to table {$table} for legacy support. Result: " . ($result ? 'success' : 'failed') . ' Error: ' . $wpdb->last_error);
            $added_columns = true;
        }

        // Add customer_email column if not exists (for legacy support)
        if (!in_array('customer_email', $existing)) {
            $result = $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `customer_email` LONGTEXT NULL DEFAULT NULL");
            error_log("Added customer_email column to table {$table} for legacy support. Result: " . ($result ? 'success' : 'failed') . ' Error: ' . $wpdb->last_error);
            $added_columns = true;
        }

        if ($added_columns) {
            // Log final column list for debugging
            $final_columns = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
            error_log("Final columns in table {$table} after adding customer fields: " . print_r($final_columns, true));
        }
    }

    /**
     * Ensure columns exist for provided associative data on per-flow table.
     */
    public function ensure_flow_table_columns($flow_name, $data) {
        global $wpdb;
        $table = $this->ensure_flow_table($flow_name);

        // Ensure customer columns exist
        $this->ensure_customer_columns_exist($table);

        $existing = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
        if (!is_array($existing)) { $existing = array(); }

        // Also ensure core columns exist (excluding id which is created by default)
        $core_columns = array('flow_id','booking_date','booking_time','service_type','status','payload','created_at','updated_at');
        foreach ($core_columns as $col) {
            if (!in_array($col, $existing)) {
                $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` LONGTEXT NULL");
                $existing[] = $col;
            }
        }

        // Add dynamic fields from data
        foreach ($data as $key => $value) {
            $col = $this->sanitize_column_name($key);
            if (in_array($col, $existing, true) || in_array($col, $core_columns, true)) {
                continue;
            }
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` LONGTEXT NULL");
            $existing[] = $col;
        }
        return $table;
    }

    /**
     * Rename columns in a per-flow table based on provided mapping old => new.
     * If destination exists, merge values and drop old.
     */
    public function rename_columns_for_flow($flow_id, $rename_map) {
        if (empty($rename_map) || !is_array($rename_map)) return false;
        global $wpdb;
        $flow = $this->get_booking_flow(intval($flow_id));
        if (!$flow) return false;
        $table = $this->get_flow_table_name($flow->name);
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) { return false; }

        $existing = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
        if (!is_array($existing)) { $existing = array(); }
        $reserved = array('id','customer_name','customer_email','booking_date','booking_time','service_type','status','payload','created_at','updated_at');

        foreach ($rename_map as $old => $new) {
            $old = $this->sanitize_column_name($old);
            $new = $this->sanitize_column_name($new);
            if ($old === '' || $new === '' || $old === $new) continue;
            $has_old = in_array($old, $existing, true);
            $has_new = in_array($new, $existing, true);
            if (!$has_old) continue;
            if ($has_new || in_array($new, $reserved, true)) {
                // Merge data then drop old
                $wpdb->query("UPDATE `{$table}` SET `{$new}` = COALESCE(`{$new}`, `{$old}`) WHERE `{$old}` IS NOT NULL AND `{$old}` <> ''");
                // Drop old column only if it's not a reserved one
                if (!in_array($old, $reserved, true)) {
                    $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN `{$old}`");
                    $existing = array_diff($existing, array($old));
                }
            } else {
                // Rename column
                $wpdb->query("ALTER TABLE `{$table}` CHANGE `{$old}` `{$new}` LONGTEXT NULL");
                // Update existing list
                $existing = array_diff($existing, array($old));
                $existing[] = $new;
            }
        }
        return true;
    }

    /**
     * Insert booking submission into unified table.
     */
    public function insert_flow_submission($flow_name, $data) {
        global $wpdb;

        // Extract core fields
        $customer_name = isset($data['customer_name']) ? sanitize_text_field($data['customer_name']) : '';
        $customer_email = isset($data['customer_email']) ? sanitize_email($data['customer_email']) : '';
        $booking_date = isset($data['booking_date']) ? sanitize_text_field($data['booking_date']) : '';
        $booking_time = isset($data['booking_time']) ? sanitize_text_field($data['booking_time']) : '';
        $service_type = isset($data['service_type']) ? sanitize_text_field($data['service_type']) : '';
        $status = isset($data['status']) ? sanitize_text_field($data['status']) : 'pending';

        // Get flow_id
        $flow_id = null;
        $flows = $this->get_booking_flows();
        if (is_array($flows)) {
            foreach ($flows as $f) {
                if (isset($f->name) && $f->name === $flow_name) {
                    $flow_id = intval($f->id);
                    break;
                }
            }
        }

        // Extract custom fields (exclude core fields)
        $core_fields = array('customer_name', 'customer_email', 'booking_date', 'booking_time', 'service_type', 'status', 'flow_id', 'flow_name');
        $custom_fields = array();
        foreach ($data as $key => $value) {
            if (!in_array($key, $core_fields)) {
                $custom_fields[$key] = $value;
            }
        }

        // Prepare row data
        $row = array(
            'customer_name'  => $customer_name,
            'customer_email' => $customer_email,
            'booking_date'   => $booking_date,
            'booking_time'   => $booking_time,
            'service_type'   => $service_type,
            'status'         => $status,
            'flow_id'        => $flow_id,
            'flow_name'      => $flow_name,
            'fields'         => !empty($custom_fields) ? wp_json_encode($custom_fields) : null,
            'payload'        => wp_json_encode($data)
        );

        // Build formats
        $formats = array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s');

        // Enable error reporting for debugging
        $wpdb->show_errors = true;
        $wpdb->suppress_errors = false;

        $res = $wpdb->insert($this->table_name, $row, $formats);

        if ($res === false) {
            // Log the error for debugging
            error_log('Booking insertion failed. Table: ' . $this->table_name . '. Error: ' . $wpdb->last_error);
            error_log('Data: ' . print_r($row, true));
        }

        return $res !== false ? $wpdb->insert_id : false;
    }

    /**
     * Insert a new booking
     */
    public function insert_booking($data) {
        // Insert via default flow name for backward-compatibility
        $flow_name = 'basic-booking';
        // Normalize fields to match expected structure
        $payload = array(
            'customer_name' => isset($data['customer_name']) ? sanitize_text_field($data['customer_name']) : '',
            'customer_email' => isset($data['customer_email']) ? sanitize_email($data['customer_email']) : '',
            'booking_date' => isset($data['booking_date']) ? sanitize_text_field($data['booking_date']) : '',
            'booking_time' => isset($data['booking_time']) ? sanitize_text_field($data['booking_time']) : '',
            'service_type' => isset($data['service_type']) ? sanitize_text_field($data['service_type']) : '',
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'pending',
        );
        if (isset($data['additional_fields'])) {
            $payload['additional_fields'] = $data['additional_fields'];
        }
        $insert_id = $this->insert_flow_submission($flow_name, $payload);
        return $insert_id;
    }

    /**
     * Get all bookings from unified table
     */
    public function get_bookings($args = array()) {
        global $wpdb;

        $defaults = array(
            'limit' => 50,
            'offset' => 0,
            'status' => '',
            'flow_id' => '',
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT * FROM {$this->table_name}";
        $params = array();
        $clauses = array();

        if (!empty($args['status'])) {
            $clauses[] = "status = %s";
            $params[] = $args['status'];
        }

        if (!empty($args['flow_id'])) {
            $clauses[] = "flow_id = %d";
            $params[] = intval($args['flow_id']);
        }

        if (!empty($clauses)) {
            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        $sql .= ' ORDER BY ' . sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);

        if (!empty($args['limit'])) {
            $sql .= ' LIMIT %d OFFSET %d';
            $params[] = intval($args['limit']);
            $params[] = intval($args['offset']);
        }

        if (!empty($params)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            $results = $wpdb->get_results($sql);
        }

        // Decode custom fields for each booking
        foreach ($results as &$result) {
            if (!empty($result->fields)) {
                $custom_fields = json_decode($result->fields, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($custom_fields)) {
                    // Add custom fields as properties to the result object
                    foreach ($custom_fields as $key => $value) {
                        $result->$key = $value;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Get booking by ID from unified table
     */
    public function get_booking($id) {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", intval($id)));

        if ($row) {
            // Decode custom fields
            if (!empty($row->fields)) {
                $custom_fields = json_decode($row->fields, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($custom_fields)) {
                    foreach ($custom_fields as $key => $value) {
                        $row->$key = $value;
                    }
                }
            }

            // Compatibility fields
            if (!isset($row->schedule_id)) {
                $row->schedule_id = null;
            }
        }

        return $row;
    }

    /**
     * Update customer field (customer_name or customer_email) for a booking
     */
    private function update_customer_field($booking_id, $flow_name, $field_name, $field_value) {
        global $wpdb;
        $table = $this->get_flow_table_name($flow_name);

        // Ensure column exists
        $this->ensure_flow_table_columns($flow_name, array($field_name => $field_value));

        $updated = $wpdb->update(
            $table,
            array($field_name => $field_value),
            array('id' => intval($booking_id)),
            array('%s'),
            array('%d')
        );

        return $updated !== false;
    }

    /**
     * Migrate customer data from direct columns in table
     */
    private function migrate_from_direct_columns(&$row, $flow_name) {
        global $wpdb;
        $table = $this->get_flow_table_name($flow_name);

        // Get all columns in this table
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
        if (!is_array($columns)) { $columns = array(); }

        $updated = false;

        // Check for name fields
        if (empty($row->customer_name)) {
            $name_fields = array('nama_lengkap', 'nama', 'name', 'full_name', 'nama lengkap', 'full name');
            foreach ($name_fields as $field) {
                if (in_array($field, $columns) && !empty($row->$field)) {
                    $row->customer_name = $row->$field;
                    $this->update_customer_field($row->id, $flow_name, 'customer_name', $row->customer_name);
                    $updated = true;
                    break;
                }
            }
        }

        // Check for email fields
        if (empty($row->customer_email)) {
            $email_fields = array('email', 'email_address', 'alamat_email', 'e-mail');
            foreach ($email_fields as $field) {
                if (in_array($field, $columns) && !empty($row->$field)) {
                    $row->customer_email = $row->$field;
                    $this->update_customer_field($row->id, $flow_name, 'customer_email', $row->customer_email);
                    $updated = true;
                    break;
                }
            }
        }

        return $updated;
    }

    /**
     * Update booking status
     */
    public function update_booking_status($booking_id, $status) {
        global $wpdb;
        $updated = $wpdb->update(
            $this->table_name,
            array('status' => sanitize_text_field($status)),
            array('id' => intval($booking_id)),
            array('%s'),
            array('%d')
        );
        return $updated !== false;
    }

    /**
     * Delete booking
     */
    public function delete_booking($booking_id) {
        global $wpdb;
        $booking = $this->get_booking($booking_id);
        if (!$booking) { return false; }

        // Check if this booking has a blocking status and needs to release schedule slot
        $blocking_statuses = get_option('booking_blocking_statuses', array('approved', 'completed'));
        $is_blocking = in_array($booking->status, $blocking_statuses, true);

        // Release schedule slot if this booking was blocking
        if ($is_blocking && !empty($booking->schedule_id)) {
            $this->update_schedule_bookings($booking->schedule_id, -1);
        }

        $deleted = $wpdb->delete(
            $this->table_name,
            array('id' => intval($booking_id)),
            array('%d')
        );

        return $deleted !== false;
    }
    
    /**
     * Clean up expired availability data (real-time)
     * Deletes availability and schedule data for dates that have passed
     * Preserves booking data for historical records
     */
    public function cleanup_expired_availability() {
        global $wpdb;

        $today = current_time('Y-m-d');
        $cleanup_count = 0;

        // Clean up availability table
        $availability_table = $wpdb->prefix . 'archeus_booking_availability';
        $availability_deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$availability_table} WHERE date < %s",
                $today
            )
        );
        $cleanup_count += $availability_deleted ?: 0;

        // Clean up schedules table
        $schedules_table = $wpdb->prefix . $this->table_prefix . 'booking_schedules';
        $schedules_deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$schedules_table} WHERE date < %s",
                $today
            )
        );
        $cleanup_count += $schedules_deleted ?: 0;

        // Log cleanup activity (optional)
        if ($cleanup_count > 0) {
            error_log("Archeus Booking: Cleaned up {$cleanup_count} expired availability records on " . current_time('mysql'));
        }

        return $cleanup_count;
    }

    /**
     * Run automatic cleanup with randomized probability to avoid performance issues
     *
     * @return int|false Number of records cleaned up or false if cleanup was skipped
     */
    public function run_automatic_cleanup() {
        // Run cleanup with 10% probability on each call to avoid performance impact
        if (rand(1, 10) !== 1) {
            return false;
        }

        return $this->cleanup_expired_availability();
    }

    /**
     * Force cleanup (for manual triggers)
     *
     * @return int Number of records cleaned up
     */
    public function force_cleanup() {
        return $this->cleanup_expired_availability();
    }

    /**
     * Get all forms
     */
    public function get_forms() {
        global $wpdb;
        $forms_table = $wpdb->prefix . $this->table_prefix . 'booking_forms';
        
        $forms = $wpdb->get_results("SELECT * FROM {$forms_table} ORDER BY name ASC");
        return $forms;
    }
    
    /**
     * Get form by ID
     */
    public function get_form($form_id) {
        global $wpdb;
        $forms_table = $wpdb->prefix . $this->table_prefix . 'booking_forms';
        
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$forms_table} WHERE id = %d",
            $form_id
        ));
        
        return $form;
    }
    
    /**
     * Get form by slug
     */
    public function get_form_by_slug($slug) {
        global $wpdb;
        $forms_table = $wpdb->prefix . $this->table_prefix . 'booking_forms';
        
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$forms_table} WHERE slug = %s",
            $slug
        ));
        
        return $form;
    }
    
    /**
     * Create new form
     */
    public function create_form($name, $slug, $description = '', $fields = array()) {
        global $wpdb;
        $forms_table = $wpdb->prefix . $this->table_prefix . 'booking_forms';
        
        $result = $wpdb->insert(
            $forms_table,
            array(
                'name' => sanitize_text_field($name),
                'slug' => sanitize_title($slug),
                'description' => sanitize_textarea_field($description),
                'fields' => maybe_serialize($fields),
                'is_active' => 1
            ),
            array('%s', '%s', '%s', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update form
     */
    public function update_form($form_id, $name, $slug, $description = '', $fields = array(), $is_active = 1) {
        global $wpdb;
        $forms_table = $wpdb->prefix . $this->table_prefix . 'booking_forms';

        // Debug log before update
        error_log('Updating form ID: ' . $form_id);
        error_log('Fields being saved: ' . print_r($fields, true));

        $result = $wpdb->update(
            $forms_table,
            array(
                'name' => sanitize_text_field($name),
                'slug' => sanitize_title($slug),
                'description' => sanitize_textarea_field($description),
                'fields' => maybe_serialize($fields),
                'is_active' => intval($is_active)
            ),
            array('id' => intval($form_id)),
            array('%s', '%s', '%s', '%s', '%d'),
            array('%d')
        );

        // Debug log result
        error_log('Update result: ' . ($result !== false ? 'success' : 'failed'));

        return $result !== false;
    }
    
    /**
     * Delete form
     */
    public function delete_form($form_id) {
        global $wpdb;
        $forms_table = $wpdb->prefix . $this->table_prefix . 'booking_forms';
        
        // First check if there are submissions associated with this form across all flows
        $booking_count = 0;
        $flows = $this->get_booking_flows();
        foreach ($flows as $flow) {
            $table = $this->get_flow_table_name($flow->name);
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($exists !== $table) { continue; }
            $c = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE form_id = %d", intval($form_id)));
            $booking_count += intval($c);
        }
        
        if ($booking_count > 0) {
            // Soft delete - deactivate the form instead
            $result = $wpdb->update(
                $forms_table,
                array('is_active' => 0),
                array('id' => intval($form_id)),
                array('%d'),
                array('%d')
            );
        } else {
            // Hard delete if no bookings
            $result = $wpdb->delete(
                $forms_table,
                array('id' => intval($form_id)),
                array('%d')
            );
        }
        
        return $result !== false;
    }
    
    /**
     * Cleanup old bookings (older than 30 days)
     */
    public function cleanup_old_bookings() {
        global $wpdb;
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        $flows = $this->get_booking_flows();
        foreach ($flows as $flow) {
            $table = $this->get_flow_table_name($flow->name);
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($exists !== $table) { continue; }
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table} WHERE created_at < %s AND status = %s",
                $thirty_days_ago,
                'pending'
            ));
        }
    }

    /**
     * Get booking count by status from unified table
     */
    public function get_booking_count_by_status($status = '') {
        global $wpdb;

        if (!empty($status)) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s", $status));
        } else {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        }

        return intval($count);
    }

    /**
     * Get booking counts (total and per status), optionally for a single flow.
     */
    public function get_booking_counts($flow_id = 0) {
        global $wpdb;

        $where_clause = '';
        $params = array();

        if ($flow_id) {
            $where_clause = 'WHERE flow_id = %d';
            $params[] = intval($flow_id);
        }

        $counts = array(
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'completed' => 0,
            'rejected' => 0,
        );

        // Base query for total count
        $sql = "SELECT COUNT(*) FROM {$this->table_name} {$where_clause}";
        if (!empty($params)) {
            $counts['total'] = intval($wpdb->get_var($wpdb->prepare($sql, $params)));
        } else {
            $counts['total'] = intval($wpdb->get_var($sql));
        }

        // Status counts
        $status_where = !empty($where_clause) ? $where_clause . ' AND status = %s' : 'WHERE status = %s';

        foreach (array('pending', 'approved', 'completed', 'rejected') as $status) {
            $status_sql = "SELECT COUNT(*) FROM {$this->table_name} {$status_where}";
            $status_params = !empty($params) ? array_merge($params, array($status)) : array($status);
            $counts[$status] = intval($wpdb->get_var($wpdb->prepare($status_sql, $status_params)));
        }

        return $counts;
    }
    
    /**
     * Create services table
     */
    public function create_services_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $services_table = $wpdb->prefix . $this->table_prefix . 'booking_services';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$services_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text NULL,
            price decimal(10,2) DEFAULT 0.00,
            duration int(11) DEFAULT 30,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Insert default services if none exists
        $default_service_count = $wpdb->get_var("SELECT COUNT(*) FROM {$services_table}");
        if ($default_service_count == 0) {
            $wpdb->insert(
                $services_table,
                array(
                    'name' => 'General Checkup',
                    'description' => 'Comprehensive health examination for your pet',
                    'price' => 150000,
                    'duration' => 30
                ),
                array('%s', '%s', '%f', '%d')
            );
            
            $wpdb->insert(
                $services_table,
                array(
                    'name' => 'Vaccination',
                    'description' => 'Vaccination services for dogs and cats',
                    'price' => 100000,
                    'duration' => 15
                ),
                array('%s', '%s', '%f', '%d')
            );

            $wpdb->insert(
                $services_table,
                array(
                    'name' => 'Grooming',
                    'description' => 'Professional grooming and hygiene services',
                    'price' => 200000,
                    'duration' => 60
                ),
                array('%s', '%s', '%f', '%d')
            );
        }
        
        // Create booking flows table
        $this->create_booking_flows_table();
    }
    
    /**
     * Create schedules table
     */
    public function create_schedules_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $schedules_table = $wpdb->prefix . $this->table_prefix . 'booking_schedules';
        
        // New schema: schedules are independent from services
        $sql = "CREATE TABLE IF NOT EXISTS {$schedules_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            max_capacity int(11) DEFAULT 1,
            current_bookings int(11) DEFAULT 0,
            is_available tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY date (date),
            KEY start_time (start_time),
            KEY end_time (end_time),
            KEY is_available (is_available),
            UNIQUE KEY unique_schedule_slot (date, start_time, end_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Migration: drop legacy service_id and old unique index if present
        // Note: best-effort; ignore failures to keep compatibility
        try {
            $has_service_id = $wpdb->get_var("SHOW COLUMNS FROM `{$schedules_table}` LIKE 'service_id'");
            if (!empty($has_service_id)) {
                // Drop unique index that references service_id if exists
                $idx = $wpdb->get_results("SHOW INDEX FROM `{$schedules_table}` WHERE Key_name = 'unique_schedule_slot'");
                if (!empty($idx)) {
                    // Attempt to drop before recreating without service_id
                    $wpdb->query("ALTER TABLE `{$schedules_table}` DROP INDEX `unique_schedule_slot`");
                }
                // Drop service_id column
                $wpdb->query("ALTER TABLE `{$schedules_table}` DROP COLUMN `service_id`");
                // Recreate unique index without service_id
                $wpdb->query("ALTER TABLE `{$schedules_table}` ADD UNIQUE KEY `unique_schedule_slot` (`date`,`start_time`,`end_time`)");
            }
        } catch (Exception $e) {
            // Silently ignore; environments may restrict ALTER
        }
        
        // Default schedules will be created based on calendar availability
    }
    
    /**
     * Get all services
     */
    public function get_services() {
        global $wpdb;
        $services_table = $wpdb->prefix . $this->table_prefix . 'booking_services';
        
        $services = $wpdb->get_results("SELECT * FROM {$services_table} ORDER BY name ASC");
        return $services;
    }
    
    /**
     * Get service by ID
     */
    public function get_service($service_id) {
        global $wpdb;
        $services_table = $wpdb->prefix . $this->table_prefix . 'booking_services';
        
        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$services_table} WHERE id = %d",
            $service_id
        ));
        
        return $service;
    }
    
    /**
     * Create new service
     */
    public function create_service($name, $description, $price, $duration) {
        global $wpdb;
        $services_table = $wpdb->prefix . $this->table_prefix . 'booking_services';

        $result = $wpdb->insert(
            $services_table,
            array(
                'name' => sanitize_text_field($name),
                'description' => sanitize_textarea_field($description),
                'price' => floatval($price),
                'duration' => intval($duration)
            ),
            array('%s', '%s', '%f', '%d')
        );
        
        // $wpdb->insert returns false on error, or the number of rows affected (1 on success)
        // So if the result is false, there was an error
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update service
     */
    public function update_service($service_id, $name, $description, $price, $duration) {
        global $wpdb;
        $services_table = $wpdb->prefix . $this->table_prefix . 'booking_services';

        $result = $wpdb->update(
            $services_table,
            array(
                'name' => sanitize_text_field($name),
                'description' => sanitize_textarea_field($description),
                'price' => floatval($price),
                'duration' => intval($duration),
                'updated_at' => current_time('mysql')
            ),
            array('id' => intval($service_id)),
            array('%s', '%s', '%f', '%d', '%s'),
            array('%d')
        );

        // $wpdb->update returns false on error or the number of rows affected (0 or 1+)
        // We consider it successful if the operation didn't fail (even if no changes were needed)
        return $result !== false;
    }
    
    /**
     * Delete service
     */
    public function delete_service($service_id) {
        global $wpdb;
        $services_table = $wpdb->prefix . $this->table_prefix . 'booking_services';
        
        $result = $wpdb->delete(
            $services_table,
            array('id' => intval($service_id)),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get all schedules
     */
    public function get_schedules($args = array()) {
        global $wpdb;
        $schedules_table = $wpdb->prefix . $this->table_prefix . 'booking_schedules';
        
        $defaults = array(
            'service_id' => 0,
            'date' => '',
            'is_available' => 1,
            'limit' => 100,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clause = array();
        $where_values = array();
        
        if (!empty($args['service_id'])) {
            $where_clause[] = "service_id = %d";
            $where_values[] = $args['service_id'];
        }
        
        if (!empty($args['date'])) {
            $where_clause[] = "date = %s";
            $where_values[] = $args['date'];
        }
        
        if (isset($args['is_available'])) {
            $where_clause[] = "is_available = %d";
            $where_values[] = $args['is_available'];
        }
        
        $where_sql = '';
        if (!empty($where_clause)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clause);
        }
        
        $limit_sql = "LIMIT %d OFFSET %d";
        $limit_values = array($args['limit'], $args['offset']);
        
        $query = "SELECT * FROM {$schedules_table} {$where_sql} ORDER BY date ASC, start_time ASC";
        if (!empty($limit_values)) {
            $query .= " " . $limit_sql;
        }
        
        $values = array_merge($where_values, $limit_values);
        
        $results = $wpdb->get_results($wpdb->prepare($query, $values));
        
        return $results;
    }
    
    /**
     * Get schedule by ID
     */
    public function get_schedule($schedule_id) {
        global $wpdb;
        $schedules_table = $wpdb->prefix . $this->table_prefix . 'booking_schedules';
        
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$schedules_table} WHERE id = %d",
            $schedule_id
        ));
        
        return $schedule;
    }

    
    /**
     * Get available schedules for a specific date and service
     */
    public function get_available_schedules($date, $service_id = null) {
        global $wpdb;
        $schedules_table = $wpdb->prefix . $this->table_prefix . 'booking_schedules';
        
        // Ensure schedules exist for this date by generating them if needed
        if (class_exists('Time_Slots_Manager')) {
            $time_slots_manager = new Time_Slots_Manager();
            // Ensure schedules exist for this date (service-agnostic)
            $time_slots_manager->generate_schedules_for_date($date);
        }
        
        $where_clause = array(
            "date = %s",
            "is_available = 1"
        );
        $where_values = array($date);
        
        // Time slots are independent from services
        
        $where_sql = 'WHERE ' . implode(' AND ', $where_clause);
        
        $query = "SELECT * FROM {$schedules_table} {$where_sql} ORDER BY start_time ASC";
        
        $all_schedules = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        // Get calendar daily limit and booked count
        $booking_calendar = new Booking_Calendar();
        $date_availability = $booking_calendar->get_availability_with_bookings($date);
        
        // If the date doesn't have specific availability settings, use defaults
        $daily_limit = isset($date_availability['availability']->daily_limit) ? $date_availability['availability']->daily_limit : 5;
        $booked_count = isset($date_availability['booked_count']) ? $date_availability['booked_count'] : 0;
        
        // Calculate remaining capacity based on daily limit (do not hard-limit list)
        $remaining_bookings_for_date = max(0, $daily_limit - $booked_count);
        
        // Filter schedules to only return those that are still bookable; do not cap to daily limit
        $available_slots = array();
        foreach ($all_schedules as $schedule) {
            // Check if this specific time slot still has available capacity
            $slot_remaining = $schedule->max_capacity - $schedule->current_bookings;

            // Check if there are any existing bookings with blocking statuses for this time slot
            $blocking_statuses = get_option('booking_blocking_statuses', array('approved', 'completed'));
            $existing_blocking_bookings = 0;

            // Check for bookings with blocking statuses for this specific time slot
            $flows = $this->get_booking_flows();
            foreach ($flows as $flow) {
                $table = $this->get_flow_table_name($flow->name);
                // Check if table exists
                $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
                if ($table_exists !== $table) continue;

                // Check for bookings with blocking statuses for this specific time slot
                $placeholders = implode(',', array_fill(0, count($blocking_statuses), '%s'));
                $sql = "SELECT COUNT(*) FROM {$table} WHERE booking_date = %s AND booking_time = %s AND status IN ($placeholders)";
                $params = array_merge(array($date, $schedule->start_time . '-' . $schedule->end_time), $blocking_statuses);

                $count = $wpdb->get_var($wpdb->prepare($sql, $params));
                $existing_blocking_bookings += intval($count);
            }

            // If slot still has capacity AND no blocking bookings
            if ($slot_remaining > 0 && $existing_blocking_bookings == 0) {
                // Add this time slot to available slots
                $available_slots[] = $schedule;
            }
        }
        
        return $available_slots;
    }
    
    /**
     * Create new schedule
     */
    public function create_schedule($service_id, $date, $start_time, $end_time, $max_capacity = 1, $is_available = 1) {
        global $wpdb;
        $schedules_table = $wpdb->prefix . $this->table_prefix . 'booking_schedules';
        
        // Check if schedule slot already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$schedules_table} WHERE date = %s AND start_time = %s AND end_time = %s",
            $date, $start_time, $end_time
        ));
        
        if ($existing) {
            return false; // Schedule slot already exists
        }
        
        
        $result = $wpdb->insert(
            $schedules_table,
            array(
                'date' => sanitize_text_field($date),
                'start_time' => sanitize_text_field($start_time),
                'end_time' => sanitize_text_field($end_time),
                'max_capacity' => intval($max_capacity),
                'current_bookings' => 0,
                'is_available' => intval($is_available)
            ),
            array('%s', '%s', '%s', '%d', '%d', '%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update schedule
     */
    public function update_schedule($schedule_id, $service_id, $date, $start_time, $end_time, $max_capacity, $is_available = 1) {
        global $wpdb;
        $schedules_table = $wpdb->prefix . $this->table_prefix . 'booking_schedules';
        
        // Get the current schedule to compare date
        $current_schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT date FROM {$schedules_table} WHERE id = %d",
            $schedule_id
        ));
        
        if (!$current_schedule) {
            return false; // Schedule doesn't exist
        }
        
        
        $result = $wpdb->update(
            $schedules_table,
            array(
                'date' => sanitize_text_field($date),
                'start_time' => sanitize_text_field($start_time),
                'end_time' => sanitize_text_field($end_time),
                'max_capacity' => intval($max_capacity),
                'is_available' => intval($is_available),
                'updated_at' => current_time('mysql')
            ),
            array('id' => intval($schedule_id)),
            array('%s', '%s', '%s', '%d', '%d', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Update schedule bookings count
     */
    public function update_schedule_bookings($schedule_id, $change) {
        global $wpdb;
        $schedules_table = $wpdb->prefix . $this->table_prefix . 'booking_schedules';

        // First check if schedule exists and get current bookings
        $schedule = $wpdb->get_row($wpdb->prepare(
            "SELECT current_bookings FROM {$schedules_table} WHERE id = %d",
            intval($schedule_id)
        ));

        if (!$schedule) {
            return false;
        }

        // Calculate new value to prevent negative bookings
        $new_bookings = max(0, intval($schedule->current_bookings) + intval($change));

        $result = $wpdb->update(
            $schedules_table,
            array(
                'current_bookings' => $new_bookings,
                'updated_at' => current_time('mysql')
            ),
            array('id' => intval($schedule_id)),
            array('%d', '%s'),
            array('%d')
        );

        return $result !== false;
    }
    
    /**
     * Delete schedule
     */
    public function delete_schedule($schedule_id) {
        global $wpdb;
        $schedules_table = $wpdb->prefix . $this->table_prefix . 'booking_schedules';
        
        $result = $wpdb->delete(
            $schedules_table,
            array('id' => intval($schedule_id)),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Toggle schedule availability
     */
    public function toggle_schedule_availability($schedule_id) {
        global $wpdb;
        $schedules_table = $wpdb->prefix . $this->table_prefix . 'booking_schedules';
        
        // Get current availability
        $current = $wpdb->get_var($wpdb->prepare(
            "SELECT is_available FROM {$schedules_table} WHERE id = %d",
            $schedule_id
        ));
        
        if ($current === null) {
            return false;
        }
        
        $new_status = $current ? 0 : 1;
        
        $result = $wpdb->update(
            $schedules_table,
            array('is_available' => $new_status),
            array('id' => intval($schedule_id)),
            array('%d'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Create booking flows table
     */
    private function create_booking_flows_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $flows_table = $wpdb->prefix . $this->table_prefix . 'booking_flows';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$flows_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text NULL,
            sections longtext NULL, -- JSON encoded sections configuration
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Migration: if legacy 'steps' column exists, move data to 'sections' then drop 'steps'
        try {
            $has_sections = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM `{$flows_table}` LIKE %s", 'sections'));
            if (empty($has_sections)) {
                $wpdb->query("ALTER TABLE `{$flows_table}` ADD COLUMN `sections` LONGTEXT NULL");
            }
            $has_steps = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM `{$flows_table}` LIKE %s", 'steps'));
            if (!empty($has_steps)) {
                // Copy values if sections is NULL
                $wpdb->query("UPDATE `{$flows_table}` SET `sections` = COALESCE(`sections`, `steps`)");
                // Drop legacy column
                $wpdb->query("ALTER TABLE `{$flows_table}` DROP COLUMN `steps`");
            }
        } catch (Exception $e) {
            // ignore migration errors to keep compatibility
        }
        
        // Insert default booking flow if none exists
        $default_flow_count = $wpdb->get_var("SELECT COUNT(*) FROM {$flows_table}");
        if ($default_flow_count == 0) {
            $default_sections = array(
                array(
                    'type' => 'calendar',
                    'name' => 'Select Date',
                    'section_name' => 'Select Date',
                    'required' => 1
                ),
                array(
                    'type' => 'time_slot',
                    'name' => 'Select Time',
                    'section_name' => 'Select Time',
                    'required' => 1
                ),
                array(
                    'type' => 'form',
                    'name' => 'Customer Information',
                    'section_name' => 'Customer Information',
                    'form_id' => 1, // Default form
                    'required' => 1
                )
            );
            
            $wpdb->insert(
                $flows_table,
                array(
                    'name' => 'Default Booking Flow',
                    'description' => 'Default booking flow with date selection, time slot selection, and customer information',
                    'sections' => json_encode($default_sections),
                    'is_active' => 1
                ),
                array('%s', '%s', '%s', '%d')
            );
        }
    }
    
    /**
     * Get all booking flows
     */
    public function get_booking_flows() {
        global $wpdb;
        $flows_table = $wpdb->prefix . $this->table_prefix . 'booking_flows';
        
        $flows = $wpdb->get_results("SELECT * FROM {$flows_table} ORDER BY name ASC");
        return $flows;
    }
    
    /**
     * Get booking flow by ID
     */
    public function get_booking_flow($flow_id) {
        global $wpdb;
        $flows_table = $wpdb->prefix . $this->table_prefix . 'booking_flows';
        
        $flow = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$flows_table} WHERE id = %d",
            $flow_id
        ));
        
        return $flow;
    }
    
    /**
     * Create new booking flow
     */
    public function create_booking_flow($name, $description, $sections) {
        global $wpdb;
        $flows_table = $wpdb->prefix . $this->table_prefix . 'booking_flows';
        
        $result = $wpdb->insert(
            $flows_table,
            array(
                'name' => sanitize_text_field($name),
                'description' => sanitize_textarea_field($description),
                'sections' => json_encode($sections),
                'is_active' => 1
            ),
            array('%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update booking flow
     */
    public function update_booking_flow($flow_id, $name, $description, $sections) {
        global $wpdb;
        $flows_table = $wpdb->prefix . $this->table_prefix . 'booking_flows';
        
        $result = $wpdb->update(
            $flows_table,
            array(
                'name' => sanitize_text_field($name),
                'description' => sanitize_textarea_field($description),
                'sections' => json_encode($sections),
                'updated_at' => current_time('mysql')
            ),
            array('id' => intval($flow_id)),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete booking flow
     */
    public function delete_booking_flow($flow_id) {
        global $wpdb;
        $flows_table = $wpdb->prefix . $this->table_prefix . 'booking_flows';
        
        $result = $wpdb->delete(
            $flows_table,
            array('id' => intval($flow_id)),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Migrate existing flow sections to use 'section_name' instead of 'label', and copy from 'name' if missing.
     */
    public function migrate_flow_sections() {
        global $wpdb;
        $flows_table = $wpdb->prefix . $this->table_prefix . 'booking_flows';
        $flows = $wpdb->get_results("SELECT id, sections FROM {$flows_table}");
        if (!$flows) return;
        foreach ($flows as $flow) {
            $changed = false;
            $sections = json_decode($flow->sections, true);
            if (!is_array($sections)) continue;
            foreach ($sections as &$step) {
                if (!isset($step['section_name']) || $step['section_name'] === '') {
                    if (!empty($step['name'])) {
                        $step['section_name'] = $step['name'];
                        $changed = true;
                    } elseif (!empty($step['label'])) {
                        $step['section_name'] = $step['label'];
                        $changed = true;
                    }
                }
                if (isset($step['label'])) {
                    unset($step['label']);
                    $changed = true;
                }
            }
            unset($step);
            if ($changed) {
                $wpdb->update(
                    $flows_table,
                    array('sections' => json_encode($sections), 'updated_at' => current_time('mysql')),
                    array('id' => intval($flow->id)),
                    array('%s', '%s'),
                    array('%d')
                );
            }
        }
    }

    /**
     * Migrate legacy status values across all per-flow tables:
     * confirmed -> approved, cancelled -> rejected
     */
    public function migrate_status_values() {
        global $wpdb;
        $flows = $this->get_booking_flows();
        if (!is_array($flows)) return false;
        foreach ($flows as $flow) {
            $table = $this->get_flow_table_name($flow->name);
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($exists !== $table) { continue; }
            $cols = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
            if (!is_array($cols) || !in_array('status', $cols, true)) { continue; }
            $wpdb->query("UPDATE `{$table}` SET `status` = 'approved' WHERE `status` = 'confirmed'");
            $wpdb->query("UPDATE `{$table}` SET `status` = 'rejected' WHERE `status` = 'cancelled'");
        }
        // Update blocking statuses option
        $blocking = get_option('booking_blocking_statuses', array('approved', 'completed'));
        if (is_array($blocking)) {
            $changed = false;
            foreach ($blocking as &$st) {
                if ($st === 'confirmed') { $st = 'approved'; $changed = true; }
                if ($st === 'cancelled') { $st = 'rejected'; $changed = true; }
            }
            unset($st);
            if ($changed) { update_option('booking_blocking_statuses', array_values(array_unique($blocking))); }
        }
        return true;
    }

    /**
     * Migrate an existing per-flow table to the new clean structure.
     * - Menangani kolom lama (custom_*, name, date, special_requests) untuk migrasi schema
     */
    public function migrate_existing_flow_table($flow_name) {
        global $wpdb;
        $table = $this->get_flow_table_name($flow_name);
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) { return false; }

        $cols = $wpdb->get_results("SHOW COLUMNS FROM `{$table}`");
        if (!$cols) { return false; }
        $col_names = array();
        foreach ($cols as $c) { $col_names[] = $c->Field; }

        $legacy_cols = array();
        foreach ($col_names as $cn) { if (preg_match('/^custom_\d+$/', $cn)) { $legacy_cols[] = $cn; } }
        foreach (array('special_requests','name','date') as $maybe) { if (in_array($maybe, $col_names, true)) { $legacy_cols[] = $maybe; } }
        if (empty($legacy_cols)) { return true; }

        $offset = 0; $batch = 200;
        do {
            $select_cols = '`id`, `additional_fields`, `customer_email`';
            foreach ($legacy_cols as $lc) { $select_cols .= ", `{$lc}`"; }
            $rows = $wpdb->get_results($wpdb->prepare("SELECT {$select_cols} FROM `{$table}` ORDER BY id ASC LIMIT %d OFFSET %d", $batch, $offset));
            if (!$rows) { break; }
            foreach ($rows as $row) {
                $extras = array();
                if (!empty($row->additional_fields)) { $prev = @maybe_unserialize($row->additional_fields); if (is_array($prev)) { $extras = $prev; } }
                foreach ($legacy_cols as $lc) { if (property_exists($row, $lc)) { $val = $row->$lc; if ($val !== null && $val !== '') { $extras[$lc] = $val; } } }
                $updates = array(); $fmts = array();
                if (!empty($extras)) { $updates['additional_fields'] = maybe_serialize($extras); $fmts[] = '%s'; }
                if (empty($row->customer_email)) { $cand = ''; foreach ($extras as $k => $v) { if (is_string($v) && preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $v)) { $cand = $v; break; } } if ($cand !== '') { $updates['customer_email'] = sanitize_email($cand); $fmts[] = '%s'; } }
                if (!empty($updates)) { $wpdb->update($table, $updates, array('id' => intval($row->id)), $fmts, array('%d')); }
            }
            $offset += $batch;
        } while (count($rows) === $batch);

        foreach ($legacy_cols as $lc) { $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN `{$lc}`"); }
        return true;
    }

    /**
     * Ensure specific columns exist for a flow table with given SQL types.
     * $columns_spec = array('column_name' => 'SQL_TYPE', ...)
     */
    public function ensure_columns_for_flow($flow_name, $columns_spec) {
        global $wpdb;
        $table = $this->get_flow_table_name($flow_name);
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) { return false; }
        $existing = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
        if (!is_array($existing)) { $existing = array(); }
        foreach ($columns_spec as $col => $type) {
            if (!in_array($col, $existing, true)) {
                $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$type} NULL");
                $existing[] = $col;
            }
        }
        return true;
    }

    

    /**
     * Normalize custom_* columns in a per-flow table to label-based columns from form definitions.
     * Uses forms referenced by the flow's sections to map field keys to labels.
     */
    public function normalize_custom_columns_by_labels($flow_id) {
        global $wpdb;
        // Get flow
        $flow = $this->get_booking_flow(intval($flow_id));
        if (!$flow) return false;
        $table = $this->get_flow_table_name($flow->name);
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) { return false; }

        // Collect form fields mapping key => label
        $fields_map = array();
        $sections = is_string($flow->sections) ? json_decode($flow->sections, true) : $flow->sections;
        if (is_array($sections)) {
            foreach ($sections as $st) {
                if (isset($st['type']) && $st['type'] === 'form' && !empty($st['form_id'])) {
                    $form = $this->get_form(intval($st['form_id']));
                    if ($form && $form->fields) {
                        $ff = maybe_unserialize($form->fields);
                        if (is_array($ff)) {
                            foreach ($ff as $k => $def) {
                                $lbl = isset($def['label']) ? $def['label'] : $k;
                                $fields_map[$k] = $lbl;
                            }
                        }
                    }
                }
            }
        }
        if (empty($fields_map)) return false;

        // Existing columns
        $existing_cols = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
        if (!is_array($existing_cols)) { $existing_cols = array(); }

        // For each custom_* key in mapping, create new column by slugifying the label, copy values, drop old
        foreach ($fields_map as $key => $label) {
            if (!preg_match('/^custom_\d+$/', $key)) continue;
            // Slugify label to snake_case
            $slug = sanitize_title($label);
            $slug = str_replace('-', '_', $slug);
            if ($slug === '' || ctype_digit(substr($slug, 0, 1))) { $slug = 'field_' . md5($label); }
            // Ensure no conflict with reserved columns
            $reserved = array('id','customer_name','customer_email','booking_date','booking_time','service_type','status','additional_fields','payload','created_at','updated_at');
            if (in_array($slug, $reserved, true)) { $slug .= '_field'; }

            // Add column if missing
            if (!in_array($slug, $existing_cols, true)) {
                $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `{$slug}` LONGTEXT NULL");
                $existing_cols[] = $slug;
            }
            // Copy data
            if (in_array($key, $existing_cols, true)) {
                $wpdb->query("UPDATE `{$table}` SET `{$slug}` = COALESCE(`{$slug}`, `{$key}`) WHERE `{$key}` IS NOT NULL AND `{$key}` <> ''");
                // Drop old custom_* column
                $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN `{$key}`");
                // Remove from list
                $existing_cols = array_diff($existing_cols, array($key));
            }
        }
        return true;
    }

    /**
     * Hapus kolom identitas/snapshot lama dari semua tabel per-flow jika ada.
     */
    public function prune_identity_columns_all_flows() {
        global $wpdb;
        $flows = $this->get_booking_flows();
        foreach ($flows as $flow) {
            $table = $this->get_flow_table_name($flow->name);
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($exists !== $table) { continue; }
            $cols = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
            if (!is_array($cols)) { $cols = array(); }
            foreach (array('customer_name','customer_email','additional_fields') as $col) {
                if (in_array($col, $cols, true)) {
                    $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN `{$col}`");
                }
            }
        }
    }

    /**
     * Ensure flow_id column exists on all per-flow tables and backfill values.
     */
    public function ensure_flow_id_column_all_flows() {
        global $wpdb;
        $flows = $this->get_booking_flows();
        if (!is_array($flows)) return;
        foreach ($flows as $flow) {
            $table = $this->get_flow_table_name($flow->name);
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
            if ($exists !== $table) { continue; }
            $cols = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
            if (!is_array($cols)) { $cols = array(); }
            if (!in_array('flow_id', $cols, true)) {
                $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `flow_id` INT(11) NULL");
                $wpdb->query("ALTER TABLE `{$table}` ADD KEY `flow_id` (`flow_id`)");
            }
            // Backfill flow_id for existing rows
            $wpdb->query($wpdb->prepare("UPDATE `{$table}` SET `flow_id` = %d WHERE `flow_id` IS NULL", intval($flow->id)));
        }
    }


    /**
     * Normalize columns based on current form keys.
     * Ensures columns for each key exist and backfills values from additional_fields.
     */
    public function normalize_columns_by_keys($flow_id) {
        global $wpdb;
        $flow = $this->get_booking_flow(intval($flow_id));
        if (!$flow) return false;
        $table = $this->get_flow_table_name($flow->name);
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($exists !== $table) { return false; }

        // Collect current keys from form sections
        $current_keys = array();
        $sections = is_string($flow->sections) ? json_decode($flow->sections, true) : $flow->sections;
        if (is_array($sections)) {
            foreach ($sections as $st) {
                if (isset($st['type']) && $st['type'] === 'form' && !empty($st['form_id'])) {
                    $form = $this->get_form(intval($st['form_id']));
                    if ($form && $form->fields) {
                        $ff = maybe_unserialize($form->fields);
                        if (is_array($ff)) { foreach ($ff as $k => $def) { $current_keys[$k] = true; } }
                    }
                }
            }
        }
        if (empty($current_keys)) return false;

        // Ensure columns exist for each key
        $existing = $wpdb->get_col("SHOW COLUMNS FROM `{$table}`", 0);
        if (!is_array($existing)) { $existing = array(); }
        foreach (array_keys($current_keys) as $col) {
            if (!in_array($col, $existing, true)) {
                $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` LONGTEXT NULL");
                $existing[] = $col;
            }
        }

        // Backfill from additional_fields
        $offset = 0; $batch = 200;
        do {
        // No backfill from additional_fields; function now ensures columns exist only
        $rows = array();
            if (!$rows) break;
            foreach ($rows as $row) {
                $extras = array();
                if (!empty($row->additional_fields)) { $prev = @maybe_unserialize($row->additional_fields); if (is_array($prev)) { $extras = $prev; } }
                $updates = array(); $fmts = array();
                foreach ($current_keys as $k => $_) {
                    if (array_key_exists($k, $extras)) {
                        $val = $extras[$k]; if (is_array($val) || is_object($val)) { $val = wp_json_encode($val); }
                        $updates[$k] = sanitize_text_field((string)$val); $fmts[] = '%s';
                    }
                }
                if (!empty($updates)) { $wpdb->update($table, $updates, array('id' => intval($row->id)), $fmts, array('%d')); }
            }
            $offset += $batch;
        } while (count($rows) === $batch);

        return true;
    }

    /**
     * Migrate data from per-flow tables to unified table
     */
    public function migrate_to_unified_table() {
        global $wpdb;

        // Get all flows
        $flows = $this->get_booking_flows();
        $migrated_count = 0;

        foreach ($flows as $flow) {
            $old_table = $this->get_flow_table_name($flow->name);
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $old_table));

            if ($exists !== $old_table) {
                continue; // Skip if table doesn't exist
            }

            // Get all bookings from old table
            $old_bookings = $wpdb->get_results("SELECT * FROM {$old_table}");

            if (!$old_bookings) {
                continue;
            }

            foreach ($old_bookings as $old_booking) {
                // Extract core fields
                $customer_name = !empty($old_booking->customer_name) ? $old_booking->customer_name : '';
                $customer_email = !empty($old_booking->customer_email) ? $old_booking->customer_email : '';
                $booking_date = !empty($old_booking->booking_date) ? $old_booking->booking_date : '';
                $booking_time = !empty($old_booking->booking_time) ? $old_booking->booking_time : '';
                $service_type = !empty($old_booking->service_type) ? $old_booking->service_type : '';
                $status = !empty($old_booking->status) ? $old_booking->status : 'pending';

                // Extract custom fields (exclude core fields and system fields)
                $core_fields = array('id', 'flow_id', 'customer_name', 'customer_email', 'booking_date', 'booking_time', 'service_type', 'status', 'payload', 'created_at', 'updated_at');
                $custom_fields = array();

                foreach ($old_booking as $key => $value) {
                    if (!in_array($key, $core_fields) && !is_null($value) && $value !== '') {
                        $custom_fields[$key] = $value;
                    }
                }

                // Try to extract customer info from payload if empty
                if (empty($customer_name) || empty($customer_email)) {
                    $payload_data = json_decode($old_booking->payload, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($payload_data)) {
                        if (empty($customer_name)) {
                            foreach (array('customer_name', 'nama_lengkap', 'nama', 'name', 'full_name') as $field) {
                                if (!empty($payload_data[$field])) {
                                    $customer_name = $payload_data[$field];
                                    break;
                                }
                            }
                        }

                        if (empty($customer_email)) {
                            foreach (array('customer_email', 'email', 'email_address') as $field) {
                                if (!empty($payload_data[$field])) {
                                    $customer_email = $payload_data[$field];
                                    break;
                                }
                            }
                        }

                        // Add remaining payload fields to custom fields
                        foreach ($payload_data as $key => $value) {
                            if (!in_array($key, $core_fields) && !isset($custom_fields[$key])) {
                                $custom_fields[$key] = $value;
                            }
                        }
                    }
                }

                // Prepare data for insertion
                $new_booking = array(
                    'customer_name'  => $customer_name,
                    'customer_email' => $customer_email,
                    'booking_date'   => $booking_date,
                    'booking_time'   => $booking_time,
                    'service_type'   => $service_type,
                    'status'         => $status,
                    'flow_id'        => $flow->id,
                    'flow_name'      => $flow->name,
                    'fields'         => !empty($custom_fields) ? wp_json_encode($custom_fields) : null,
                    'payload'        => $old_booking->payload,
                    'created_at'     => $old_booking->created_at,
                    'updated_at'     => $old_booking->updated_at
                );

                // Insert into unified table
                $result = $wpdb->insert($this->table_name, $new_booking);

                if ($result !== false) {
                    $migrated_count++;
                }
            }
        }

        // Log migration result
        error_log("Archeus Booking: Migrated {$migrated_count} bookings to unified table");

        return $migrated_count;
    }
}
