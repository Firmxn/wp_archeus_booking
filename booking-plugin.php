<?php
/**
 * Plugin Name: Archeus Booking System
 * Description: A comprehensive booking system plugin for WordPress with form builder, service management, and booking flows
 * Version: 1.0.0
 * Author: Firmansyah Pramudia Ariyanto
 * Author URI: https://github.com/firmxn
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
    define('ARCHEUS_BOOKING_VERSION', '1.2.3');
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

        // Ensure email settings are properly configured
        self::ensure_email_settings();
        if (class_exists('Booking_Shortcodes')) {
            new Booking_Shortcodes();
        }
        if (class_exists('Booking_Calendar')) {
            $booking_calendar = new Booking_Calendar();
            // Ensure tables are created even if plugin wasn't re-activated after updates
            if (method_exists($booking_calendar, 'create_tables')) {
                $booking_calendar->create_tables();
            }
        }
        if (class_exists('Time_Slots_Manager')) {
            $time_slots_manager = new Time_Slots_Manager();
            // Ensure tables are created even if plugin wasn't re-activated after updates
            if (method_exists($time_slots_manager, 'create_tables')) {
                $time_slots_manager->create_tables();
            }
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
     * Ensure email settings are properly configured
     */
    public static function ensure_email_settings() {
        $email_settings = get_option('booking_email_settings');

        // If email settings don't exist or are incomplete, set defaults
        if (!$email_settings || !isset($email_settings['enable_status_change_emails'])) {
            $default_settings = array(
                'enable_customer_confirmation' => 1,
                'enable_admin_notification' => 1,
                'enable_status_change_emails' => 1,
                'admin_email_address' => get_option('admin_email'),
                'customer_confirmation_subject' => 'Booking Confirmation - #{booking_id}',
                'customer_confirmation_body' => '<p>Dear {customer_name},</p><p>Thank you for your booking. Here are your booking details:</p><p><strong>Booking ID:</strong> {booking_id}<br><strong>Service:</strong> {service_type}<br><strong>Date:</strong> {booking_date}<br><strong>Time:</strong> {booking_time}</p><p>We will confirm your booking shortly.</p><hr><p><strong>Available Tags:</strong></p><p><strong>Customer Info:</strong> {customer_name}, {nama_lengkap}, {nama}, {customer_email}, {email_pelanggan}, {alamat_email}</p><p><strong>Booking Details:</strong> {booking_id}, {booking_date}, {tanggal_reservasi}, {booking_time}, {waktu_reservasi}, {service_type}, {layanan}, {jenis_layanan}, {time_slot}, {slot_waktu}, {status}, {new_status}</p><p><strong>Company Info:</strong> {company_name}, {nama_perusahaan}, {company_url}, {url_perusahaan}, {admin_email}, {email_admin}</p><p><strong>Date/Time:</strong> {current_date}, {current_time}, {current_datetime}</p><p><strong>Dynamic:</strong> {greeting}, {email_title}, {any_custom_field}</p>',
                'admin_notification_subject' => 'New Booking Received - #{booking_id}',
                'admin_notification_body' => '<p>A new booking has been received:</p><p><strong>Booking ID:</strong> {booking_id}<br><strong>Customer:</strong> {customer_name}<br><strong>Email:</strong> {customer_email}<br><strong>Service:</strong> {service_type}<br><strong>Date:</strong> {booking_date}<br><strong>Time:</strong> {booking_time}</p><hr><p><strong>Available Tags:</strong></p><p><strong>Customer Info:</strong> {customer_name}, {nama_lengkap}, {nama}, {customer_email}, {email_pelanggan}, {alamat_email}</p><p><strong>Booking Details:</strong> {booking_id}, {booking_date}, {tanggal_reservasi}, {booking_time}, {waktu_reservasi}, {service_type}, {layanan}, {jenis_layanan}, {time_slot}, {slot_waktu}, {status}, {new_status}</p><p><strong>Company Info:</strong> {company_name}, {nama_perusahaan}, {company_url}, {url_perusahaan}, {admin_email}, {email_admin}</p><p><strong>Date/Time:</strong> {current_date}, {current_time}, {current_datetime}</p><p><strong>Dynamic:</strong> {greeting}, {email_title}, {any_custom_field}</p>',
                'status_change_subject' => 'Booking Status Update - #{booking_id}',
                'status_change_body' => '<p>Dear {customer_name},</p><p>Your booking status has been updated to: <strong>{new_status}</strong></p><p><strong>Booking ID:</strong> {booking_id}<br><strong>Service:</strong> {service_type}<br><strong>Date:</strong> {booking_date}<br><strong>Time:</strong> {booking_time}</p><hr><p><strong>Available Tags:</strong></p><p><strong>Customer Info:</strong> {customer_name}, {nama_lengkap}, {nama}, {customer_email}, {email_pelanggan}, {alamat_email}</p><p><strong>Booking Details:</strong> {booking_id}, {booking_date}, {tanggal_reservasi}, {booking_time}, {waktu_reservasi}, {service_type}, {layanan}, {jenis_layanan}, {time_slot}, {slot_waktu}, {status}, {new_status}</p><p><strong>Company Info:</strong> {company_name}, {nama_perusahaan}, {company_url}, {url_perusahaan}, {admin_email}, {email_admin}</p><p><strong>Date/Time:</strong> {current_date}, {current_time}, {current_datetime}</p><p><strong>Dynamic:</strong> {greeting}, {email_title}, {any_custom_field}</p>'
            );

            // Merge with existing settings if any
            if ($email_settings && is_array($email_settings)) {
                $email_settings = array_merge($default_settings, $email_settings);
            } else {
                $email_settings = $default_settings;
            }

            update_option('booking_email_settings', $email_settings);
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
            // Call create_tables directly to ensure it runs during activation
            $booking_calendar->create_tables();
        }
        if (class_exists('Time_Slots_Manager')) {
            $time_slots_manager = new Time_Slots_Manager();
            // Call create_tables directly to ensure it runs during activation
            $time_slots_manager->create_tables();
        }

        // Set default email settings if not already set
        $email_settings = get_option('booking_email_settings');
        if (!$email_settings) {
            $default_email_settings = array(
                'enable_customer_confirmation' => 1,
                'enable_admin_notification' => 1,
                'enable_status_change_emails' => 1,
                'admin_email_address' => get_option('admin_email'),
                'customer_confirmation_subject' => 'Booking Confirmation - #{booking_id}',
                'customer_confirmation_body' => '<p>Dear {customer_name},</p><p>Thank you for your booking. Here are your booking details:</p><p><strong>Booking ID:</strong> {booking_id}<br><strong>Service:</strong> {service_type}<br><strong>Date:</strong> {booking_date}<br><strong>Time:</strong> {booking_time}</p><p>We will confirm your booking shortly.</p><hr><p><strong>Available Tags:</strong></p><p><strong>Customer Info:</strong> {customer_name}, {nama_lengkap}, {nama}, {customer_email}, {email_pelanggan}, {alamat_email}</p><p><strong>Booking Details:</strong> {booking_id}, {booking_date}, {tanggal_reservasi}, {booking_time}, {waktu_reservasi}, {service_type}, {layanan}, {jenis_layanan}, {time_slot}, {slot_waktu}, {status}, {new_status}</p><p><strong>Company Info:</strong> {company_name}, {nama_perusahaan}, {company_url}, {url_perusahaan}, {admin_email}, {email_admin}</p><p><strong>Date/Time:</strong> {current_date}, {current_time}, {current_datetime}</p><p><strong>Dynamic:</strong> {greeting}, {email_title}, {any_custom_field}</p>',
                'admin_notification_subject' => 'New Booking Received - #{booking_id}',
                'admin_notification_body' => '<p>A new booking has been received:</p><p><strong>Booking ID:</strong> {booking_id}<br><strong>Customer:</strong> {customer_name}<br><strong>Email:</strong> {customer_email}<br><strong>Service:</strong> {service_type}<br><strong>Date:</strong> {booking_date}<br><strong>Time:</strong> {booking_time}</p><hr><p><strong>Available Tags:</strong></p><p><strong>Customer Info:</strong> {customer_name}, {nama_lengkap}, {nama}, {customer_email}, {email_pelanggan}, {alamat_email}</p><p><strong>Booking Details:</strong> {booking_id}, {booking_date}, {tanggal_reservasi}, {booking_time}, {waktu_reservasi}, {service_type}, {layanan}, {jenis_layanan}, {time_slot}, {slot_waktu}, {status}, {new_status}</p><p><strong>Company Info:</strong> {company_name}, {nama_perusahaan}, {company_url}, {url_perusahaan}, {admin_email}, {email_admin}</p><p><strong>Date/Time:</strong> {current_date}, {current_time}, {current_datetime}</p><p><strong>Dynamic:</strong> {greeting}, {email_title}, {any_custom_field}</p>',
                'status_change_subject' => 'Booking Status Update - #{booking_id}',
                'status_change_body' => '<p>Dear {customer_name},</p><p>Your booking status has been updated to: <strong>{new_status}</strong></p><p><strong>Booking ID:</strong> {booking_id}<br><strong>Service:</strong> {service_type}<br><strong>Date:</strong> {booking_date}<br><strong>Time:</strong> {booking_time}</p><hr><p><strong>Available Tags:</strong></p><p><strong>Customer Info:</strong> {customer_name}, {nama_lengkap}, {nama}, {customer_email}, {email_pelanggan}, {alamat_email}</p><p><strong>Booking Details:</strong> {booking_id}, {booking_date}, {tanggal_reservasi}, {booking_time}, {waktu_reservasi}, {service_type}, {layanan}, {jenis_layanan}, {time_slot}, {slot_waktu}, {status}, {new_status}</p><p><strong>Company Info:</strong> {company_name}, {nama_perusahaan}, {company_url}, {url_perusahaan}, {admin_email}, {email_admin}</p><p><strong>Date/Time:</strong> {current_date}, {current_time}, {current_datetime}</p><p><strong>Dynamic:</strong> {greeting}, {email_title}, {any_custom_field}</p>'
            );
            update_option('booking_email_settings', $default_email_settings);
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
