-- Phase 1: Payment Processing System Migration
-- This migration adds unified payment processing, receipts, and daily reconciliation

-- 1. Unified Payments Table
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT UNIQUE,
  payment_method ENUM('cash','mpesa','card') NOT NULL,
  payment_status ENUM('unpaid','pending','paid','failed','refunded') DEFAULT 'unpaid',
  amount INT NOT NULL,
  amount_paid INT DEFAULT 0,
  discount_amount INT DEFAULT 0,
  tax_amount INT DEFAULT 0,
  reference_id VARCHAR(100),
  external_transaction_id VARCHAR(100),
  paid_at TIMESTAMP NULL,
  refunded_at TIMESTAMP NULL,
  refund_reason TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
  INDEX idx_status (payment_status),
  INDEX idx_method (payment_method),
  INDEX idx_created (created_at)
);

-- 2. Receipts Table
CREATE TABLE IF NOT EXISTS receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  receipt_number VARCHAR(50) UNIQUE NOT NULL,
  payment_id INT NOT NULL,
  receipt_type ENUM('original','copy','digital') DEFAULT 'original',
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  printed_at TIMESTAMP NULL,
  email_sent_at TIMESTAMP NULL,
  sms_sent_at TIMESTAMP NULL,
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
  INDEX idx_number (receipt_number),
  INDEX idx_payment (payment_id)
);

-- 3. Payment Discounts Table
CREATE TABLE IF NOT EXISTS payment_discounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_id INT NOT NULL,
  discount_type ENUM('percentage','fixed','loyalty','promo') NOT NULL,
  discount_code VARCHAR(50),
  discount_amount INT NOT NULL,
  reason TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
  INDEX idx_payment (payment_id)
);

-- 4. Payment Status Log (Audit Trail)
CREATE TABLE IF NOT EXISTS payment_status_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_id INT NOT NULL,
  old_status VARCHAR(50),
  new_status VARCHAR(50) NOT NULL,
  reason TEXT,
  changed_by INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES users(id),
  INDEX idx_payment (payment_id),
  INDEX idx_created (created_at)
);

-- 5. Card Payments Table (Stripe/Pesapal)
CREATE TABLE IF NOT EXISTS card_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_id INT NOT NULL UNIQUE,
  card_last_four VARCHAR(4),
  card_brand VARCHAR(50),
  processor_transaction_id VARCHAR(100),
  processor_response JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
  INDEX idx_processor (processor_transaction_id)
);

-- 6. Daily Cash Reconciliation
CREATE TABLE IF NOT EXISTS daily_cash_reconciliation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reconciliation_date DATE NOT NULL UNIQUE,
  opening_balance INT DEFAULT 0,
  cash_received INT DEFAULT 0,
  cash_withdrawn INT DEFAULT 0,
  closing_balance INT DEFAULT 0,
  discrepancy INT DEFAULT 0,
  reconciled_by INT,
  reconciled_at TIMESTAMP NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reconciled_by) REFERENCES users(id),
  INDEX idx_date (reconciliation_date)
);

-- 7. Daily Revenue Summary (Cache Table)
CREATE TABLE IF NOT EXISTS daily_revenue_summary (
  id INT AUTO_INCREMENT PRIMARY KEY,
  summary_date DATE NOT NULL UNIQUE,
  total_revenue INT DEFAULT 0,
  cash_revenue INT DEFAULT 0,
  mpesa_revenue INT DEFAULT 0,
  card_revenue INT DEFAULT 0,
  completed_appointments INT DEFAULT 0,
  cancelled_appointments INT DEFAULT 0,
  new_customers INT DEFAULT 0,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_date (summary_date)
);

-- Alter appointments table to reference payments (if not already done)
ALTER TABLE appointments 
ADD COLUMN payment_id INT UNIQUE AFTER status,
ADD FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL;
