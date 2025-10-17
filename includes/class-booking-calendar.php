<?php
/**
 * Booking Calendar Class
 * Developed by Archeus Catalyst
 */

if (!defined('ABSPATH')) {
    exit;
}

class Booking_Calendar {

    private $availability_table;

    public function __construct() {
        global $wpdb;
        $this->availability_table = $wpdb->prefix . 'archeus_booking_availability';
        
        add_action('plugins_loaded', array($this, 'create_tables'));
    }

    /**
     * Create availability table
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->availability_table} (
            id int(11) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            availability_status varchar(20) NOT NULL DEFAULT 'available', -- available, unavailable
            daily_limit int(11) DEFAULT 5,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date (date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get availability data for a specific date
     */
    public function get_availability($date) {
        global $wpdb;

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->availability_table} WHERE date = %s",
            $date
        ));

        return $result ? $result : null;
    }

    /**
     * Set availability for a specific date
     */
    public function set_availability($date, $status, $limit = 5) {
        global $wpdb;

        $result = $wpdb->replace(
            $this->availability_table,
            array(
                'date' => $date,
                'availability_status' => $status,
                'daily_limit' => $limit
            ),
            array('%s', '%s', '%d')
        );

        return $result !== false;
    }

    /**
     * Batch set availability for date range with separate weekend options
     */
    public function batch_set_availability($start_date, $end_date, $status, $limit = 5, $include_saturday = true, $include_sunday = true) {
        $current_date = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);

        while ($current_date <= $end_date_obj) {
            $day_of_week = (int)$current_date->format('N'); // 1=Monday, 7=Sunday

            // Skip specific weekend days if not included
            if (($day_of_week == 6 && !$include_saturday) || // Saturday
                ($day_of_week == 7 && !$include_sunday)) {   // Sunday
                $current_date->modify('+1 day');
                continue;
            }

            $this->set_availability($current_date->format('Y-m-d'), $status, $limit);
            $current_date->modify('+1 day');
        }
    }

    /**
     * Get availability for a month
     */
    public function get_month_availability($year, $month) {
        global $wpdb;

        $first_day = "$year-$month-01";
        $last_day = date('Y-m-t', strtotime($first_day));

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT date, availability_status, daily_limit FROM {$this->availability_table} WHERE date BETWEEN %s AND %s ORDER BY date",
            $first_day,
            $last_day
        ));

        // Create an array with all days of the month
        $availability = array();
        $current_date = new DateTime($first_day);
        $end_date = new DateTime($last_day);

        while ($current_date <= $end_date) {
            $date_str = $current_date->format('Y-m-d');
            $day_of_week = (int)$current_date->format('N'); // 1=Monday, 7=Sunday

            // Default status: weekends (Saturday=6, Sunday=7) are unavailable
            $default_status = ($day_of_week >= 6) ? 'unavailable' : 'available';

            $availability[$date_str] = array(
                'date' => $date_str,
                'availability_status' => $default_status,
                'daily_limit' => 5, // Default
                'booked_count' => 0
            );
            $current_date->modify('+1 day');
        }

        // Override with actual data from database
        foreach ($results as $result) {
            $availability[$result->date] = array(
                'date' => $result->date,
                'availability_status' => $result->availability_status,
                'daily_limit' => $result->daily_limit,
                'booked_count' => 0
            );
        }

        // Get booking counts for the month
        $booking_counts = $this->get_booking_counts_by_date($year, $month);
        foreach ($booking_counts as $date => $count) {
            if (isset($availability[$date])) {
                $availability[$date]['booked_count'] = $count;
            }
        }

        return $availability;
    }

    /**
     * Get booking counts by date for a specific month from unified table
     */
    private function get_booking_counts_by_date($year, $month) {
        global $wpdb;

        $first_day = "$year-$month-01";
        $last_day = date('Y-m-t', strtotime($first_day));

        $counts = array();

        // Get booking counts from unified table
        $table_name = $wpdb->prefix . 'archeus_booking';
        $statuses = get_option('booking_blocking_statuses', array('approved', 'completed'));

        if (is_array($statuses) && !empty($statuses)) {
            $ph = implode(',', array_fill(0, count($statuses), '%s'));
            $args = array_merge(array($first_day, $last_day), $statuses);
            $sql = "SELECT booking_date, COUNT(*) as count FROM {$table_name} WHERE booking_date BETWEEN %s AND %s AND status IN ($ph) GROUP BY booking_date";
            $results = $wpdb->get_results($wpdb->prepare($sql, $args));
            foreach ($results as $r) {
                $counts[$r->booking_date] = intval($r->count);
            }
        }

        return $counts;
    }

    /**
     * Get availability status with booking count from unified table
     */
    public function get_availability_with_bookings($date) {
        $availability = $this->get_availability($date);

        if (!$availability) {
            // Check if date is weekend and set default accordingly
            $date_obj = new DateTime($date);
            $day_of_week = (int)$date_obj->format('N'); // 1=Monday, 7=Sunday
            $default_status = ($day_of_week >= 6) ? 'unavailable' : 'available';

            // Return default availability if not set
            $availability = (object) array(
                'date' => $date,
                'availability_status' => $default_status,
                'daily_limit' => 5
            );
        }

        // Get booking count for this date from unified table
        global $wpdb;
        $booked_count = 0;
        $table_name = $wpdb->prefix . 'archeus_booking';
        $statuses = get_option('booking_blocking_statuses', array('approved', 'completed'));

        if (is_array($statuses) && !empty($statuses)) {
            $ph = implode(',', array_fill(0, count($statuses), '%s'));
            $args = array_merge(array($date), $statuses);
            $sql = "SELECT COUNT(*) FROM {$table_name} WHERE booking_date = %s AND status IN ($ph)";
            $booked_count = intval($wpdb->get_var($wpdb->prepare($sql, $args)));
        }

        return array(
            'availability' => $availability,
            'booked_count' => intval($booked_count)
        );
    }
    
    /**
     * Get max months setting for calendar display
     */
    public function get_max_months_display() {
        $max_months = get_option('booking_max_calendar_months', 3);
        return intval($max_months);
    }
    
    /**
     * Set max months setting for calendar display
     */
    public function set_max_months_display($months) {
        update_option('booking_max_calendar_months', max(1, intval($months)));
    }
}

