// Debug: Check if functions are loaded
console.log('ARCHAEUS ADMIN JS LOADED!');
jQuery(document).ready(function ($) {
  console.log('ARCHAEUS ADMIN JS DOCUMENT READY!');
  // Check for table overflow and add visual indicator
  function checkTableOverflow() {
    $('.form-fields-builder').each(function() {
      var $builder = $(this);
      var $table = $builder.find('table');
      var tableNaturalWidth = 0;

      // Calculate natural width by summing column widths
      $table.find('thead th').each(function() {
        var $th = $(this);
        var text = $th.text().trim();
        var tempSpan = $('<span>').text(text).css({
          'white-space': 'nowrap',
          'visibility': 'hidden',
          'position': 'absolute',
          'font': $th.css('font'),
          'padding': $th.css('padding')
        });
        $('body').append(tempSpan);
        var textWidth = tempSpan.width();
        tempSpan.remove();
        tableNaturalWidth += textWidth + 40; // Add padding buffer
      });

      // Check if natural width exceeds container width
      if (tableNaturalWidth > $builder.width()) {
        $builder.addClass('table-overflow overflowing');
        // Switch to auto layout for scrollable table
        $table.css({
          'width': tableNaturalWidth,
          'table-layout': 'auto'
        });
      } else {
        $builder.removeClass('table-overflow overflowing');
        // Use full width layout
        $table.css({
          'width': '100%',
          'table-layout': 'fixed'
        });
      }
    });
  }

  // Check overflow on load and resize
  checkTableOverflow();
  $(window).on('resize', checkTableOverflow);

  // Check overflow when form fields are added/removed
  $(document).on('click', '#add-field-btn, .remove-field', function() {
    setTimeout(checkTableOverflow, 100);
  });

  // Check overflow when input fields change size
  $(document).on('input', '.form-fields-builder input[type="text"], .form-fields-builder textarea', function() {
    setTimeout(checkTableOverflow, 50);
  });

  // Check overflow when dropdown changes
  $(document).on('change', '.form-fields-builder select', function() {
    setTimeout(checkTableOverflow, 50);
  });
  // Define global functions directly on window object
  window.showStatusChangeDialog = function (callback, newStatus, prevStatus) {
    console.log("showStatusChangeDialog called with:", {
      newStatus,
      prevStatus,
    });
    // Remove any existing dialogs
    $(".ab-dialog-overlay").remove();

    var statusLabels = {
      pending: "Menunggu",
      approved: "Disetujui",
      completed: "Selesai",
      rejected: "Ditolak",
    };

    var statusTo = statusLabels[newStatus] || newStatus;
    var statusFrom = statusLabels[prevStatus] || prevStatus;

    var dialogHtml =
      '<div class="ab-dialog-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;">' +
      '<div class="ab-dialog" style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-width: 400px; width: 90%;">' +
      '<h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #1f2937;">Konfirmasi Perubahan Status</h3>' +
      '<p style="margin: 0 0 20px 0; color: #4b5563; line-height: 1.5;">Apakah Anda yakin ingin mengubah status booking dari <strong>"' +
      statusFrom +
      '"</strong> ke <strong>"' +
      statusTo +
      '"</strong>?</p>' +
      '<div style="display: flex; gap: 12px; justify-content: flex-end;">' +
      '<button type="button" class="ab-dialog-cancel button button-secondary" style="padding: 8px 16px; font-size: 14px;">Batal</button>' +
      '<button type="button" class="ab-dialog-confirm button button-primary" style="padding: 8px 16px; font-size: 14px;">Ya, Ubah Status</button>' +
      "</div>" +
      "</div>" +
      "</div>";

    var $dialog = $(dialogHtml).appendTo("body");

    // Handle button clicks
    $dialog.find(".ab-dialog-cancel").on("click", function () {
      console.log("Dialog cancelled");
      $dialog.remove();
      callback(false);
    });

    $dialog.find(".ab-dialog-confirm").on("click", function () {
      console.log("Dialog confirmed");
      $dialog.remove();
      callback(true);
    });

    // Close on overlay click
    $dialog.on("click", function (e) {
      if (e.target === this) {
        console.log("Dialog overlay clicked - cancelled");
        $dialog.remove();
        callback(false);
      }
    });

    // Add enter key support
    $dialog.on("keydown", function (e) {
      if (e.key === "Enter") {
        $dialog.find(".ab-dialog-confirm").click();
      } else if (e.key === "Escape") {
        $dialog.find(".ab-dialog-cancel").click();
      }
    });

    // Focus confirm button
    setTimeout(function () {
      $dialog.find(".ab-dialog-confirm").focus();
    }, 100);
  };

  window.showDeleteConfirmationDialog = function (callback, bookingId) {
    console.log("showDeleteConfirmationDialog called with:", { bookingId });
    // Remove any existing dialogs
    $(".ab-dialog-overlay").remove();

    var dialogHtml =
      '<div class="ab-dialog-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;">' +
      '<div class="ab-dialog" style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-width: 400px; width: 90%;">' +
      '<h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #1f2937;">Konfirmasi Hapus Booking</h3>' +
      '<p style="margin: 0 0 20px 0; color: #4b5563; line-height: 1.5;">Apakah Anda yakin ingin menghapus booking ini? Tindakan ini tidak dapat dibatalkan.</p>' +
      '<div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 12px; margin-bottom: 20px;">' +
      '<p style="margin: 0; color: #991b1b; font-size: 14px;">Booking ID: <strong>#' +
      bookingId +
      "</strong></p>" +
      "</div>" +
      '<div style="display: flex; gap: 12px; justify-content: flex-end;">' +
      '<button type="button" class="ab-dialog-cancel button button-secondary" style="padding: 8px 16px; font-size: 14px;">Batal</button>' +
      '<button type="button" class="ab-dialog-confirm button button-primary" style="background: #dc2626; border-color: #dc2626; padding: 8px 16px; font-size: 14px;">Ya, Hapus</button>' +
      "</div>" +
      "</div>" +
      "</div>";

    var $dialog = $(dialogHtml).appendTo("body");

    // Handle button clicks
    $dialog.find(".ab-dialog-cancel").on("click", function () {
      console.log("Delete dialog cancelled");
      $dialog.remove();
      callback(false);
    });

    $dialog.find(".ab-dialog-confirm").on("click", function () {
      console.log("Delete dialog confirmed");
      $dialog.remove();
      callback(true);
    });

    // Close on overlay click
    $dialog.on("click", function (e) {
      if (e.target === this) {
        console.log("Delete dialog overlay clicked - cancelled");
        $dialog.remove();
        callback(false);
      }
    });

    // Add enter key support
    $dialog.on("keydown", function (e) {
      if (e.key === "Enter") {
        $dialog.find(".ab-dialog-confirm").click();
      } else if (e.key === "Escape") {
        $dialog.find(".ab-dialog-cancel").click();
      }
    });

    // Focus confirm button
    setTimeout(function () {
      $dialog.find(".ab-dialog-confirm").focus();
    }, 100);
  };

  // Legacy function for compatibility with inline script in admin class
  // Helper function to update booking status via AJAX
  window.updateBookingStatus = function (
    bookingId,
    newStatus,
    prevStatus,
    $sel,
    $menu,
    $item,
    $label
  ) {
    console.log(
      "Archeus: Sending status change request - Booking ID:",
      bookingId,
      "New Status:",
      newStatus
    );
    console.log("Archeus: AJAX request data:", {
      action: "update_booking_status",
      booking_id: bookingId,
      status: newStatus,
      nonce: archeus_booking_ajax.nonce,
    });

    // Proceed to save with full-page overlay
    if (!document.getElementById("ab-loading-style")) {
      var css =
        "\n.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}\n.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}\n.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}\n@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}\n";
      var styleTag = document.createElement("style");
      styleTag.id = "ab-loading-style";
      styleTag.type = "text/css";
      styleTag.appendChild(document.createTextNode(css));
      document.head.appendChild(styleTag);
    }
    var $overlay = $(
      '<div class="ab-loading-overlay"><div class="ab-loading-spinner"></div><div class="ab-loading-text">Menyimpan...</div></div>'
    ).appendTo("body");

    // Disable the dropdown
    if ($sel) $sel.prop("disabled", true);

    $.ajax({
      url: archeus_booking_ajax.ajax_url,
      type: "POST",
      dataType: "text",
      data: {
        action: "update_booking_status",
        booking_id: bookingId,
        status: newStatus,
        nonce: archeus_booking_ajax.nonce,
      },
      success: function (resp) {
        console.log("Archeus: AJAX response received:", resp);
        try {
          if (typeof resp === "string") {
            var firstBrace = resp.indexOf("{");
            if (firstBrace > 0) resp = resp.slice(firstBrace);
            resp = JSON.parse(resp);
          }
          console.log("Archeus: Parsed response:", resp);
        } catch (e) {
          console.error("Admin JSON parse error (update status)", e, resp);
          showToast("Invalid server response while updating status.", "error");
          if ($sel) $sel.val(prevStatus);
          if ($overlay) {
            $overlay.remove();
          }
          return;
        }
        if (resp && resp.success) {
          showToast(
            (resp.data && resp.data.message) || "Status updated.",
            "success"
          );

          // Update UI elements if provided
          if ($sel && $menu && $item && $label) {
            // Update the select value
            $sel.val(newStatus);
            $menu.find(".ab-dd-item").removeClass("is-selected");
            $item.addClass("is-selected");
            $label.text($item.text());

            // Adjust visibility of 'completed' option
            if (newStatus === "approved") {
              if ($sel.find('option[value="completed"]').length === 0) {
                $sel.append('<option value="completed">Selesai</option>');
              }
            } else if (newStatus !== "completed") {
              // Hide completed unless status is approved or already completed
              $sel.find('option[value="completed"]').remove();
            }

            $sel.data("prev", newStatus);
          }

          // Show loading overlay a bit longer, then refresh page to show updated data
          setTimeout(function () {
            console.log("Refreshing page to show updated booking data...");
            window.location.reload();
          }, 1500); // Wait 1.5 seconds before refresh to show success message
        } else {
          var msg =
            resp && resp.data && resp.data.message
              ? resp.data.message
              : "Failed to update status.";
          showToast(msg, "error");
          if ($sel) $sel.val(prevStatus);
          if ($sel) $sel.prop("disabled", false);
          if ($overlay) {
            $overlay.remove();
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("Archeus: AJAX error occurred:", {
          xhr: xhr,
          status: status,
          error: error,
          responseText: xhr.responseText,
          statusCode: xhr.status,
        });
        showToast(
          "An error occurred while updating the booking status.",
          "error"
        );
        if ($sel) $sel.val(prevStatus);
        if ($sel) $sel.prop("disabled", false);
        if ($overlay) {
          $overlay.remove();
        }
      },
    });
  };

  window.showDeleteConfirm = function (message, redirectUrl, callback) {
    console.log("showDeleteConfirm called with:", { message, redirectUrl, callback: typeof callback });

    // Check if this is service or form deletion (already handled by our event delegation)
    if (message.includes("layanan") || message.includes("formulir")) {
      // Check if there's a delete button that's already being handled
      var $handledButton = $('[data-delete-handled="true"]');
      if ($handledButton.length > 0) {
        console.log("Delete already handled by event delegation, skipping custom dialog");
        return;
      }
    }

    // Remove any existing dialogs
    $(".ab-dialog-overlay").remove();

    // Check if this is a booking delete (no redirectUrl) or other delete (with redirectUrl)
    var isBookingDelete = !redirectUrl || redirectUrl === "";

    var dialogHtml =
      '<div class="ab-dialog-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;">' +
      '<div class="ab-dialog" style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-width: 400px; width: 90%;">' +
      '<h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #1f2937;">Konfirmasi Hapus</h3>' +
      '<p style="margin: 0 0 20px 0; color: #4b5563; line-height: 1.5;">' +
      message +
      "</p>" +
      '<div style="display: flex; gap: 12px; justify-content: flex-end;">' +
      '<button type="button" class="ab-dialog-cancel button button-secondary" style="padding: 8px 16px; font-size: 14px;">Batal</button>' +
      '<button type="button" class="ab-dialog-confirm button button-primary" style="background: #dc2626; border-color: #dc2626; padding: 8px 16px; font-size: 14px;">Ya, Hapus</button>' +
      "</div>" +
      "</div>" +
      "</div>";

    var $dialog = $(dialogHtml).appendTo("body");

    // Handle button clicks
    $dialog.find(".ab-dialog-cancel").on("click", function () {
      console.log("Delete confirm dialog cancelled");
      $dialog.remove();
      // Clean up any pending delete markers
      $('.delete-booking[data-delete-pending="true"]').removeAttr("data-delete-pending");
      $('.delete-service[data-delete-pending="true"]').removeAttr("data-delete-pending");
      $('.delete-flow[data-delete-pending="true"]').removeAttr("data-delete-pending");
      $('.delete-time-slot[data-delete-pending="true"]').removeAttr("data-delete-pending");
      $('.delete-form[data-delete-pending="true"]').removeAttr("data-delete-pending");
      // Clean up handled markers
      $('.delete-booking[data-delete-handled="true"]').removeAttr("data-delete-handled");
      $('.delete-service[data-delete-handled="true"]').removeAttr("data-delete-handled");
      $('.delete-flow[data-delete-handled="true"]').removeAttr("data-delete-handled");
      $('.delete-time-slot[data-delete-handled="true"]').removeAttr("data-delete-handled");
      $('.delete-form[data-delete-handled="true"]').removeAttr("data-delete-handled");
    });

    $dialog.find(".ab-dialog-confirm").on("click", function () {
      console.log("Delete confirm dialog confirmed");
      $dialog.remove();

      // Check if we have a callback function (for custom actions like clear email logs)
      if (typeof callback === "function") {
        console.log("Executing callback function");
        callback();
        return;
      }

      if (isBookingDelete) {
        // For booking delete, we'll handle it differently since we don't have the booking ID here
        // The actual delete will be handled by the event handler that called this dialog
        console.log(
          "Booking delete confirmed - event handler will execute the delete"
        );
        // We need to trigger the delete confirmation in a different way
        // Since we can't easily get the booking ID here, we'll use a different approach
        setTimeout(function () {
          // Find the last clicked delete button and trigger its confirm action
          var $deleteBtn = $('.delete-booking[data-delete-pending="true"]');
          console.log('Looking for delete-booking button:', {
            button: $deleteBtn,
            length: $deleteBtn.length,
            allBookingButtons: $('.delete-booking[data-delete-pending="true"]'),
            allTimeSlotButtons: $('.delete-time-slot[data-delete-pending="true"]')
          });

          if ($deleteBtn.length) {
            var bookingId = $deleteBtn.data("id");
            $deleteBtn.removeAttr("data-delete-pending");

            // Show loading overlay
            var $overlay = $(
              '<div class="ab-loading-overlay" style="position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;"><div class="ab-loading-spinner" style="width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;"></div><div class="ab-loading-text" style="margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;">Menghapus...</div></div>'
            ).appendTo("body");

            $.ajax({
              url: archeus_booking_ajax.ajax_url,
              type: "POST",
              data: {
                action: "delete_booking",
                booking_id: bookingId,
                nonce: archeus_booking_ajax.nonce,
              },
              success: function (response) {
                if ($overlay) $overlay.remove();
                if (response.success) {
                  location.reload();
                } else {
                  showToast("Gagal menghapus booking: " + (response.data || "Unknown error"), 'error');
                }
              },
              error: function () {
                if ($overlay) $overlay.remove();
                showToast("Terjadi kesalahan saat menghapus booking.", 'error');
              },
            });
          }
        }, 100);
      } else {
        // Handle service and form deletion
        setTimeout(function () {
          console.log('Checking for pending delete buttons:', {
            service: $('.delete-service[data-delete-pending="true"]').length,
            flow: $('.delete-flow[data-delete-pending="true"]').length,
            timeSlot: $('.delete-time-slot[data-delete-pending="true"]').length,
            form: $('.delete-form[data-delete-pending="true"]').length
          });

          // Find service delete button
          var $serviceBtn = $('.delete-service[data-delete-pending="true"]');
          if ($serviceBtn.length) {
            var serviceId = $serviceBtn.data('service-id');
            $serviceBtn.removeAttr('data-delete-pending');

            // Call service delete handler
            if (typeof handleServiceDelete === 'function') {
              handleServiceDelete($serviceBtn, serviceId);
            }
            return;
          }

          // Find flow delete button
          var $flowBtn = $('.delete-flow[data-delete-pending="true"]');
          if ($flowBtn.length) {
            var flowId = $flowBtn.data('flow-id');
            $flowBtn.removeAttr('data-delete-pending');

            // Call flow delete handler
            if (typeof handleFlowDelete === 'function') {
              handleFlowDelete($flowBtn, flowId);
            }
            return;
          }

          // Find time slot delete button
          var $timeSlotBtn = $('.delete-time-slot[data-delete-pending="true"]');
          console.log('Looking for time slot button:', {
            button: $timeSlotBtn,
            length: $timeSlotBtn.length,
            allPendingButtons: $('.delete-time-slot[data-delete-pending="true"]')
          });

          if ($timeSlotBtn.length) {
            var slotId = $timeSlotBtn.data('slot-id');
            console.log('Found time slot button, slotId:', slotId);
            $timeSlotBtn.removeAttr('data-delete-pending');

            // Call time slot delete handler
            if (typeof handleTimeSlotDelete === 'function') {
              console.log('handleTimeSlotDelete function available, calling it');
              handleTimeSlotDelete($timeSlotBtn, slotId);
            } else {
              console.log('handleTimeSlotDelete function not available');
            }
            return;
          } else {
            console.log('No time slot button found with data-delete-pending="true"');
          }

          // Find form delete button
          var $formBtn = $('.delete-form[data-delete-pending="true"]');
          if ($formBtn.length) {
            var formId = $formBtn.data('form-id');
            $formBtn.removeAttr('data-delete-pending');

            // Call form delete handler
            if (typeof handleFormDelete === 'function') {
              handleFormDelete($formBtn, formId);
            }
            return;
          }
        }, 100);
      }
    });

    // Close on overlay click
    $dialog.on("click", function (e) {
      if (e.target === this) {
        console.log("Delete confirm dialog overlay clicked - cancelled");
        $dialog.remove();
        // Clean up any pending delete markers
        $('.delete-booking[data-delete-pending="true"]').removeAttr("data-delete-pending");
        $('.delete-service[data-delete-pending="true"]').removeAttr("data-delete-pending");
        $('.delete-flow[data-delete-pending="true"]').removeAttr("data-delete-pending");
        $('.delete-time-slot[data-delete-pending="true"]').removeAttr("data-delete-pending");
        $('.delete-form[data-delete-pending="true"]').removeAttr("data-delete-pending");
        // Clean up handled markers
        $('.delete-booking[data-delete-handled="true"]').removeAttr("data-delete-handled");
        $('.delete-service[data-delete-handled="true"]').removeAttr("data-delete-handled");
        $('.delete-flow[data-delete-handled="true"]').removeAttr("data-delete-handled");
        $('.delete-time-slot[data-delete-handled="true"]').removeAttr("data-delete-handled");
        $('.delete-form[data-delete-handled="true"]').removeAttr("data-delete-handled");
      }
    });

    // Add enter key support
    $dialog.on("keydown", function (e) {
      if (e.key === "Enter") {
        $dialog.find(".ab-dialog-confirm").click();
      } else if (e.key === "Escape") {
        $dialog.find(".ab-dialog-cancel").click();
      }
    });

    // Focus confirm button
    setTimeout(function () {
      $dialog.find(".ab-dialog-confirm").focus();
    }, 100);
  };

  // Status control: show custom confirmation dialog
  $(document).on("focusin", ".booking-status", function () {
    var currentValue = $(this).val();
    console.log("Focusin event - setting prev status:", {
      element: this,
      currentValue: currentValue,
      currentValueType: typeof currentValue,
      isCustomDropdown: $(this).hasClass('ab-hidden-select')
    });
    $(this).data("prev", currentValue);
  });

  // Also handle mousedown for custom dropdowns to capture previous state
  $(document).on("mousedown", ".booking-status", function () {
    var $sel = $(this);
    // Only for custom dropdowns (hidden select)
    if ($sel.hasClass('ab-hidden-select')) {
      var currentValue = $sel.val();
      console.log("Mousedown event on custom dropdown - setting prev status:", currentValue);
      $sel.data("prev", currentValue);
    }
  });

  $(document).on("change", ".booking-status", function () {
    console.log("Booking status change event triggered!");
    try {
      if (window.console && console.debug)
        console.debug("[archeus] booking-status change detected");
    } catch (e) {}
    var $sel = $(this);
    var bookingId = $sel.data("id");
    var newStatus = $sel.val();
    var prevStatus = $sel.data("prev");

    console.log("Status change:", {
      from: prevStatus,
      to: newStatus,
      isSame: newStatus === prevStatus
    });

    // Prevent execution if the same status is selected again
    if (newStatus === prevStatus) {
      console.log("Same status selected, preventing execution for better user experience");
      return;
    }

    // Check if status is being changed to rejected - show reason dialog
    if (newStatus === 'rejected' && prevStatus !== 'rejected') {
      showRejectionReasonDialog(bookingId, prevStatus, newStatus, $sel);
      return;
    }

    proceedWithStatusUpdate(bookingId, newStatus, prevStatus, $sel);
  });

  // Function to show rejection reason dialog
  function showRejectionReasonDialog(bookingId, prevStatus, newStatus, $selectElement) {
    // Remove any existing dialogs
    $(".ab-dialog-overlay").remove();

    var dialogHtml =
      '<div class="ab-dialog-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 100000;">' +
      '<div class="ab-dialog" style="background: white; padding: 24px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-width: 500px; width: 90%;">' +
      '<h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: #1f2937;">Alasan Penolakan Booking</h3>' +
      '<p style="margin: 0 0 20px 0; color: #4b5563; line-height: 1.5;">Silakan masukkan alasan kenapa booking ini ditolak. Alasan ini akan dikirimkan ke customer.</p>' +
      '<textarea id="rejection-reason" rows="4" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; resize: vertical; margin-bottom: 20px;" placeholder="Contoh: Jadwal sudah penuh, persyaratan tidak terpenuhi, dll."></textarea>' +
      '<div style="display: flex; gap: 8px; justify-content: flex-end;">' +
      '<button type="button" class="ab-dialog-cancel button button-secondary" style="padding: 8px 16px; font-size: 14px;">Batal</button>' +
      '<button type="button" class="ab-dialog-confirm button button-primary" style="background: #dc2626; border-color: #dc2626; padding: 8px 16px; font-size: 14px;">Tolak dengan Alasan</button>' +
      '</div>' +
      '</div>' +
      '</div>';

    var $dialog = $(dialogHtml).appendTo("body");

    // Handle cancel button
    $dialog.find(".ab-dialog-cancel").on("click", function () {
      console.log("Rejection dialog cancelled");
      $dialog.remove();
      // Reset select to previous status
      $selectElement.val(prevStatus);
      // Also update the custom dropdown display
      if ($selectElement.hasClass('ab-hidden-select')) {
        var $wrapper = $selectElement.closest('.ab-dd');
        var $label = $wrapper.find('.ab-dd-label');
        var $menu = $wrapper.find('.ab-dd-menu');
        $label.text($selectElement.find('option:selected').text());
        $menu.find('.ab-dd-item').removeClass('is-selected');
        $menu.find('.ab-dd-item[data-value="' + prevStatus + '"]').addClass('is-selected');
      }
    });

    // Handle confirm button
    $dialog.find(".ab-dialog-confirm").on("click", function () {
      var rejectionReason = $("#rejection-reason").val().trim();

      if (!rejectionReason) {
        alert("Silakan masukkan alasan penolakan terlebih dahulu.");
        return;
      }

      console.log("Rejection dialog confirmed with reason:", rejectionReason);
      $dialog.remove();

      // Proceed with status update including rejection reason
      proceedWithStatusUpdate(bookingId, newStatus, prevStatus, $selectElement, rejectionReason);
    });

    // Handle overlay click
    $dialog.on("click", function (e) {
      if (e.target === this) {
        console.log("Rejection dialog overlay clicked - cancelled");
        $dialog.remove();
        // Reset select to previous status
        $selectElement.val(prevStatus);
        // Also update the custom dropdown display
        if ($selectElement.hasClass('ab-hidden-select')) {
          var $wrapper = $selectElement.closest('.ab-dd');
          var $label = $wrapper.find('.ab-dd-label');
          var $menu = $wrapper.find('.ab-dd-menu');
          $label.text($selectElement.find('option:selected').text());
          $menu.find('.ab-dd-item').removeClass('is-selected');
          $menu.find('.ab-dd-item[data-value="' + prevStatus + '"]').addClass('is-selected');
        }
      }
    });

    // Handle keyboard events
    $dialog.on("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        $dialog.find(".ab-dialog-confirm").click();
      } else if (e.key === "Escape") {
        e.preventDefault();
        $dialog.find(".ab-dialog-cancel").click();
      }
    });

    // Focus on textarea
    $("#rejection-reason").focus();
  }

  // Function to proceed with status update (refactored from original logic)
  function proceedWithStatusUpdate(bookingId, newStatus, prevStatus, $selectElement, rejectionReason) {
    // Set default value for rejectionReason if not provided
    if (typeof rejectionReason === 'undefined') {
      rejectionReason = '';
    }
    console.log(
      "Archeus: Sending status change request - Booking ID:",
      bookingId,
      "New Status:",
      newStatus,
      "Rejection Reason:",
      rejectionReason
    );

    var requestData = {
      action: "update_booking_status",
      booking_id: bookingId,
      status: newStatus,
      nonce: archeus_booking_ajax.nonce,
    };

    // Add rejection reason if provided
    if (rejectionReason) {
      requestData.rejection_reason = rejectionReason;
    }

    console.log("Archeus: AJAX request data:", requestData);

    // Proceed to save with full-page overlay
    if (!document.getElementById("ab-loading-style")) {
      var css = '.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}';
      var styleTag = document.createElement("style");
      styleTag.id = "ab-loading-style";
      styleTag.type = "text/css";
      styleTag.appendChild(document.createTextNode(css));
      document.head.appendChild(styleTag);
    }
    var $overlay = $(
      '<div class="ab-loading-overlay"><div class="ab-loading-spinner"></div><div class="ab-loading-text">Memperbarui status...</div></div>'
    ).appendTo("body");
    $selectElement.prop("disabled", true);

    $.ajax({
      url: archeus_booking_ajax.ajax_url,
      type: "POST",
      dataType: "text",
      data: requestData,
      success: function (resp) {
        console.log("Archeus: AJAX response received:", resp);
        console.log("Archeus: Response type:", typeof resp);
        console.log("Archeus: Response length:", resp ? resp.length : 'null');
        try {
          if (typeof resp === "string") {
            var firstBrace = resp.indexOf("{");
            if (firstBrace > 0) resp = resp.slice(firstBrace);
            resp = JSON.parse(resp);
          }
          console.log("Archeus: Parsed response:", resp);
        } catch (e) {
          console.error("Admin JSON parse error (update status)", e, resp);
          showToast("Invalid server response while updating status.", "error");
          $selectElement.val(prevStatus);
          if ($overlay) {
            $overlay.remove();
          }
          return;
        }
        if (resp && resp.success) {
          showToast(
            (resp.data && resp.data.message) || "Status berhasil diperbarui.",
            "success"
          );
          try {
            var __abMap = {
              pending: "#ab-count-pending",
              approved: "#ab-count-approved",
              completed: "#ab-count-completed",
              rejected: "#ab-count-rejected",
            };
            if (prevStatus && __abMap[prevStatus]) {
              var __p = jQuery(__abMap[prevStatus]);
              if (__p.length) {
                var __pv =
                  parseInt((__p.text() || "0").replace(/[^0-9]/g, "")) || 0;
                __p.text(Math.max(__pv - 1, 0));
              }
            }
            if (newStatus && __abMap[newStatus]) {
              var __n = jQuery(__abMap[newStatus]);
              if (__n.length) {
                var __nv =
                  parseInt((__n.text() || "0").replace(/[^0-9]/g, "")) || 0;
                __n.text(__nv + 1);
              }
            }
          } catch (e) {}
          // Adjust visibility of 'completed' option
          if (newStatus === "approved") {
            if ($selectElement.find('option[value="completed"]').length === 0) {
              $selectElement.append('<option value="completed">Selesai</option>');
            }
          } else if (newStatus !== "completed") {
            // Hide completed unless status is approved or already completed
            $selectElement.find('option[value="completed"]').remove();
          }
          $selectElement.data("prev", newStatus);
          $selectElement.prop("disabled", false);

          // Remove loading overlay first
          if ($overlay) {
            $overlay.remove();
          }

          // Show success message, then refresh page to show updated data and send email notification
          setTimeout(function () {
            console.log("Refreshing page to show updated booking data...");
            window.location.reload();
          }, 1500); // Wait 1.5 seconds before refresh to show success message and allow email notification
        } else {
          var msg =
            resp && resp.data && resp.data.message
              ? resp.data.message
              : "Failed to update status.";
          showToast(msg, "error");
          $selectElement.val(prevStatus);
          $selectElement.prop("disabled", false);
          if ($overlay) {
            $overlay.remove();
          }
        }
      },
      error: function (xhr, status, error) {
        console.error("Archeus: AJAX error occurred:", {
          xhr: xhr,
          status: status,
          error: error,
          responseText: xhr.responseText,
          statusCode: xhr.status,
        });
        showToast(
          "An error occurred while updating the booking status.",
          "error"
        );
        $selectElement.val(prevStatus);
        $selectElement.prop("disabled", false);
        if ($overlay) {
          $overlay.remove();
        }
      },
    });
  }

  // Handle delete booking buttons
  $(document).on("click", ".delete-booking", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var bookingId = $btn.data("id");

    console.log("Delete booking button clicked:", { bookingId });

    // Mark this button as pending deletion so the dialog can find it
    $btn.attr("data-delete-pending", "true");

    // Call the custom delete confirmation dialog
    if (typeof showDeleteConfirm === "function") {
      showDeleteConfirm("Yakin ingin menghapus booking ini?", "");
    }
  });

  // Handle view details button
  $(document).on("click", ".view-details-btn", function (e) {
    e.preventDefault();

    var bookingId = $(this).data("id");
    var detailsRow = $('tr.booking-details-row[data-id="' + bookingId + '"]');
    var container = detailsRow.find(".booking-details");

    // If visible and has content (not loading), hide
    if (detailsRow.is(":visible") && !container.hasClass("loading")) {
      detailsRow.hide();
      return;
    }

    // Show loading
    container.html('<div class="loading">Memuat detail...</div>');
    container.addClass("loading");
    detailsRow.show();

    console.log("Loading details for booking ID:", bookingId);
    console.log("AJAX data:", {
      action: "get_booking_details",
      booking_id: bookingId,
      nonce: archeus_booking_ajax.nonce,
    });

    $.ajax({
      url: archeus_booking_ajax.ajax_url,
      type: "POST",
      dataType: "text",
      data: {
        action: "get_booking_details",
        booking_id: bookingId,
        nonce: archeus_booking_ajax.nonce,
      },
      success: function (resp) {
        console.log("Raw response:", resp);
        try {
          if (typeof resp === "string") {
            var firstBrace = resp.indexOf("{");
            if (firstBrace > 0) resp = resp.slice(firstBrace);
            console.log("Cleaned response:", resp);
            resp = JSON.parse(resp);
          }
          console.log("Parsed response:", resp);
        } catch (e) {
          console.error("Details JSON parse error", e, resp);
          container.html(
            '<div class="error">Gagal memuat detail. Error parsing JSON.</div>'
          );
          return;
        }
        if (!resp || !resp.success || !resp.data) {
          console.error("Invalid response structure:", resp);
          var errorMsg = resp && resp.data ? resp.data : "Gagal memuat detail.";
          container.html('<div class="error">' + errorMsg + "</div>");
          return;
        }
        try {
          console.log("Starting data rendering...");
          var data = resp.data;
          var keys = Object.keys(data);
          console.log("Data keys:", keys);
          var exclude = [
            "payload",
            "form_id",
            "schedule_id",
            "flow_id",
            "flowname",
            "flow_name",
            "id",
            "time_slot",
            "fields",  // Exclude 'fields' as custom fields are now individual properties
          ];

          // Function to get user-friendly field labels
          function getFieldLabel(key) {
            var labelMap = {
              'customer_name': 'Nama Lengkap',
              'customer_email': 'Email',
              'nama_lengkap': 'Nama Lengkap',
              'email': 'Email',
              'nama': 'Nama',
              'phone': 'No. Telepon',
              'telepon': 'No. Telepon',
              'no_hp': 'No. HP',
              'alamat': 'Alamat',
              'tanggal': 'Tanggal',
              'waktu': 'Waktu',
              'date': 'Tanggal',
              'time': 'Waktu',
              'booking_date': 'Tanggal Reservasi',
              'booking_time': 'Waktu Reservasi',
              'service_type': 'Layanan',
              'jenis_layanan': 'Layanan',
              'bukti_reservasi': 'Bukti Reservasi',
              'bukti_vaksin': 'Bukti Vaksin',
              'status': 'Status',
              'created_at': 'Tanggal Dibuat',
              'updated_at': 'Tanggal Diperbarui',
              // Common custom fields
              'jenis_hewan': 'Jenis Hewan',
              'nama_hewan': 'Nama Hewan',
              'jenis_kelamin_hewan': 'Jenis Kelamin Hewan',
              'jenis_vaksin': 'Jenis Vaksin',
              'tanggal_vaksinasi_terakhir': 'Tanggal Vaksinasi Terakhir',
              'usia_hewan': 'Usia Hewan',
              'berat_hewan': 'Berat Hewan',
              'keluhan': 'Keluhan',
              'catatan': 'Catatan',
              'pesan': 'Pesan',
              'keterangan': 'Keterangan',
              'alamat_lengkap': 'Alamat Lengkap',
              'kota': 'Kota',
              'provinsi': 'Provinsi',
              'kode_pos': 'Kode Pos',
              'no_telepon': 'No. Telepon',
              'no_handphone': 'No. Handphone',
              'tanggal_lahir': 'Tanggal Lahir',
              'usia': 'Usia',
              'pekerjaan': 'Pekerjaan',
              'instansi': 'Instansi',
              'alasan': 'Alasan',
              'riwayat': 'Riwayat'
            };

            return labelMap[key] || key.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
          }

          var html = "";
          html += '<div class="booking-details-card">';
          html += '<h4 class="booking-details-title">Detail Reservasi</h4>';
          html += '<table class="booking-details-table"><tbody>';
          console.log("Starting to process keys...");
          var previewId = "bv-preview-" + bookingId;

          // Prioritized fields for custom layout
          var prioritizedFields = ['customer_name', 'customer_email', 'service_type', 'booking_time'];
          var processedFields = [];

          // First row: customer_name and customer_email
          if (data.customer_name || data.customer_email) {
            html += "<tr>";
            html += "<td><strong>Nama:</strong> " + (data.customer_name || "-") + "</td>";
            html += "<td><strong>Email:</strong> " + (data.customer_email || "-") + "</td>";
            html += "</tr>";
            processedFields.push('customer_name', 'customer_email');
          }

          // Second row: service_type and booking_time
          if (data.service_type || data.booking_time) {
            html += "<tr>";
            html += "<td><strong>Layanan:</strong> " + (data.service_type || "-") + "</td>";
            html += "<td><strong>Waktu:</strong> " + (data.booking_time || "-") + "</td>";
            html += "</tr>";
            processedFields.push('service_type', 'booking_time');
          }

          // Filter and categorize remaining keys
          var filteredKeys = keys.filter(function (k) {
            return exclude.indexOf(k) === -1 && processedFields.indexOf(k) === -1;
          });

          // Function to check if a value is a file URL
          function isFileUrl(value) {
            if (!value || typeof value !== 'string') return false;
            // Check for WordPress upload patterns
            return value.includes('/wp-content/uploads/') ||
                   value.match(/\.(pdf|jpg|jpeg|png|gif|bmp|doc|docx|xls|xlsx|ppt|pptx|txt|zip|rar)$/i);
          }

          // Function to extract filename from URL
          function extractFileName(url) {
            try {
              var p = url.split("?")[0];
              return decodeURIComponent(p.substring(p.lastIndexOf("/") + 1));
            } catch (e) {
              return url;
            }
          }

          // Function to generate file action buttons
          function generateFileActions(url, previewId) {
            return '<div class="bv-actions">' +
                   '  <button type="button" class="button bv-preview" data-url="' + url + '" data-target="#' + previewId + '" aria-label="Lihat pratinjau">' +
                   '    <span class="dashicons dashicons-visibility" aria-hidden="true"></span>' +
                   '  </button>' +
                   '  <a class="button bv-open" href="' + url + '" target="_blank" rel="noopener" aria-label="Buka di tab baru">' +
                   '    <span class="dashicons dashicons-external" aria-hidden="true"></span>' +
                   '  </a>' +
                   '  <a class="button bv-download" href="' + url + '" download aria-label="Unduh berkas">' +
                   '    <span class="dashicons dashicons-download" aria-hidden="true"></span>' +
                   '  </a>' +
                   '</div>';
          }

          // Process remaining keys in pairs, except for address and file fields
          for (var i = 0; i < filteredKeys.length; i++) {
            var k = filteredKeys[i];
            var v = data[k];

            // Handle special fields as full-width rows
            if (k === "alamat") {
              html += '<tr><td colspan="2"><strong>' + getFieldLabel(k) + ':</strong> ' + (v || '-') + '</td></tr>';
            }
            // Handle file fields dynamically based on content
            else if (isFileUrl(v)) {
              var fileName = extractFileName(v);
              var actions = generateFileActions(v, previewId);
              html += '<tr><td colspan="2"><div class="bv-container" style="display: flex; justify-content: space-between; align-items: center;"><div><strong>' +
                     getFieldLabel(k) + ':</strong> ' + fileName +
                     '</div><div>' + actions +
                     '</div></div></td></tr>';
            }
            // Handle other fields (not file URLs) in pairs or single
            else {
              // Check if we can pair with the next field
              // Don't pair if current or next field is address or a file URL
              var nextField = i + 1 < filteredKeys.length ? filteredKeys[i + 1] : null;
              var nextFieldValue = nextField ? data[nextField] : null;

              if (
                nextField &&
                k !== "alamat" &&
                nextField !== "alamat" &&
                !isFileUrl(v) &&
                !isFileUrl(nextFieldValue)
              ) {
                // Current field
                var k2 = filteredKeys[i + 1];
                var v2 = data[k2];

                html += "<tr>";
                html +=
                  "<td><strong>" +
                  getFieldLabel(k) +
                  ":</strong> " +
                  (v == null ? "" : v) +
                  "</td>";
                html +=
                  "<td><strong>" +
                  getFieldLabel(k2) +
                  ":</strong> " +
                  (v2 == null ? "" : v2) +
                  "</td>";
                html += "</tr>";

                i++; // Skip the next field as we've already processed it
              } else {
                // Single field row
                html += "<tr>";
                html +=
                  '<td colspan="2"><strong>' +
                  getFieldLabel(k) +
                  ":</strong> " +
                  (v == null ? "" : v) +
                  "</td>";
                html += "</tr>";
              }
            }
          }
          console.log("Finished processing keys. HTML length:", html.length);
          html += "</tbody></table>";
          html +=
            '<div id="' +
            previewId +
            '" class="bv-preview-container" style="margin-top:12px; display:none;"></div>';
          html += "</div>";
          console.log("About to set container HTML...");
          container.html(html);
          container.removeClass("loading");
          console.log(
            "Container HTML set successfully. Details should be visible."
          );
        } catch (renderError) {
          console.error("Error during rendering:", renderError);
          container.removeClass("loading");
          container.html(
            '<div class="error">Terjadi kesalahan saat menampilkan detail: ' +
              renderError.message +
              "</div>"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", {
          xhr: xhr,
          status: status,
          error: error,
        });
        console.error("Response text:", xhr.responseText);
        var errorMsg = "Gagal memuat detail.";
        if (xhr.responseText) {
          try {
            var errorResp = JSON.parse(xhr.responseText);
            if (errorResp && errorResp.data) {
              errorMsg = errorResp.data;
            }
          } catch (e) {}
        }
        container.removeClass("loading");
        container.html('<div class="error">' + errorMsg + "</div>");
      },
    });
  });

  // Preview bukti_vaksin inline
  $(document).on("click", ".bv-preview", function () {
    var url = $(this).data("url");
    var target = $(this).data("target");
    var $target = $(target);
    if (!$target.length) return;
    // Toggle if already visible
    if ($target.is(":visible")) {
      $target.hide().empty();
      return;
    }
    var lower = (url || "").toLowerCase();
    var html = "";
    if (/(\.png|\.jpe?g|\.gif|\.webp|\.bmp)$/.test(lower)) {
      html =
        '<img src="' +
        url +
        '" alt="Bukti Vaksin" style="max-width:100%;height:auto;border:1px solid #ddd;padding:4px;background:#fff;">';
    } else if (/\.pdf$/.test(lower)) {
      html =
        '<iframe src="' +
        url +
        '#toolbar=1" style="width:100%;height:520px;border:1px solid #ddd;background:#fff;"></iframe>';
    } else {
      html =
        '<div class="notice">Tidak dapat melakukan pratinjau berkas ini. Gunakan tombol Buka atau Unduh.</div>';
    }
    $target.html(html).show();
  });

  // Function to show messages - make it global
  window.showToast = function(message, type) {
    try {
      if (!document.getElementById("ab-toast-style")) {
        var css =
          ".ab-toast{position:fixed;right:16px;bottom:16px;background:#1f2937;color:#fff;padding:10px 14px;border-radius:6px;box-shadow:0 6px 16px rgba(0,0,0,.2);z-index:100000;opacity:.98;transition:opacity .3s ease, transform .3s ease;transform:translateY(8px);} .ab-toast.success{background:#16a34a} .ab-toast.error{background:#dc2626}";
        var st = document.createElement("style");
        st.id = "ab-toast-style";
        st.appendChild(document.createTextNode(css));
        document.head.appendChild(st);
      }
      var el = document.createElement("div");
      el.className = "ab-toast " + (type || "success");
      el.textContent = message;
      document.body.appendChild(el);
      setTimeout(function () {
        el.style.opacity = 0;
        el.style.transform = "translateY(0)";
        setTimeout(function () {
          if (el && el.parentNode) {
            el.parentNode.removeChild(el);
          }
        }, 400);
      }, 1000);
    } catch (e) {
      showMessage(message, type === "error" ? "error" : "success");
    }
  }

  function showMessage(message, type) {
    // Remove any existing messages
    $(".booking-message").remove();

    var messageClass =
      type === "success" ? "notice notice-success" : "notice notice-error";
    var messageHtml =
      '<div class="booking-message ' +
      messageClass +
      '"><p>' +
      message +
      "</p></div>";

    $(".wrap").prepend(messageHtml);

    // Auto-hide success messages after 5 seconds
    if (type === "success") {
      setTimeout(function () {
        $(".booking-message").fadeOut();
      }, 5000);
    }
  }

  // Helper function to format date
  function formatDate(dateString) {
    var date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  }

  // Helper function to format full date/time
  function formatDateFull(dateTimeString) {
    var date = new Date(dateTimeString);
    return date.toLocaleString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  // Progressive enhancement: custom dropdown for .ab-dropdown (copied from booking-flow)
  function enhanceAbDropdowns(root) {
    console.log('enhanceAbDropdowns called with root:', root);
    var $root = root && root.jquery ? root : $(document);
    var $dropdowns = $root.find("select.ab-dropdown").not('.field-type-select');
    console.log('Found select.ab-dropdown elements (excluding field-type-select):', $dropdowns.length);
    $dropdowns.each(function () {
      var $sel = $(this);
      console.log('Processing dropdown:', $sel.attr('id'), 'or', $sel.attr('class'));
      if ($sel.data("ab-dd")) return; // already enhanced
      $sel.data("ab-dd", true);
      console.log('Enhancing dropdown:', $sel.attr('id'));

      var selectedText = $sel.find("option:selected").text() || "";
      var $wrap = $('<div class="ab-dd"></div>');
      var $btn = $(
        '<button type="button" class="ab-dd-toggle" aria-haspopup="listbox" aria-expanded="false"></button>'
      );
      var $label = $('<span class="ab-dd-label"></span>').text(selectedText);
      var $caret = $('<span class="ab-dd-caret" aria-hidden="true"></span>');
      $btn.append($label).append($caret);
      var $menu = $('<div class="ab-dd-menu" role="listbox"></div>');

      $sel.find("option").each(function () {
        var $opt = $(this);
        var optValue = $opt.attr("value");
        var optText = $opt.text();

        var $item = $(
          '<div class="ab-dd-item" role="option" tabindex="-1"></div>'
        ).text(optText);
        $item.attr("data-value", optValue);
        if ($opt.is(":selected")) $item.addClass("is-selected");
        $menu.append($item);
      });

      $sel.addClass("ab-hidden-select").hide().after($wrap);
      $wrap.append($btn).append($menu);
      $sel.appendTo($wrap); // keep in wrap to trigger change

      function closeMenu() {
        console.log('closeMenu called for:', $sel.attr('id'));
        $wrap.removeClass("open");
        $btn.attr("aria-expanded", "false");
        console.log('Menu closed, .open class removed');

        // Reset positioning when closing dropdown
        if ($wrap.closest('.form-fields-builder').length > 0) {
          $menu.css({
            position: '',
            left: '',
            top: '',
            width: '',
            maxHeight: ''
          });
        }
      }
      function openMenu() {
        console.log('openMenu called for:', $sel.attr('id'));
        $wrap.addClass("open");
        $btn.attr("aria-expanded", "true");
        console.log('Menu opened, .open class added');

        // Debug positioning before positioning function
        setTimeout(function() {
          console.log('=== POSITIONING DEBUG ===');
          console.log('Button position:', $btn.offset());
          console.log('Button dimensions:', $btn.outerWidth(), 'x', $btn.outerHeight());
          console.log('Menu position:', $menu.offset());
          console.log('Menu dimensions:', $menu.outerWidth(), 'x', $menu.outerHeight());
          console.log('Menu CSS position:', $menu.css('position'));
          console.log('Menu CSS top:', $menu.css('top'));
          console.log('Menu CSS left:', $menu.css('left'));
          console.log('Menu CSS right:', $menu.css('right'));
          console.log('Menu CSS bottom:', $menu.css('bottom'));
          console.log('Menu CSS transform:', $menu.css('transform'));
          console.log('Menu CSS display:', $menu.css('display'));
          console.log('Menu CSS visibility:', $menu.css('visibility'));
          console.log('Menu CSS opacity:', $menu.css('opacity'));
          console.log('Menu z-index:', $menu.css('z-index'));
          console.log('Menu classes:', $menu.attr('class'));
          console.log('Parent container:', $wrap.parent());
          console.log('=== END POSITIONING DEBUG ===');

          positionDropdown($menu, $btn);
        }, 10);
      }

      // Smart dropdown positioning to escape overflow containers
      function positionDropdown($menu, $btn) {
        try {
          var $formBuilder = $wrap.closest('.form-fields-builder, .table-overflow, .widefat, .admin-card-body');
          var $bookingFilters = $wrap.closest('.booking-filters');
          var isInOverflow = $formBuilder.length > 0;
          var isInBookingFilters = $bookingFilters.length > 0;

          console.log('Positioning debug - isInOverflow:', isInOverflow, 'isInBookingFilters:', isInBookingFilters);

          if (isInBookingFilters) {
            // Use simple absolute positioning for booking-filters
            console.log('Using booking-filters positioning');
            $menu.css({
              position: 'absolute',
              left: '0',
              top: 'calc(100% + 4px)',
              width: '100%',
              minWidth: '220px',
              zIndex: 999999
            });
          } else if (isInOverflow) {
            // Use fixed positioning to escape overflow container
            var btnRect = $btn[0].getBoundingClientRect();
            var viewportHeight = window.innerHeight;
            var menuHeight = Math.min(300, $menu.find('.ab-dd-item').length * 40);

            // Calculate position
            var left = btnRect.left;
            var top = btnRect.bottom + 2; // Show below button first

            // Adjust if menu would go off right edge
            if (left + 200 > window.innerWidth) {
              left = Math.max(10, window.innerWidth - 210);
            }

            // If menu would go off bottom, position above button
            if (top + menuHeight > viewportHeight - 20) {
              top = btnRect.top - menuHeight - 2;
              // If still would go off top, position below anyway
              if (top < 10) {
                top = btnRect.bottom + 2;
              }
            }

            // Ensure the dropdown is visible and properly positioned
            $menu.css({
              position: 'fixed',
              left: left + 'px',
              top: top + 'px',
              width: '200px',
              maxHeight: (viewportHeight - top - 10) + 'px',
              zIndex: 999999
            });
          } else {
            // Use normal absolute positioning for non-overflow containers
            $menu.css({
              position: 'absolute',
              left: '0',
              top: 'calc(100% + 6px)',
              width: 'auto',
              minWidth: '200px',
              zIndex: 99999
            });
          }
        } catch (e) {
          console.error('Dropdown positioning error:', e);
          // Fallback to simple positioning
          $menu.css({
            position: 'absolute',
            left: '0',
            top: 'calc(100% + 4px)',
            width: '100%',
            minWidth: '220px',
            zIndex: 999999
          });
        }
      }

      $btn.on("click", function (e) {
        console.log('Dropdown button clicked:', $sel.attr('id'));
        e.preventDefault();
        if ($wrap.hasClass("open")) {
          console.log('Closing dropdown');
          closeMenu();
        } else {
          console.log('Opening dropdown');
          openMenu();
        }
      });

      $(document).on("click", function (e) {
        if (!$.contains($wrap[0], e.target)) closeMenu();
      });

      // Handle window scroll/resize to reposition dropdown
      var scrollResizeHandler = function() {
        if ($wrap.hasClass("open") && $wrap.closest('.form-fields-builder').length > 0) {
          positionDropdown($menu, $btn);
        }
      };
      $(window).on('scroll', scrollResizeHandler);
      $(window).on('resize', scrollResizeHandler);

      $menu.on("click", ".ab-dd-item", function () {
        var val = $(this).attr("data-value");
        $sel.val(val).trigger("change");
        $menu.find(".ab-dd-item").removeClass("is-selected");
        $(this).addClass("is-selected");
        $label.text($(this).text());
        closeMenu();
      });

      $sel.on("change", function () {
        var txt = $sel.find("option:selected").text() || "";
        var val = $sel.val();
        $label.text(txt);
        $menu.find(".ab-dd-item").each(function () {
          var $i = $(this);
          $i.toggleClass("is-selected", $i.attr("data-value") == val);
        });
      });
    });
  }

  // Initialize dropdowns with proper timing
  $(document).ready(function() {
    console.log('Document ready, initializing dropdowns...');
    setTimeout(function() {
      enhanceAbDropdowns($(document));
    }, 100);
  });

  // Observe DOM changes to enhance future selects
  if (window.MutationObserver) {
    var moAll = new MutationObserver(function (muts) {
      // Try enhancing any new selects
      try {
        enhanceAbDropdowns($(document));
      } catch (e) {}
    });
    moAll.observe(document.body, { childList: true, subtree: true });
  }

  // Handle status filter (with overlay)
  $("#booking-status-filter").on("change", function (event, data) {
    var isInitial = data && data.isInitialLoad;
    var status = $(this).val();
    var flowId = $("#booking-flow-filter").length
      ? $("#booking-flow-filter").val()
      : $("#ab-flow-select").length
      ? $("#ab-flow-select").val()
      : 0;
    var $overlay = null;

    if (!isInitial) {
      if (!document.getElementById("ab-loading-style")) {
        var css =
          "\n.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}\n.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}\n.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}\n@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}\n";
        var st = document.createElement("style");
        st.id = "ab-loading-style";
        st.appendChild(document.createTextNode(css));
        document.head.appendChild(st);
      }
      $overlay = $(
        '<div class="ab-loading-overlay"><div class="ab-loading-spinner"></div><div class="ab-loading-text">Memuat...</div></div>'
      ).appendTo("body");
    }

    $.ajax({
      url: archeus_booking_ajax.ajax_url,
      type: "POST",
      dataType: "text",
      data: {
        action: "get_bookings",
        status: status,
        flow_id: flowId,
        nonce: archeus_booking_ajax.nonce,
      },
      success: function (resp) {
        try {
          if (typeof resp === "string") {
            var fb = resp.indexOf("{");
            if (fb > 0) resp = resp.slice(fb);
            resp = JSON.parse(resp);
          }
        } catch (e) {
          console.error("Admin JSON parse error (filter bookings)", e, resp);
          showToast("Invalid server response while loading bookings.", "error");
          if ($overlay) {
            $overlay.remove();
          }
          return;
        }
        if (resp && resp.success) {
          var bookings = resp.data && (resp.data.bookings || resp.data);
          console.log("Bookings data received:", bookings); // Debug log
          if (bookings && bookings.length > 0) {
            console.log("First booking structure:", bookings[0]); // Debug first record structure
          }
          updateBookingsTable(bookings);
          if (resp.data && resp.data.stats) {
            updateDashboardStats(resp.data.stats);
          }
          // Update shortcode display if exists
          if (resp.data && resp.data.shortcode) {
            updateShortcodeDisplay(resp.data.shortcode);
          }
        } else {
          var msg =
            resp && resp.data && resp.data.message
              ? resp.data.message
              : "Failed to load bookings.";
          showToast(msg, "error");
        }
        if ($overlay) {
          $overlay.remove();
        }
      },
      error: function () {
        showToast("An error occurred while refreshing bookings.", "error");
        if ($overlay) {
          $overlay.remove();
        }
      },
    });
  });

  // Handle flow filter (silent) - for booking-flow-filter
  $("#booking-flow-filter").on("change", function (event, data) {
    var status = $("#booking-status-filter").val();
    var flowId = $(this).val();
    $.ajax({
      url: archeus_booking_ajax.ajax_url,
      type: "POST",
      dataType: "text",
      data: {
        action: "get_bookings",
        status: status,
        flow_id: flowId,
        nonce: archeus_booking_ajax.nonce,
      },
      success: function (resp) {
        try {
          if (typeof resp === "string") {
            var fb = resp.indexOf("{");
            if (fb > 0) resp = resp.slice(fb);
            resp = JSON.parse(resp);
          }
        } catch (e) {
          console.error("Admin JSON parse error (filter bookings)", e, resp);
          showToast("Invalid server response while loading bookings.", "error");
          return;
        }
        if (resp && resp.success) {
          var bookings = resp.data && (resp.data.bookings || resp.data);
          console.log("Bookings data received:", bookings); // Debug log
          if (bookings && bookings.length > 0) {
            console.log("First booking structure:", bookings[0]); // Debug first record structure
          }
          updateBookingsTable(bookings);
          if (resp.data && resp.data.stats) {
            updateDashboardStats(resp.data.stats);
          }
          // Update shortcode display if exists
          if (resp.data && resp.data.shortcode) {
            updateShortcodeDisplay(resp.data.shortcode);
          }
        } else {
          var msg =
            resp && resp.data && resp.data.message
              ? resp.data.message
              : "Failed to load bookings.";
          showToast(msg, "error");
        }
      },
      error: function () {
        showToast("An error occurred while refreshing bookings.", "error");
      },
    });
  });

  // Handle admin notice flow filter (#ab-flow-select) - update all dashboard components
  $("#ab-flow-select").on("change", function (event, data) {
    var isInitial = data && data.isInitialLoad;
    var flowId = $(this).val();
    var status = $("#booking-status-filter").val();
    var $overlay = null;

    // Update active flow label
    updateFlowLabel();

    if (!isInitial) {
      if (!document.getElementById("ab-loading-style")) {
        var css =
          "\n.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}\n.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}\n.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}\n@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}\n";
        var st = document.createElement("style");
        st.id = "ab-loading-style";
        st.appendChild(document.createTextNode(css));
        document.head.appendChild(st);
      }
      $overlay = $(
        '<div class="ab-loading-overlay"><div class="ab-loading-spinner"></div><div class="ab-loading-text">Memuat...</div></div>'
      ).appendTo("body");
    }

    // Update all dashboard components
    $.ajax({
      url: archeus_booking_ajax.ajax_url,
      type: "POST",
      dataType: "text",
      data: {
        action: "get_bookings",
        status: status,
        flow_id: flowId,
        nonce: archeus_booking_ajax.nonce,
      },
      success: function (resp) {
        try {
          if (typeof resp === "string") {
            var fb = resp.indexOf("{");
            if (fb > 0) resp = resp.slice(fb);
            resp = JSON.parse(resp);
          }
        } catch (e) {
          console.error("Admin JSON parse error (flow filter)", e, resp);
          showToast("Invalid server response while loading bookings.", "error");
          if ($overlay) {
            $overlay.remove();
          }
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
          var msg =
            resp && resp.data && resp.data.message
              ? resp.data.message
              : "Failed to load bookings.";
          showToast(msg, "error");
        }
        if ($overlay) {
          $overlay.remove();
        }
      },
      error: function () {
        showToast("An error occurred while refreshing bookings.", "error");
        if ($overlay) {
          $overlay.remove();
        }
      },
    });
  });

  // Handle refresh button
  $("#refresh-bookings").on("click", function () {
    var status = $("#booking-status-filter").val();
    var flowId = $("#booking-flow-filter").length
      ? $("#booking-flow-filter").val()
      : $("#ab-flow-select").length
      ? $("#ab-flow-select").val()
      : 0;
    // Overlay while refreshing
    if (!document.getElementById("ab-loading-style")) {
      var css =
        "\n.ab-loading-overlay{position:fixed;inset:0;width:100%;height:100%;background:rgba(255,255,255,0.75);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:99999;}\n.ab-loading-spinner{width:60px;height:60px;border:6px solid #e5e7eb;border-top:6px solid #54b335;border-radius:50%;animation:abspin 1s linear infinite;}\n.ab-loading-text{margin-top:12px;font-weight:600;color:#1f2937;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}\n@keyframes abspin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}\n";
      var st = document.createElement("style");
      st.id = "ab-loading-style";
      st.appendChild(document.createTextNode(css));
      document.head.appendChild(st);
    }
    var $overlay2 = $(
      '<div class="ab-loading-overlay"><div class="ab-loading-spinner"></div><div class="ab-loading-text">Memuat...</div></div>'
    ).appendTo("body");
    $.ajax({
      url: archeus_booking_ajax.ajax_url,
      type: "POST",
      dataType: "text",
      data: {
        action: "get_bookings",
        status: status,
        flow_id: flowId,
        nonce: archeus_booking_ajax.nonce,
      },
      success: function (resp) {
        try {
          if (typeof resp === "string") {
            var firstBrace = resp.indexOf("{");
            if (firstBrace > 0) resp = resp.slice(firstBrace);
            resp = JSON.parse(resp);
          }
        } catch (e) {
          console.error("Admin JSON parse error (refresh bookings)", e, resp);
          showToast("Invalid server response while refreshing.", "error");
          if ($overlay2) {
            $overlay2.remove();
          }
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
          showToast("Bookings refreshed successfully.", "success");
          if ($overlay2) {
            $overlay2.remove();
          }
        } else {
          var msg =
            resp && resp.data && resp.data.message
              ? resp.data.message
              : "Failed to refresh bookings.";
          showToast(msg, "error");
          if ($overlay2) {
            $overlay2.remove();
          }
        }
      },
      error: function () {
        showToast("An error occurred while refreshing bookings.", "error");
        if ($overlay2) {
          $overlay2.remove();
        }
      },
    });
  });

  // Function to update the bookings table
  function updateBookingsTable(bookings) {
    var tbody = $("#bookings-table-body");
    tbody.empty();

    if (!bookings || !bookings.length) {
      tbody.append(
        '<tr class="no-data"><td colspan="8" class="no-data-cell">Data tidak tersedia atau data kosong.</td></tr>'
      );
      return;
    }

    $.each(bookings, function (index, booking) {
      var completedAllowed =
        booking.status === "approved" || booking.status === "completed";

      // Handle multiple possible field names for customer name
      var customerName =
        booking.display_name ||
        booking.nama_lengkap ||
        booking.customer_name ||
        booking.name ||
        (booking.first_name && booking.last_name
          ? booking.first_name + " " + booking.last_name
          : "") ||
        "-";
      var customerTitle = customerName !== "-" ? customerName : "";

      var row =
        '<tr data-id="' +
        booking.id +
        '">' +
        '<td class="col-id">' +
        booking.id +
        "</td>" +
        '<td class="col-name" title="' +
        customerTitle +
        '">' +
        customerName +
        "</td>" +
        "<td>" +
        formatDate(booking.booking_date) +
        "</td>" +
        "<td>" +
        (booking.booking_time || "") +
        "</td>" +
        "<td>" +
        booking.service_type +
        "</td>" +
        "<td>" +
        '<div class="status-control">' +
        '<select class="booking-status ab-select ab-dropdown" data-id="' +
        booking.id +
        '">' +
        '<option value="pending"' +
        (booking.status === "pending" ? " selected" : "") +
        ">Menunggu</option>" +
        '<option value="approved"' +
        (booking.status === "approved" ? " selected" : "") +
        ">Disetujui</option>" +
        (completedAllowed
          ? '<option value="completed"' +
            (booking.status === "completed" ? " selected" : "") +
            ">Selesai</option>"
          : "") +
        '<option value="rejected"' +
        (booking.status === "rejected" ? " selected" : "") +
        ">Ditolak</option>" +
        "</select>" +
        "</div>" +
        "</td>" +
        "<td>" +
        formatDateFull(booking.created_at) +
        "</td>" +
        '<td class="col-actions">' +
        '<div class="action-buttons">' +
        '<button class="view-details-btn button" data-id="' +
        booking.id +
        '" title="Lihat Detail"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><span class="screen-reader-text">Lihat Detail</span></button>' +
        '<button class="delete-booking button" data-id="' +
        booking.id +
        '" title="Hapus Booking"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="text">Hapus</span></button>' +
        "</div>" +
        "</td>" +
        "</tr>" +
        '<tr class="booking-details-row" data-id="' +
        booking.id +
        '" style="display: none;">' +
        '<td colspan="7">' +
        '<div class="booking-details">' +
        "<h4>Additional Information</h4>" +
        "<p>Tidak ada detail tambahan.</p>" +
        "</div>" +
        "</td>" +
        "</tr>";

      tbody.append(row);
    });

    // Re-enhance dropdowns for new rows
    enhanceAbDropdowns(tbody);
  }

  // Update dashboard stats counters
  function updateDashboardStats(stats) {
    try {
      if (typeof stats !== "object" || !stats) return;
      if (typeof stats.total !== "undefined")
        jQuery("#ab-count-total").text(stats.total);
      if (typeof stats.pending !== "undefined")
        jQuery("#ab-count-pending").text(stats.pending);
      if (typeof stats.approved !== "undefined")
        jQuery("#ab-count-approved").text(stats.approved);
      if (typeof stats.completed !== "undefined")
        jQuery("#ab-count-completed").text(stats.completed);
      if (typeof stats.rejected !== "undefined")
        jQuery("#ab-count-rejected").text(stats.rejected);
    } catch (e) {}
  }

  // Update active flow label near stats
  function updateFlowLabel() {
    try {
      var $sel = jQuery("#ab-flow-select");
      var label =
        $sel.length && $sel.find("option:selected").text()
          ? $sel.find("option:selected").text()
          : "Semua Flow";
      var $lbl = jQuery("#ab-flow-active");
      if ($lbl.length) $lbl.text(label);
    } catch (e) {}
  }

  // Update shortcode display in admin notice
  function updateShortcodeDisplay(shortcode) {
    try {
      // Update via HTML container (if exists)
      var $shortcodeContainer = $(".archeus-booking-shortcode");
      if ($shortcodeContainer.length && shortcode) {
        $shortcodeContainer.html(shortcode);
      }

      // Also update directly the code element and copy button data
      var $codeElement = $("#ab-sc-with-id");
      var $copyButton = $("#ab-copy-with-id");

      if ($codeElement.length && shortcode) {
        $codeElement.text(shortcode);
      }

      if ($copyButton.length && shortcode) {
        $copyButton.attr("data-copy", shortcode);
      }

      // Also handle via flow select change (fallback)
      var $flowSelect = $("#ab-flow-select");
      if ($flowSelect.length) {
        var selectedId = $flowSelect.val() || "1";
        var dynamicShortcode = '[archeus_booking id="' + selectedId + '"]';

        if ($codeElement.length) {
          $codeElement.text(dynamicShortcode);
        }
        if ($copyButton.length) {
          $copyButton.attr("data-copy", dynamicShortcode);
        }
      }
    } catch (e) {
      console.error("Error updating shortcode display:", e);
    }
  }

  // Initialize label on load
  updateFlowLabel();

  // Handle direct flow select change for shortcode update (backup for inline JS)
  $(document).on("change", "#ab-flow-select", function () {
    var selectedId = $(this).val() || "1";
    var shortcode = '[archeus_booking id="' + selectedId + '"]';

    // Update the code display
    var $codeElement = $("#ab-sc-with-id");
    if ($codeElement.length) {
      $codeElement.text(shortcode);
    }

    // Update the copy button data
    var $copyButton = $("#ab-copy-with-id");
    if ($copyButton.length) {
      $copyButton.attr("data-copy", shortcode);
    }

    console.log("Shortcode updated to:", shortcode);
  });

  // Handle copy shortcode buttons
  $(document).on("click", ".ab-copy-btn", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var textToCopy =
      $btn.attr("data-copy") ||
      $btn.siblings(".ab-shortcode-code").text() ||
      "";

    if (!textToCopy) {
      showToast("Tidak ada teks untuk disalin.", "error");
      return;
    }

    // Modern clipboard API
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard
        .writeText(textToCopy)
        .then(function () {
          showToast("Shortcode berhasil disalin!", "success");

          // Visual feedback
          var $icon = $btn.find(".dashicons");
          var $text = $btn.find("span:not(.dashicons)");
          var originalIcon = $icon.attr("class");
          var originalText = $text.text();

          $icon.removeClass("dashicons-clipboard").addClass("dashicons-yes");
          $text.text("Tersalin!");

          setTimeout(function () {
            $icon.removeClass("dashicons-yes").addClass(originalIcon);
            $text.text(originalText);
          }, 2000);
        })
        .catch(function (err) {
          console.error("Clipboard API failed:", err);
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
      var successful = document.execCommand("copy");
      if (successful) {
        showToast("Shortcode berhasil disalin!", "success");

        // Visual feedback
        var $icon = $btn.find(".dashicons");
        var $text = $btn.find("span:not(.dashicons)");
        var originalIcon = $icon.attr("class");
        var originalText = $text.text();

        $icon.removeClass("dashicons-clipboard").addClass("dashicons-yes");
        $text.text("Tersalin!");

        setTimeout(function () {
          $icon.removeClass("dashicons-yes").addClass(originalIcon);
          $text.text(originalText);
        }, 2000);
      } else {
        showToast("Gagal menyalin shortcode.", "error");
      }
    } catch (err) {
      console.error("Fallback copy failed:", err);
      showToast("Browser tidak mendukung fitur salin.", "error");
    }

    document.body.removeChild(textArea);
  }

  // Update select state for custom dropdowns
  function updateAbSelectState(sel) {
    try {
      var opt = sel && sel.options ? sel.options[sel.selectedIndex] : null;
      var txt = opt ? (opt.text || '') : '';
      if (sel) sel.setAttribute('title', txt);
      if (!sel || sel.value === '' || sel.value === null) {
        $(sel).addClass('is-placeholder');
      } else {
        $(sel).removeClass('is-placeholder');
      }
    } catch(e) {
      // Silently handle errors
    }
  }

  // Form field builder enhancements
  // Initialize validation for existing fields when page loads
  $(document).ready(function() {
    // Add validation to existing field rows (skip auto-detected fields)
    $('#form-fields-container .form-field-row').each(function() {
      var $row = $(this);
      // Only add validation to non-auto-detected fields
      if ($row.data('auto-detected') !== 'true') {
        addFieldValidationListeners($row);
      }
    });
  });

  // Remove any existing event handlers to prevent duplication
  $(document).off('click', '#add-field-btn');
  $(document).on('click', '#add-field-btn', function() {
    var $btn = $(this);
    var $container = $('#form-fields-container');
    var fieldIndex = $container.find('.form-field-row').length;

    // Add loading state
    $btn.prop('disabled', true);

    setTimeout(function() {
      var newFieldHtml = createFieldRowHtml(fieldIndex);
      $container.append(newFieldHtml);

      // Animate new field entry
      var $newRow = $container.find('.form-field-row').last();
      $newRow.hide().fadeIn(300);

      // Focus on label input for immediate typing
      var $labelInput = $newRow.find('input[name^="field_labels["]');
      var $keyInput = $newRow.find('input[name^="field_keys_input["]');
      $labelInput.focus();

      // Add validation event listeners
      addFieldValidationListeners($newRow);

      // Hint removed - no longer needed

      // Update select styling and enhance dropdown
      var $newSelect = $newRow.find('select.ab-select');

      // Update select state
      if ($newSelect.length > 0) {
        updateAbSelectState($newSelect[0]);

        // Initialize custom dropdown for the new select
        if (typeof enhanceAbDropdowns === 'function') {
          // Small delay to ensure DOM is ready
          setTimeout(function() {
            enhanceAbDropdowns($newSelect);
          }, 50);
        }
      }

      // Set up immediate detection for the new field
      $labelInput.on('input', function() {
        clearTimeout($labelInput.data('typing-timer'));
        $labelInput.data('typing-timer', setTimeout(function() {
          // Trigger auto-detection
          $labelInput.trigger('input');
        }, 200)); // Faster response for new fields
      });

      $btn.prop('disabled', false);
    }, 100);
  });

  // Add validation listeners to field inputs
  function addFieldValidationListeners($row) {
    var $keyInput = $row.find('.field-key-input');
    var $labelInput = $row.find('.field-label-input');

    // Fallback: find inputs by name pattern if class not found
    if ($keyInput.length === 0) {
      $keyInput = $row.find('input[name^="field_keys_input["]');
    }
    if ($labelInput.length === 0) {
      $labelInput = $row.find('input[name^="field_labels["]');
    }

    // Validate key on input
    $keyInput.on('input blur', function() {
      var key = $(this).val();
      var isValid = validateFieldKey(key, $row);

      if (!isValid) {
        $(this).addClass('error');
        $row.addClass('has-error');
      } else {
        $(this).removeClass('error');
        $row.removeClass('has-error');

        // Auto-fill label if key matches custom pattern
        autoFillLabelFromKey(key, $labelInput);
      }
    });

    // Validate label on input
    $labelInput.on('input blur', function() {
      var label = $(this).val();
      var isValid = validateFieldLabel(label, $row);

      if (!isValid) {
        $(this).addClass('error');
        $row.addClass('has-error');
      } else {
        $(this).removeClass('error');
        $row.removeClass('has-error');
      }
    });
  }

  // Validate all form fields before submission
  function validateFormFields() {
    var isValid = true;
    var $rows = $('#form-fields-container .form-field-row');

    $rows.each(function(index) {
      var $row = $(this);

      // Skip validation for auto-detected fields (they have predefined values)
      if ($row.data('auto-detected') === 'true') {
        return; // continue to next row
      }

      var $keyInput = $row.find('.field-key-input');
      var $labelInput = $row.find('.field-label-input');

      // Fallback: find inputs by name pattern if class not found
      if ($keyInput.length === 0) {
        $keyInput = $row.find('input[name^="field_keys_input["]');
      }
      if ($labelInput.length === 0) {
        $labelInput = $row.find('input[name^="field_labels["]');
      }

      var key = $keyInput.val() || '';
      var label = $labelInput.val() || '';

      var keyValid = validateFieldKey(key, $row);
      var labelValid = validateFieldLabel(label, $row);

      if (!keyValid) {
        $keyInput.addClass('error');
        $row.addClass('has-error');
        isValid = false;
      }

      if (!labelValid) {
        $labelInput.addClass('error');
        $row.addClass('has-error');
        isValid = false;
      }
    });
    return isValid;
  }

  // Helper function to create field row HTML
  function createFieldRowHtml(index) {
    var fieldNumber = index + 1; // Start from 1 instead of 0
    return '<tr class="form-field-row" data-field-index="' + index + '" data-auto-detected="false">' +
      '<td>' +
        '<input type="hidden" name="field_keys[]" value="custom_' + fieldNumber + '">' +
        '<input type="text" name="field_keys_input[custom_' + fieldNumber + ']" value="custom_' + fieldNumber + '" class="regular-text field-key-input" placeholder="contoh: nama_hewan">' +
      '</td>' +
      '<td>' +
        '<input type="text" name="field_labels[custom_' + fieldNumber + ']" value="Custom ' + fieldNumber + '" placeholder="Label field" class="field-label-input">' +
      '</td>' +
      '<td>' +
        '<select class="ab-select ab-dropdown field-type-select" name="field_types[custom_' + fieldNumber + ']">' +
          '<option value="text">Text</option>' +
          '<option value="email">Email</option>' +
          '<option value="number">Number</option>' +
          '<option value="date">Date</option>' +
          '<option value="time">Time</option>' +
          '<option value="select">Select</option>' +
          '<option value="textarea">Textarea</option>' +
          '<option value="file">File Upload</option>' +
        '</select>' +
      '</td>' +
      '<td class="col-required"><input type="checkbox" name="field_required[custom_' + fieldNumber + ']" value="1"></td>' +
      '<td><input type="text" name="field_placeholders[custom_' + fieldNumber + ']" value="" placeholder="Placeholder text"></td>' +
      '<td class="options-cell">' +
        '<textarea name="field_options[custom_' + fieldNumber + ']" rows="2" class="large-text field-options" placeholder="Satu nilai per baris" style="display:none;"></textarea>' +
      '</td>' +
      '<td class="col-actions"><button type="button" class="button remove-field" title="Hapus Field"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text">Hapus</span></button></td>' +
    '</tr>';
  }

  // Dynamic field type behavior
  $(document).on('change', '.field-type-select', function() {
    var $select = $(this);
    var $row = $select.closest('tr');
    var fieldType = $select.val();
    var $optionsCell = $row.find('.options-cell');

    // Show/hide options textarea based on field type
    if (fieldType === 'select') {
      $optionsCell.find('.field-options').slideDown(200);
    } else {
      $optionsCell.find('.field-options').slideUp(200);
    }
  });

  // Auto-detect field key based on label
  function autoDetectFieldKey(label) {
    var labelLower = label.toLowerCase();

    // Name detection patterns
    var namePatterns = [
      'nama lengkap', 'full name', 'complete name', 'customer name',
      'nama lengkap anda', 'your full name', 'nama customer', 'nama pelanggan',
      'nama pengunjung', 'visitor name', 'guest name', 'nama anda', 'your name',
      'nama pemesan', 'pemesan nama', 'nama pembeli', 'nama klien',
      'nama', 'name'
    ];

    // Email detection patterns
    var emailPatterns = [
      'email address', 'e-mail address', 'email anda', 'your email',
      'alamat email', 'email customer', 'email pelanggan',
      'email pemesan', 'email pembeli', 'email klien',
      'alamat email anda', 'your email address'
    ];

    // Check name patterns
    for (var i = 0; i < namePatterns.length; i++) {
      var pattern = namePatterns[i];
      if (pattern === 'nama' || pattern === 'name') {
        // Only exact match for single words
        if (labelLower === pattern) {
          return 'customer_name';
        }
      } else {
        // For phrases, use indexOf
        if (labelLower.indexOf(pattern) !== -1) {
          return 'customer_name';
        }
      }
    }

    // Check email patterns - single word "email" handled separately
    if (labelLower === 'email') {
      return 'customer_email';
    }

    for (var i = 0; i < emailPatterns.length; i++) {
      if (labelLower.indexOf(emailPatterns[i]) !== -1) {
        return 'customer_email';
      }
    }

    return null;
  }

  // Validate field key - ensure it's not empty
  function validateFieldKey(key, $row) {
    if (!key || key.trim() === '') {
      return false;
    }
    return true;
  }

  // Validate field label - ensure it's not empty
  function validateFieldLabel(label, $row) {
    if (!label || label.trim() === '') {
      return false;
    }
    return true;
  }

  // Auto-fill label based on custom key pattern
  function autoFillLabelFromKey(key, $labelInput) {
    var customMatch = key.match(/^custom_(\d+)$/);
    if (customMatch) {
      var number = customMatch[1];
      $labelInput.val('Custom ' + number);
    }
  }

  // Check if field key is already used
  function isFieldKeyUsed(key, excludeRow) {
    var used = false;
    $('#form-fields-container .form-field-row').not(excludeRow).each(function() {
      var $row = $(this);
      var $keyInput = $row.find('input[name^="field_keys_input["]');
      if ($keyInput.val() === key) {
        used = true;
        return false;
      }
    });
    return used;
  }

  // Handle label changes to auto-detect field keys
  $(document).on('input', 'input[name^="field_labels["]', function() {
    var $labelInput = $(this);
    var $row = $labelInput.closest('tr');
    var $keyInput = $row.find('input[name^="field_keys_input["]');
    var $hiddenKey = $row.find('input[name^="field_keys["]');
    var currentKey = $keyInput.val();
    var label = $labelInput.val().trim();

    // Allow auto-detect for all fields, including custom fields
    // This ensures that auto-detection works for new fields and existing fields

    // Simple detection - only match specific patterns
    var detectedKey = autoDetectFieldKey(label);
    var isCurrentlyAutoDetected = $row.data('auto-detected') === 'true';
    var currentKeyType = $row.data('auto-type');

    // Case 1: Label matches name/email pattern - lock it
    if (detectedKey && !isFieldKeyUsed(detectedKey, $row)) {
      // Update key to match detection
      $keyInput.val(detectedKey);
      $hiddenKey.val(detectedKey);
      $row.data('auto-detected', 'true');
      $row.data('auto-type', detectedKey === 'customer_name' ? 'name' : 'email');

      // Update UI to show it's auto-detected
      $keyInput.prop('readonly', true).addClass('auto-detected-key');
      $row.find('.remove-field').hide();
      $row.addClass('auto-detected-row');

      // Ensure required checkbox is always checked and disabled for customer_name and customer_email
      var $requiredCheckbox = $row.find('input[name^="field_required["]');
      $requiredCheckbox.prop('checked', true).prop('disabled', true);
      $row.find('.col-required').addClass('required-locked');

      // Show feedback
      showFieldDetectionFeedback($row, detectedKey === 'customer_name' ? 'nama' : 'email', true);
    }
    // Case 2: Label no longer matches - unlock it
    else if (isCurrentlyAutoDetected && !detectedKey) {
      // Keep the current key but unlock it
      $keyInput.prop('readonly', false).removeClass('auto-detected-key');
      $row.data('auto-detected', 'false');
      $row.removeData('auto-type');
      $row.find('.remove-field').show();
      $row.removeClass('auto-detected-row');

      // Re-enable required checkbox for non-auto-detected fields
      var $requiredCheckbox = $row.find('input[name^="field_required["]');
      $requiredCheckbox.prop('disabled', false);
      $row.find('.col-required').removeClass('required-locked');

      // Show feedback
      showFieldDetectionFeedback($row, 'custom', false);
    }
    // Case 3: Switching between name/email types
    else if (isCurrentlyAutoDetected && detectedKey && currentKeyType !== (detectedKey === 'customer_name' ? 'name' : 'email')) {
      // Update to new detection type
      $keyInput.val(detectedKey);
      $hiddenKey.val(detectedKey);
      $row.data('auto-type', detectedKey === 'customer_name' ? 'name' : 'email');

      // Ensure required checkbox is always checked and disabled for customer_name and customer_email
      var $requiredCheckbox = $row.find('input[name^="field_required["]');
      $requiredCheckbox.prop('checked', true).prop('disabled', true);
      $row.find('.col-required').addClass('required-locked');

      // Show feedback
      showFieldDetectionFeedback($row, detectedKey === 'customer_name' ? 'nama' : 'email', true);
    }
  });

  // Function to show detection feedback
  function showFieldDetectionFeedback($row, fieldType, isDetected, reason = '') {
    // Remove existing feedback
    $row.find('.detection-feedback').remove();

    if (isDetected) {
      var feedbackText = fieldType === 'nama' ? 'Field nama - key otomatis terkunci' : 'Field email - key otomatis terkunci';
      var $feedback = $('<div class="detection-feedback" style="color: #dc2626; margin-left: 4px; font-size: 12px; margin-top: 4px;">' + feedbackText + '</div>');
      $row.find('td:first').append($feedback);
    } else {
      var feedbackText = 'Field kustom - key dapat diubah';
      var feedbackColor = '#10b981';

      // Show specific reason if provided
      if (reason) {
        feedbackText = reason;
        feedbackColor = '#ffc107'; // Warning color
      }

      var $feedback = $('<div class="detection-feedback" style="color: ' + feedbackColor + '; font-size: 12px; margin-top: 4px; margin-left: 4px;">' + feedbackText + '</div>');
      $row.find('td:first').append($feedback);
    }
  }

  
  // Allow manual override for non-auto-detected fields with confirmation
  $(document).on('focus', 'input[name^="field_keys_input["]', function() {
    var $keyInput = $(this);
    var $row = $keyInput.closest('tr');

    // Only for non-auto-detected fields
    if ($row.data('auto-detected') !== 'true') {
      $keyInput.data('original-value', $keyInput.val());
    }
  });

  $(document).on('blur', 'input[name^="field_keys_input["]', function() {
    var $keyInput = $(this);
    var $row = $keyInput.closest('tr');
    var originalValue = $keyInput.data('original-value');

    // Only for non-auto-detected fields
    if ($row.data('auto-detected') !== 'true' && originalValue !== undefined) {
      var newValue = $keyInput.val();
      var $labelInput = $row.find('input[name^="field_labels["]');
      var label = $labelInput.val().trim();

      // Check if the new value conflicts with auto-detection
      var detectedKey = autoDetectFieldKey(label);

      if (detectedKey && newValue !== detectedKey) {
        // Warn user about potential conflict
        if (confirm('Field ini terdeteksi sebagai ' + (detectedKey === 'customer_name' ? 'nama' : 'email') + '. Mengubah key dapat mempengaruhi fungsi sistem. Lanjutkan?')) {
          // User confirmed, allow the change
          $keyInput.val(newValue);
          $row.find('input[name^="field_keys["]').val(newValue);
        } else {
          // User cancelled, restore original value
          $keyInput.val(originalValue);
        }
      }

      $keyInput.removeData('original-value');
    }
  });

  // Initialize auto-detection for existing fields on page load
  $(document).ready(function() {
    $('#form-fields-container .form-field-row').each(function() {
      var $row = $(this);
      var $labelInput = $row.find('input[name^="field_labels["]');
      var $keyInput = $row.find('input[name^="field_keys_input["]');
      var $hiddenKey = $row.find('input[name^="field_keys["]');
      var label = $labelInput.val().trim();
      var currentKey = $keyInput.val();

      // Enhanced initialization logic
      var detectedKey = autoDetectFieldKey(label);

      // Check if this field should be auto-detected
      if (detectedKey && (currentKey === detectedKey || currentKey === 'customer_name' || currentKey === 'customer_email')) {
        // Set as auto-detected
        $row.data('auto-detected', 'true');
        $row.data('auto-type', detectedKey === 'customer_name' ? 'name' : 'email');

        // Ensure key is correct
        $keyInput.val(detectedKey);
        $hiddenKey.val(detectedKey);

        // Update UI
        $keyInput.prop('readonly', true).addClass('auto-detected-key');
        $row.find('.remove-field').hide();
        $row.addClass('auto-detected-row');

        // Add data attributes for consistency
        $row.attr('data-auto-detected', 'true');
        $row.attr('data-auto-type', detectedKey === 'customer_name' ? 'name' : 'email');
      } else if (detectedKey && !isFieldKeyUsed(detectedKey, $row)) {
        // New field that should be auto-detected
        $keyInput.val(detectedKey);
        $hiddenKey.val(detectedKey);
        $row.data('auto-detected', 'true');
        $row.data('auto-type', detectedKey === 'customer_name' ? 'name' : 'email');

        // Update UI
        $keyInput.prop('readonly', true).addClass('auto-detected-key');
        $row.find('.remove-field').hide();
        $row.addClass('auto-detected-row');

        // Ensure required checkbox is always checked and disabled for customer_name and customer_email
        var $requiredCheckbox = $row.find('input[name^="field_required["]');
        $requiredCheckbox.prop('checked', true).prop('disabled', true);
        $row.find('.col-required').addClass('required-locked');

        // Add data attributes
        $row.attr('data-auto-detected', 'true');
        $row.attr('data-auto-type', detectedKey === 'customer_name' ? 'name' : 'email');

        // Show feedback
        showFieldDetectionFeedback($row, detectedKey === 'customer_name' ? 'nama' : 'email', true);
      }
    });

    // Add real-time validation for all label inputs
    $('input[name^="field_labels["]').on('keyup', function() {
      var $labelInput = $(this);
      var $row = $labelInput.closest('tr');

      // Trigger detection on every keystroke for immediate feedback
      clearTimeout($labelInput.data('typing-timer'));
      $labelInput.data('typing-timer', setTimeout(function() {
        $labelInput.trigger('input');
      }, 300)); // 300ms delay to avoid excessive triggering while typing
    });
  });

  // Remove field without confirmation
  $(document).on('click', '.remove-field', function(e) {
    e.preventDefault();
    var $row = $(this).closest('.form-field-row');

    $row.fadeOut(300, function() {
      $(this).remove();
    });
  });

  // Handler function for service deletion
  function handleServiceDelete($button, serviceId) {
    var $row = $button.closest('tr');

    // Show loading state
    $button.prop('disabled', true);
    $button.find('.dashicons').addClass('spin');

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'delete_service',
        service_id: serviceId,
        nonce: archeus_booking_ajax.nonce
      },
      success: function(response) {
        if (response.success) {
          // Clean up handled markers before removing the row
          $button.removeAttr('data-delete-handled');
          $button.removeAttr('data-delete-pending');

          // Show success message and redirect to empty form
          showToast(response.data.message, 'success');
          setTimeout(function() {
            window.location.href = window.location.pathname + '?page=archeus-booking-services';
          }, 1000);
        } else {
          showToast(response.data.message, 'error');
          $button.prop('disabled', false);
          $button.find('.dashicons').removeClass('spin');
          // Clean up markers on error
          $button.removeAttr('data-delete-handled');
          $button.removeAttr('data-delete-pending');
        }
      },
      error: function() {
        showToast('Gagal menghapus layanan.', 'error');
        $button.prop('disabled', false);
        $button.find('.dashicons').removeClass('spin');
        // Clean up markers on error
        $button.removeAttr('data-delete-handled');
        $button.removeAttr('data-delete-pending');
      }
    });
  }

  // Handler function for time slot deletion
  function handleTimeSlotDelete($button, slotId) {
    console.log('handleTimeSlotDelete called with:', {
      button: $button,
      slotId: slotId,
      row: $button.closest('tr')
    });

    var $row = $button.closest('tr');

    // Show loading state
    $button.prop('disabled', true);
    $button.find('.dashicons').addClass('spin');

    console.log('Sending AJAX request for time slot deletion');

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'delete_time_slot',
        slot_id: slotId,
        nonce: archeus_booking_ajax.nonce
      },
      success: function(response) {
        console.log('AJAX response received:', response);
        if (response.success) {
          console.log('Time slot deletion successful');
          // Clean up handled markers before removing the row
          $button.removeAttr('data-delete-handled');
          $button.removeAttr('data-delete-pending');

          // Show success message and redirect to empty form
          showToast(response.data.message, 'success');
          setTimeout(function() {
            window.location.href = window.location.pathname + '?page=archeus-booking-time-slots';
          }, 1000);
        } else {
          console.log('Time slot deletion failed:', response.data);
          showToast(response.data.message, 'error');
          $button.prop('disabled', false);
          $button.find('.dashicons').removeClass('spin');
          // Clean up markers on error
          $button.removeAttr('data-delete-handled');
          $button.removeAttr('data-delete-pending');
        }
      },
      error: function(xhr, status, error) {
        console.log('AJAX error occurred:', {
          xhr: xhr,
          status: status,
          error: error
        });
        showToast('Gagal menghapus slot waktu.', 'error');
        $button.prop('disabled', false);
        $button.find('.dashicons').removeClass('spin');
        // Clean up markers on error
        $button.removeAttr('data-delete-handled');
        $button.removeAttr('data-delete-pending');
      }
    });
  }

  // Handler function for flow deletion
  function handleFlowDelete($button, flowId) {
    var $row = $button.closest('tr');

    // Show loading state
    $button.prop('disabled', true);
    $button.find('.dashicons').addClass('spin');

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'delete_flow',
        flow_id: flowId,
        nonce: archeus_booking_ajax.nonce
      },
      success: function(response) {
        if (response.success) {
          // Clean up handled markers before removing the row
          $button.removeAttr('data-delete-handled');
          $button.removeAttr('data-delete-pending');

          // Show success message and redirect to empty form
          showToast(response.data.message, 'success');
          setTimeout(function() {
            window.location.href = window.location.pathname + '?page=archeus-booking-flow';
          }, 1000);
        } else {
          showToast(response.data.message, 'error');
          $button.prop('disabled', false);
          $button.find('.dashicons').removeClass('spin');
          // Clean up markers on error
          $button.removeAttr('data-delete-handled');
          $button.removeAttr('data-delete-pending');
        }
      },
      error: function() {
        showToast('Gagal menghapus booking flow.', 'error');
        $button.prop('disabled', false);
        $button.find('.dashicons').removeClass('spin');
        // Clean up markers on error
        $button.removeAttr('data-delete-handled');
        $button.removeAttr('data-delete-pending');
      }
    });
  }

  // Handler function for form deletion
  function handleFormDelete($button, formId) {
    var $row = $button.closest('tr');

    // Show loading state
    $button.prop('disabled', true);
    $button.find('.dashicons').addClass('spin');

    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: {
        action: 'delete_form',
        form_id: formId,
        nonce: archeus_booking_ajax.nonce
      },
      success: function(response) {
        if (response.success) {
          // Clean up handled markers before removing the row
          $button.removeAttr('data-delete-handled');
          $button.removeAttr('data-delete-pending');

          // Show success message
          showToast(response.data.message, 'success');

          // Reload the page to show empty form (redirect to same page without edit parameters)
          setTimeout(function() {
            window.location.href = window.location.pathname + '?page=archeus-booking-forms';
          }, 1000); // Wait 1 second for toast to show
        } else {
          showToast(response.data.message, 'error');
          $button.prop('disabled', false);
          $button.find('.dashicons').removeClass('spin');
          // Clean up markers on error
          $button.removeAttr('data-delete-handled');
          $button.removeAttr('data-delete-pending');
        }
      },
      error: function() {
        showToast('Gagal menghapus formulir.', 'error');
        $button.prop('disabled', false);
        $button.find('.dashicons').removeClass('spin');
        // Clean up markers on error
        $button.removeAttr('data-delete-handled');
        $button.removeAttr('data-delete-pending');
      }
    });
  }

  // Handle service deletion with event delegation
  $(document).on('click', '.delete-service', function(e) {
    e.preventDefault();
    e.stopPropagation(); // Prevent event bubbling

    var $button = $(this);
    var serviceId = $button.data('service-id');

    if (!serviceId) {
      // Fallback to onclick handler if data attribute not available
      return;
    }

    // Mark as handled to prevent other handlers
    $button.attr('data-delete-handled', 'true');

    // Mark this button as pending deletion so the dialog can find it
    $button.attr('data-delete-pending', 'true');

    // Call the custom delete confirmation dialog
    if (typeof showDeleteConfirm === "function") {
      showDeleteConfirm('Yakin ingin menghapus layanan ini?', 'service-delete');
    }
  });

  // Handle form deletion with event delegation
  $(document).on('click', '.delete-form', function(e) {
    e.preventDefault();
    e.stopPropagation(); // Prevent event bubbling

    var $button = $(this);
    var formId = $button.data('form-id');

    if (!formId) {
      return;
    }

    // Mark as handled to prevent other handlers
    $button.attr('data-delete-handled', 'true');

    // Mark this button as pending deletion so the dialog can find it
    $button.attr('data-delete-pending', 'true');

    // Call the custom delete confirmation dialog
    if (typeof showDeleteConfirm === "function") {
      showDeleteConfirm('Yakin ingin menghapus formulir ini?', 'form-delete');
    }
  });

  // Handle time slot deletion with event delegation
  $(document).on('click', '.delete-time-slot', function(e) {
    e.preventDefault();
    e.stopPropagation(); // Prevent event bubbling

    var $button = $(this);
    var slotId = $button.data('slot-id');

    console.log('Time slot delete clicked:', {
      button: $button,
      slotId: slotId,
      attributes: $button.attr('data-slot-id'),
      data: $button.data()
    });

    if (!slotId) {
      console.log('No slotId found, returning');
      // Fallback to onclick handler if data attribute not available
      return;
    }

    // Mark as handled to prevent other handlers
    $button.attr('data-delete-handled', 'true');

    // Mark this button as pending deletion so the dialog can find it
    $button.attr('data-delete-pending', 'true');

    console.log('Marked button as pending:', {
      'data-delete-handled': $button.attr('data-delete-handled'),
      'data-delete-pending': $button.attr('data-delete-pending')
    });

    // Call the custom delete confirmation dialog
    if (typeof showDeleteConfirm === "function") {
      console.log('Calling showDeleteConfirm for time slot');
      showDeleteConfirm('Yakin ingin menghapus slot waktu ini?', 'time-slot-delete');
    } else {
      console.log('showDeleteConfirm function not available');
    }
  });

  // Handle flow deletion with event delegation
  $(document).on('click', '.delete-flow', function(e) {
    e.preventDefault();
    e.stopPropagation(); // Prevent event bubbling

    var $button = $(this);
    var flowId = $button.data('flow-id');

    if (!flowId) {
      // Fallback to onclick handler if data attribute not available
      return;
    }

    // Mark as handled to prevent other handlers
    $button.attr('data-delete-handled', 'true');

    // Mark this button as pending deletion so the dialog can find it
    $button.attr('data-delete-pending', 'true');

    // Call the custom delete confirmation dialog
    if (typeof showDeleteConfirm === "function") {
      showDeleteConfirm('Yakin ingin menghapus booking flow ini?', 'flow-delete');
    }
  });

  // Debug: Check if functions are loaded
  console.log("Archeus Admin JS loaded - Functions available:", {
    showStatusChangeDialog: typeof window.showStatusChangeDialog,
    showDeleteConfirmationDialog: typeof window.showDeleteConfirmationDialog,
    showDeleteConfirm: typeof window.showDeleteConfirm,
    updateBookingStatus: typeof window.updateBookingStatus,
    jQuery: typeof jQuery,
  });

  // Debug: Check for dropdowns
  console.log('Looking for dropdown elements...');
  console.log('Found select.ab-dropdown:', $('select.ab-dropdown').length);
  console.log('Found #booking-status-filter:', $('#booking-status-filter').length);

  // Try to initialize dropdowns
  if (typeof enhanceAbDropdowns === 'function') {
    console.log('enhanceAbDropdowns function found, initializing...');
    enhanceAbDropdowns($(document));
  } else {
    console.log('enhanceAbDropdowns function NOT found!');
  }

  // Initial data is already loaded via PHP, no need for additional refresh
  console.log(
    "Initial data loaded, skipping automatic refresh to prevent conflicts with detail views"
  );

  // Handle service form submission via AJAX
  $(document).on('submit', '.service-form .settings-form', function(e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $form.find('input[name="save_service"]');
    var serviceId = $form.find('input[name="service_id"]').val();
    var isUpdate = serviceId && serviceId > 0;

    // Validate required fields
    var serviceName = $form.find('input[name="service_name"]').val().trim();
    if (!serviceName) {
      showToast('Nama layanan wajib diisi', 'error');
      return;
    }

    // Show loading state
    $submitBtn.prop('disabled', true);
    $submitBtn.addClass('loading');

    // Get checkbox status
    var $checkbox = $form.find('input[name="is_active"]');
    var isActiveValue = $checkbox.is(':checked') ? 1 : 0;

    // Prepare form data
    var formData = {
      action: isUpdate ? 'update_service' : 'create_service',
      nonce: archeus_booking_ajax.nonce,
      service_id: serviceId,
      service_name: serviceName,
      service_description: $form.find('textarea[name="service_description"]').val(),
      service_price: parseFloat($form.find('input[name="service_price"]').val()) || 0,
      service_duration: parseInt($form.find('input[name="service_duration"]').val()) || 30,
      is_active: isActiveValue
    };

    // Send AJAX request
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: formData,
      traditional: true, // Ensure array-like data is sent correctly
      success: function(response) {
        if (response.success) {
          // Show success toast
          showToast(response.data.message, 'success');

          // Redirect to empty form after creation/update
          setTimeout(function() {
            window.location.href = window.location.pathname + '?page=archeus-booking-services';
          }, 1500);
        } else {
          showToast(response.data.message, 'error');
          $submitBtn.prop('disabled', false);
          $submitBtn.removeClass('loading');
        }
      },
      error: function(xhr, status, error) {
        showToast('Terjadi kesalahan saat menyimpan layanan', 'error');
        $submitBtn.prop('disabled', false);
        $submitBtn.removeClass('loading');
      }
    });
  });

  // Handle booking form submission via AJAX
  $(document).on('submit', '.settings-form[data-ajax-form="true"]', function(e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $form.find('input[name="save_form"]');
    var $submitBtnTimeSlot = $form.find('input[name="save_time_slot"]');

    // Determine which form type this is
    if ($submitBtnTimeSlot.length) {
      // This is a time slot form, let it be handled by the time slot handler
      return;
    }

    if (!$submitBtn.length) {
      // No save button found, this might not be the right form
      return;
    }

    var formId = $form.find('input[name="form_id"]').val();
    var isUpdate = formId && formId > 0;

    // Validate required fields
    var $formNameField = $form.find('input[name="form_name"]');
    var formName = $formNameField.length ? $formNameField.val().trim() : '';
    if (!formName) {
      showToast('Nama formulir wajib diisi', 'error');
      return;
    }

    // Validate form fields
    if (!validateFormFields()) {
      showToast('Key dan Label field tidak boleh kosong', 'error');

      // Scroll to first error
      var $firstError = $('#form-fields-container .has-error').first();
      if ($firstError.length) {
        $('html, body').animate({
          scrollTop: $firstError.offset().top - 100
        }, 300);

        // Focus on first error input
        $firstError.find('input.error').first().focus();
      }

      return;
    }

    // Show loading state
    $submitBtn.prop('disabled', true);
    $submitBtn.addClass('loading');

    // Collect form data
    var formData = new FormData($form[0]);

    // Handle array fields manually for proper FormData serialization
    $form.find('input[name^="field_keys["]').each(function() {
      var name = $(this).attr('name');
      var value = $(this).val();
      formData.append(name, value);
    });

    $form.find('input[name^="field_keys_input["]').each(function() {
      var name = $(this).attr('name');
      var value = $(this).val();
      formData.append(name, value);
    });

    // Debug log untuk field collection
    console.log('Collecting field data...');

    $form.find('input[name^="field_labels["]').each(function() {
      var name = $(this).attr('name');
      var value = $(this).val();
      console.log('Field label:', name, '=', value);
      formData.append(name, value);
    });

    $form.find('select[name^="field_types["]').each(function() {
      var name = $(this).attr('name');
      var value = $(this).val();
      console.log('Field type:', name, '=', value);
      formData.append(name, value);
    });

    // Handle required checkboxes properly - only process once to avoid duplication
  console.log('Starting required checkbox processing...');
  $form.find('input[name^="field_keys["]').each(function() {
    var fieldKey = $(this).val();
    var checkboxName = 'field_required[' + fieldKey + ']';
    var $checkbox = $form.find('input[name="' + checkboxName + '"]');
    var isChecked = $checkbox.length > 0 ? $checkbox.is(':checked') : false;
    var value = isChecked ? '1' : '0';

    console.log('Required field processing:', {
      fieldKey: fieldKey,
      checkboxName: checkboxName,
      checkboxFound: $checkbox.length > 0,
      isChecked: isChecked,
      valueToSend: value,
      checkboxHTML: $checkbox.length > 0 ? $checkbox[0].outerHTML : 'NOT_FOUND'
    });

    formData.append(checkboxName, value);
  });

  // Log entire FormData for debugging
  console.log('FormData contents being sent:');
  for (var pair of formData.entries()) {
    console.log(pair[0] + ': ' + pair[1]);
  }

    $form.find('input[name^="field_placeholders["]').each(function() {
      var name = $(this).attr('name');
      var value = $(this).val();
      formData.append(name, value);
    });

    $form.find('textarea[name^="field_options["]').each(function() {
      var name = $(this).attr('name');
      var value = $(this).val();
      formData.append(name, value);
    });

    formData.append('action', isUpdate ? 'update_form' : 'create_form');
    formData.append('nonce', archeus_booking_ajax.nonce);

    // Also add the booking forms nonce
    var $bookingFormsNonce = $form.find('input[name="booking_forms_nonce"]');
    if ($bookingFormsNonce.length) {
      formData.append('booking_forms_nonce', $bookingFormsNonce.val());
      console.log('Added booking_forms_nonce:', $bookingFormsNonce.val());
    }

    // Debug: Log form submission
    console.log('Form submitted via AJAX', {
      action: isUpdate ? 'update_form' : 'create_form',
      formId: formId,
      isUpdate: isUpdate
    });

    // Send AJAX request
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        console.log('AJAX response:', response);
        if (response.success) {
          // Show success toast
          showToast(response.data.message, 'success');

          // For both create and update, redirect to clean form page
          setTimeout(function() {
            var baseUrl = window.location.href.split('?')[0];
            var newUrl = baseUrl + '?page=archeus-booking-forms&form_saved=true';
            window.location.href = newUrl;
          }, 1500);
        } else {
          showToast(response.data.message, 'error');
          $submitBtn.prop('disabled', false);
          $submitBtn.removeClass('loading');
        }
      },
      error: function(xhr, status, error) {
        showToast('Terjadi kesalahan saat menyimpan formulir', 'error');
        $submitBtn.prop('disabled', false);
        $submitBtn.removeClass('loading');
      }
    });
  });

  // Handle time slot form submission via AJAX
  $(document).on('submit', '.time-slots-page .settings-form[data-ajax-form="true"]', function(e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $form.find('input[name="save_time_slot"]');

    if (!$submitBtn.length) {
      // No time slot submit button found, this might not be the right form
      return;
    }

    var slotId = $form.find('input[name="slot_id"]').val();
    var isUpdate = slotId && slotId > 0;

    // Validate required fields
    var $timeLabelField = $form.find('input[name="time_label"]');
    var $startTimeField = $form.find('input[name="start_time"]');
    var $endTimeField = $form.find('input[name="end_time"]');

    var timeLabel = $timeLabelField.length ? $timeLabelField.val().trim() : '';
    var startTime = $startTimeField.length ? $startTimeField.val().trim() : '';
    var endTime = $endTimeField.length ? $endTimeField.val().trim() : '';

    if (!timeLabel || !startTime || !endTime) {
      showToast('Semua field wajib diisi', 'error');
      return;
    }

    // Show loading state
    $submitBtn.prop('disabled', true);
    $submitBtn.addClass('loading');

    // Check if archeus_booking_ajax is available
    if (typeof archeus_booking_ajax === 'undefined') {
      showToast('Error: AJAX configuration not loaded. Please refresh the page.', 'error');
      console.error('archeus_booking_ajax object not found');
      return;
    }

    // Get checkbox status
    var $checkbox = $form.find('input[name="is_active"]');
    var isActiveValue = $checkbox.length && $checkbox.is(':checked') ? 1 : 0;

    // Get max capacity with safe parsing
    var $maxCapacityField = $form.find('input[name="max_capacity"]');
    var maxCapacity = $maxCapacityField.length ? parseInt($maxCapacityField.val()) || 1 : 1;

    // Prepare form data
    var formData = {
      action: isUpdate ? 'update_time_slot' : 'create_time_slot',
      nonce: archeus_booking_ajax.nonce,
      slot_id: slotId,
      time_label: timeLabel,
      start_time: startTime,
      end_time: endTime,
      max_capacity: maxCapacity,
      is_active: isActiveValue
    };

    // Debug: Log form submission
    console.log('Time slot submitted via AJAX', {
      action: formData.action,
      slotId: slotId,
      isUpdate: isUpdate,
      formData: formData,
      ajaxurl: ajaxurl,
      nonce_available: typeof archeus_booking_ajax !== 'undefined'
    });

    // Send AJAX request
    $.ajax({
      url: ajaxurl,
      type: 'POST',
      data: formData,
      dataType: 'json', // Expect JSON response
      success: function(response) {
        console.log('Time slot AJAX response:', response);
        if (response.success) {
          // Show success toast
          showToast(response.data.message, 'success');

          // Redirect to empty form after creation/update
          setTimeout(function() {
            window.location.href = window.location.pathname + '?page=archeus-booking-time-slots';
          }, 1500);
        } else {
          // Show detailed error message
          var errorMessage = response.data.message;

          // Add debug info if available
          if (response.data.debug_info) {
            console.log('Debug info:', response.data.debug_info);
            errorMessage += ' (Check console for details)';
          }

          showToast(errorMessage, 'error');
          $submitBtn.prop('disabled', false);
          $submitBtn.removeClass('loading');
        }
      },
      error: function(xhr, status, error) {
        console.log('Time slot AJAX error:', error);
        showToast('Terjadi kesalahan saat menyimpan slot waktu', 'error');
        $submitBtn.prop('disabled', false);
        $submitBtn.removeClass('loading');
      }
    });
  });

  // Email Settings Toggle Switches
  $('.toggle-switch-simple input').on('change', function() {
    const $toggle = $(this);
    const $card = $toggle.closest('.admin-card');
    const $indicator = $card.find('.status-indicator-simple');
    const $statusText = $card.find('.admin-card-status').not($indicator);

    if ($toggle.is(':checked')) {
      $indicator.removeClass('inactive').addClass('active');
      $statusText.text('Enabled');
    } else {
      $indicator.removeClass('active').addClass('inactive');
      $statusText.text('Disabled');
    }
  });

  // Initialize auto-detected fields to ensure required checkbox is checked
  function initializeAutoDetectedFields() {
    console.log('initializeAutoDetectedFields called');
    var $autoRows = $('.form-field-row[data-auto-detected="true"]');
    console.log('Found auto-detected rows:', $autoRows.length);

    // Find all auto-detected field rows
    $autoRows.each(function() {
      var $row = $(this);
      var $keyInput = $row.find('.field-key-input');
      var $hiddenKey = $row.find('input[name^="field_keys["]');
      var $labelInput = $row.find('.field-label-input');
      var keyValue = $keyInput.val() || $hiddenKey.val() || '';
      var labelValue = $labelInput.val() || '';

      console.log('Processing row:', keyValue, labelValue);
      console.log('Key input found:', $keyInput.length, 'Hidden key found:', $hiddenKey.length);
      console.log('Row HTML:', $row.html());

      // Check if this is customer_name or customer_email field
      if (keyValue === 'customer_name' || keyValue === 'customer_email') {
        console.log('Found customer field, processing...');

        // Ensure required checkbox is always checked and disabled for customer_name and customer_email
        var $requiredCheckbox = $row.find('input[name^="field_required["]');
        console.log('Required checkbox found:', $requiredCheckbox.length);
        console.log('Required checkbox before:', $requiredCheckbox.prop('checked'), $requiredCheckbox.prop('disabled'));

        $requiredCheckbox.prop('checked', true).prop('disabled', true);
        console.log('Required checkbox after:', $requiredCheckbox.prop('checked'), $requiredCheckbox.prop('disabled'));

        $row.find('.col-required').addClass('required-locked');

        // Trigger auto detection feedback to show the field is locked
        showFieldDetectionFeedback($row, keyValue === 'customer_name' ? 'nama' : 'email', true);

        // Make key input readonly and add visual feedback
        $keyInput.prop('readonly', true).addClass('auto-detected-key');
        $row.find('.remove-field').hide();
        $row.addClass('auto-detected-row');

        console.log('Successfully initialized:', keyValue);
      }
    });
  }

  // Run initialization when page loads
  console.log('Page loaded, running initializeAutoDetectedFields...');
  initializeAutoDetectedFields();

  // Also initialize when new fields are added
  $(document).on('click', '#add-field-btn', function() {
    setTimeout(initializeAutoDetectedFields, 150);
  });
});
