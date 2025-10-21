jQuery(document).ready(function($) {
    'use strict';

    // Debug: Confirm script is loading
    console.log('History.js script loaded and ready');

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
        html += '<tr><th>' + archeus_booking_l10n.history_id + '</th><td>#' + data.id + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.original_booking_id + '</th><td>#' + data.original_booking_id + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.customer_name + '</th><td>' + escapeHtml(data.customer_name) + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.customer_email + '</th><td>' + escapeHtml(data.customer_email) + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.booking_date + '</th><td>' + escapeHtml(data.booking_date) + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.booking_time + '</th><td>' + escapeHtml(data.booking_time) + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.service_type + '</th><td>' + escapeHtml(data.service_type) + '</td></tr>';
        html += '<tr><th>' + archeus_booking_l10n.status + '</th><td><span class="status-badge status-' + data.status + '">' + escapeHtml(data.status.charAt(0).toUpperCase() + data.status.slice(1)) + '</span></td></tr>';

        if (data.flow_name) {
            html += '<tr><th>' + archeus_booking_l10n.flow_name + '</th><td>' + escapeHtml(data.flow_name) + '</td></tr>';
        }

        html += '<tr><th>' + archeus_booking_l10n.moved_at + '</th><td>' + escapeHtml(data.moved_at) + '</td></tr>';

        if (data.moved_by) {
            html += '<tr><th>' + archeus_booking_l10n.moved_by + '</th><td>' + escapeHtml(data.moved_by.name) + ' (' + escapeHtml(data.moved_by.email) + ')</td></tr>';
        }

        html += '</table>';
        html += '</div>';

        // Completion/Rejection Notes Section
        if (data.completion_notes || data.rejection_reason) {
            html += '<div class="details-section">';
            html += '<h4>' + archeus_booking_l10n.notes_section + '</h4>';

            if (data.completion_notes) {
                html += '<p><strong>' + archeus_booking_l10n.completion_notes + ':</strong></p>';
                html += '<p>' + escapeHtml(data.completion_notes) + '</p>';
            }

            if (data.rejection_reason) {
                html += '<p><strong>' + archeus_booking_l10n.rejection_reason + ':</strong></p>';
                html += '<p>' + escapeHtml(data.rejection_reason) + '</p>';
            }

            html += '</div>';
        }

        // Custom Fields Section
        if (data.custom_fields && Object.keys(data.custom_fields).length > 0) {
            html += '<div class="details-section">';
            html += '<h4>' + archeus_booking_l10n.custom_fields + '</h4>';
            html += '<table class="custom-fields-table">';

            $.each(data.custom_fields, function(key, value) {
                if (value && value !== '') {
                    html += '<tr>';
                    html += '<th>' + escapeHtml(key) + '</th>';
                    html += '<td>' + formatFieldValue(value) + '</td>';
                    html += '</tr>';
                }
            });

            html += '</table>';
            html += '</div>';
        }

        // Payload Data Section
        if (data.payload && Object.keys(data.payload).length > 0) {
            html += '<div class="details-section">';
            html += '<h4>' + archeus_booking_l10n.payload_data + '</h4>';
            html += '<pre class="payload-data">' + escapeHtml(JSON.stringify(data.payload, null, 2)) + '</pre>';
            html += '</div>';
        }

        html += '</div>';
        $modalBody.html(html);
    }

    // Format field value for display
    function formatFieldValue(value) {
        if (Array.isArray(value)) {
            return '<ul><li>' + value.map(function(v) { return escapeHtml(String(v)); }).join('</li><li>') + '</ul>';
        } else if (typeof value === 'object' && value !== null) {
            return '<pre>' + escapeHtml(JSON.stringify(value, null, 2)) + '</pre>';
        } else {
            return escapeHtml(String(value));
        }
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Show error message
    function showError(message) {
        $modalBody.html('<div class="error-message">' + escapeHtml(message) + '</div>');
    }

    // Localization defaults
    if (typeof archeus_booking_l10n === 'undefined') {
        window.archeus_booking_l10n = {
            basic_info: 'Basic Information',
            history_id: 'History ID',
            original_booking_id: 'Original Booking ID',
            customer_name: 'Customer Name',
            customer_email: 'Customer Email',
            booking_date: 'Booking Date',
            booking_time: 'Booking Time',
            service_type: 'Service Type',
            status: 'Status',
            flow_name: 'Flow Name',
            moved_at: 'Moved At',
            moved_by: 'Moved By',
            notes_section: 'Notes',
            completion_notes: 'Completion Notes',
            rejection_reason: 'Rejection Reason',
            custom_fields: 'Custom Fields',
            payload_data: 'Payload Data'
        };
    }

});