# AdvancePayment - Dolibarr Module

Module for managing advance payments and deposits. Allows linking miscellaneous payments (bank entries) to orders, proposals or projects.

## Features

- Link payment → customer order
- Link payment → commercial proposal
- Link payment → project
- Mark advance payments as used
- Integration into forms via hooks
- Selection interface with filters

---

## Installation

### Prerequisites

- Dolibarr >= 19.0
- PHP >= 7.1

### Module Installation

1. Copy the `advancepayment` folder into `htdocs/custom/`
2. Enable the module in **Setup > Modules > Other**

---

## Usage

### Link a payment to an order/proposal

1. Go to a bank entry card (miscellaneous payment)
2. A "Link to..." button appears via the hook
3. Select the order or commercial proposal
4. The link is created

### Use an advance payment on an invoice

From an order or proposal card, linked advance payments are displayed and can be marked as "used" when invoicing.

### Available Pages

| Page | Description |
|------|-------------|
| `paymentlinkto.php` | Link a payment to an order/proposal |
| `paymentlinkto_project.php` | Link a payment to a project |
| `paymentlinkto_list.php` | List of existing links |
| `invoicelinkpayment.php` | Manage advance payments from an invoice |

---

## Architecture

### File Structure

```
advancepayment/
├── class/
│   ├── advancepaymentlink.class.php    # CRUD object + utility
│   └── actions_advancepayment.class.php # Hooks
├── core/modules/
│   └── modAdvancepayment.class.php     # Module descriptor
├── admin/
│   ├── setup.php                       # Configuration
│   └── about.php                       # About
├── js/
│   └── advancepayment.js.php           # JavaScript UI
├── langs/
│   ├── en_US/advancepayment.lang
│   └── fr_FR/advancepayment.lang
├── sql/                                # Database tables
├── paymentlinkto.php                   # Link to order/proposal
├── paymentlinkto_project.php           # Link to project
├── paymentlinkto_list.php              # Links list
└── invoicelinkpayment.php              # Links from invoice
```

### Main Classes

#### `Advancepaymentlink`
CRUD object representing a payment → element link:

| Field | Type | Description |
|-------|------|-------------|
| `rowid` | int | Technical ID |
| `type_link` | varchar | Element type (`commande`, `propal`, `soc`) |
| `payment_rowid` | int | Payment ID (bank entry) |
| `element_rowid` | int | Linked element ID |
| `used` | int | 0 = available, 1 = used |

#### `AdvancePaymentLinks`
Utility class for common operations:

```php
// Get elements linked to a payment
$links = $advancePaymentLinks->getElementLinks($payment_rowid);

// Get payments linked to an element
$payments = $advancePaymentLinks->getPaymentLinks('commande', $order_id);

// Mark an advance payment as used
$advancePaymentLinks->usePaymentLinkFrom('commande', $order_id, $payment_id);

// Remove all links from a payment
$advancePaymentLinks->removePaymentLinks($payment_rowid);
```

### Hook

The module uses the `variouscard` hook to integrate into miscellaneous payment cards and display linking options.

### SQL Table

```sql
CREATE TABLE llx_advancepayment_advancepaymentlink (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    type_link       VARCHAR(255) NOT NULL,
    payment_rowid   INTEGER NOT NULL,
    element_rowid   INTEGER NOT NULL,
    used            INTEGER DEFAULT 0,
    date_creation   DATETIME NOT NULL,
    tms             TIMESTAMP,
    fk_user_creat   INTEGER NOT NULL,
    fk_user_modif   INTEGER
);
```

---

## Development

### Adding a new link type

1. Add the type in `paymentlinkto.php` (`if ($type == '...')` conditions)
2. Add the SQL query to list elements
3. Update `AdvancePaymentLinks::getPaymentLinks()` if needed

### Dolibarr Tables Used

| Table | Usage |
|-------|-------|
| `llx_bank` | Bank entries (payments) |
| `llx_commande` | Customer orders |
| `llx_propal` | Commercial proposals |
| `llx_projet` | Projects |
| `llx_societe` | Third parties |

---

## License

GPLv3 - See COPYING file
