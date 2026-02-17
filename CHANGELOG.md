# Changelog

All notable changes to Agent Box Orders for WooCommerce will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2026-02-17

### Added
- **Payment & Collection Metabox**: Integrated payment status and collection method metabox into the plugin (migrated from standalone `wc-order-payment-metabox` plugin)
  - Payment status and collection method dropdowns on order edit screen
  - Pickup/COD date and time fields
  - Sortable order list columns with colored badge indicators
  - Change tracking with order notes
  - HPOS and legacy post type support
  - Database indexes for sorting performance
- **Admin Settings for Payment & Collection**: New "Payment & Collection Settings" section in WooCommerce > Settings > Advanced > Agent Box Orders
  - Repeater fields to add/remove/edit payment statuses and collection methods
  - Customizable badge colors (background and text) per status/method
  - Live preview of badge appearance in settings
- **Dynamic Dropdowns**: Create Box Order page now reads payment statuses and collection methods from settings instead of hardcoded values
- Added "Pending Payment" option to payment statuses
- Increased maximum quantity per item from 999 to 9999

### Changed
- Receipt upload and pickup info fields integrated into Create Box Order sidebar
- Customer and shipping address sections displayed side by side with responsive stacking
- Updated collecting list print template

### Fixed
- Receipt upload text display
- Customer detail metabox at Create Box Order

## [1.2.1] - 2025-02-04

### Changed
- Improved variable product UI: variation select dropdown now displays inline with product field instead of stacking below
- Applied inline layout to admin create order, frontend submit order, and admin edit box order forms
- Added responsive behavior to stack variation select on smaller screens for better mobile usability

## [1.2.0] - 2025-02-04

### Added
- **Admin Order Creation**: New admin page under WooCommerce → Create Box Order
  - Shop managers and administrators can now create box orders directly from the admin dashboard
  - Direct order creation without going through cart/checkout flow
  - Customer selection: Choose existing WooCommerce customer or create guest order with manual billing details
  - Order status selection: Admin can set any WooCommerce order status during creation
  - Full product search with variable product and variation support
  - Real-time order total calculation
  - Order is marked with box metadata for tracking

### Changed
- Updated plugin structure to support admin order creation functionality

## [1.1.0] - 2025-01-30

### Added
- Box order editing capabilities in admin meta box
- Print view for warehouse/fulfillment
- Variable product support with variation selection
- Guest mode for demo purposes

### Changed
- Improved product search with SKU support
- Enhanced UI/UX for order form

### Fixed
- HPOS (High-Performance Order Storage) compatibility
- Various bug fixes and improvements

## [1.0.0] - 2025-01-12

### Added
- Initial release
- Frontend order form via `[abox_order_form]` shortcode
- Multi-box order creation for sales agents
- Customer labeling for each box
- Product search and selection
- Quantity management per item
- Cart integration with WooCommerce checkout
- Box order metadata storage
- Admin column showing box count on orders list
- Role-based access control (Sales Agent, Shop Manager, Administrator)
- Settings page under WooCommerce → Settings → Advanced → Agent Box Orders
  - Maximum boxes per order
  - Maximum items per box
  - Allowed user roles
  - Clear cart on submit option
