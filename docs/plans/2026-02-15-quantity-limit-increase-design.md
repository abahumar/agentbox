# Increase Maximum Quantity Limit Design

**Date:** 2026-02-15
**Status:** Approved
**Author:** Design session with user

## Overview

### Current State
- Maximum quantity per item is hardcoded to 999 across the plugin
- This limit applies to both simple and variable products
- When stock management is enabled, the limit is reduced to available stock

### Desired State
- Increase maximum quantity to 9999 (practically unlimited)
- Maintain stock management override - when enabled, quantity is still limited to available stock
- No changes to UI or user experience, just increased capacity

### Success Criteria
- Users can add up to 9999 units of any item in a box order
- Stock management continues to prevent overselling when enabled
- No impact on existing orders or functionality

## Approach Selection

Three approaches were considered:

1. **Very Large Number (9999) - SELECTED**
   - Simple, clean implementation
   - Still has a safety cap to prevent accidental errors
   - Works well with HTML input fields and JavaScript
   - Stock limits still apply when enabled

2. **Use PHP_INT_MAX (2+ billion)**
   - Truly unlimited but could cause frontend/database issues
   - No safety cap for accidental typos

3. **Configurable Setting**
   - Most flexible but more complex
   - Overkill for this requirement

**Decision:** Approach 1 (9999) provides the best balance of simplicity, safety, and practical unlimited capacity.

## Technical Implementation

### Files to Modify

#### PHP Backend (4 locations)

1. **includes/class-abox-ajax.php:84** - Frontend simple products
   ```php
   // Before: $max_qty = 999;
   // After:  $max_qty = 9999;
   ```

2. **includes/class-abox-ajax.php:197** - Frontend variations
   ```php
   // Before: $max_qty = 999;
   // After:  $max_qty = 9999;
   ```

3. **admin/class-abox-admin-create-order.php:258** - Admin simple products
   ```php
   // Before: $max_qty = 999;
   // After:  $max_qty = 9999;
   ```

4. **admin/class-abox-admin-create-order.php:348** - Admin variations
   ```php
   // Before: $max_qty = 999;
   // After:  $max_qty = 9999;
   ```

#### JavaScript Fallbacks (4 locations)

5. **assets/js/admin-create-order.js:454** - Variation fallback
   ```javascript
   // Before: (variation.max_qty || 999)
   // After:  (variation.max_qty || 9999)
   ```

6. **assets/js/admin-create-order.js:876** - Product fallback
   ```javascript
   // Before: (product.max_qty || 999)
   // After:  (product.max_qty || 9999)
   ```

7. **assets/js/public.js:320** - Variation fallback
   ```javascript
   // Before: (variation.max_qty || 999)
   // After:  (variation.max_qty || 9999)
   ```

8. **assets/js/public.js:629** - Product fallback
   ```javascript
   // Before: (product.max_qty || 999)
   // After:  (product.max_qty || 9999)
   ```

#### HTML Templates (2 locations)

9. **public/views/order-form.php:145** - Frontend input max attribute
   ```html
   <!-- Before: max="999" -->
   <!-- After:  max="9999" -->
   ```

10. **admin/views/create-order.php:383** - Admin input max attribute
    ```html
    <!-- Before: max="999" -->
    <!-- After:  max="9999" -->
    ```

### Logic Flow (Unchanged)

The existing logic remains the same:
1. Set default `$max_qty = 9999`
2. If product has stock management enabled AND stock quantity > 0:
   - Override `$max_qty` with actual stock quantity
3. Return `max_qty` in response for frontend validation

### Why Multiple Locations

The plugin handles different contexts separately:
- **Frontend vs Admin**: Separate AJAX handlers for frontend order form and admin create order page
- **Simple vs Variable**: Different methods for simple products and variable products
- **Backend vs Frontend**: PHP sets the max, JavaScript uses fallbacks, HTML enforces client-side

All 10 locations must be updated for consistency across all product types and interfaces.

## Testing & Validation

### Test Cases

1. **Frontend Order Form**
   - Add simple product with quantity up to 9999
   - Add variable product with quantity up to 9999
   - Verify input accepts values beyond 999
   - Attempt to enter 10000 (should be prevented by max attribute)

2. **Admin Create Order**
   - Create order from admin panel with high quantities
   - Verify same behavior as frontend

3. **Stock Management Override**
   - Test product with stock management enabled and 500 units in stock
   - Verify maximum quantity is limited to 500 (not 9999)
   - Test product without stock management
   - Verify maximum quantity is 9999

4. **Edge Cases**
   - Test entering exactly 9999 units
   - Test entering 10000 units (should fail validation)
   - Verify existing orders with quantities ≤999 are unaffected

### Backward Compatibility

- ✅ No database schema changes
- ✅ No changes to order processing logic
- ✅ Only affects maximum allowed input value
- ✅ Existing orders remain unchanged
- ✅ Stock management safety net preserved

### Risk Assessment

**Risk Level:** Low

- Simple value changes with no logic modifications
- Stock management prevents overselling
- HTML max attributes prevent UI accidents
- No breaking changes to API or data structure

## Next Steps

1. Create implementation plan
2. Execute changes across all 10 locations
3. Test thoroughly in both frontend and admin
4. Verify stock management still works correctly
5. Update plugin version and changelog if needed
