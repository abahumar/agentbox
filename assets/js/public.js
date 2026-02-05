/**
 * Agent Box Orders - Public JavaScript
 */
(function($) {
    'use strict';

    const AboxOrderForm = {
        searchTimeout: null,

        /**
         * Initialize the order form
         */
        init: function() {
            this.bindEvents();
            this.addBox(); // Start with one box
            this.updateSummary();
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            const self = this;

            // Add box button
            $(document).on('click', '#abox-add-box-btn, .abox-add-box', function(e) {
                e.preventDefault();
                self.addBox();
            });

            // Remove box button
            $(document).on('click', '.abox-remove-box', function(e) {
                e.preventDefault();
                if (confirm(abox_vars.i18n.confirm_remove_box)) {
                    self.removeBox($(this).closest('.abox-box'));
                }
            });

            // Add item button
            $(document).on('click', '.abox-add-item', function(e) {
                e.preventDefault();
                self.addItemRow($(this).closest('.abox-box'));
            });

            // Remove item button
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

            // Select product from dropdown
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

            // Submit form
            $(document).on('submit', '#abox-order-form', function(e) {
                e.preventDefault();
                self.submitForm();
            });

            // Close search dropdown on outside click
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.abox-product-search-wrapper').length) {
                    $('.abox-search-results').hide();
                }
            });

            // Keyboard navigation for search results
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

            // Variation select change
            $(document).on('change', '.abox-variation-select', function() {
                self.selectVariation($(this));
            });
        },

        /**
         * Add a new box
         */
        addBox: function() {
            const boxCount = $('.abox-box').length;

            if (boxCount >= abox_vars.max_boxes) {
                this.showMessage(abox_vars.i18n.max_boxes_reached, 'error');
                return;
            }

            const boxIndex = boxCount + 1;
            const template = $('#abox-box-template').html();
            const boxHtml = template.replace(/\{\{index\}\}/g, boxIndex);

            $('#abox-boxes-container').append(boxHtml);

            // Add first item row
            const $newBox = $('#abox-boxes-container .abox-box').last();
            this.addItemRow($newBox);
            this.updateBoxNumbers();
            this.updateSummary();

            // Focus on customer label
            $newBox.find('.abox-customer-label').focus();
        },

        /**
         * Remove a box
         */
        removeBox: function($box) {
            // Don't allow removing the last box
            if ($('.abox-box').length <= 1) {
                return;
            }

            $box.slideUp(300, function() {
                $(this).remove();
                AboxOrderForm.updateBoxNumbers();
                AboxOrderForm.updateSummary();
            });
        },

        /**
         * Add an item row to a box
         */
        addItemRow: function($box) {
            const $itemsContainer = $box.find('.abox-items-container');
            const itemCount = $itemsContainer.find('.abox-item-row').length;

            if (itemCount >= abox_vars.max_items) {
                this.showMessage(abox_vars.i18n.max_items_reached, 'error');
                return;
            }

            const template = $('#abox-item-template').html();
            $itemsContainer.append(template);

            // Focus on search field
            $itemsContainer.find('.abox-item-row').last().find('.abox-product-search').focus();
        },

        /**
         * Remove an item row
         */
        removeItemRow: function($row) {
            const $box = $row.closest('.abox-box');
            const $container = $box.find('.abox-items-container');

            $row.slideUp(200, function() {
                $(this).remove();

                // Ensure at least one row remains
                if ($container.find('.abox-item-row').length === 0) {
                    AboxOrderForm.addItemRow($box);
                }

                AboxOrderForm.updateBoxTotal($box);
                AboxOrderForm.updateSummary();
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

            // Clear product selection if search field is modified
            const $row = $input.closest('.abox-item-row');
            if ($row.find('.abox-product-id').val()) {
                $row.find('.abox-product-id').val('');
                $row.find('.abox-variation-id').val('');
                $row.find('.abox-product-type').val('');
                $row.find('.abox-product-price').val('0');
                $row.find('.abox-price-display').text('-');
                $row.find('.abox-variation-select-wrapper').hide();
                $row.find('.abox-variation-select').html('<option value="">' + (abox_vars.i18n.select_variation || 'Select variation...') + '</option>');
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
                    url: abox_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'abox_search_products',
                        nonce: abox_vars.nonce,
                        term: term
                    },
                    beforeSend: function() {
                        $results.html('<div class="abox-loading">' + abox_vars.i18n.searching + '</div>').show();
                    },
                    success: function(response) {
                        if (response.success && response.data.products.length > 0) {
                            let html = '';
                            response.data.products.forEach(function(product) {
                                html += self.getSearchResultTemplate(product);
                            });
                            $results.html(html).show();
                        } else {
                            $results.html('<div class="abox-no-results">' + abox_vars.i18n.no_results + '</div>').show();
                        }
                    },
                    error: function() {
                        $results.html('<div class="abox-error">' + abox_vars.i18n.error_occurred + '</div>').show();
                    }
                });
            }, 300);
        },

        /**
         * Select a product from search results
         */
        selectProduct: function($result) {
            const self = this;
            const $row = $result.closest('.abox-item-row');
            const productData = $result.data();

            $row.find('.abox-product-search').val(productData.name);
            $row.find('.abox-product-id').val(productData.id);
            $row.find('.abox-product-type').val(productData.type || 'simple');
            $row.find('.abox-search-results').hide();

            // Check if this is a variable product
            if (productData.type === 'variable') {
                // Clear variation data and show loading
                $row.find('.abox-variation-id').val('');
                $row.find('.abox-product-price').val('0');
                $row.find('.abox-price-display').text('-');
                $row.find('.abox-row-total').text('-');

                const $variationWrapper = $row.find('.abox-variation-select-wrapper');
                const $variationSelect = $row.find('.abox-variation-select');
                const $variationLoading = $row.find('.abox-variation-loading');

                $variationWrapper.show();
                $variationSelect.hide();
                $variationLoading.show();

                // Fetch variations
                $.ajax({
                    url: abox_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'abox_get_variations',
                        nonce: abox_vars.nonce,
                        product_id: productData.id
                    },
                    success: function(response) {
                        $variationLoading.hide();

                        if (response.success && response.data.variations.length > 0) {
                            let options = '<option value="">' + (abox_vars.i18n.select_variation || 'Select variation...') + '</option>';

                            response.data.variations.forEach(function(variation) {
                                const escapedAttrs = variation.attributes ? self.escapeHtml(variation.attributes) : '';
                                const label = escapedAttrs || self.formatPrice(variation.price);
                                options += '<option value="' + variation.variation_id + '" ' +
                                    'data-price="' + variation.price + '" ' +
                                    'data-price-html="' + self.escapeAttr(variation.price_html) + '" ' +
                                    'data-max-qty="' + (variation.max_qty || 999) + '">' +
                                    label +
                                    '</option>';
                            });

                            $variationSelect.html(options).show();
                        } else {
                            $variationSelect.html('<option value="">' + (abox_vars.i18n.no_variations || 'No variations available') + '</option>').show();
                        }
                    },
                    error: function() {
                        $variationLoading.hide();
                        $variationSelect.html('<option value="">' + (abox_vars.i18n.error_loading || 'Error loading variations') + '</option>').show();
                    }
                });

                this.updateBoxTotal($row.closest('.abox-box'));
                this.updateSummary();
            } else {
                // Simple product - hide variation dropdown
                $row.find('.abox-variation-select-wrapper').hide();
                $row.find('.abox-variation-id').val('');
                $row.find('.abox-product-price').val(productData.price);
                $row.find('.abox-price-display').html(this.formatPrice(productData.price));

                // Update quantity max based on stock
                if (productData.maxQty) {
                    $row.find('.abox-quantity').attr('max', productData.maxQty);
                }

                this.updateRowTotal($row);
                this.updateBoxTotal($row.closest('.abox-box'));
                this.updateSummary();

                // Focus on quantity
                $row.find('.abox-quantity').focus().select();
            }
        },

        /**
         * Select a variation from dropdown
         */
        selectVariation: function($select) {
            const $row = $select.closest('.abox-item-row');
            const $option = $select.find('option:selected');
            const variationId = $select.val();

            if (!variationId) {
                // No variation selected - clear price
                $row.find('.abox-variation-id').val('');
                $row.find('.abox-product-price').val('0');
                $row.find('.abox-price-display').text('-');
                $row.find('.abox-row-total').text('-');
                this.updateBoxTotal($row.closest('.abox-box'));
                this.updateSummary();
                return;
            }

            const price = $option.data('price');
            const maxQty = $option.data('maxQty');

            $row.find('.abox-variation-id').val(variationId);
            $row.find('.abox-product-price').val(price);
            $row.find('.abox-price-display').html(this.formatPrice(price));

            // Update quantity max based on variation stock
            if (maxQty) {
                $row.find('.abox-quantity').attr('max', maxQty);
            }

            this.updateRowTotal($row);
            this.updateBoxTotal($row.closest('.abox-box'));
            this.updateSummary();

            // Focus on quantity
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
                $row.find('.abox-row-total').text('-');
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
         * Update form summary
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
         * Update box numbers after add/remove
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

                        // Include variation_id if present
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
            const self = this;

            if (boxes.length === 0) {
                return { valid: false, message: abox_vars.i18n.error_no_boxes };
            }

            for (let i = 0; i < boxes.length; i++) {
                if (!boxes[i].label) {
                    return { valid: false, message: abox_vars.i18n.error_empty_label };
                }

                if (boxes[i].items.length === 0) {
                    return { valid: false, message: abox_vars.i18n.error_no_items };
                }
            }

            // Check for variable products without variation selected
            let missingVariation = false;
            $('.abox-box').each(function() {
                $(this).find('.abox-item-row').each(function() {
                    const $row = $(this);
                    const productId = $row.find('.abox-product-id').val();
                    const productType = $row.find('.abox-product-type').val();
                    const variationId = $row.find('.abox-variation-id').val();

                    if (productId && productType === 'variable' && !variationId) {
                        missingVariation = true;
                        return false; // Break inner loop
                    }
                });

                if (missingVariation) {
                    return false; // Break outer loop
                }
            });

            if (missingVariation) {
                return { valid: false, message: abox_vars.i18n.error_select_variation || 'Please select a variation for all variable products.' };
            }

            return { valid: true };
        },

        /**
         * Submit the form
         */
        submitForm: function() {
            const self = this;
            const boxes = this.collectFormData();

            // Validate
            const validation = this.validateForm(boxes);
            if (!validation.valid) {
                this.showMessage(validation.message, 'error');
                return;
            }

            const $submitBtn = $('#abox-submit-btn');
            const originalText = $submitBtn.text();

            $.ajax({
                url: abox_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'abox_submit_boxes',
                    nonce: abox_vars.nonce,
                    boxes: boxes
                },
                beforeSend: function() {
                    $submitBtn.prop('disabled', true).text(abox_vars.i18n.submitting);
                    self.hideMessage();
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        window.location.href = response.data.redirect;
                    } else {
                        self.showMessage(response.data.message, 'error');
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    self.showMessage(abox_vars.i18n.error_occurred, 'error');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            const $messages = $('#abox-messages');
            $messages
                .removeClass('abox-error abox-success')
                .addClass('abox-' + type)
                .html(message)
                .show();

            // Scroll to message
            $('html, body').animate({
                scrollTop: $messages.offset().top - 100
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
            const imageHtml = product.image
                ? '<img src="' + this.escapeAttr(product.image) + '" alt="">'
                : '';

            return '<div class="abox-search-result" ' +
                'data-id="' + product.id + '" ' +
                'data-name="' + this.escapeAttr(product.name) + '" ' +
                'data-price="' + product.price + '" ' +
                'data-price-html="' + this.escapeAttr(product.price_html) + '" ' +
                'data-max-qty="' + (product.max_qty || 999) + '" ' +
                'data-type="' + (product.type || 'simple') + '">' +
                imageHtml +
                '<div class="abox-result-info">' +
                '<span class="abox-result-name">' + this.escapeHtml(product.name) + '</span>' +
                '</div>' +
                '</div>';
        },

        /**
         * Format price according to WooCommerce settings
         */
        formatPrice: function(price) {
            const currency = abox_vars.currency;
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

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#abox-order-form').length) {
            AboxOrderForm.init();
        }
    });

})(jQuery);
