/**
 * Email Tags Functionality
 */

(function() {
    'use strict';

    /**
     * Copy tag to clipboard
     */
    window.copyTagToClipboard = function(tagText, buttonElement) {
        // Modern clipboard API
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(tagText).then(() => {
                showButtonFeedback(buttonElement, true);
            }).catch(err => {
                // Fallback to legacy method
                legacyCopyToClipboard(tagText, buttonElement);
            });
        } else {
            // Fallback for older browsers
            legacyCopyToClipboard(tagText, buttonElement);
        }
    };

    /**
     * Legacy copy to clipboard method
     */
    function legacyCopyToClipboard(text, buttonElement) {
        // Create temporary textarea
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        textarea.style.pointerEvents = 'none';
        document.body.appendChild(textarea);
        
        // Select and copy
        textarea.select();
        textarea.setSelectionRange(0, 99999); // For mobile devices
        
        try {
            const successful = document.execCommand('copy');
            showButtonFeedback(buttonElement, successful);
        } catch (err) {
            showButtonFeedback(buttonElement, false);
            console.error('Copy error:', err);
        }
        
        // Remove temporary element
        document.body.removeChild(textarea);
    }

    /**
     * Show visual feedback on copy button
     */
    function showButtonFeedback(buttonElement, success) {
        if (!buttonElement) return;
        
        // Add copied class
        buttonElement.classList.add('copied');
        
        // Change icon to checkmark
        const icon = buttonElement.querySelector('.dashicons');
        if (icon && success) {
            const originalClass = icon.className;
            icon.classList.remove('dashicons-clipboard');
            icon.classList.add('dashicons-yes');
            
            // Restore original state after delay
            setTimeout(() => {
                buttonElement.classList.remove('copied');
                icon.className = originalClass;
            }, 1500);
        } else {
            // Remove copied class if failed
            setTimeout(() => {
                buttonElement.classList.remove('copied');
            }, 1500);
        }
    }

    /**
     * Initialize email tags functionality
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Add keyboard accessibility for copy buttons
        const copyButtons = document.querySelectorAll('.copy-tag-btn');
        copyButtons.forEach(button => {
            button.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
        
        // Add hover effect descriptions
        copyButtons.forEach(button => {
            button.setAttribute('aria-label', 'Copy tag to clipboard');
        });
    });

})(); // End IIFE
