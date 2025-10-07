=== Archeus Booking System ===
Contributors: ArcheusCatalyst
Tags: booking, appointments, calendar, reservations, scheduling
Requires at least: 5.0
Tested up to: 6.3
Requires PHP: 7.0
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive booking system plugin for WordPress developed by Archeus Catalyst that allows users to book appointments and manage reservations.

== Description ==

Archeus Booking System is a comprehensive and easy-to-use WordPress plugin developed by Archeus Catalyst that provides advanced booking functionality for your website. It allows visitors to submit booking requests, which can be managed through the WordPress admin panel.

Features:
* Custom booking form with multiple fields
* Calendar integration for date selection
* Admin panel for managing bookings
* Email notifications for booking confirmations
* Shortcode support for easy integration
* Responsive design

== Installation ==

1. Upload the `booking-plugin` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create a Booking Flow in the admin, then use the shortcode [archeus_booking id="<flow_id>"] on a page or post
4. Manage bookings through the 'Bookings' menu in the WordPress admin panel

== Frequently Asked Questions ==

= How do I add the booking flow to my site? =

Create a flow under Bookings > Booking Flow, then embed it using [archeus_booking id="<flow_id>"] on a page or post.

= Can I customize the form fields? =

Yes, the plugin allows for customization of form fields through the admin panel.

= Does the plugin include a calendar? =

Yes, the plugin includes a date selection calendar for bookings.

= Can I customize the form fields? =

Yes. Add a “Form” step to your Booking Flow and configure its fields under Bookings > Booking Forms.

= Can I display an availability calendar on my site? =

Yes! Use the [archeus_booking_calendar] shortcode to display an availability calendar on any page or post.
The calendar shows availability based on your settings in the admin panel, with different colors indicating:
- Green: Available dates
- Red: Fully booked dates
- Yellow: Holiday/unavailable dates
- Light blue: Limited availability (some slots filled)

= How do I manage calendar availability? =

Go to Bookings > Calendar in your WordPress admin panel to set availability for individual dates or date ranges.

== Changelog ==

= 1.0.0 =
* Initial release
* Added availability calendar feature with color-coded status indicators
* Added admin panel for managing calendar availability
* Added [archeus_booking_calendar] shortcode for displaying calendar on frontend

