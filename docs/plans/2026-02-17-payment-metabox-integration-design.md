# Payment Metabox Integration Design

## Goal

Move the standalone `wc-order-payment-metabox` plugin into Agent Box Orders and add admin settings for managing payment statuses and collection methods dynamically.

## Architecture

Absorb the WC Order Payment Metabox plugin as a new class `ABOX_Payment_Metabox` within Agent Box Orders, following the existing class-per-feature pattern.

### Files

| File | Action | Purpose |
|------|--------|---------|
| `admin/class-abox-payment-metabox.php` | Create | Payment & Collection metabox on order edit screen + order list columns |
| `includes/class-abox-settings.php` | Modify | Add repeater fields for payment statuses and collection methods |
| `admin/views/create-order.php` | Modify | Read statuses/methods from settings instead of hardcoded values |
| `agent-box-orders.php` | Modify | Load new class in `load_dependencies()` |
| `assets/css/admin.css` | Modify | Styles for repeater settings UI |
| `assets/js/admin-settings.js` | Create | JS for repeater add/remove row logic |

## Settings Design

New section "Payment & Collection Settings" added to WooCommerce > Settings > Advanced > Agent Box Orders.

### Repeater Fields

Two repeater tables using custom WooCommerce settings field type `abox_repeater`:

**Payment Statuses** (`abox_payment_statuses` option):
```php
[
    ['slug' => 'done', 'label' => 'Done Payment', 'bg_color' => '#74d62f', 'text_color' => '#ffffff'],
    ['slug' => 'cash_cashier', 'label' => 'Cash di Cashier', 'bg_color' => '#dd3333', 'text_color' => '#ffffff'],
    ['slug' => 'cod', 'label' => 'Cash on Delivery (COD)', 'bg_color' => '#dd3333', 'text_color' => '#ffffff'],
    ['slug' => 'pending_payment', 'label' => 'Pending Payment', 'bg_color' => '#dd3333', 'text_color' => '#ffffff'],
    ['slug' => 'partial', 'label' => 'Partial Payment', 'bg_color' => '#eeee22', 'text_color' => '#555555'],
]
```

**Collection Methods** (`abox_collection_methods` option):
```php
[
    ['slug' => 'postage', 'label' => 'Postage', 'bg_color' => '#f760ed', 'text_color' => '#ffffff'],
    ['slug' => 'pickup_hq', 'label' => 'Pickup - HQ', 'bg_color' => '#eeee22', 'text_color' => '#555555'],
    ['slug' => 'pickup_terengganu', 'label' => 'Pickup - Terengganu', 'bg_color' => '#1e73be', 'text_color' => '#ffffff'],
    ['slug' => 'runner_delivered', 'label' => 'Runner Delivered', 'bg_color' => '#8224e3', 'text_color' => '#ffffff'],
]
```

Each row has: slug (text), label (text), badge background color (color picker), text color (color picker), delete button.

### Custom Field Type

Registered via `woocommerce_admin_field_abox_repeater` hook since WooCommerce Settings API doesn't natively support repeater fields. Add/remove row logic handled by `admin-settings.js`.

## ABOX_Payment_Metabox Class

Migrated from `wc-order-payment-metabox` plugin with these changes:

- Payment statuses and collection methods read from `abox_payment_statuses` / `abox_collection_methods` options instead of hardcoded arrays
- Badge CSS generated dynamically from saved colors via `admin_head`
- Legacy keys (`pickup`, `runner`) still recognized for display but not shown in dropdowns
- All existing functionality preserved: metabox, save with order notes, order list columns (sortable), HPOS + legacy support, database indexes

### Key Methods

- `get_payment_statuses()` - reads from option, returns `['slug' => 'label']` array
- `get_collection_methods()` - reads from option, returns `['slug' => 'label']` array
- `get_status_colors($type)` - returns `['slug' => ['bg' => '#...', 'text' => '#...']]`
- `admin_styles()` - outputs dynamic CSS from saved colors
- Metabox render/save, column render/sort - same as current plugin

## Create Order Page Changes

Replace hardcoded `<option>` elements with PHP loop reading from `abox_payment_statuses` and `abox_collection_methods` options. Same pattern as the metabox dropdowns.

## Migration Strategy

- On first load (option doesn't exist), seed `abox_payment_statuses` and `abox_collection_methods` with current defaults
- Same meta keys used (`_payment_status`, `_collection_method`, `_pickup_cod_date`, `_pickup_cod_time`) - zero data migration needed
- User manually deactivates standalone `wc-order-payment-metabox` plugin after confirming new integration works
- Database indexes created on plugin activation (same as current standalone plugin)

## Data Flow

1. Admin saves statuses/methods in WooCommerce > Settings > Advanced > Agent Box Orders
2. `ABOX_Payment_Metabox` reads options to render metabox dropdowns and order list badges
3. Create Order page reads same options for its dropdowns
4. Dynamic CSS output via `admin_head` based on saved badge colors
5. Order notes track changes with human-readable labels from settings
