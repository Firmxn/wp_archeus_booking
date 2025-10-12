=== Archeus Booking System ===
Contributors: ArcheusCatalyst
Tags: booking, appointments, calendar, reservations, scheduling, forms
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive booking system plugin for WordPress developed by Archeus Catalyst that allows users to create custom booking flows, manage appointments, and handle reservations with advanced form builder and calendar features.

== Description ==

Archeus Booking System is a comprehensive and easy-to-use WordPress plugin developed by Archeus Catalyst that provides advanced booking functionality for your website. It features a powerful form builder, flexible booking flows, service management, time slot configuration, and complete booking management system.

Features:
* **Custom Form Builder**: Create dynamic booking forms with various field types (text, email, number, date, time, select, textarea, file upload)
* **Booking Flow Management**: Build custom booking flows with multiple sections (forms, services, time slots, confirmation)
* **Service Management**: Manage different services with pricing, duration, and availability settings
* **Time Slot Management**: Configure available time slots with customizable durations
* **Calendar Integration**: Full calendar system with availability management and color-coded status indicators
* **Auto-Detection**: Smart field detection for customer_name and customer_email with automatic required validation
* **Email System**: Customizable email templates for customer confirmations, admin notifications, and status change emails
* **Admin Panel**: Comprehensive admin interface for managing all aspects of the booking system
* **Shortcode Support**: Easy integration with [archeus_booking id="<flow_id>"] and [archeus_booking_calendar]
* **Responsive Design**: Mobile-friendly interface for both admin and frontend
* **Status Management**: Complete booking status tracking (pending, approved, rejected, completed, cancelled)

== Installation ==

1. Upload the `archeus-booking` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your booking system:
   - Create Services under Bookings > Service Management
   - Set up Time Slots under Bookings > Time Slot Management
   - Create Booking Forms under Bookings > Booking Forms
   - Build Booking Flows under Bookings > Booking Flow Management
4. Use the shortcode [archeus_booking id="<flow_id>"] on any page or post to display your booking flow
5. Display availability calendar with [archeus_booking_calendar] shortcode
6. Manage all bookings through the 'Bookings' dashboard in WordPress admin panel

== Frequently Asked Questions ==

= How do I create a booking flow? =

Go to Bookings > Booking Flow Management and create a new flow. You can add multiple sections including forms, services, time slots, and confirmation pages. Each flow generates a unique shortcode for embedding.

= How does the form builder work? =

The form builder allows you to create custom fields with various types (text, email, number, date, time, select, textarea, file upload). The system automatically detects customer_name and customer_email fields and makes them required.

= Can I manage multiple services? =

Yes! Use Service Management to create different services with specific pricing, duration, and availability settings. Each service can be assigned different time slots.

= How do time slots work? =

Time Slot Management allows you to configure available booking times with customizable durations. You can set different time slots for different days and services.

= What email notifications are available? =

The system includes customizable email templates for:
- Customer confirmation emails
- Admin notification emails
- Status change emails (when booking status is updated)

= Can I display an availability calendar? =

Yes! Use the [archeus_booking_calendar] shortcode to display an availability calendar. The calendar shows:
- Green: Available dates
- Red: Fully booked dates
- Yellow: Holiday/unavailable dates
- Light blue: Limited availability

= How do I manage bookings? =

All bookings can be managed through the main Bookings dashboard where you can view, approve, reject, or cancel bookings. The system also provides filtering by status and booking flow.

== Changelog ==

= 1.0.0 =
* Initial stable release
* Complete booking system with form builder, flow management, and admin interface
* Service management with pricing and duration configuration
* Time slot management with customizable booking periods
* Advanced form builder with multiple field types and auto-detection
* Email notification system with customizable templates
* Calendar integration with availability management
* Status management system for complete booking lifecycle
* Responsive design for both admin and frontend interfaces
* Shortcode support for easy integration
* Auto-detection of customer_name and customer_email fields with required validation

