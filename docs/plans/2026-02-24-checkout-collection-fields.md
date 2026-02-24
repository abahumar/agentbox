# Checkout Collection Fields Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add Collection Method, Pickup Date, and Pickup Time fields to WooCommerce checkout page that sync with admin box order creation system.

**Architecture:** Create new `ABOX_Checkout_Fields` class to add three custom checkout fields using WooCommerce's checkout field system. Fields conditionally display based on admin settings (which collection methods require date/time). Save to same order meta keys as admin create order (`_collection_method`, `_pickup_cod_date`, `_pickup_cod_time`). JavaScript handles conditional field visibility.

**Tech Stack:** WordPress, WooCommerce, PHP 7.4+, jQuery, WooCommerce checkout hooks

---

## Task 1: Add Admin Settings for DateTime-Required Methods

**Files:**
- Modify: `includes/class-abox-settings.php:159-163` (after collection methods setting)

**Step 1: Add settings field for datetime-required methods**

In `includes/class-abox-settings.php`, add new setting after the collection methods setting (after line 159):

```php
array(
    'title'   => __( 'Collection Methods Requiring Date/Time', 'agent-box-orders' ),
    'desc'    => __( 'Select which collection methods require customers to provide pickup date and time at checkout.', 'agent-box-orders' ),
    'id'      => 'abox_collection_methods_require_datetime',
    'type'    => 'multiselect',
    'class'   => 'wc-enhanced-select',
    'css'     => 'min-width: 350px;',
    'default' => array( 'pickup_hq', 'pickup_terengganu' ),
    'options' => self::get_collection_methods_for_select(),
),
```

**Step 2: Add helper method to format collection methods for select**

Add this method to `ABOX_Settings` class (after `get_collection_methods()` method around line 264):

```php
/**
 * Get collection methods formatted for select/multiselect options
 *
 * @return array Array of ['slug' => 'Label']
 */
private static function get_collection_methods_for_select() {
    $methods = self::get_collection_methods();
    $options = array();
    foreach ( $methods as $method ) {
        $options[ $method['slug'] ] = $method['label'];
    }
    return $options;
}
```

**Step 3: Add getter method for datetime-required methods**

Add this method to `ABOX_Settings` class (after the helper method from Step 2):

```php
/**
 * Get collection methods that require date/time at checkout
 *
 * @return array Array of method slugs
 */
public static function get_datetime_required_methods() {
    $methods = get_option( 'abox_collection_methods_require_datetime', array( 'pickup_hq', 'pickup_terengganu' ) );
    if ( ! is_array( $methods ) ) {
        $methods = array( 'pickup_hq', 'pickup_terengganu' );
    }
    return $methods;
}
```

**Step 4: Test settings appear in admin**

Run: Navigate to WP Admin > WooCommerce > Settings > Advanced > Agent Box Orders
Expected: See new "Collection Methods Requiring Date/Time" multi-select field with collection methods as options

**Step 5: Commit settings changes**

```bash
git add includes/class-abox-settings.php
git commit -m "feat: add admin setting for collection methods requiring date/time

Add multiselect setting to configure which collection methods require
pickup date and time at checkout. Defaults to pickup_hq and
pickup_terengganu methods."
```

---

## Task 2: Create Checkout Fields Class

**Files:**
- Create: `includes/class-abox-checkout-fields.php`

**Step 1: Create checkout fields class file**

Create `includes/class-abox-checkout-fields.php`:

```php
<?php
/**
 * Checkout Fields Integration
 *
 * @package Agent_Box_Orders
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ABOX_Checkout_Fields class
 */
class ABOX_Checkout_Fields {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'add_checkout_fields' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_checkout_fields' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add collection fields to checkout
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function add_checkout_fields( $fields ) {
		// Placeholder - will implement next
		return $fields;
	}

	/**
	 * Validate checkout fields
	 */
	public function validate_checkout_fields() {
		// Placeholder - will implement next
	}

	/**
	 * Save checkout fields to order meta
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_checkout_fields( $order_id ) {
		// Placeholder - will implement next
	}

	/**
	 * Enqueue scripts for conditional field display
	 */
	public function enqueue_scripts() {
		// Placeholder - will implement next
	}
}
```

**Step 2: Include new class in main plugin file**

In `agent-box-orders.php`, add after line 82 (after `class-abox-checkout.php` include):

```php
require_once ABOX_PLUGIN_DIR . 'includes/class-abox-checkout-fields.php';
```

**Step 3: Initialize class in loader**

In `includes/class-abox-loader.php`, add after line 49 (after `new ABOX_Checkout();`):

```php
// Checkout fields for collection method
new ABOX_Checkout_Fields();
```

**Step 4: Test class loads without errors**

Run: Load any page on the site
Expected: No PHP errors, site loads normally

**Step 5: Commit checkout fields class skeleton**

```bash
git add includes/class-abox-checkout-fields.php agent-box-orders.php includes/class-abox-loader.php
git commit -m "feat: add checkout fields class skeleton

Create ABOX_Checkout_Fields class to handle collection method,
pickup date, and pickup time fields on checkout page."
```

---

## Task 3: Implement Add Checkout Fields Method

**Files:**
- Modify: `includes/class-abox-checkout-fields.php:28-31`

**Step 1: Implement add_checkout_fields method**

Replace the `add_checkout_fields()` method in `includes/class-abox-checkout-fields.php`:

```php
/**
 * Add collection fields to checkout
 *
 * @param array $fields Checkout fields.
 * @return array
 */
public function add_checkout_fields( $fields ) {
	$collection_methods = ABOX_Settings::get_collection_methods();

	// Don't add fields if no collection methods configured
	if ( empty( $collection_methods ) ) {
		return $fields;
	}

	// Build options array for select field
	$method_options = array( '' => __( '— Select Collection Method —', 'agent-box-orders' ) );
	foreach ( $collection_methods as $method ) {
		$method_options[ $method['slug'] ] = $method['label'];
	}

	// Add collection method field
	$fields['billing']['collection_method'] = array(
		'type'     => 'select',
		'label'    => __( 'Collection Method', 'agent-box-orders' ),
		'required' => true,
		'class'    => array( 'form-row-wide' ),
		'priority' => 25,
		'options'  => $method_options,
	);

	// Add pickup date field
	$fields['billing']['pickup_date'] = array(
		'type'              => 'date',
		'label'             => __( 'Pickup Date', 'agent-box-orders' ),
		'required'          => false,
		'class'             => array( 'form-row-wide', 'abox-pickup-field' ),
		'priority'          => 26,
		'custom_attributes' => array(
			'min' => gmdate( 'Y-m-d' ),
		),
	);

	// Add pickup time field
	$fields['billing']['pickup_time'] = array(
		'type'     => 'time',
		'label'    => __( 'Pickup Time', 'agent-box-orders' ),
		'required' => false,
		'class'    => array( 'form-row-wide', 'abox-pickup-field' ),
		'priority' => 27,
	);

	return $fields;
}
```

**Step 2: Test fields appear on checkout**

Run: Navigate to checkout page
Expected: See "Collection Method" dropdown, "Pickup Date" and "Pickup Time" fields below billing fields

**Step 3: Commit add fields implementation**

```bash
git add includes/class-abox-checkout-fields.php
git commit -m "feat: add collection fields to checkout

Add collection method dropdown, pickup date, and pickup time fields
to checkout page after billing details using WooCommerce checkout
field system."
```

---

## Task 4: Implement Field Validation

**Files:**
- Modify: `includes/class-abox-checkout-fields.php:39-42`

**Step 1: Implement validate_checkout_fields method**

Replace the `validate_checkout_fields()` method in `includes/class-abox-checkout-fields.php`:

```php
/**
 * Validate checkout fields
 */
public function validate_checkout_fields() {
	$collection_method = isset( $_POST['collection_method'] ) ? sanitize_text_field( wp_unslash( $_POST['collection_method'] ) ) : '';

	// Collection method is always required
	if ( empty( $collection_method ) ) {
		wc_add_notice( __( 'Please select a collection method.', 'agent-box-orders' ), 'error' );
		return;
	}

	// Validate collection method is a valid option
	$collection_methods = ABOX_Settings::get_collection_methods();
	$valid_slugs        = wp_list_pluck( $collection_methods, 'slug' );

	if ( ! in_array( $collection_method, $valid_slugs, true ) ) {
		wc_add_notice( __( 'Invalid collection method selected.', 'agent-box-orders' ), 'error' );
		return;
	}

	// Check if this method requires date/time
	$datetime_required_methods = ABOX_Settings::get_datetime_required_methods();

	if ( in_array( $collection_method, $datetime_required_methods, true ) ) {
		$pickup_date = isset( $_POST['pickup_date'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_date'] ) ) : '';
		$pickup_time = isset( $_POST['pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_time'] ) ) : '';

		if ( empty( $pickup_date ) ) {
			wc_add_notice( __( 'Please select a pickup date for your chosen collection method.', 'agent-box-orders' ), 'error' );
		}

		if ( empty( $pickup_time ) ) {
			wc_add_notice( __( 'Please select a pickup time for your chosen collection method.', 'agent-box-orders' ), 'error' );
		}
	}
}
```

**Step 2: Test validation when fields empty**

Run: Go to checkout, leave collection method empty, click Place Order
Expected: Error "Please select a collection method."

**Step 3: Test validation when method requires date/time**

Run: Select a method requiring date/time (e.g., "Pickup - HQ"), leave date/time empty, click Place Order
Expected: Errors for missing date and time

**Step 4: Commit validation implementation**

```bash
git add includes/class-abox-checkout-fields.php
git commit -m "feat: add validation for collection fields

Validate collection method is selected and is valid. When method
requires date/time, validate pickup date and time are provided."
```

---

## Task 5: Implement Save to Order Meta

**Files:**
- Modify: `includes/class-abox-checkout-fields.php:50-53`

**Step 1: Implement save_checkout_fields method**

Replace the `save_checkout_fields()` method in `includes/class-abox-checkout-fields.php`:

```php
/**
 * Save checkout fields to order meta
 *
 * @param int $order_id Order ID.
 */
public function save_checkout_fields( $order_id ) {
	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	// Save collection method
	if ( isset( $_POST['collection_method'] ) ) {
		$collection_method = sanitize_text_field( wp_unslash( $_POST['collection_method'] ) );
		$order->update_meta_data( '_collection_method', $collection_method );
	}

	// Save pickup date
	if ( isset( $_POST['pickup_date'] ) && ! empty( $_POST['pickup_date'] ) ) {
		$pickup_date = sanitize_text_field( wp_unslash( $_POST['pickup_date'] ) );
		$order->update_meta_data( '_pickup_cod_date', $pickup_date );
	}

	// Save pickup time
	if ( isset( $_POST['pickup_time'] ) && ! empty( $_POST['pickup_time'] ) ) {
		$pickup_time = sanitize_text_field( wp_unslash( $_POST['pickup_time'] ) );
		$order->update_meta_data( '_pickup_cod_time', $pickup_time );
	}

	$order->save();
}
```

**Step 2: Test data saves to order meta**

Run: Complete checkout with collection method, date, and time
Expected: Order created successfully

**Step 3: Verify meta data in database**

Run: Go to WP Admin > WooCommerce > Orders > View the order > Custom Fields section
Expected: See `_collection_method`, `_pickup_cod_date`, `_pickup_cod_time` meta keys with values

**Step 4: Commit save implementation**

```bash
git add includes/class-abox-checkout-fields.php
git commit -m "feat: save collection fields to order meta

Save collection method, pickup date, and pickup time to order meta
using same meta keys as admin create order system for consistency."
```

---

## Task 6: Create JavaScript for Conditional Display

**Files:**
- Create: `assets/js/checkout-fields.js`

**Step 1: Create JavaScript file**

Create `assets/js/checkout-fields.js`:

```javascript
/**
 * Checkout Fields Conditional Display
 *
 * Show/hide pickup date and time fields based on selected collection method.
 */
jQuery(function($) {
	'use strict';

	var methodsRequireDateTime = aboxCheckoutFieldsVars.methodsRequireDateTime || [];
	var $collectionMethodField = $('#collection_method');
	var $pickupDateField = $('#pickup_date_field');
	var $pickupTimeField = $('#pickup_time_field');

	/**
	 * Toggle pickup date/time fields based on collection method
	 */
	function togglePickupFields() {
		var selectedMethod = $collectionMethodField.val();

		if (methodsRequireDateTime.indexOf(selectedMethod) !== -1) {
			// Method requires date/time - show fields and make required
			$pickupDateField.slideDown(200);
			$pickupTimeField.slideDown(200);
			$pickupDateField.find('input').prop('required', true);
			$pickupTimeField.find('input').prop('required', true);
		} else {
			// Method doesn't require date/time - hide fields and remove required
			$pickupDateField.slideUp(200);
			$pickupTimeField.slideUp(200);
			$pickupDateField.find('input').prop('required', false).val('');
			$pickupTimeField.find('input').prop('required', false).val('');
		}
	}

	// Initialize on page load
	togglePickupFields();

	// Toggle on collection method change
	$collectionMethodField.on('change', togglePickupFields);

	// Re-initialize after WooCommerce updates checkout
	$(document.body).on('updated_checkout', function() {
		$collectionMethodField = $('#collection_method');
		$pickupDateField = $('#pickup_date_field');
		$pickupTimeField = $('#pickup_time_field');
		togglePickupFields();
		$collectionMethodField.on('change', togglePickupFields);
	});
});
```

**Step 2: Test JavaScript file has no syntax errors**

Run: Check JavaScript syntax with any linter or browser console
Expected: No syntax errors

**Step 3: Commit JavaScript file**

```bash
git add assets/js/checkout-fields.js
git commit -m "feat: add JavaScript for conditional field display

Add script to show/hide pickup date and time fields based on
selected collection method. Updates required attribute dynamically."
```

---

## Task 7: Enqueue JavaScript and Localize Data

**Files:**
- Modify: `includes/class-abox-checkout-fields.php:61-64`

**Step 1: Implement enqueue_scripts method**

Replace the `enqueue_scripts()` method in `includes/class-abox-checkout-fields.php`:

```php
/**
 * Enqueue scripts for conditional field display
 */
public function enqueue_scripts() {
	// Only load on checkout page
	if ( ! is_checkout() ) {
		return;
	}

	// Enqueue JavaScript
	wp_enqueue_script(
		'abox-checkout-fields',
		ABOX_PLUGIN_URL . 'assets/js/checkout-fields.js',
		array( 'jquery' ),
		ABOX_VERSION,
		true
	);

	// Localize script with datetime-required methods
	wp_localize_script(
		'abox-checkout-fields',
		'aboxCheckoutFieldsVars',
		array(
			'methodsRequireDateTime' => ABOX_Settings::get_datetime_required_methods(),
		)
	);
}
```

**Step 2: Test script loads on checkout page**

Run: Navigate to checkout page, open browser DevTools > Network tab
Expected: See `checkout-fields.js` loaded

**Step 3: Test conditional display works**

Run: On checkout, select "Postage" (doesn't require date/time)
Expected: Date/time fields hide

Run: Select "Pickup - HQ" (requires date/time)
Expected: Date/time fields appear

**Step 4: Commit enqueue implementation**

```bash
git add includes/class-abox-checkout-fields.php
git commit -m "feat: enqueue checkout fields JavaScript

Load JavaScript on checkout page with localized data for which
collection methods require date/time input."
```

---

## Task 8: Add CSS for Field Styling (Optional)

**Files:**
- Create: `assets/css/checkout-fields.css`
- Modify: `includes/class-abox-checkout-fields.php:61` (in enqueue_scripts method)

**Step 1: Create CSS file**

Create `assets/css/checkout-fields.css`:

```css
/**
 * Checkout Collection Fields Styling
 */

/* Collection Details Section */
.woocommerce-checkout #collection_method_field,
.woocommerce-checkout #pickup_date_field,
.woocommerce-checkout #pickup_time_field {
	margin-bottom: 1em;
}

/* Add spacing before collection method to separate from billing */
.woocommerce-checkout #collection_method_field {
	margin-top: 1.5em;
	padding-top: 1.5em;
	border-top: 1px solid #e0e0e0;
}

/* Hide pickup fields by default (JS will show when needed) */
.woocommerce-checkout #pickup_date_field,
.woocommerce-checkout #pickup_time_field {
	display: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
	.woocommerce-checkout #collection_method_field {
		margin-top: 1em;
		padding-top: 1em;
	}
}
```

**Step 2: Enqueue CSS in enqueue_scripts method**

In `includes/class-abox-checkout-fields.php`, add before the `wp_enqueue_script()` call in `enqueue_scripts()` method:

```php
// Enqueue CSS
wp_enqueue_style(
	'abox-checkout-fields',
	ABOX_PLUGIN_URL . 'assets/css/checkout-fields.css',
	array(),
	ABOX_VERSION
);
```

**Step 3: Test styling on checkout**

Run: Navigate to checkout page
Expected: See spacing/border above collection method field, fields properly styled

**Step 4: Commit CSS styling**

```bash
git add assets/css/checkout-fields.css includes/class-abox-checkout-fields.php
git commit -m "feat: add CSS styling for checkout fields

Add spacing and visual separation for collection details section.
Hide pickup fields by default for JavaScript to control display."
```

---

## Task 9: Test Complete Flow End-to-End

**Files:**
- None (testing only)

**Step 1: Test checkout with method NOT requiring date/time**

Run:
1. Go to checkout
2. Fill billing details
3. Select "Postage" as collection method
4. Click Place Order

Expected: Order created successfully without errors, no date/time saved to order meta

**Step 2: Test checkout with method requiring date/time**

Run:
1. Go to checkout
2. Fill billing details
3. Select "Pickup - HQ" as collection method
4. Date/time fields appear
5. Fill pickup date and time
6. Click Place Order

Expected: Order created successfully, `_collection_method`, `_pickup_cod_date`, `_pickup_cod_time` saved to order meta

**Step 3: Test validation blocks incomplete submissions**

Run:
1. Go to checkout
2. Select "Pickup - HQ"
3. Leave date/time empty
4. Click Place Order

Expected: Validation errors "Please select a pickup date" and "Please select a pickup time"

**Step 4: Test JavaScript disabled gracefully degrades**

Run:
1. Disable JavaScript in browser
2. Go to checkout
3. Complete order with "Pickup - HQ"

Expected: Date/time fields visible (not hidden), validation still works server-side, order completes

**Step 5: Verify data syncs with admin create order**

Run:
1. Create order from admin (WooCommerce > Create Box Order)
2. Add collection method, date, time
3. Create order
4. View order details

Expected: Same meta keys (`_collection_method`, `_pickup_cod_date`, `_pickup_cod_time`) used for both checkout and admin

**Step 6: Document test results**

Create test summary in commit message for final commit

---

## Task 10: Update Version and Final Commit

**Files:**
- Modify: `agent-box-orders.php:26` (version constant)
- Modify: `agent-box-orders.php:6` (plugin header)

**Step 1: Bump version number**

In `agent-box-orders.php`:

Change line 6:
```php
 * Version: 1.4.0
```

Change line 26:
```php
define( 'ABOX_VERSION', '1.4.0' );
```

**Step 2: Test plugin version updated**

Run: Go to WP Admin > Plugins
Expected: See "Agent Box Orders for WooCommerce" version 1.4.0

**Step 3: Final commit with version bump**

```bash
git add agent-box-orders.php
git commit -m "chore: bump version to 1.4.0

Release version 1.4.0 with checkout collection fields feature.

New Features:
- Collection method dropdown on checkout page
- Pickup date and time fields (conditional display)
- Admin settings to configure which methods require date/time
- Validation for required fields
- Data syncs with admin box order creation

Tested:
- Fields appear on checkout after billing details
- Conditional display based on collection method selection
- Validation blocks incomplete submissions
- Data saves to correct order meta keys
- JavaScript disabled graceful degradation
- Mobile responsive design"
```

---

## Testing Checklist

After completing all tasks, verify:

### Functional Tests
- [ ] Collection method dropdown shows all configured methods
- [ ] Pickup date/time fields hidden by default
- [ ] Selecting method requiring date/time shows fields with slide animation
- [ ] Selecting method NOT requiring date/time hides fields
- [ ] Validation blocks checkout when collection method missing
- [ ] Validation blocks checkout when date/time required but missing
- [ ] Validation allows checkout when all required fields filled
- [ ] Order meta saved correctly to `_collection_method`, `_pickup_cod_date`, `_pickup_cod_time`
- [ ] Same meta keys used by admin create order and checkout
- [ ] Admin settings control which methods require date/time
- [ ] Fields appear after billing, before shipping

### Edge Cases
- [ ] No collection methods configured (fields don't appear)
- [ ] JavaScript disabled (fields visible, validation works)
- [ ] Admin changes settings (new checkouts use updated config)
- [ ] Mobile responsive (fields stack properly)
- [ ] Theme compatibility (fields inherit styling)

### Browser Tests
- [ ] Chrome (date/time pickers work)
- [ ] Firefox (date/time pickers work)
- [ ] Safari (date/time pickers work)
- [ ] Edge (date/time pickers work)

---

## Summary

This plan implements checkout collection fields in 10 incremental tasks:

1. Add admin settings for datetime-required methods
2. Create checkout fields class skeleton
3. Implement add checkout fields method
4. Implement field validation
5. Implement save to order meta
6. Create JavaScript for conditional display
7. Enqueue JavaScript and localize data
8. Add CSS styling (optional)
9. Test complete flow end-to-end
10. Update version and final commit

Each task is atomic, testable, and builds on previous tasks. The implementation follows WordPress and WooCommerce best practices, reuses existing plugin patterns, and maintains consistency with the admin box order creation system.
