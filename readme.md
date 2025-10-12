# 🎯 Archeus Booking System

<div align="center">

![WordPress](https://img.shields.io/badge/WordPress-6.3%2B-blue?style=for-the-badge&logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-7.0%2B-purple?style=for-the-badge&logo=php&logoColor=white)
![License](https://img.shields.io/badge/License-GPLv2-green?style=for-the-badge)
![Version](https://img.shields.io/badge/Version-1.0.0-orange?style=for-the-badge)

**A comprehensive WordPress booking plugin with intelligent form builder, service management, and booking flows**

[🚀 Live Demo](#) • [📖 Documentation](#-features) • [⚡ Installation](#-installation) • [🐛 Issues](https://github.com/firmxn/archeus-booking/issues)

</div>

---

## ✨ Features

### 🎨 **Smart Form Builder**
- **Dynamic Field Types**: Text, Email, Number, Date, Time, Select, Textarea, File Upload
- **Auto-Detection**: Intelligent customer_name & customer_email field detection with required validation
- **Real-time Validation**: Live form validation with user-friendly feedback
- **Customizable Layout**: Drag-and-drop form building interface

### 🔄 **Booking Flow Management**
- **Multi-Step Flows**: Create custom booking flows with multiple sections
- **Flexible Sections**: Forms, Services, Time Slots, Confirmation pages
- **Conditional Logic**: Show/hide sections based on user input
- **Flow Templates**: Pre-built templates for common use cases

### 🛠️ **Service Management**
- **Service Catalog**: Manage different services with pricing and duration
- **Availability Settings**: Set service-specific availability rules
- **Pricing Flexibility**: Fixed prices, variable pricing, or free services
- **Service Categories**: Organize services into logical groups

### ⏰ **Time Slot Management**
- **Customizable Slots**: Configure available booking time slots
- **Duration Control**: Set different durations for different services
- **Buffer Times**: Add breaks between appointments
- **Recurring Schedules**: Set up recurring availability patterns

### 📧 **Email Notification System**
- **Customizable Templates**: Edit email content for all notifications
- **Multiple Triggers**: Customer confirmations, admin notifications, status changes
- **Tag Replacement**: Dynamic content with {booking_id}, {customer_name}, etc.
- **HTML Support**: Rich text emails with branding

### 📊 **Admin Dashboard**
- **Comprehensive Overview**: All bookings in one place
- **Status Management**: Track booking lifecycle (pending → approved → completed)
- **Filtering & Search**: Find bookings quickly by date, status, or customer
- **Export Functionality**: Export booking data for reporting

### 📱 **Responsive Design**
- **Mobile-First**: Works perfectly on all devices
- **Admin Interface**: Clean, intuitive admin panel
- **Frontend Forms**: Beautiful, user-friendly booking forms
- **Accessibility**: WCAG compliant design

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

### Method 3: Composer
```bash
composer require firmxn/archeus-booking
```

---

## ⚡ Quick Start

1. **Configure Services** → Bookings → Service Management
2. **Set Time Slots** → Bookings → Time Slot Management
3. **Create Forms** → Bookings → Booking Forms
4. **Build Flows** → Bookings → Booking Flow Management
5. **Embed Shortcode**: `[archeus_booking id="1"]` on any page

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

### Custom Flow with Multiple Services
```shortcode
[archeus_booking id="2" service="consultation"]
```

---

## 🔧 Configuration

### Service Setup
- Define service names, descriptions, and pricing
- Set duration and availability rules
- Configure service-specific requirements

### Time Slot Configuration
- Set available hours and days
- Configure slot durations
- Add buffer times between appointments

### Form Building
- Add custom fields with validation rules
- Set up conditional logic
- Configure auto-detection for key fields

### Email Templates
- Customize confirmation emails
- Set up admin notifications
- Configure status change notifications

---

## 🎨 Screenshots

<!-- Add screenshots here when available -->
<!-- ![Admin Dashboard](screenshots/admin-dashboard.png)
![Form Builder](screenshots/form-builder.png)
![Booking Calendar](screenshots/booking-calendar.png) -->

---

## 🛠️ Development

### Requirements
- WordPress 6.3+
- PHP 7.0+
- MySQL 5.6+

### Local Development
```bash
# Clone repository
git clone https://github.com/firmxn/archeus-booking.git

# Install dependencies
composer install

# Set up local environment
npm install
npm run build
```

### Contributing
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📋 Changelog

### [1.0.0] - 2024-10-12
- ✨ Initial stable release
- 🎨 Complete form builder with auto-detection
- 🔄 Flexible booking flow management
- 🛠️ Comprehensive service management
- ⏰ Advanced time slot configuration
- 📧 Customizable email notification system
- 📊 Full admin dashboard with filtering
- 📱 Responsive design for all devices

---

## 🤝 Support

### Documentation
- 📖 [Full Documentation](https://github.com/firmxn/archeus-booking/wiki)
- 🎬 [Video Tutorials](https://youtube.com/playlist)
- 💡 [FAQ Section](https://github.com/firmxn/archeus-booking/wiki/FAQ)

### Getting Help
- 🐛 [Report Issues](https://github.com/firmxn/archeus-booking/issues)
- 💬 [Discussions](https://github.com/firmxn/archeus-booking/discussions)
- 📧 [Email Support](mailto:firmansyah@example.com)

### Community
- 💬 [WordPress.org Support Forum](https://wordpress.org/support/plugin/archeus-booking/)
- 🐙 [GitHub Discussions](https://github.com/firmxn/archeus-booking/discussions)
- 🐦 [Twitter Updates](https://twitter.com/firmxn)

---

## 📄 License

This project is licensed under the GPLv2 License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- [WordPress](https://wordpress.org/) for the amazing CMS platform
- [Bootstrap](https://getbootstrap.com/) for the UI components
- [jQuery](https://jquery.com/) for JavaScript utilities
- All contributors and users who make this plugin better

---

<div align="center">

**Made with ❤️ by [Firmansyah Pramudia Ariyanto](https://github.com/firmxn)**

[🌐 Website](https://firmxn.dev) • [💼 LinkedIn](https://linkedin.com/in/firmxn) • [🐙 GitHub](https://github.com/firmxn)

![Star History](https://img.shields.io/github/stars/firmxn/archeus-booking?style=social)

</div>