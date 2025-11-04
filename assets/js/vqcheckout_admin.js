/**
 * VQ Checkout - Global Admin JS (File 07) - UPDATED
 * Handles JS-based tabs and conditional logic in the plugin's admin settings page.
 */
jQuery(function($) {
    'use strict';

    const VQAdminGlobal = {
        settingsWrap: $('.vqcheckout-settings-wrap'),

        init: function() {
            if (this.settingsWrap.length === 0) return;

            this.initTabs();
            this.handleSettingsDependencies();
        },

        // Initialize JS-based Tabs functionality (Fix for Saving Bug)
        initTabs: function() {
            const tabsNav = this.settingsWrap.find('.vq-nav-tabs');
            const tabsContent = this.settingsWrap.find('.vq-tab-content');
            const submitButton = this.settingsWrap.find('.vq-submit-button-wrapper');

            // Handle tab switching
            tabsNav.on('click', 'a', function(e) {
                e.preventDefault();
                const tabLink = $(this);
                const tabId = tabLink.data('tab');

                // Update active state in nav
                tabsNav.find('a').removeClass('nav-tab-active');
                tabLink.addClass('nav-tab-active');

                // Show/Hide content
                tabsContent.removeClass('vq-tab-active');
                $('#' + tabId).addClass('vq-tab-active');

                // Hide submit button on Tools tab, show on others
                if (tabId === 'tab-tools') {
                    submitButton.hide();
                } else {
                    submitButton.show();
                }

                // Update URL hash for bookmarking and persistence
                history.replaceState(null, null, window.location.pathname + window.location.search.split('#')[0] + '#' + tabId);
            });

            // Activate tab based on URL hash if present, otherwise activate first tab
            const hash = window.location.hash;
            if (hash && tabsNav.find('a[href="' + hash + '"]').length) {
                tabsNav.find('a[href="' + hash + '"]').trigger('click');
            } else {
                // Ensure the first tab is active if no hash matches
                tabsNav.find('a:first').trigger('click');
            }
        },

        // Manages conditional visibility of settings
        handleSettingsDependencies: function() {
            
            // 1. Main Anti-Spam Toggle
            const antiSpamEnabled = $('#anti_spam_enabled');
            const antiSpamTable = $('#vq-antispam-settings-table');

            function toggleAntiSpamSubSettings() {
                if (antiSpamTable.length) {
                    // Toggle visibility of all sub-settings rows (.vq-sub-setting)
                    antiSpamTable.find('.vq-sub-setting').toggle(antiSpamEnabled.is(':checked'));
                }
                // After toggling the section, re-evaluate the reCaptcha specific dependencies
                toggleRecaptchaFields();
            }

            // 2. reCAPTCHA Toggle
            const recaptchaEnabled = $('#recaptcha_enabled');
            const recaptchaKeyFields = $('.vq-recaptcha-keys');

            function toggleRecaptchaFields() {
                // Show keys only if Anti-Spam is ON AND reCaptcha is ON
                if (antiSpamEnabled.is(':checked') && recaptchaEnabled.is(':checked')) {
                    recaptchaKeyFields.show();
                } else {
                    recaptchaKeyFields.hide();
                }
            }

            // 3. NEW: PayPal Conversion Toggle
            const paypalConversionEnabled = $('#paypal_conversion_enabled');
            const paypalRateField = $('.vq-paypal-conversion-rate');

            function togglePaypalRateField() {
                if (paypalRateField.length) {
                    paypalRateField.toggle(paypalConversionEnabled.is(':checked'));
                }
            }

            // Bind events
            if (antiSpamEnabled.length) {
                antiSpamEnabled.on('change', toggleAntiSpamSubSettings);
            }
            if (recaptchaEnabled.length) {
                recaptchaEnabled.on('change', toggleRecaptchaFields);
            }
            if (paypalConversionEnabled.length) {
                 paypalConversionEnabled.on('change', togglePaypalRateField);
            }

            // Initialize state (Important for initial load)
            toggleAntiSpamSubSettings(); // This runs toggleRecaptchaFields internally
            togglePaypalRateField();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VQAdminGlobal.init();

        // Debug: Log checkbox values before submit (for troubleshooting)
        $('.vqcheckout-settings-form').on('submit', function() {
            console.log('=== VQ Checkout Settings Submit ===');
            $(this).find('input[type="checkbox"]').each(function() {
                console.log($(this).attr('name') + ': ' + ($(this).is(':checked') ? 'checked' : 'unchecked'));
            });
        });
    });
});