# Changelog

All notable changes to Agent Box Orders for WooCommerce will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
