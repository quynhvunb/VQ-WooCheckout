/**
 * VQ Checkout - Checkout Frontend JS (File 04)
 * Handles dynamic loading of Wards on the Checkout page, including robust compatibility with country switching.
 */
jQuery(function($) {
    'use strict';

    const VQCheckout = {
        // Cache for loaded wards data
        wardsCache: {},
        // Configuration localized from PHP (Stage 1)
        config: typeof vqcheckoutCheckout !== 'undefined' ? vqcheckoutCheckout : null,

        init: function() {
            if (!this.config) return;

            // Bind to WC core event for country change. This ensures compatibility if show_country is enabled.
            $(document.body).on('country_to_state_changed', this.onCountryChange.bind(this));
            
            // Bind to province change event using event delegation (for AJAX updates)
            $(document.body).on('change', '#billing_state, #shipping_state', this.onProvinceChange.bind(this));
            
            // Initialize fields on load and after WC AJAX updates
            this.initFields();
            $(document.body).on('updated_checkout updated_cart_totals', this.initFields.bind(this));
        },

        initFields: function() {
            // Check current country selections and set up fields accordingly
            this.handleCountrySetup('billing');
            this.handleCountrySetup('shipping');
        },

        // Handler for WooCommerce's country_to_state_changed event
        onCountryChange: function(event, country, wrapper) {
            // Determine the section (billing or shipping) from the wrapper provided by the WC event
            const section = wrapper.closest('.woocommerce-billing-fields').length ? 'billing' : (wrapper.closest('.woocommerce-shipping-fields').length ? 'shipping' : null);
            
            if (section) {
                this.handleCountrySetup(section, country);
            }
        },
        
        handleCountrySetup: function(section, country = null) {
            // Determine the country if not provided
            if (!country) {
                const countryField = $(`[name="${section}_country"]`); // Selects both <select> and <input type="hidden">
                if (countryField.length && countryField.val()) {
                    country = countryField.val();
                } else {
                    // If field is missing or empty, we cannot proceed reliably.
                    return;
                }
            }

            if (country === 'VN') {
                this.setupVNAddressFields(section);
            } else {
                // For non-VN countries, ensure City field reverts to a standard input
                this.resetAddressFields(section);
            }
        },

        setupVNAddressFields: function(section) {
            const provinceSelect = $(`#${section}_state`);
            let wardSelect = $(`#${section}_city`);

            // Ensure ward field is a select element (it might be an input if switched from another country)
            if (wardSelect.is('input')) {
                wardSelect = this.convertCityToSelect(section);
            }

            // If province is already selected (e.g., pre-filled data), load wards
            const selectedProvince = provinceSelect.val();
            if (selectedProvince) {
                // Pass the current value of the ward select to restore it after loading
                const currentWard = wardSelect.val();
                this.loadWards(selectedProvince, section, currentWard);
            }
        },

        resetAddressFields: function(section) {
            const wardSelect = $(`#${section}_city`);
            // If ward field is a select, revert it back to input for other countries
            if (wardSelect.is('select')) {
                this.convertCityToInput(section);
            }
        },

        // Utility to convert City input to a select element for Wards
        convertCityToSelect: function(section) {
            const cityField = $(`#${section}_city`);
            const placeholder = this.config.i18n.select_ward || 'Chọn xã/phường...';

            const select = $('<select></select>')
                .attr('name', cityField.attr('name'))
                .attr('id', cityField.attr('id'))
                .attr('class', cityField.attr('class'))
                .attr('autocomplete', cityField.attr('autocomplete') || 'address-level2')
                .attr('data-placeholder', placeholder);
            
            // Add initial option
            select.append(new Option('Chọn tỉnh/thành trước...', '', false, false));

            cityField.replaceWith(select);
            // Initialize SelectWoo (WC standard)
            select.selectWoo();
            return select;
        },

        // Utility to convert City select back to an input element
        convertCityToInput: function(section) {
             const cityField = $(`#${section}_city`);
             // Destroy SelectWoo instance if initialized
             if (cityField.hasClass('select2-hidden-accessible')) {
                cityField.selectWoo('destroy');
             }

             const input = $('<input type="text" />')
                .attr('name', cityField.attr('name'))
                .attr('id', cityField.attr('id'))
                .attr('class', cityField.attr('class'))
                .attr('autocomplete', cityField.attr('autocomplete') || 'address-level2')
                .attr('placeholder', cityField.attr('data-placeholder') || '');

             cityField.replaceWith(input);
             return input;
        },

        onProvinceChange: function(event) {
            const select = $(event.target);
            const section = select.attr('id').includes('billing') ? 'billing' : 'shipping';
            
            // Crucial check: Only proceed if the country context is VN
            const countryField = $(`[name="${section}_country"]`);
            const country = (countryField.length) ? countryField.val() : 'VN';

            if (country !== 'VN') return;

            const provinceCode = select.val();
            
            // When user manually changes province, we reset the ward selection (currentWard = null)
            const currentWard = null; 

            if (provinceCode) {
                this.loadWards(provinceCode, section, currentWard);
            } else {
                this.clearWards(section);
            }
        },

        loadWards: function(provinceCode, section, currentWard) {
            // Check cache first
            if (this.wardsCache[provinceCode]) {
                this.populateWards(this.wardsCache[provinceCode], section, currentWard);
                return;
            }

            // Load via AJAX (as determined in Stage 1 optimization)
            this.toggleLoading(true, section);

            $.ajax({
                url: this.config.ajax_url,
                method: 'POST',
                data: {
                    action: 'vqcheckout_load_wards',
                    nonce: this.config.nonce,
                    province_code: provinceCode
                },
                success: (response) => {
                    if (response.success && response.data) {
                        // Cache the result
                        this.wardsCache[provinceCode] = response.data;
                        this.populateWards(response.data, section, currentWard);
                    } else {
                        this.clearWards(section);
                    }
                },
                error: () => {
                    console.error('Error loading wards.');
                    this.clearWards(section);
                },
                complete: () => {
                    this.toggleLoading(false, section);
                }
            });
        },

        populateWards: function(wards, section, currentWard) {
            const wardSelect = $(`#${section}_city`);
            
            // Safety check: Ensure the element is still a select
            if (!wardSelect.is('select')) return;

            wardSelect.empty();
            const placeholder = this.config.i18n.select_ward || 'Chọn xã/phường...';
            wardSelect.append(new Option(placeholder, '', false, false));

            wards.forEach(ward => {
                const selected = (ward.id === currentWard);
                // Use Option constructor for proper SelectWoo handling
                wardSelect.append(new Option(ward.text, ward.id, false, selected));
            });

            // Trigger change to update SelectWoo display and notify WC (important for shipping calculation)
            wardSelect.trigger('change');
        },

        clearWards: function(section) {
            const wardSelect = $(`#${section}_city`);
            if (!wardSelect.is('select')) return;

            wardSelect.empty();
            wardSelect.append(new Option('Chọn tỉnh/thành trước...', '', false, false));
            wardSelect.trigger('change');
        },

        toggleLoading: function(isLoading, section) {
            // Use WooCommerce block UI for consistent UX
            const wardRow = $(`#${section}_city_field`);
            if (isLoading) {
                wardRow.block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            } else {
                wardRow.unblock();
            }
        }
    };

    // Initialize VQCheckout
    VQCheckout.init();
});