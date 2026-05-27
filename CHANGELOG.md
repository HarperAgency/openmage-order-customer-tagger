# Changelog — Harper Order & Customer Tagger (OpenMage)

All notable changes to this project will be documented in this file.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [1.0.0] — 2026-05-27

### Added
- Admin Tags CRUD under Sales → Order & Customer Tags → Tags
- Admin Rules CRUD with JS condition builder (15 order/customer context fields)
- Orders grid: Tags column with filter dropdown and inline click-to-edit popover
- Orders grid: "Re-run Tagger Rules" mass action
- Customers grid: Tags column with filter dropdown and inline click-to-edit popover
- Rule engine observer wired to `sales_order_save_after` and `customer_save_after`
- Grid column injection via `core_block_abstract_prepare_layout_after` (no class rewrites)
- DB schema: 4 tables (harper_tagger_tags, rules, order_tags, customer_tags)
- 14 seed tags and 14 seed rules on first install
- Uninstall script drops all 4 tables in dependency order
- Composer deployment via `magento-hackathon/magento-composer-installer`

### Security
- CSRF protection: `_validateFormKey()` added to all save actions in Tags and Rules controllers
- ACL: all admin actions gated by `sales/harper_tagger` resource
