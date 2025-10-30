=== Archeus Booking System ===
Contributors: firmxn
Tags: booking, appointments, calendar, reservations, scheduling, form builder, services, time slots, history, export
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress booking plugin with intelligent form builder, booking history, Excel/CSV export, multi-language support, and automated database migrations.

== Description ==

Archeus Booking System is a **comprehensive WordPress booking plugin** that transforms your website into a powerful appointment and reservation management system. Built with modern architecture and best practices, it offers enterprise-level features with an intuitive interface.

= 🎯 Core Features =

* **Advanced Form Builder** - Create dynamic forms with 8 field types and smart auto-detection for customer name/email
* **Booking History & Archive** - Complete audit trail with automatic archiving for completed/rejected bookings
* **Professional Export System** - Export to Excel (.xlsx) and CSV with advanced date filtering
* **Multi-Language Support** - English and Indonesian smart field detection (15+ name patterns, 10+ email patterns)
* **Service Catalog** - Manage services with pricing, duration, and availability settings
* **Time Slot Management** - Flexible scheduling with capacity control and max bookings per slot
* **Interactive Calendar** - Color-coded availability calendar with booking count display
* **Email Automation** - Customizable templates with 30+ dynamic tags (bilingual support)
* **Booking Flows** - Multi-step processes: Calendar → Services → Time Slots → Forms → Confirmation
* **Database Migrations** - Seamless updates with automatic schema evolution
* **Page Builder Ready** - Native Elementor support, Gutenberg blocks, and widget compatibility

= 🚀 Advanced Features =

* **Excel Export with PhpSpreadsheet** - Generate professional .xlsx files
* **CSV Export** - Standard format with proper encoding
* **Smart Auto-Detection** - Automatically detects "nama lengkap", "full name", "email", "alamat email" fields
* **SessionStorage Persistence** - Form data saved across page reloads
* **Automated Cleanup** - Cron-based cleanup of expired availability and schedules
* **Status Workflow** - Pending → Approved → Completed/Rejected with history tracking
* **Capacity Management** - Control maximum bookings per time slot
* **Responsive Design** - Perfect on desktops, tablets, and mobile devices
* **Developer Friendly** - 25+ AJAX actions, WordPress hooks, and extensible architecture
* **Security First** - Nonce validation, input sanitization, and capability checks

= 📊 Perfect For =

* Medical clinics and healthcare providers
* Salons, spas, and beauty services
* Consultation services (legal, accounting, coaching)
* Equipment rental and venue booking
* Event management and scheduling
* Educational institutions (tutoring, classes)
* Professional services of all types
* Any service-based business requiring appointments

= 🔌 Integration & Compatibility =

* **Elementor** - Full page builder support with editor preview
* **WordPress Blocks** - Gutenberg compatible
* **PhpSpreadsheet** - Professional Excel export library
* **WordPress Multisite** - Fully compatible
* **Translation Ready** - .pot file included, Indonesian translation provided
* **Mobile Responsive** - Works on all screen sizes

= 🛠️ Technical Highlights =

* Object-oriented PHP architecture
* WordPress coding standards compliant
* Security-first approach (nonce validation, sanitization)
* Optimized database queries with proper indexing
* AJAX-driven admin interface
* RESTful design patterns
* Automated database migrations
* Zero-downtime plugin updates

== Installation ==

= Automatic Installation (Recommended) =

1. Go to WordPress Admin → Plugins → Add New
2. Search for "Archeus Booking System"
3. Click "Install Now" and then "Activate"
4. Navigate to "Bookings" menu to configure

= Manual Installation =

1. Download the plugin ZIP file
2. **IMPORTANT**: Ensure the ZIP includes the `vendor/` folder (required for Excel export)
3. Upload via WordPress Admin → Plugins → Add New → Upload Plugin
4. Activate the plugin
5. Go to Bookings → Booking Flow to create your first flow

= For Developers (Git/Composer) =

1. Clone repository to `/wp-content/plugins/archeus-booking/`
2. Run `composer install` in the plugin directory to install PhpSpreadsheet
3. Activate through WordPress admin
4. Verify `vendor/` folder exists for Excel export functionality

= Post-Installation Setup =

1. **Create Services** - Go to Bookings → Services and add your services with pricing
2. **Configure Time Slots** - Set available booking time windows
3. **Build Forms** - Create custom forms with required fields (customer_name, customer_email auto-detected)
4. **Create Booking Flow** - Combine Calendar + Services + Time Slots + Forms
5. **Add to Page** - Use shortcode `[archeus_booking id="1"]` on any page/post
6. **Customize Emails** - Configure email templates with 30+ dynamic tags
7. **Set Calendar Availability** - Define available dates and holidays

= Troubleshooting =

**Excel Export Not Working?**
Run `composer install` in the plugin directory to install PhpSpreadsheet library.

**Vendor folder missing?**
Download the complete package or run `composer require phpoffice/phpspreadsheet`

**Database errors on activation?**
Try deactivating and reactivating the plugin to re-run table creation.

== Frequently Asked Questions ==

= How do I add a booking form to my site? =

1. Go to Bookings → Booking Flow
2. Click "Create New Flow"
3. Add sections: Calendar, Services, Time Slots, Forms
4. Configure each section (select forms, set labels, descriptions)
5. Save the flow
6. Use the generated shortcode: `[archeus_booking id="X"]` on any page/post

= Can I export booking data? =

Yes! Go to Bookings → History and click "Export to Excel" or "Export to CSV". You can filter by date range and sort by various fields. **Note**: Excel export requires PhpSpreadsheet (included in complete package or install via `composer install`).

= How do email templates work? =

Go to Bookings → Email Settings. You can customize 3 email types:
- Customer confirmation emails
- Admin notification emails
- Status change emails

Use dynamic tags like `{customer_name}`, `{booking_date}`, `{service_type}` for personalization. The plugin supports 30+ tags including Indonesian translations ({nama_lengkap}, {tanggal_reservasi}, etc.).

= What field types are supported? =

The form builder supports 8 field types:
- Text (single line)
- Email (with validation)
- Number
- Date
- Time
- Select/Dropdown
- Textarea (multi-line)
- File Upload

= Does it support multiple languages? =

Yes! The plugin includes:
- Smart auto-detection for English AND Indonesian field labels
- Translation-ready with .pot file
- Indonesian translation included (id_ID)
- Multi-language email tags (English and Indonesian aliases)

Examples:
- "Nama Lengkap" or "Full Name" both auto-detected as customer_name
- "Email" or "Alamat Email" both auto-detected as customer_email

= How does the booking history work? =

When a booking is marked as "Completed" or "Rejected", it's automatically moved to the History table. This includes:
- Original booking data preserved
- Completion/rejection notes
- Who moved it (user tracking) and when
- Full export capability to Excel/CSV
- Permanent archive for audit trails

= Can I limit bookings per time slot? =

Yes! When creating time slots (Bookings → Time Slots), set the "Max Capacity" field. The system will:
- Track current bookings vs capacity
- Show "Limited" or "Full" status on calendar
- Prevent overbooking automatically
- Display booking count on calendar days (e.g., "15 (2/5)" = 2 of 5 slots booked)

= Is it compatible with Elementor? =

Yes! Full Elementor support including:
- Works in Elementor editor
- Preview mode compatibility
- Automatic script loading in Elementor context
- Widget-ready shortcodes
- No conflicts with Elementor styles

= Can I customize the booking form fields? =

Yes! The plugin includes a powerful form builder with smart auto-detection:
- Add any custom fields (8 types available)
- Fields with labels like "Nama", "Full Name", "Customer Name" automatically map to customer_name
- Fields with labels like "Email", "Alamat Email" automatically map to customer_email
- Configure validation rules, placeholders, and required fields
- Add dropdown options for select fields

= How do I customize the calendar colors? =

The calendar uses CSS classes you can override in your theme:
- `.available` - Green (available dates)
- `.limited` - Yellow (partially booked)
- `.full` - Orange (fully booked)
- `.unavailable` - Red (closed dates)
- `.selected-date` - Blue (user selection)
- `.past` - Gray (past dates)

Add custom CSS in Appearance → Customize → Additional CSS or use a custom CSS plugin.

= Can users upload files in booking forms? =

Yes! Add a "File" field type in your form. Users can upload documents, images, etc. File data is stored with the booking and can be accessed in the admin dashboard.

= Does it work with WordPress Multisite? =

Yes! The plugin is fully compatible with WordPress Multisite installations. Each site can have its own independent booking configuration, services, and forms.

= How do I backup my booking data? =

Two methods:
1. **Export Feature**: Use Excel or CSV export (Bookings → History) to download all data
2. **Database Backup**: Use your regular WordPress backup solution - includes all plugin tables automatically

= What happens during plugin updates? =

The plugin includes an automated database migration system:
- Schema updates run automatically on plugin activation
- Existing data is preserved (backward compatible)
- No manual database intervention required
- Zero-downtime upgrades
- Migration logs available for debugging

= How does the cleanup system work? =

The plugin includes automated cleanup:
- **Cron-based**: Daily cleanup of expired availability and schedules
- **Probability-based**: 10% chance per page load to reduce server load
- **Manual option**: Force cleanup via admin if needed
- **Preserves bookings**: Only cleans up availability data, not booking records

= Can I customize the shortcode behavior? =

Yes! The shortcode supports multiple variations:
- `[archeus_booking id="1"]` - Display specific flow
- `[archeus_booking]` - Auto-select first available flow
- `[archeus_booking flow="1"]` - Alternative syntax
- `[archeus_booking_calendar]` - Calendar only (no booking form)

== Screenshots ==

1. Admin dashboard showing booking overview and management interface
2. Form builder with drag-and-drop field configuration
3. Calendar view with availability management
4. Time slot configuration interface
5. Frontend booking form example

== Changelog ==

= 1.3.0 =
* Feature: Complete booking history and archive system with automatic archiving
* Feature: Excel export using PhpSpreadsheet library (.xlsx format)
* Feature: CSV export with advanced filtering and date range selection
* Feature: Automated database migration system for seamless updates
* Feature: Multi-language smart field auto-detection (English + Indonesian)
* Feature: 30+ dynamic email template tags with bilingual support
* Feature: Elementor page builder integration with editor/preview support
* Feature: SessionStorage-based form persistence across page reloads
* Feature: Automated cleanup with probability-based execution (10% per request)
* Feature: Calendar booking count display (e.g., "15 (2/5)")
* Feature: Max months display setting for calendar navigation
* Enhancement: Improved status workflow (pending → approved → completed/rejected)
* Enhancement: History archiving with completion notes and rejection reasons
* Enhancement: User tracking (moved_by field) for audit trails
* Enhancement: Export with date range filtering and custom sorting
* Enhancement: Email template system with Indonesian tag aliases
* Enhancement: Smart booking flow fallback (auto-select if ID missing)
* Fix: Dropdown positioning issues in form builder
* Fix: Status migration for legacy bookings (confirmed→approved, cancelled→rejected)
* Fix: Date filtering logic in export (booking_date → moved_at for history table)
* Fix: Legacy table cleanup for pre-unified-table installations
* Performance: Optimized database queries with proper indexing on unified table
* Performance: Probability-based cleanup to reduce server load
* Performance: AJAX-driven admin interface for reduced page loads
* Security: Enhanced input sanitization and nonce validation
* Database: Unified table architecture - single wp_archeus_booking table for all flows
* Database: Automated schema evolution with backward compatibility
* Database: migrate_flow_sections() - Booking flow section updates
* Database: migrate_status_values() - Status value normalization
* Database: Legacy migration support for pre-1.3.0 installations

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

= 1.3.0 =
Major update with booking history, Excel/CSV export, multi-language support, and database migrations. **IMPORTANT**: Requires PhpSpreadsheet for Excel export - run `composer install` if installing from Git. Existing installations will automatically migrate database schema. Backup your database before upgrading as a precaution.

= 1.0.0 =
Initial release. Ready for production use with all core booking features.

== Additional Info ==

For support, feature requests, or contributions, please visit our GitHub repository: https://github.com/Firmxn/wp_archeus_booking

Developed by Archeus Catalyst with ❤️ for the WordPress community.