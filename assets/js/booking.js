/**
 * Booking Dashboard JavaScript
 * Handles booking management functionality including pagination
 *
 * @package ArcheusBooking
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initializeBookingPaginationDisplay();
    });

    // Initialize booking pagination display on page load
    function initializeBookingPaginationDisplay() {
        // Check if we're on booking management page
        if (window.location.href.indexOf('archeus-booking-management') === -1) {
            return; // Only run on booking management page
        }

        var $totalTypeCount = $('.total-type-count');
        if ($totalTypeCount.length) {
            var totalCount = parseInt($totalTypeCount.text()) || 0;
            var $pagination = $('.booking-pagination-container');

            // Debug: Log the initial values
            console.log('initializeBookingPaginationDisplay - totalCount:', totalCount, 'from element:', $totalTypeCount.text());

            // Show/hide pagination based on item count
            if (totalCount <= 10) {
                console.log('Initial load - Hiding booking pagination because totalCount <= 10:', totalCount);
                $pagination.hide();
            } else {
                console.log('Initial load - Showing booking pagination because totalCount > 10:', totalCount);
                $pagination.show();
            }
        } else {
            console.log('initializeBookingPaginationDisplay - No .total-type-count element found');
        }
    }

    // AJAX Pagination functionality - SPECIFIC TO BOOKING PAGE ONLY
    $(document).on('click', '.booking-nav-button, .booking-page-button', function(e) {
        e.preventDefault();
        console.log('Booking pagination clicked');
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
            var status = $("#booking-status-filter").val();
            var flowId = $("#booking-flow-filter").length
                ? $("#booking-flow-filter").val()
                : $("#ab-flow-select").length
                ? $("#ab-flow-select").val()
                : 0;

            // Update URL without page reload
            var newUrl = window.location.href.split('?')[0] + '?page=archeus-booking-management&paged=' + page;
            if (status) newUrl += '&status=' + encodeURIComponent(status);
            if (flowId) newUrl += '&flow_id=' + encodeURIComponent(flowId);
            window.history.pushState({ page: page }, '', newUrl);

            // Load bookings for current page with pagination
            $.ajax({
                url: ArcheusBookingAdmin.ajax_url,
                type: "POST",
                dataType: "text",
                data: {
                    action: "get_bookings",
                    status: status,
                    flow_id: flowId,
                    page: page,
                    limit: 10,
                    nonce: ArcheusBookingAdmin.nonce,
                },
                success: function (resp) {
                    console.log('Booking AJAX response received:', resp);
                    try {
                        if (typeof resp === "string") {
                            var firstBrace = resp.indexOf("{");
                            if (firstBrace > 0) resp = resp.slice(firstBrace);
                            resp = JSON.parse(resp);
                        }
                    } catch (e) {
                        console.error("Booking JSON parse error (pagination)", e, resp);
                        alert("Invalid server response while loading page.");
                        return;
                    }
                    if (resp && resp.success) {
                        console.log('Booking AJAX success - data:', resp.data);
                        updateBookingTable(resp.data.bookings || resp.data);
                        // Update pagination display
                        if (resp.data && resp.data.total_count !== undefined) {
                            updateBookingPaginationDisplay(resp.data.total_count, resp.data.current_page, resp.data.per_page);
                        }
                        // Show success message
                        if (typeof showToast !== 'undefined') {
                            showToast("Bookings loaded successfully.", "success");
                        }
                    } else {
                        var msg =
                            resp && resp.data && resp.data.message
                                ? resp.data.message
                                : "Failed to load bookings.";
                        alert(msg);
                    }
                },
                error: function () {
                    alert("Terjadi kesalahan saat memuat bookings.");
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

    // Update booking table with new data
    function updateBookingTable(bookings) {
        console.log('updateBookingTable called with:', bookings);
        var $tableBody = $('#bookings-table-body');
        if (!$tableBody.length) {
            console.error('Booking table body not found!');
            return;
        }

        // Clear existing rows
        $tableBody.empty();

        if (!bookings || bookings.length === 0) {
            $tableBody.html('<tr class="no-data"><td colspan="8" class="no-data-cell">Data tidak tersedia atau data kosong.</td></tr>');
            return;
        }

        // Generate rows for each booking
        bookings.forEach(function(booking, index) {
            var row = '<tr data-id="' + escapeHtml(booking.id) + '">';

            // Index Number
            row += '<td class="col-id">' + escapeHtml(booking.index) + '</td>';

            // Customer Name
            row += '<td class="col-name" title="' + escapeHtml(booking.customer_name) + '">' + escapeHtml(booking.customer_name) + '</td>';

            // Booking Date
            row += '<td>' + escapeHtml(booking.booking_date) + '</td>';

            // Booking Time
            row += '<td>' + escapeHtml(booking.booking_time) + '</td>';

            // Service Type
            row += '<td>' + escapeHtml(booking.service_type) + '</td>';

            // Status
            var statusClass = booking.status || '';
            var statusText = statusClass.charAt(0).toUpperCase() + statusClass.slice(1);
            row += '<td><span class="status-badge status-' + statusClass + '">' + escapeHtml(statusText) + '</span></td>';

            // Created Date
            row += '<td>' + escapeHtml(booking.created_at) + '</td>';

            // Actions
            row += '<td>';
            row += '<button type="button" class="button view-details-btn" data-id="' + escapeHtml(booking.id) + '" title="Lihat Detail">';
            row += '<span class="dashicons dashicons-visibility" aria-hidden="true"></span>';
            row += '<span class="screen-reader-text">Lihat Detail</span>';
            row += '</button> ';
            row += '<button type="button" class="button delete-booking" data-id="' + escapeHtml(booking.id) + '" title="Hapus Booking">';
            row += '<span class="dashicons dashicons-trash" aria-hidden="true"></span>';
            row += '<span class="screen-reader-text">Hapus</span>';
            row += '</button>';
            row += '</td>';

            row += '</tr>';

            // Details row (initially hidden)
            row += '<tr class="booking-details-row" data-id="' + escapeHtml(booking.id) + '" style="display: none;">';
            row += '<td colspan="8">';
            row += '<div class="booking-details">';
            row += '<h4>Additional Information</h4>';
            row += '<p>Tidak ada detail tambahan.</p>';
            row += '</div>';
            row += '</td>';
            row += '</tr>';

            $tableBody.append(row);
        });
    }

    // Update pagination display for booking page
    function updateBookingPaginationDisplay(totalCount, currentPage, perPage) {
        // Check if we're on booking management page
        if (window.location.href.indexOf('archeus-booking-management') === -1) {
            return; // Only run on booking management page
        }

        var $pagination = $('.booking-pagination-container');

        // Debug: Log the parameters
        console.log('updateBookingPaginationDisplay called with:', {totalCount: totalCount, currentPage: currentPage, perPage: perPage});
        console.log('$pagination element:', $pagination);
        console.log('$pagination.length:', $pagination.length);

        if ($pagination.length === 0) {
            console.log('Booking pagination container not found!');
            return;
        }

        // Only show pagination if more than 10 items
        if (totalCount <= 10) {
            console.log('Hiding booking pagination because totalCount <= 10:', totalCount);
            $pagination.hide();
            return;
        }

        console.log('Showing booking pagination because totalCount > 10:', totalCount);
        $pagination.show();

        var totalPages = Math.ceil(totalCount / perPage);
        var $paginationLinks = $pagination.find('.booking-pagination-links');

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
            paginationHtml += '<a class="first-page booking-nav-button" data-page="1"></a>';
            paginationHtml += '<a class="prev-page booking-nav-button" data-page="' + (currentPage - 1) + '"></a>';
        }

        // Page numbers - show max 3 pages with ellipsis (AJAX version)
        console.log('Booking pagination debug:', {totalCount: totalCount, perPage: perPage, totalPages: totalPages, currentPage: currentPage});

        if (totalPages <= 3) {
            // Show all pages if 3 or less
            for (var i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    paginationHtml += '<span class="paging-input">' + i + '</span>';
                } else {
                    paginationHtml += '<a class="page-numbers booking-page-button" data-page="' + i + '">' + i + '</a>';
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
                        paginationHtml += '<a class="page-numbers booking-page-button" data-page="' + i + '">' + i + '</a>';
                    }
                }
                paginationHtml += '<span class="pagination-dots">...</span>';
                paginationHtml += '<a class="page-numbers booking-page-button" data-page="' + totalPages + '">' + totalPages + '</a>';
            } else if (currentPage === totalPages || currentPage === totalPages - 1) {
                // Last 2 pages: Show 1 ... (last-2), (last-1), last
                paginationHtml += '<a class="page-numbers booking-page-button" data-page="1">1</a>';
                paginationHtml += '<span class="pagination-dots">...</span>';
                for (var i = totalPages - 2; i <= totalPages; i++) {
                    if (i === currentPage) {
                        paginationHtml += '<span class="paging-input">' + i + '</span>';
                    } else {
                        paginationHtml += '<a class="page-numbers booking-page-button" data-page="' + i + '">' + i + '</a>';
                    }
                }
            } else {
                // Middle pages: Show 1 ... (current-1), current, (current+1) ... last
                paginationHtml += '<a class="page-numbers booking-page-button" data-page="1">1</a>';
                paginationHtml += '<span class="pagination-dots">...</span>';

                var startPage = Math.max(2, currentPage - 1);
                var endPage = Math.min(totalPages - 1, currentPage + 1);

                for (var i = startPage; i <= endPage; i++) {
                    if (i === currentPage) {
                        paginationHtml += '<span class="paging-input">' + i + '</span>';
                    } else {
                        paginationHtml += '<a class="page-numbers booking-page-button" data-page="' + i + '">' + i + '</a>';
                    }
                }

                paginationHtml += '<span class="pagination-dots">...</span>';
                paginationHtml += '<a class="page-numbers booking-page-button" data-page="' + totalPages + '">' + totalPages + '</a>';
            }
        }

        // Next and last links (AJAX version)
        if (currentPage < totalPages) {
            paginationHtml += '<a class="next-page booking-nav-button" data-page="' + (currentPage + 1) + '"></a>';
            paginationHtml += '<a class="last-page booking-nav-button" data-page="' + totalPages + '"></a>';
        }

        console.log('Generated booking pagination HTML:', paginationHtml);
        $paginationLinks.html(paginationHtml);
    }

    // Utility function to escape HTML
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) {
            return '';
        }
        return unsafe.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

})(jQuery);