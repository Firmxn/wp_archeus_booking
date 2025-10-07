<?php
/**
 * Plugin Name: Archeus Booking System
 * Description: A comprehensive booking system plugin for WordPress developed by Archeus Catalyst
 * Version: 1.2.45
 * Author: Archeus Catalyst
 * Text Domain: archeus-booking
 *
 * Shortcodes:
 * - [archeus_booking id="<flow_id>"]
 * - [archeus_booking_calendar]
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prevent accidental early output (e.g., from BOM in included files)
if (!headers_sent() && !ob_get_level()) {
    ob_start();
}

// Define plugin constants using __FILE__ directly instead of functions that might not be available yet
if (!defined('ARCHEUS_BOOKING_VERSION')) {
    define('ARCHEUS_BOOKING_VERSION', '1.2.62.' . time());
}

// Define paths early so other files can use them
if (!defined('ARCHEUS_BOOKING_PATH')) {
    define('ARCHEUS_BOOKING_PATH', plugin_dir_path(__FILE__));
}

if (!defined('ARCHEUS_BOOKING_URL')) {
    define('ARCHEUS_BOOKING_URL', plugin_dir_url(__FILE__));
}

// Include required files
if (file_exists(ARCHEUS_BOOKING_PATH . 'includes/class-booking-database.php')) {
    require_once ARCHEUS_BOOKING_PATH . 'includes/class-booking-database.php';
}
if (file_exists(ARCHEUS_BOOKING_PATH . 'includes/class-booking-shortcodes.php')) {
    require_once ARCHEUS_BOOKING_PATH . 'includes/class-booking-shortcodes.php';
}
if (file_exists(ARCHEUS_BOOKING_PATH . 'includes/class-booking-calendar.php')) {
    require_once ARCHEUS_BOOKING_PATH . 'includes/class-booking-calendar.php';
}
if (file_exists(ARCHEUS_BOOKING_PATH . 'includes/class-time-slots-manager.php')) {
    require_once ARCHEUS_BOOKING_PATH . 'includes/class-time-slots-manager.php';
}
if (file_exists(ARCHEUS_BOOKING_PATH . 'public/class-booking-calendar-public.php')) {
    require_once ARCHEUS_BOOKING_PATH . 'public/class-booking-calendar-public.php';
}
if (file_exists(ARCHEUS_BOOKING_PATH . 'admin/class-booking-admin.php')) {
    require_once ARCHEUS_BOOKING_PATH . 'admin/class-booking-admin.php';
}
if (file_exists(ARCHEUS_BOOKING_PATH . 'public/class-booking-public.php')) {
    require_once ARCHEUS_BOOKING_PATH . 'public/class-booking-public.php';
}

/**
 * Main plugin class
 */
class Booking_Plugin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('archeus-booking', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        if (class_exists('Booking_Database')) {
            $db_instance = new Booking_Database();
            // Ensure tables/migrations run even if plugin wasn't re-activated after updates
            if (method_exists($db_instance, 'create_tables')) {
                $db_instance->create_tables();
            }
            // Migrate booking flows to use section_name
            if (method_exists($db_instance, 'migrate_flow_sections')) {
                $db_instance->migrate_flow_sections();
            }
            // Migrate legacy statuses to new naming (confirmed->approved, cancelled->rejected)
            if (method_exists($db_instance, 'migrate_status_values')) {
                $db_instance->migrate_status_values();
            }
            // Prune identity columns (customer_name, customer_email) from per-flow tables
            if (method_exists($db_instance, 'prune_identity_columns_all_flows')) {
                $db_instance->prune_identity_columns_all_flows();
            }
            // Ensure flow_id column exists and backfilled for all per-flow tables
            if (method_exists($db_instance, 'ensure_flow_id_column_all_flows')) {
                $db_instance->ensure_flow_id_column_all_flows();
            }
        }
        if (class_exists('Booking_Shortcodes')) {
            new Booking_Shortcodes();
        }
        if (class_exists('Booking_Calendar')) {
            new Booking_Calendar();
        }
        if (class_exists('Time_Slots_Manager')) {
            new Time_Slots_Manager();
        }
        if (class_exists('Booking_Calendar_Public')) {
            new Booking_Calendar_Public();
        }
        if (class_exists('Booking_Admin')) {
            new Booking_Admin();
        }
        if (class_exists('Booking_Public')) {
            new Booking_Public();
        }
        
        // Schedule cron job for cleaning up old bookings
        add_action('wp', array($this, 'schedule_booking_cleanup'));
        add_action('archeus_booking_cleanup_cron', array($this, 'perform_booking_cleanup'));
    }
    
    /**
     * Schedule booking cleanup
     */
    public function schedule_booking_cleanup() {
        if (!wp_next_scheduled('archeus_booking_cleanup_cron')) {
            wp_schedule_event(time(), 'daily', 'archeus_booking_cleanup_cron');
        }
    }
    
    /**
     * Perform booking cleanup
     */
    public function perform_booking_cleanup() {
        if (class_exists('Booking_Database')) {
            $booking_db = new Booking_Database();
            $booking_db->cleanup_old_bookings();
        }
    }

    /**
     * Run on plugin activation
     */
    public static function activate() {
        if (class_exists('Booking_Database')) {
            $booking_db = new Booking_Database();
            $booking_db->create_tables();
        }
        if (class_exists('Booking_Calendar')) {
            $booking_calendar = new Booking_Calendar();
            $booking_calendar->create_tables();
        }
        if (class_exists('Time_Slots_Manager')) {
            $time_slots_manager = new Time_Slots_Manager();
            $time_slots_manager->create_tables();
        }
        flush_rewrite_rules();
    }

    /**
     * Run on plugin deactivation
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('Booking_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('Booking_Plugin', 'deactivate'));

// Initialize the plugin
new Booking_Plugin();
