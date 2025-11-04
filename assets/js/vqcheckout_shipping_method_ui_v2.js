/**
 * VQ Checkout - Shipping Method UI V2 (devvn pattern)
 * Handles ward-specific shipping configuration interface
 */

// Verify script is loaded
console.log('🚀 VQ Checkout: vqcheckout_shipping_method_ui_v2.js LOADED');

jQuery(function($) {
    'use strict';

    console.log('🚀 VQ Checkout: jQuery ready');

    const VQShippingV2 = {
        wardsData: {},
        wardRatesIndex: 0,
        isLoadingWards: false,
        isInitialized: false,

        init: function() {
            console.log('=== VQ Checkout Shipping V2: Init ===');
            console.log('Ward rates wrapper count:', $('.vq-ward-rates-wrapper').length);
            console.log('Is already initialized:', this.isInitialized);

            // Only run on shipping settings page
            if ($('.vq-ward-rates-wrapper').length === 0) {
                console.warn('⚠️ VQ Checkout: vq-ward-rates-wrapper not found, aborting init');
                return false;
            }

            // Prevent multiple initializations
            if (this.isInitialized) {
                console.log('ℹ️ VQ Checkout: Already initialized, skipping');
                return true;
            }

            console.log('🎬 VQ Checkout: Initializing...');
            this.ensureButtonsVisible();
            this.bindEvents();
            this.loadWardsData();
            this.initSortable();
            this.loadExistingRates();
            this.bindConditionToggles();
            this.bindFormSubmit();

            this.isInitialized = true;
            console.log('✅ VQ Checkout: Init complete');
            return true;
        },

        /**
         * Ensure action buttons are visible (fix for Bug 2)
         */
        ensureButtonsVisible: function() {
            const table = $('.vq-ward-rates-table');
            if (table.length === 0) return;

            // Force tfoot to be visible
            const tfoot = table.find('tfoot');
            if (tfoot.length > 0) {
                tfoot.css({
                    'display': 'table-footer-group',
                    'visibility': 'visible',
                    'opacity': '1'
                });

                // Force buttons to be visible
                tfoot.find('button').css({
                    'display': 'inline-block',
                    'visibility': 'visible',
                    'opacity': '1'
                });
            } else {
                // If tfoot doesn't exist, inject it
                console.warn('VQ Checkout: tfoot not found, injecting buttons');
                table.append(`
                    <tfoot style="display: table-footer-group !important; visibility: visible !important;">
                        <tr>
                            <th colspan="5" style="padding: 10px; background: #f9f9f9;">
                                <button type="button" class="button button-primary vq-add-ward-rate" style="margin-right: 10px;">Thêm xã/phường</button>
                                <button type="button" class="button vq-remove-selected">Xóa lựa chọn</button>
                            </th>
                        </tr>
                    </tfoot>
                `);
            }

            console.log('VQ Checkout: Button visibility ensured');
        },

        /**
         * Bind UI events
         */
        bindEvents: function() {
            const self = this;

            // Add ward rate row
            $(document).on('click', '.vq-add-ward-rate', function(e) {
                e.preventDefault();
                self.addWardRateRow();
            });

            // Remove selected rows
            $(document).on('click', '.vq-remove-selected', function(e) {
                e.preventDefault();
                $('.vq-ward-rates-body input[type="checkbox"]:checked').each(function() {
                    $(this).closest('tr').remove();
                });
            });

            // Select all checkboxes
            $(document).on('change', '.vq-select-all', function() {
                const checked = $(this).is(':checked');
                $('.vq-ward-rates-body input[type="checkbox"]').prop('checked', checked);
            });

            // Toggle "No Shipping" checkbox
            $(document).on('change', '.vq-no-shipping', function() {
                const row = $(this).closest('tr');
                const costInputs = row.find('.vq-cost-inputs');

                if ($(this).is(':checked')) {
                    costInputs.hide();
                    costInputs.find('input').prop('disabled', true);
                } else {
                    costInputs.show();
                    costInputs.find('input').prop('disabled', false);
                }
            });

            // Toggle order total conditions
            $(document).on('change', '.vq-enable-conditions', function() {
                const row = $(this).closest('tr');
                const conditionsTable = row.find('.vq-conditions-table');

                if ($(this).is(':checked')) {
                    conditionsTable.show();
                } else {
                    conditionsTable.hide();
                }
            });

            // Add condition row
            $(document).on('click', '.vq-add-condition', function(e) {
                e.preventDefault();
                const tbody = $(this).closest('.vq-conditions-table').find('tbody');
                self.addConditionRow(tbody);
            });

            // Remove condition row
            $(document).on('click', '.vq-remove-condition', function(e) {
                e.preventDefault();
                $(this).closest('tr').remove();
            });

            // Add global price condition
            $(document).on('click', '.vq-add-price-condition', function(e) {
                e.preventDefault();
                self.addGlobalConditionRow();
            });

            // Remove global condition
            $(document).on('click', '.vq-remove-global-condition', function(e) {
                e.preventDefault();
                if ($(this).closest('.vq-condition-rows').find('tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            });
        },

        /**
         * Load wards data via AJAX
         */
        loadWardsData: function() {
            const self = this;
            const provincesData = $('.vq-provinces-data').val();

            console.log('=== VQ Checkout: Load Wards Data ===');
            console.log('Provinces data element:', $('.vq-provinces-data').length);
            console.log('Provinces data value:', provincesData);
            console.log('Is loading:', this.isLoadingWards);

            if (!provincesData || this.isLoadingWards) {
                console.warn('VQ Checkout: No provinces data or already loading');
                return;
            }

            let provinces = [];
            try {
                provinces = JSON.parse(provincesData);
            } catch(e) {
                console.error('VQ Checkout: Invalid provinces data:', e);
                return;
            }

            console.log('Parsed provinces:', provinces);

            if (provinces.length === 0) {
                console.warn('VQ Checkout: No provinces to load');
                return;
            }

            this.isLoadingWards = true;

            console.log('VQ Checkout: Sending AJAX request to:', vqcheckoutShippingAdmin.ajax_url || ajaxurl);

            $.ajax({
                url: vqcheckoutShippingAdmin.ajax_url || ajaxurl,
                method: 'POST',
                data: {
                    action: 'vqcheckout_load_shipping_wards_admin',
                    nonce: vqcheckoutShippingAdmin.nonce || vqcheckoutAdmin.nonce,
                    provinces: provinces
                },
                success: function(response) {
                    console.log('VQ Checkout: AJAX response:', response);
                    if (response.success && response.data) {
                        self.wardsData = response.data;
                        console.log('VQ Checkout: Wards data loaded:', self.wardsData);
                        // NOTE: Don't call initSelect2 here - there are no select elements yet!
                        // Select2 will be initialized when user clicks "Thêm xã/phường" button
                        // via addWardRateRow() -> initSelect2()
                    } else {
                        console.error('VQ Checkout: Failed to load wards:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('VQ Checkout: AJAX error:', error);
                    console.error('XHR:', xhr);
                },
                complete: function() {
                    self.isLoadingWards = false;
                    console.log('VQ Checkout: AJAX complete');
                }
            });
        },

        /**
         * Initialize Select2 for ward selection
         * Called when a new row is added via addWardRateRow()
         */
        initSelect2: function(elements) {
            const self = this;

            console.log('🔧 VQ Checkout: initSelect2 called');
            console.log('Elements count:', elements.length);
            console.log('wardsData available:', !!self.wardsData);
            console.log('wardsData length:', self.wardsData ? self.wardsData.length : 0);

            elements.each(function() {
                const $select = $(this);

                console.log('Processing select element:', $select);

                // Skip if already initialized
                if ($select.hasClass('select2-hidden-accessible')) {
                    console.log('Select2 already initialized, skipping');
                    return;
                }

                // Clear and populate options
                $select.empty();

                if (self.wardsData && Array.isArray(self.wardsData)) {
                    console.log('Populating select with', self.wardsData.length, 'province groups');

                    $.each(self.wardsData, function(i, group) {
                        const $optgroup = $('<optgroup></optgroup>').attr('label', group.text);

                        if (group.children && Array.isArray(group.children)) {
                            console.log('Adding', group.children.length, 'wards for', group.text);

                            $.each(group.children, function(j, ward) {
                                $optgroup.append(new Option(ward.text, ward.id, false, false));
                            });
                        }

                        $select.append($optgroup);
                    });

                    console.log('Total options in select:', $select.find('option').length);
                } else {
                    console.warn('⚠️ wardsData not available or not array!');
                }

                // Initialize Select2
                $select.select2({
                    width: '100%',
                    placeholder: 'Chọn xã/phường...',
                    allowClear: true
                });
            });
        },

        /**
         * Add a new ward rate row
         */
        addWardRateRow: function(data) {
            const index = this.wardRatesIndex++;
            const row = this.getWardRateRowHTML(index, data || {});

            $('.vq-ward-rates-body').append(row);

            // Initialize Select2 for the new row
            const $newRow = $('.vq-ward-rates-body tr:last');
            this.initSelect2($newRow.find('.vq-ward-select'));

            // Restore selected values if provided
            if (data && data.wards && Array.isArray(data.wards)) {
                $newRow.find('.vq-ward-select').val(data.wards).trigger('change');
            }
        },

        /**
         * Generate HTML for ward rate row
         */
        getWardRateRowHTML: function(index, data) {
            const cost = data.cost || '';
            const title = data.title || '';
            const noShipping = data.no_shipping || false;
            const hasConditions = data.conditions && data.conditions.length > 0;

            return `
                <tr class="vq-ward-rate-row" data-index="${index}">
                    <td class="check-column">
                        <input type="checkbox" class="vq-row-select">
                    </td>
                    <td>
                        <select class="vq-ward-select" multiple="multiple" style="width: 300px;">
                            <!-- Options populated by Select2 -->
                        </select>
                    </td>
                    <td class="vq-cost-cell">
                        <div class="vq-cost-inputs" style="${noShipping ? 'display:none;' : ''}">
                            <input type="text" class="vq-rate-cost regular-text"
                                   value="${cost}" placeholder="0"
                                   ${noShipping ? 'disabled' : ''}>

                            <div class="vq-conditions-toggle" style="margin-top: 5px;">
                                <label>
                                    <input type="checkbox" class="vq-enable-conditions" ${hasConditions ? 'checked' : ''}>
                                    Tùy ship theo tổng đơn hàng
                                </label>
                            </div>

                            <div class="vq-conditions-table" style="${hasConditions ? '' : 'display:none;'} margin-top: 10px;">
                                <table class="widefat" style="width: auto;">
                                    <thead>
                                        <tr>
                                            <th>Điều kiện giá order >=</th>
                                            <th>Giá vận chuyển</th>
                                            <th width="30"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${this.getConditionRowsHTML(data.conditions || [])}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3">
                                                <button type="button" class="button button-small vq-add-condition">Thêm điều kiện</button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="vq-no-shipping-option" style="margin-top: 5px;">
                            <label>
                                <input type="checkbox" class="vq-no-shipping" ${noShipping ? 'checked' : ''}>
                                Không vận chuyển tới đây
                            </label>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="vq-rate-title regular-text"
                               value="${title}" placeholder="Tiêu đề của hình thức vận chuyển">
                    </td>
                    <td class="vq-sort-column">
                        <span class="vq-sort-handle dashicons dashicons-menu"></span>
                    </td>
                </tr>
            `;
        },

        /**
         * Generate HTML for condition rows
         */
        getConditionRowsHTML: function(conditions) {
            if (!conditions || conditions.length === 0) {
                return `
                    <tr>
                        <td><input type="number" class="vq-cond-min small-text" min="0" step="1000" placeholder="0"></td>
                        <td><input type="number" class="vq-cond-cost small-text" min="0" step="1000" placeholder="0"></td>
                        <td><a href="#" class="vq-remove-condition button button-small">×</a></td>
                    </tr>
                `;
            }

            return conditions.map(cond => `
                <tr>
                    <td><input type="number" class="vq-cond-min small-text" min="0" step="1000" value="${cond.min || ''}" placeholder="0"></td>
                    <td><input type="number" class="vq-cond-cost small-text" min="0" step="1000" value="${cond.cost || ''}" placeholder="0"></td>
                    <td><a href="#" class="vq-remove-condition button button-small">×</a></td>
                </tr>
            `).join('');
        },

        /**
         * Add condition row to existing table
         */
        addConditionRow: function(tbody) {
            tbody.append(`
                <tr>
                    <td><input type="number" class="vq-cond-min small-text" min="0" step="1000" placeholder="0"></td>
                    <td><input type="number" class="vq-cond-cost small-text" min="0" step="1000" placeholder="0"></td>
                    <td><a href="#" class="vq-remove-condition button button-small">×</a></td>
                </tr>
            `);
        },

        /**
         * Add global price condition row
         */
        addGlobalConditionRow: function() {
            const tbody = $('.vq-condition-rows');
            tbody.append(`
                <tr class="vq-condition-row">
                    <td><input type="number" name="vq_all_price_cond_min[]" min="0" step="1000" class="regular-text" /></td>
                    <td><input type="number" name="vq_all_price_cond_cost[]" min="0" step="1000" class="regular-text" /></td>
                    <td><a href="#" class="vq-remove-global-condition button">×</a></td>
                </tr>
            `);
        },

        /**
         * Initialize sortable for ward rates table
         */
        initSortable: function() {
            if ($.fn.sortable) {
                $('.vq-ward-rates-body').sortable({
                    items: 'tr',
                    cursor: 'move',
                    axis: 'y',
                    handle: '.vq-sort-handle',
                    opacity: 0.6,
                    placeholder: 'vq-sort-placeholder'
                });
            }
        },

        /**
         * Load existing ward rates from hidden input
         */
        loadExistingRates: function() {
            const dataInput = $('.vq-ward-rates-data').val();

            if (!dataInput) {
                return;
            }

            try {
                const rates = JSON.parse(dataInput);

                if (Array.isArray(rates)) {
                    const self = this;
                    rates.forEach(function(rate) {
                        self.addWardRateRow(rate);
                    });
                }
            } catch(e) {
                console.error('Error parsing existing rates:', e);
            }
        },

        /**
         * Bind condition toggles
         */
        bindConditionToggles: function() {
            $('.vq-toggle-condition').on('change', function() {
                const target = $(this).data('target');

                if ($(this).is(':checked')) {
                    $(target).show();
                } else {
                    $(target).hide();
                }
            });

            // Auto-check if there are existing conditions (more than one row or first row has values)
            const priceConditionRows = $('.vq-condition-rows tr');
            if (priceConditionRows.length > 0) {
                const hasValues = priceConditionRows.first().find('input[name="vq_all_price_cond_min[]"]').val();
                if (priceConditionRows.length > 1 || hasValues) {
                    $('.vq-toggle-condition[data-target=".vq-price-condition-table"]').prop('checked', true).trigger('change');
                }
            }
        },

        /**
         * Serialize ward rates to JSON before form submit
         */
        bindFormSubmit: function() {
            const self = this;

            $('form').on('submit', function(e) {
                // Check if this form contains our ward rates table
                if ($('.vq-ward-rates-wrapper').length === 0) {
                    return true;
                }

                const rates = self.serializeWardRates();
                $('.vq-ward-rates-data').val(JSON.stringify(rates));

                return true;
            });
        },

        /**
         * Serialize all ward rates data to array
         */
        serializeWardRates: function() {
            const rates = [];

            $('.vq-ward-rates-body tr').each(function() {
                const $row = $(this);
                const wards = $row.find('.vq-ward-select').val() || [];
                const cost = $row.find('.vq-rate-cost').val() || '0';
                const title = $row.find('.vq-rate-title').val() || '';
                const noShipping = $row.find('.vq-no-shipping').is(':checked');
                const hasConditions = $row.find('.vq-enable-conditions').is(':checked');

                // Skip empty rows
                if (wards.length === 0) {
                    return;
                }

                const rate = {
                    wards: wards,
                    cost: parseFloat(cost) || 0,
                    title: title,
                    no_shipping: noShipping ? 1 : 0,
                    conditions: []
                };

                // Collect conditions if enabled
                if (hasConditions && !noShipping) {
                    $row.find('.vq-conditions-table tbody tr').each(function() {
                        const min = $(this).find('.vq-cond-min').val();
                        const condCost = $(this).find('.vq-cond-cost').val();

                        if (min && condCost) {
                            rate.conditions.push({
                                min: parseFloat(min) || 0,
                                cost: parseFloat(condCost) || 0
                            });
                        }
                    });
                }

                rates.push(rate);
            });

            return rates;
        }
    };

    // Retry initialization with increasing delays
    let initAttempts = 0;
    const maxAttempts = 10;

    function tryInit() {
        initAttempts++;
        console.log('🔄 VQ Checkout: Initialization attempt #' + initAttempts);

        if ($('.vq-ward-rates-wrapper').length > 0) {
            console.log('✅ VQ Checkout: Wrapper found on attempt #' + initAttempts);
            VQShippingV2.init();
            return true;
        }

        if (initAttempts < maxAttempts) {
            const delay = initAttempts * 200; // Increasing delay: 200ms, 400ms, 600ms...
            console.log('⏳ VQ Checkout: Wrapper not found, retrying in ' + delay + 'ms...');
            setTimeout(tryInit, delay);
            return false;
        }

        console.error('❌ VQ Checkout: Failed to find wrapper after ' + maxAttempts + ' attempts');
        return false;
    }

    // Initialize on document ready
    $(document).ready(function() {
        console.log('📄 VQ Checkout: Document ready');
        tryInit();
    });

    // Re-initialize when WooCommerce settings page loads
    $(document).on('wc_backbone_modal_loaded', function(e, target) {
        console.log('🎯 VQ Checkout: wc_backbone_modal_loaded event, target:', target);
        if (target === 'wc-modal-shipping-method-settings') {
            initAttempts = 0; // Reset attempts
            setTimeout(tryInit, 100);
        }
    });

    // Listen for WooCommerce inline shipping method edit
    $(document).on('click', '.wc-shipping-zone-method-settings', function() {
        console.log('🖱️ VQ Checkout: Shipping method settings clicked');
        initAttempts = 0; // Reset attempts
        setTimeout(tryInit, 300);
    });

    // Use MutationObserver to detect when wrapper is added to DOM
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    $(mutation.addedNodes).each(function() {
                        if ($(this).hasClass('vq-ward-rates-wrapper') || $(this).find('.vq-ward-rates-wrapper').length > 0) {
                            console.log('🔍 VQ Checkout: Wrapper detected via MutationObserver');
                            VQShippingV2.init();
                            observer.disconnect(); // Stop observing once initialized
                        }
                    });
                }
            });
        });

        // Start observing when document is ready
        $(document).ready(function() {
            const targetNode = document.querySelector('.woocommerce') || document.body;
            console.log('👁️ VQ Checkout: Starting MutationObserver on', targetNode.tagName);
            observer.observe(targetNode, {
                childList: true,
                subtree: true
            });
        });
    }
});
