/**
 * VQ Checkout - Store Settings Admin JS (File 03)
 * Handles dynamic loading of Wards in WooCommerce -> Settings -> General.
 */
jQuery(function($) {
    'use strict';

    const VQStoreSettings = {
        provinceSelect: $('#woocommerce_default_country'), // WC uses this ID for Country/State
        wardSelect: $('#woocommerce_store_city'), // We repurposed this ID for Wards
        
        init: function() {
            // Check if our custom fields exist on the page
            if (this.provinceSelect.length && this.wardSelect.length) {
                // Ensure SelectWoo is initialized (WC should handle this for enhanced selects, but as a fallback)
                if (!this.wardSelect.hasClass('select2-hidden-accessible')) {
                     // Match WC standard styling
                     this.wardSelect.selectWoo({ width: '350px' });
                }
                this.bindEvents();
            }
        },

        bindEvents: function() {
            // Listen to changes on the Country/State selector
            this.provinceSelect.on('change', this.onProvinceChange.bind(this));
        },

        onProvinceChange: function() {
            const locationValue = this.provinceSelect.val();
            let provinceCode = '';

            // WC format for Country:State is "CC:STATECODE" (e.g., "VN:HANOI")
            if (locationValue && locationValue.startsWith('VN:')) {
                provinceCode = locationValue.substring(3);
            }

            if (provinceCode) {
                this.loadWards(provinceCode);
            } else {
                // Reset if not VN or no specific province selected (e.g., "VN" only)
                this.resetWards();
            }
        },

        loadWards: function(provinceCode) {
            this.toggleLoading(true);

            // Use the global AJAX object defined in Stage 1 (vqcheckoutAdmin)
            if (typeof vqcheckoutAdmin === 'undefined') {
                console.error('VQ Checkout: vqcheckoutAdmin object not found.');
                this.toggleLoading(false);
                return;
            }

            $.ajax({
                url: vqcheckoutAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'vqcheckout_load_wards',
                    nonce: vqcheckoutAdmin.nonce,
                    province_code: provinceCode
                },
                success: (response) => {
                    if (response.success && response.data.length > 0) {
                        this.populateWards(response.data);
                    } else {
                        this.resetWards();
                    }
                },
                error: () => {
                    console.error('VQ Checkout: Error loading wards.');
                    this.resetWards();
                },
                complete: () => {
                    this.toggleLoading(false);
                }
            });
        },

        populateWards: function(wards) {
            this.wardSelect.empty();
            // Use standard Option constructor for SelectWoo compatibility
            this.wardSelect.append(new Option('Chọn xã/phường...', '', false, false));

            $.each(wards, (index, ward) => {
                this.wardSelect.append(new Option(ward.text, ward.id, false, false));
            });

            // Notify SelectWoo about the update
            this.wardSelect.trigger('change');
        },

        resetWards: function() {
            this.wardSelect.empty();
            this.wardSelect.append(new Option('Chọn tỉnh/thành trước...', '', false, false));
            this.wardSelect.trigger('change');
        },

        toggleLoading: function(isLoading) {
            this.wardSelect.prop('disabled', isLoading);
            if (isLoading) {
                this.wardSelect.empty().append(new Option('Đang tải...', '', false, false));
                this.wardSelect.trigger('change');
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VQStoreSettings.init();
    });
});