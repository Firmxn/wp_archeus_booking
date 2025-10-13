=== Archeus Booking System ===
Contributors: firmxn
Tags: booking, appointments, calendar, reservations, scheduling, form builder, services, time slots
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive booking system plugin for WordPress with form builder, service management, and calendar integration.

== Description ==

Archeus Booking System is a powerful and flexible WordPress booking plugin that provides complete booking functionality for your website. Built with modern web technologies, it offers an intuitive admin interface and seamless frontend experience.

Key Features:
* **Custom Form Builder** - Create dynamic booking forms with various field types
* **Service Management** - Organize and manage different services with time slots
* **Calendar Integration** - Full calendar view with availability management
* **Time Slot Management** - Flexible time slot configuration with capacity control
* **Booking Flows** - Multi-step booking process with customizable steps
* **Email Notifications** - Automated email notifications for booking confirmations
* **Admin Dashboard** - Comprehensive admin panel for managing all bookings
* **Responsive Design** - Works perfectly on all devices
* **Shortcode Support** - Easy integration with [archeus_booking id="<flow_id>"] and [archeus_booking_calendar]

Perfect for:
* Appointment booking systems
* Service-based businesses
* Consultation scheduling
* Reservation management
* Event booking
* Professional services

== Installation ==

1. Upload the `archeus-booking` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Bookings' in your WordPress admin to configure your booking system
4. Create a Booking Flow and use the shortcode [archeus_booking id="<flow_id>"] on any page or post
5. Manage bookings through the 'Bookings' menu in the WordPress admin panel

== Frequently Asked Questions ==

= How do I add a booking form to my site? =

Create a Booking Flow under Bookings > Booking Flow, then embed it using [archeus_booking id="<flow_id>"] on any page or post.

= Can I customize the booking form fields? =

Yes! The plugin includes a powerful form builder. Add a "Form" step to your Booking Flow and configure custom fields under Bookings > Booking Forms. You can add text fields, email fields, numbers, dates, selects, textareas, and file uploads.

= Does the plugin support multiple services? =

Yes! You can create multiple services with different time slots, pricing, and availability settings through the Services management interface.

= How does time slot management work? =

The plugin allows you to configure specific time slots for each service with capacity control. You can set maximum bookings per slot and manage availability through an intuitive admin interface.

= Can I display an availability calendar? =

Yes! Use the [archeus_booking_calendar] shortcode to display an interactive calendar showing availability with color-coded indicators:
- Green: Available dates
- Red: Fully booked dates
- Yellow: Holiday/unavailable dates
- Light blue: Limited availability

= How do booking notifications work? =

The plugin automatically sends email notifications to both admins and customers when bookings are made, confirmed, or cancelled. You can customize email templates through the admin panel.

= Is the plugin mobile-friendly? =

Yes! The plugin is fully responsive and works perfectly on all devices including desktops, tablets, and smartphones.

== Screenshots ==

1. Admin dashboard showing booking overview and management interface
2. Form builder with drag-and-drop field configuration
3. Calendar view with availability management
4. Time slot configuration interface
5. Frontend booking form example

== Changelog ==

= 1.0.0 =
* Initial stable release
* Complete booking system with form builder
* Service management with time slots
* Calendar integration with availability management
* Multi-step booking flows
* Email notification system
* Responsive design for all devices
* Admin dashboard with comprehensive management tools
* Shortcode support for easy integration

== Upgrade Notice ==

= 1.0.0 =
This is the initial stable release of Archeus Booking System. All features are fully tested and ready for production use.

== Additional Info ==

For support, feature requests, or contributions, please visit our GitHub repository: https://github.com/Firmxn/wp_archeus_booking

Developed by Archeus Catalyst with ❤️ for the WordPress community.