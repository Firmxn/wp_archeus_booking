<?php
/**
 * Time Slots Manager Class
 * Allows configuring flexible time slots that can be applied to any day
 * Developed by Archeus Catalyst
 */

if (!defined('ABSPATH')) {
    exit;
}

class Time_Slots_Manager {

    private $time_slots_table;

    public function __construct() {
        global $wpdb;
        $this->time_slots_table = $wpdb->prefix . 'archeus_booking_time_slots';
        
        add_action('plugins_loaded', array($this, 'create_tables'));
    }

    /**
     * Create time slots table
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Time slots table - defines available time slots that can be applied to any date
        $sql = "CREATE TABLE IF NOT EXISTS {$this->time_slots_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            time_label varchar(255) NOT NULL, -- e.g., 'Morning Session', 'Afternoon Session'
            start_time time NOT NULL,         -- e.g., '09:00:00'
            end_time time NOT NULL,           -- e.g., '10:00:00'
            max_capacity int(11) DEFAULT 1,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY start_time (start_time),
            KEY end_time (end_time),
            UNIQUE KEY unique_time_window (start_time, end_time)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Remove sort_order column if it exists (for backwards compatibility)
        $this->maybe_remove_sort_order_column();
        
        // Insert default time slots if none exist
        $default_slots_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->time_slots_table}");
        if ($default_slots_count == 0) {
            $this->create_default_time_slots();
        }
    }
    
    /**
     * Remove sort_order column if it exists for backwards compatibility
     */
    private function maybe_remove_sort_order_column() {
        global $wpdb;
        
        // Check if sort_order column exists using WordPress dbDelta approach
        $columns = $wpdb->get_col("DESC {$this->time_slots_table}");
        
        if (in_array('sort_order', $columns)) {
            // Instead of dropping the column (which may not work with dbDelta), 
            // we'll just ignore it in our queries going forward
            // The column will be ignored during updates and not included in new records
        }
    }
    
    /**
     * Create default time slots
     */
    private function create_default_time_slots() {
        global $wpdb;
        
        // Default time slots - 3 sessions as mentioned in the requirement
        $default_time_slots = array(
            array(
                'time_label' => 'Morning Session',
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
                'max_capacity' => 2
            ),
            array(
                'time_label' => 'Afternoon Session',
                'start_time' => '13:00:00',
                'end_time' => '15:00:00',
                'max_capacity' => 2
            ),
            array(
                'time_label' => 'Evening Session',
                'start_time' => '16:00:00',
                'end_time' => '18:00:00',
                'max_capacity' => 2
            )
        );
        
        foreach ($default_time_slots as $slot) {
            $wpdb->insert(
                $this->time_slots_table,
                array(
                    'time_label' => $slot['time_label'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'max_capacity' => $slot['max_capacity'],
                    'is_active' => 1
                ),
                array('%s', '%s', '%s', '%d', '%d')
            );
        }
    }

    /**
     * Get all time slots
     */
    public function get_time_slots($active_only = true) {
        global $wpdb;

        $where_clause = $active_only ? 'WHERE is_active = 1' : '';
        $order_clause = 'ORDER BY start_time ASC';

        $query = "SELECT * FROM {$this->time_slots_table} {$where_clause} {$order_clause}";
        $results = $wpdb->get_results($query);

        return $results;
    }

    /**
     * Get time slot by ID
     */
    public function get_time_slot($slot_id) {
        global $wpdb;

        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->time_slots_table} WHERE id = %d",
            $slot_id
        ));

        return $slot;
    }

    /**
     * Create new time slot
     */
    public function create_time_slot($time_label, $start_time, $end_time, $max_capacity = 1, $sort_order = 0) {
        global $wpdb;

        // Validate time format - accept both HH:MM and HH:MM:SS formats
        $time_pattern_hhmm = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/';
        $time_pattern_hhmmss = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';
        
        // Convert HH:MM to HH:MM:SS if needed
        if (preg_match($time_pattern_hhmm, $start_time)) {
            $start_time .= ':00';
        }
        
        if (preg_match($time_pattern_hhmm, $end_time)) {
            $end_time .= ':00';
        }
        
        // Validate the final format
        if (!preg_match($time_pattern_hhmmss, $start_time) || 
            !preg_match($time_pattern_hhmmss, $end_time)) {
            return false;
        }

        // Prevent duplicate time windows (same start-end)
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->time_slots_table} WHERE start_time = %s AND end_time = %s",
            $start_time,
            $end_time
        ));
        if ($existing_id) {
            return false;
        }

        $result = $wpdb->insert(
            $this->time_slots_table,
            array(
                'time_label' => sanitize_text_field($time_label),
                'start_time' => sanitize_text_field($start_time),
                'end_time' => sanitize_text_field($end_time),
                'max_capacity' => intval($max_capacity),
                'is_active' => 1
            ),
            array('%s', '%s', '%s', '%d', '%d')
        );

        // Check for database errors
        if ($result === false) {
            // Log the error for debugging
            error_log('Time_Slots_Manager: Insert failed for time_label: ' . $time_label . ', start_time: ' . $start_time . ', end_time: ' . $end_time);
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update time slot
     */
    public function update_time_slot($slot_id, $time_label, $start_time, $end_time, $max_capacity = 1, $is_active = 1, $sort_order = 0) {
        global $wpdb;

        // Validate time format - accept both HH:MM and HH:MM:SS formats
        $time_pattern_hhmm = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/';
        $time_pattern_hhmmss = '/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';
        
        // Convert HH:MM to HH:MM:SS if needed
        if (preg_match($time_pattern_hhmm, $start_time)) {
            $start_time .= ':00';
        }
        
        if (preg_match($time_pattern_hhmm, $end_time)) {
            $end_time .= ':00';
        }
        
        // Validate the final format
        if (!preg_match($time_pattern_hhmmss, $start_time) || 
            !preg_match($time_pattern_hhmmss, $end_time)) {
            return false;
        }

        // Prevent duplicate time windows for other records
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->time_slots_table} WHERE start_time = %s AND end_time = %s AND id <> %d",
            $start_time,
            $end_time,
            intval($slot_id)
        ));
        if ($existing_id) {
            return false;
        }

        $result = $wpdb->update(
            $this->time_slots_table,
            array(
                'time_label' => sanitize_text_field($time_label),
                'start_time' => sanitize_text_field($start_time),
                'end_time' => sanitize_text_field($end_time),
                'max_capacity' => intval($max_capacity),
                'is_active' => intval($is_active),
                'updated_at' => current_time('mysql')
            ),
            array('id' => intval($slot_id)),
            array('%s', '%s', '%s', '%d', '%d', '%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Delete time slot
     */
    public function delete_time_slot($slot_id) {
        global $wpdb;

        $result = $wpdb->delete(
            $this->time_slots_table,
            array('id' => intval($slot_id)),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Get all time slots for a specific date
     * This returns all configured time slots for the date, regardless of service
     */
    public function get_time_slots_for_date($date) {
        global $wpdb;
        $schedules_table = $wpdb->prefix . 'archeus_booking_schedules';
        
        // Get all active time slots
        $time_slots = $this->get_time_slots();
        
        // For each time slot, check if there are corresponding schedules for this date
        $result = array();
        foreach ($time_slots as $slot) {
            $schedules = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$schedules_table} WHERE date = %s AND start_time = %s AND end_time = %s",
                $date, $slot->start_time, $slot->end_time
            ));
            
            foreach ($schedules as $schedule) {
                $result[] = $schedule;
            }
        }
        
        // Sort by start time
        usort($result, function($a, $b) {
            return strcmp($a->start_time, $b->start_time);
        });
        
        return $result;
    }

    /**
     * Generate schedules for a date based on configured time slots
     */
    // Generate schedules for a date based on configured time slots (no service binding)
    public function generate_schedules_for_date($date) {
        global $wpdb;
        $schedules_table = $wpdb->prefix . 'archeus_booking_schedules';
        
        // Get all active time slots
        $time_slots = $this->get_time_slots();
        
        $slots_added = 0;
        
        foreach ($time_slots as $slot) {
            // Check if a schedule already exists for this time slot on this date
            $existing_schedule = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$schedules_table} WHERE date = %s AND start_time = %s AND end_time = %s",
                $date, $slot->start_time, $slot->end_time
            ));
            
            // If no schedule exists, create one using the time slot configuration
            if (!$existing_schedule) {
                $result = $wpdb->insert(
                    $schedules_table,
                    array(
                        'date' => sanitize_text_field($date),
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'max_capacity' => $slot->max_capacity,
                        'current_bookings' => 0,
                        'is_available' => 1
                    ),
                    array('%s', '%s', '%s', '%d', '%d', '%d')
                );
                
                if ($result !== false) {
                    $slots_added++;
                }
            }
        }
        
        return $slots_added;
    }
}
