/**
 * VQ Checkout - Auto-fill Frontend JS (File 07)
 * Handles AJAX lookup of customer data based on phone number input (for guests).
 */
jQuery(function($) {
    'use strict';

    const VQAutofill = {
        phoneField: $('#billing_phone'),
        // Configuration relies on the global object from Stage 1 (vqcheckoutCheckout)
        config: typeof vqcheckoutCheckout !== 'undefined' ? vqcheckoutCheckout : null,
        debounceTimer: null,
        lookupCache: {},

        init: function() {
            if (!this.config || this.phoneField.length === 0) return;

            // Note: Script is only enqueued for non-logged-in users (checked in PHP).

            this.bindEvents();
        },

        bindEvents: function() {
            // Use 'input' event for real-time detection, debounced for performance
            this.phoneField.on('input', this.onPhoneInput.bind(this));
        },

        onPhoneInput: function() {
            clearTimeout(this.debounceTimer);
            const phone = this.phoneField.val().trim();

            // Basic client-side validation (matches PHP validation logic)
            const isValidPhone = /^(0|\+84)(3[2-9]|5[25689]|7[0|6-9]|8[1-9]|9[0-4|6-9])[0-9]{7}$/.test(phone.replace(/[^0-9\+]/g, ''));

            if (isValidPhone) {
                // Debounce the lookup (wait 500ms after user stops typing)
                this.debounceTimer = setTimeout(() => {
                    this.lookupCustomer(phone);
                }, 500);
            }
        },

        lookupCustomer: function(phone) {
            // Check cache first
            if (this.lookupCache.hasOwnProperty(phone)) {
                if (this.lookupCache[phone]) {
                    this.fillForm(this.lookupCache[phone]);
                }
                return;
            }
            
            this.toggleLoading(true);

            $.ajax({
                url: this.config.ajax_url,
                method: 'POST',
                data: {
                    action: 'vqcheckout_autofill_lookup',
                    // Use the main checkout nonce
                    nonce: this.config.nonce,
                    phone: phone
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.lookupCache[phone] = response.data;
                        this.fillForm(response.data);
                    } else {
                        // Cache the negative result
                        this.lookupCache[phone] = false;
                    }
                },
                error: (xhr) => {
                    console.log('VQ Autofill: Error occurred during lookup.');
                    this.lookupCache[phone] = false;
                },
                complete: () => {
                    this.toggleLoading(false);
                }
            });
        },

        fillForm: function(data) {
            // Iterate over the data returned by AJAX (keys match field IDs)
            $.each(data, (key, value) => {
                if (!value) return;
                
                // Skip address location fields, handled separately
                if (['billing_country', 'billing_state', 'billing_city'].includes(key)) return;

                this.setFieldValue(key, value);
            });

            // Handle Address fields (Country, State, City) - Requires synchronization
            this.fillAddressDetails(data);
        },

        fillAddressDetails: function(data) {
            const country = data.billing_country || 'VN';
            const state = data.billing_state;
            const city = data.billing_city;

            // 1. Set Country (and trigger WC event)
            const countryField = $('#billing_country');
            // Check if the field exists (it might be hidden by settings)
            if (countryField.length && countryField.val() !== country) {
                countryField.val(country).trigger('change');
                // Wait for potential WC country change handlers
                setTimeout(() => {
                    this.setStateAndCity(state, city);
                }, 300);
            } else {
                // If country is already correct or field is hidden (default VN)
                this.setStateAndCity(state, city);
            }
        },

        setStateAndCity: function(state, city) {
             const stateField = $('#billing_state');
             
             if (stateField.length && state) {
                 // Check if the field is empty before filling
                 if (stateField.val() === '' || stateField.val() === null) {
                     stateField.val(state).trigger('change');
                     
                     // City (Ward) must be set AFTER the ward list loads asynchronously.
                     if (city) {
                        this.waitForWardsToLoad(city);
                     }
                 }
             }
        },

        // Robust mechanism to wait for asynchronous ward loading triggered by state change
        waitForWardsToLoad: function(wardValue) {
            let attempts = 0;
            const maxAttempts = 20; // Timeout after 4 seconds (20 * 200ms)
            const cityField = $('#billing_city');

            const checkInterval = setInterval(() => {
                // Check if the target option now exists in the dynamic dropdown
                if (cityField.is('select') && cityField.find(`option[value="${wardValue}"]`).length > 0) {
                    clearInterval(checkInterval);
                    this.setFieldValue('billing_city', wardValue);
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkInterval);
                    console.log('VQ Autofill: Timeout waiting for wards to load.');
                }
                attempts++;
            }, 200);
        },

        setFieldValue: function(fieldId, value) {
            const field = $(`#${fieldId}`);
            if (field.length && value) {
                // Only fill if the field is currently empty to respect user input
                if (field.val() === '' || field.val() === null) {
                    field.val(value).trigger('change');
                }
            }
        },

        toggleLoading: function(isLoading) {
            // Use WC block UI on the billing fields wrapper for visual feedback
            const wrapper = $('.woocommerce-billing-fields__field-wrapper');
            if (isLoading) {
                wrapper.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            } else {
                wrapper.unblock();
            }
        }
    };

    // Initialize VQAutofill
    $(document).ready(function() {
        VQAutofill.init();
    });
});