# Admin Box Order Editing Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Allow administrators to edit box orders after submission directly from the WooCommerce order edit screen with full audit trail.

**Architecture:** Extend existing meta box with edit mode toggle. New editor class handles AJAX save/search. Changes sync to WooCommerce order line items via `$order->calculate_totals()`. Edit history stored in `_abox_edit_history` order meta.

**Tech Stack:** PHP 7.4+, WordPress/WooCommerce APIs, jQuery, AJAX with nonce verification, HPOS compatible

---

## Task 1: Update Plugin Version

**Files:**
- Modify: `agent-box-orders.php:6` (header version)
- Modify: `agent-box-orders.php:26` (constant)

**Step 1: Update version in plugin header**

Change line 6 from:
```php
 * Version: 1.0.0
```
to:
```php
 * Version: 1.1.0
```

**Step 2: Update version constant**

Change line 26 from:
```php
define( 'ABOX_VERSION', '1.0.0' );
```
to:
```php
define( 'ABOX_VERSION', '1.1.0' );
```

---

## Task 2: Add Setting to Enable Admin Editing

**Files:**
- Modify: `includes/class-abox-settings.php:99-105`

**Step 1: Add new setting field**

After the `abox_guest_mode` setting array (line 105), add this new setting:

```php
array(
    'title'   => __( 'Admin Box Editing', 'agent-box-orders' ),
    'desc'    => __( 'Allow administrators to edit box orders after submission.', 'agent-box-orders' ),
    'id'      => 'abox_enable_admin_editing',
    'type'    => 'checkbox',
    'default' => 'no',
),
```

**Step 2: Add to get_settings method**

In the `get_settings()` method (around line 143-151), add:

```php
'enable_admin_editing' => 'yes' === get_option( 'abox_enable_admin_editing', 'no' ),
```

---

## Task 3: Create Editor CSS File

**Files:**
- Create: `assets/css/admin-editor.css`

**Step 1: Create the CSS file**

```css
/**
 * Admin Box Editor Styles
 *
 * @package Agent_Box_Orders
 */

/* Edit Mode Container */
.abox-edit-mode {
    background-color: #fffbeb;
    border: 1px solid #f59e0b;
    border-radius: 4px;
    padding: 12px;
    margin: -12px;
}

.abox-edit-mode .abox-meta-box-content {
    background: transparent;
}

/* Edit Mode Header */
.abox-edit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.abox-edit-header h3 {
    margin: 0;
    font-size: 14px;
    color: #92400e;
}

.abox-edit-header .abox-unsaved-indicator {
    color: #dc2626;
    font-weight: 600;
    margin-left: 4px;
}

.abox-edit-actions {
    display: flex;
    gap: 8px;
}

/* Box Accordion */
.abox-accordion {
    margin-bottom: 12px;
}

.abox-accordion-item {
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    margin-bottom: 8px;
}

.abox-accordion-header {
    display: flex;
    align-items: center;
    padding: 12px;
    cursor: pointer;
    user-select: none;
    gap: 12px;
}

.abox-accordion-header:hover {
    background-color: #f9fafb;
}

.abox-accordion-toggle {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    transition: transform 0.2s;
}

.abox-accordion-item.open .abox-accordion-toggle {
    transform: rotate(90deg);
}

.abox-accordion-label {
    flex: 1;
    font-weight: 500;
}

.abox-accordion-label input {
    width: 200px;
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
}

.abox-accordion-summary {
    display: flex;
    gap: 16px;
    color: #6b7280;
    font-size: 13px;
}

.abox-accordion-remove {
    color: #9ca3af;
    padding: 4px 8px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 16px;
}

.abox-accordion-remove:hover {
    color: #dc2626;
}

.abox-accordion-content {
    display: none;
    padding: 12px;
    border-top: 1px solid #e5e7eb;
}

.abox-accordion-item.open .abox-accordion-content {
    display: block;
}

/* Items Table in Edit Mode */
.abox-edit-items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
}

.abox-edit-items-table th,
.abox-edit-items-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.abox-edit-items-table th {
    font-weight: 500;
    color: #374151;
    font-size: 12px;
    text-transform: uppercase;
}

.abox-edit-items-table .abox-col-qty {
    width: 80px;
}

.abox-edit-items-table .abox-col-qty input {
    width: 60px;
    padding: 4px;
    text-align: center;
}

.abox-edit-items-table .abox-col-price,
.abox-edit-items-table .abox-col-subtotal {
    width: 100px;
    text-align: right;
}

.abox-edit-items-table .abox-col-actions {
    width: 40px;
    text-align: center;
}

.abox-item-remove {
    color: #9ca3af;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    padding: 4px;
}

.abox-item-remove:hover {
    color: #dc2626;
}

/* Product Search */
.abox-product-search-container {
    position: relative;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
}

.abox-product-search-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 14px;
}

.abox-product-search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.abox-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 100;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    display: none;
}

.abox-search-results.visible {
    display: block;
}

.abox-search-result-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    cursor: pointer;
    gap: 12px;
}

.abox-search-result-item:hover,
.abox-search-result-item.selected {
    background-color: #f3f4f6;
}

.abox-search-result-item img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}

.abox-search-result-info {
    flex: 1;
}

.abox-search-result-name {
    font-weight: 500;
}

.abox-search-result-meta {
    font-size: 12px;
    color: #6b7280;
}

.abox-search-loading,
.abox-search-no-results {
    padding: 12px;
    text-align: center;
    color: #6b7280;
}

/* Add Box Button */
.abox-add-box-container {
    margin-top: 12px;
}

.abox-add-box-btn {
    width: 100%;
    padding: 12px;
    border: 2px dashed #d1d5db;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    border-radius: 4px;
    font-size: 14px;
}

.abox-add-box-btn:hover {
    border-color: #3b82f6;
    color: #3b82f6;
    background-color: #eff6ff;
}

/* Edit History */
.abox-edit-history-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 12px;
    color: #6b7280;
    text-decoration: none;
    font-size: 13px;
}

.abox-edit-history-link:hover {
    color: #3b82f6;
}

.abox-edit-history-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
    align-items: center;
    justify-content: center;
}

.abox-edit-history-modal.visible {
    display: flex;
}

.abox-edit-history-content {
    background: #fff;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    padding: 24px;
}

.abox-edit-history-content h3 {
    margin: 0 0 16px 0;
}

.abox-history-entry {
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    margin-bottom: 8px;
}

.abox-history-entry-header {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 8px;
}

.abox-history-entry-changes {
    font-size: 13px;
}

.abox-history-entry-changes li {
    margin-bottom: 4px;
}

/* Box Total Row */
.abox-box-total-row {
    display: flex;
    justify-content: flex-end;
    padding: 8px;
    font-weight: 500;
    background: #f9fafb;
}

/* Variation Selector */
.abox-variation-selector {
    margin-top: 8px;
    padding: 8px;
    background: #f9fafb;
    border-radius: 4px;
}

.abox-variation-selector select {
    width: 100%;
    padding: 6px;
}

/* Responsive */
@media screen and (max-width: 782px) {
    .abox-edit-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .abox-accordion-summary {
        display: none;
    }
}
```

---

## Task 4: Create Editor JavaScript File

**Files:**
- Create: `assets/js/admin-editor.js`

**Step 1: Create the JavaScript file**

```javascript
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

            var self = this;
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
```

---

## Task 5: Create Meta Box Editor Class

**Files:**
- Create: `admin/class-abox-meta-box-editor.php`

**Step 1: Create the editor class file**

```php
<?php
/**
 * Meta box editor for box orders
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ABOX_Meta_Box_Editor class
 */
class ABOX_Meta_Box_Editor {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // AJAX handlers
        add_action( 'wp_ajax_abox_admin_get_edit_template', array( $this, 'ajax_get_edit_template' ) );
        add_action( 'wp_ajax_abox_admin_save_boxes', array( $this, 'ajax_save_boxes' ) );
        add_action( 'wp_ajax_abox_admin_search_products', array( $this, 'ajax_search_products' ) );
        add_action( 'wp_ajax_abox_admin_get_variations', array( $this, 'ajax_get_variations' ) );
        add_action( 'wp_ajax_abox_admin_get_edit_history', array( $this, 'ajax_get_edit_history' ) );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_scripts( $hook ) {
        // Only load on order edit screens
        if ( ! in_array( $hook, array( 'post.php', 'woocommerce_page_wc-orders' ), true ) ) {
            return;
        }

        // Check if editing is enabled
        if ( 'yes' !== get_option( 'abox_enable_admin_editing', 'no' ) ) {
            return;
        }

        // Get order ID
        $order_id = $this->get_current_order_id();
        if ( ! $order_id ) {
            return;
        }

        // Check if this is a box order
        $order = wc_get_order( $order_id );
        if ( ! $order || 'yes' !== $order->get_meta( '_abox_is_box_order' ) ) {
            return;
        }

        wp_enqueue_style(
            'abox-admin-editor',
            ABOX_PLUGIN_URL . 'assets/css/admin-editor.css',
            array(),
            ABOX_VERSION
        );

        wp_enqueue_script(
            'abox-admin-editor',
            ABOX_PLUGIN_URL . 'assets/js/admin-editor.js',
            array( 'jquery' ),
            ABOX_VERSION,
            true
        );

        wp_localize_script( 'abox-admin-editor', 'aboxEditorData', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'abox_admin_nonce' ),
            'orderId'        => $order_id,
            'currencySymbol' => get_woocommerce_currency_symbol(),
            'decimals'       => wc_get_price_decimals(),
            'placeholder'    => wc_placeholder_img_src(),
            'i18n'           => array(
                'loading'          => __( 'Loading...', 'agent-box-orders' ),
                'saving'           => __( 'Saving...', 'agent-box-orders' ),
                'save'             => __( 'Save Changes', 'agent-box-orders' ),
                'error'            => __( 'An error occurred. Please try again.', 'agent-box-orders' ),
                'confirmDiscard'   => __( 'You have unsaved changes. Are you sure you want to discard them?', 'agent-box-orders' ),
                'confirmRemoveBox' => __( 'Are you sure you want to remove this box?', 'agent-box-orders' ),
                'searching'        => __( 'Searching...', 'agent-box-orders' ),
                'noResults'        => __( 'No products found.', 'agent-box-orders' ),
                'selectVariation'  => __( 'Select a variation...', 'agent-box-orders' ),
                'noVariations'     => __( 'No available variations found.', 'agent-box-orders' ),
                'newBoxLabel'      => __( 'Customer Name', 'agent-box-orders' ),
                'removeBox'        => __( 'Remove box', 'agent-box-orders' ),
                'product'          => __( 'Product', 'agent-box-orders' ),
                'qty'              => __( 'Qty', 'agent-box-orders' ),
                'price'            => __( 'Price', 'agent-box-orders' ),
                'subtotal'         => __( 'Subtotal', 'agent-box-orders' ),
                'searchPlaceholder'=> __( 'Search products to add...', 'agent-box-orders' ),
                'item'             => __( 'item', 'agent-box-orders' ),
                'items'            => __( 'items', 'agent-box-orders' ),
                'noBoxes'          => __( 'At least one box is required.', 'agent-box-orders' ),
                'emptyLabel'       => __( 'All boxes must have a customer label.', 'agent-box-orders' ),
                'emptyBox'         => __( 'Box "%s" has no items.', 'agent-box-orders' ),
                'invalidQty'       => __( 'All quantities must be at least 1.', 'agent-box-orders' ),
                'editHistory'      => __( 'Edit History', 'agent-box-orders' ),
                'noHistory'        => __( 'No edit history available.', 'agent-box-orders' ),
            ),
        ) );
    }

    /**
     * Get current order ID from context
     *
     * @return int|null
     */
    private function get_current_order_id() {
        // HPOS
        if ( isset( $_GET['id'] ) ) {
            return absint( $_GET['id'] );
        }

        // Classic
        if ( isset( $_GET['post'] ) ) {
            return absint( $_GET['post'] );
        }

        return null;
    }

    /**
     * AJAX: Get edit template
     */
    public function ajax_get_edit_template() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'agent-box-orders' ) ) );
        }

        $boxes = $order->get_meta( '_abox_boxes' );

        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            wp_send_json_error( array( 'message' => __( 'No box data available.', 'agent-box-orders' ) ) );
        }

        ob_start();
        include ABOX_PLUGIN_DIR . 'admin/views/meta-box-editor.php';
        $html = ob_get_clean();

        wp_send_json_success( array(
            'html'  => $html,
            'boxes' => $boxes,
        ) );
    }

    /**
     * AJAX: Save boxes
     */
    public function ajax_save_boxes() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $boxes    = isset( $_POST['boxes'] ) ? $_POST['boxes'] : array();

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'agent-box-orders' ) ) );
        }

        // Get old boxes for comparison
        $old_boxes = $order->get_meta( '_abox_boxes' );

        // Sanitize new boxes
        $sanitized_boxes = $this->sanitize_boxes( $boxes );

        if ( is_wp_error( $sanitized_boxes ) ) {
            wp_send_json_error( array( 'message' => $sanitized_boxes->get_error_message() ) );
        }

        // Detect changes
        $changes = $this->detect_changes( $old_boxes, $sanitized_boxes );

        // Update order meta
        $order->update_meta_data( '_abox_boxes', $sanitized_boxes );

        // Update order line items
        $this->sync_order_line_items( $order, $sanitized_boxes );

        // Log changes
        if ( ! empty( $changes ) ) {
            $this->log_changes( $order, $changes );
        }

        // Recalculate totals
        $order->calculate_totals();
        $order->save();

        wp_send_json_success( array( 'message' => __( 'Changes saved successfully.', 'agent-box-orders' ) ) );
    }

    /**
     * Sanitize boxes data
     *
     * @param array $boxes Raw boxes data.
     * @return array|WP_Error
     */
    private function sanitize_boxes( $boxes ) {
        if ( empty( $boxes ) || ! is_array( $boxes ) ) {
            return new WP_Error( 'no_boxes', __( 'At least one box is required.', 'agent-box-orders' ) );
        }

        $sanitized = array();

        foreach ( $boxes as $box ) {
            $label = isset( $box['label'] ) ? sanitize_text_field( wp_unslash( $box['label'] ) ) : '';

            if ( empty( $label ) ) {
                return new WP_Error( 'empty_label', __( 'All boxes must have a customer label.', 'agent-box-orders' ) );
            }

            $items = isset( $box['items'] ) ? $box['items'] : array();

            if ( empty( $items ) || ! is_array( $items ) ) {
                return new WP_Error( 'empty_box', sprintf( __( 'Box "%s" has no items.', 'agent-box-orders' ), $label ) );
            }

            $sanitized_items = array();

            foreach ( $items as $item ) {
                $product_id   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
                $variation_id = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
                $quantity     = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 0;

                if ( ! $product_id || $quantity < 1 ) {
                    continue;
                }

                $sanitized_items[] = array(
                    'product_id'      => $product_id,
                    'variation_id'    => $variation_id,
                    'product_name'    => isset( $item['product_name'] ) ? sanitize_text_field( wp_unslash( $item['product_name'] ) ) : '',
                    'variation_attrs' => isset( $item['variation_attrs'] ) ? sanitize_text_field( wp_unslash( $item['variation_attrs'] ) ) : '',
                    'quantity'        => $quantity,
                    'price'           => isset( $item['price'] ) ? floatval( $item['price'] ) : 0,
                );
            }

            if ( empty( $sanitized_items ) ) {
                return new WP_Error( 'empty_box', sprintf( __( 'Box "%s" has no valid items.', 'agent-box-orders' ), $label ) );
            }

            $sanitized[] = array(
                'label' => $label,
                'items' => $sanitized_items,
            );
        }

        return $sanitized;
    }

    /**
     * Detect changes between old and new boxes
     *
     * @param array $old_boxes Old boxes data.
     * @param array $new_boxes New boxes data.
     * @return array Changes array.
     */
    private function detect_changes( $old_boxes, $new_boxes ) {
        $changes = array();

        // Index old boxes by label for comparison
        $old_by_label = array();
        if ( is_array( $old_boxes ) ) {
            foreach ( $old_boxes as $box ) {
                $old_by_label[ $box['label'] ] = $box;
            }
        }

        $new_by_label = array();
        foreach ( $new_boxes as $box ) {
            $new_by_label[ $box['label'] ] = $box;
        }

        // Check for removed boxes
        foreach ( $old_by_label as $label => $old_box ) {
            if ( ! isset( $new_by_label[ $label ] ) ) {
                $changes[] = array(
                    'type'        => 'box_removed',
                    'box'         => $label,
                    'description' => sprintf( __( 'Box "%s" removed', 'agent-box-orders' ), $label ),
                );
            }
        }

        // Check for added boxes and item changes
        foreach ( $new_by_label as $label => $new_box ) {
            if ( ! isset( $old_by_label[ $label ] ) ) {
                $changes[] = array(
                    'type'        => 'box_added',
                    'box'         => $label,
                    'description' => sprintf( __( 'Box "%s" added', 'agent-box-orders' ), $label ),
                );
                continue;
            }

            $old_box = $old_by_label[ $label ];

            // Index items by product/variation ID
            $old_items = array();
            foreach ( $old_box['items'] as $item ) {
                $key = $item['variation_id'] ? 'v_' . $item['variation_id'] : 'p_' . $item['product_id'];
                $old_items[ $key ] = $item;
            }

            $new_items = array();
            foreach ( $new_box['items'] as $item ) {
                $key = $item['variation_id'] ? 'v_' . $item['variation_id'] : 'p_' . $item['product_id'];
                $new_items[ $key ] = $item;
            }

            // Check for removed items
            foreach ( $old_items as $key => $old_item ) {
                if ( ! isset( $new_items[ $key ] ) ) {
                    $changes[] = array(
                        'type'        => 'item_removed',
                        'box'         => $label,
                        'product'     => $old_item['product_name'],
                        'description' => sprintf( __( 'Box "%1$s": removed "%2$s"', 'agent-box-orders' ), $label, $old_item['product_name'] ),
                    );
                }
            }

            // Check for added items and quantity changes
            foreach ( $new_items as $key => $new_item ) {
                if ( ! isset( $old_items[ $key ] ) ) {
                    $changes[] = array(
                        'type'        => 'item_added',
                        'box'         => $label,
                        'product'     => $new_item['product_name'],
                        'qty'         => $new_item['quantity'],
                        'description' => sprintf( __( 'Box "%1$s": added "%2$s" x%3$d', 'agent-box-orders' ), $label, $new_item['product_name'], $new_item['quantity'] ),
                    );
                } elseif ( $old_items[ $key ]['quantity'] !== $new_item['quantity'] ) {
                    $changes[] = array(
                        'type'        => 'qty_changed',
                        'box'         => $label,
                        'product'     => $new_item['product_name'],
                        'from'        => $old_items[ $key ]['quantity'],
                        'to'          => $new_item['quantity'],
                        'description' => sprintf( __( 'Box "%1$s": "%2$s" qty %3$d → %4$d', 'agent-box-orders' ), $label, $new_item['product_name'], $old_items[ $key ]['quantity'], $new_item['quantity'] ),
                    );
                }
            }
        }

        return $changes;
    }

    /**
     * Log changes to order
     *
     * @param WC_Order $order   Order object.
     * @param array    $changes Changes array.
     */
    private function log_changes( $order, $changes ) {
        $user = wp_get_current_user();

        // Add to edit history meta
        $history = $order->get_meta( '_abox_edit_history' );
        if ( ! is_array( $history ) ) {
            $history = array();
        }

        $history[] = array(
            'user_id'   => $user->ID,
            'user_name' => $user->display_name,
            'timestamp' => current_time( 'mysql' ),
            'changes'   => $changes,
        );

        $order->update_meta_data( '_abox_edit_history', $history );

        // Add order note
        $note_parts = array();
        foreach ( $changes as $change ) {
            $note_parts[] = $change['description'];
        }

        $order->add_order_note(
            sprintf(
                /* translators: 1: user name, 2: changes list */
                __( 'Box order edited by %1$s — %2$s', 'agent-box-orders' ),
                $user->display_name,
                implode( '; ', $note_parts )
            ),
            false,
            true
        );
    }

    /**
     * Sync order line items with boxes
     *
     * @param WC_Order $order Order object.
     * @param array    $boxes Boxes data.
     */
    private function sync_order_line_items( $order, $boxes ) {
        // Remove all existing line items
        foreach ( $order->get_items() as $item_id => $item ) {
            $order->remove_item( $item_id );
        }

        // Aggregate products from all boxes
        $aggregated = array();

        foreach ( $boxes as $box ) {
            foreach ( $box['items'] as $item ) {
                $key = $item['variation_id'] ? 'v_' . $item['variation_id'] : 'p_' . $item['product_id'];

                if ( isset( $aggregated[ $key ] ) ) {
                    $aggregated[ $key ]['quantity'] += $item['quantity'];
                } else {
                    $aggregated[ $key ] = array(
                        'product_id'   => $item['product_id'],
                        'variation_id' => $item['variation_id'],
                        'quantity'     => $item['quantity'],
                        'price'        => $item['price'],
                    );
                }
            }
        }

        // Add new line items
        foreach ( $aggregated as $item_data ) {
            $product = $item_data['variation_id']
                ? wc_get_product( $item_data['variation_id'] )
                : wc_get_product( $item_data['product_id'] );

            if ( ! $product ) {
                continue;
            }

            $item = new WC_Order_Item_Product();
            $item->set_product( $product );
            $item->set_quantity( $item_data['quantity'] );
            $item->set_subtotal( $item_data['price'] * $item_data['quantity'] );
            $item->set_total( $item_data['price'] * $item_data['quantity'] );

            $order->add_item( $item );
        }
    }

    /**
     * AJAX: Search products
     */
    public function ajax_search_products() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

        if ( strlen( $term ) < 2 ) {
            wp_send_json_success( array( 'products' => array() ) );
        }

        $data_store  = WC_Data_Store::load( 'product' );
        $product_ids = $data_store->search_products( $term, '', true, false, 20 );

        $products = array();

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );

            if ( ! $product || ! $product->is_purchasable() ) {
                continue;
            }

            $product_type = $product->get_type();
            if ( 'variation' === $product_type ) {
                continue;
            }

            $products[] = array(
                'id'         => $product->get_id(),
                'name'       => $product->get_name(),
                'sku'        => $product->get_sku(),
                'price'      => (float) $product->get_price(),
                'price_html' => $product->get_price_html(),
                'image'      => wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ),
                'type'       => $product_type,
            );
        }

        wp_send_json_success( array( 'products' => $products ) );
    }

    /**
     * AJAX: Get variations
     */
    public function ajax_get_variations() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $product    = wc_get_product( $product_id );

        if ( ! $product || 'variable' !== $product->get_type() ) {
            wp_send_json_error( array( 'message' => __( 'Product is not a variable product.', 'agent-box-orders' ) ) );
        }

        $variations           = array();
        $available_variations = $product->get_available_variations();

        foreach ( $available_variations as $variation_data ) {
            $variation = wc_get_product( $variation_data['variation_id'] );

            if ( ! $variation || ! $variation->is_purchasable() ) {
                continue;
            }

            $attribute_parts      = array();
            $variation_attributes = $variation->get_variation_attributes();

            foreach ( $variation_attributes as $attr_key => $attr_value ) {
                $taxonomy  = str_replace( 'attribute_', '', $attr_key );
                $attr_name = wc_attribute_label( $taxonomy, $product );

                if ( $attr_value ) {
                    if ( taxonomy_exists( $taxonomy ) ) {
                        $term = get_term_by( 'slug', $attr_value, $taxonomy );
                        if ( $term ) {
                            $attr_value = $term->name;
                        }
                    }
                    $attribute_parts[] = $attr_name . ': ' . ucfirst( $attr_value );
                }
            }

            $variations[] = array(
                'variation_id'  => $variation->get_id(),
                'attributes'    => implode( ', ', $attribute_parts ),
                'sku'           => $variation->get_sku(),
                'price'         => (float) $variation->get_price(),
                'display_price' => wc_price( $variation->get_price() ),
            );
        }

        wp_send_json_success( array( 'variations' => $variations ) );
    }

    /**
     * AJAX: Get edit history
     */
    public function ajax_get_edit_history() {
        check_ajax_referer( 'abox_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'agent-box-orders' ) ) );
        }

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'agent-box-orders' ) ) );
        }

        $history = $order->get_meta( '_abox_edit_history' );

        if ( ! is_array( $history ) ) {
            $history = array();
        }

        // Reverse to show newest first
        $history = array_reverse( $history );

        wp_send_json_success( array( 'history' => $history ) );
    }
}
```

---

## Task 6: Create Edit Mode Template

**Files:**
- Create: `admin/views/meta-box-editor.php`

**Step 1: Create the template file**

```php
<?php
/**
 * Meta box editor template
 *
 * @package Agent_Box_Orders
 *
 * @var array $boxes Boxes data
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="abox-edit-mode">
    <div class="abox-edit-header">
        <h3>
            <?php esc_html_e( 'Editing Box Order', 'agent-box-orders' ); ?>
            <span class="abox-unsaved-indicator" style="display: none;">*</span>
        </h3>
        <div class="abox-edit-actions">
            <button type="button" class="button abox-cancel-btn"><?php esc_html_e( 'Cancel', 'agent-box-orders' ); ?></button>
            <button type="button" class="button button-primary abox-save-btn"><?php esc_html_e( 'Save Changes', 'agent-box-orders' ); ?></button>
        </div>
    </div>

    <div class="abox-accordion">
        <?php foreach ( $boxes as $index => $box ) :
            $box_total  = 0;
            $item_count = 0;
            foreach ( $box['items'] as $item ) {
                $box_total  += $item['price'] * $item['quantity'];
                $item_count += $item['quantity'];
            }
        ?>
            <div class="abox-accordion-item">
                <div class="abox-accordion-header">
                    <span class="abox-accordion-toggle dashicons dashicons-arrow-right-alt2"></span>
                    <div class="abox-accordion-label">
                        <input type="text" value="<?php echo esc_attr( $box['label'] ); ?>" placeholder="<?php esc_attr_e( 'Customer Name', 'agent-box-orders' ); ?>">
                    </div>
                    <div class="abox-accordion-summary">
                        <span class="abox-box-items">
                            <?php
                            printf(
                                esc_html( _n( '%d item', '%d items', $item_count, 'agent-box-orders' ) ),
                                $item_count
                            );
                            ?>
                        </span>
                        <span class="abox-box-total"><?php echo wc_price( $box_total ); ?></span>
                    </div>
                    <button type="button" class="abox-accordion-remove" title="<?php esc_attr_e( 'Remove box', 'agent-box-orders' ); ?>">&times;</button>
                </div>
                <div class="abox-accordion-content">
                    <table class="abox-edit-items-table">
                        <thead>
                            <tr>
                                <th class="abox-col-product"><?php esc_html_e( 'Product', 'agent-box-orders' ); ?></th>
                                <th class="abox-col-qty"><?php esc_html_e( 'Qty', 'agent-box-orders' ); ?></th>
                                <th class="abox-col-price"><?php esc_html_e( 'Price', 'agent-box-orders' ); ?></th>
                                <th class="abox-col-subtotal"><?php esc_html_e( 'Subtotal', 'agent-box-orders' ); ?></th>
                                <th class="abox-col-actions"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $box['items'] as $item ) :
                                $subtotal     = $item['price'] * $item['quantity'];
                                $variation_id = isset( $item['variation_id'] ) ? $item['variation_id'] : 0;
                                $product      = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $item['product_id'] );
                            ?>
                                <tr data-product-id="<?php echo esc_attr( $item['product_id'] ); ?>"
                                    data-variation-id="<?php echo esc_attr( $variation_id ); ?>"
                                    data-product-name="<?php echo esc_attr( $item['product_name'] ); ?>"
                                    data-variation-attrs="<?php echo esc_attr( isset( $item['variation_attrs'] ) ? $item['variation_attrs'] : '' ); ?>"
                                    data-price="<?php echo esc_attr( $item['price'] ); ?>">
                                    <td class="abox-col-product">
                                        <?php echo esc_html( $item['product_name'] ); ?>
                                        <?php if ( ! empty( $item['variation_attrs'] ) ) : ?>
                                            <br><small><?php echo esc_html( $item['variation_attrs'] ); ?></small>
                                        <?php endif; ?>
                                        <?php if ( $product && $product->get_sku() ) : ?>
                                            <span class="abox-product-sku">(<?php echo esc_html( $product->get_sku() ); ?>)</span>
                                        <?php endif; ?>
                                        <?php if ( ! $product ) : ?>
                                            <span class="abox-product-deleted"><?php esc_html_e( '(deleted)', 'agent-box-orders' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="abox-col-qty">
                                        <input type="number" class="abox-qty-input" value="<?php echo esc_attr( $item['quantity'] ); ?>" min="1">
                                    </td>
                                    <td class="abox-col-price"><?php echo wc_price( $item['price'] ); ?></td>
                                    <td class="abox-col-subtotal abox-item-subtotal"><?php echo wc_price( $subtotal ); ?></td>
                                    <td class="abox-col-actions">
                                        <button type="button" class="abox-item-remove">&times;</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="abox-product-search-container">
                        <input type="text" class="abox-product-search-input" placeholder="<?php esc_attr_e( 'Search products to add...', 'agent-box-orders' ); ?>">
                        <div class="abox-search-results"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="abox-add-box-container">
        <button type="button" class="abox-add-box-btn">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e( 'Add New Box', 'agent-box-orders' ); ?>
        </button>
    </div>

    <?php
    $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
    $order    = wc_get_order( $order_id );
    $history  = $order ? $order->get_meta( '_abox_edit_history' ) : array();
    if ( ! empty( $history ) ) :
    ?>
        <a href="#" class="abox-edit-history-link">
            <span class="dashicons dashicons-backup"></span>
            <?php esc_html_e( 'View Edit History', 'agent-box-orders' ); ?>
        </a>
    <?php endif; ?>
</div>
```

---

## Task 7: Update Meta Box Class to Show Edit Button

**Files:**
- Modify: `admin/class-abox-meta-box.php`

**Step 1: Update render_meta_box method**

After line 126 (before include statement), add the edit button:

```php
// Show edit button if admin editing is enabled
$show_edit_button = 'yes' === get_option( 'abox_enable_admin_editing', 'no' ) && current_user_can( 'manage_woocommerce' );

if ( $show_edit_button ) {
    echo '<div class="abox-edit-button-container" style="margin-bottom: 12px;">';
    echo '<button type="button" class="button abox-edit-btn">';
    echo '<span class="dashicons dashicons-edit" style="vertical-align: middle;"></span> ';
    echo esc_html__( 'Edit Boxes', 'agent-box-orders' );
    echo '</button>';
    echo '</div>';
}
```

---

## Task 8: Update Loader to Initialize Editor

**Files:**
- Modify: `agent-box-orders.php:86-88`

**Step 1: Add editor class require**

After line 87 (`require_once ABOX_PLUGIN_DIR . 'admin/class-abox-meta-box.php';`), add:

```php
require_once ABOX_PLUGIN_DIR . 'admin/class-abox-meta-box-editor.php';
```

**Step 2: Update loader to initialize editor**

Modify `includes/class-abox-loader.php` line 57. After `new ABOX_Meta_Box();`, add:

```php
new ABOX_Meta_Box_Editor();
```

---

## Task 9: Test the Implementation

**Step 1: Enable the setting**

1. Go to WooCommerce > Settings > Advanced > Agent Box Orders
2. Enable "Admin Box Editing"
3. Save

**Step 2: Test edit mode**

1. Go to an existing box order
2. Click "Edit Boxes" button
3. Test expanding/collapsing accordions
4. Test changing quantities
5. Test removing items
6. Test adding products via search
7. Test adding/removing boxes
8. Test save functionality
9. Verify order line items updated
10. Verify order note added
11. Check edit history

---

## Summary

**Files Created (4):**
- `assets/css/admin-editor.css`
- `assets/js/admin-editor.js`
- `admin/class-abox-meta-box-editor.php`
- `admin/views/meta-box-editor.php`

**Files Modified (4):**
- `agent-box-orders.php` (version bump + require)
- `includes/class-abox-settings.php` (new setting)
- `includes/class-abox-loader.php` (init editor)
- `admin/class-abox-meta-box.php` (edit button)

---

Plan complete and saved to `docs/plans/2026-01-29-admin-box-editing-design.md`. Two execution options:

**1. Subagent-Driven (this session)** - I dispatch fresh subagent per task, review between tasks, fast iteration

**2. Parallel Session (separate)** - Open new session with executing-plans, batch execution with checkpoints

Which approach?
