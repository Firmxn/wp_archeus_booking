jQuery(document).ready(function($) {
    console.log('Calendar script loaded and ready.');

    // Get max months setting from server
    var maxMonths = calendar_ajax.max_months || 6;
    
    // Calendar navigation logic removed to prevent conflict with inline scripts in booking flow.
    // The inline script in includes/class-booking-shortcodes.php now handles this for the user-facing calendar.

    // Date selection logic removed. This is now handled by the inline script in includes/class-booking-shortcodes.php to prevent conflicts.
});