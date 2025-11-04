/**
 * VQ Checkout - Shipping Method Admin UI JS (File 05) - UPDATED FOR COMPLEX UI
 * Handles dynamic tables, AJAX loading, sorting, and nested interactions.
 */
jQuery(function($) {
    'use strict';

    const VQShippingUI = {
        wardsData: null,
        isLoadingWards: false,

        init: function() {
            const wrappers = $('.vq-dynamic-table-wrapper');
            if (wrappers.length === 0) return;

            // Initialize dynamic tables based on their specific wrapper IDs
            wrappers.each(function() {
                const wrapper = $(this);
                const wrapperId = wrapper.data('wrapper-id');

                switch (wrapperId) {
                    case 'global-conditions':
                        VQShippingUI.initDynamicTable(wrapper, '#tmpl-vq-global-condition-row-template');
                        break;
                    case 'ward-rates':
                        // Use the complex template for ward rates
                        VQShippingUI.initDynamicTable(wrapper, '#tmpl-vq-ward-rate-complex-row-template');
                        VQShippingUI.loadWardsData(wrapper);
                        VQShippingUI.initSortable(wrapper);
                        VQShippingUI.initComplexRowInteractions(wrapper);
                        break;
                }
            });
        },

        /**
         * Handles adding/removing rows for dynamic tables (Generalized).
         */
        initDynamicTable: function(wrapper, templateId) {
            // Check if template exists before initializing
            if ($(templateId).length === 0) return;
            
            const template = wp.template(templateId.substring(1)); 

            // Event: Add new row
            wrapper.on('click', '.vq-add-row', function(e) {
                e.preventDefault();
                const button = $(this);
                const tbody = wrapper.find('.vq-dynamic-table-body').first(); // Use first() for main tables
                
                let index = parseInt(button.data('rows')) + 1;
                
                // Render HTML from template
                const html = template({ index: index });
                tbody.append(html);

                // Specific handling for Ward Rates (Complex UI)
                if (wrapper.data('wrapper-id') === 'ward-rates') {
                    const newRow = tbody.find('tr.vq-dynamic-row:last-child');
                    // Update the data-row-index attribute (crucial for sub-tables)
                    newRow.attr('data-row-index', index); 
                    VQShippingUI.initWardSelect2(newRow.find('.vq-ward-select'));
                    // Ensure sub-table interactions are initialized for the new row
                    VQShippingUI.initSubTable(newRow); 
                }

                button.data('rows', index);
            });

            // Event: Remove row (works for main and sub-tables)
            wrapper.on('click', '.vq-remove-row', function(e) {
                e.preventDefault();
                $(this).closest('.vq-dynamic-row').remove();
            });
        },

        // Initialize interactions specific to complex rows (Ward Rates)
        initComplexRowInteractions: function(wrapper) {
            // Initialize sub-tables for existing rows
            wrapper.find('.vq-ward-rate-row').each(function() {
                VQShippingUI.initSubTable($(this));
            });

            // Handle "No Shipping" toggle visibility
            wrapper.on('change', '.vq-toggle-no-shipping', function() {
                const checked = $(this).is(':checked');
                // Toggle visibility of the cost/conditions wrapper within the same cell
                $(this).closest('.vq-cost-conditions-cell').find('.vq-cost-conditions-wrapper').toggle(!checked);
            });
        },

        // Initialize Sub-tables (Conditions within Ward Rates)
        initSubTable: function(parentRow) {
            const subTableWrapper = parentRow.find('.vq-ward-conditions-sub-table-wrapper');
            // Ensure the sub-table template exists
            if ($('#tmpl-vq-ward-condition-sub-row-template').length === 0) return;

            const template = wp.template('tmpl-vq-ward-condition-sub-row-template');
            // Get the parent index from the main row's data attribute
            // We must use attr() here because data() might cache the initial template value '{{data.index}}'
            const parentIndex = parentRow.attr('data-row-index');

            // Event: Add sub-row
            subTableWrapper.on('click', '.vq-add-sub-row', function(e) {
                e.preventDefault();
                const button = $(this);
                const tbody = subTableWrapper.find('.vq-dynamic-table-body');

                let subIndex = parseInt(button.data('rows')) + 1;

                // Render HTML using parent and sub index
                const html = template({ parentIndex: parentIndex, subIndex: subIndex });
                tbody.append(html);

                button.data('rows', subIndex);
            });
        },

        // Initialize Sortable (for Ward Rates)
        initSortable: function(wrapper) {
            // Ensure jQuery UI sortable is loaded
            if ($.fn.sortable) {
                wrapper.find('.vq-sortable').sortable({
                    items: '.vq-dynamic-row',
                    cursor: 'move',
                    axis: 'y',
                    handle: '.vq-col-sort',
                    containment: 'parent',
                    opacity: 0.6,
                    // Note: We do NOT need to re-initialize Select2 after sorting in this complex structure
                    // because the inputs remain intact during sorting when using nested tables/divs correctly.
                });
            }
        },


        /**
         * Loads Wards data via AJAX (No changes needed here)
         */
        loadWardsData: function(wrapper) {
            const provinces = wrapper.data('provinces');

            if (!provinces || provinces.length === 0 || this.isLoadingWards) {
                return;
            }

            if (this.wardsData) {
                this.initializeWardUI(wrapper);
                return;
            }

            this.isLoadingWards = true;

            if (typeof vqcheckoutShippingAdmin === 'undefined') return;

            $.ajax({
                url: vqcheckoutShippingAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'vqcheckout_load_shipping_wards_admin',
                    nonce: vqcheckoutShippingAdmin.nonce,
                    provinces: provinces
                },
                success: (response) => {
                    if (response.success) {
                        VQShippingUI.wardsData = response.data;
                        this.initializeWardUI(wrapper);
                    } else {
                        $('.vq-loading-wards').text('Lỗi tải dữ liệu: ' + (response.data || 'Không tìm thấy.')).css('color', 'red');
                    }
                },
                error: () => {
                    $('.vq-loading-wards').text('Lỗi kết nối khi tải dữ liệu.').css('color', 'red');
                },
                complete: () => {
                    this.isLoadingWards = false;
                }
            });
        },

        initializeWardUI: function(wrapper) {
            wrapper.find('.vq-loading-wards').hide();
            wrapper.find('.vq-shipping-table').show();
            this.initWardSelect2(wrapper.find('.vq-ward-select'));
        },

        /**
         * Initializes Select2 for Ward selection (No changes needed here)
         */
        initWardSelect2: function(elements) {
            elements.each(function() {
                const select = $(this);
                
                // Capture currently selected values
                const selectedValues = select.val();

                // Clear existing options
                select.empty();

                // Populate options
                if (VQShippingUI.wardsData && VQShippingUI.wardsData.length > 0) {
                    $.each(VQShippingUI.wardsData, function(i, group) {
                        const optgroup = $('<optgroup></optgroup>').attr('label', group.text);
                        $.each(group.children, function(j, ward) {
                            optgroup.append(new Option(ward.text, ward.id, false, false));
                        });
                        select.append(optgroup);
                    });
                } else {
                    select.append(new Option('Không có xã/phường khả dụng', '', false, false));
                }

                // Restore the previously selected values
                if (selectedValues && selectedValues.length > 0) {
                    select.val(selectedValues);
                }

                // Initialize SelectWoo
                if ($.fn.selectWoo) {
                    select.selectWoo({
                        width: '100%',
                        allowClear: true
                    });
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VQShippingUI.init();
    });

    // Re-initialize when the WooCommerce settings modal opens
    $(document).on('wc_backbone_modal_loaded', function(e, target) {
        if (target === 'wc-modal-shipping-method-settings') {
            // Reset data cache when modal opens
            VQShippingUI.wardsData = null; 
            VQShippingUI.init();
        }
    });
});