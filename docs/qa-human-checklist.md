# Harper Order & Customer Tagger — OpenMage Human QA Checklist v1.0

**Test site:** https://harperservice.tech/openmage.dev/
**Admin URL:** https://harperservice.tech/openmage.dev/index.php/admin/
**Module version:** 1.0.0
**Date tested:** _______________
**Tester:** _______________

> **Note:** This module targets OpenMage (Magento 1 community fork). It is a
> separate codebase from the WooCommerce plugin and requires its own QA pass.
> Adobe Commerce / Magento 2 is architecturally different and passing M2 tests
> does **not** imply M1/OpenMage passes.

---

## Before You Start

**Login:** `harperadmin` / `Harper@Mage2026!`

- [ ] Log into the OpenMage admin at the URL above
- [ ] Confirm module is active: **System → Configuration → Advanced → Advanced** → `HarperAgency_OrderCustomerTagger` = Enable
- [ ] Run setup scripts if first install: navigate to any admin page and check that `harper_tagger_*` tables exist in the DB (4 tables: tags, rules, order_tags, customer_tags)
- [ ] Confirm 14 seed tags are present: **Sales → Order & Customer Tags → Tags**

---

## 1 — Tags Admin CRUD

Go to: **Sales → Order & Customer Tags → Tags**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 1.1 | Tags grid loads showing all 14 seed tags | | | |
| 1.2 | Each tag shows: ID, Name, Type, Colour badge preview, Active status | | | |
| 1.3 | Click "Add New Tag" — form loads | | | |
| 1.4 | Create a tag: Name = "QA Test Tag", Type = Order, Colour = #FF5733 | | | |
| 1.5 | Save — tag appears in the grid with correct colour badge | | | |
| 1.6 | Edit the tag — change name to "QA Order Tag" — save | | | |
| 1.7 | Grid reflects the updated name | | | |
| 1.8 | Select the QA tag checkbox → Mass Action → Delete → confirm | | | |
| 1.9 | Tag is removed from the grid | | | |
| 1.10 | Create a customer-type tag: Name = "QA Customer Tag", Type = Customer | | | |

---

## 2 — Rules Admin CRUD

Go to: **Sales → Order & Customer Tags → Rules**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 2.1 | Rules grid loads showing seed rules (if any) | | | |
| 2.2 | Click "Add New Rule" — rule form loads | | | |
| 2.3 | Fill in: Label = "High Value Test", Tag = (any order tag), Trigger = order_place | | | |
| 2.4 | Add condition: Order Total > 50 using the condition builder | | | |
| 2.5 | Set Logic = AND, Priority = 10, Active = Yes | | | |
| 2.6 | Save — rule appears in the rules grid | | | |
| 2.7 | Edit the rule — change priority to 5 — save | | | |
| 2.8 | Grid reflects updated priority | | | |
| 2.9 | Mass Action: Disable — rule shows Active = No | | | |
| 2.10 | Mass Action: Enable — rule shows Active = Yes | | | |
| 2.11 | Mass Action: Delete — rule is removed | | | |

---

## 3 — Orders Grid: Tags Column

Go to: **Sales → Orders**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 3.1 | A "Tags" column is visible in the orders grid | | | |
| 3.2 | Orders with no tags show a dash (—) or empty | | | |
| 3.3 | Tags column is filterable — a dropdown appears in the column header filter row | | | |
| 3.4 | Select a tag in the column filter — grid filters to matching orders only | | | |
| 3.5 | Clearing the filter restores all orders | | | |

---

## 4 — Orders Grid: Inline Tag Editing

Go to: **Sales → Orders**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 4.1 | Clicking a Tags cell opens the tag popover | | | |
| 4.2 | Popover header shows "Order #[ID] — Tags" | | | |
| 4.3 | "Applied" section shows current tags (dash if none) | | | |
| 4.4 | "Add tag" section shows available tags as colour badges | | | |
| 4.5 | Click a badge — tag is applied; moves to Applied section | | | |
| 4.6 | Cell updates without page reload | | | |
| 4.7 | Click × on an applied tag — tag is removed | | | |
| 4.8 | Clicking outside the popover closes it | | | |
| 4.9 | Pressing ESC closes the popover | | | |

---

## 5 — Orders Grid: Bulk Re-run Rules

Go to: **Sales → Orders**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 5.1 | "Re-run Tagger Rules" option appears in the Mass Actions dropdown | | | |
| 5.2 | Check one or more orders — select "Re-run Tagger Rules" — Submit | | | |
| 5.3 | Page reloads with success flash: "Tagger rules re-run on N order(s)" | | | |
| 5.4 | Tags on the selected orders reflect current rules | | | |
| 5.5 | No PHP errors or warnings on the page | | | |

---

## 6 — Order Detail: Tags Display

Go to: **Sales → Orders → click any order**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 6.1 | Order detail page loads without error | | | |
| 6.2 | Tags applied to this order appear in the order detail (sidebar or tab) | | | |
| 6.3 | Tags display as coloured badge pills | | | |
| 6.4 | "No tags applied" message shown for untagged orders | | | |

---

## 7 — Customers Grid: Tags Column

Go to: **Customers → Manage Customers**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 7.1 | A "Tags" column is visible in the customers grid | | | |
| 7.2 | Customers with no tags show a dash (—) | | | |
| 7.3 | Tags column has a filter dropdown in the header row | | | |
| 7.4 | Selecting a tag in the filter shows only tagged customers | | | |

---

## 8 — Customers Grid: Inline Tag Editing

Go to: **Customers → Manage Customers**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 8.1 | Clicking a Tags cell opens the popover for that customer | | | |
| 8.2 | Popover header shows "Customer #[ID] — Tags" | | | |
| 8.3 | Available customer-type tags are shown (not order tags) | | | |
| 8.4 | Click a badge — tag applied; cell updates immediately | | | |
| 8.5 | Click × — tag removed | | | |
| 8.6 | ESC closes popover | | | |

---

## 9 — Customer Detail: Tags Display

Go to: **Customers → Manage Customers → click any customer**

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 9.1 | Customer detail page loads without error | | | |
| 9.2 | Tags applied to this customer appear on the detail page | | | |
| 9.3 | "No tags applied" shown for untagged customers | | | |

---

## 10 — Rule Engine: Auto-Tagging at Order Place

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 10.1 | Create rule: Order Total > 0, tag = any active order tag, Trigger = order_place, Active = Yes | | | |
| 10.2 | Place a test order (use any payment method) | | | |
| 10.3 | Go to **Sales → Orders** — new order has the tag applied automatically | | | |
| 10.4 | Go to **Sales → Orders → Rules** — use "Re-run Tagger Rules" to re-evaluate — same tag still applied | | | |

---

## 11 — Edge Cases

| # | What to check | Pass | Fail | Notes |
|---|---|---|---|---|
| 11.1 | Apply all available tags to one order — "All tags applied." in Add tag section | | | |
| 11.2 | Remove all tags from an order — cell shows dash | | | |
| 11.3 | Popover does not break the Magento grid layout | | | |
| 11.4 | No JavaScript errors in browser console during normal operation | | | |
| 11.5 | No PHP errors in `var/log/exception.log` after completing sections 1–10 | | | |
| 11.6 | Pagination in orders grid still works when Tags column filter is active | | | |

---

## 12 — Cross-Browser Spot Check

Repeat tests **4.1 → 4.9** in each browser:

| Browser | Popover opens | Add works | Remove works | Cell updates | Closes on ESC |
|---|---|---|---|---|---|
| Chrome 120+ | | | | | |
| Firefox 121+ | | | | | |
| Edge 120+ | | | | | |

---

## Sign-Off

| Section | Tester | Date | Result | Notes |
|---|---|---|---|---|
| 1 — Tags CRUD | | | PASS / FAIL | |
| 2 — Rules CRUD | | | PASS / FAIL | |
| 3 — Orders grid Tags column + filter | | | PASS / FAIL | |
| 4 — Orders grid inline editing | | | PASS / FAIL | |
| 5 — Orders bulk re-run | | | PASS / FAIL | |
| 6 — Order detail tags | | | PASS / FAIL | |
| 7 — Customers grid Tags column + filter | | | PASS / FAIL | |
| 8 — Customers grid inline editing | | | PASS / FAIL | |
| 9 — Customer detail tags | | | PASS / FAIL | |
| 10 — Auto-tagging at order place | | | PASS / FAIL | |
| 11 — Edge cases | | | PASS / FAIL | |
| 12 — Cross-browser | | | PASS / FAIL | |

**Overall result:** PASS / FAIL
**Sign-off:** _______________ Date: _______________

---

## Reporting a Failure

Include in your report:
1. Section and test number (e.g. §4 — 4.5)
2. Browser + version
3. OpenMage version (check System → Configuration → Advanced → Admin → Magento Version)
4. Steps you took
5. What you expected vs what happened
6. Screenshot or screen recording
7. Any console errors (F12 → Console) and any lines from `var/log/exception.log`

Send to: **dev@harper.agency**
