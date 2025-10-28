# 🎯 Archeus Booking System

<div align="center">

![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-blue?style=for-the-badge&logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-GPLv2-green?style=for-the-badge)
![Version](https://img.shields.io/badge/Version-1.0.0-orange?style=for-the-badge)

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
- **Dynamic Content**: Smart tag replacement with {booking_id}, {customer_name}, etc.
- **HTML Support**: Rich text emails with custom branding

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

---

## 🚀 Installation

### Method 1: WordPress Admin
1. Download the plugin ZIP file
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload `archeus-booking.zip`
4. Activate the plugin

### Method 2: FTP/SFTP
1. Extract the ZIP file
2. Upload `archeus-booking` folder to `/wp-content/plugins/`
3. Go to WordPress admin and activate the plugin

### Method 3: Manual Installation
1. Copy the plugin folder to your WordPress plugins directory
2. Navigate to the Plugins page in your WordPress admin
3. Find "Archeus Booking System" and click "Activate"

---

## ⚡ Quick Start

1. **Configure Services** → Bookings → Service Management
2. **Set Time Slots** → Bookings → Time Slot Management
3. **Create Forms** → Bookings → Booking Forms
4. **Build Flows** → Bookings → Booking Flow Management
5. **Embed Shortcode**: `[archeus_booking id="1"]` on any page
6. **Display Calendar**: Use `[archeus_booking_calendar]` for availability view

---

## 📖 Usage

### Basic Booking Form
```shortcode
[archeus_booking id="1"]
```

### Availability Calendar
```shortcode
[archeus_booking_calendar]
```

### Advanced Configuration
The plugin supports extensive customization options:
- Custom form fields with validation
- Multiple service types with different pricing
- Complex time slot configurations
- Custom email templates
- Integration with payment gateways (via hooks)

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

### [1.0.0] - 2024-10-13
- ✨ Initial stable release
- 🎨 Complete form builder with smart auto-detection
- 🔄 Flexible booking flow management system
- 🛠️ Comprehensive service management interface
- ⏰ Advanced time slot configuration with capacity control
- 📧 Customizable email notification system
- 📅 Interactive calendar with availability management
- 📊 Full admin dashboard with advanced filtering
- 📱 Fully responsive design for all devices
- 🔧 Fixed dropdown positioning issues in form builder
- 🚀 Performance optimizations and security improvements

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