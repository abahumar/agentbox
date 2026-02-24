# Checkout Collection Fields Design

**Date:** 2026-02-24
**Feature:** Add Collection Method, Pickup Date, and Pickup Time fields to WooCommerce checkout page

## Overview

Add collection details fields to the WooCommerce checkout page that sync with the admin box order creation system. Customers will be able to select their collection method and provide pickup date/time when applicable, mirroring the fields available when agents create orders from the admin panel.

## Requirements

### Functional Requirements

1. **Collection Details Section** on checkout page
   - Position: After billing details, before shipping details
   - Visible for: All WooCommerce orders (not just box orders)
   - Fields included:
     - Collection Method (dropdown) - **required**
     - Pickup Date (date input) - **conditionally required**
     - Pickup Time (time input) - **conditionally required**

2. **Conditional Field Display**
   - Pickup Date and Pickup Time fields show/hide based on selected collection method
   - Admin configures which collection methods require date/time via settings
   - Default methods requiring date/time: "Pickup" and "COD"

3. **Field Validation**
   - Collection Method: Always required
   - Pickup Date: Required when collection method requires date/time
   - Pickup Time: Required when collection method requires date/time
   - Date constraint: Any future date allowed (no restrictions)
   - Time format: Browser's native time picker

4. **Data Synchronization**
   - Save to same order meta keys as admin create order:
     - `_collection_method`
     - `_pickup_cod_date`
     - `_pickup_cod_time`
   - Ensures consistency between admin-created and customer-created orders

### Non-Functional Requirements

1. **Compatibility**
   - Follow WooCommerce field system standards
   - Compatible with classic checkout (WooCommerce Blocks support future enhancement)
   - Inherit theme styling automatically
   - Work with JavaScript disabled (graceful degradation)

2. **Maintainability**
   - Reuse existing plugin patterns and code structure
   - Follow existing naming conventions (ABOX_ prefix)
   - Use existing settings infrastructure

3. **User Experience**
   - Clear error messages on validation failure
   - Smooth show/hide transitions for conditional fields
   - Mobile-responsive design

## Architecture

### Component Overview

```
ABOX_Checkout_Fields (new class)
├── Add fields to checkout
├── Validate fields on submission
├── Save to order meta
└── Enqueue conditional display JavaScript

ABOX_Settings (extended)
└── Add datetime-required methods configuration

Integration Points:
├── WooCommerce checkout hooks
├── Existing ABOX_Settings::get_collection_methods()
└── Same meta keys as admin create order
```

### Data Flow

**1. Field Rendering:**
```
Checkout page loads
→ woocommerce_checkout_fields filter
→ ABOX_Checkout_Fields::add_checkout_fields()
→ Adds 3 custom fields (collection_method, pickup_date, pickup_time)
→ JavaScript checks selected method
→ Show/hide date/time fields conditionally
```

**2. Validation:**
```
Customer clicks "Place Order"
→ woocommerce_checkout_process action
→ ABOX_Checkout_Fields::validate_checkout_fields()
→ Check collection method selected
→ If method requires date/time, validate those fields
→ Display error notice if validation fails
→ Block checkout until valid
```

**3. Save:**
```
Validation passes
→ woocommerce_checkout_update_order_meta action
→ ABOX_Checkout_Fields::save_checkout_fields()
→ Save to order meta (_collection_method, _pickup_cod_date, _pickup_cod_time)
→ Meta keys match admin create order system
```

## Implementation Details

### Files to Create

**1. `includes/class-abox-checkout-fields.php`**

Main class handling checkout field integration:

```php
class ABOX_Checkout_Fields {
    public function __construct()
    public function add_checkout_fields($fields)
    public function validate_checkout_fields()
    public function save_checkout_fields($order_id)
    public function enqueue_scripts()
}
```

Key methods:
- `add_checkout_fields()` - Adds fields via WooCommerce filter, priority 25-27 for position
- `validate_checkout_fields()` - Checks required fields based on settings
- `save_checkout_fields()` - Writes to order meta using HPOS-compatible methods
- `enqueue_scripts()` - Loads JS/CSS only on checkout page

**2. `assets/js/checkout-fields.js`**

Conditional display logic:

```javascript
jQuery(function($) {
    var methodsRequireDateTime = aboxCheckoutVars.methodsRequireDateTime;

    $('#collection_method').on('change', function() {
        var selectedMethod = $(this).val();
        var $dateField = $('#pickup_date_field');
        var $timeField = $('#pickup_time_field');

        if (methodsRequireDateTime.includes(selectedMethod)) {
            $dateField.slideDown().find('input').prop('required', true);
            $timeField.slideDown().find('input').prop('required', true);
        } else {
            $dateField.slideUp().find('input').prop('required', false);
            $timeField.slideUp().find('input').prop('required', false);
        }
    }).trigger('change'); // Initialize on load
});
```

**3. `assets/css/checkout-fields.css`** (optional)

Minimal styling for section heading and spacing:

```css
.abox-collection-details-section {
    margin-top: 20px;
    margin-bottom: 20px;
}

.abox-collection-details-section h3 {
    margin-bottom: 15px;
    font-size: 1.2em;
}
```

### Files to Modify

**1. `includes/class-abox-loader.php`**

Register new class:

```php
// In define_public_hooks() method
$checkout_fields = new ABOX_Checkout_Fields();
$loader->add_filter('woocommerce_checkout_fields', $checkout_fields, 'add_checkout_fields', 10, 1);
$loader->add_action('woocommerce_checkout_process', $checkout_fields, 'validate_checkout_fields');
$loader->add_action('woocommerce_checkout_update_order_meta', $checkout_fields, 'save_checkout_fields');
$loader->add_action('wp_enqueue_scripts', $checkout_fields, 'enqueue_scripts');
```

**2. `includes/class-abox-settings.php`**

Add new setting field:

```php
// In add_settings() method, after collection_methods setting
array(
    'title'   => __('Collection Methods Requiring Date/Time', 'agent-box-orders'),
    'desc'    => __('Select which collection methods require customers to provide pickup date and time at checkout.', 'agent-box-orders'),
    'id'      => 'abox_collection_methods_require_datetime',
    'type'    => 'multiselect',
    'class'   => 'wc-enhanced-select',
    'css'     => 'min-width: 350px;',
    'default' => array('pickup', 'cod'),
    'options' => self::get_collection_methods_for_select(),
),
```

Add helper methods:

```php
public static function get_datetime_required_methods() {
    return get_option('abox_collection_methods_require_datetime', array('pickup', 'cod'));
}

private static function get_collection_methods_for_select() {
    $methods = self::get_collection_methods();
    $options = array();
    foreach ($methods as $method) {
        $options[$method['slug']] = $method['label'];
    }
    return $options;
}
```

**3. `agent-box-orders.php`**

Include new file:

```php
require_once plugin_dir_path(__FILE__) . 'includes/class-abox-checkout-fields.php';
```

### Field Configuration

**Collection Method Field:**
```php
'collection_method' => array(
    'type'     => 'select',
    'label'    => __('Collection Method', 'agent-box-orders'),
    'required' => true,
    'class'    => array('form-row-wide'),
    'priority' => 25,
    'options'  => $this->get_collection_method_options(),
    'default'  => '',
)
```

**Pickup Date Field:**
```php
'pickup_date' => array(
    'type'              => 'date',
    'label'             => __('Pickup Date', 'agent-box-orders'),
    'required'          => false, // Managed by JS and validation
    'class'             => array('form-row-wide'),
    'priority'          => 26,
    'custom_attributes' => array('min' => date('Y-m-d')),
)
```

**Pickup Time Field:**
```php
'pickup_time' => array(
    'type'     => 'time',
    'label'    => __('Pickup Time', 'agent-box-orders'),
    'required' => false, // Managed by JS and validation
    'class'    => array('form-row-wide'),
    'priority' => 27,
)
```

## Error Handling

### Validation Error Messages

```php
// Collection method not selected
wc_add_notice(__('Please select a collection method.', 'agent-box-orders'), 'error');

// Pickup date missing (when required)
wc_add_notice(__('Please select a pickup date for your chosen collection method.', 'agent-box-orders'), 'error');

// Pickup time missing (when required)
wc_add_notice(__('Please select a pickup time for your chosen collection method.', 'agent-box-orders'), 'error');
```

### Edge Cases

**1. No collection methods configured:**
- Check if methods exist before rendering field
- Gracefully skip field rendering if empty
- Log warning for admin (optional)

**2. JavaScript disabled:**
- Date/time fields remain visible (always shown)
- Server-side validation handles requirements correctly
- No broken functionality

**3. Admin changes settings after orders placed:**
- Existing orders unaffected
- New checkouts use updated settings
- No data migration needed

**4. WooCommerce Blocks checkout:**
- Fields will not appear (classic checkout only)
- Document limitation
- Future enhancement opportunity

**5. Theme compatibility:**
- Uses WooCommerce native field system
- Inherits theme styles automatically
- Minimal custom CSS needed

### Data Sanitization

```php
// Collection method
$collection_method = sanitize_text_field($_POST['collection_method']);
// Validate against allowed methods
if (!in_array($collection_method, $allowed_methods)) {
    $collection_method = '';
}

// Pickup date
$pickup_date = sanitize_text_field($_POST['pickup_date']);
// Validate date format YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickup_date)) {
    $pickup_date = '';
}

// Pickup time
$pickup_time = sanitize_text_field($_POST['pickup_time']);
// Validate time format HH:MM
if (!preg_match('/^\d{2}:\d{2}$/', $pickup_time)) {
    $pickup_time = '';
}
```

## Admin Settings

**Location:** WooCommerce > Settings > Advanced > Agent Box Orders

**New Setting:**

**Field Label:** Collection Methods Requiring Date/Time

**Field Type:** Multi-select checkbox

**Description:** "Select which collection methods require customers to provide pickup date and time at checkout."

**Options:** Dynamically populated from `ABOX_Settings::get_collection_methods()`

**Default Value:** `['pickup', 'cod']`

**Storage:** WordPress option `abox_collection_methods_require_datetime`

**Example UI:**
```
☑ Pickup
☑ COD (Cash on Delivery)
☐ Delivery
☐ Agent Delivery
```

## Testing Checklist

### Functional Testing

- [ ] Collection method dropdown shows all configured methods
- [ ] Pickup date/time fields hidden by default
- [ ] Selecting method requiring date/time shows fields
- [ ] Selecting method NOT requiring date/time hides fields
- [ ] Validation blocks checkout when collection method missing
- [ ] Validation blocks checkout when date/time required but missing
- [ ] Validation allows checkout when all required fields filled
- [ ] Order meta saved correctly (`_collection_method`, `_pickup_cod_date`, `_pickup_cod_time`)
- [ ] Admin settings control which methods require date/time
- [ ] Fields appear after billing, before shipping

### Edge Case Testing

- [ ] No collection methods configured (graceful degradation)
- [ ] JavaScript disabled (fields always visible, validation works)
- [ ] Admin changes settings (new orders use new config)
- [ ] Multiple themes tested (styling inherits correctly)
- [ ] Mobile responsive (fields stack properly)

### Integration Testing

- [ ] Admin create order uses same meta keys
- [ ] Order emails show collection details
- [ ] Order admin panel displays collection details
- [ ] Existing orders without collection data load correctly
- [ ] Plugin deactivation/reactivation doesn't break data

### Browser Testing

- [ ] Chrome (date/time pickers work)
- [ ] Firefox (date/time pickers work)
- [ ] Safari (date/time pickers work)
- [ ] Edge (date/time pickers work)
- [ ] Mobile browsers (iOS Safari, Chrome Android)

## Future Enhancements

1. **WooCommerce Blocks Checkout Support**
   - Implement as block-based checkout field
   - Use Store API for data handling

2. **Date/Time Slot Management**
   - Admin configures available time slots
   - Customers select from predefined options
   - Integration with business hours

3. **Pickup Location Selection**
   - Multiple pickup locations
   - Show available dates/times per location
   - Integration with inventory by location

4. **Email Template Customization**
   - Dedicated email section for collection details
   - Styled pickup information display

5. **Calendar Integration**
   - Admin calendar view of scheduled pickups
   - Capacity management by date/time

## Security Considerations

1. **Input Validation**
   - All fields sanitized and validated server-side
   - Cannot be bypassed via JavaScript manipulation

2. **Capability Checks**
   - Settings page requires `manage_woocommerce` capability
   - No security holes from new fields

3. **Data Storage**
   - HPOS-compatible meta data methods
   - No direct database queries

4. **XSS Prevention**
   - All output escaped (`esc_html`, `esc_attr`)
   - No user input rendered without sanitization

## Performance Considerations

1. **Minimal Database Impact**
   - 3 additional meta fields per order
   - Indexed by WooCommerce order meta system

2. **JavaScript Loading**
   - Only loaded on checkout page
   - Minimal file size (<2KB)

3. **No Additional API Calls**
   - All data loaded with existing checkout request
   - No AJAX required for basic functionality

## Summary

This design adds collection method, pickup date, and pickup time fields to the WooCommerce checkout page using the standard WooCommerce checkout field system. The fields sync with the admin box order creation system by using the same meta keys. Admin settings control which collection methods require date/time input. The implementation follows WooCommerce best practices and integrates seamlessly with the existing plugin architecture.
