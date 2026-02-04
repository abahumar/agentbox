/**
 * Admin Box Editor
 *
 * @package Agent_Box_Orders
 */

(function($) {
    'use strict';

    var AboxEditor = {
        orderId: 0,
        originalData: null,
        currentData: null,
        hasChanges: false,
        searchTimeout: null,

        init: function() {
            this.orderId = aboxEditorData.orderId;
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Edit mode toggle
            $(document).on('click', '.abox-edit-btn', function(e) {
                e.preventDefault();
                self.enterEditMode();
            });

            // Cancel edit
            $(document).on('click', '.abox-cancel-btn', function(e) {
                e.preventDefault();
                self.exitEditMode();
            });

            // Save changes
            $(document).on('click', '.abox-save-btn', function(e) {
                e.preventDefault();
                self.saveChanges();
            });

            // Accordion toggle
            $(document).on('click', '.abox-accordion-header', function(e) {
                if ($(e.target).is('input, button')) return;
                $(this).closest('.abox-accordion-item').toggleClass('open');
            });

            // Label change
            $(document).on('change', '.abox-accordion-label input', function() {
                self.markChanged();
            });

            // Quantity change
            $(document).on('change', '.abox-qty-input', function() {
                var $row = $(this).closest('tr');
                var qty = parseInt($(this).val()) || 0;
                var price = parseFloat($row.data('price')) || 0;
                $row.find('.abox-item-subtotal').text(self.formatPrice(qty * price));
                self.updateBoxTotal($(this).closest('.abox-accordion-item'));
                self.markChanged();
            });

            // Remove item
            $(document).on('click', '.abox-item-remove', function(e) {
                e.preventDefault();
                var $row = $(this).closest('tr');
                var $accordion = $row.closest('.abox-accordion-item');
                $row.remove();
                self.updateBoxTotal($accordion);
                self.updateBoxSummary($accordion);
                self.markChanged();
            });

            // Remove box
            $(document).on('click', '.abox-accordion-remove', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (confirm(aboxEditorData.i18n.confirmRemoveBox)) {
                    $(this).closest('.abox-accordion-item').remove();
                    self.markChanged();
                }
            });

            // Add box
            $(document).on('click', '.abox-add-box-btn', function(e) {
                e.preventDefault();
                self.addBox();
            });

            // Product search
            $(document).on('input', '.abox-product-search-input', function() {
                var $input = $(this);
                var term = $input.val();
                var $container = $input.closest('.abox-product-search-container');

                clearTimeout(self.searchTimeout);

                if (term.length < 2) {
                    $container.find('.abox-search-results').removeClass('visible');
                    return;
                }

                self.searchTimeout = setTimeout(function() {
                    self.searchProducts(term, $container);
                }, 300);
            });

            // Search result click
            $(document).on('click', '.abox-search-result-item', function() {
                var $item = $(this);
                var $container = $item.closest('.abox-product-search-container');
                var $accordion = $container.closest('.abox-accordion-item');

                if ($item.data('type') === 'variable') {
                    self.showVariationSelector($item, $container, $accordion);
                } else {
                    self.addProductToBox($item.data(), $accordion);
                    $container.find('.abox-product-search-input').val('');
                    $container.find('.abox-search-results').removeClass('visible');
                }
            });

            // Variation select
            $(document).on('change', '.abox-variation-select', function() {
                var $select = $(this);
                var $container = $select.closest('.abox-product-search-container');
                var $accordion = $container.closest('.abox-accordion-item');
                var data = $select.find(':selected').data();

                if (data && data.variationId) {
                    self.addProductToBox({
                        id: $select.data('productId'),
                        variationId: data.variationId,
                        name: $select.data('productName') + ' - ' + data.attributes,
                        price: data.price,
                        sku: data.sku || ''
                    }, $accordion);

                    $container.find('.abox-variation-selector').remove();
                    $container.find('.abox-product-search-input').val('');
                    $container.find('.abox-search-results').removeClass('visible');
                }
            });

            // Close search results on click outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.abox-product-search-container').length) {
                    $('.abox-search-results').removeClass('visible');
                    $('.abox-variation-selector').remove();
                }
            });

            // View history
            $(document).on('click', '.abox-edit-history-link', function(e) {
                e.preventDefault();
                self.showEditHistory();
            });

            // Close history modal
            $(document).on('click', '.abox-edit-history-modal', function(e) {
                if ($(e.target).is('.abox-edit-history-modal') || $(e.target).is('.abox-history-close')) {
                    $('.abox-edit-history-modal').removeClass('visible');
                }
            });
        },

        enterEditMode: function() {
            var self = this;
            var $container = $('#abox-boxes-breakdown .inside');

            // Store original HTML
            this.originalData = $container.html();

            // Load edit mode template via AJAX
            $.ajax({
                url: aboxEditorData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abox_admin_get_edit_template',
                    order_id: this.orderId,
                    nonce: aboxEditorData.nonce
                },
                beforeSend: function() {
                    $container.html('<p>' + aboxEditorData.i18n.loading + '</p>');
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                        self.currentData = response.data.boxes;
                    } else {
                        alert(response.data.message || aboxEditorData.i18n.error);
                        $container.html(self.originalData);
                    }
                },
                error: function() {
                    alert(aboxEditorData.i18n.error);
                    $container.html(self.originalData);
                }
            });
        },

        exitEditMode: function() {
            if (this.hasChanges && !confirm(aboxEditorData.i18n.confirmDiscard)) {
                return;
            }

            var $container = $('#abox-boxes-breakdown .inside');
            $container.html(this.originalData);
            this.hasChanges = false;
        },

        markChanged: function() {
            this.hasChanges = true;
            $('.abox-unsaved-indicator').show();
        },

        saveChanges: function() {
            var self = this;
            var boxes = this.collectBoxData();

            if (!this.validateBoxes(boxes)) {
                return;
            }

            $.ajax({
                url: aboxEditorData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abox_admin_save_boxes',
                    order_id: this.orderId,
                    boxes: boxes,
                    nonce: aboxEditorData.nonce
                },
                beforeSend: function() {
                    $('.abox-save-btn').prop('disabled', true).text(aboxEditorData.i18n.saving);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || aboxEditorData.i18n.error);
                        $('.abox-save-btn').prop('disabled', false).text(aboxEditorData.i18n.save);
                    }
                },
                error: function() {
                    alert(aboxEditorData.i18n.error);
                    $('.abox-save-btn').prop('disabled', false).text(aboxEditorData.i18n.save);
                }
            });
        },

        collectBoxData: function() {
            var boxes = [];

            $('.abox-accordion-item').each(function() {
                var $box = $(this);
                var label = $box.find('.abox-accordion-label input').val();
                var items = [];

                $box.find('.abox-edit-items-table tbody tr').each(function() {
                    var $row = $(this);
                    items.push({
                        product_id: $row.data('productId'),
                        variation_id: $row.data('variationId') || 0,
                        product_name: $row.data('productName'),
                        variation_attrs: $row.data('variationAttrs') || '',
                        quantity: parseInt($row.find('.abox-qty-input').val()) || 0,
                        price: parseFloat($row.data('price')) || 0
                    });
                });

                boxes.push({
                    label: label,
                    items: items
                });
            });

            return boxes;
        },

        validateBoxes: function(boxes) {
            if (boxes.length === 0) {
                alert(aboxEditorData.i18n.noBoxes);
                return false;
            }

            for (var i = 0; i < boxes.length; i++) {
                if (!boxes[i].label.trim()) {
                    alert(aboxEditorData.i18n.emptyLabel);
                    return false;
                }

                if (boxes[i].items.length === 0) {
                    alert(aboxEditorData.i18n.emptyBox.replace('%s', boxes[i].label));
                    return false;
                }

                for (var j = 0; j < boxes[i].items.length; j++) {
                    if (boxes[i].items[j].quantity < 1) {
                        alert(aboxEditorData.i18n.invalidQty);
                        return false;
                    }
                }
            }

            return true;
        },

        searchProducts: function(term, $container) {
            var self = this;
            var $results = $container.find('.abox-search-results');

            $.ajax({
                url: aboxEditorData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abox_admin_search_products',
                    term: term,
                    nonce: aboxEditorData.nonce
                },
                beforeSend: function() {
                    $results.html('<div class="abox-search-loading">' + aboxEditorData.i18n.searching + '</div>').addClass('visible');
                },
                success: function(response) {
                    if (response.success && response.data.products.length > 0) {
                        var html = '';
                        $.each(response.data.products, function(i, product) {
                            html += '<div class="abox-search-result-item" ' +
                                'data-id="' + product.id + '" ' +
                                'data-name="' + self.escapeHtml(product.name) + '" ' +
                                'data-price="' + product.price + '" ' +
                                'data-sku="' + (product.sku || '') + '" ' +
                                'data-type="' + product.type + '">' +
                                '<img src="' + (product.image || aboxEditorData.placeholder) + '" alt="">' +
                                '<div class="abox-search-result-info">' +
                                '<div class="abox-search-result-name">' + self.escapeHtml(product.name) + '</div>' +
                                '<div class="abox-search-result-meta">' +
                                (product.sku ? 'SKU: ' + product.sku + ' | ' : '') +
                                product.price_html +
                                '</div></div></div>';
                        });
                        $results.html(html);
                    } else {
                        $results.html('<div class="abox-search-no-results">' + aboxEditorData.i18n.noResults + '</div>');
                    }
                }
            });
        },

        showVariationSelector: function($item, $container, $accordion) {
            var self = this;
            var productId = $item.data('id');

            // Remove any existing variation selector
            $container.find('.abox-variation-selector').remove();

            $.ajax({
                url: aboxEditorData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abox_admin_get_variations',
                    product_id: productId,
                    nonce: aboxEditorData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.variations.length > 0) {
                        var html = '<div class="abox-variation-selector">' +
                            '<select class="abox-variation-select" data-product-id="' + productId + '" data-product-name="' + self.escapeHtml($item.data('name')) + '">' +
                            '<option value="">' + aboxEditorData.i18n.selectVariation + '</option>';

                        $.each(response.data.variations, function(i, v) {
                            html += '<option value="' + v.variation_id + '" ' +
                                'data-variation-id="' + v.variation_id + '" ' +
                                'data-attributes="' + self.escapeHtml(v.attributes) + '" ' +
                                'data-price="' + v.price + '" ' +
                                'data-sku="' + (v.sku || '') + '">' +
                                v.attributes + ' - ' + v.display_price +
                                '</option>';
                        });

                        html += '</select></div>';
                        $container.find('.abox-search-results').after(html);
                    } else {
                        alert(aboxEditorData.i18n.noVariations);
                    }
                }
            });
        },

        addProductToBox: function(productData, $accordion) {
            var $tbody = $accordion.find('.abox-edit-items-table tbody');
            var html = '<tr data-product-id="' + productData.id + '" ' +
                'data-variation-id="' + (productData.variationId || 0) + '" ' +
                'data-product-name="' + this.escapeHtml(productData.name) + '" ' +
                'data-variation-attrs="' + (productData.variationAttrs || '') + '" ' +
                'data-price="' + productData.price + '">' +
                '<td class="abox-col-product">' + this.escapeHtml(productData.name) +
                (productData.sku ? ' <span class="abox-product-sku">(' + productData.sku + ')</span>' : '') +
                '</td>' +
                '<td class="abox-col-qty"><input type="number" class="abox-qty-input" value="1" min="1"></td>' +
                '<td class="abox-col-price">' + this.formatPrice(productData.price) + '</td>' +
                '<td class="abox-col-subtotal abox-item-subtotal">' + this.formatPrice(productData.price) + '</td>' +
                '<td class="abox-col-actions"><button type="button" class="abox-item-remove">&times;</button></td>' +
                '</tr>';

            $tbody.append(html);
            this.updateBoxTotal($accordion);
            this.updateBoxSummary($accordion);
            this.markChanged();
        },

        addBox: function() {
            var boxCount = $('.abox-accordion-item').length + 1;
            var html = '<div class="abox-accordion-item open">' +
                '<div class="abox-accordion-header">' +
                '<span class="abox-accordion-toggle dashicons dashicons-arrow-right-alt2"></span>' +
                '<div class="abox-accordion-label"><input type="text" value="" placeholder="' + aboxEditorData.i18n.newBoxLabel + '"></div>' +
                '<div class="abox-accordion-summary"><span class="abox-box-items">0 items</span><span class="abox-box-total">' + this.formatPrice(0) + '</span></div>' +
                '<button type="button" class="abox-accordion-remove" title="' + aboxEditorData.i18n.removeBox + '">&times;</button>' +
                '</div>' +
                '<div class="abox-accordion-content">' +
                '<table class="abox-edit-items-table">' +
                '<thead><tr>' +
                '<th class="abox-col-product">' + aboxEditorData.i18n.product + '</th>' +
                '<th class="abox-col-qty">' + aboxEditorData.i18n.qty + '</th>' +
                '<th class="abox-col-price">' + aboxEditorData.i18n.price + '</th>' +
                '<th class="abox-col-subtotal">' + aboxEditorData.i18n.subtotal + '</th>' +
                '<th class="abox-col-actions"></th>' +
                '</tr></thead>' +
                '<tbody></tbody>' +
                '</table>' +
                '<div class="abox-product-search-container">' +
                '<input type="text" class="abox-product-search-input" placeholder="' + aboxEditorData.i18n.searchPlaceholder + '">' +
                '<div class="abox-search-results"></div>' +
                '</div>' +
                '</div></div>';

            $('.abox-add-box-container').before(html);
            this.markChanged();
        },

        updateBoxTotal: function($accordion) {
            var total = 0;
            $accordion.find('.abox-edit-items-table tbody tr').each(function() {
                var qty = parseInt($(this).find('.abox-qty-input').val()) || 0;
                var price = parseFloat($(this).data('price')) || 0;
                total += qty * price;
            });
            $accordion.find('.abox-box-total').text(this.formatPrice(total));
        },

        updateBoxSummary: function($accordion) {
            var itemCount = 0;
            $accordion.find('.abox-edit-items-table tbody tr').each(function() {
                itemCount += parseInt($(this).find('.abox-qty-input').val()) || 0;
            });
            $accordion.find('.abox-box-items').text(itemCount + ' ' + (itemCount === 1 ? aboxEditorData.i18n.item : aboxEditorData.i18n.items));
        },

        showEditHistory: function() {
            var self = this;

            $.ajax({
                url: aboxEditorData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'abox_admin_get_edit_history',
                    order_id: this.orderId,
                    nonce: aboxEditorData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderHistoryModal(response.data.history);
                    } else {
                        alert(response.data.message || aboxEditorData.i18n.error);
                    }
                }
            });
        },

        renderHistoryModal: function(history) {
            var html = '<div class="abox-edit-history-modal visible">' +
                '<div class="abox-edit-history-content">' +
                '<h3>' + aboxEditorData.i18n.editHistory + ' <button type="button" class="abox-history-close button">&times;</button></h3>';

            if (history.length === 0) {
                html += '<p>' + aboxEditorData.i18n.noHistory + '</p>';
            } else {
                $.each(history, function(i, entry) {
                    html += '<div class="abox-history-entry">' +
                        '<div class="abox-history-entry-header">' +
                        '<span>' + entry.user_name + '</span>' +
                        '<span>' + entry.timestamp + '</span>' +
                        '</div>' +
                        '<ul class="abox-history-entry-changes">';

                    $.each(entry.changes, function(j, change) {
                        html += '<li>' + change.description + '</li>';
                    });

                    html += '</ul></div>';
                });
            }

            html += '</div></div>';

            $('body').append(html);
        },

        formatPrice: function(price) {
            return aboxEditorData.currencySymbol + parseFloat(price).toFixed(aboxEditorData.decimals);
        },

        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        if (typeof aboxEditorData !== 'undefined') {
            AboxEditor.init();
        }
    });

})(jQuery);
