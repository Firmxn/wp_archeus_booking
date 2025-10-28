/**
 * Booking Flow JavaScript
 * Handles the booking flow navigation and form submission
 */
jQuery(document).ready(function($) {
    // HTML Escaping Functions for Security
    function escapeHtml(text) {
        if (!text) return '';

        // Convert to string if not already
        text = String(text);

        // Create a div element to use DOM escaping
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Strict HTML escaping for all user input
    function escapeHtmlStrict(text) {
        if (!text) return '';

        // Convert to string if not already
        text = String(text);

        // Escape all HTML special characters
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
            .replace(/\//g, '&#x2F;');
    }

    // Sanitize string to prevent XSS
    function sanitizeString(str) {
        if (!str) return '';
        str = String(str);
        return escapeHtmlStrict(str.trim());
    }

    // Validate email format
    function isValidEmail(email) {
        if (!email) return false;
        email = sanitizeString(email);
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Validate phone number - must be 10-13 digits
    function isValidPhone(phone) {
        if (!phone) return false;
        phone = sanitizeString(phone);
        // Remove all non-digit characters (spaces, dashes, plus, parentheses, etc.)
        var digitsOnly = phone.replace(/[^\d]/g, '');
        // Check if the cleaned phone number has 10-13 digits
        var phoneRegex = /^\d{10,13}$/;
        return phoneRegex.test(digitsOnly);
    }

    // Validate NIK (Nomor Induk Kependudukan) - must be exactly 16 digits
    function isValidNIK(nik) {
        if (!nik) return false;
        nik = sanitizeString(nik);
        // Remove any spaces or dashes that might have been entered
        nik = nik.replace(/[\s\-]/g, '');
        var nikRegex = /^\d{16}$/;
        return nikRegex.test(nik);
    }

    // Initialize the booking flow
    initBookingFlow();

    // Initialize custom dropdowns
    initCustomDropdowns();

    function initBookingFlow() {
        // Ensure a default date (today) exists so time slots can appear without manual date
        let selectedDate = sessionStorage.getItem('archeus_selected_date');
        if (!selectedDate) {
            const d = new Date();
            const y = d.getFullYear();
            const m = ("0" + (d.getMonth() + 1)).slice(-2);
            const day = ("0" + d.getDate()).slice(-2);
            selectedDate = `${y}-${m}-${day}`;
            sessionStorage.setItem('archeus_selected_date', selectedDate);
        }
        // Indicate progression styling
        $('.next-section-btn').addClass('next-active');

        // Rehydrate form fields from sessionStorage on load
        rehydrateFormFields();

        // Handle next button clicks
        $('.next-section-btn').on('click', function(e) {
            e.preventDefault();
            
            var currentSection = $(this).closest('.section-content');
            var nextSectionNum = $(this).data('section');
            var nextSection = $('#section-' + nextSectionNum);
            
            // Validate current section if required
            var isValid = validateCurrentSection(currentSection);
            if (!isValid) {
                return false;
            }
            
            // Save current section data to sessionStorage
            saveSectionData(currentSection);
            
            // Hide current section and show next
            currentSection.removeClass('active');
            nextSection.addClass('active');
            
            // Update progress indicator
            $('.section-indicator').removeClass('active');
            $('.section-indicator[data-section="' + nextSectionNum + '"]').addClass('active');
        });
        
        // Handle previous button clicks
        $('.prev-section-btn').on('click', function(e) {
            e.preventDefault();
            
            var currentSection = $(this).closest('.section-content');
            var prevSectionNum = $(this).data('section');
            var prevSection = $('#section-' + prevSectionNum);
            
            // Hide current section and show previous
            currentSection.removeClass('active');
            prevSection.addClass('active');
            
            // Update progress indicator
            $('.section-indicator').removeClass('active');
            $('.section-indicator[data-section="' + prevSectionNum + '"]').addClass('active');
        });
        
        // Handle final submission (satu tombol di akhir)
        $('.submit-booking-btn').on('click', function(e) {
            e.preventDefault();
            console.log('Submit button clicked');

            // Additional validation: Check HTML5 form validity
            var $bookingForm = $('.booking-form-fields');
            if ($bookingForm.length > 0) {
              console.log('Checking HTML5 form validity...');

              // Log all fields and their required status
              $bookingForm.find('input, select, textarea').each(function() {
                var $field = $(this);
                console.log('Field:', $field.attr('name'), 'type:', $field.attr('type'), 'required:', $field.attr('required'), 'value:', $field.val());
              });

              // Create a temporary form element to validate all fields
              var $tempForm = $('<form>').append($bookingForm.find('input, select, textarea').clone());

              // Fix undefined type for enhanced dropdowns
              $tempForm.find('[type="undefined"]').attr('type', 'text');

              // Fix enhanced dropdown values in temp form
              $bookingForm.find('.ab-dd').each(function() {
                var $originalField = $(this);
                var $select = $originalField.find('select');
                var $tempField = $tempForm.find('[name="' + $select.attr('name') + '"]');

                if ($tempField.length > 0 && $select.val()) {
                  $tempField.val($select.val());
                }
              });

              var isValid = $tempForm[0].checkValidity();
              console.log('HTML5 validation result:', isValid);

              if (!isValid) {
                console.log('HTML5 validation failed, showing validation errors');

                // Add validation styling to actual fields
                $bookingForm.find('input, select, textarea').each(function() {
                  var $field = $(this);
                  var $tempField = $tempForm.find('[name="' + $field.attr('name') + '"]');

                  // Handle enhanced dropdown validation
                  if ($field.hasClass('ab-dd') || $field.closest('.ab-dd').length > 0) {
                    var $abDropdown = $field.hasClass('ab-dd') ? $field : $field.closest('.ab-dd');
                    var $select = $abDropdown.find('select');
                    var selectedValue = $select.val();
                    var hasValue = selectedValue && selectedValue !== '' && selectedValue !== null;

                    if (hasValue) {
                      // Valid - remove error classes
                      $abDropdown.removeClass('error');
                      $select.removeClass('error');
                      $field.removeClass('error');
                      return; // Skip this field - it's valid
                    } else {
                      // Invalid - add error classes
                      $abDropdown.addClass('error');
                      $select.addClass('error');
                      $field.addClass('error');
                      console.log('Enhanced dropdown failed validation:', $field.attr('name'), 'value:', selectedValue);
                      return;
                    }
                  }

                  // Handle regular field validation
                  if ($tempField.length > 0 && !$tempField[0].checkValidity()) {
                    // For enhanced dropdowns, add error to the container
                    if ($field.hasClass('ab-hidden-select')) {
                      $field.closest('.ab-dd').addClass('error');
                    }
                    $field.addClass('error');
                    console.log('Field failed validation:', $field.attr('name'), 'value:', $field.val());
                  } else {
                    // For enhanced dropdowns, remove error from container
                    if ($field.hasClass('ab-hidden-select')) {
                      $field.closest('.ab-dd').removeClass('error');
                    }
                    $field.removeClass('error');
                  }
                });

                // Show validation message and scroll to first error
                var $firstError = $bookingForm.find('.error').first();
                if ($firstError.length > 0) {
                  console.log('Scrolling to first error:', $firstError.attr('name'));
                  $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                  }, 500);
                }

                alert('Mohon lengkapi semua field yang diperlukan.');
                return false;
              }
            }

            // Validation: date, time slot, and service must be selected
            var selectedDate = sessionStorage.getItem('archeus_selected_date');
            var selectedTime = sessionStorage.getItem('archeus_selected_time_slot');
            var selectedService = sessionStorage.getItem('archeus_selected_service');

            console.log('Selected date:', selectedDate);
            console.log('Selected time:', selectedTime);
            console.log('Selected service:', selectedService);

            // Set default date if missing
            if (!selectedDate) {
                const d = new Date();
                const y = d.getFullYear();
                const m = ("0" + (d.getMonth() + 1)).slice(-2);
                const day = ("0" + d.getDate()).slice(-2);
                selectedDate = `${y}-${m}-${day}`;
                sessionStorage.setItem('archeus_selected_date', selectedDate);
            }

            // Validate time slot
            if (!selectedTime) {
                alert('Silakan pilih waktu terlebih dahulu.');
                return false;
            }

            // Validate service selection
            if (!selectedService) {
                alert('Silakan pilih layanan terlebih dahulu.');
                return false;
            }

            // Validate all required form fields - using direct validation approach
            // NOTE: Section-based validation (Approach 1) is disabled because it has issues with file field validation
            // Using direct validation (Approach 2) instead which works correctly for all field types including file inputs

            // Approach 2: Direct validation of all required fields in the entire form
            console.log('Performing direct validation of all required fields...');
            var allValid = true;
            var firstError = null;

            $('.booking-form-fields').find('input[required], select[required], textarea[required]').each(function() {
                var $field = $(this);
                var isEmpty = false;
                console.log('Direct validation - checking field:', $field.attr('name'), 'type:', $field.attr('type'), 'required:', $field.attr('required'));

                // Handle different field types
                if ($field.attr('type') === 'file') {
                    // For file inputs, check if any file is selected
                    isEmpty = !$field[0].files || $field[0].files.length === 0;
                    var $errorTarget = $field.closest('.file-upload');

                    console.log('=== DEBUG FILE FIELD ===');
                    console.log('File field element:', $field[0]);
                    console.log('File field jQuery object:', $field);
                    console.log('Looking for .file-upload container...');
                    console.log('Container found:', $errorTarget.length);
                    console.log('Container is:', $errorTarget[0]);
                    console.log('Field empty:', isEmpty);
                } else if ($field.is('select')) {
                    // For select, check if value is empty (including placeholder option)
                    // Handle both regular select and enhanced dropdown
                    var selectValue = $field.val();
                    var $dropdownContainer = $field.closest('.ab-dd');

                    // If this is an enhanced dropdown, also check the selected option text
                    if ($dropdownContainer.length > 0) {
                        var $selectedOption = $dropdownContainer.find('.ab-dd-item.is-selected');
                        var selectedText = $selectedOption.text() || '';
                        // Consider empty if value is empty or if it's the placeholder text
                        isEmpty = !selectValue || selectValue === '' || selectedText === '-- Pilih --' || selectedText.trim() === '';
                        // Add error class to the dropdown container for better styling
                        var $errorTarget = $dropdownContainer;
                    } else {
                        // Regular select
                        isEmpty = !selectValue || selectValue === '';
                        var $errorTarget = $field;
                    }
                    console.log('Direct validation - Select field:', $field.attr('name'), 'value:', selectValue, 'empty:', isEmpty, 'enhanced:', $dropdownContainer.length > 0);
                } else {
                    // For other inputs and textareas
                    isEmpty = !$field.val() || $field.val().trim() === '';
                    var $errorTarget = $field;
                }

                if (isEmpty) {
                    allValid = false;

                    // Test: Check if jQuery addClass works
                    console.log('=== TESTING jQuery addClass ===');
                    console.log('Before addClass:', $errorTarget.attr('class'));

                    $errorTarget.addClass('error');

                    // Force CSS style as backup
                    $errorTarget.css({
                        'border': '3px solid #ff0000 !important',
                        'background': '#ffeeee !important'
                    });

                    console.log('After addClass:', $errorTarget.attr('class'));
                    console.log('CSS applied:', $errorTarget.attr('style'));

                    // Verify with direct DOM manipulation
                    setTimeout(function() {
                        var domElement = $errorTarget[0];
                        console.log('DOM element classList:', domElement.classList);
                        console.log('DOM element computed style:', window.getComputedStyle(domElement).border);

                        // Fallback: Also add error class to container if input has error
                        if ($field.hasClass('error')) {
                            console.log('=== FALLBACK: Input has error class, adding to container ===');
                            $errorTarget.addClass('error');
                            $errorTarget.addClass('has-input-error');
                        }
                    }, 100);

                    // Store the first error element
                    if (firstError === null) {
                        firstError = $errorTarget;
                    }
                } else {
                    $errorTarget.removeClass('error');
                }
            });

            // Validate email fields in direct validation
            $('.booking-form-fields').find('input[type="email"]').each(function() {
                var $emailField = $(this);
                var email = $emailField.val();
                if (email && !isValidEmail(email)) {
                    allValid = false;
                    $emailField.addClass('error');
                    if (firstError === null) {
                        firstError = $emailField;
                    }
                } else {
                    $emailField.removeClass('error');
                }
            });

            // Validate NIK fields in direct validation (fields with name containing 'nik')
            $('.booking-form-fields').find('input[name*="nik"], input[name*="NIK"]').each(function() {
                var $nikField = $(this);
                var nik = $nikField.val();
                if (nik && !isValidNIK(nik)) {
                    allValid = false;
                    $nikField.addClass('error');
                    if (firstError === null) {
                        firstError = $nikField;
                    }
                    // Show specific error message for NIK
                    setTimeout(function() {
                        alert('NIK harus berjumlah tepat 16 digit.');
                    }, 100);
                } else {
                    $nikField.removeClass('error');
                }
            });

            // Validate phone fields in direct validation
            $('.booking-form-fields').find('input[type="tel"], input[name*="phone"], input[name*="telepon"], input[name*="hp"], input[name*="mobile"]').each(function() {
                var $phoneField = $(this);
                var phone = $phoneField.val();
                if (phone && !isValidPhone(phone)) {
                    allValid = false;
                    $phoneField.addClass('error');
                    if (firstError === null) {
                        firstError = $phoneField;
                    }
                    // Show specific error message for phone
                    setTimeout(function() {
                        alert('Nomor telepon harus 10-13 digit.');
                    }, 100);
                } else {
                    $phoneField.removeClass('error');
                }
            });

            console.log('Direct validation result:', allValid);

            if (!allValid) {
                console.log('Direct validation failed, stopping submission');
                alert('Mohon lengkapi semua field yang diperlukan.');
                if (firstError) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 500);
                }
                return false;
            }

            console.log('All validations passed, proceeding with submission');
            // Kumpulkan seluruh data dari halaman (form + pilihan)
            var bookingData = collectBookingData();
            console.log('Booking data collected:', bookingData);

            // Submit the booking data
            submitBooking(bookingData);
        });
    }

    // Populate form fields from previously saved data
    function rehydrateFormFields() {
        var raw = sessionStorage.getItem('archeus_form_data');
        if (!raw) return;
        var saved;
        try { saved = JSON.parse(raw) || {}; } catch(e) { return; }
        if (typeof saved !== 'object') return;

        $('.booking-form-fields').each(function(){
            var $scope = $(this);
            // Inputs and textareas
            $scope.find('input:not([type="file"]):not([type="checkbox"]):not([type="radio"]), textarea').each(function(){
                var name = $(this).attr('name');
                if (!name) return;
                if (saved.hasOwnProperty(name)) {
                    $(this).val(saved[name]);
                }
            });
            // Selects (single and multiple)
            $scope.find('select').each(function(){
                var name = $(this).attr('name');
                if (!name) return;
                if (saved.hasOwnProperty(name)) {
                    $(this).val(saved[name]).trigger('change');
                }
            });
            // Checkboxes
            $scope.find('input[type="checkbox"]').each(function(){
                var name = $(this).attr('name');
                var val = $(this).val();
                if (!name) return;
                if (!saved.hasOwnProperty(name)) return;
                if (Array.isArray(saved[name])) {
                    $(this).prop('checked', saved[name].indexOf(val) !== -1);
                } else {
                    $(this).prop('checked', saved[name] == val);
                }
            });
            // Radios
            $scope.find('input[type="radio"]').each(function(){
                var name = $(this).attr('name');
                var val = $(this).val();
                if (!name) return;
                if (saved.hasOwnProperty(name)) {
                    $(this).prop('checked', saved[name] == val);
                }
            });
        });
    }
    
    function validateCurrentSection(sectionElement) {
        // Get the section type
        var sectionType = sectionElement.data('type');
        
        // Validate based on section type
        switch(sectionType) {
            case 'calendar':
                // Auto-select today's date if none chosen
                var selectedDate = sessionStorage.getItem('archeus_selected_date');
                if (selectedDate === null) {
                    var d = new Date();
                    var y = d.getFullYear();
                    var m = ("0" + (d.getMonth() + 1)).slice(-2);
                    var day = ("0" + d.getDate()).slice(-2);
                    sessionStorage.setItem('archeus_selected_date', y + '-' + m + '-' + day);
                }
                return true;
            
            case 'time_slot':
                // Check if a time slot is selected
                var timeSlotSelected = sectionElement.find('input[name="time_slot"]:checked').length > 0;
                if (!timeSlotSelected) {
                    alert('Silakan pilih waktu terlebih dahulu.');
                    return false;
                }
                return true;
                
            case 'form':
                // Enhanced form validation
                console.log('Validating form section...');
                var valid = true;
                var firstError = null;
                var requiredFields = sectionElement.find('input[required], select[required], textarea[required]');
                var fileFields = sectionElement.find('input[type="file"][required]');
                console.log('=== Form Validation Debug ===');
                console.log('Found required fields:', requiredFields.length);
                console.log('Found required file fields:', fileFields.length);
                requiredFields.each(function() {
                    var $field = $(this);
                    console.log('Required field:', {
                        name: $field.attr('name'),
                        type: $field.attr('type'),
                        required: $field.attr('required'),
                        value: $field.val(),
                        tagName: this.tagName
                    });
                });

                requiredFields.each(function() {
                    var $field = $(this);
                    var isEmpty = false;
                    console.log('Checking field:', $field.attr('name'), $field.attr('type'));

                    // Handle different field types
                    if ($field.attr('type') === 'file') {
                        // For file inputs, check if any file is selected
                        isEmpty = !$field[0].files || $field[0].files.length === 0;
                        console.log('File field empty:', isEmpty);
                        console.log('File field name:', $field.attr('name'));
                        console.log('File field files:', $field[0].files);
                        console.log('File field files length:', $field[0].files ? $field[0].files.length : 'undefined');

                        // For file inputs, add error class to the parent .file-upload container
                        var $errorTarget = $field.closest('.file-upload');
                        console.log('File upload container found:', $errorTarget.length);
                        console.log('Container before error:', $errorTarget.attr('class'));

                        // Additional file validation - only if file is selected
                        if (!isEmpty) {
                            var file = $field[0].files[0];
                            var maxSize = 5 * 1024 * 1024; // 5MB
                            var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
                            var allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

                            // Check file size
                            if (file.size > maxSize) {
                                valid = false;
                                alert('Ukuran file terlalu besar. Maksimal 5MB.');
                                $errorTarget.addClass('error');
                                return false;
                            }

                            // Check file type
                            if (!allowedTypes.includes(file.type)) {
                                valid = false;
                                alert('Tipe file tidak diizinkan. Hanya JPG, PNG, GIF, dan PDF.');
                                $errorTarget.addClass('error');
                                return false;
                            }

                            // Check file extension
                            var fileExtension = file.name.split('.').pop().toLowerCase();
                            if (!allowedExtensions.includes(fileExtension)) {
                                valid = false;
                                alert('Ekstensi file tidak diizinkan. Hanya JPG, PNG, GIF, dan PDF.');
                                $errorTarget.addClass('error');
                                return false;
                            }
                        }
                    } else if ($field.is('select')) {
                        // For select, check if value is empty (including placeholder option)
                        // Handle both regular select and enhanced dropdown
                        var selectValue = $field.val();
                        var $dropdownContainer = $field.closest('.ab-dd');

                        // If this is an enhanced dropdown, also check the selected option text
                        if ($dropdownContainer.length > 0) {
                            var $selectedOption = $dropdownContainer.find('.ab-dd-item.is-selected');
                            var selectedText = $selectedOption.text() || '';
                            // Consider empty if value is empty or if it's the placeholder text
                            isEmpty = !selectValue || selectValue === '' || selectedText === '-- Pilih --' || selectedText.trim() === '';
                            // Add error class to the dropdown container for better styling
                            var $errorTarget = $dropdownContainer;
                        } else {
                            // Regular select
                            isEmpty = !selectValue || selectValue === '';
                            var $errorTarget = $field;
                        }
                        console.log('Select field value:', selectValue, 'empty:', isEmpty, 'container:', $dropdownContainer.length > 0);
                    } else {
                        // For other inputs and textareas
                        isEmpty = !$field.val() || $field.val().trim() === '';
                        console.log('Text field value:', $field.val(), 'empty:', isEmpty);
                        var $errorTarget = $field;
                    }

                    if (isEmpty) {
                        valid = false;
                        $errorTarget.addClass('error');
                        console.log('Added error class to:', $errorTarget);
                        console.log('Container after error:', $errorTarget.attr('class'));
                        if ($field.attr('type') === 'file') {
                            console.log('File field is empty, validation failed');
                        }

                        // Store the first error element
                        if (firstError === null) {
                            firstError = $errorTarget;
                        }
                    } else {
                        $errorTarget.removeClass('error');
                    }
                });

                // Validate email fields if they exist
                sectionElement.find('input[type="email"]').each(function() {
                    var $emailField = $(this);
                    var email = $emailField.val();
                    console.log('Email field value:', email, 'valid:', isValidEmail(email));
                    if (email && !isValidEmail(email)) {
                        valid = false;
                        $emailField.addClass('error');

                        if (firstError === null) {
                            firstError = $emailField;
                        }
                    } else {
                        $emailField.removeClass('error');
                    }
                });

                // Validate NIK fields if they exist (fields with name containing 'nik')
                sectionElement.find('input[name*="nik"], input[name*="NIK"]').each(function() {
                    var $nikField = $(this);
                    var nik = $nikField.val();
                    console.log('NIK field value:', nik, 'valid:', isValidNIK(nik));
                    if (nik && !isValidNIK(nik)) {
                        valid = false;
                        $nikField.addClass('error');

                        if (firstError === null) {
                            firstError = $nikField;
                        }
                        // Show specific error message for NIK
                        setTimeout(function() {
                            alert('NIK harus berjumlah tepat 16 digit.');
                        }, 100);
                    } else {
                        $nikField.removeClass('error');
                    }
                });

                // Validate phone fields if they exist
                sectionElement.find('input[type="tel"], input[name*="phone"], input[name*="telepon"], input[name*="hp"], input[name*="mobile"]').each(function() {
                    var $phoneField = $(this);
                    var phone = $phoneField.val();
                    console.log('Phone field value:', phone, 'valid:', isValidPhone(phone));
                    if (phone && !isValidPhone(phone)) {
                        valid = false;
                        $phoneField.addClass('error');

                        if (firstError === null) {
                            firstError = $phoneField;
                        }
                        // Show specific error message for phone
                        setTimeout(function() {
                            alert('Nomor telepon harus 10-13 digit.');
                        }, 100);
                    } else {
                        $phoneField.removeClass('error');
                    }
                });

                console.log('Form validation result:', valid);
                console.log('First error element:', firstError ? firstError.attr('name') : 'none');
                
                // Scroll to the first error if any
                if (!valid && firstError !== null) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 500);
                    alert('Mohon lengkapi semua field yang diperlukan.');
                }
                
                return valid;
                
            case 'services':
                // Check if a service is selected
                var serviceSelected = sectionElement.find('input[name="service_type"]:checked').length > 0;
                if (!serviceSelected) {
                    alert('Silakan pilih layanan terlebih dahulu.');
                    return false;
                }
                return true;
                
            default:
                return true;
        }
    }
    
    
    function saveSectionData(sectionElement) {
        // Get the section type
        var sectionType = sectionElement.data('type');
        
        // Save data based on section type
        switch(sectionType) {
            case 'calendar':
                // Calendar data is already saved by the calendar.js click handler
                break;
                
            case 'time_slot':
                var selectedTimeSlot = sectionElement.find('input[name="time_slot"]:checked').val();
                if (selectedTimeSlot) {
                    sessionStorage.setItem('archeus_selected_time_slot', selectedTimeSlot);
                }
                break;
                
            case 'form':
                // Save form data
                var formData = {};
                sectionElement.find('input:not([type="file"]), select, textarea').each(function() {
                    var name = $(this).attr('name');
                    var value = $(this).val();
                    
                    // Handle checkboxes and radio buttons
                    if ($(this).attr('type') === 'checkbox' || $(this).attr('type') === 'radio') {
                        if ($(this).is(':checked')) {
                            formData[name] = value;
                        }
                    } else if (name) {
                        formData[name] = value;
                    }
                });
                
                // Handle multiple select fields
                sectionElement.find('select[multiple]').each(function() {
                    var name = $(this).attr('name');
                    var values = $(this).val();
                    if (name && values) {
                        formData[name] = values;
                    }
                });
                
                sessionStorage.setItem('archeus_form_data', JSON.stringify(formData));
                break;
                
            case 'services':
                // Save selected service
                var selectedService = sectionElement.find('input[name="service_type"]:checked').val();
                if (selectedService) {
                    sessionStorage.setItem('archeus_selected_service', selectedService);
                }
                break;
        }
    }
    
    function collectBookingData() {
        var selectedDate = sessionStorage.getItem('archeus_selected_date') || '';
        var selectedTime = sessionStorage.getItem('archeus_selected_time_slot') || '';
        var selectedService = sessionStorage.getItem('archeus_selected_service') || '';
        var formFields = collectFormFields();
        if (!selectedService && formFields['service_type']) { selectedService = formFields['service_type']; }

        // Gabungkan tanggal + jam jika perlu
        var combinedTimeSlot = selectedTime;
        if (selectedDate && selectedTime && selectedTime.indexOf(' ') === -1) {
            combinedTimeSlot = selectedDate + ' ' + selectedTime;
        }

        // Susun struktur sesuai yang diharapkan server (array bertingkat per section)
        var payload = {
            section_calendar: { booking_date: selectedDate },
            section_time_slot: { time_slot: combinedTimeSlot },
            section_service: { service_type: selectedService },
            section_form: formFields
        };
        return payload;
    }

    // Custom file upload handling (booking flow forms)
    $(document).on('change input', '.bf-file-input', function(){
        var fileName = 'Belum ada file';
        if (this.files && this.files.length) {
            fileName = this.files.length > 1 ? (this.files.length + ' file dipilih') : this.files[0].name;
        } else {
            // Fallback for browsers without File API: use the input value
            var val = (this.value || '').split('\\').pop();
            if (val) fileName = val;
        }
        var $wrap = $(this).closest('.file-upload');
        $wrap.find('.bf-file-name').text(fileName);
        $wrap.toggleClass('has-file', fileName && fileName !== 'Belum ada file');
    });

    // Clear selected file
    $(document).on('click', '.bf-file-clear', function(e){
        e.preventDefault();
        e.stopPropagation(); // Mencegah event bubbling ke container

        // Cegah event mencapai label atau file input
        e.stopImmediatePropagation();

        var $wrap = $(this).closest('.file-upload');
        var $input = $wrap.find('.bf-file-input');
        if ($input.length) {
            // Clear the file input value
            $input.val('');
            // Update UI
            $wrap.removeClass('has-file');
            $wrap.find('.bf-file-name').text('Belum ada file');
            // Trigger change event to ensure consistency
            $input.trigger('change');
        }
        return false; // Mencegah default behavior dan propagasi lebih lanjut
    });

    // Prevent file input from triggering when clicking on clear button
    $(document).on('mousedown', '.bf-file-clear', function(e){
        e.preventDefault();
        e.stopPropagation();
        return false;
    });

    function collectFormFields() {
        var data = {};
        console.log('Collecting form fields...');

        $('.booking-form-fields').find('input:not([type="file"]), select, textarea').each(function() {
            var name = $(this).attr('name');
            if (!name) {
                console.log('Field without name attribute:', this);
                return;
            }

            var value = $(this).val();

            // Handle different field types
            if ($(this).attr('type') === 'checkbox') {
                if ($(this).is(':checked')) {
                    data[name] = sanitizeString(value);
                    console.log('Checkbox collected:', name, '=', sanitizeString(value));
                }
            } else if ($(this).attr('type') === 'radio') {
                if ($(this).is(':checked')) {
                    data[name] = sanitizeString(value);
                    console.log('Radio collected:', name, '=', sanitizeString(value));
                }
            } else if ($(this).attr('type') === 'email') {
                // Additional email validation and sanitization
                if (isValidEmail(value)) {
                    data[name] = sanitizeString(value);
                } else {
                    console.log('Invalid email format for:', name, '=', value);
                }
            } else if ($(this).attr('type') === 'tel') {
                // Phone number validation
                if (isValidPhone(value)) {
                    data[name] = sanitizeString(value);
                } else {
                    console.log('Invalid phone format for:', name, '=', value);
                }
            } else if ($(this).is('textarea')) {
                // For textareas, allow more characters but still escape HTML
                data[name] = sanitizeString(value);
            } else {
                data[name] = sanitizeString(value);
            }
        });

        // Multiple selects - sanitize each value
        $('.booking-form-fields').find('select[multiple]').each(function(){
            var name = $(this).attr('name');
            var values = $(this).val();
            if (name && values) {
                data[name] = Array.isArray(values) ? values.map(function(val) { return sanitizeString(val); }) : sanitizeString(values);
                console.log('Multiple select collected:', name, '=', data[name]);
            }
        });

        console.log('Final collected form data:', data);
        return data;
    }
    
    function submitBooking(bookingData) {
        // Get the flow ID
        var flowId = $('.booking-flow-container').data('flow-id');
        
        // Prepare FormData for AJAX (includes files)
        var fd = new FormData();
        fd.append('action', 'submit_booking_flow');
        fd.append('flow_id', flowId);
        fd.append('nonce', booking_flow_ajax.nonce);
        // Send form_data as JSON
        try { fd.append('form_data', JSON.stringify(bookingData)); } catch(e) { fd.append('form_data', ''); }
        // Append file inputs
        $('.booking-form-fields').find('input[type="file"]').each(function(){
            var name = $(this).attr('name');
            if (!name) return;
            if (this.files && this.files.length) {
                fd.append(name, this.files[0]);
            }
        });
        
        // Show loading indicator
        $('.booking-flow-container').append('<div class="loading-overlay"><div class="loading-spinner"></div></div>');
        
        // Submit via AJAX (multipart) with manual JSON parsing
        $.ajax({
            url: booking_flow_ajax.ajax_url,
            type: 'POST',
            dataType: 'text',
            data: fd,
            processData: false,
            contentType: false,
            success: function(resp) {
                // Hide loading indicator
                $('.loading-overlay').remove();

                try {
                    if (typeof resp === 'string') {
                        var firstBrace = resp.indexOf('{');
                        if (firstBrace > 0) resp = resp.slice(firstBrace);
                        resp = JSON.parse(resp);
                    }
                } catch (e) {
                    console.error('Booking flow JSON parse error', e, resp);
                    alert('Terjadi kesalahan pada respons server. Coba lagi.');
                    return;
                }

                if (resp && resp.success) {
                    // Clear session storage
                    sessionStorage.removeItem('archeus_selected_date');
                    sessionStorage.removeItem('archeus_selected_time_slot');
                    sessionStorage.removeItem('archeus_form_data');
                    sessionStorage.removeItem('archeus_selected_service');

                    // Show success message
                    var msg = (resp.data && resp.data.message) ? resp.data.message : 'Reservasi berhasil dikirim. Silakan cek email untuk konfirmasi.';
                    $('.booking-flow-container').html('<div class="success-message">' + msg + '</div>');
                } else {
                    var err = (resp && resp.data && resp.data.message) ? resp.data.message : 'Gagal mengirim booking.';
                    alert(err);
                }
            },
            error: function(xhr, status, err) {
                // Hide loading indicator
                $('.loading-overlay').remove();

                // Tolerant handling: some setups return 200 HTML or route through layers
                try {
                    var txt = xhr && xhr.responseText ? xhr.responseText : '';
                    if (txt) {
                        var firstBrace = txt.indexOf('{');
                        if (firstBrace >= 0) { txt = txt.slice(firstBrace); }
                        var parsed = JSON.parse(txt);
                        if (parsed && parsed.success) {
                            // Treat as success
                            sessionStorage.removeItem('archeus_selected_date');
                            sessionStorage.removeItem('archeus_selected_time_slot');
                            sessionStorage.removeItem('archeus_form_data');
                            sessionStorage.removeItem('archeus_selected_service');
                            var msg = (parsed.data && parsed.data.message) ? parsed.data.message : 'Reservasi berhasil dikirim. Silakan cek email untuk konfirmasi.';
                            $('.booking-flow-container').html('<div class="success-message">' + msg + '</div>');
                            return;
                        }
                    }
                } catch (e) {
                    // fallthrough to generic error
                }

                // Fallback: if HTTP status indicates success, treat as success to avoid false negatives
                if (xhr && xhr.status && xhr.status >= 200 && xhr.status < 400) {
                    sessionStorage.removeItem('archeus_selected_date');
                    sessionStorage.removeItem('archeus_selected_time_slot');
                    sessionStorage.removeItem('archeus_form_data');
                    sessionStorage.removeItem('archeus_selected_service');
                    $('.booking-flow-container').html('<div class="success-message">Reservasi Anda berhasil dikirim. Silakan cek email; kami akan mengabarkan hasil reservasi melalui email.</div>');
                    return;
                }

                console.error('Network/server error submitting booking', status, err, xhr && xhr.responseText);
                alert('Network error. Please try again.');
            }
        });
    }

    // Initialize Custom Dropdowns (same as admin)
    function initCustomDropdowns() {
        // Update select state
        function updateSelectState(sel) {
            try {
                var opt = sel && sel.options ? sel.options[sel.selectedIndex] : null;
                var txt = opt ? (opt.text || '') : '';
                if (sel) sel.setAttribute('title', txt);
                if (!sel || sel.value === '' || sel.value === null) {
                    $(sel).addClass('is-placeholder');
                } else {
                    $(sel).removeClass('is-placeholder');
                }
            } catch(e) {}
        }

        // Initialize all selects
        $('.booking-flow-container select.ab-select').each(function() {
            updateSelectState(this);
        });

        // Handle change events
        $(document).on('change', '.booking-flow-container select.ab-select', function() {
            updateSelectState(this);
        });

        // Enhanced dropdown for .ab-dropdown
        function enhanceDropdowns(root) {
            var $root = root && root.jquery ? root : $('.booking-flow-container');
            $root.find('select.ab-dropdown').each(function() {
                var $sel = $(this);
                if ($sel.data('ab-dd')) return; // already enhanced
                $sel.data('ab-dd', true);

                var selectedText = $sel.find('option:selected').text() || '';
                var safeSelectedText = sanitizeString(selectedText);
                var $wrap = $('<div class="ab-dd"></div>');
                var $btn = $('<button type="button" class="ab-dd-toggle" aria-haspopup="listbox" aria-expanded="false"></button>');
                var $label = $('<span class="ab-dd-label"></span>').text(safeSelectedText);
                var $caret = $('<span class="ab-dd-caret" aria-hidden="true"></span>');
                $btn.append($label).append($caret);
                var $menu = $('<div class="ab-dd-menu" role="listbox"></div>');

                $sel.find('option').each(function() {
                    var $opt = $(this);
                    var safeText = sanitizeString($opt.text());
                    var safeValue = sanitizeString($opt.attr('value'));
                    var $item = $('<div class="ab-dd-item" role="option" tabindex="-1"></div>').text(safeText);
                    $item.attr('data-value', safeValue);
                    if ($opt.is(':selected')) $item.addClass('is-selected');
                    $menu.append($item);
                });
                // Fix type attribute for validation
                $sel.attr('type', 'select');
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
                    var safeText = sanitizeString($(this).text());
                    $label.text(safeText);

                    // Remove error styling when value is selected
                    if (val && val !== '') {
                      $wrap.removeClass('error');
                      $sel.removeClass('error');
                    }

                    closeMenu();
                });

                $sel.on('change', function() {
                    var txt = $sel.find('option:selected').text() || '';
                    var val = $sel.val();
                    var safeText = sanitizeString(txt);
                    $label.text(safeText);
                    $menu.find('.ab-dd-item').each(function() {
                        var $i = $(this);
                        $i.toggleClass('is-selected', $i.attr('data-value') == val);
                    });

                    // Remove error styling when value is selected
                    if (val && val !== '') {
                      $wrap.removeClass('error');
                      $sel.removeClass('error');
                    }
                });
            });
        }

        // Initialize enhanced dropdowns
        enhanceDropdowns();
    }
});
