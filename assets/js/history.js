jQuery(document).ready(function($) {
    'use strict';

    // Debug: Confirm script is loading
    console.log('History.js script loaded and ready');

    // Helper functions (defined at the top to ensure they're available)
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatFieldValue(value) {
        if (!value && value !== 0) return '';

        if (Array.isArray(value)) {
            if (value.length === 0) return '';
            return '<ul><li>' + value.map(function(v) { return escapeHtml(String(v)); }).join('</li><li>') + '</ul>';
        } else if (typeof value === 'object') {
            return '<pre>' + escapeHtml(JSON.stringify(value, null, 2)) + '</pre>';
        } else {
            return escapeHtml(String(value));
        }
    }

    // Extract filename from full path
    function formatFilePath(path) {
        if (!path) return '';

        // Check if it looks like a file path (contains / or \)
        if (path.includes('/') || path.includes('\\')) {
            // Extract filename from path
            var filename = path.split('/').pop().split('\\').pop();

            // If the extracted filename is not empty and different from original path
            if (filename && filename !== path) {
                return escapeHtml(filename);
            }
        }

        // If it doesn't look like a path or extraction failed, return original value
        return escapeHtml(String(path));
    }

    // Convert snake_case to capitalized words like "Customer Name"
    function formatHeaderName(key) {
        if (!key) return '';

        // Replace underscores with spaces and capitalize each word
        var formatted = key
            .replace(/_/g, ' ')                      // Replace underscores with spaces
            .replace(/-/g, ' ')                      // Replace hyphens with spaces
            .toLowerCase()                            // Convert to lowercase first
            .replace(/\b\w/g, function(l) {       // Capitalize first letter of each word
                return l.toUpperCase();
            });

        return escapeHtml(formatted);
    }

    // Initialize localization object
    var archeus_booking_l10n = window.archeus_booking_l10n || {
        basic_info: 'Basic Information',
        customer_name: 'Customer Name',
        customer_email: 'Customer Email',
        booking_date: 'Booking Date',
        booking_time: 'Booking Time',
        service_type: 'Service Type',
        status: 'Status',
        moved_at: 'Created At',
        rejection_reason: 'Rejection Reason',
        custom_fields: 'Custom Fields',
        close: 'Close',
        loading: 'Loading...',
        error: 'Error'
    };

    // Initialize
    var ArcheusBookingHistory = window.ArcheusBookingHistory || {
        ajax_url: '',
        nonce: '',
        view_details_text: 'View Details',
        close_text: 'Close',
        loading_text: 'Loading...'
    };

    // Debug: Log the localization object
    console.log('ArcheusBookingHistory localization object:', ArcheusBookingHistory);
    console.log('archeus_booking_l10n object:', archeus_booking_l10n);

    // Modal functionality
    var $modal = $('#history-details-modal');
    var $modalBody = $modal.find('.modal-body');

    // Debug: Check if modal element exists
    console.log('Modal element found:', $modal.length > 0 ? 'Yes' : 'No');

    // Open modal when view details button is clicked
    $(document).on('click', '.view-history-details', function(e) {
        e.preventDefault();
        console.log('View Details button clicked');

        var $button = $(this);
        var historyId = $button.data('history-id');

        console.log('History ID:', historyId);
        console.log('ArcheusBookingHistory object:', ArcheusBookingHistory);

        if (!historyId) {
            console.log('No history ID found');
            return;
        }

        // Show loading state
        $modalBody.html('<div class="loading">' + ArcheusBookingHistory.loading_text + '</div>');
        $modal.show();

        // Load history details via AJAX
        $.ajax({
            url: ArcheusBookingHistory.ajax_url,
            type: 'POST',
            data: {
                action: 'get_history_details',
                history_id: historyId,
                nonce: ArcheusBookingHistory.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderHistoryDetails(response.data);
                } else {
                    showError(response.data ? response.data.message : 'Failed to load history details.');
                }
            },
            error: function(xhr, status, error) {
                showError('Request failed: ' + error);
            }
        });
    });

    // Close modal when close button is clicked
    $modal.on('click', '.modal-close', function(e) {
        e.preventDefault();
        $modal.hide();
        $modalBody.empty();
    });

    // Close modal when clicking outside modal content
    $modal.on('click', function(e) {
        if (e.target === $modal[0]) {
            $modal.hide();
            $modalBody.empty();
        }
    });

    // Render history details in modal
    function renderHistoryDetails(data) {
        var html = '<div class="history-details-content">';

        // Basic Information Section
        html += '<div class="details-section">';
        html += '<h4>' + archeus_booking_l10n.basic_info + '</h4>';
        html += '<table class="details-table">';
        // History ID and Original Booking ID are hidden from display but kept in database
        html += '<tr><th>' + archeus_booking_l10n.customer_name + '</th><td>' + escapeHtml(data.customer_name) + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.customer_email + '</th><td>' + escapeHtml(data.customer_email) + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.booking_date + '</th><td>' + escapeHtml(data.booking_date) + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.booking_time + '</th><td>' + escapeHtml(data.booking_time) + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.service_type + '</th><td>' + escapeHtml(data.service_type) + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.status + '</th><td><span class="status-badge status-' + data.status + '">' + escapeHtml(data.status.charAt(0).toUpperCase() + data.status.slice(1)) + '</span></td></tr>';

        // Flow Name is hidden from display but kept in database

        html += '<tr><th>' + archeus_booking_l10n.moved_at + '</th><td>' + escapeHtml(data.moved_at) + '</td></tr>';

        // Moved By is hidden from display but kept in database

        // Add rejection reason to basic information if it exists
        if (data.rejection_reason) {
            html += '<tr><th>' + archeus_booking_l10n.rejection_reason + '</th><td>' + escapeHtml(data.rejection_reason) + '</td></tr>';
        }

        html += '</table>';
        html += '</div>';

        // Custom Fields Section
        if (data.custom_fields && Object.keys(data.custom_fields).length > 0) {
            html += '<div class="details-section">';
            html += '<h4>' + archeus_booking_l10n.custom_fields + '</h4>';
            html += '<table class="custom-fields-table">';

            $.each(data.custom_fields, function(key, value) {
                if (value && value !== '') {
                    html += '<tr>';
                    html += '<th>' + formatHeaderName(key) + '</th>';
                    html += '<td>' + formatFieldValue(key, value) + '</td>';
                    html += '</tr>';
                }
            });

            html += '</table>';
            html += '</div>';
        }

        // Payload Data is hidden from display but kept in database

        html += '</div>';
        $modalBody.html(html);
    }

    // Format field value for display
    function formatFieldValue(key, value) {
        if (!value && value !== 0) return '';

        // Special handling for file path fields
        var fileFields = ['bukti_vaksinasi', 'foto', 'gambar', 'file', 'attachment', 'dokumen'];
        if (typeof value === 'string' && fileFields.some(function(field) {
            return key.toLowerCase().includes(field) || field.includes(key.toLowerCase());
        })) {
            return formatFilePath(value);
        }

        if (Array.isArray(value)) {
            if (value.length === 0) return '';
            return '<ul><li>' + value.map(function(v) { return escapeHtml(String(v)); }).join('</li><li>') + '</ul>';
        } else if (typeof value === 'object' && value !== null) {
            return '<pre>' + escapeHtml(JSON.stringify(value, null, 2)) + '</pre>';
        } else {
            return escapeHtml(String(value));
        }
    }

    // Show error message
    function showError(message) {
        $modalBody.html('<div class="error-message">' + escapeHtml(message) + '</div>');
    }

});