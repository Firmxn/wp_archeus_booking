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
        basic_info: 'Informasi Dasar',
        customer_name: 'Nama Pemesan',
        customer_email: 'Email',
        booking_date: 'Tanggal Pemesanan',
        booking_time: 'Waktu Pemesanan',
        service_type: 'Jenis Layanan',
        price: 'Harga Layanan',
        status: 'Status',
        moved_at: ' Tanggal Perubahan',
        rejection_reason: 'Alasan Penolakan',
        custom_fields: 'Data Tambahan',
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
    $(document).on('click', '.view-details-button', function(e) {
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
        html += '<tr><th>' + archeus_booking_l10n.price + '</th><td>' + (data.price ? 'Rp ' + parseFloat(data.price).toLocaleString('id-ID') : '-') + '</td></tr>';
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

    // Export history to HTML
    function exportHistoryToHTML() {
        console.log('Export to HTML clicked');

        // Show loading state
        var $exportButton = $('.export-html-button');
        var originalText = $exportButton.html();
        $exportButton.html('<span class="dashicons dashicons-update spinning"></span> Exporting...');
        $exportButton.prop('disabled', true);

        // Set hidden form fields with current filter values
        $('#export-status').val($('#status').val());
        $('#export-search').val($('#s').val());
        $('#export-date-from').val($('#date_from').val());
        $('#export-date-to').val($('#date_to').val());
        $('#export-flow-id').val($('#flow_id').val());
        $('#export-orderby').val($('#orderby').val());
        $('#export-order').val($('#order').val());

        console.log('Export parameters:', {
            status: $('#status').val(),
            flow_id: $('#flow_id').val(),
            search: $('#s').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val(),
            orderby: $('#orderby').val(),
            order: $('#order').val()
        });

        // Submit form for direct download
        $('#export-history-form').submit();

        // Restore button after a delay
        setTimeout(function() {
            $exportButton.html(originalText);
            $exportButton.prop('disabled', false);
        }, 3000);
    }

    // Export history to Excel
    function exportHistoryToExcel() {
        console.log('Export to Excel clicked');

        // Show loading state
        var $exportButton = $('.export-excel-button');
        var originalText = $exportButton.html();
        $exportButton.html('<span class="dashicons dashicons-update spinning"></span> Exporting...');
        $exportButton.prop('disabled', true);

        // Set hidden form fields with current filter values
        $('#export-csv-status').val($('#status').val());
        $('#export-csv-search').val($('#s').val());
        $('#export-csv-date-from').val($('#date_from').val());
        $('#export-csv-date-to').val($('#date_to').val());
        $('#export-csv-flow-id').val($('#flow_id').val());
        $('#export-csv-orderby').val($('#orderby').val());
        $('#export-csv-order').val($('#order').val());

        console.log('Excel Export parameters:', {
            status: $('#status').val(),
            flow_id: $('#flow_id').val(),
            search: $('#s').val(),
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val(),
            orderby: $('#orderby').val(),
            order: $('#order').val()
        });

        // Submit form for direct download
        $('#export-history-csv-form').submit();

        // Restore button after a delay
        setTimeout(function() {
            $exportButton.html(originalText);
            $exportButton.prop('disabled', false);
        }, 3000);
    }

    // Initialize export button click handlers
    $(document).on('click', '.export-html-button', function(e) {
        e.preventDefault();
        exportHistoryToHTML();
    });

    $(document).on('click', '.export-excel-button', function(e) {
        e.preventDefault();
        exportHistoryToExcel();
    });

    // Clear History functionality
    $(document).on('click', '.clear-history-button', function(e) {
        e.preventDefault();

        // Show confirmation dialog
        if (confirm('Apakah Anda yakin ingin menghapus semua data history? Data yang dihapus tidak dapat dikembalikan.')) {
            // Show loading state
            var $button = $(this);
            var originalText = $button.text();
            $button.prop('disabled', true).text('Menghapus...');

            // Send AJAX request to clear history
            $.ajax({
                url: ArcheusBookingHistory.ajax_url,
                type: 'POST',
                data: {
                    action: 'clear_booking_history',
                    nonce: ArcheusBookingHistory.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message and reload page
                        alert(response.data.message);
                        window.location.reload();
                    } else {
                        // Show error message
                        alert(response.data ? response.data.message : 'Gagal menghapus data history.');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    // Show error message
                    alert('Request failed: ' + error);
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
    });

    // AJAX Pagination functionality - SPECIFIC TO HISTORY PAGE ONLY
    $(document).on('click', '.history-nav-button, .history-page-button', function(e) {
        e.preventDefault();
        console.log('History pagination clicked');
        console.log('Clicked element:', $(this));
        console.log('Data page attribute:', $(this).data('page'));

        var page = $(this).data('page');

        if (page) {
            page = parseInt(page);

            // Show loading overlay
            if (!document.getElementById("ab-loading-style")) {
                var css =
                    "\n.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}\n.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}\n.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}\n@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}\n";
                var style = document.createElement("style");
                style.id = "ab-loading-style";
                style.textContent = css;
                document.head.appendChild(style);
            }

            var $overlay = $("<div>")
                .addClass("ab-loading-overlay")
                .html(
                    '<div class="ab-loading-spinner"></div><div class="ab-loading-text">Loading...</div>'
                );

            $("body").append($overlay);

            // Get current filter values
            var status = $('#status').val();
            var search = $('#s').val();
            var dateFrom = $('#date_from').val();
            var dateTo = $('#date_to').val();
            var flowId = $('#flow_id').val();
            var orderBy = $('#orderby').val();
            var order = $('#order').val();

            // Update URL without page reload
            var newUrl = window.location.href.split('?')[0] + '?page=archeus-booking-history&paged=' + page;
            if (status) newUrl += '&status=' + encodeURIComponent(status);
            if (search) newUrl += '&s=' + encodeURIComponent(search);
            if (dateFrom) newUrl += '&date_from=' + encodeURIComponent(dateFrom);
            if (dateTo) newUrl += '&date_to=' + encodeURIComponent(dateTo);
            if (flowId) newUrl += '&flow_id=' + encodeURIComponent(flowId);
            if (orderBy) newUrl += '&orderby=' + encodeURIComponent(orderBy);
            if (order) newUrl += '&order=' + encodeURIComponent(order);

            window.history.pushState({ page: page }, '', newUrl);

            // Load history bookings for current page with pagination
            $.ajax({
                url: ArcheusBookingHistory.ajax_url,
                type: "POST",
                dataType: "text",
                data: {
                    action: "get_history_bookings",
                    status: status,
                    search: search,
                    date_from: dateFrom,
                    date_to: dateTo,
                    flow_id: flowId,
                    orderby: orderBy,
                    order: order,
                    page: page,
                    limit: 10,
                    nonce: ArcheusBookingHistory.nonce,
                },
                success: function (resp) {
                    console.log('History AJAX response received:', resp);
                    try {
                        if (typeof resp === "string") {
                            var firstBrace = resp.indexOf("{");
                            if (firstBrace > 0) resp = resp.slice(firstBrace);
                            resp = JSON.parse(resp);
                        }
                    } catch (e) {
                        console.error("History JSON parse error (pagination)", e, resp);
                        alert("Invalid server response while loading page.");
                        return;
                    }
                    if (resp && resp.success) {
                        console.log('History AJAX success - data:', resp.data);
                        updateHistoryTable(resp.data.bookings);
                        // Update pagination display
                        if (resp.data && resp.data.total_count !== undefined) {
                            updateHistoryPaginationDisplay(resp.data.total_count, resp.data.current_page, resp.data.per_page);
                        }
                        // Show success message
                        if (typeof showToast !== 'undefined') {
                            showToast("History bookings loaded successfully.", "success");
                        }
                    } else {
                        var msg =
                            resp && resp.data && resp.data.message
                                ? resp.data.message
                                : "Failed to load history bookings.";
                        alert(msg);
                    }
                },
                error: function () {
                    alert("Terjadi kesalahan saat memuat history bookings.");
                },
                complete: function () {
                    // Remove loading overlay
                    if ($overlay) {
                        $overlay.remove();
                    }
                },
            });
        }
    });

    // Update history table with new data
    function updateHistoryTable(bookings) {
        console.log('updateHistoryTable called with:', bookings);
        var $tableBody = $('#booking-history-table tbody');
        if (!$tableBody.length) {
            console.error('History table body not found!');
            return;
        }

        // Clear existing rows
        $tableBody.empty();

        if (!bookings || bookings.length === 0) {
            $tableBody.html('<tr><td colspan="12" style="text-align: center;">Tidak ada data booking history ditemukan.</td></tr>');
            return;
        }

        // Generate rows for each booking
        bookings.forEach(function(booking) {
            var row = '<tr>';

            // Checkbox
            row += '<th scope="row" class="check-column">';
            row += '<input type="checkbox" name="booking[]" value="' + escapeHtml(booking.id) + '" />';
            row += '</th>';

            // ID
            row += '<td>' + escapeHtml(booking.display_id) + '</td>';

            // Customer Name
            row += '<td>' + escapeHtml(booking.customer_name) + '</td>';

            // Customer Email
            row += '<td>';
            if (booking.customer_email) {
                row += '<a href="mailto:' + escapeHtml(booking.customer_email) + '">' + escapeHtml(booking.customer_email) + '</a>';
            } else {
                row += '-';
            }
            row += '</td>';

            // Booking Date
            row += '<td>' + escapeHtml(booking.booking_date) + '</td>';

            // Booking Time
            row += '<td>' + escapeHtml(booking.booking_time) + '</td>';

            // Service Type
            row += '<td>' + escapeHtml(booking.service_type) + '</td>';

            // Price
            row += '<td>' + (booking.price ? 'Rp ' + parseFloat(booking.price).toLocaleString('id-ID') : '-') + '</td>';

            // Flow Name
            row += '<td>' + escapeHtml(booking.flow_name || '-') + '</td>';

            // Status
            var statusClass = booking.status || '';
            var statusText = statusClass.charAt(0).toUpperCase() + statusClass.slice(1);
            row += '<td><span class="status-badge status-' + statusClass + '">' + escapeHtml(statusText) + '</span></td>';

            // Moved At
            row += '<td>' + escapeHtml(booking.moved_at) + '</td>';

            // Actions
            row += '<td>';
            row += '<button type="button" class="button view-details-button" data-history-id="' + escapeHtml(booking.id) + '">' + ArcheusBookingHistory.view_details_text + '</button>';
            row += '</td>';

            row += '</tr>';
            $tableBody.append(row);
        });
    }

    // Update pagination display for history page
    function updateHistoryPaginationDisplay(totalCount, currentPage, perPage) {
        var $pagination = $('.history-pagination-container');

        // Debug: Log the parameters
        console.log('updateHistoryPaginationDisplay called with:', {totalCount: totalCount, currentPage: currentPage, perPage: perPage});
        console.log('$pagination element:', $pagination);
        console.log('$pagination.length:', $pagination.length);

        if ($pagination.length === 0) {
            console.log('History pagination container not found!');
            return;
        }

        // Only show pagination if more than 10 items
        if (totalCount <= 10) {
            console.log('Hiding history pagination because totalCount <= 10:', totalCount);
            $pagination.hide();
            return;
        }

        console.log('Showing history pagination because totalCount > 10:', totalCount);
        $pagination.show();

        var totalPages = Math.ceil(totalCount / perPage);
        var $paginationLinks = $pagination.find('.history-pagination-links');

        // Update displaying num text
        var startItem = (currentPage - 1) * perPage + 1;
        var endItem = Math.min(currentPage * perPage, totalCount);
        $pagination.find('.displaying-num').html(
            'Menampilkan ' + startItem + '–' + endItem + ' dari <span class="total-type-count">' + totalCount + '</span>'
        );

        // Generate pagination links
        var paginationHtml = '';

        // First and previous links
        if (currentPage > 1) {
            paginationHtml += '<a class="first-page history-nav-button" data-page="1"></a>';
            paginationHtml += '<a class="prev-page history-nav-button" data-page="' + (currentPage - 1) + '"></a>';
        }

        // Page numbers - show max 3 pages with ellipsis (AJAX version)
        console.log('Pagination debug:', {totalCount: totalCount, perPage: perPage, totalPages: totalPages, currentPage: currentPage});

        if (totalPages <= 3) {
            // Show all pages if 3 or less
            for (var i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    paginationHtml += '<span class="paging-input">' + i + '</span>';
                } else {
                    paginationHtml += '<a class="page-numbers history-page-button" data-page="' + i + '">' + i + '</a>';
                }
            }
        } else {
            // Show max 3 pages with ellipsis for more (AJAX version)
            if (currentPage === 1 || currentPage === 2) {
                // Page 1 & 2: Show 1, 2, 3 ... last
                for (var i = 1; i <= 3; i++) {
                    if (i === currentPage) {
                        paginationHtml += '<span class="paging-input">' + i + '</span>';
                    } else {
                        paginationHtml += '<a class="page-numbers history-page-button" data-page="' + i + '">' + i + '</a>';
                    }
                }
                paginationHtml += '<span class="pagination-dots">...</span>';
                paginationHtml += '<a class="page-numbers history-page-button" data-page="' + totalPages + '">' + totalPages + '</a>';
            } else if (currentPage === totalPages || currentPage === totalPages - 1) {
                // Last 2 pages: Show 1 ... (last-2), (last-1), last
                paginationHtml += '<a class="page-numbers button" data-page="1">1</a>';
                paginationHtml += '<span class="pagination-dots">...</span>';
                for (var i = totalPages - 2; i <= totalPages; i++) {
                    if (i === currentPage) {
                        paginationHtml += '<span class="paging-input">' + i + '</span>';
                    } else {
                        paginationHtml += '<a class="page-numbers history-page-button" data-page="' + i + '">' + i + '</a>';
                    }
                }
            } else {
                // Middle pages: Show 1 ... (current-1), current, (current+1) ... last
                paginationHtml += '<a class="page-numbers button" data-page="1">1</a>';
                paginationHtml += '<span class="pagination-dots">...</span>';

                var startPage = Math.max(2, currentPage - 1);
                var endPage = Math.min(totalPages - 1, currentPage + 1);

                for (var i = startPage; i <= endPage; i++) {
                    if (i === currentPage) {
                        paginationHtml += '<span class="paging-input">' + i + '</span>';
                    } else {
                        paginationHtml += '<a class="page-numbers history-page-button" data-page="' + i + '">' + i + '</a>';
                    }
                }

                paginationHtml += '<span class="pagination-dots">...</span>';
                paginationHtml += '<a class="page-numbers history-page-button" data-page="' + totalPages + '">' + totalPages + '</a>';
            }
        }

        // Next and last links (AJAX version)
        if (currentPage < totalPages) {
            paginationHtml += '<a class="next-page history-nav-button" data-page="' + (currentPage + 1) + '"></a>';
            paginationHtml += '<a class="last-page history-nav-button" data-page="' + totalPages + '"></a>';
        }

        console.log('Generated history pagination HTML:', paginationHtml);
        $paginationLinks.html(paginationHtml);
    }

    // Initialize history pagination display on page load
    function initializeHistoryPaginationDisplay() {
        var $totalTypeCount = $('.total-type-count');
        if ($totalTypeCount.length) {
            var totalCount = parseInt($totalTypeCount.text()) || 0;
            var $pagination = $('.history-pagination-container');

            // Debug: Log the initial values
            console.log('initializeHistoryPaginationDisplay - totalCount:', totalCount, 'from element:', $totalTypeCount.text());

            // Show/hide pagination based on item count
            if (totalCount <= 10) {
                console.log('Initial load - Hiding history pagination because totalCount <= 10:', totalCount);
                $pagination.hide();
            } else {
                console.log('Initial load - Showing history pagination because totalCount > 10:', totalCount);
                $pagination.show();
            }
        } else {
            console.log('initializeHistoryPaginationDisplay - No .total-type-count element found');
        }
    }

    // Run history pagination initialization when DOM is ready
    $(document).ready(function() {
        initializeHistoryPaginationDisplay();
    });

});