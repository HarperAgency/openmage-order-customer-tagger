# OpenMage Order & Customer Tagger

Automatically tag orders and customers in OpenMage (Magento 1) using configurable rule-based conditions — no code required.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)
![OpenMage](https://img.shields.io/badge/OpenMage-19%2B-orange)
![License](https://img.shields.io/badge/license-MIT-green)

---

## What It Does

Define rules in the Magento admin. When an order is saved, the module evaluates those rules and applies tags to the order and/or customer automatically.

**Example rules:**
- Tag orders over $500 as `High Value`
- Tag customers from international addresses as `International`
- Tag first-time buyers as `First Order`
- Tag orders paid by cheque as `Unconfirmed Payment`

Tags appear on the order detail screen and the customer account, making it easy to filter, segment, and act on your data.

---

## Features

- Rule builder UI in Magento admin — no coding needed
- Supports order total, payment method, shipping method, address, item count, and customer history conditions
- Tags applied automatically on every order save
- Tags visible on order and customer admin screens
- Powered by the [Harper Rule Engine](https://github.com/harper-agency/harper-rule-engine)

---

## Requirements

- OpenMage LTS 19.x or 20.x (Magento 1 compatible)
- PHP 7.4+
- MySQL 5.7+

---

## Installation

### From ZIP (recommended)

1. Download the latest release ZIP from the [Releases](../../releases) page
2. Extract to your OpenMage root — the `app/` directory merges with your existing one
3. Log in to admin and go to **System → Cache Management → Flush Magento Cache**
4. Go to **System → Configuration → Harper Agency → Order & Customer Tagger** to verify

### From Source

```bash
git clone https://github.com/harper-agency/openmage-order-customer-tagger.git
```

Copy the `app/` directory to your OpenMage root and flush the cache.

---

## Usage

1. Go to **Catalog → Order & Customer Tagger → Tags** — review the 14 seed tags or create your own
2. Go to **Catalog → Order & Customer Tagger → Rules** → click **Add Rule**
3. Set conditions, choose the tag to apply, and save
4. Place or update an order — matching tags are applied automatically

---

## Database Tables

The module creates 4 tables on first install:

| Table | Purpose |
|---|---|
| `harper_tagger_tag` | Tag definitions (name, colour, type) |
| `harper_tagger_rule` | Rule definitions and conditions |
| `harper_tagger_order_tag` | Order ↔ tag associations |
| `harper_tagger_customer_tag` | Customer ↔ tag associations |

---

## Development & Testing

```bash
composer install
php vendor/bin/phpunit --configuration tests/phpunit.xml
```

Tests require PHP 7.4+ and run without a Magento installation — all Mage classes are stubbed.

---

## Support & Custom Development

This module is free and open source. For custom rule conditions, integrations, or OpenMage/Magento development:

**[Harper Agency](https://harperservice.tech)** — OpenMage, Magento & eCommerce development

---

## License

MIT — see [LICENSE](LICENSE)
