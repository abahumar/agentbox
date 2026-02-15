# Quantity Limit Increase Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Increase maximum quantity per item from 999 to 9999 across all locations in the Agent Box Orders plugin.

**Architecture:** Simple find-and-replace operation across 10 locations (4 PHP backend files, 4 JavaScript files, 2 HTML templates). No logic changes, only value updates. Stock management override logic remains unchanged.

**Tech Stack:** PHP, JavaScript, HTML, WordPress/WooCommerce

---

## Task 1: Update Frontend AJAX Handler

**Files:**
- Modify: `wp-content/plugins/agent-box-orders/includes/class-abox-ajax.php:84`
- Modify: `wp-content/plugins/agent-box-orders/includes/class-abox-ajax.php:197`

**Step 1: Update simple products max quantity (line 84)**

Navigate to line 84 and change:

```php
// Before:
$max_qty = 999;

// After:
$max_qty = 9999;
```

**Step 2: Update variable products max quantity (line 197)**

Navigate to line 197 and change:

```php
// Before:
$max_qty = 999;

// After:
$max_qty = 9999;
```

**Step 3: Verify the changes**

Read the file to confirm both changes are correct. The logic flow should remain:
- Set `$max_qty = 9999`
- If product manages stock and has stock > 0, override with stock quantity
- Return `max_qty` in AJAX response

**Step 4: Save the file**

Ensure changes are saved.

---

## Task 2: Update Admin Create Order Handler

**Files:**
- Modify: `wp-content/plugins/agent-box-orders/admin/class-abox-admin-create-order.php:258`
- Modify: `wp-content/plugins/agent-box-orders/admin/class-abox-admin-create-order.php:348`

**Step 1: Update simple products max quantity (line 258)**

Navigate to line 258 and change:

```php
// Before:
$max_qty = 999;

// After:
$max_qty = 9999;
```

**Step 2: Update variable products max quantity (line 348)**

Navigate to line 348 and change:

```php
// Before:
$max_qty = 999;

// After:
$max_qty = 9999;
```

**Step 3: Verify the changes**

Read the file to confirm both changes maintain the same logic structure as the AJAX handler.

**Step 4: Save the file**

Ensure changes are saved.

---

## Task 3: Update JavaScript Fallback Values

**Files:**
- Modify: `wp-content/plugins/agent-box-orders/assets/js/admin-create-order.js:454`
- Modify: `wp-content/plugins/agent-box-orders/assets/js/admin-create-order.js:876`
- Modify: `wp-content/plugins/agent-box-orders/assets/js/public.js:320`
- Modify: `wp-content/plugins/agent-box-orders/assets/js/public.js:629`

**Step 1: Update admin-create-order.js variation fallback (line 454)**

Navigate to line 454 and change:

```javascript
// Before:
'data-max-qty="' + (variation.max_qty || 999) + '">' +

// After:
'data-max-qty="' + (variation.max_qty || 9999) + '">' +
```

**Step 2: Update admin-create-order.js product fallback (line 876)**

Navigate to line 876 and change:

```javascript
// Before:
'data-max-qty="' + (product.max_qty || 999) + '" ' +

// After:
'data-max-qty="' + (product.max_qty || 9999) + '" ' +
```

**Step 3: Update public.js variation fallback (line 320)**

Navigate to line 320 and change:

```javascript
// Before:
'data-max-qty="' + (variation.max_qty || 999) + '">' +

// After:
'data-max-qty="' + (variation.max_qty || 9999) + '">' +
```

**Step 4: Update public.js product fallback (line 629)**

Navigate to line 629 and change:

```javascript
// Before:
'data-max-qty="' + (product.max_qty || 999) + '" ' +

// After:
'data-max-qty="' + (product.max_qty || 9999) + '" ' +
```

**Step 5: Verify all JavaScript changes**

Read both JavaScript files to confirm all 4 fallback values are updated to 9999.

**Step 6: Save the files**

Ensure changes are saved.

---

## Task 4: Update HTML Template Max Attributes

**Files:**
- Modify: `wp-content/plugins/agent-box-orders/public/views/order-form.php:145`
- Modify: `wp-content/plugins/agent-box-orders/admin/views/create-order.php:383`

**Step 1: Update frontend order form max attribute (line 145)**

Navigate to line 145 in `order-form.php` and change:

```html
<!-- Before: -->
max="999"

<!-- After: -->
max="9999"
```

**Step 2: Update admin create order max attribute (line 383)**

Navigate to line 383 in `create-order.php` and change:

```html
<!-- Before: -->
max="999"

<!-- After: -->
max="9999"
```

**Step 3: Verify the changes**

Read both template files to confirm the max attributes are updated and the input structure remains intact.

**Step 4: Save the files**

Ensure changes are saved.

---

## Task 5: Commit All Changes

**Files:**
- All 6 modified files

**Step 1: Check git status**

Run:
```bash
cd wp-content/plugins/agent-box-orders
git status
```

Expected: 6 modified files shown:
- includes/class-abox-ajax.php
- admin/class-abox-admin-create-order.php
- assets/js/admin-create-order.js
- assets/js/public.js
- public/views/order-form.php
- admin/views/create-order.php

**Step 2: Review changes**

Run:
```bash
git diff
```

Expected: All changes show 999 → 9999 (10 total replacements across 6 files)

**Step 3: Stage all changes**

Run:
```bash
git add includes/class-abox-ajax.php \
        admin/class-abox-admin-create-order.php \
        assets/js/admin-create-order.js \
        assets/js/public.js \
        public/views/order-form.php \
        admin/views/create-order.php
```

**Step 4: Commit with descriptive message**

Run:
```bash
git commit -m "$(cat <<'EOF'
feat: increase maximum quantity per item from 999 to 9999

Updated all 10 locations across PHP backend, JavaScript frontend, and HTML
templates to support quantities up to 9999 per item. Stock management limits
still apply when enabled.

Changes:
- PHP: class-abox-ajax.php (2 locations)
- PHP: class-abox-admin-create-order.php (2 locations)
- JS: admin-create-order.js (2 locations)
- JS: public.js (2 locations)
- HTML: order-form.php (1 location)
- HTML: create-order.php (1 location)

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
EOF
)"
```

Expected: Commit successful with all 6 files

**Step 5: Verify commit**

Run:
```bash
git log -1 --stat
```

Expected: Shows commit with 6 files changed, ~10 lines changed total

---

## Task 6: Test Frontend Order Form

**Prerequisites:**
- WordPress site running locally
- Agent Box Orders plugin active
- Access to frontend order form page

**Step 1: Navigate to frontend order form**

Open browser and navigate to the page with the `[abox_order_form]` shortcode.

**Step 2: Add a simple product**

- Search for and add a simple product without stock management
- In the quantity field, enter `9999`
- Verify the input accepts the value
- Verify no validation errors appear

**Step 3: Test maximum boundary**

- Try to enter `10000` in the quantity field
- Expected: Browser validation should prevent this (HTML max attribute)
- Verify the max attribute is working

**Step 4: Add a variable product**

- Search for and add a variable product
- Select a variation
- Enter quantity `9999`
- Verify it works correctly

**Step 5: Test with stock-managed product**

- Add a product with stock management enabled (e.g., 500 units in stock)
- Enter quantity `9999`
- Verify the max quantity is limited to available stock (500)
- Expected: Quantity should be capped at stock level, not 9999

**Step 6: Document results**

Note any issues or unexpected behavior. Expected result: All tests pass.

---

## Task 7: Test Admin Create Order

**Prerequisites:**
- WordPress admin access
- Shop Manager or Administrator role

**Step 1: Navigate to admin create order page**

Open WordPress admin and go to: WooCommerce → Create Box Order

**Step 2: Test simple product quantity**

- Add a simple product without stock management
- Set quantity to `9999`
- Verify it accepts the value
- Verify the order total calculates correctly

**Step 3: Test variable product quantity**

- Add a variable product
- Select a variation
- Set quantity to `9999`
- Verify it works correctly

**Step 4: Test stock-managed product**

- Add a product with stock management enabled
- Try to set quantity higher than available stock
- Expected: Should be limited to available stock

**Step 5: Create a test order**

- Select a customer or create guest order
- Set order status to "Processing"
- Submit the order
- Verify order is created successfully with high quantities

**Step 6: Review created order**

- Navigate to WooCommerce → Orders
- Open the newly created order
- Verify quantities are correctly saved
- Verify order totals are correct

**Step 7: Document results**

Note any issues. Expected result: All tests pass.

---

## Task 8: Final Verification and Documentation

**Step 1: Search for remaining hardcoded 999 values**

Run:
```bash
cd wp-content/plugins/agent-box-orders
grep -rn "\b999\b" --include="*.php" --include="*.js" --include="*.html" .
```

Expected: Only CSS color values (#999) and styling should remain, no quantity-related 999 values.

**Step 2: Run any existing tests**

If the plugin has automated tests:
```bash
# Check for test files
find . -name "*test*" -o -name "*spec*"
```

If tests exist, run them and verify they still pass.

**Step 3: Update CHANGELOG.md**

Add entry to the changelog:

```markdown
## [Unreleased]

### Changed
- Increased maximum quantity per item from 999 to 9999 for greater ordering flexibility
- Stock management limits still apply when inventory tracking is enabled
```

**Step 4: Commit changelog update**

Run:
```bash
git add CHANGELOG.md
git commit -m "docs: update changelog for quantity limit increase"
```

**Step 5: Bump version number (optional)**

If this warrants a version bump, update:
- `agent-box-orders.php` header version
- Plugin version constant

Since this is a minor enhancement, consider bumping to 1.2.2.

**Step 6: Final verification checklist**

Verify:
- ✅ All 10 locations updated (4 PHP, 4 JS, 2 HTML)
- ✅ Frontend order form works with 9999 quantity
- ✅ Admin create order works with 9999 quantity
- ✅ Stock management still limits quantities correctly
- ✅ All changes committed to git
- ✅ Changelog updated

---

## Testing Summary

**Test Matrix:**

| Test Case | Location | Expected Result |
|-----------|----------|----------------|
| Simple product, no stock mgmt | Frontend | Accepts 9999 |
| Simple product, no stock mgmt | Admin | Accepts 9999 |
| Variable product, no stock mgmt | Frontend | Accepts 9999 |
| Variable product, no stock mgmt | Admin | Accepts 9999 |
| Product with 500 stock | Frontend | Max 500 (stock limit) |
| Product with 500 stock | Admin | Max 500 (stock limit) |
| Enter 10000 | Both | Rejected by max attribute |
| Existing orders | Both | Unaffected |

**Risk Assessment:**

- **Low Risk**: Simple value changes, no logic modifications
- **Backward Compatible**: Existing orders and data unaffected
- **Safety Net**: Stock management and HTML max attributes prevent issues

---

## Rollback Plan

If issues arise:

**Step 1: Revert the commit**

```bash
cd wp-content/plugins/agent-box-orders
git revert HEAD~1  # Or specific commit hash
```

**Step 2: Clear any caches**

If using object caching or page caching, clear caches.

**Step 3: Test rollback**

Verify the plugin works correctly with 999 limit restored.

---

## Success Criteria

- ✅ All 10 code locations updated from 999 to 9999
- ✅ Frontend order form accepts quantities up to 9999
- ✅ Admin create order accepts quantities up to 9999
- ✅ Stock management override still works correctly
- ✅ HTML max attributes prevent values over 9999
- ✅ No JavaScript errors in browser console
- ✅ No PHP errors in WordPress debug log
- ✅ Existing orders function normally
- ✅ Order totals calculate correctly with high quantities
- ✅ All changes committed to version control
