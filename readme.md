# 🎯 Archeus Booking System

<div align="center">

![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-blue?style=for-the-badge&logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-GPLv2-green?style=for-the-badge)
![Version](https://img.shields.io/badge/Version-1.3.0-orange?style=for-the-badge)

**A comprehensive WordPress booking plugin with intelligent form builder, service management, and calendar integration**

[🚀 Quick Start](#-quick-start) • [📖 Documentation](#-features) • [⚡ Installation](#-installation) • [🐛 Issues](https://github.com/Firmxn/wp_archeus_booking)

</div>

---

## ✨ Features

### 🎨 **Intelligent Form Builder**
- **Dynamic Field Types**: Text, Email, Number, Date, Time, Select, Textarea, File Upload
- **Smart Auto-Detection**: Automatically detects customer_name & customer_email fields with required validation
- **Real-time Validation**: Live form validation with toast notifications
- **Customizable Layout**: Intuitive form building interface with drag-and-drop functionality
- **Field Validation**: Comprehensive validation rules for all field types

### 🔄 **Advanced Booking Flow Management**
- **Multi-Step Flows**: Create custom booking processes with multiple sections
- **Flexible Components**: Forms, Services, Time Slots, Confirmation pages
- **Flow Templates**: Pre-built templates for common booking scenarios
- **Conditional Logic**: Show/hide sections based on user input and selections
- **Smart Fallback**: Auto-selects first available flow if ID not specified
- **SessionStorage Persistence**: Form data preserved across page reloads and navigation

### 📊 **Advanced Booking History & Archive System**
- **Complete Audit Trail**: Track all booking lifecycle events from creation to completion
- **Automatic Archiving**: Completed/rejected bookings moved to history table automatically
- **Export Capabilities**: Export history to Excel (.xlsx) and CSV formats
- **Date Range Filtering**: Filter exports by start date, end date, and custom ranges
- **Detailed Tracking**: Completion notes, rejection reasons, and user tracking (who moved)
- **Archive Management**: View, search, and manage archived bookings separately

### 🔄 **Automated Database Migration System**
- **Seamless Updates**: Automatic schema evolution on plugin updates
- **Zero Downtime**: Backward compatible migrations preserve existing data
- **Multiple Strategies**: Flow sections, status values, column management
- **Smart Migration**: Detects and applies only necessary database changes
- **Safe Upgrades**: No manual database intervention required

### 🌐 **Multi-Language Smart Field Detection**
- **Bilingual Support**: English and Indonesian label auto-detection
- **15+ Name Patterns**: Detects "nama lengkap", "full name", "customer name", etc.
- **10+ Email Patterns**: Recognizes "email", "alamat email", "e-mail", etc.
- **Intelligent Mapping**: Automatically maps to customer_name and customer_email fields
- **Conflict Resolution**: Handles duplicate field keys gracefully

### 🛠️ **Comprehensive Service Management**
- **Service Catalog**: Manage different services with detailed configurations
- **Availability Settings**: Set service-specific availability rules and constraints
- **Pricing Flexibility**: Fixed prices, variable pricing, or free services
- **Service Categories**: Organize services into logical groups for better management

### ⏰ **Sophisticated Time Slot Management**
- **Customizable Slots**: Configure available booking time slots with precision
- **Capacity Control**: Set maximum bookings per time slot
- **Duration Management**: Set different durations for different services
- **Buffer Times**: Add breaks between appointments for better service quality
- **Recurring Schedules**: Set up recurring availability patterns automatically

### 📅 **Calendar Integration**
- **Interactive Calendar**: Full calendar view with availability management
- **Color-coded Availability**: Visual indicators for different availability states
- **Holiday Management**: Mark holidays and unavailable dates
- **Real-time Updates**: Live calendar updates based on booking changes

### 📧 **Intelligent Email Notification System**
- **Customizable Templates**: Edit email content for all notification types
- **Multiple Triggers**: Customer confirmations, admin notifications, status changes
- **30+ Dynamic Tags**: Smart tag replacement with customer, booking, company, and time data
- **Multi-Language Tags**: English and Indonesian tag aliases ({customer_name}, {nama_lengkap})
- **HTML Support**: Rich text emails with custom branding and automatic template wrapping
- **Conditional Sending**: Enable/disable individual notification types

### 📊 **Powerful Admin Dashboard**
- **Comprehensive Overview**: All bookings in one centralized location
- **Status Management**: Track complete booking lifecycle (pending → approved → completed → cancelled)
- **Advanced Filtering**: Find bookings quickly by date, status, customer, or service
- **Export Functionality**: Export booking data for reporting and analysis

### 📱 **Responsive Design**
- **Mobile-First Approach**: Works perfectly on all devices and screen sizes
- **Modern Admin Interface**: Clean, intuitive admin panel with smooth interactions
- **Accessible Frontend**: WCAG compliant booking forms
- **Cross-browser Compatibility**: Works on all modern browsers

### 📤 **Professional Export System**
- **Excel Export**: Generate .xlsx files using PhpSpreadsheet library
- **CSV Export**: Standard comma-separated values format
- **Advanced Filtering**: Filter by date range, status, service type
- **Customizable Columns**: Export only the data you need
- **Sorting Options**: Order by any field in ascending or descending order
- **Bulk Operations**: Export hundreds of bookings in seconds

### 🔌 **Page Builder Integration**
- **Elementor Support**: Native compatibility with Elementor page builder
- **Elementor Editor**: Works seamlessly in Elementor editor and preview modes
- **Block Editor Ready**: Compatible with WordPress Gutenberg blocks
- **Widget Support**: Use shortcodes in WordPress widgets
- **Preview Compatible**: Full functionality in preview contexts

### ⚡ **Performance & Automation**
- **Automated Cleanup**: Cron-based cleanup of expired availability and schedules
- **Probability-Based Execution**: Smart cleanup runs 10% of time to reduce server load
- **Manual Cleanup Option**: Force cleanup on demand via admin interface
- **Optimized Queries**: Database indexes on critical columns for fast searches
- **AJAX-Driven Admin**: Reduces page loads and improves response time

---

## 🚀 Installation

### ⚠️ **CRITICAL: Composer Dependencies**
This plugin **REQUIRES** PhpSpreadsheet for Excel export functionality. The `vendor/` folder is **NOT** included in the Git repository.

### Method 1: Development Installation (For Developers)
```bash
# 1. Clone or download the plugin
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/Firmxn/wp_archeus_booking.git archeus-booking
cd archeus-booking

# 2. Install Composer dependencies (REQUIRED!)
composer install

# 3. Verify installation
ls vendor/phpoffice/phpspreadsheet  # Should exist
ls vendor/autoload.php              # Should exist

# 4. Activate in WordPress
# Go to WordPress Admin → Plugins → Activate "Archeus Booking System"
```

### Method 2: WordPress Admin Upload (Production Ready)
1. **Download the complete package** including `vendor/` folder
2. Go to WordPress Admin → **Plugins → Add New → Upload Plugin**
3. Upload the ZIP file
4. Click **Install Now**, then **Activate**
5. Navigate to **Bookings** menu to start configuration

### Method 3: FTP/SFTP (Manual Upload)
1. Extract the plugin ZIP file
2. **Verify** that `vendor/` folder exists in the extracted folder
3. Upload `archeus-booking/` folder to `/wp-content/plugins/`
4. Go to WordPress admin → Plugins → Activate

### Method 4: Production Build (For Distribution)
```bash
# Install production dependencies (optimized)
composer install --no-dev --optimize-autoloader

# Create production-ready ZIP
zip -r archeus-booking-production.zip archeus-booking/ \
  -x "*.git*" -x "*node_modules*" -x "*.DS_Store" -x "*__MACOSX*"

# The ZIP now includes vendor/ and is ready for distribution
```

## 🔧 Dependencies & Requirements

### Required Dependencies (via Composer)
```json
{
  "require": {
    "phpoffice/phpspreadsheet": "^5.2"
  }
}
```
- **PhpSpreadsheet ^5.2**: Excel export functionality (.xlsx file generation)
- **PSR Components**: Auto-loaded by PhpSpreadsheet (PSR-4, PSR-7, PSR-HTTP)

### System Requirements
- **WordPress**: 5.0 or higher (tested up to 6.6)
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.6 or higher (or MariaDB 10.0+)
- **PHP Extensions**: `zip`, `xml`, `gd` (for PhpSpreadsheet)
- **Memory Limit**: 256M recommended (for large exports)
- **Composer**: Required for development installation only

### Browser Compatibility
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

---

## 🔥 Troubleshooting

### Common Installation Issues

#### 🚨 Error: "vendor/autoload.php not found"
**Cause**: Composer dependencies not installed  
**Solution**:
```bash
cd /path/to/wp-content/plugins/archeus-booking
composer install
# Verify: ls vendor/autoload.php
```

#### 🚨 Error: "Class 'PhpOffice\PhpSpreadsheet\Spreadsheet' not found"
**Cause**: PhpSpreadsheet not properly installed  
**Solution**:
```bash
# Remove vendor and reinstall
rm -rf vendor/
composer install

# Or install explicitly
composer require phpoffice/phpspreadsheet:^5.2
```

#### 🚨 Export to Excel fails silently
**Causes & Solutions**:
1. **Memory limit too low**:
   - Add to `wp-config.php`: `define('WP_MEMORY_LIMIT', '256M');`
   - Or increase PHP memory_limit in `php.ini`

2. **Missing PHP extensions**:
   ```bash
   # Check installed extensions
   php -m | grep -E 'zip|xml|gd'
   
   # Install missing extensions (Ubuntu/Debian)
   sudo apt-get install php-zip php-xml php-gd
   ```

3. **File permissions**:
   ```bash
   # Ensure web server can write to upload directory
   chmod 755 /wp-content/uploads/
   ```

#### 🚨 Database errors on activation
**Solution**: Try deactivating and reactivating the plugin
```bash
# Via WP-CLI
wp plugin deactivate archeus-booking
wp plugin activate archeus-booking

# This will re-run database table creation
```

#### 🚨 Shortcode not displaying
**Causes**:
- Missing flow ID: Use `[archeus_booking id="1"]` not `[archeus_booking]`
- No flows created: Create a flow in Bookings → Booking Flow first
- Theme conflict: Check JavaScript console for errors

### Performance Issues

#### Slow admin dashboard
**Solutions**:
1. Reduce pagination limit in history (default: 50 rows)
2. Clear old booking history regularly
3. Optimize database tables:
   ```sql
   OPTIMIZE TABLE wp_archeus_booking;
   OPTIMIZE TABLE wp_archeus_booking_history;
   ```

#### Export timing out
**Solutions**:
1. Increase PHP max_execution_time:
   ```php
   // In wp-config.php
   set_time_limit(300); // 5 minutes
   ```
2. Export smaller date ranges
3. Use CSV instead of Excel for large datasets

---

## ⚡ Quick Start

1. **Configure Services** → Bookings → Services → Add services with pricing and duration
2. **Set Time Slots** → Bookings → Time Slots → Define available booking windows
3. **Create Forms** → Bookings → Forms → Build custom forms with smart field detection
4. **Build Flows** → Bookings → Booking Flow → Combine Calendar + Services + Time Slots + Forms
5. **Embed Shortcode**: Add `[archeus_booking id="1"]` to any page/post
6. **Customize Emails** → Bookings → Email → Configure templates with 30+ dynamic tags
7. **Manage Bookings** → Bookings → Dashboard → Approve, complete, or reject bookings
8. **Export Data** → Bookings → History → Export to Excel or CSV

---

## 📖 Usage

### Shortcode Examples

#### Basic Booking Flow
```shortcode
[archeus_booking id="1"]
```

#### Auto-Select First Flow (Fallback)
```shortcode
[archeus_booking]
<!-- Automatically selects the first flow or flow with existing data -->
```

#### Alternative Attribute Names
```shortcode
[archeus_booking flow="1"]
[archeus_booking flow_id="1"]
<!-- All variations work the same way -->
```

#### Standalone Availability Calendar
```shortcode
[archeus_booking_calendar]
<!-- Displays calendar only, without booking form -->
```

### Advanced Configuration
The plugin supports extensive customization:
- **Custom Form Fields**: 8 field types with validation rules
- **Multi-Language Detection**: English and Indonesian auto-detection
- **Service Management**: Multiple services with different pricing and durations
- **Time Slot Capacity**: Control maximum bookings per slot
- **Email Templates**: Customize with 30+ dynamic tags in both languages
- **Status Workflow**: pending → approved → completed/rejected with history archiving
- **Export Options**: Excel (.xlsx) and CSV with advanced filtering
- **Page Builder Integration**: Elementor, Gutenberg, widgets
- **Developer Hooks**: Multiple actions and filters for customization

---

## 🗄️ Database Architecture

### Main Database Tables

| Table Name | Purpose | Key Fields |
|------------|---------|------------|
| `wp_archeus_booking` | **Unified booking records** | `id`, `customer_name`, `customer_email`, `booking_date`, `booking_time`, `service_type`, `price`, `status`, `flow_id`, `fields` (JSON), `payload` (JSON) |
| `wp_archeus_booking_forms` | **Form definitions** | `id`, `name`, `fields` (serialized array of field configurations) |
| `wp_archeus_booking_history` | **Archive for completed/rejected bookings** | `id`, `original_booking_id`, `status`, `moved_at`, `moved_by`, `completion_notes`, `rejection_reason` |
| `wp_archeus_booking_services` | **Service catalog** | `id`, `name`, `description`, `price`, `duration`, `created_at` |
| `wp_archeus_booking_schedules` | **Time slot assignments** | `id`, `service_id`, `date`, `start_time`, `end_time`, `max_capacity`, `current_bookings` |
| `wp_archeus_booking_time_slots` | **Time slot templates** | `id`, `time_label`, `start_time`, `end_time`, `max_capacity` |
| `wp_archeus_booking_availability` | **Calendar availability** | `id`, `date`, `availability_status`, `daily_limit` |

### Unified Booking Table Architecture

The plugin uses a **single unified table** (`wp_archeus_booking`) to store all bookings from all flows:

**Key Design:**
- **Single Source of Truth**: All bookings stored in one table (`wp_archeus_booking`)
- **Flow Identification**: `flow_id` and `flow_name` columns identify the originating flow
- **JSON Flexibility**: Custom form fields stored in `fields` (JSON) column
- **Complete Data**: Full booking payload stored in `payload` (JSON) column
- **No Per-Flow Tables**: Plugin does NOT create separate tables per flow for active bookings
- **Simplified Queries**: Easy to search across all bookings regardless of flow

**Benefits:**
- ✅ Single query to retrieve all bookings
- ✅ No table proliferation (no separate table per flow)
- ✅ Easier database maintenance and optimization
- ✅ Better performance with properly indexed unified table
- ✅ Simplified backup and migration process
- ✅ Consistent data structure across all flows

### Database Features
- **Unified Architecture**: Single table for all flows eliminates complexity and table proliferation
- **JSON Field Storage**: Flexible custom field storage in `fields` and `payload` columns
- **Flow Tracking**: `flow_id` and `flow_name` columns for flow identification and filtering
- **Automatic Indexing**: Optimized indexes on `booking_date`, `status`, `service_type`, `flow_id` for fast queries
- **Schema Evolution**: Automated migrations via `dbDelta()` function ensure smooth updates
- **Backward Compatibility**: Existing data preserved during schema updates
- **Cleanup Automation**: Cron-based cleanup of expired availability (10% probability per request)

---

## 📧 Email Template Tags

The plugin provides **30+ dynamic tags** for email customization with **bilingual support** (English & Indonesian).

### Customer Information Tags
| Tag | Indonesian Alias | Description |
|-----|------------------|-------------|
| `{customer_name}` | `{nama_lengkap}`, `{nama}` | Customer's full name |
| `{customer_email}` | `{email_pelanggan}`, `{alamat_email}` | Customer's email address |

### Booking Details Tags
| Tag | Indonesian Alias | Description |
|-----|------------------|-------------|
| `{booking_id}` | - | Unique booking ID number |
| `{booking_date}` | `{tanggal_reservasi}` | Formatted booking date |
| `{booking_time}` | `{waktu_reservasi}` | Formatted booking time (HH:MM) |
| `{service_type}` | `{layanan}`, `{jenis_layanan}` | Selected service name |
| `{time_slot}` | `{slot_waktu}` | Time slot range (e.g., "09:00 - 10:00") |
| `{status}` | - | Current booking status |
| `{new_status}` | - | Updated status (for status change emails) |
| `{price}` | - | Service price |

### Company Information Tags
| Tag | Indonesian Alias | Description |
|-----|------------------|-------------|
| `{company_name}` | `{nama_perusahaan}` | WordPress site name |
| `{company_url}` | `{url_perusahaan}` | WordPress site URL |
| `{admin_email}` | `{email_admin}` | Admin email address |

### Date/Time Tags
| Tag | Description |
|-----|-------------|
| `{current_date}` | Current date (WordPress date format) |
| `{current_time}` | Current time (WordPress time format) |
| `{current_datetime}` | Current date and time combined |

### Contextual Tags
| Tag | Description |
|-----|-------------|
| `{greeting}` | Auto-generated greeting ("Halo {name}," for customer, "Halo Admin," for admin) |
| `{email_title}` | Auto-generated email title based on email type |

### Custom Field Tags
- **Any custom form field**: Use `{field_key}` to insert custom field values
- Example: If you have a field with key `phone_number`, use `{phone_number}` in email templates
- Arrays are automatically converted to comma-separated values

### Email Template Example
```html
<p>Dear {customer_name},</p>

<p>Thank you for your booking request!</p>

<p><strong>Booking Details:</strong></p>
<ul>
  <li>Booking ID: {booking_id}</li>
  <li>Service: {service_type}</li>
  <li>Date: {booking_date}</li>
  <li>Time: {booking_time}</li>
  <li>Time Slot: {time_slot}</li>
</ul>

<p>We will confirm your booking shortly.</p>

<p>Best regards,<br>
{company_name}<br>
{company_url}</p>
```

---

## 🔧 Configuration

### Service Setup
- Define service names, descriptions, and pricing
- Set duration and availability rules
- Configure service-specific requirements
- Set capacity limits per time slot

### Time Slot Configuration
- Set available hours and days
- Configure slot durations and intervals
- Add buffer times between appointments
- Set maximum bookings per slot

### Form Building
- Add custom fields with validation rules
- Configure auto-detection for key fields (customer_name, customer_email)
- Set required fields and custom validation
- Add conditional logic based on user input

### Email Templates
- Customize confirmation emails for customers
- Set up admin notifications for new bookings
- Configure status change notifications
- Use dynamic tags for personalized content

---

## 🎨 How It Works

### Booking Process Flow
1. **User Access**: User visits page with booking shortcode
2. **Form Display**: Multi-step booking form appears based on configured flow
3. **Service Selection**: User selects desired service and available time slot
4. **Form Submission**: User fills in required information
5. **Validation**: System validates all input data
6. **Booking Creation**: System creates booking record in database
7. **Notification**: Email notifications sent to user and admin
8. **Confirmation**: Admin can approve, reject, or manage booking

### Technical Architecture
- **WordPress Integration**: Built on WordPress best practices and hooks
- **Database Structure**: Optimized database tables for performance
- **AJAX Handling**: Asynchronous operations for better user experience
- **Security**: Implements WordPress security standards and sanitization
- **Performance**: Caching and optimization for high-traffic sites

### Key Components
- **Admin Interface**: Comprehensive management dashboard
- **Frontend Forms**: User-friendly booking interface
- **Calendar System**: Interactive calendar with real-time updates
- **Notification Engine**: Automated email and notification system
- **Database Layer**: Efficient data storage and retrieval

---

## 🛠️ Development

### Requirements
- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

### File Structure
```
archeus-booking/
├── admin/              # Admin interface files
├── assets/             # CSS, JS, and other assets
├── includes/           # Core functionality classes
├── public/             # Frontend functionality
├── views/              # Template files
├── booking-plugin.php  # Main plugin file
└── readme.txt          # WordPress plugin readme
```

### Hooks and Filters
The plugin provides numerous hooks and filters for customization:
- `archeus_booking_before_form`
- `archeus_booking_after_submission`
- `archeus_booking_email_content`
- `archeus_booking_validation_filters`

---

## 📋 Changelog

### [1.3.0] - Current Version
#### 🎉 Major Features
- ✨ **Booking History & Archive System**: Complete audit trail with automatic archiving for completed/rejected bookings
- ✨ **Professional Export**: Excel (.xlsx) using PhpSpreadsheet and CSV export with advanced filtering
- ✨ **Database Migration System**: Automated schema evolution with zero-downtime upgrades
- ✨ **Multi-Language Smart Detection**: Bilingual field auto-detection (English + Indonesian) with 15+ name patterns and 10+ email patterns
- ✨ **30+ Email Template Tags**: Comprehensive dynamic tag system with Indonesian aliases
- ✨ **Elementor Integration**: Native page builder support with editor and preview compatibility
- ✨ **SessionStorage Persistence**: Form state preservation across page reloads

#### 🔧 Enhancements
- 🔄 Improved status workflow: `pending → approved → completed/rejected` with history archiving
- 📊 Booking history with completion notes, rejection reasons, and user tracking
- 📤 Export with date range filtering and custom sorting (ASC/DESC)
- 📧 Email template system with bilingual tag support
- 📅 Calendar with booking count display and max months setting
- ⚡ Automated cleanup with probability-based execution (10% per request)
- 🔌 Page builder integration (Elementor, Gutenberg, widgets)
- 🎯 Smart booking flow fallback (auto-select if ID missing)

#### 🐛 Bug Fixes
- Fixed dropdown positioning issues in form builder
- Fixed status migration for legacy bookings (confirmed→approved, cancelled→rejected)
- Corrected date filtering logic in export (booking_date → moved_at for history table)
- Legacy table cleanup for installations upgrading from pre-unified-table versions

#### 🚀 Performance & Security
- Optimized database queries with proper indexing on unified table
- Probability-based cleanup to reduce server load
- Enhanced input sanitization and nonce validation
- AJAX-driven admin interface for reduced page loads
- Cron-based automated cleanup for expired availability

#### 🔄 Database Migrations
- Unified table architecture - single `wp_archeus_booking` table for all flows
- Automated schema evolution with backward compatibility
- `migrate_flow_sections()` - Booking flow section updates
- `migrate_status_values()` - Status value normalization (confirmed→approved, cancelled→rejected)
- Legacy migration support for pre-1.3.0 installations

### [1.0.0] - 2024-10-13
- ✨ Initial stable release
- 🎨 Complete form builder with drag-and-drop functionality
- 🔄 Flexible booking flow management system
- 🛠️ Comprehensive service management interface
- ⏰ Advanced time slot configuration with capacity control
- 📧 Customizable email notification system
- 📅 Interactive calendar with availability management
- 📊 Full admin dashboard with advanced filtering
- 📱 Fully responsive design for all devices
- 🔒 Security-focused implementation with WordPress standards

---

## 🤝 Support

### Getting Help
- 📖 Check this documentation first
- 🐛 [Report Issues](https://github.com/Firmxn/wp_archeus_booking/issues)
- 💬 [GitHub Discussions](https://github.com/Firmxn/wp_archeus_booking/discussions)
- 📧 Email: firmansyahpramudiaa@gmail.com

### Contributing
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the GPLv2 License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- [WordPress](https://wordpress.org/) for the amazing CMS platform
- [jQuery](https://jquery.com/) for JavaScript utilities
- All contributors and users who make this plugin better
- WordPress community for continuous support and inspiration

---

<div align="center">

**Made with ❤️ by [Firmansyah Pramudia Ariyanto](https://github.com/firmxn)**

[🌐 Personal Website](https://firmxn.dev) • [💼 LinkedIn](https://linkedin.com/in/firmxn) • [🐙 GitHub](https://github.com/firmxn)

**Archeus Catalyst - Building Digital Solutions**

</div>