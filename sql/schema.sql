-- TradePOSX (PHP rebuild) — multi-tenant schema for TradeERP + ServiceERP.
-- Every business table carries org_id, always scoped server-side (see includes/Tenant.php,
-- includes/Auth.php::requireOrgAccess) — never trust a tenant id supplied by the client.
-- `legacy_id` columns hold the original KiranaMate Firestore/Mongo-style string id, used only
-- during the one-time data migration (sql/migrate/) to cross-reference old records.

SET NAMES utf8mb4;

-- ── Platform ────────────────────────────────────────────────────────────

CREATE TABLE organizations (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  slug          VARCHAR(60) NOT NULL UNIQUE,
  name          VARCHAR(150) NOT NULL,
  business_type ENUM('trade','service','both') NOT NULL DEFAULT 'both',
  trading_sector VARCHAR(60) NULL,
  gstin         VARCHAR(20) NULL,
  address       VARCHAR(255) NULL,
  phone         VARCHAR(20) NULL,
  theme_color   VARCHAR(20) NULL,
  status        ENUM('active','demo','suspended') NOT NULL DEFAULT 'active',
  legacy_id     VARCHAR(64) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_org_legacy (legacy_id)
) ENGINE=InnoDB;

CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  org_id        INT NULL,                        -- NULL only for platform superadmin
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NULL,
  -- Nullable, optional alongside email — the old KiranaMate app logged in by
  -- username only (sometimes an email-shaped string, sometimes not), so
  -- migrated accounts need this to keep their exact existing login working.
  -- New (non-migrated) signups from the SuperAdmin form just leave it null
  -- and log in with email as usual.
  username      VARCHAR(60) NULL,
  mobile        VARCHAR(20) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('superadmin','owner','admin','staff') NOT NULL DEFAULT 'staff',
  permissions   JSON NULL,
  status        ENUM('active','disabled') NOT NULL DEFAULT 'active',
  legacy_id     VARCHAR(64) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_users_org (org_id),
  UNIQUE KEY uniq_users_org_username (org_id, username),
  -- Global, since the platform-level /login "Login" tab looks a user up by
  -- email alone with no org in the URL to scope by. NULL-safe in MySQL —
  -- migrated username-only accounts (email NULL) never collide on this.
  UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE activity_log (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  org_id      INT NOT NULL,
  user_id     INT NULL,
  action      VARCHAR(100) NOT NULL,
  entity_type VARCHAR(60) NULL,
  entity_id   INT NULL,
  description VARCHAR(255) NULL,
  ip_address  VARCHAR(45) NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_activity_org (org_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  org_id     INT NOT NULL,
  user_id    INT NULL,
  type       VARCHAR(60) NOT NULL,
  message    VARCHAR(255) NOT NULL,
  link       VARCHAR(255) NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notif_org_user (org_id, user_id)
) ENGINE=InnoDB;

-- ── TradeERP ────────────────────────────────────────────────────────────

CREATE TABLE suppliers (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  org_id             INT NOT NULL,
  name               VARCHAR(150) NOT NULL,
  contact_person     VARCHAR(120) NULL,
  mobile             VARCHAR(20) NULL,
  company_name       VARCHAR(150) NULL,
  category           VARCHAR(80) NULL,
  gstin              VARCHAR(20) NULL,
  address            VARCHAR(255) NULL,
  city               VARCHAR(80) NULL,
  outstanding_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
  notes              TEXT NULL,
  legacy_id          VARCHAR(64) NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_suppliers_org (org_id)
) ENGINE=InnoDB;

CREATE TABLE products (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  org_id             INT NOT NULL,
  name               VARCHAR(200) NOT NULL,
  sku                VARCHAR(80) NULL,
  barcode            VARCHAR(80) NULL,
  category           VARCHAR(80) NOT NULL DEFAULT 'Other',
  brand              VARCHAR(120) NULL,
  unit               VARCHAR(20) NOT NULL DEFAULT 'pcs',
  purchase_price     DECIMAL(12,2) NOT NULL DEFAULT 0,
  selling_price      DECIMAL(12,2) NOT NULL DEFAULT 0,
  mrp                DECIMAL(12,2) NOT NULL DEFAULT 0,
  current_stock      DECIMAL(12,3) NOT NULL DEFAULT 0,
  min_stock          DECIMAL(12,3) NOT NULL DEFAULT 0,
  supplier_id        INT NULL,
  gst_percent        DECIMAL(5,2) NOT NULL DEFAULT 0,
  hsn                VARCHAR(20) NULL,
  batch_number       VARCHAR(60) NULL,
  expiry_date        DATE NULL,
  manufacturing_date DATE NULL,
  image              VARCHAR(255) NULL,
  description        TEXT NULL,
  -- Sparse sector-specific fields (grade, thickness, IMEI, purity, vehicle model, etc.)
  -- — kept as JSON rather than 25+ mostly-null columns, matching how sparse they are
  -- per trading sector (see kiranamate/src/types.ts Product interface).
  attributes         JSON NULL,
  status             ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
  legacy_id          VARCHAR(64) NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  INDEX idx_products_org (org_id),
  INDEX idx_products_org_barcode (org_id, barcode),
  INDEX idx_products_org_sku (org_id, sku)
) ENGINE=InnoDB;

CREATE TABLE customers (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  org_id             INT NOT NULL,
  name               VARCHAR(150) NOT NULL,
  mobile             VARCHAR(20) NOT NULL,
  address            VARCHAR(255) NULL,
  area               VARCHAR(80) NULL,
  email              VARCHAR(150) NULL,
  opening_balance    DECIMAL(12,2) NOT NULL DEFAULT 0,
  current_balance    DECIMAL(12,2) NOT NULL DEFAULT 0,  -- authoritative running Udhaar balance
  credit_limit       DECIMAL(12,2) NULL,
  trust_rating       VARCHAR(30) NULL,
  notes              TEXT NULL,
  legacy_id          VARCHAR(64) NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_customers_org (org_id),
  INDEX idx_customers_org_mobile (org_id, mobile)
) ENGINE=InnoDB;

CREATE TABLE customer_transactions (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  org_id         INT NOT NULL,
  customer_id    INT NOT NULL,
  type           ENUM('CREDIT_SALE','PAYMENT_RECEIVED','OPENING_BALANCE','RETURN_CREDIT','DISCOUNT_ADJUSTMENT') NOT NULL,
  amount         DECIMAL(12,2) NOT NULL,
  balance_after  DECIMAL(12,2) NOT NULL,
  payment_method ENUM('CASH','UPI','BANK','OTHER') NULL,
  reference_id   VARCHAR(60) NULL,   -- Sale ID / Purchase ID / Payment Receipt No
  notes          VARCHAR(255) NULL,
  created_by     VARCHAR(120) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  INDEX idx_ctxn_org_customer (org_id, customer_id)
) ENGINE=InnoDB;

CREATE TABLE purchases (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  org_id              INT NOT NULL,
  purchase_number     VARCHAR(40) NOT NULL,
  supplier_id         INT NULL,
  supplier_name       VARCHAR(150) NULL,
  invoice_number      VARCHAR(60) NULL,
  purchase_date       DATE NULL,
  invoice_date        DATE NULL,
  subtotal            DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_amount          DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount_amount     DECIMAL(12,2) NOT NULL DEFAULT 0,
  freight_charges     DECIMAL(12,2) NOT NULL DEFAULT 0,
  grand_total         DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount         DECIMAL(12,2) NOT NULL DEFAULT 0,
  payment_status      ENUM('PAID','PENDING','PARTIAL') NOT NULL DEFAULT 'PENDING',
  payment_method      ENUM('CASH','UPI','BANK','OTHER') NULL,
  notes               TEXT NULL,
  status              ENUM('CANCELLED') NULL,   -- NULL = normal completed purchase; never hard-delete a purchase
  cancel_reason       VARCHAR(255) NULL,
  cancelled_at        TIMESTAMP NULL,
  legacy_id           VARCHAR(64) NULL,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
  INDEX idx_purchases_org (org_id)
) ENGINE=InnoDB;

CREATE TABLE purchase_items (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  org_id         INT NOT NULL,
  purchase_id    INT NOT NULL,
  product_id     INT NULL,
  product_name   VARCHAR(200) NOT NULL,
  unit           VARCHAR(20) NULL,
  quantity       DECIMAL(12,3) NOT NULL DEFAULT 0,
  purchase_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  gst_percent    DECIMAL(5,2) NULL,
  gst_amount     DECIMAL(12,2) NULL,
  discount       DECIMAL(12,2) NULL,
  selling_price  DECIMAL(12,2) NULL,
  mrp            DECIMAL(12,2) NULL,
  total_price    DECIMAL(12,2) NOT NULL DEFAULT 0,
  hsn            VARCHAR(20) NULL,
  batch_number   VARCHAR(60) NULL,
  expiry_date    DATE NULL,
  free_qty       DECIMAL(12,3) NULL,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  INDEX idx_pitems_purchase (purchase_id)
) ENGINE=InnoDB;

CREATE TABLE sales (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  org_id            INT NOT NULL,
  sale_number       VARCHAR(40) NOT NULL,
  customer_id       INT NULL,
  customer_name     VARCHAR(150) NULL,
  customer_mobile   VARCHAR(20) NULL,
  customer_gstin    VARCHAR(20) NULL,
  subtotal          DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount          DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_type          ENUM('INCLUSIVE','EXCLUSIVE','EXEMPT') NULL,
  gst_rate          DECIMAL(5,2) NULL,
  taxable_amount    DECIMAL(12,2) NULL,
  cgst_amount       DECIMAL(12,2) NULL,
  sgst_amount       DECIMAL(12,2) NULL,
  igst_amount       DECIMAL(12,2) NULL,
  total_tax_amount  DECIMAL(12,2) NULL,
  grand_total       DECIMAL(12,2) NOT NULL DEFAULT 0,
  payment_method    ENUM('CASH','UPI','BANK','OTHER','CREDIT') NOT NULL DEFAULT 'CASH',
  payment_status    ENUM('PAID','PENDING','PARTIAL') NOT NULL DEFAULT 'PAID',
  received_amount   DECIMAL(12,2) NULL,
  change_amount     DECIMAL(12,2) NULL,
  created_by_name   VARCHAR(120) NULL,
  notes             TEXT NULL,
  status            ENUM('CANCELLED') NULL,   -- NULL = normal completed sale; never hard-delete a sale
  cancel_reason     VARCHAR(255) NULL,
  cancelled_at      TIMESTAMP NULL,
  legacy_id         VARCHAR(64) NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  INDEX idx_sales_org (org_id),
  INDEX idx_sales_org_created (org_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  org_id       INT NOT NULL,
  sale_id      INT NOT NULL,
  product_id   INT NULL,
  product_name VARCHAR(200) NOT NULL,
  quantity     DECIMAL(12,3) NOT NULL DEFAULT 0,
  unit_price   DECIMAL(12,2) NOT NULL DEFAULT 0,
  mrp          DECIMAL(12,2) NULL,
  total_price  DECIMAL(12,2) NOT NULL DEFAULT 0,
  gst_rate     DECIMAL(5,2) NULL,
  gst_amount   DECIMAL(12,2) NULL,
  -- Snapshotted from the product at sale time so the printed invoice stays
  -- accurate even if the product's own current batch/expiry changes later.
  hsn          VARCHAR(20) NULL,
  batch_number VARCHAR(60) NULL,
  expiry_date  DATE NULL,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  INDEX idx_sitems_sale (sale_id)
) ENGINE=InnoDB;

CREATE TABLE expenses (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  org_id     INT NOT NULL,
  category   VARCHAR(80) NOT NULL,
  amount     DECIMAL(12,2) NOT NULL DEFAULT 0,
  notes      VARCHAR(255) NULL,
  legacy_id  VARCHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_expenses_org (org_id)
) ENGINE=InnoDB;

CREATE TABLE inventory_transactions (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  org_id       INT NOT NULL,
  product_id   INT NOT NULL,
  type         ENUM('STOCK_IN_PURCHASE','STOCK_OUT_SALE','MANUAL_ADD','MANUAL_REDUCE','DAMAGE_EXPIRED','INITIAL_STOCK','SALE_CANCELLED') NOT NULL,
  quantity     DECIMAL(12,3) NOT NULL,
  reference_id VARCHAR(60) NULL,
  notes        VARCHAR(255) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_inv_org_product (org_id, product_id)
) ENGINE=InnoDB;

-- ── ServiceERP ──────────────────────────────────────────────────────────

CREATE TABLE service_items (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  org_id           INT NOT NULL,
  name             VARCHAR(150) NOT NULL,
  category         VARCHAR(80) NULL,
  price            DECIMAL(12,2) NOT NULL DEFAULT 0,
  duration_minutes INT NULL,
  -- Days of service warranty a completed job of this type carries — set on
  -- job_cards.warranty_expiry_date automatically when the job is marked
  -- Completed/Delivered (0/null = no service warranty for this item).
  warranty_days    INT NULL DEFAULT 0,
  gst_percent      DECIMAL(5,2) NOT NULL DEFAULT 0,
  sector           VARCHAR(60) NOT NULL,
  description      TEXT NULL,
  is_popular       TINYINT(1) NOT NULL DEFAULT 0,
  legacy_id        VARCHAR(64) NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_svcitems_org (org_id)
) ENGINE=InnoDB;

CREATE TABLE service_customers (
  id                        INT AUTO_INCREMENT PRIMARY KEY,
  org_id                    INT NOT NULL,
  name                      VARCHAR(150) NOT NULL,
  mobile                    VARCHAR(20) NOT NULL,
  email                     VARCHAR(150) NULL,
  address                   VARCHAR(255) NULL,
  sector                    VARCHAR(60) NULL,
  total_spent               DECIMAL(12,2) NOT NULL DEFAULT 0,
  amc_status                ENUM('Active','Expired','None') NULL,
  amc_expiry_date           DATE NULL,
  notes                     TEXT NULL,
  legacy_id                 VARCHAR(64) NULL,
  created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_svccust_org (org_id),
  INDEX idx_svccust_org_mobile (org_id, mobile)
) ENGINE=InnoDB;

CREATE TABLE service_staff (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  org_id                INT NOT NULL,
  name                  VARCHAR(120) NOT NULL,
  role                  VARCHAR(80) NULL,
  mobile                VARCHAR(20) NULL,
  specialty             VARCHAR(120) NULL,
  rating                DECIMAL(3,2) NULL,
  total_jobs_completed  INT NOT NULL DEFAULT 0,
  commission_percent    DECIMAL(5,2) NOT NULL DEFAULT 0,
  daily_target          DECIMAL(12,2) NULL,
  status                ENUM('Active','On-Leave','Busy') NOT NULL DEFAULT 'Active',
  commission_payable    DECIMAL(12,2) NOT NULL DEFAULT 0,
  legacy_id             VARCHAR(64) NULL,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_svcstaff_org (org_id)
) ENGINE=InnoDB;

CREATE TABLE appointments (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  org_id         INT NOT NULL,
  customer_id    INT NULL,
  customer_name  VARCHAR(150) NULL,
  mobile         VARCHAR(20) NULL,
  service_id     INT NULL,
  service_name   VARCHAR(150) NULL,
  service_sector VARCHAR(60) NULL,
  staff_id       INT NULL,
  staff_name     VARCHAR(120) NULL,
  appt_date      DATE NOT NULL,
  appt_time      TIME NOT NULL,
  status         ENUM('Scheduled','In-Progress','Completed','Cancelled','No-Show') NOT NULL DEFAULT 'Scheduled',
  notes          VARCHAR(255) NULL,
  total_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
  legacy_id      VARCHAR(64) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES service_customers(id) ON DELETE SET NULL,
  FOREIGN KEY (staff_id) REFERENCES service_staff(id) ON DELETE SET NULL,
  INDEX idx_appt_org_date (org_id, appt_date)
) ENGINE=InnoDB;

CREATE TABLE job_cards (
  id                        INT AUTO_INCREMENT PRIMARY KEY,
  org_id                    INT NOT NULL,
  job_no                    VARCHAR(40) NOT NULL,
  customer_id               INT NULL,
  customer_name             VARCHAR(150) NULL,
  mobile                    VARCHAR(20) NULL,
  service_sector            VARCHAR(60) NULL,
  assigned_staff_id         INT NULL,
  assigned_staff_name       VARCHAR(120) NULL,
  status                    ENUM('Pending','In-Progress','Completed','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
  priority                  ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  issue_description         TEXT NULL,
  device_or_vehicle_info    VARCHAR(150) NULL,
  -- Captured at intake (is this device already under manufacturer/purchase
  -- warranty?) or set automatically on completion from the service item's
  -- warranty_days ('Service Warranty' — this repair itself is guaranteed).
  warranty_status           ENUM('None','Manufacturer','Extended','Service Warranty') NOT NULL DEFAULT 'None',
  warranty_expiry_date      DATE NULL,
  labour_charges            DECIMAL(12,2) NOT NULL DEFAULT 0,
  material_charges          DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount              DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount                DECIMAL(12,2) NOT NULL DEFAULT 0,
  balance_amount            DECIMAL(12,2) NOT NULL DEFAULT 0,
  estimated_completion_date DATE NULL,
  actual_completion_date    DATE NULL,
  remarks                   TEXT NULL,
  legacy_id                 VARCHAR(64) NULL,
  created_at                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES service_customers(id) ON DELETE SET NULL,
  FOREIGN KEY (assigned_staff_id) REFERENCES service_staff(id) ON DELETE SET NULL,
  INDEX idx_jobcards_org (org_id)
) ENGINE=InnoDB;

CREATE TABLE job_card_parts (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  org_id      INT NOT NULL,
  job_card_id INT NOT NULL,
  name        VARCHAR(150) NOT NULL,
  quantity    DECIMAL(12,3) NOT NULL DEFAULT 1,
  unit_price  DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (job_card_id) REFERENCES job_cards(id) ON DELETE CASCADE,
  INDEX idx_jcparts_job (job_card_id)
) ENGINE=InnoDB;

CREATE TABLE service_packages (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  org_id            INT NOT NULL,
  name              VARCHAR(150) NOT NULL,
  sector            VARCHAR(60) NULL,
  price             DECIMAL(12,2) NOT NULL DEFAULT 0,
  duration_days     INT NULL,
  included_services JSON NULL,
  discount_percent  DECIMAL(5,2) NULL,
  description       TEXT NULL,
  is_amc            TINYINT(1) NOT NULL DEFAULT 0,
  legacy_id         VARCHAR(64) NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_svcpkg_org (org_id)
) ENGINE=InnoDB;

CREATE TABLE service_invoices (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  org_id           INT NOT NULL,
  invoice_no       VARCHAR(40) NOT NULL,
  customer_id      INT NULL,
  customer_name    VARCHAR(150) NULL,
  mobile           VARCHAR(20) NULL,
  invoice_date     DATE NOT NULL,
  labour_charges   DECIMAL(12,2) NOT NULL DEFAULT 0,
  material_charges DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount         DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_type         ENUM('INCLUSIVE','EXCLUSIVE','EXEMPT') NULL,
  gst_amount       DECIMAL(12,2) NOT NULL DEFAULT 0,
  taxable_amount   DECIMAL(12,2) NULL,
  cgst_amount      DECIMAL(12,2) NULL,
  sgst_amount      DECIMAL(12,2) NULL,
  grand_total      DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount      DECIMAL(12,2) NOT NULL DEFAULT 0,
  balance_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,
  payment_method   ENUM('CASH','UPI','BANK','OTHER') NULL,
  status           ENUM('PAID','PARTIAL','UNPAID','CANCELLED') NOT NULL DEFAULT 'UNPAID',
  job_card_id      INT NULL,
  appointment_id   INT NULL,
  notes            TEXT NULL,
  cancel_reason    VARCHAR(255) NULL,
  cancelled_at     TIMESTAMP NULL,
  legacy_id        VARCHAR(64) NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES service_customers(id) ON DELETE SET NULL,
  FOREIGN KEY (job_card_id) REFERENCES job_cards(id) ON DELETE SET NULL,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
  INDEX idx_svcinv_org (org_id)
) ENGINE=InnoDB;

CREATE TABLE service_quotations (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  org_id           INT NOT NULL,
  quote_no         VARCHAR(40) NOT NULL,
  customer_id      INT NULL,
  customer_name    VARCHAR(150) NULL,
  mobile           VARCHAR(20) NULL,
  quote_date       DATE NOT NULL,
  valid_until      DATE NULL,
  labour_charges   DECIMAL(12,2) NOT NULL DEFAULT 0,
  material_charges DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount         DECIMAL(12,2) NOT NULL DEFAULT 0,
  gst_amount       DECIMAL(12,2) NOT NULL DEFAULT 0,
  grand_total      DECIMAL(12,2) NOT NULL DEFAULT 0,
  status           ENUM('Draft','Sent','Approved','ConvertedToJob','ConvertedToInvoice') NOT NULL DEFAULT 'Draft',
  notes            TEXT NULL,
  legacy_id        VARCHAR(64) NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES service_customers(id) ON DELETE SET NULL,
  INDEX idx_svcquote_org (org_id)
) ENGINE=InnoDB;

-- Shared line-items table for both invoices and quotations (type + parent id).
CREATE TABLE service_invoice_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  org_id        INT NOT NULL,
  invoice_id    INT NULL,
  quotation_id  INT NULL,
  service_id    INT NULL,
  name          VARCHAR(150) NOT NULL,
  item_type     ENUM('SERVICE','MATERIAL','LABOUR','PACKAGE') NOT NULL DEFAULT 'SERVICE',
  price         DECIMAL(12,2) NOT NULL DEFAULT 0,
  quantity      DECIMAL(12,3) NOT NULL DEFAULT 1,
  total         DECIMAL(12,2) NOT NULL DEFAULT 0,
  gst_rate      DECIMAL(5,2) NULL,
  gst_amount    DECIMAL(12,2) NULL,
  staff_id      INT NULL,   -- lets invoice creation auto-accrue this staff member's commission
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (invoice_id) REFERENCES service_invoices(id) ON DELETE CASCADE,
  FOREIGN KEY (quotation_id) REFERENCES service_quotations(id) ON DELETE CASCADE,
  FOREIGN KEY (staff_id) REFERENCES service_staff(id) ON DELETE SET NULL,
  INDEX idx_sii_invoice (invoice_id),
  INDEX idx_sii_quotation (quotation_id)
) ENGINE=InnoDB;

CREATE TABLE service_payments (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  org_id        INT NOT NULL,
  receipt_no    VARCHAR(40) NOT NULL,
  customer_id   INT NULL,
  customer_name VARCHAR(150) NULL,
  amount        DECIMAL(12,2) NOT NULL DEFAULT 0,
  payment_date  DATE NOT NULL,
  method        ENUM('CASH','UPI','BANK','OTHER') NOT NULL DEFAULT 'CASH',
  reference_no  VARCHAR(60) NULL,
  invoice_id    INT NULL,
  job_card_id   INT NULL,
  notes         VARCHAR(255) NULL,
  legacy_id     VARCHAR(64) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (customer_id) REFERENCES service_customers(id) ON DELETE SET NULL,
  FOREIGN KEY (invoice_id) REFERENCES service_invoices(id) ON DELETE SET NULL,
  FOREIGN KEY (job_card_id) REFERENCES job_cards(id) ON DELETE SET NULL,
  INDEX idx_svcpay_org (org_id)
) ENGINE=InnoDB;

-- ── Offline mobile POS support ─────────────────────────────────────────
-- A sale/invoice created offline on the mobile card POS carries a client-generated
-- UUID (client_ref) so a retried sync after a flaky connection can't double-insert it.
ALTER TABLE sales ADD COLUMN client_ref VARCHAR(64) NULL, ADD UNIQUE KEY uniq_sales_org_clientref (org_id, client_ref);
ALTER TABLE service_invoices ADD COLUMN client_ref VARCHAR(64) NULL, ADD UNIQUE KEY uniq_svcinv_org_clientref (org_id, client_ref);

-- Lets a purchase be cancelled (reversing its stock-in and supplier balance), matching
-- how sales/service_invoices are voided/cancelled rather than hard-deleted.
ALTER TABLE purchases ADD COLUMN status ENUM('CANCELLED') NULL, ADD COLUMN cancel_reason VARCHAR(255) NULL, ADD COLUMN cancelled_at TIMESTAMP NULL;

-- ── Customer Outreach (review requests / promotions over WhatsApp) ─────
-- No WhatsApp Business API is configured anywhere in this app (see
-- config/constants.php's WHATSAPP_PROVIDER) — every WhatsApp send in TradePOSX,
-- including this one, is a wa.me click-to-chat link the owner clicks themselves.
-- "Recurring" therefore means the UI surfaces who's due again on the chosen
-- cadence, not a background job that sends unattended.
CREATE TABLE outreach_campaigns (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  org_id        INT NOT NULL,
  name          VARCHAR(150) NOT NULL,
  campaign_type ENUM('REVIEW','PROMOTION','CUSTOM') NOT NULL DEFAULT 'CUSTOM',
  message       TEXT NOT NULL,
  recurrence    ENUM('ONE_TIME','WEEKLY','EVERY_15_DAYS','MONTHLY') NOT NULL DEFAULT 'MONTHLY',
  -- Which customer list this campaign draws from — a "both"-type org can run
  -- separate campaigns against its trade customers and its service customers.
  audience      ENUM('TRADE','SERVICE') NOT NULL DEFAULT 'TRADE',
  status        ENUM('ACTIVE','PAUSED') NOT NULL DEFAULT 'ACTIVE',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_outreach_campaigns_org (org_id)
) ENGINE=InnoDB;

-- One row per WhatsApp click, so the Run screen can compute "due again" per
-- customer per campaign. customer_id points at customers.id (audience=TRADE)
-- or service_customers.id (audience=SERVICE) — no FK, same polymorphic-reference
-- pattern as activity_log.entity_id, since it targets two different tables.
CREATE TABLE outreach_sends (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  org_id      INT NOT NULL,
  campaign_id INT NOT NULL,
  customer_id INT NOT NULL,
  sent_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  FOREIGN KEY (campaign_id) REFERENCES outreach_campaigns(id) ON DELETE CASCADE,
  INDEX idx_outreach_sends_campaign_customer (campaign_id, customer_id),
  INDEX idx_outreach_sends_org (org_id)
) ENGINE=InnoDB;

-- ── Brokers (TradeERP) & Specialized Staff (ServiceERP) commission tracking ─────
-- Both mirror service_staff's own commission_payable pattern: a running total
-- accrued when a sale/invoice records them, reduced by a manual "Pay" action
-- (not tied to any specific transaction) when the owner actually settles up.
-- The commission itself is a payout the business owes out of its own margin —
-- it is NEVER subtracted from what the customer is billed (that's what the
-- separate Discount field is for).
CREATE TABLE brokers (
  id                          INT AUTO_INCREMENT PRIMARY KEY,
  org_id                      INT NOT NULL,
  name                        VARCHAR(150) NOT NULL,
  mobile                      VARCHAR(20) NULL,
  default_commission_percent DECIMAL(5,2) NOT NULL DEFAULT 5,
  commission_payable          DECIMAL(12,2) NOT NULL DEFAULT 0,
  notes                       TEXT NULL,
  created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_brokers_org (org_id)
) ENGINE=InnoDB;

CREATE TABLE specialized_staff (
  id                          INT AUTO_INCREMENT PRIMARY KEY,
  org_id                      INT NOT NULL,
  name                        VARCHAR(150) NOT NULL,
  mobile                      VARCHAR(20) NULL,
  default_commission_percent DECIMAL(5,2) NOT NULL DEFAULT 10,
  commission_payable          DECIMAL(12,2) NOT NULL DEFAULT 0,
  notes                       TEXT NULL,
  created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE,
  INDEX idx_spstaff_org (org_id)
) ENGINE=InnoDB;

ALTER TABLE sales
  ADD COLUMN broker_id INT NULL AFTER customer_gstin,
  ADD COLUMN broker_commission_percent DECIMAL(5,2) NULL AFTER broker_id,
  ADD COLUMN broker_commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER broker_commission_percent,
  ADD FOREIGN KEY (broker_id) REFERENCES brokers(id) ON DELETE SET NULL;

ALTER TABLE service_invoices
  ADD COLUMN specialized_staff_id INT NULL AFTER mobile,
  ADD COLUMN staff_commission_percent DECIMAL(5,2) NULL AFTER specialized_staff_id,
  ADD COLUMN staff_commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER staff_commission_percent,
  ADD FOREIGN KEY (specialized_staff_id) REFERENCES specialized_staff(id) ON DELETE SET NULL;
