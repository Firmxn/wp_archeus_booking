// Debug: Check if functions are loaded
jQuery(document).ready(function($) {
    console.log('Archeus Admin JS loaded - Functions available:', {
        showStatusChangeDialog: typeof window.showStatusChangeDialog,
        showDeleteConfirmationDialog: typeof window.showDeleteConfirmationDialog,
        showDeleteConfirm: typeof window.showDeleteConfirm,
        updateBookingStatus: typeof window.updateBookingStatus,
        jQuery: typeof jQuery
    });

    // Define global functions directly on window object
    window.showStatusChangeDialog = function(callback, newStatus, prevStatus) {
        console.log('showStatusChangeDialog called with:', { newStatus, prevStatus });
        // Remove any existing dialogs
        $('.ab-dialog-overlay').remove();

        var statusLabels = {
            'pending': 'Menunggu',
            'approved': 'Disetujui',
            'completed': 'Selesai',
            'rejected': 'Ditolak'
        };

        var statusTo = statusLabels[newStatus] || newStatus;
        var statusFrom = statusLabels[prevStatus] || prevStatus;

        var dialogHtml = '<div class="ab-dialog-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;">' +
            '<div class="ab-dialog" style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-width: 400px; width: 90%;">' +
                '<h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #1f2937;">Konfirmasi Perubahan Status</h3>' +
                '<p style="margin: 0 0 20px 0; color: #4b5563; line-height: 1.5;">Apakah Anda yakin ingin mengubah status booking dari <strong>"' + statusFrom + '"</strong> ke <strong>"' + statusTo + '"</strong>?</p>' +
                '<div style="display: flex; gap: 12px; justify-content: flex-end;">' +
                    '<button type="button" class="ab-dialog-cancel button button-secondary" style="padding: 8px 16px; font-size: 14px;">Batal</button>' +
                    '<button type="button" class="ab-dialog-confirm button button-primary" style="padding: 8px 16px; font-size: 14px;">Ya, Ubah Status</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        var $dialog = $(dialogHtml).appendTo('body');

        // Handle button clicks
        $dialog.find('.ab-dialog-cancel').on('click', function() {
            console.log('Dialog cancelled');
            $dialog.remove();
            callback(false);
        });

        $dialog.find('.ab-dialog-confirm').on('click', function() {
            console.log('Dialog confirmed');
            $dialog.remove();
            callback(true);
        });

        // Close on overlay click
        $dialog.on('click', function(e) {
            if (e.target === this) {
                console.log('Dialog overlay clicked - cancelled');
                $dialog.remove();
                callback(false);
            }
        });

        // Add enter key support
        $dialog.on('keydown', function(e) {
            if (e.key === 'Enter') {
                $dialog.find('.ab-dialog-confirm').click();
            } else if (e.key === 'Escape') {
                $dialog.find('.ab-dialog-cancel').click();
            }
        });

        // Focus confirm button
        setTimeout(function() {
            $dialog.find('.ab-dialog-confirm').focus();
        }, 100);
    };

    window.showDeleteConfirmationDialog = function(callback, bookingId) {
        console.log('showDeleteConfirmationDialog called with:', { bookingId });
        // Remove any existing dialogs
        $('.ab-dialog-overlay').remove();

        var dialogHtml = '<div class="ab-dialog-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;">' +
            '<div class="ab-dialog" style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-width: 400px; width: 90%;">' +
                '<h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #1f2937;">Konfirmasi Hapus Booking</h3>' +
                '<p style="margin: 0 0 20px 0; color: #4b5563; line-height: 1.5;">Apakah Anda yakin ingin menghapus booking ini? Tindakan ini tidak dapat dibatalkan.</p>' +
                '<div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px; margin-bottom: 20px;">' +
                    '<p style="margin: 0; color: #991b1b; font-size: 14px;">Booking ID: <strong>#' + bookingId + '</strong></p>' +
                '</div>' +
                '<div style="display: flex; gap: 12px; justify-content: flex-end;">' +
                    '<button type="button" class="ab-dialog-cancel button button-secondary" style="padding: 8px 16px; font-size: 14px;">Batal</button>' +
                    '<button type="button" class="ab-dialog-confirm button button-primary" style="background: #dc2626; border-color: #dc2626; padding: 8px 16px; font-size: 14px;">Ya, Hapus</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        var $dialog = $(dialogHtml).appendTo('body');

        // Handle button clicks
        $dialog.find('.ab-dialog-cancel').on('click', function() {
            console.log('Delete dialog cancelled');
            $dialog.remove();
            callback(false);
        });

        $dialog.find('.ab-dialog-confirm').on('click', function() {
            console.log('Delete dialog confirmed');
            $dialog.remove();
            callback(true);
        });

        // Close on overlay click
        $dialog.on('click', function(e) {
            if (e.target === this) {
                console.log('Delete dialog overlay clicked - cancelled');
                $dialog.remove();
                callback(false);
            }
        });

        // Add enter key support
        $dialog.on('keydown', function(e) {
            if (e.key === 'Enter') {
                $dialog.find('.ab-dialog-confirm').click();
            } else if (e.key === 'Escape') {
                $dialog.find('.ab-dialog-cancel').click();
            }
        });

        // Focus confirm button
        setTimeout(function() {
            $dialog.find('.ab-dialog-confirm').focus();
        }, 100);
    };

    // Legacy function for compatibility with inline script in admin class
    // Helper function to update booking status via AJAX
    window.updateBookingStatus = function(bookingId, newStatus, prevStatus, $sel, $menu, $item, $label) {
        console.log('Archeus: Sending status change request - Booking ID:', bookingId, 'New Status:', newStatus);
        console.log('Archeus: AJAX request data:', { action: 'update_booking_status', booking_id: bookingId, status: newStatus, nonce: archeus_booking_ajax.nonce });

        // Proceed to save with full-page overlay
        if (!document.getElementById('ab-loading-style')) {
            var css = '\n.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}\n.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}\n.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}\n@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}\n';
            var styleTag = document.createElement('style');
            styleTag.id = 'ab-loading-style';
            styleTag.type = 'text/css';
            styleTag.appendChild(document.createTextNode(css));
            document.head.appendChild(styleTag);
        }
        var $overlay = $('<div class="ab-loading-overlay"><div class="ab-loading-spinner"></div><div class="ab-loading-text">Menyimpan...</div></div>').appendTo('body');

        // Disable the dropdown
        if ($sel) $sel.prop('disabled', true);

        $.ajax({
            url: archeus_booking_ajax.ajax_url,
            type: 'POST',
            dataType: 'text',
            data: { action: 'update_booking_status', booking_id: bookingId, status: newStatus, nonce: archeus_booking_ajax.nonce },
            success: function(resp) {
                console.log('Archeus: AJAX response received:', resp);
                try {
                    if (typeof resp === 'string') {
                        var firstBrace = resp.indexOf('{');
                        if (firstBrace > 0) resp = resp.slice(firstBrace);
                        resp = JSON.parse(resp);
                    }
                    console.log('Archeus: Parsed response:', resp);
                } catch (e) {
                    console.error('Admin JSON parse error (update status)', e, resp);
                    showToast('Invalid server response while updating status.', 'error');
                    if ($sel) $sel.val(prevStatus);
                    if ($overlay) { $overlay.remove(); }
                    return;
                }
                if (resp && resp.success) {
                    showToast((resp.data && resp.data.message) || 'Status updated.', 'success');

                    // Update UI elements if provided
                    if ($sel && $menu && $item && $label) {
                        // Update the select value
                        $sel.val(newStatus);
                        $menu.find('.ab-dd-item').removeClass('is-selected');
                        $item.addClass('is-selected');
                        $label.text($item.text());

                        // Adjust visibility of 'completed' option
                        if (newStatus === 'approved') {
                            if ($sel.find('option[value="completed"]').length === 0) {
                                $sel.append('<option value="completed">Selesai</option>');
                            }
                        } else if (newStatus !== 'completed') {
                            // Hide completed unless status is approved or already completed
                            $sel.find('option[value="completed"]').remove();
                        }

                        $sel.data('prev', newStatus);
                    }

                    // Show loading overlay a bit longer, then refresh page to show updated data
                    setTimeout(function() {
                        console.log('Refreshing page to show updated booking data...');
                        window.location.reload();
                    }, 1500); // Wait 1.5 seconds before refresh to show success message
                } else {
                    var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to update status.';
                    showToast(msg, 'error');
                    if ($sel) $sel.val(prevStatus);
                    if ($sel) $sel.prop('disabled', false);
                    if ($overlay) { $overlay.remove(); }
                }
            },
            error: function(xhr, status, error) {
                console.error('Archeus: AJAX error occurred:', { xhr: xhr, status: status, error: error });
                showToast('An error occurred while updating the booking status.', 'error');
                if ($sel) $sel.val(prevStatus);
                if ($sel) $sel.prop('disabled', false);
                if ($overlay) { $overlay.remove(); }
            }
        });
    };

    window.showDeleteConfirm = function(message, redirectUrl) {
        console.log('showDeleteConfirm called with:', { message, redirectUrl });

        // Remove any existing dialogs
        $('.ab-dialog-overlay').remove();

        // Check if this is a booking delete (no redirectUrl) or other delete (with redirectUrl)
        var isBookingDelete = !redirectUrl || redirectUrl === '';

        var dialogHtml = '<div class="ab-dialog-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;">' +
            '<div class="ab-dialog" style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-width: 400px; width: 90%;">' +
                '<h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #1f2937;">Konfirmasi Hapus</h3>' +
                '<p style="margin: 0 0 20px 0; color: #4b5563; line-height: 1.5;">' + message + '</p>' +
                '<div style="display: flex; gap: 12px; justify-content: flex-end;">' +
                    '<button type="button" class="ab-dialog-cancel button button-secondary" style="padding: 8px 16px; font-size: 14px;">Batal</button>' +
                    '<button type="button" class="ab-dialog-confirm button button-primary" style="background: #dc2626; border-color: #dc2626; padding: 8px 16px; font-size: 14px;">Ya, Hapus</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        var $dialog = $(dialogHtml).appendTo('body');

        // Handle button clicks
        $dialog.find('.ab-dialog-cancel').on('click', function() {
            console.log('Delete confirm dialog cancelled');
            $dialog.remove();
            // Clean up any pending delete markers
            $('.delete-booking[data-delete-pending="true"]').removeAttr('data-delete-pending');
        });

        $dialog.find('.ab-dialog-confirm').on('click', function() {
            console.log('Delete confirm dialog confirmed');
            $dialog.remove();

            if (isBookingDelete) {
                // For booking delete, we'll handle it differently since we don't have the booking ID here
                // The actual delete will be handled by the event handler that called this dialog
                console.log('Booking delete confirmed - event handler will execute the delete');
                // We need to trigger the delete confirmation in a different way
                // Since we can't easily get the booking ID here, we'll use a different approach
                setTimeout(function() {
                    // Find the last clicked delete button and trigger its confirm action
                    var $deleteBtn = $('.delete-booking[data-delete-pending="true"]');
                    if ($deleteBtn.length) {
                        var bookingId = $deleteBtn.data('id');
                        $deleteBtn.removeAttr('data-delete-pending');

                        // Show loading overlay
                        var $overlay = $('<div class="ab-loading-overlay" style="position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;"><div class="ab-loading-spinner" style="width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;"></div><div class="ab-loading-text" style="margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;">Menghapus...</div></div>').appendTo('body');

                        $.ajax({
                            url: archeus_booking_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'delete_booking',
                                booking_id: bookingId,
                                nonce: archeus_booking_ajax.nonce
                            },
                            success: function(response) {
                                if ($overlay) $overlay.remove();
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert('Gagal menghapus booking: ' + (response.data || 'Unknown error'));
                                }
                            },
                            error: function() {
                                if ($overlay) $overlay.remove();
                                alert('Terjadi kesalahan saat menghapus booking.');
                            }
                        });
                    }
                }, 100);
            } else if (redirectUrl) {
                // For other deletes, redirect to URL
                window.location.href = redirectUrl;
            }
        });

        // Close on overlay click
        $dialog.on('click', function(e) {
            if (e.target === this) {
                console.log('Delete confirm dialog overlay clicked - cancelled');
                $dialog.remove();
                // Clean up any pending delete markers
                $('.delete-booking[data-delete-pending="true"]').removeAttr('data-delete-pending');
            }
        });

        // Add enter key support
        $dialog.on('keydown', function(e) {
            if (e.key === 'Enter') {
                $dialog.find('.ab-dialog-confirm').click();
            } else if (e.key === 'Escape') {
                $dialog.find('.ab-dialog-cancel').click();
            }
        });

        // Focus confirm button
        setTimeout(function() {
            $dialog.find('.ab-dialog-confirm').focus();
        }, 100);
    };

    // Status control: show custom confirmation dialog
    $(document).on('focusin', '.booking-status', function(){
        $(this).data('prev', $(this).val());
    });

    $(document).on('change', '.booking-status', function() {
        console.log('Booking status change event triggered!');
        try { if (window.console && console.debug) console.debug('[archeus] booking-status change detected'); } catch(e) {}
        var $sel = $(this);
        var bookingId = $sel.data('id');
        var newStatus = $sel.val();
        var prevStatus = $sel.data('prev');

        console.log('Status change details:', { bookingId, newStatus, prevStatus });

        console.log('Archeus: Sending status change request - Booking ID:', bookingId, 'New Status:', newStatus);
        console.log('Archeus: AJAX request data:', { action: 'update_booking_status', booking_id: bookingId, status: newStatus, nonce: archeus_booking_ajax.nonce });

        // Proceed to save with full-page overlay
        if (!document.getElementById('ab-loading-style')) {
            var css = '\n.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}\n.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}\n.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}\n@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}\n';
            var styleTag = document.createElement('style');
            styleTag.id = 'ab-loading-style';
            styleTag.type = 'text/css';
            styleTag.appendChild(document.createTextNode(css));
            document.head.appendChild(styleTag);
        }
        var $overlay = $('<div class="ab-loading-overlay"><div class="ab-loading-spinner"></div><div class="ab-loading-text">Memperbarui status...</div></div>').appendTo('body');
        $sel.prop('disabled', true);

        $.ajax({
            url: archeus_booking_ajax.ajax_url,
            type: 'POST',
            dataType: 'text',
            data: { action: 'update_booking_status', booking_id: bookingId, status: newStatus, nonce: archeus_booking_ajax.nonce },
            success: function(resp) {
                console.log('Archeus: AJAX response received:', resp);
                try {
                    if (typeof resp === 'string') {
                        var firstBrace = resp.indexOf('{');
                        if (firstBrace > 0) resp = resp.slice(firstBrace);
                        resp = JSON.parse(resp);
                    }
                    console.log('Archeus: Parsed response:', resp);
                } catch (e) {
                    console.error('Admin JSON parse error (update status)', e, resp);
                    showToast('Invalid server response while updating status.', 'error');
                    $sel.val(prevStatus);
                    if ($overlay) { $overlay.remove(); }
                    return;
                }
                if (resp && resp.success) {
                    showToast((resp.data && resp.data.message) || 'Status berhasil diperbarui.', 'success');
                    try {
                        var __abMap = { pending: '#ab-count-pending', approved: '#ab-count-approved', completed: '#ab-count-completed', rejected: '#ab-count-rejected' };
                        if (prevStatus && __abMap[prevStatus]) {
                            var __p = jQuery(__abMap[prevStatus]);
                            if (__p.length) {
                                var __pv = parseInt((__p.text()||'0').replace(/[^0-9]/g,''))||0;
                                __p.text(Math.max(__pv-1,0));
                            }
                        }
                        if (newStatus && __abMap[newStatus]) {
                            var __n = jQuery(__abMap[newStatus]);
                            if (__n.length) {
                                var __nv = parseInt((__n.text()||'0').replace(/[^0-9]/g,''))||0;
                                __n.text(__nv+1);
                            }
                        }
                    } catch(e){}
                    // Adjust visibility of 'completed' option
                    if (newStatus === 'approved') {
                        if ($sel.find('option[value="completed"]').length === 0) {
                            $sel.append('<option value="completed">Selesai</option>');
                        }
                    } else if (newStatus !== 'completed') {
                        // Hide completed unless status is approved or already completed
                        $sel.find('option[value="completed"]').remove();
                    }
                    $sel.data('prev', newStatus);
                    $sel.prop('disabled', false);

                    // Show loading overlay a bit longer, then refresh page to show updated data and send email notification
                    setTimeout(function() {
                        console.log('Refreshing page to show updated booking data...');
                        window.location.reload();
                    }, 1500); // Wait 1.5 seconds before refresh to show success message and allow email notification
                } else {
                    var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to update status.';
                    showToast(msg, 'error');
                    $sel.val(prevStatus);
                    $sel.prop('disabled', false);
                    if ($overlay) { $overlay.remove(); }
                }
            },
            error: function(xhr, status, error) {
                console.error('Archeus: AJAX error occurred:', { xhr: xhr, status: status, error: error });
                showToast('An error occurred while updating the booking status.', 'error');
                $sel.val(prevStatus);
                $sel.prop('disabled', false);
                if ($overlay) { $overlay.remove(); }
            }
        });
    });

    // Handle delete booking buttons
    $(document).on('click', '.delete-booking', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var bookingId = $btn.data('id');

        console.log('Delete booking button clicked:', { bookingId });

        // Mark this button as pending deletion so the dialog can find it
        $btn.attr('data-delete-pending', 'true');

        // Call the custom delete confirmation dialog
        if (typeof showDeleteConfirm === 'function') {
            showDeleteConfirm('Yakin ingin menghapus booking ini?', '');
        } else {
            // Fallback to browser confirm
            if (confirm('Yakin ingin menghapus booking ini?')) {
                // Handle the delete directly
                var $overlay = $('<div class="ab-loading-overlay" style="position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;"><div class="ab-loading-spinner" style="width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;"></div><div class="ab-loading-text" style="margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;">Menghapus...</div></div>').appendTo('body');

                $.ajax({
                    url: archeus_booking_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_booking',
                        booking_id: bookingId,
                        nonce: archeus_booking_ajax.nonce
                    },
                    success: function(response) {
                        if ($overlay) $overlay.remove();
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Gagal menghapus booking: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        if ($overlay) $overlay.remove();
                        alert('Terjadi kesalahan saat menghapus booking.');
                    }
                });
            } else {
                // Clean up the marker if cancelled
                $btn.removeAttr('data-delete-pending');
            }
        }
    });

    // Handle view details button
    $(document).on('click', '.view-details-btn', function(e) {
        e.preventDefault();

        var bookingId = $(this).data('id');
        var detailsRow = $('tr.booking-details-row[data-id="' + bookingId + '"]');

        // If visible, hide
        if (detailsRow.is(':visible')) { detailsRow.hide(); return; }

        // Show loading
        var container = detailsRow.find('.booking-details');
        container.html('<div class="loading">Memuat detail...</div>');
        detailsRow.show();

        $.ajax({
            url: archeus_booking_ajax.ajax_url,
            type: 'POST',
            dataType: 'text',
            data: { action: 'get_booking_details', booking_id: bookingId, nonce: archeus_booking_ajax.nonce },
            success: function(resp){
                try {
                    if (typeof resp === 'string'){
                        var firstBrace = resp.indexOf('{');
                        if (firstBrace > 0) resp = resp.slice(firstBrace);
                        resp = JSON.parse(resp);
                    }
                } catch(e){
                    console.error('Details JSON parse error', e, resp);
                    container.html('<div class="error">Gagal memuat detail.</div>');
                    return;
                }
                if (!resp || !resp.success || !resp.data){
                    container.html('<div class="error">Gagal memuat detail.</div>');
                    return;
                }
                var data = resp.data;
                var keys = Object.keys(data);
                var exclude = ['payload','form_id','schedule_id','flow_id','flowname','flow_name'];
                var html = '';
                html += '<div class="booking-details-card">';
                html +=   '<h4 class="booking-details-title">Detail Reservasi</h4>';
                html +=   '<table class="booking-details-table"><tbody>';
                var previewId = 'bv-preview-' + bookingId;
                keys.forEach(function(k){
                    if (exclude.indexOf(k) !== -1) return;
                    var v = data[k];
                    if (k === 'bukti_vaksin' && v) {
                        var fileName = (function(u){ try{ var p=u.split('?')[0]; return decodeURIComponent(p.substring(p.lastIndexOf('/')+1)); }catch(e){ return u; } })(v);
                        var actions = '' +
                          '<div class="bv-actions">' +
                          '  <button type="button" class="button bv-preview" data-url="' + v + '" data-target="#' + previewId + '" aria-label="Lihat pratinjau">' +
                          '    <span class="dashicons dashicons-visibility" aria-hidden="true"></span><span>Lihat</span>' +
                          '  </button>' +
                          '  <a class="button bv-open" href="' + v + '" target="_blank" rel="noopener" aria-label="Buka di tab baru">' +
                          '    <span class="dashicons dashicons-external" aria-hidden="true"></span><span>Buka Tab</span>' +
                          '  </a>' +
                          '  <a class="button bv-download" href="' + v + '" download aria-label="Unduh berkas">' +
                          '    <span class="dashicons dashicons-download" aria-hidden="true"></span><span>Unduh</span>' +
                          '  </a>' +
                          '</div>';
                        html += '<tr><th>' + k.replace(/_/g,' ') + '</th><td>' + fileName + ' ' + actions + '</td></tr>';
                    } else {
                        html += '<tr><th>' + k.replace(/_/g,' ') + '</th><td>' + (v == null ? '' : v) + '</td></tr>';
                    }
                });
                html +=   '</tbody></table>';
                html +=   '<div id="' + previewId + '" class="bv-preview-container" style="margin-top:12px; display:none;"></div>';
                html += '</div>';
                container.html(html);
            },
            error: function(){
                container.html('<div class="error">Gagal memuat detail.</div>');
            }
        });
    });

    // Preview bukti_vaksin inline
    $(document).on('click', '.bv-preview', function(){
        var url = $(this).data('url');
        var target = $(this).data('target');
        var $target = $(target);
        if (!$target.length) return;
        // Toggle if already visible
        if ($target.is(':visible')) { $target.hide().empty(); return; }
        var lower = (url || '').toLowerCase();
        var html = '';
        if (/(\.png|\.jpe?g|\.gif|\.webp|\.bmp)$/.test(lower)) {
            html = '<img src="' + url + '" alt="Bukti Vaksin" style="max-width:100%;height:auto;border:1px solid #ddd;padding:4px;background:#fff;">';
        } else if (/\.pdf$/.test(lower)) {
            html = '<iframe src="' + url + '#toolbar=1" style="width:100%;height:520px;border:1px solid #ddd;background:#fff;"></iframe>';
        } else {
            html = '<div class="notice">Tidak dapat melakukan pratinjau berkas ini. Gunakan tombol Buka atau Unduh.</div>';
        }
        $target.html(html).show();
    });

    // Function to show messages
    function showToast(message, type) {
        try {
            if (!document.getElementById('ab-toast-style')) {
                var css = '.ab-toast{position:fixed;right:16px;bottom:16px;background:#1f2937;color:#fff;padding:10px 14px;border-radius:6px;box-shadow:0 6px 16px rgba(0,0,0,.2);z-index:100000;opacity:.98;transition:opacity .3s ease, transform .3s ease;transform:translateY(8px);} .ab-toast.success{background:#16a34a} .ab-toast.error{background:#dc2626}';
                var st = document.createElement('style'); st.id='ab-toast-style'; st.appendChild(document.createTextNode(css)); document.head.appendChild(st);
            }
            var el = document.createElement('div'); el.className='ab-toast ' + (type||'success'); el.textContent = message; document.body.appendChild(el);
            setTimeout(function(){ el.style.opacity=0; el.style.transform='translateY(0)'; setTimeout(function(){ if(el && el.parentNode){ el.parentNode.removeChild(el); } }, 400); }, 2500);
        } catch(e) {
            showMessage(message, type==='error'?'error':'success');
        }
    }

    function showMessage(message, type) {
        // Remove any existing messages
        $('.booking-message').remove();

        var messageClass = type === 'success' ? 'notice notice-success' : 'notice notice-error';
        var messageHtml = '<div class="booking-message ' + messageClass + '"><p>' + message + '</p></div>';

        $('.wrap').prepend(messageHtml);

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $('.booking-message').fadeOut();
            }, 5000);
        }
    }

    // Helper function to format date
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    // Helper function to format full date/time
    function formatDateFull(dateTimeString) {
        var date = new Date(dateTimeString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Progressive enhancement: custom dropdown for .ab-dropdown (copied from booking-flow)
    function enhanceAbDropdowns(root){
        var $root = root && root.jquery ? root : $(document);
        $root.find('select.ab-dropdown').each(function(){
            var $sel = $(this);
            if ($sel.data('ab-dd')) return; // already enhanced
            $sel.data('ab-dd', true);

            var selectedText = $sel.find('option:selected').text() || '';
            var $wrap = $('<div class="ab-dd"></div>');
            var $btn = $('<button type="button" class="ab-dd-toggle" aria-haspopup="listbox" aria-expanded="false"></button>');
            var $label = $('<span class="ab-dd-label"></span>').text(selectedText);
            var $caret = $('<span class="ab-dd-caret" aria-hidden="true"></span>');
            $btn.append($label).append($caret);
            var $menu = $('<div class="ab-dd-menu" role="listbox"></div>');

            $sel.find('option').each(function(){
                var $opt = $(this);
                var $item = $('<div class="ab-dd-item" role="option" tabindex="-1"></div>').text($opt.text());
                $item.attr('data-value', $opt.attr('value'));
                if ($opt.is(':selected')) $item.addClass('is-selected');
                $menu.append($item);
            });
            $sel.addClass('ab-hidden-select').hide().after($wrap);
            $wrap.append($btn).append($menu);
            $sel.appendTo($wrap); // keep in wrap to trigger change

            function closeMenu() {
                $wrap.removeClass('open');
                $btn.attr('aria-expanded', 'false');
            }
            function openMenu() {
                $wrap.addClass('open');
                $btn.attr('aria-expanded', 'true');
            }

            $btn.on('click', function(e) {
                e.preventDefault();
                if ($wrap.hasClass('open')) closeMenu();
                else openMenu();
            });

            $(document).on('click', function(e) {
                if (!$.contains($wrap[0], e.target)) closeMenu();
            });

            $menu.on('click', '.ab-dd-item', function() {
                var val = $(this).attr('data-value');
                $sel.val(val).trigger('change');
                $menu.find('.ab-dd-item').removeClass('is-selected');
                $(this).addClass('is-selected');
                $label.text($(this).text());
                closeMenu();
            });

            $sel.on('change', function() {
                var txt = $sel.find('option:selected').text() || '';
                var val = $sel.val();
                $label.text(txt);
                $menu.find('.ab-dd-item').each(function() {
                    var $i = $(this);
                    $i.toggleClass('is-selected', $i.attr('data-value') == val);
                });
            });
        });
    }

    // Initialize dropdowns
    enhanceAbDropdowns($(document));

    // Observe DOM changes to enhance future selects
    if (window.MutationObserver) {
        var moAll = new MutationObserver(function(muts){
            // Try enhancing any new selects
            try { enhanceAbDropdowns($(document)); } catch(e){}
        });
        moAll.observe(document.body, { childList: true, subtree: true });
    }

    // Handle status filter (with overlay)
    $('#booking-status-filter').on('change', function(event, data) {
        var isInitial = data && data.isInitialLoad;
        var status = $(this).val();
        var flowId = $('#booking-flow-filter').length ? $('#booking-flow-filter').val() : ($('#ab-flow-select').length ? $('#ab-flow-select').val() : 0);
        var $overlay = null;

        if (!isInitial) {
            if (!document.getElementById('ab-loading-style')) {
                var css = '\n.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}\n.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}\n.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}\n@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}\n';
                var st = document.createElement('style'); st.id='ab-loading-style'; st.appendChild(document.createTextNode(css)); document.head.appendChild(st);
            }
            $overlay = $('<div class="ab-loading-overlay"><div class="ab-loading-spinner"></div><div class="ab-loading-text">Memuat...</div></div>').appendTo('body');
        }

        $.ajax({
            url: archeus_booking_ajax.ajax_url,
            type: 'POST',
            dataType: 'text',
            data: { action: 'get_bookings', status: status, flow_id: flowId, nonce: archeus_booking_ajax.nonce },
            success: function(resp) {
                try { if (typeof resp === 'string') { var fb = resp.indexOf('{'); if (fb > 0) resp = resp.slice(fb); resp = JSON.parse(resp); } } catch (e) { console.error('Admin JSON parse error (filter bookings)', e, resp); showToast('Invalid server response while loading bookings.', 'error'); if ($overlay) { $overlay.remove(); } return; }
                if (resp && resp.success) {
                    var bookings = resp.data && (resp.data.bookings || resp.data);
                    console.log('Bookings data received:', bookings); // Debug log
                    if (bookings && bookings.length > 0) {
                        console.log('First booking structure:', bookings[0]); // Debug first record structure
                    }
                    updateBookingsTable(bookings);
                    if (resp.data && resp.data.stats) { updateDashboardStats(resp.data.stats); }
                    // Update shortcode display if exists
                    if (resp.data && resp.data.shortcode) { updateShortcodeDisplay(resp.data.shortcode); }
                } else {
                    var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to load bookings.';
                    showToast(msg, 'error');
                }
                if ($overlay) { $overlay.remove(); }
            },
            error: function() {
                showToast('An error occurred while refreshing bookings.', 'error');
                if ($overlay) { $overlay.remove(); }
            }
        });
    });

    // Handle flow filter (silent) - for booking-flow-filter
    $('#booking-flow-filter').on('change', function(event, data) {
        var status = $('#booking-status-filter').val();
        var flowId = $(this).val();
        $.ajax({
            url: archeus_booking_ajax.ajax_url,
            type: 'POST',
            dataType: 'text',
            data: { action: 'get_bookings', status: status, flow_id: flowId, nonce: archeus_booking_ajax.nonce },
            success: function(resp) {
                try { if (typeof resp === 'string') { var fb = resp.indexOf('{'); if (fb > 0) resp = resp.slice(fb); resp = JSON.parse(resp); } } catch (e) { console.error('Admin JSON parse error (filter bookings)', e, resp); showToast('Invalid server response while loading bookings.', 'error'); return; }
                if (resp && resp.success) {
                    var bookings = resp.data && (resp.data.bookings || resp.data);
                    console.log('Bookings data received:', bookings); // Debug log
                    if (bookings && bookings.length > 0) {
                        console.log('First booking structure:', bookings[0]); // Debug first record structure
                    }
                    updateBookingsTable(bookings);
                    if (resp.data && resp.data.stats) { updateDashboardStats(resp.data.stats); }
                    // Update shortcode display if exists
                    if (resp.data && resp.data.shortcode) { updateShortcodeDisplay(resp.data.shortcode); }
                } else {
                    var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to load bookings.';
                    showToast(msg, 'error');
                }
            },
            error: function() {
                showToast('An error occurred while refreshing bookings.', 'error');
            }
        });
    });

    // Handle admin notice flow filter (#ab-flow-select) - update all dashboard components
    $('#ab-flow-select').on('change', function(event, data) {
        var isInitial = data && data.isInitialLoad;
        var flowId = $(this).val();
        var status = $('#booking-status-filter').val();
        var $overlay = null;

        // Update active flow label
        updateFlowLabel();

        if (!isInitial) {
            if (!document.getElementById('ab-loading-style')) {
                var css = '\n.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}\n.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}\n.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}\n@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}\n';
                var st = document.createElement('style'); st.id='ab-loading-style'; st.appendChild(document.createTextNode(css)); document.head.appendChild(st);
            }
            $overlay = $('<div class="ab-loading-overlay"><div class="ab-loading-spinner"></div><div class="ab-loading-text">Memuat...</div></div>').appendTo('body');
        }

        // Update all dashboard components
        $.ajax({
            url: archeus_booking_ajax.ajax_url,
            type: 'POST',
            dataType: 'text',
            data: { action: 'get_bookings', status: status, flow_id: flowId, nonce: archeus_booking_ajax.nonce },
            success: function(resp) {
                try {
                    if (typeof resp === 'string') {
                        var fb = resp.indexOf('{');
                        if (fb > 0) resp = resp.slice(fb);
                        resp = JSON.parse(resp);
                    }
                } catch (e) {
                    console.error('Admin JSON parse error (flow filter)', e, resp);
                    showToast('Invalid server response while loading bookings.', 'error');
                    if ($overlay) { $overlay.remove(); }
                    return;
                }

                if (resp && resp.success) {
                    var bookings = resp.data && (resp.data.bookings || resp.data);
                    updateBookingsTable(bookings);
                    if (resp.data && resp.data.stats) {
                        updateDashboardStats(resp.data.stats);
                    }

                    // Update shortcode display if exists
                    if (resp.data && resp.data.shortcode) {
                        updateShortcodeDisplay(resp.data.shortcode);
                    }
                } else {
                    var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to load bookings.';
                    showToast(msg, 'error');
                }
                if ($overlay) { $overlay.remove(); }
            },
            error: function() {
                showToast('An error occurred while refreshing bookings.', 'error');
                if ($overlay) { $overlay.remove(); }
            }
        });
    });

    // Handle refresh button
    $('#refresh-bookings').on('click', function() {
        var status = $('#booking-status-filter').val();
        var flowId = $('#booking-flow-filter').length ? $('#booking-flow-filter').val() : ($('#ab-flow-select').length ? $('#ab-flow-select').val() : 0);
        // Overlay while refreshing
        if (!document.getElementById('ab-loading-style')) {
            var css = '\n.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}\n.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}\n.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}\n@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}\n';
            var st = document.createElement('style'); st.id='ab-loading-style'; st.appendChild(document.createTextNode(css)); document.head.appendChild(st);
        }
        var $overlay2 = $('<div class="ab-loading-overlay"><div class="ab-loading-spinner"></div><div class="ab-loading-text">Memuat...</div></div>').appendTo('body');
        $.ajax({
            url: archeus_booking_ajax.ajax_url,
            type: 'POST',
            dataType: 'text',
            data: {
                action: 'get_bookings',
                status: status,
                flow_id: flowId,
                nonce: archeus_booking_ajax.nonce
            },
            success: function(resp) {
                try {
                    if (typeof resp === 'string') {
                        var firstBrace = resp.indexOf('{');
                        if (firstBrace > 0) resp = resp.slice(firstBrace);
                        resp = JSON.parse(resp);
                    }
                } catch (e) {
                    console.error('Admin JSON parse error (refresh bookings)', e, resp);
                    showToast('Invalid server response while refreshing.', 'error');
                    if ($overlay2) { $overlay2.remove(); }
                    return;
                }
                if (resp && resp.success) {
                    var bookings = resp.data && (resp.data.bookings || resp.data);
                    updateBookingsTable(bookings);
                    if (resp.data && resp.data.stats) { updateDashboardStats(resp.data.stats); }
                    // Update shortcode display if exists
                    if (resp.data && resp.data.shortcode) { updateShortcodeDisplay(resp.data.shortcode); }
                    showToast('Bookings refreshed successfully.', 'success');
                    if ($overlay2) { $overlay2.remove(); }
                } else {
                    var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to refresh bookings.';
                    showToast(msg, 'error');
                    if ($overlay2) { $overlay2.remove(); }
                }
            },
            error: function() {
                showToast('An error occurred while refreshing bookings.', 'error');
                if ($overlay2) { $overlay2.remove(); }
            }
        });
    });

    // Function to update the bookings table
    function updateBookingsTable(bookings) {
        var tbody = $('#bookings-table-body');
        tbody.empty();

        if (!bookings || !bookings.length) {
            tbody.append('<tr class="no-data"><td colspan="8" class="no-data-cell">Data tidak tersedia atau data kosong.</td></tr>');
            return;
        }

        $.each(bookings, function(index, booking) {
            var completedAllowed = (booking.status === 'approved' || booking.status === 'completed');

            // Handle multiple possible field names for customer name
            var customerName = booking.display_name || booking.nama_lengkap || booking.customer_name || booking.name || (booking.first_name && booking.last_name ? booking.first_name + ' ' + booking.last_name : '') || '-';
            var customerTitle = customerName !== '-' ? customerName : '';

            var row = '<tr data-id="' + booking.id + '">' +
                '<td class="col-id">' + booking.id + '</td>' +
                '<td class="col-name" title="' + customerTitle + '">' + customerName + '</td>' +
                '<td>' + formatDate(booking.booking_date) + '</td>' +
                '<td>' + (booking.booking_time || '') + '</td>' +
                '<td>' + booking.service_type + '</td>' +
                '<td>' +
                    '<div class="status-control">' +
                        '<select class="booking-status ab-select ab-dropdown" data-id="' + booking.id + '">' +
                            '<option value="pending"' + (booking.status === 'pending' ? ' selected' : '') + '>Menunggu</option>' +
                            '<option value="approved"' + (booking.status === 'approved' ? ' selected' : '') + '>Disetujui</option>' +

                            (completedAllowed ? ('<option value="completed"' + (booking.status === 'completed' ? ' selected' : '') + '>Selesai</option>') : '') +
                            '<option value="rejected"' + (booking.status === 'rejected' ? ' selected' : '') + '>Ditolak</option>' +
                        '</select>' +
                    '</div>' +
                '</td>' +
                '<td>' + formatDateFull(booking.created_at) + '</td>' +
                '<td class="col-actions">' +
                    '<div class="action-buttons">' +
                        '<button class="view-details-btn button" data-id="' + booking.id + '">Lihat Detail</button>' +
                        '<button class="delete-booking button" data-id="' + booking.id + '" title="Hapus Booking"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="text">Hapus</span></button>' +
                    '</div>' +
                '</td>' +
            '</tr>' +
            '<tr class="booking-details-row" data-id="' + booking.id + '" style="display: none;">' +
                '<td colspan="7">' +
                    '<div class="booking-details">' +
                        '<h4>Additional Information</h4>' +
                        '<p>Tidak ada detail tambahan.</p>' +
                    '</div>' +
                '</td>' +
            '</tr>';

            tbody.append(row);
        });

        // Re-enhance dropdowns for new rows
        enhanceAbDropdowns(tbody);
    }

    // Update dashboard stats counters
    function updateDashboardStats(stats) {
        try {
            if (typeof stats !== 'object' || !stats) return;
            if (typeof stats.total !== 'undefined') jQuery('#ab-count-total').text(stats.total);
            if (typeof stats.pending !== 'undefined') jQuery('#ab-count-pending').text(stats.pending);
            if (typeof stats.approved !== 'undefined') jQuery('#ab-count-approved').text(stats.approved);
            if (typeof stats.completed !== 'undefined') jQuery('#ab-count-completed').text(stats.completed);
            if (typeof stats.rejected !== 'undefined') jQuery('#ab-count-rejected').text(stats.rejected);
        } catch(e) {}
    }

    // Update active flow label near stats
    function updateFlowLabel(){
        try {
            var $sel = jQuery('#ab-flow-select');
            var label = ($sel.length && $sel.find('option:selected').text()) ? $sel.find('option:selected').text() : 'Semua Flow';
            var $lbl = jQuery('#ab-flow-active'); if ($lbl.length) $lbl.text(label);
        } catch(e) {}
    }

    // Update shortcode display in admin notice
    function updateShortcodeDisplay(shortcode){
        try {
            // Update via HTML container (if exists)
            var $shortcodeContainer = $('.archeus-booking-shortcode');
            if ($shortcodeContainer.length && shortcode) {
                $shortcodeContainer.html(shortcode);
            }

            // Also update directly the code element and copy button data
            var $codeElement = $('#ab-sc-with-id');
            var $copyButton = $('#ab-copy-with-id');

            if ($codeElement.length && shortcode) {
                $codeElement.text(shortcode);
            }

            if ($copyButton.length && shortcode) {
                $copyButton.attr('data-copy', shortcode);
            }

            // Also handle via flow select change (fallback)
            var $flowSelect = $('#ab-flow-select');
            if ($flowSelect.length) {
                var selectedId = $flowSelect.val() || '1';
                var dynamicShortcode = '[archeus_booking id="' + selectedId + '"]';

                if ($codeElement.length) {
                    $codeElement.text(dynamicShortcode);
                }
                if ($copyButton.length) {
                    $copyButton.attr('data-copy', dynamicShortcode);
                }
            }
        } catch(e) {
            console.error('Error updating shortcode display:', e);
        }
    }

    // Initialize label on load
    updateFlowLabel();

    // Handle direct flow select change for shortcode update (backup for inline JS)
    $(document).on('change', '#ab-flow-select', function() {
        var selectedId = $(this).val() || '1';
        var shortcode = '[archeus_booking id="' + selectedId + '"]';

        // Update the code display
        var $codeElement = $('#ab-sc-with-id');
        if ($codeElement.length) {
            $codeElement.text(shortcode);
        }

        // Update the copy button data
        var $copyButton = $('#ab-copy-with-id');
        if ($copyButton.length) {
            $copyButton.attr('data-copy', shortcode);
        }

        console.log('Shortcode updated to:', shortcode);
    });

    // Handle copy shortcode buttons
    $(document).on('click', '.ab-copy-btn', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var textToCopy = $btn.attr('data-copy') || $btn.siblings('.ab-shortcode-code').text() || '';

        if (!textToCopy) {
            showToast('Tidak ada teks untuk disalin.', 'error');
            return;
        }

        // Modern clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                showToast('Shortcode berhasil disalin!', 'success');

                // Visual feedback
                var $icon = $btn.find('.dashicons');
                var $text = $btn.find('span:not(.dashicons)');
                var originalIcon = $icon.attr('class');
                var originalText = $text.text();

                $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes');
                $text.text('Tersalin!');

                setTimeout(function() {
                    $icon.removeClass('dashicons-yes').addClass(originalIcon);
                    $text.text(originalText);
                }, 2000);

            }).catch(function(err) {
                console.error('Clipboard API failed:', err);
                fallbackCopyTextToClipboard(textToCopy, $btn);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyTextToClipboard(textToCopy, $btn);
        }
    });

    // Fallback copy function using document.execCommand
    function fallbackCopyTextToClipboard(text, $btn) {
        var textArea = document.createElement("textarea");
        textArea.value = text;

        // Avoid scrolling to bottom
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                showToast('Shortcode berhasil disalin!', 'success');

                // Visual feedback
                var $icon = $btn.find('.dashicons');
                var $text = $btn.find('span:not(.dashicons)');
                var originalIcon = $icon.attr('class');
                var originalText = $text.text();

                $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes');
                $text.text('Tersalin!');

                setTimeout(function() {
                    $icon.removeClass('dashicons-yes').addClass(originalIcon);
                    $text.text(originalText);
                }, 2000);
            } else {
                showToast('Gagal menyalin shortcode.', 'error');
            }
        } catch (err) {
            console.error('Fallback copy failed:', err);
            showToast('Browser tidak mendukung fitur salin.', 'error');
        }

        document.body.removeChild(textArea);
    }

    // Ensure initial data matches current flow selection
    setTimeout(function(){
        try {
            if (jQuery('#booking-status-filter').length) {
                jQuery('#booking-status-filter').trigger('change', { isInitialLoad: true });
            } else if (jQuery('#ab-flow-select').length) {
                jQuery('#ab-flow-select').trigger('change', { isInitialLoad: true });
            }
        } catch(e) {}
    }, 0);

});