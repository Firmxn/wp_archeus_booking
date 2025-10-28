# AGENTS.md

**AI Coding Agent Guidelines for Archeus Booking System**

This document provides comprehensive guidance for AI coding agents (Claude, GPT-4, Cursor, GitHub Copilot, etc.) when working with this WordPress booking plugin codebase.

---

## 🎯 Project Overview

**Archeus Booking System** is a production-grade WordPress plugin for comprehensive booking management featuring:

- **Intelligent Form Builder** with smart auto-detection (customer_name, customer_email)
- **Multi-Step Booking Flows** with conditional logic and flexible components
- **Service & Time Slot Management** with capacity controls and recurring schedules
- **Interactive Calendar** with real-time availability updates
- **Email Notification System** with customizable templates and dynamic tags
- **AJAX-Driven Admin Dashboard** with filtering, search, and Excel export
- **Security-First Architecture** with nonce validation, input sanitization, and XSS prevention

**Tech Stack**: PHP 7.4+, WordPress 5.0+, jQuery, MySQL, PhpSpreadsheet (Composer)

**Author**: Firmansyah Pramudia Ariyanto  
**Version**: 1.0.0  
**License**: GPLv2

---

## 🏗️ Architecture & Codebase Structure

### Directory Organization

```
archeus-booking/
├── booking-plugin.php              # Main plugin entry point and bootstrapper
│
├── includes/                       # Core business logic layer
│   ├── class-booking-database.php      # Database operations and migrations
│   ├── class-booking-calendar.php      # Calendar functionality
│   ├── class-booking-shortcodes.php    # Shortcode registration
│   └── class-time-slots-manager.php    # Time slot management
│
├── admin/                          # Admin interface (backend)
│   └── class-booking-admin.php         # Admin dashboard and AJAX handlers
│
├── public/                         # Frontend interface
│   ├── class-booking-public.php        # Public booking forms
│   └── class-booking-calendar-public.php # Public calendar display
│
├── assets/                         # Frontend resources
│   ├── css/                            # Modular stylesheets
│   │   ├── admin.css                   # Admin dashboard styles
│   │   ├── booking-flow.css            # Multi-step form styles
│   │   ├── calendar.css                # Calendar component styles
│   │   └── public.css                  # Public-facing styles
│   └── js/                             # JavaScript modules
│       ├── admin.js                    # Admin functionality
│       ├── booking-flow.js             # Frontend booking flow
│       └── calendar.js                 # Calendar interactions
│
├── languages/                      # i18n translation files
├── vendor/                         # Composer dependencies (PhpSpreadsheet)
├── composer.json                   # PHP dependency management
├── CLAUDE.md                       # Claude-specific guidelines
└── README.md                       # User-facing documentation
```

### Core Components Architecture

#### 1. **Main Plugin File** (`booking-plugin.php`)
```php
class Booking_Plugin {
    // ✓ WordPress plugin standards compliance
    // ✓ Constants definition (ARCHEUS_BOOKING_PATH, ARCHEUS_BOOKING_URL)
    // ✓ Component initialization on 'init' hook
    // ✓ Database migration orchestration
    // ✓ Cron job scheduling for cleanup
    // ✓ Email settings initialization
}
```

**Key Responsibilities**:
- Plugin activation/deactivation hooks
- Component loading and initialization
- Database schema migration coordination
- Cron job setup for automated cleanup
- Default email template configuration

#### 2. **Database Layer** (`includes/class-booking-database.php`)
```php
class Booking_Database {
    // ✓ WordPress dbDelta() for safe migrations
    // ✓ JSON field storage for flexibility
    // ✓ Automated cleanup routines
    // ✓ Legacy data transformations
    // ✓ Transaction support for data integrity
}
```

**Database Schema**:
- `wp_archeus_booking` - Main booking records with JSON form data
- `wp_archeus_booking_forms` - Dynamic form definitions
- `wp_archeus_booking_history` - Completed booking archive
- `wp_archeus_services` - Service catalog
- `wp_archeus_schedules` - Time slot configurations

**Design Pattern**: Unified booking table with JSON storage for custom form fields

#### 3. **Admin Interface** (`admin/class-booking-admin.php`)
```php
class Booking_Admin {
    // ✓ AJAX-driven dashboard (all operations via AJAX)
    // ✓ Form builder with drag-and-drop
    // ✓ Service/schedule CRUD operations
    // ✓ Export to Excel (PhpSpreadsheet)
    // ✓ Email template editor with tag system
    // ✓ Comprehensive filtering and search
}
```

**Admin Features**:
- Real-time booking status management (pending → approved → completed → rejected)
- Visual form builder with field type selection
- Service configuration with pricing and availability
- Time slot setup with capacity and duration controls
- Excel export with custom date ranges
- Email template customization with dynamic tag preview

#### 4. **Frontend System** (`public/class-booking-public.php`)
```php
class Booking_Public {
    // ✓ Multi-step booking flow rendering
    // ✓ SessionStorage-based state persistence
    // ✓ Real-time form validation
    // ✓ Smart field auto-detection
    // ✓ AJAX form submission
    // ✓ Elementor compatibility
}
```

**Frontend Features**:
- Step-by-step booking wizard (Form → Service → Time Slot → Confirmation)
- Auto-detection of customer_name and customer_email with required validation
- Real-time availability checking for time slots
- Toast notifications for user feedback
- Mobile-responsive design

#### 5. **Calendar System** (`includes/class-booking-calendar.php`)
```php
class Booking_Calendar {
    // ✓ Interactive availability calendar
    // ✓ Holiday management
    // ✓ Real-time slot availability
    // ✓ Color-coded visual indicators
    // ✓ Admin and public views
}
```

#### 6. **Time Slot Manager** (`includes/class-time-slots-manager.php`)
```php
class Time_Slots_Manager {
    // ✓ Capacity management per slot
    // ✓ Buffer time configuration
    // ✓ Recurring schedule patterns
    // ✓ Service-specific durations
    // ✓ Availability calculation engine
}
```

---

## 🔧 Technical Implementation Details

### Database Design Philosophy

**Unified Booking Table with JSON Flexibility**:
```sql
CREATE TABLE wp_archeus_booking (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    flow_id INT(11) NOT NULL,
    customer_name VARCHAR(255),      -- Auto-detected from form
    customer_email VARCHAR(255),     -- Auto-detected from form
    booking_date DATE,
    booking_time TIME,
    service_type VARCHAR(255),
    time_slot VARCHAR(255),
    status VARCHAR(50),
    form_data LONGTEXT,              -- JSON storage for custom fields
    created_at DATETIME,
    updated_at DATETIME
);
```

**Why JSON Storage?**:
- Flexible form schema without ALTER TABLE migrations
- Support for unlimited custom fields per flow
- Easy form builder implementation
- Future-proof design for schema changes

### Multi-Step Booking Flow System

**Flow Definition Structure** (JSON stored in database):
```json
{
  "flow_id": 1,
  "flow_name": "General Booking",
  "sections": [
    {
      "section_type": "form",
      "section_name": "Customer Information",
      "form_id": 1
    },
    {
      "section_type": "services",
      "section_name": "Select Service"
    },
    {
      "section_type": "timeslots",
      "section_name": "Choose Time Slot"
    },
    {
      "section_type": "confirmation",
      "section_name": "Confirm Booking"
    }
  ]
}
```

**Flow Execution**:
1. User lands on page with `[archeus_booking id="1"]`
2. Frontend loads flow definition via AJAX
3. SessionStorage saves progress across steps
4. Each step validates before proceeding
5. Final confirmation creates booking record
6. Email notifications trigger automatically

### Smart Form Field System

**Auto-Detection Logic** (in `public/class-booking-public.php`):
```javascript
// Frontend automatically detects these field names:
const SMART_FIELDS = {
    customer_name: ['customer_name', 'nama_lengkap', 'nama', 'name'],
    customer_email: ['customer_email', 'email', 'email_pelanggan', 'alamat_email']
};

// Auto-marks as required with validation
// No need for admin to manually configure
```

**Supported Field Types**:
- `text` - Single-line text input
- `email` - Email with format validation
- `number` - Numeric input with min/max
- `date` - Date picker
- `time` - Time picker
- `select` - Dropdown with custom options
- `textarea` - Multi-line text
- `file` - File upload (with MIME type validation)

### Email Notification System

**Dynamic Tag System**:
```php
// Available tags for email templates:

// Customer Information
{customer_name}, {nama_lengkap}, {nama}
{customer_email}, {email_pelanggan}, {alamat_email}

// Booking Details
{booking_id}, {booking_date}, {tanggal_reservasi}
{booking_time}, {waktu_reservasi}
{service_type}, {layanan}, {jenis_layanan}
{time_slot}, {slot_waktu}
{status}, {new_status}

// Company Information
{company_name}, {nama_perusahaan}
{company_url}, {url_perusahaan}
{admin_email}, {email_admin}

// Date/Time
{current_date}, {current_time}, {current_datetime}

// Dynamic Fields
{greeting} - Time-based greeting (Good morning/afternoon/evening)
{any_custom_field} - Any field from form_data JSON
```

**Email Triggers**:
1. Customer confirmation (on booking creation)
2. Admin notification (on new booking)
3. Status change notification (on admin status update)

### Security Implementation

**WordPress Security Best Practices**:
```php
// 1. Nonce Validation (all AJAX operations)
check_ajax_referer('booking_nonce', 'nonce');

// 2. Capability Checks (admin operations)
if (!current_user_can('manage_options')) {
    wp_send_json_error('Unauthorized');
}

// 3. Input Sanitization
$customer_name = sanitize_text_field($_POST['customer_name']);
$customer_email = sanitize_email($_POST['customer_email']);

// 4. Output Escaping (templates)
echo esc_html($booking->customer_name);
echo esc_attr($booking->customer_email);
echo esc_url($booking_url);

// 5. SQL Prepared Statements
$wpdb->prepare("SELECT * FROM {$wpdb->prefix}archeus_booking WHERE id = %d", $booking_id);
```

**XSS Prevention**:
- All user input sanitized on server side
- All output escaped in templates and JavaScript
- HTML in email templates uses `wp_kses_post()`

**CSRF Protection**:
- WordPress nonces for all AJAX operations
- Nonce verification before any data modification

---

## 💻 Development Workflow

### Local Development Setup

```bash
# 1. Navigate to plugin directory (Windows path)
cd C:\Code\my-wordpress\wordpress\wp-content\plugins\archeus-booking

# 2. Install PHP dependencies
composer install

# 3. Test plugin activation (via WordPress admin or WP-CLI)
wp plugin activate archeus-booking

# 4. Check database tables created
wp db query "SHOW TABLES LIKE 'wp_archeus_%'"

# 5. Verify plugin is active
wp plugin list --status=active
```

### WordPress WP-CLI Commands

```bash
# Plugin management
wp plugin activate archeus-booking
wp plugin deactivate archeus-booking
wp plugin uninstall archeus-booking

# Database operations
wp db query "SELECT COUNT(*) FROM wp_archeus_booking"
wp db export wp_archeus_backup.sql

# Clear cache after changes
wp cache flush
wp rewrite flush

# Check for errors
wp plugin status archeus-booking
```

### Testing Workflow

**Manual Testing Checklist**:
1. **Form Builder** - Create form with all field types, test validation
2. **Service Management** - Add services with different durations/prices
3. **Time Slot Setup** - Configure slots with capacity limits
4. **Booking Flow** - Complete full booking process from start to finish
5. **Admin Dashboard** - Test filtering, search, status changes, Excel export
6. **Email Notifications** - Verify all email triggers and tag replacements
7. **Calendar** - Check availability display and holiday management

**Browser Testing**:
- Chrome, Firefox, Safari, Edge (latest versions)
- Mobile: iOS Safari, Android Chrome
- Responsive breakpoints: 320px, 768px, 1024px, 1440px

**WordPress Compatibility**:
- Test with default Twenty Twenty-Four theme
- Test with popular page builders (Elementor, WPBakery)
- Check Classic Editor and Block Editor compatibility

### Debugging Tips

**Enable WordPress Debug Mode** (`wp-config.php`):
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

**Check Error Logs**:
```bash
# WordPress debug log
tail -f wp-content/debug.log

# PHP error log (depends on server config)
tail -f /var/log/php_errors.log
```

**AJAX Debugging**:
```javascript
// Frontend console logs
console.log('Form data:', formData);
console.log('AJAX response:', response);

// Backend error logging
error_log(print_r($booking_data, true));
error_log('Booking created with ID: ' . $booking_id);
```

**Database Query Debugging**:
```php
// WordPress database debug
$wpdb->show_errors();
$wpdb->print_error();
error_log($wpdb->last_query);
```

---

## 📋 Coding Standards & Conventions

### WordPress Coding Standards

**File Naming**:
- Classes: `class-booking-database.php` (lowercase with hyphens)
- Assets: `booking-flow.css`, `admin.js` (descriptive names)

**Class Naming**:
```php
// Class names: Capitalized with underscores
class Booking_Database {}
class Booking_Admin {}

// Method names: Lowercase with underscores
public function create_booking() {}
public function get_booking_by_id() {}
```

**Hook Naming**:
```php
// Action hooks
do_action('archeus_booking_before_create', $booking_data);
do_action('archeus_booking_after_create', $booking_id);

// Filter hooks
$email_content = apply_filters('archeus_booking_email_content', $content, $booking);
```

**Database Queries**:
```php
// Always use prepared statements
$booking = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}archeus_booking WHERE id = %d",
        $booking_id
    )
);

// Use WordPress table prefix
$table_name = $wpdb->prefix . 'archeus_booking';
```

**Internationalization (i18n)**:
```php
// Text domain: 'archeus-booking'
__('Booking created successfully', 'archeus-booking');
_e('Select a service', 'archeus-booking');
esc_html_e('Customer Name', 'archeus-booking');
```

### PHP Best Practices

**Type Safety**:
```php
// Use strict types where possible
public function create_booking(array $data): int {}
public function get_booking(int $id): ?object {}

// Validate input types
if (!is_numeric($booking_id)) {
    return false;
}
```

**Error Handling**:
```php
// Use try-catch for critical operations
try {
    $booking_id = $this->create_booking($data);
    $this->send_notification($booking_id);
} catch (Exception $e) {
    error_log('Booking creation failed: ' . $e->getMessage());
    return false;
}
```

**Code Organization**:
```php
// Group related methods together
// 1. Constructor and initialization
// 2. Public methods
// 3. Protected/private helper methods
// 4. AJAX handlers
// 5. Database operations
```

### JavaScript Best Practices

**jQuery Usage**:
```javascript
// Use WordPress jQuery noConflict wrapper
(function($) {
    'use strict';
    
    // Your code here
    
})(jQuery);
```

**AJAX Pattern**:
```javascript
$.ajax({
    url: bookingData.ajaxUrl,
    type: 'POST',
    data: {
        action: 'create_booking',
        nonce: bookingData.nonce,
        booking_data: formData
    },
    beforeSend: function() {
        // Show loading state
    },
    success: function(response) {
        if (response.success) {
            // Handle success
        } else {
            // Handle error
        }
    },
    error: function(xhr, status, error) {
        console.error('AJAX error:', error);
    }
});
```

**Data Validation**:
```javascript
// Validate before submission
function validateBookingForm() {
    let isValid = true;
    
    // Check required fields
    $('.required-field').each(function() {
        if (!$(this).val().trim()) {
            isValid = false;
            showError($(this), 'This field is required');
        }
    });
    
    return isValid;
}
```

### CSS Architecture

**Component-Based Organization**:
```css
/* admin.css - Admin dashboard styles */
/* booking-flow.css - Multi-step form styles */
/* calendar.css - Calendar component styles */
/* public.css - General public-facing styles */
```

**Naming Convention (BEM-inspired)**:
```css
.booking-form {}
.booking-form__field {}
.booking-form__field--error {}
.booking-form__submit-btn {}
.booking-form__submit-btn--disabled {}
```

**Responsive Design**:
```css
/* Mobile-first approach */
.booking-form {
    padding: 1rem;
}

@media (min-width: 768px) {
    .booking-form {
        padding: 2rem;
    }
}
```

---

## 🔄 Common Development Tasks

### Adding a New Form Field Type

**1. Update Database Schema** (if needed):
```php
// In includes/class-booking-database.php
// No schema change needed - uses JSON storage
```

**2. Add Field Type to Form Builder**:
```php
// In admin/class-booking-admin.php
$field_types = array(
    'text' => 'Text',
    'email' => 'Email',
    'number' => 'Number',
    'date' => 'Date',
    'time' => 'Time',
    'select' => 'Dropdown',
    'textarea' => 'Textarea',
    'file' => 'File Upload',
    'new_type' => 'New Field Type', // Add here
);
```

**3. Add Frontend Rendering**:
```php
// In public/class-booking-public.php
case 'new_type':
    echo '<input type="new_type" name="' . esc_attr($field['name']) . '" />';
    break;
```

**4. Add Validation**:
```javascript
// In assets/js/booking-flow.js
if (fieldType === 'new_type') {
    // Custom validation logic
}
```

### Adding a New Email Notification Trigger

**1. Define Email Template Setting**:
```php
// In booking-plugin.php or admin class
$email_settings['new_trigger_subject'] = 'New Trigger - #{booking_id}';
$email_settings['new_trigger_body'] = '<p>Email content with {tags}</p>';
```

**2. Send Email on Trigger**:
```php
// In appropriate class method
$this->send_email_notification(
    $booking_id,
    'new_trigger',
    $customer_email
);
```

**3. Update Admin Email Settings UI**:
```html
<!-- In admin template -->
<div class="email-template-section">
    <h3>New Trigger Email</h3>
    <input type="text" name="new_trigger_subject" />
    <textarea name="new_trigger_body"></textarea>
</div>
```

### Adding a New Booking Status

**1. Add Status to Database Enum** (optional):
```php
// In includes/class-booking-database.php
// Current statuses: pending, approved, completed, rejected
// Add in status dropdown/validation
```

**2. Update Admin Status Dropdown**:
```php
// In admin/class-booking-admin.php
$statuses = array(
    'pending' => 'Pending',
    'approved' => 'Approved',
    'completed' => 'Completed',
    'rejected' => 'Rejected',
    'new_status' => 'New Status', // Add here
);
```

**3. Add Status Color Coding** (optional):
```css
/* In assets/css/admin.css */
.status-badge.new-status {
    background-color: #your-color;
    color: #fff;
}
```

### Extending the Calendar System

**1. Add Calendar Feature**:
```php
// In includes/class-booking-calendar.php
public function add_recurring_holiday($date_pattern, $name) {
    // Implementation
}
```

**2. Add Admin UI**:
```html
<!-- In admin template -->
<button id="add-recurring-holiday">Add Recurring Holiday</button>
```

**3. Add AJAX Handler**:
```javascript
// In assets/js/admin.js
$('#add-recurring-holiday').on('click', function() {
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'add_recurring_holiday',
            nonce: nonce,
            // holiday data
        }
    });
});
```

---

## 🛡️ Security Considerations

### Input Validation & Sanitization

**Always Sanitize User Input**:
```php
// Text fields
$name = sanitize_text_field($_POST['name']);

// Email
$email = sanitize_email($_POST['email']);

// URL
$url = esc_url_raw($_POST['url']);

// Integer
$id = absint($_POST['id']);

// Textarea (preserves line breaks)
$message = sanitize_textarea_field($_POST['message']);

// HTML content (allows specific tags)
$content = wp_kses_post($_POST['content']);
```

**Validate Data Types**:
```php
// Check required fields
if (empty($customer_name) || empty($customer_email)) {
    wp_send_json_error('Required fields missing');
}

// Validate email format
if (!is_email($customer_email)) {
    wp_send_json_error('Invalid email format');
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $booking_date)) {
    wp_send_json_error('Invalid date format');
}
```

### Output Escaping

**Escape Based on Context**:
```php
// HTML context
echo esc_html($customer_name);

// Attribute context
echo '<input value="' . esc_attr($customer_email) . '" />';

// URL context
echo '<a href="' . esc_url($booking_url) . '">View</a>';

// JavaScript context
echo '<script>var name = "' . esc_js($customer_name) . '";</script>';

// Translation with escaping
echo esc_html__('Booking created', 'archeus-booking');
```

### Database Security

**Always Use Prepared Statements**:
```php
// CORRECT - Prepared statement
$booking = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}archeus_booking WHERE id = %d AND customer_email = %s",
        $booking_id,
        $customer_email
    )
);

// WRONG - SQL injection vulnerable
// $booking = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}archeus_booking WHERE id = $booking_id");
```

**Use WordPress Database Methods**:
```php
// INSERT
$wpdb->insert(
    $table_name,
    array(
        'customer_name' => $name,
        'customer_email' => $email,
    ),
    array('%s', '%s')
);

// UPDATE
$wpdb->update(
    $table_name,
    array('status' => $status),
    array('id' => $booking_id),
    array('%s'),
    array('%d')
);
```

### File Upload Security

**Validate File Uploads**:
```php
// Check file type
$allowed_types = array('jpg', 'jpeg', 'png', 'pdf');
$file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

if (!in_array(strtolower($file_extension), $allowed_types)) {
    wp_send_json_error('Invalid file type');
}

// Check file size (5MB limit)
if ($_FILES['file']['size'] > 5 * 1024 * 1024) {
    wp_send_json_error('File too large');
}

// Use WordPress upload functions
$upload = wp_handle_upload($_FILES['file'], array('test_form' => false));
```

---

## 🐛 Troubleshooting Guide

### Common Issues & Solutions

#### Issue: Database Tables Not Created
```php
// Solution: Manually trigger table creation
if (class_exists('Booking_Database')) {
    $db = new Booking_Database();
    $db->create_tables();
}

// Check if tables exist
global $wpdb;
$table_name = $wpdb->prefix . 'archeus_booking';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if (!$table_exists) {
    error_log('Table does not exist: ' . $table_name);
}
```

#### Issue: AJAX Requests Failing
```javascript
// Debug AJAX errors
$.ajax({
    // ... your config
    error: function(xhr, status, error) {
        console.error('Status:', status);
        console.error('Error:', error);
        console.error('Response:', xhr.responseText);
    }
});
```

```php
// Backend debugging
add_action('wp_ajax_your_action', 'debug_ajax');
function debug_ajax() {
    error_log('AJAX called');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Your logic
    
    wp_send_json_success('Debug message');
}
```

#### Issue: Email Notifications Not Sending
```php
// Check WordPress mail function
$test_email = wp_mail(
    'test@example.com',
    'Test Subject',
    'Test message',
    array('Content-Type: text/html; charset=UTF-8')
);

if (!$test_email) {
    error_log('wp_mail() failed');
    // Check server mail configuration
}

// Use WordPress SMTP plugin for better email delivery
// Recommended: WP Mail SMTP, Easy WP SMTP
```

#### Issue: Form Builder Not Saving
```javascript
// Check JSON stringify
console.log('Form data:', JSON.stringify(formData));

// Verify AJAX success callback
success: function(response) {
    console.log('Save response:', response);
    if (!response.success) {
        console.error('Save failed:', response.data);
    }
}
```

```php
// Backend validation
if (!isset($_POST['form_data']) || empty($_POST['form_data'])) {
    error_log('Form data missing or empty');
    wp_send_json_error('No form data received');
}

$form_data = json_decode(stripslashes($_POST['form_data']), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('JSON decode error: ' . json_last_error_msg());
    wp_send_json_error('Invalid JSON data');
}
```

#### Issue: Time Slots Not Displaying
```php
// Check schedule configuration
$schedules = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}archeus_schedules WHERE is_active = 1"
);
error_log('Active schedules: ' . print_r($schedules, true));

// Verify date/time format
$booking_date = date('Y-m-d', strtotime($request_date));
error_log('Formatted date: ' . $booking_date);
```

#### Issue: Permission Errors in Admin
```php
// Check user capabilities
if (!current_user_can('manage_options')) {
    error_log('User lacks manage_options capability');
    wp_send_json_error('Insufficient permissions');
}

// Alternative capability check
if (!current_user_can('edit_posts')) {
    // Use appropriate capability
}
```

---

## 📚 Resources & References

### WordPress Documentation
- [Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Database API](https://developer.wordpress.org/apis/handbook/database/)
- [AJAX in WordPress](https://developer.wordpress.org/plugins/javascript/ajax/)
- [Security Best Practices](https://developer.wordpress.org/plugins/security/)

### External Libraries
- [PhpSpreadsheet Documentation](https://phpspreadsheet.readthedocs.io/)
- [jQuery Documentation](https://api.jquery.com/)

### Tools & Utilities
- [WP-CLI](https://wp-cli.org/) - WordPress command-line interface
- [Query Monitor](https://querymonitor.com/) - WordPress debugging plugin
- [Debug Bar](https://wordpress.org/plugins/debug-bar/) - WordPress debug toolbar

### Testing Resources
- [WordPress Plugin Unit Tests](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

---

## 🤖 AI Agent-Specific Guidelines

### For Code Generation Agents (GitHub Copilot, Cursor, etc.)

**When Generating Code**:
1. ✅ Always use WordPress coding standards (underscores, not camelCase)
2. ✅ Include nonce validation for AJAX operations
3. ✅ Sanitize all input, escape all output
4. ✅ Use prepared statements for database queries
5. ✅ Add i18n functions for user-facing strings
6. ✅ Follow existing file naming conventions
7. ✅ Match existing code style in the file being edited

**Code Patterns to Follow**:
```php
// AJAX handler pattern
public function ajax_handler() {
    check_ajax_referer('booking_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Unauthorized', 'archeus-booking'));
    }
    
    $data = sanitize_text_field($_POST['data']);
    
    // Your logic here
    
    wp_send_json_success(array(
        'message' => __('Success', 'archeus-booking'),
        'data' => $result
    ));
}
```

### For Chat-Based Agents (Claude, GPT-4, etc.)

**When Explaining Code**:
1. Reference specific file paths and line numbers
2. Explain WordPress-specific concepts (hooks, filters, nonces)
3. Highlight security implications
4. Suggest testing approaches
5. Mention potential edge cases

**When Debugging**:
1. Ask about WordPress version and PHP version
2. Check database table existence
3. Verify plugin activation
4. Review error logs (debug.log)
5. Test with WordPress default theme

**When Refactoring**:
1. Maintain backward compatibility
2. Keep database schema migrations safe (use dbDelta)
3. Don't break existing shortcodes
4. Test admin and frontend separately
5. Consider multisite compatibility

### For Autonomous Agents (Devin, Cursor Agent Mode, etc.)

**Task Execution Order**:
1. **Read** existing code and understand architecture
2. **Plan** changes considering impact on other components
3. **Implement** following existing patterns
4. **Test** both admin and frontend functionality
5. **Verify** no WordPress errors in debug.log
6. **Document** changes in code comments

**Testing Checklist Before Completion**:
- [ ] Plugin activates without errors
- [ ] Database tables created successfully
- [ ] Admin dashboard loads correctly
- [ ] Frontend booking form displays properly
- [ ] AJAX operations work (check browser console)
- [ ] Email notifications send successfully
- [ ] No PHP errors in debug.log
- [ ] No JavaScript errors in console
- [ ] Responsive design works on mobile

---

## 🚀 Quick Reference Cheat Sheet

### Key Functions & Methods

```php
// Get booking by ID
$booking = $booking_db->get_booking($booking_id);

// Create new booking
$booking_id = $booking_db->create_booking($booking_data);

// Update booking status
$booking_db->update_booking_status($booking_id, 'approved');

// Get available time slots
$slots = $time_slots_manager->get_available_slots($service_id, $date);

// Send email notification
$this->send_email_notification($booking_id, $type, $recipient_email);

// Get form by ID
$form = $booking_db->get_form($form_id);

// Get service by ID
$service = $booking_db->get_service($service_id);
```

### Important Constants

```php
ARCHEUS_BOOKING_VERSION  // Plugin version
ARCHEUS_BOOKING_PATH     // Plugin directory path
ARCHEUS_BOOKING_URL      // Plugin URL
```

### Database Tables

```php
$wpdb->prefix . 'archeus_booking'         // Main bookings
$wpdb->prefix . 'archeus_booking_forms'   // Form definitions
$wpdb->prefix . 'archeus_booking_history' // Booking history
$wpdb->prefix . 'archeus_services'        // Services
$wpdb->prefix . 'archeus_schedules'       // Time schedules
```

### Shortcodes

```php
[archeus_booking id="1"]           // Display booking flow
[archeus_booking_calendar]         // Display calendar
```

### AJAX Actions

```php
// Admin actions
'create_booking_form'
'update_booking_status'
'export_bookings'
'save_email_settings'

// Public actions
'submit_booking'
'get_available_slots'
'check_availability'
```

---

## 📝 Contribution Guidelines

### Before Making Changes

1. **Read this document** thoroughly
2. **Review existing code** in the relevant files
3. **Check CLAUDE.md** for Claude-specific guidelines
4. **Test locally** before committing

### Code Review Checklist

- [ ] Follows WordPress coding standards
- [ ] Includes security measures (nonce, sanitization, escaping)
- [ ] Uses prepared statements for database queries
- [ ] Includes i18n functions for strings
- [ ] Backward compatible with existing data
- [ ] Tested on WordPress 5.0+ and PHP 7.4+
- [ ] No errors in debug.log
- [ ] Works with default WordPress theme
- [ ] Responsive design on mobile devices
- [ ] Commented complex logic

### Git Commit Messages

```
feat: Add recurring schedule support for time slots
fix: Resolve email notification sending issue
refactor: Improve database query performance
docs: Update AGENTS.md with new field type instructions
style: Format admin CSS according to standards
test: Add unit tests for booking creation
```

---

## 📄 License & Credits

**License**: GPLv2 or later  
**Author**: Firmansyah Pramudia Ariyanto  
**GitHub**: [@firmxn](https://github.com/firmxn)  
**Website**: [firmxn.dev](https://firmxn.dev)

---

**Last Updated**: 2025-10-29  
**Document Version**: 1.0.0

---

*This document is maintained as part of the Archeus Booking System codebase. When working with this project, AI agents should refer to this guide for architectural decisions, coding standards, and best practices.*
