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

            // Toggle guest billing fields
            $('#abox-customer-select').on('change', function() {
                const customerId = $(this).val();
                if (customerId && customerId !== '0') {
                    $('#abox-guest-billing').slideUp();
                } else {
                    $('#abox-guest-billing').slideDown();
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
                if (!boxes[i].label) {
                    return { valid: false, message: abox_admin_vars.i18n.error_empty_label };
                }

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
            $('#abox-guest-billing input, #abox-guest-billing select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    const key = name.replace('billing[', '').replace(']', '');
                    billing[key] = $(this).val();
                }
            });

            $.ajax({
                url: abox_admin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'abox_admin_create_order',
                    nonce: abox_admin_vars.nonce,
                    boxes: boxes,
                    customer_id: $('#abox-customer-select').val() || 0,
                    order_status: $('#abox-order-status').val(),
                    billing: billing
                },
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
