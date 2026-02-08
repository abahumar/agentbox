/**
 * Agent Box Orders - Admin Create Order JavaScript
 */
(function($) {
    'use strict';

    const AboxAdminOrder = {
        searchTimeout: null,

        /**
         * Initialize
         */
        init: function() {
            this.initCustomerSelect();
            this.initReceiptUpload();
            this.bindEvents();
            this.addBox();
            this.updateSummary();
        },

        /**
         * Initialize customer select with Select2
         */
        initCustomerSelect: function() {
            const self = this;

            $('#abox-customer-select').select2({
                ajax: {
                    url: abox_admin_vars.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            action: 'abox_admin_search_customers',
                            nonce: abox_admin_vars.nonce,
                            term: params.term
                        };
                    },
                    processResults: function(response) {
                        if (response.success) {
                            return { results: response.data.customers };
                        }
                        return { results: [] };
                    },
                    cache: true
                },
                minimumInputLength: 2,
                placeholder: abox_admin_vars.i18n.search_customers,
                allowClear: true
            });

            // Fetch and populate billing when customer is selected
            $('#abox-customer-select').on('change', function() {
                const customerId = $(this).val();
                if (customerId && customerId !== '0') {
                    self.fetchCustomerDetails(customerId);
                } else {
                    self.clearBillingFields();
                    self.clearShippingFields();
                }
            });
        },

        /**
         * Initialize receipt upload UI
         */
        initReceiptUpload: function() {
            var self = this;

            // File select button
            $(document).on('click', '.abox-select-receipt-btn', function(e) {
                e.preventDefault();
                $('#abox-receipt-files').click();
            });

            // Click on upload area
            $(document).on('click', '.abox-receipt-upload-area', function() {
                $('#abox-receipt-files').click();
            });

            // Drag and drop
            $(document).on('dragover', '.abox-receipt-upload-area', function(e) {
                e.preventDefault();
                $(this).css('border-color', '#0073aa');
            }).on('dragleave', '.abox-receipt-upload-area', function(e) {
                e.preventDefault();
                $(this).css('border-color', '#ccc');
            }).on('drop', '.abox-receipt-upload-area', function(e) {
                e.preventDefault();
                $(this).css('border-color', '#ccc');
                var files = e.originalEvent.dataTransfer.files;
                if (files.length) {
                    $('#abox-receipt-files')[0].files = files;
                    $('#abox-receipt-files').trigger('change');
                }
            });

            // Files selected
            $(document).on('change', '#abox-receipt-files', function() {
                var files = this.files;
                var $list = $('.abox-selected-files-list');
                var $ul = $list.find('ul');
                $ul.empty();

                if (files.length) {
                    var maxSize = 5 * 1024 * 1024;
                    var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
                    var validFiles = [];
                    var errors = [];

                    for (var i = 0; i < files.length; i++) {
                        var file = files[i];
                        if (file.size > maxSize) {
                            errors.push(file.name + ' - exceeds 5MB');
                            continue;
                        }
                        if (!allowedTypes.includes(file.type)) {
                            errors.push(file.name + ' - invalid file type');
                            continue;
                        }
                        validFiles.push(file.name);
                    }

                    if (errors.length) {
                        alert('Some files were rejected:\n' + errors.join('\n'));
                    }

                    if (validFiles.length) {
                        validFiles.forEach(function(name) {
                            $ul.append('<li>' + self.escapeHtml(name) + '</li>');
                        });
                        $list.show();
                    } else {
                        $list.hide();
                        $(this).val('');
                    }
                } else {
                    $list.hide();
                }
            });
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Add box
            $(document).on('click', '#abox-add-box-btn', function(e) {
                e.preventDefault();
                self.addBox();
            });

            // Remove box
            $(document).on('click', '.abox-remove-box', function(e) {
                e.preventDefault();
                if (confirm(abox_admin_vars.i18n.confirm_remove_box)) {
                    self.removeBox($(this).closest('.abox-box'));
                }
            });

            // Toggle box
            $(document).on('click', '.abox-toggle-box', function(e) {
                e.preventDefault();
                const $box = $(this).closest('.abox-box');
                $box.toggleClass('closed');
                $(this).attr('aria-expanded', !$box.hasClass('closed'));
            });

            // Add item
            $(document).on('click', '.abox-add-item', function(e) {
                e.preventDefault();
                self.addItemRow($(this).closest('.abox-box'));
            });

            // Remove item
            $(document).on('click', '.abox-remove-item', function(e) {
                e.preventDefault();
                self.removeItemRow($(this).closest('.abox-item-row'));
            });

            // Product search
            $(document).on('input', '.abox-product-search', function() {
                self.handleProductSearch($(this));
            });

            // Product search focus
            $(document).on('focus', '.abox-product-search', function() {
                const $results = $(this).closest('.abox-product-search-wrapper').find('.abox-search-results');
                if ($results.children().length > 0) {
                    $results.show();
                }
            });

            // Select product
            $(document).on('click', '.abox-search-result', function(e) {
                e.preventDefault();
                self.selectProduct($(this));
            });

            // Quantity change
            $(document).on('change input', '.abox-quantity', function() {
                self.updateRowTotal($(this).closest('.abox-item-row'));
                self.updateBoxTotal($(this).closest('.abox-box'));
                self.updateSummary();
            });

            // Variation select
            $(document).on('change', '.abox-variation-select', function() {
                self.selectVariation($(this));
            });

            // Close dropdown on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.abox-product-search-wrapper').length) {
                    $('.abox-search-results').hide();
                }
            });

            // Keyboard navigation
            $(document).on('keydown', '.abox-product-search', function(e) {
                const $wrapper = $(this).closest('.abox-product-search-wrapper');
                const $results = $wrapper.find('.abox-search-results');
                const $items = $results.find('.abox-search-result');
                const $active = $items.filter('.active');

                if (e.keyCode === 40) { // Down
                    e.preventDefault();
                    if ($active.length) {
                        $active.removeClass('active').next('.abox-search-result').addClass('active');
                    } else {
                        $items.first().addClass('active');
                    }
                } else if (e.keyCode === 38) { // Up
                    e.preventDefault();
                    if ($active.length) {
                        $active.removeClass('active').prev('.abox-search-result').addClass('active');
                    } else {
                        $items.last().addClass('active');
                    }
                } else if (e.keyCode === 13) { // Enter
                    e.preventDefault();
                    if ($active.length) {
                        self.selectProduct($active);
                    }
                } else if (e.keyCode === 27) { // Escape
                    $results.hide();
                }
            });

            // Ship to different address toggle
            $(document).on('change', '#abox-ship-different', function() {
                if ($(this).is(':checked')) {
                    $('#abox-shipping-fields').slideDown();
                } else {
                    $('#abox-shipping-fields').slideUp();
                }
            });

            // Submit form
            $(document).on('submit', '#abox-admin-order-form', function(e) {
                e.preventDefault();
                self.submitForm();
            });
        },

        /**
         * Add box
         */
        addBox: function() {
            const boxCount = $('.abox-box').length;

            if (boxCount >= abox_admin_vars.max_boxes) {
                this.showMessage(abox_admin_vars.i18n.max_boxes_reached, 'error');
                return;
            }

            const boxIndex = boxCount + 1;
            const template = $('#abox-box-template').html();
            const boxHtml = template.replace(/\{\{index\}\}/g, boxIndex);

            $('#abox-boxes-container').append(boxHtml);

            const $newBox = $('#abox-boxes-container .abox-box').last();
            this.addItemRow($newBox);
            this.updateBoxNumbers();
            this.updateSummary();

            $newBox.find('.abox-customer-label').focus();
        },

        /**
         * Remove box
         */
        removeBox: function($box) {
            if ($('.abox-box').length <= 1) {
                return;
            }

            $box.slideUp(300, function() {
                $(this).remove();
                AboxAdminOrder.updateBoxNumbers();
                AboxAdminOrder.updateSummary();
            });
        },

        /**
         * Add item row
         */
        addItemRow: function($box) {
            const $container = $box.find('.abox-items-container');
            const itemCount = $container.find('.abox-item-row').length;

            if (itemCount >= abox_admin_vars.max_items) {
                this.showMessage(abox_admin_vars.i18n.max_items_reached, 'error');
                return;
            }

            const template = $('#abox-item-template').html();
            $container.append(template);

            $container.find('.abox-item-row').last().find('.abox-product-search').focus();
        },

        /**
         * Remove item row
         */
        removeItemRow: function($row) {
            const $box = $row.closest('.abox-box');
            const $container = $box.find('.abox-items-container');

            $row.fadeOut(200, function() {
                $(this).remove();

                if ($container.find('.abox-item-row').length === 0) {
                    AboxAdminOrder.addItemRow($box);
                }

                AboxAdminOrder.updateBoxTotal($box);
                AboxAdminOrder.updateSummary();
            });
        },

        /**
         * Handle product search
         */
        handleProductSearch: function($input) {
            const self = this;
            const term = $input.val().trim();
            const $wrapper = $input.closest('.abox-product-search-wrapper');
            const $results = $wrapper.find('.abox-search-results');

            clearTimeout(this.searchTimeout);

            // Clear selection if modified
            const $row = $input.closest('.abox-item-row');
            if ($row.find('.abox-product-id').val()) {
                $row.find('.abox-product-id').val('');
                $row.find('.abox-variation-id').val('');
                $row.find('.abox-product-type').val('');
                $row.find('.abox-product-price').val('0');
                $row.find('.abox-price-display').text('—');
                $row.find('.abox-variation-select-wrapper').hide();
                $row.find('.abox-variation-select').html('<option value="">' + abox_admin_vars.i18n.select_variation + '</option>');
                this.updateRowTotal($row);
                this.updateBoxTotal($row.closest('.abox-box'));
                this.updateSummary();
            }

            if (term.length < 2) {
                $results.hide().empty();
                return;
            }

            this.searchTimeout = setTimeout(function() {
                $.ajax({
                    url: abox_admin_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'abox_admin_search_products',
                        nonce: abox_admin_vars.nonce,
                        term: term
                    },
                    beforeSend: function() {
                        $results.html('<div class="abox-loading">' + abox_admin_vars.i18n.searching + '</div>').show();
                    },
                    success: function(response) {
                        if (response.success && response.data.products.length > 0) {
                            let html = '';
                            response.data.products.forEach(function(product) {
                                html += self.getSearchResultTemplate(product);
                            });
                            $results.html(html).show();
                        } else {
                            $results.html('<div class="abox-no-results">' + abox_admin_vars.i18n.no_results + '</div>').show();
                        }
                    },
                    error: function() {
                        $results.html('<div class="abox-error">' + abox_admin_vars.i18n.error_occurred + '</div>').show();
                    }
                });
            }, 300);
        },

        /**
         * Select product
         */
        selectProduct: function($result) {
            const self = this;
            const $row = $result.closest('.abox-item-row');
            const productData = $result.data();

            $row.find('.abox-product-search').val(productData.name);
            $row.find('.abox-product-id').val(productData.id);
            $row.find('.abox-product-type').val(productData.type || 'simple');
            $row.find('.abox-search-results').hide();

            if (productData.type === 'variable') {
                $row.find('.abox-variation-id').val('');
                $row.find('.abox-product-price').val('0');
                $row.find('.abox-price-display').text('—');
                $row.find('.abox-row-total').text('—');

                const $variationWrapper = $row.find('.abox-variation-select-wrapper');
                const $variationSelect = $row.find('.abox-variation-select');
                const $variationLoading = $row.find('.abox-variation-loading');

                $variationWrapper.show();
                $variationSelect.hide();
                $variationLoading.show().addClass('is-active');

                $.ajax({
                    url: abox_admin_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'abox_admin_get_variations',
                        nonce: abox_admin_vars.nonce,
                        product_id: productData.id
                    },
                    success: function(response) {
                        $variationLoading.hide().removeClass('is-active');

                        if (response.success && response.data.variations.length > 0) {
                            let options = '<option value="">' + abox_admin_vars.i18n.select_variation + '</option>';

                            response.data.variations.forEach(function(variation) {
                                const escapedAttrs = variation.attributes ? self.escapeHtml(variation.attributes) : '';
                                const label = escapedAttrs || self.formatPrice(variation.price);
                                options += '<option value="' + variation.variation_id + '" ' +
                                    'data-price="' + variation.price + '" ' +
                                    'data-price-html="' + self.escapeAttr(variation.price_html) + '" ' +
                                    'data-max-qty="' + (variation.max_qty || 999) + '">' +
                                    label + '</option>';
                            });

                            $variationSelect.html(options).show();
                        } else {
                            $variationSelect.html('<option value="">' + abox_admin_vars.i18n.no_variations + '</option>').show();
                        }
                    },
                    error: function() {
                        $variationLoading.hide().removeClass('is-active');
                        $variationSelect.html('<option value="">' + abox_admin_vars.i18n.error_loading + '</option>').show();
                    }
                });

                this.updateBoxTotal($row.closest('.abox-box'));
                this.updateSummary();
            } else {
                $row.find('.abox-variation-select-wrapper').hide();
                $row.find('.abox-variation-id').val('');
                $row.find('.abox-product-price').val(productData.price);
                $row.find('.abox-price-display').html(this.formatPrice(productData.price));

                if (productData.maxQty) {
                    $row.find('.abox-quantity').attr('max', productData.maxQty);
                }

                this.updateRowTotal($row);
                this.updateBoxTotal($row.closest('.abox-box'));
                this.updateSummary();

                $row.find('.abox-quantity').focus().select();
            }
        },

        /**
         * Select variation
         */
        selectVariation: function($select) {
            const $row = $select.closest('.abox-item-row');
            const $option = $select.find('option:selected');
            const variationId = $select.val();

            if (!variationId) {
                $row.find('.abox-variation-id').val('');
                $row.find('.abox-product-price').val('0');
                $row.find('.abox-price-display').text('—');
                $row.find('.abox-row-total').text('—');
                this.updateBoxTotal($row.closest('.abox-box'));
                this.updateSummary();
                return;
            }

            const price = $option.data('price');
            const maxQty = $option.data('maxQty');

            $row.find('.abox-variation-id').val(variationId);
            $row.find('.abox-product-price').val(price);
            $row.find('.abox-price-display').html(this.formatPrice(price));

            if (maxQty) {
                $row.find('.abox-quantity').attr('max', maxQty);
            }

            this.updateRowTotal($row);
            this.updateBoxTotal($row.closest('.abox-box'));
            this.updateSummary();

            $row.find('.abox-quantity').focus().select();
        },

        /**
         * Update row total
         */
        updateRowTotal: function($row) {
            const price = parseFloat($row.find('.abox-product-price').val()) || 0;
            const qty = parseInt($row.find('.abox-quantity').val()) || 0;
            const total = price * qty;

            if (price > 0 && qty > 0) {
                $row.find('.abox-row-total').html(this.formatPrice(total));
            } else {
                $row.find('.abox-row-total').text('—');
            }
        },

        /**
         * Update box total
         */
        updateBoxTotal: function($box) {
            let total = 0;

            $box.find('.abox-item-row').each(function() {
                const price = parseFloat($(this).find('.abox-product-price').val()) || 0;
                const qty = parseInt($(this).find('.abox-quantity').val()) || 0;
                total += price * qty;
            });

            $box.find('.abox-box-total-value').html(this.formatPrice(total));
        },

        /**
         * Update summary
         */
        updateSummary: function() {
            let totalBoxes = $('.abox-box').length;
            let totalItems = 0;
            let grandTotal = 0;

            $('.abox-box').each(function() {
                $(this).find('.abox-item-row').each(function() {
                    const productId = $(this).find('.abox-product-id').val();
                    const qty = parseInt($(this).find('.abox-quantity').val()) || 0;
                    const price = parseFloat($(this).find('.abox-product-price').val()) || 0;

                    if (productId && qty > 0) {
                        totalItems += qty;
                        grandTotal += price * qty;
                    }
                });
            });

            $('#abox-total-boxes').text(totalBoxes);
            $('#abox-total-items').text(totalItems);
            $('#abox-grand-total').html(this.formatPrice(grandTotal));
        },

        /**
         * Update box numbers
         */
        updateBoxNumbers: function() {
            $('.abox-box').each(function(index) {
                $(this).attr('data-box-index', index + 1);
                $(this).find('.abox-box-number').text(index + 1);
            });
        },

        /**
         * Fetch customer billing/shipping details via AJAX
         */
        fetchCustomerDetails: function(customerId) {
            var self = this;

            $.ajax({
                url: abox_admin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'abox_admin_get_customer_details',
                    nonce: abox_admin_vars.nonce,
                    customer_id: customerId
                },
                success: function(response) {
                    if (response.success) {
                        var b = response.data.billing;
                        $('#abox-billing-first-name').val(b.first_name || '');
                        $('#abox-billing-last-name').val(b.last_name || '');
                        $('#abox-billing-email').val(b.email || '');
                        $('#abox-billing-phone').val(b.phone || '');
                        $('#abox-billing-address-1').val(b.address_1 || '');
                        $('#abox-billing-address-2').val(b.address_2 || '');
                        $('#abox-billing-city').val(b.city || '');
                        $('#abox-billing-state').val(b.state || '');
                        $('#abox-billing-postcode').val(b.postcode || '');
                        $('#abox-billing-country').val(b.country || '');

                        var s = response.data.shipping;
                        $('#abox-shipping-first-name').val(s.first_name || '');
                        $('#abox-shipping-last-name').val(s.last_name || '');
                        $('#abox-shipping-address-1').val(s.address_1 || '');
                        $('#abox-shipping-address-2').val(s.address_2 || '');
                        $('#abox-shipping-city').val(s.city || '');
                        $('#abox-shipping-state').val(s.state || '');
                        $('#abox-shipping-postcode').val(s.postcode || '');
                        $('#abox-shipping-country').val(s.country || '');
                    }
                }
            });
        },

        /**
         * Clear billing fields
         */
        clearBillingFields: function() {
            $('#abox-billing-first-name, #abox-billing-last-name, #abox-billing-email, #abox-billing-phone').val('');
            $('#abox-billing-address-1, #abox-billing-address-2, #abox-billing-city, #abox-billing-state, #abox-billing-postcode').val('');
            $('#abox-billing-country').val('');
        },

        /**
         * Clear shipping fields
         */
        clearShippingFields: function() {
            $('#abox-shipping-first-name, #abox-shipping-last-name').val('');
            $('#abox-shipping-address-1, #abox-shipping-address-2, #abox-shipping-city, #abox-shipping-state, #abox-shipping-postcode').val('');
            $('#abox-shipping-country').val('');
            $('#abox-ship-different').prop('checked', false);
            $('#abox-shipping-fields').hide();
        },

        /**
         * Collect form data
         */
        collectFormData: function() {
            const boxes = [];

            $('.abox-box').each(function() {
                const $box = $(this);
                const box = {
                    label: $box.find('.abox-customer-label').val().trim(),
                    items: []
                };

                $box.find('.abox-item-row').each(function() {
                    const $row = $(this);
                    const productId = $row.find('.abox-product-id').val();
                    const variationId = $row.find('.abox-variation-id').val();
                    const quantity = parseInt($row.find('.abox-quantity').val()) || 0;

                    if (productId && quantity > 0) {
                        const item = {
                            product_id: productId,
                            quantity: quantity
                        };

                        if (variationId) {
                            item.variation_id = variationId;
                        }

                        box.items.push(item);
                    }
                });

                boxes.push(box);
            });

            return boxes;
        },

        /**
         * Validate form
         */
        validateForm: function(boxes) {
            if (boxes.length === 0) {
                return { valid: false, message: abox_admin_vars.i18n.error_no_boxes };
            }

            for (let i = 0; i < boxes.length; i++) {
                if (boxes[i].items.length === 0) {
                    return { valid: false, message: abox_admin_vars.i18n.error_no_items };
                }
            }

            // Check for variable products without variation
            let missingVariation = false;
            $('.abox-box').each(function() {
                $(this).find('.abox-item-row').each(function() {
                    const $row = $(this);
                    const productId = $row.find('.abox-product-id').val();
                    const productType = $row.find('.abox-product-type').val();
                    const variationId = $row.find('.abox-variation-id').val();

                    if (productId && productType === 'variable' && !variationId) {
                        missingVariation = true;
                        return false;
                    }
                });

                if (missingVariation) {
                    return false;
                }
            });

            if (missingVariation) {
                return { valid: false, message: abox_admin_vars.i18n.error_select_variation };
            }

            return { valid: true };
        },

        /**
         * Submit form
         */
        submitForm: function() {
            const self = this;
            const boxes = this.collectFormData();

            const validation = this.validateForm(boxes);
            if (!validation.valid) {
                this.showMessage(validation.message, 'error');
                return;
            }

            const $submitBtn = $('#abox-submit-btn');
            const originalText = $submitBtn.text();

            // Collect billing data
            const billing = {};
            $('#abox-billing-details input, #abox-billing-details select').each(function() {
                const name = $(this).attr('name');
                if (name && name.indexOf('billing[') === 0) {
                    const key = name.replace('billing[', '').replace(']', '');
                    billing[key] = $(this).val();
                }
            });

            // Collect shipping data
            const shipping = {};
            const shipDifferent = $('#abox-ship-different').is(':checked');
            if (shipDifferent) {
                $('#abox-shipping-fields input, #abox-shipping-fields select').each(function() {
                    const name = $(this).attr('name');
                    if (name && name.indexOf('shipping[') === 0) {
                        const key = name.replace('shipping[', '').replace(']', '');
                        shipping[key] = $(this).val();
                    }
                });
            }

            // Use FormData to support file uploads
            var formData = new FormData();
            formData.append('action', 'abox_admin_create_order');
            formData.append('nonce', abox_admin_vars.nonce);
            formData.append('customer_id', $('#abox-customer-select').val() || 0);
            formData.append('order_status', $('#abox-order-status').val());

            // Append boxes as JSON
            formData.append('boxes_json', JSON.stringify(boxes));

            // Append billing
            $.each(billing, function(key, val) {
                formData.append('billing[' + key + ']', val);
            });

            // Append shipping
            if (shipDifferent) {
                formData.append('ship_to_different', '1');
                $.each(shipping, function(key, val) {
                    formData.append('shipping[' + key + ']', val);
                });
            }

            // Payment & Collection fields
            formData.append('payment_status', $('#abox-payment-status').val());
            formData.append('collection_method', $('#abox-collection-method').val());
            formData.append('pickup_cod_date', $('#abox-pickup-cod-date').val());
            formData.append('pickup_cod_time', $('#abox-pickup-cod-time').val());

            // Receipt notes
            formData.append('receipt_notes', $('#abox-receipt-notes').val());

            // Append receipt files
            var receiptFiles = $('#abox-receipt-files')[0].files;
            if (receiptFiles.length) {
                for (var i = 0; i < receiptFiles.length; i++) {
                    formData.append('receipt_files[]', receiptFiles[i]);
                }
            }

            $.ajax({
                url: abox_admin_vars.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $submitBtn.prop('disabled', true).text(abox_admin_vars.i18n.creating_order);
                    self.hideMessage();
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message + ' <a href="' + response.data.edit_url + '">' + 'View Order #' + response.data.order_id + '</a>', 'success');

                        // Redirect to edit order page after short delay
                        setTimeout(function() {
                            window.location.href = response.data.edit_url;
                        }, 1500);
                    } else {
                        self.showMessage(response.data.message, 'error');
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    self.showMessage(abox_admin_vars.i18n.error_occurred, 'error');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            const $messages = $('#abox-messages');
            const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';

            $messages
                .removeClass('notice-error notice-success')
                .addClass('notice ' + noticeClass)
                .html('<p>' + message + '</p>')
                .show();

            $('html, body').animate({
                scrollTop: $messages.offset().top - 50
            }, 300);
        },

        /**
         * Hide message
         */
        hideMessage: function() {
            $('#abox-messages').hide().empty();
        },

        /**
         * Get search result template
         */
        getSearchResultTemplate: function(product) {
            return '<div class="abox-search-result" ' +
                'data-id="' + product.id + '" ' +
                'data-name="' + this.escapeAttr(product.name) + '" ' +
                'data-price="' + product.price + '" ' +
                'data-price-html="' + this.escapeAttr(product.price_html) + '" ' +
                'data-max-qty="' + (product.max_qty || 999) + '" ' +
                'data-type="' + (product.type || 'simple') + '">' +
                '<div class="abox-result-info">' +
                '<span class="abox-result-name">' + this.escapeHtml(product.name) + '</span>' +
                '</div>' +
                '</div>';
        },

        /**
         * Format price
         */
        formatPrice: function(price) {
            const currency = abox_admin_vars.currency;
            const formatted = price.toFixed(currency.decimals)
                .replace('.', currency.decimal)
                .replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousand);

            switch (currency.position) {
                case 'left':
                    return currency.symbol + formatted;
                case 'right':
                    return formatted + currency.symbol;
                case 'left_space':
                    return currency.symbol + ' ' + formatted;
                case 'right_space':
                    return formatted + ' ' + currency.symbol;
                default:
                    return currency.symbol + formatted;
            }
        },

        /**
         * Escape HTML for text content
         */
        escapeHtml: function(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        /**
         * Escape string for use in HTML attributes
         */
        escapeAttr: function(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }
    };

    $(document).ready(function() {
        if ($('#abox-admin-order-form').length) {
            AboxAdminOrder.init();
        }
    });

})(jQuery);
