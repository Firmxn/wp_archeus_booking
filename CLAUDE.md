# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Archeus Booking System is a comprehensive WordPress plugin for managing bookings with intelligent form builder, service management, and calendar integration. The plugin follows WordPress best practices with a modular, class-based architecture.

## Development Commands

### WordPress Plugin Development
```bash
# Plugin location (relative to WordPress root)
cd wp-content/plugins/archeus-booking/

# Install PHP dependencies
composer install

# Test plugin activation/deactivation (via WordPress admin or WP-CLI)
wp plugin activate archeus-booking
wp plugin deactivate archeus-booking
```

### Database Operations
The plugin automatically handles database migrations on activation. Key tables created:
- `wp_archeus_booking` - Main booking records
- `wp_archeus_booking_forms` - Dynamic form definitions
- `wp_archeus_booking_history` - Booking archive
- `wp_archeus_services` - Service catalog
- `wp_archeus_schedules` - Time slot management

## Architecture Overview

### Core Components

**Main Plugin File** (`booking-plugin.php`)
- Single entry point following WordPress plugin standards
- Defines plugin constants and includes core classes
- Manages activation/deactivation hooks and database migrations
- Includes automated database migration system for schema evolution

**Database Layer** (`includes/class-booking-database.php`)
- Centralized database operations using WordPress standards
- Implements `dbDelta()` for safe schema migrations
- Handles automated cleanup and maintenance tasks
- Manages unified booking table with JSON field storage for flexibility

**Admin Interface** (`admin/class-booking-admin.php`)
- AJAX-driven admin dashboard with comprehensive booking management
- Form builder with drag-and-drop functionality
- Service and time slot management
- Export capabilities using PhpSpreadsheet library
- Email template management with dynamic tag replacement

**Frontend System** (`public/class-booking-public.php`)
- Multi-step booking forms with real-time validation
- SessionStorage-based form state persistence
- Integration with Elementor page builder
- Smart field auto-detection for customer information

**Booking Flow Architecture**
- Multi-step flow system with JSON-based form definitions
- Component architecture: Forms → Services → Time Slots → Confirmation
- Conditional logic and flow branching capabilities
- Session-based form data persistence across steps

### Key Design Patterns

**Database Schema**
- Unified booking table with JSON field storage for maximum flexibility
- Normalized service and schedule tables for performance
- History table for audit trails and completed bookings
- Automatic migration system for schema evolution

**Form System**
- Dynamic form definitions stored as JSON with validation rules
- Smart field auto-detection (customer_name, customer_email)
- Multi-step flows with session state management
- Real-time validation with comprehensive error states

**Frontend Architecture**
- Component-based CSS organization (admin.css, booking-flow.css, calendar.css, etc.)
- jQuery-based JavaScript with AJAX for real-time interactions
- Security-focused implementation with HTML escaping and XSS prevention
- SessionStorage for form state persistence

## Important Technical Details

### Dependencies
- PHP 7.4+
- WordPress 5.0+
- PhpSpreadsheet (via Composer) for Excel export functionality

### Security Implementation
- WordPress nonce validation for all AJAX operations
- Comprehensive input sanitization and XSS prevention
- Proper capability checking for admin operations
- HTML escaping in templates and JavaScript
- Security-focused validation system with comprehensive error handling

### Database Migration System
- Automatic schema evolution using `dbDelta()`
- Column additions handled safely without data loss
- Automated cleanup routines for expired bookings and temporary data
- Migration system handles legacy data transformations

### Email System
- Customizable email templates with dynamic tag replacement
- Multi-language support with localized tags
- HTML email support with WordPress wp_mail() integration
- Multiple notification triggers (confirmation, status changes, etc.)

### Frontend Validation
- Real-time form validation with comprehensive error states
- Session-based form data persistence for multi-step flows
- Security-focused JavaScript with proper escaping
- Smart field auto-detection for customer information

## Shortcode System

### Primary Shortcodes
- `[archeus_booking id="<flow_id>"]` - Display multi-step booking flow
- `[archeus_booking_calendar]` - Show availability calendar

### Integration Points
- Elementor page builder support
- WordPress widget compatibility
- Template function calls for theme integration

## File Organization

```
archeus-booking/
├── admin/              # Admin interface and backend management
├── assets/             # CSS, JavaScript, and static resources
├── includes/           # Core business logic and shared functionality
├── public/             # Frontend functionality and user interactions
├── vendor/             # Composer dependencies (PhpSpreadsheet)
├── views/              # Template files for rendering interfaces
└── booking-plugin.php  # Main plugin bootstrap file
```

## Key Architectural Principles

### Multi-Step Booking Flow System
- Each flow consists of multiple sections (forms, services, time slots, confirmation)
- Flow definitions stored as JSON with conditional logic support
- Session-based state management for user progress tracking
- Component-based architecture for easy extension

### Smart Form Field System
- Automatic detection of customer_name and customer_email fields
- Comprehensive validation rules with real-time feedback
- Support for various field types (text, email, number, date, time, select, textarea, file)
- JSON-based form definitions for maximum flexibility

### Time Slot Management
- Sophisticated availability system with capacity controls
- Buffer time management between appointments
- Recurring schedule patterns with holiday exceptions
- Real-time availability updates based on booking changes

### Admin Dashboard Architecture
- AJAX-driven interface for real-time operations
- Comprehensive filtering and search capabilities
- Export functionality for reporting and analysis
- Status management workflow for booking lifecycle

## Development Guidelines

### When Working with Forms
- Use the smart field auto-detection system for customer_name and customer_email
- Implement validation using the built-in validation framework
- Leverage SessionStorage for multi-step form state persistence
- Follow the established error state patterns with proper CSS classes

### When Working with Database
- Use the centralized database class for all operations
- Implement proper escaping for all database queries
- Follow WordPress database best practices
- Use JSON field storage for flexible form data

### When Working with Frontend
- Use the modular CSS architecture for consistent styling
- Implement AJAX operations with proper nonce validation
- Follow the established validation state patterns
- Ensure proper HTML escaping for all user-generated content

### When Working with Admin Interface
- Use AJAX-driven interface patterns for consistency
- Implement proper capability checks for all admin operations
- Follow the established UI patterns for better UX consistency
- Use the centralized database operations for data management