/**
 * Ultimate Addons for Elementor Forms - Turnstile Integration JavaScript
 * 
 * @package Simple Cloudflare Turnstile
 * @since 1.34.0
 */

(function($) {
    'use strict';

    // UAE Forms Turnstile Integration
    var UAETurnstile = {
        
        init: function() {
            this.handleFormSubmissions();
            this.handleAjaxForms();
            this.handleFormResets();
        },

        /**
         * Handle regular form submissions
         */
        handleFormSubmissions: function() {
            // Handle UAE login form submissions
            $('body').on('submit', 'form[class*="uael-login-form"]', function(e) {
                var form = $(this);
                var turnstileWidget = form.find('.cf-turnstile');
                
                if (turnstileWidget.length > 0) {
                    var response = turnstileWidget.find('input[name="cf-turnstile-response"]').val();
                    if (!response || response === '') {
                        e.preventDefault();
                        alert('Please complete the security verification.');
                        return false;
                    }
                }
            });

            // Handle UAE registration form submissions
            $('body').on('submit', 'form[class*="uael-registration-form"]', function(e) {
                var form = $(this);
                var turnstileWidget = form.find('.cf-turnstile');
                
                if (turnstileWidget.length > 0) {
                    var response = turnstileWidget.find('input[name="cf-turnstile-response"]').val();
                    if (!response || response === '') {
                        e.preventDefault();
                        alert('Please complete the security verification.');
                        return false;
                    }
                }
            });
        },

        /**
         * Handle AJAX form submissions
         */
        handleAjaxForms: function() {
            // Listen for UAE AJAX form responses
            $(document).ajaxComplete(function(event, xhr, settings) {
                // Check if this is a UAE form submission
                if (settings.data && (settings.data.indexOf('uael_login_form_submit') > -1 || 
                    settings.data.indexOf('uael_register_user') > -1)) {
                    
                    try {
                        var response = JSON.parse(xhr.responseText);
                        
                        // If form submission failed, reset Turnstile widgets
                        if (!response.success) {
                            UAETurnstile.resetTurnstileWidgets();
                        }
                    } catch (e) {
                        // If response parsing failed, reset widgets as precaution
                        UAETurnstile.resetTurnstileWidgets();
                    }
                }
            });
        },

        /**
         * Handle form resets and re-renders
         */
        handleFormResets: function() {
            // Reset Turnstile when forms are reset
            $('body').on('reset', 'form[class*="uael-login-form"], form[class*="uael-registration-form"]', function() {
                UAETurnstile.resetTurnstileWidgets();
            });
        },

        /**
         * Reset all Turnstile widgets on the page
         */
        resetTurnstileWidgets: function() {
            if (typeof turnstile !== 'undefined') {
                $('.cf-turnstile').each(function() {
                    var widgetId = $(this).attr('id');
                    if (widgetId && widgetId.indexOf('cfturnstile-uae-') === 0) {
                        try {
                            turnstile.reset('#' + widgetId);
                        } catch (e) {
                            // If reset fails, try to re-render
                            try {
                                turnstile.render('#' + widgetId);
                            } catch (e2) {
                                console.log('Turnstile reset/render failed for: ' + widgetId);
                            }
                        }
                    }
                });
            }
        },

        /**
         * Render Turnstile widgets for dynamically loaded content
         */
        renderTurnstileWidgets: function() {
            if (typeof turnstile !== 'undefined') {
                $('.cf-turnstile[id*="cfturnstile-uae-"]').each(function() {
                    var widget = $(this);
                    // Only render if not already rendered
                    if (!widget.find('iframe').length) {
                        var widgetId = widget.attr('id');
                        try {
                            turnstile.render('#' + widgetId);
                        } catch (e) {
                            console.log('Turnstile render failed for: ' + widgetId);
                        }
                    }
                });
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        UAETurnstile.init();
        
        // Re-render widgets after a short delay to ensure DOM is fully loaded
        setTimeout(function() {
            UAETurnstile.renderTurnstileWidgets();
        }, 500);
    });

    // Make functions globally available for other scripts
    window.UAETurnstile = UAETurnstile;

})(jQuery);