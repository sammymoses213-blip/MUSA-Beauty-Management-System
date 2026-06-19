# MUSA Beauty Management System - Complete Implementation Plan

**Date:** June 16, 2026  
**Version:** 2.0 (Comprehensive)  
**Status:** Planning & Design Phase

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Requirements Analysis](#system-requirements-analysis)
3. [Database Design](#database-design)
4. [User Interfaces & Structure](#user-interfaces--structure)
5. [Business Logic & Features](#business-logic--features)
6. [Code Organization & Architecture](#code-organization--architecture)
7. [Implementation Roadmap](#implementation-roadmap)
8. [Integration Points](#integration-points)

---

## Executive Summary

The MUSA Beauty Management System is a comprehensive salon management platform supporting:
- **Multi-role access** (Client, Stylist, Admin)
- **Full appointment lifecycle** with real-time scheduling
- **Multiple payment methods** (Cash, M-Pesa, Card payments)
- **Inventory management** for products and supplies
- **Advanced reporting** with PDF/Excel export
- **Revenue tracking** and financial analytics
- **Customer & staff performance analytics**

**Current Implementation Status:**
- ✅ Core user roles & authentication
- ✅ Service management
- ✅ Basic appointment booking
- ✅ MPesa STK push integration
- ⚠️ Partial payment tracking
- ❌ Inventory management (TBD)
- ❌ Advanced reports (TBD)
- ❌ Card payments (TBD)

---

## System Requirements Analysis

### Functional Requirements

#### 1. Core Beauty Suite Functionalities

##### 1.1 Customer Management
- **Features:**
  - Customer registration (auto-assigned 'client' role)
  - Profile management (name, phone, email, preferences)
  - Customer history (all appointments, services used)
  - Loyalty tracking (repeat visit count)
  - Contact preferences (SMS, email)
  - Customer segmentation (new, regular, VIP)

- **User Flows:**
  - Register → Login → View Dashboard → Book Appointment → Receive Reminder → Rate Service
  - Admin: View all customers → Search → View history → Export list

- **Database Tables:**
  - `users` (existing, extend with fields)
  - `customer_preferences` (new)
  - `customer_loyalty` (new)

##### 1.2 Appointment Booking & Scheduling
- **Features:**
  - Real-time availability calendar
  - Service selection with pricing display
  - Stylist assignment based on specialization
  - Time slot management (30-min, 45-min, 1hr, 1.5hr, 2hr, 3hr blocks)
  - Appointment status tracking (booked → completed/cancelled)
  - Rescheduling capability
  - Cancellation with reason tracking
  - Waitlist management

- **Constraints:**
  - Prevent double-booking
  - No past appointments
  - Working hours validation (9 AM - 7 PM)
  - Staff break times

- **Database Tables:**
  - `appointments` (existing, extend)
  - `appointment_notes` (new)
  - `appointment_waitlist` (new)
  - `working_hours` (new)
  - `staff_breaks` (new)

##### 1.3 Staff Management
- **Features:**
  - Stylist profile creation
  - Specialization tags (Hair, Makeup, Nails, Spa, etc.)
  - Availability calendar
  - Performance metrics (avg rating, total appointments)
  - Commission tracking
  - On-leave management
  - Performance bonuses

- **Workflows:**
  - Admin: Create stylist → Assign services → View schedule
  - Stylist: View assigned appointments → Mark complete → View ratings

- **Database Tables:**
  - `stylists` (existing, extend)
  - `stylist_commission` (new)
  - `stylist_leave` (new)
  - `stylist_specializations` (new)

##### 1.4 Beauty Services Management
- **Features:**
  - Service categories (Hair, Makeup, Nails, Spa, Grooming, Packages)
  - Service pricing & duration
  - Service description & images
  - Service availability by stylist
  - Package bundles
  - Seasonal pricing

- **Database Tables:**
  - `services` (existing, extend)
  - `service_pricing_history` (new)
  - `service_packages` (new)
  - `service_package_items` (new)

##### 1.5 Inventory/Product Management
- **Features:**
  - Product catalog (hair dyes, polish, oils, supplies)
  - Stock level tracking with low-stock alerts
  - Supplier management
  - Purchase order management
  - Stock movement history (in/out)
  - Cost tracking and margins
  - Expiration date tracking
  - Automatic reorder points

- **Workflows:**
  - Admin: Add product → Set stock level → Receive alerts → Create purchase order
  - Track: Stock usage per appointment → Low stock alert → Reorder

- **Database Tables:**
  - `products` (new)
  - `product_categories` (new)
  - `stock_levels` (new)
  - `stock_movements` (new)
  - `suppliers` (new)
  - `purchase_orders` (new)
  - `purchase_order_items` (new)

##### 1.6 Service History Tracking
- **Features:**
  - Complete history of all services received by customer
  - Service details (date, stylist, duration, cost)
  - Product/supplies used per appointment
  - Before/after photos optional
  - Customer notes/preferences
  - Stylist notes for next visit
  - Service frequency analysis

- **Database Tables:**
  - `appointment_services` (new - for multi-service appointments)
  - `appointment_products_used` (new)
  - `service_feedback` (new)

---

#### 2. Payment Methods & Processing

##### 2.1 Cash Payments
- **Features:**
  - Manual receipt generation
  - Cash reconciliation
  - Daily cash drawer balancing
  - Receipt numbering (auto-increment)

- **Workflows:**
  - Customer books → Completes service → Pays cash → Generate receipt → Update payment status

- **Database Tables:**
  - `payments` (new - consolidated payment table)
  - `cash_transactions` (new)
  - `daily_cash_reconciliation` (new)

##### 2.2 Mobile Money Payments (M-Pesa)
- **Features:**
  - ✅ STK push to customer phone
  - Payment confirmation callback
  - Transaction reference tracking
  - Failed payment retry
  - Partial payment support
  - Transaction receipts

- **Workflows:**
  - Customer books → Selects MPesa → Enter phone → STK prompt → Payment confirmed → Auto-email receipt

- **Database Tables:**
  - `mpesa_payments` (existing, reference only)
  - Updates to `appointments` payment fields

##### 2.3 Card Payments
- **Features:**
  - Credit/debit card processing (Stripe/Pesapal integration)
  - PCI-DSS compliance (tokenized payments)
  - Transaction fees tracking
  - Failed payment handling with retry
  - Refund processing
  - 3D Secure support

- **Workflows:**
  - Customer books → Selects Card → Redirected to Stripe/Pesapal → Complete 3DS → Return with confirmation

- **Database Tables:**
  - `card_payments` (new)
  - `payment_tokens` (new)
  - `card_transaction_fees` (new)

##### 2.4 Payment Recording & Receipt Generation
- **Features:**
  - Unified payment recording system
  - Receipt templates (SMS, Email, Print)
  - Receipt number sequencing
  - Payment method breakdown
  - Tax calculation
  - Discount/promo tracking

- **Database Tables:**
  - `payments` (consolidated)
  - `receipts` (new)
  - `payment_discounts` (new)

##### 2.5 Payment Status Tracking
- **Features:**
  - Real-time payment status (pending → completed → failed/refunded)
  - Failed payment alerts
  - Refund tracking
  - Chargeback handling
  - Payment timeout notifications

- **Workflows:**
  - Payment initiated → Status pending → Callback received → Status updated → Customer notified

- **Database Tables:**
  - Enhanced `appointments` table with payment_status
  - `payment_status_log` (new - audit trail)

##### 2.6 Daily Revenue Calculations
- **Features:**
  - Daily total by payment method
  - Total by stylist
  - Total by service category
  - Running daily total
  - Previous day comparison
  - End-of-day reconciliation report

- **Calculations:**
  - Formula: SUM(appointment amount_paid WHERE date = TODAY)
  - By method: SUM WHERE payment_method = 'cash'|'mpesa'|'card'
  - By stylist: SUM WHERE stylist_id = X

- **Database Tables:**
  - `daily_revenue_summary` (new - cache table)
  - Queries from `appointments`, `payments`

---

#### 3. Report Generation

##### 3.1 Daily Sales Reports
- **Metrics:**
  - Total revenue (cash, MPesa, card breakdown)
  - Appointments count (completed vs cancelled)
  - Top 5 services by revenue
  - Top 5 stylists by appointments
  - Customer acquisition (new vs repeat)
  - Average transaction value
  - Payment method distribution (%)

- **Report Structure:**
  ```
  MUSA Beauty - Daily Sales Report
  Date: June 16, 2026
  
  Revenue Summary:
  ├─ Cash: KES 5,500 (35%)
  ├─ MPesa: KES 8,200 (52%)
  ├─ Card: KES 1,800 (13%)
  └─ TOTAL: KES 15,500
  
  Appointments: 12 completed, 1 cancelled
  Avg Transaction: KES 1,292
  
  [Detailed tables with services, stylists, customers]
  ```

##### 3.2 Monthly Revenue Reports
- **Metrics:**
  - Monthly total revenue (vs previous months)
  - Weekly breakdown
  - Growth percentage
  - Best performing week
  - Best performing day of week
  - Revenue per stylist
  - Revenue per service category
  - Customer retention rate
  - Profit analysis (revenue - inventory costs)

- **Report Structure:**
  - Header: Month overview, comparison to previous 3 months
  - Line chart: Daily revenue trend
  - Pie chart: Revenue by payment method
  - Bar chart: Revenue by service category
  - Detailed tables: All metrics

##### 3.3 Appointment Reports
- **Metrics:**
  - Total appointments (completed, cancelled, no-show)
  - Appointment completion rate (%)
  - Cancellation rate
  - Average appointment duration
  - No-show rate
  - Peak appointment times
  - Stylist utilization rate
  - Service demand ranking

- **Filters:**
  - Date range
  - Stylist
  - Service
  - Status
  - Customer segment

##### 3.4 Customer Reports
- **Metrics:**
  - Total active customers
  - New customers (this month)
  - Repeat customer percentage
  - Customer lifetime value (CLV)
  - Average customer spend
  - Customer acquisition cost (CAC)
  - Churn rate
  - Top 10 customers by revenue
  - Customer satisfaction (avg rating)

- **Segmentation:**
  - New (0-1 month)
  - Regular (1-6 months)
  - Loyal (6+ months)
  - Inactive (no appointment last 3 months)

##### 3.5 Staff Performance Reports
- **Metrics per Stylist:**
  - Total appointments completed
  - Average rating
  - Revenue generated
  - Commission earned
  - Customer retention (repeat clients)
  - No-show count
  - Average service duration vs standard
  - Customer satisfaction score
  - Best-rated services

- **Comparisons:**
  - Stylist ranking by revenue
  - Stylist ranking by rating
  - Month-on-month performance change
  - Bonus eligibility

##### 3.6 Inventory Stock Reports
- **Metrics:**
  - Low stock alerts (below reorder point)
  - Stock value (current inventory value)
  - Stock aging (oldest items)
  - Usage rate by product
  - Wastage/expiry tracking
  - Supplier performance (delivery time, quality)
  - Inventory turnover ratio
  - Reorder requirements

- **Alerts:**
  - Items below minimum stock
  - Items near expiry (within 30 days)
  - Items not used in 90 days

##### 3.7 Export Reports to PDF & Excel
- **Formats:**
  - PDF: Professional formatted reports with logos, charts, tables
  - Excel: Pivot-friendly data with multiple sheets
  - CSV: Raw data export

- **Libraries:**
  - PDF: TCPDF or mPDF
  - Excel: PhpSpreadsheet or SimpleXLSX
  - CSV: Native PHP fputcsv()

- **Features:**
  - Scheduled report generation
  - Email delivery
  - Report history/archive
  - Custom report templates

---

## Database Design

### Current Tables (Existing)

```sql
-- Authentication & Users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password VARCHAR(255) NOT NULL,
  role ENUM('client','stylist','admin') NOT NULL DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services
CREATE TABLE services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(100) NOT NULL,
  price INT NOT NULL,
  duration VARCHAR(100) NOT NULL,
  description TEXT,
  image VARCHAR(255),
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stylists
CREATE TABLE stylists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  specialization VARCHAR(255),
  rating DECIMAL(3,2) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Appointments (Enhanced)
CREATE TABLE appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  stylist_id INT NOT NULL,
  service_id INT NOT NULL,
  appointment_date DATETIME NOT NULL,
  status ENUM('booked','in-progress','completed','cancelled','no-show') DEFAULT 'booked',
  payment_method ENUM('cash','mpesa','card') DEFAULT 'cash',
  payment_status ENUM('unpaid','pending','paid','failed','refunded') DEFAULT 'unpaid',
  amount_paid INT NOT NULL DEFAULT 0,
  mpesa_checkout_request_id VARCHAR(255),
  mpesa_receipt_number VARCHAR(255),
  notes TEXT,
  reminder_sent TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id),
  FOREIGN KEY (stylist_id) REFERENCES users(id),
  FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Payments
CREATE TABLE mpesa_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  merchant_request_id VARCHAR(255),
  checkout_request_id VARCHAR(255),
  result_code VARCHAR(50),
  result_desc TEXT,
  amount VARCHAR(50),
  mpesa_receipt_number VARCHAR(100),
  phone_number VARCHAR(30),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reviews
CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  stylist_id INT NOT NULL,
  rating TINYINT CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id),
  FOREIGN KEY (stylist_id) REFERENCES users(id)
);
```

### New Tables (Required for Complete Implementation)

#### 1. Customer & Loyalty
```sql
CREATE TABLE customer_preferences (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL UNIQUE,
  preferred_stylist_id INT,
  communication_sms TINYINT(1) DEFAULT 1,
  communication_email TINYINT(1) DEFAULT 1,
  favorite_services JSON,
  allergies_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id),
  FOREIGN KEY (preferred_stylist_id) REFERENCES users(id)
);

CREATE TABLE customer_loyalty (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL UNIQUE,
  visit_count INT DEFAULT 0,
  total_spent INT DEFAULT 0,
  loyalty_points INT DEFAULT 0,
  tier ENUM('bronze','silver','gold','platinum') DEFAULT 'bronze',
  last_visit_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id)
);
```

#### 2. Appointment Management
```sql
CREATE TABLE appointment_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,
  note_type ENUM('stylist','admin','system') DEFAULT 'stylist',
  content TEXT NOT NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE appointment_waitlist (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  service_id INT NOT NULL,
  stylist_id INT,
  preferred_date_from DATE,
  preferred_date_to DATE,
  status ENUM('active','cancelled','fulfilled') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id),
  FOREIGN KEY (service_id) REFERENCES services(id)
);

CREATE TABLE working_hours (
  id INT AUTO_INCREMENT PRIMARY KEY,
  day_of_week INT (0-6, 0=Sunday),
  open_time TIME NOT NULL,
  close_time TIME NOT NULL,
  is_open TINYINT(1) DEFAULT 1
);

CREATE TABLE staff_breaks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stylist_id INT NOT NULL,
  break_date DATE NOT NULL,
  break_start TIME,
  break_end TIME,
  break_type ENUM('lunch','break','leave') DEFAULT 'break',
  reason TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (stylist_id) REFERENCES stylists(id)
);
```

#### 3. Staff Management
```sql
CREATE TABLE stylist_specializations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stylist_id INT NOT NULL,
  specialization VARCHAR(100) NOT NULL,
  proficiency_level ENUM('beginner','intermediate','expert') DEFAULT 'intermediate',
  FOREIGN KEY (stylist_id) REFERENCES stylists(id),
  UNIQUE KEY unique_specialization (stylist_id, specialization)
);

CREATE TABLE stylist_commission (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stylist_id INT NOT NULL,
  commission_type ENUM('percentage','fixed') DEFAULT 'percentage',
  commission_value DECIMAL(5,2) NOT NULL,
  min_revenue_threshold INT DEFAULT 0,
  effective_from DATE,
  effective_to DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (stylist_id) REFERENCES stylists(id)
);

CREATE TABLE stylist_leave (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stylist_id INT NOT NULL,
  leave_from DATE NOT NULL,
  leave_to DATE NOT NULL,
  reason TEXT,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (stylist_id) REFERENCES stylists(id)
);
```

#### 4. Services & Products
```sql
CREATE TABLE service_packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  total_price INT NOT NULL,
  discount_percentage DECIMAL(5,2) DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE service_package_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  package_id INT NOT NULL,
  service_id INT NOT NULL,
  quantity INT DEFAULT 1,
  FOREIGN KEY (package_id) REFERENCES service_packages(id),
  FOREIGN KEY (service_id) REFERENCES services(id)
);

CREATE TABLE service_pricing_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_id INT NOT NULL,
  old_price INT,
  new_price INT,
  effective_from DATE,
  effective_to DATE,
  reason VARCHAR(255),
  FOREIGN KEY (service_id) REFERENCES services(id)
);
```

#### 5. Inventory Management
```sql
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category_id INT NOT NULL,
  sku VARCHAR(50) UNIQUE NOT NULL,
  description TEXT,
  unit_price INT NOT NULL,
  reorder_point INT NOT NULL,
  supplier_id INT,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES product_categories(id),
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

CREATE TABLE product_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description TEXT
);

CREATE TABLE stock_levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL UNIQUE,
  quantity_on_hand INT NOT NULL DEFAULT 0,
  quantity_reserved INT DEFAULT 0,
  last_counted_at TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  movement_type ENUM('in','out','adjustment','expiry') NOT NULL,
  quantity INT NOT NULL,
  reference_type VARCHAR(50),
  reference_id INT,
  notes TEXT,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  contact_person VARCHAR(150),
  email VARCHAR(100),
  phone VARCHAR(20),
  address TEXT,
  city VARCHAR(100),
  payment_terms VARCHAR(100),
  rating DECIMAL(3,2),
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE purchase_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  po_number VARCHAR(50) UNIQUE NOT NULL AUTO_INCREMENT,
  supplier_id INT NOT NULL,
  order_date DATE NOT NULL,
  expected_delivery_date DATE,
  actual_delivery_date DATE,
  total_amount INT NOT NULL,
  status ENUM('draft','submitted','confirmed','delivered','cancelled') DEFAULT 'draft',
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE purchase_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  unit_price INT NOT NULL,
  line_total INT NOT NULL,
  FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);
```

#### 6. Service History & Feedback
```sql
CREATE TABLE appointment_services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,
  service_id INT NOT NULL,
  quantity INT DEFAULT 1,
  price_at_service INT NOT NULL,
  duration_minutes INT,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id),
  FOREIGN KEY (service_id) REFERENCES services(id)
);

CREATE TABLE appointment_products_used (
  id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity_used DECIMAL(10,2),
  unit_used VARCHAR(50),
  FOREIGN KEY (appointment_id) REFERENCES appointments(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE service_feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT NOT NULL,
  cleanliness_rating INT,
  staff_friendliness_rating INT,
  service_quality_rating INT,
  overall_rating INT,
  improvement_suggestions TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id)
);
```

#### 7. Payment & Financial
```sql
CREATE TABLE payments (
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
  paid_at TIMESTAMP,
  refunded_at TIMESTAMP,
  refund_reason TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (appointment_id) REFERENCES appointments(id)
);

CREATE TABLE receipts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  receipt_number VARCHAR(50) UNIQUE NOT NULL,
  payment_id INT NOT NULL,
  receipt_type ENUM('original','copy','digital') DEFAULT 'original',
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  printed_at TIMESTAMP,
  email_sent_at TIMESTAMP,
  sms_sent_at TIMESTAMP,
  FOREIGN KEY (payment_id) REFERENCES payments(id)
);

CREATE TABLE payment_discounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_id INT NOT NULL,
  discount_type ENUM('percentage','fixed','loyalty','promo') NOT NULL,
  discount_code VARCHAR(50),
  discount_amount INT NOT NULL,
  reason TEXT,
  FOREIGN KEY (payment_id) REFERENCES payments(id)
);

CREATE TABLE payment_status_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_id INT NOT NULL,
  old_status VARCHAR(50),
  new_status VARCHAR(50) NOT NULL,
  reason TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (payment_id) REFERENCES payments(id)
);

CREATE TABLE card_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  payment_id INT NOT NULL UNIQUE,
  card_last_four VARCHAR(4),
  card_brand VARCHAR(50),
  processor_transaction_id VARCHAR(100),
  processor_response JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (payment_id) REFERENCES payments(id)
);

CREATE TABLE daily_cash_reconciliation (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reconciliation_date DATE NOT NULL UNIQUE,
  opening_balance INT,
  cash_received INT,
  cash_withdrawn INT,
  closing_balance INT,
  discrepancy INT,
  reconciled_by INT,
  reconciled_at TIMESTAMP,
  notes TEXT,
  FOREIGN KEY (reconciled_by) REFERENCES users(id)
);
```

#### 8. Reports & Analytics
```sql
CREATE TABLE daily_revenue_summary (
  id INT AUTO_INCREMENT PRIMARY KEY,
  summary_date DATE NOT NULL UNIQUE,
  total_revenue INT,
  cash_revenue INT,
  mpesa_revenue INT,
  card_revenue INT,
  completed_appointments INT,
  cancelled_appointments INT,
  new_customers INT,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE report_schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_type VARCHAR(100) NOT NULL,
  frequency ENUM('daily','weekly','monthly','custom') DEFAULT 'daily',
  delivery_method ENUM('email','download','both') DEFAULT 'email',
  recipient_email VARCHAR(100),
  is_active TINYINT(1) DEFAULT 1,
  last_generated_at TIMESTAMP,
  next_generation_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE report_archives (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_type VARCHAR(100) NOT NULL,
  report_date DATE NOT NULL,
  file_path VARCHAR(255),
  file_format ENUM('pdf','excel','csv') NOT NULL,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## User Interfaces & Structure

### Directory Structure

```
/workspaces/MUSA-Beauty-Management-System/
├── admin/
│   ├── dashboard.php                    # Admin dashboard (existing)
│   ├── manage_services.php              # Service CRUD (existing)
│   ├── manage_users.php                 # User management (existing)
│   ├── manage_inventory.php             # NEW: Inventory management
│   ├── manage_suppliers.php             # NEW: Supplier management
│   ├── purchase_orders.php              # NEW: PO management
│   ├── staff_management.php             # NEW: Stylist management
│   ├── staff_performance.php            # NEW: Performance analytics
│   ├── financial_dashboard.php          # NEW: Revenue & profit dashboard
│   ├── revenue_reconciliation.php       # NEW: Daily/monthly reconciliation
│   ├── reports/
│   │   ├── daily_sales.php              # NEW: Daily sales report
│   │   ├── monthly_revenue.php          # NEW: Monthly revenue
│   │   ├── appointments.php             # NEW: Appointment reports
│   │   ├── customers.php                # NEW: Customer reports
│   │   ├── staff_performance.php        # NEW: Staff performance
│   │   ├── inventory_stock.php          # NEW: Inventory reports
│   │   ├── export.php                   # NEW: Export to PDF/Excel
│   │   └── report_scheduler.php         # NEW: Schedule reports
│   └── settings/
│       ├── payment_config.php           # NEW: Payment methods config
│       ├── sms_config.php               # SMS/Notification config
│       └── business_settings.php        # Tax rates, discounts, etc.
│
├── client/
│   ├── dashboard.php                    # Client dashboard (existing)
│   ├── book_appointment.php             # Booking (enhanced)
│   ├── my_appointments.php              # Appointment history
│   ├── mpesa_status.php                 # MPesa payment status
│   ├── mpesa_callback.php               # MPesa callback (existing)
│   ├── favorites.php                    # NEW: Favorite stylists/services
│   ├── loyalty_points.php               # NEW: Loyalty program
│   ├── my_profile.php                   # NEW: Profile & preferences
│   ├── service_history.php              # NEW: Detailed service history
│   └── reviews.php                      # Reviews (existing)
│
├── stylist/
│   ├── dashboard.php                    # Stylist dashboard (existing)
│   ├── appointments.php                 # Assigned appointments (existing)
│   ├── schedule.php                     # Schedule management
│   ├── commission_tracker.php           # NEW: Commission tracking
│   ├── performance_metrics.php          # NEW: Personal metrics
│   └── leave_requests.php               # NEW: Leave management
│
├── includes/
│   ├── auth.php                         # Authentication (existing)
│   ├── header.php                       # Header template (existing)
│   ├── footer.php                       # Footer template (existing)
│   ├── sms.php                          # SMS sending (existing)
│   ├── mpesa.php                        # MPesa integration (existing)
│   ├── payment_processor.php            # NEW: Payment processing
│   ├── card_payment.php                 # NEW: Card payment (Stripe/Pesapal)
│   ├── inventory_manager.php            # NEW: Inventory operations
│   ├── report_generator.php             # NEW: Report generation
│   ├── notification_manager.php         # NEW: Multi-channel notifications
│   ├── appointment_scheduler.php        # NEW: Appointment logic
│   ├── financial_manager.php            # NEW: Revenue calculations
│   └── email_templates/                 # NEW: Email templates
│       ├── appointment_confirmed.html
│       ├── payment_receipt.html
│       ├── report_scheduled.html
│       └── low_stock_alert.html
│
├── config/
│   ├── db.php                           # Database config (existing)
│   ├── load_env.php                     # Environment loader (existing)
│   ├── payment_config.php               # NEW: Payment methods config
│   ├── email_config.php                 # NEW: Email settings
│   └── business_config.php              # NEW: Business settings
│
├── api/                                 # NEW: REST API endpoints
│   ├── appointments.php
│   ├── services.php
│   ├── payments.php
│   ├── inventory.php
│   └── reports.php
│
├── assets/
│   ├── css/
│   │   ├── style.css                    # Main styles (existing)
│   │   ├── admin-dashboard.css          # NEW: Admin dashboard
│   │   ├── reports.css                  # NEW: Reports styling
│   │   └── responsive.css               # Responsive design
│   ├── js/
│   │   ├── main.js                      # Main scripts (existing)
│   │   ├── charts.js                    # NEW: Chart.js for reports
│   │   ├── payments.js                  # NEW: Payment handling
│   │   ├── calendar.js                  # NEW: Calendar widget
│   │   └── forms.js                     # Form validation
│   └── images/
│       └── products/                    # NEW: Product images
│
├── logs/                                # Log files
│   ├── errors.log
│   ├── payments.log                     # NEW: Payment logs
│   └── inventory.log                    # NEW: Inventory logs
│
├── reports/                             # NEW: Generated reports directory
│   ├── daily/
│   ├── monthly/
│   └── annual/
│
├── cron/                                # NEW: Scheduled tasks
│   ├── daily_revenue_summary.php        # Generate daily summary
│   ├── send_reminders.php               # Send appointment reminders
│   ├── check_low_stock.php              # Low stock alerts
│   └── generate_monthly_reports.php     # Monthly report generation
│
├── db_schema.sql                        # Database schema (enhanced)
├── .env                                 # Environment variables
├── .env.example                         # Example env file
├── package.json                         # Node dependencies
├── README.md                            # Documentation
└── health-check.php                     # System health check
```

### Page Wireframes & Components

#### Admin Dashboard (Enhanced)
```
┌─ MUSA Beauty Admin Dashboard ─────────────────────────────────────────┐
│                                                                         │
│  ┌─ Navigation ────────────────────────────────────────────────────┐   │
│  │ Dashboard | Services | Staff | Inventory | Payments | Reports  │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─ Quick Stats Row ───────────────────────────────────────────────┐   │
│  │ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐              │   │
│  │ │ Today Revenue│ │Appointments │ │ New Customers│              │   │
│  │ │   KES 15,500 │ │      12      │ │      5       │              │   │
│  │ └──────────────┘ └──────────────┘ └──────────────┘              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─ Charts Row ────────────────────────────────────────────────────┐   │
│  │ ┌──────────────────────┐  ┌──────────────────────┐              │   │
│  │ │ Revenue Trend (7d)   │  │Payment Method Split  │              │   │
│  │ │ [Line Chart]         │  │ [Pie Chart]          │              │   │
│  │ └──────────────────────┘  └──────────────────────┘              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─ Recent Transactions ──────────────────────────────────────────┐   │
│  │ [Table with Last 5 Appointments]                              │   │
│  └─────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
```

#### Inventory Management Page
```
┌─ Inventory Management ──────────────────────────────────────────────┐
│                                                                      │
│ [Add Product] [Import CSV] [Settings]                               │
│                                                                      │
│ ┌─ Filters & Search ──────────────────────────────────────────────┐│
│ │ Category: [All ▼]  Stock: [All ▼]  Search: [_____________]     ││
│ │ [Low Stock Only] [Expiring Soon] [Apply Filters]               ││
│ └─────────────────────────────────────────────────────────────────┘│
│                                                                      │
│ ┌─ Product List ──────────────────────────────────────────────────┐│
│ │ Product Name    | SKU      | Stock | Reorder | Unit Price | Act│
│ │─────────────────┼──────────┼───────┼─────────┼────────────┼────│
│ │ Hair Dye Black  | HD-BLK01 │ 12    │ 20      │ KES 500    │[E] │
│ │ Nail Polish Red │ NP-RED02 │ 5 ⚠   │ 15      │ KES 200    │[E] │
│ │ Shampoo 1L      │ SHP-1L01 │ 3 ⚠⚠  │ 10      │ KES 800    │[E] │
│ └─────────────────────────────────────────────────────────────────┘│
│                                                                      │
│ Pagination: 1 2 3 ... [Next]                                         │
└──────────────────────────────────────────────────────────────────────┘
```

#### Report Dashboard
```
┌─ Reports & Analytics ───────────────────────────────────────────────┐
│                                                                      │
│  ┌─ Report Type Selection ─────────────────────────────────────┐   │
│  │ [Daily Sales] [Monthly Revenue] [Appointments] [Customers]  │   │
│  │ [Staff Performance] [Inventory] [Financial]                │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─ Date Range & Options ─────────────────────────────────────┐   │
│  │ From: [Date ▼] To: [Date ▼]                                │   │
│  │ [PDF] [Excel] [CSV] [Print] [Email] [Schedule]             │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  ┌─ Report Preview ─────────────────────────────────────────────┐  │
│  │ Report Title: Daily Sales - June 16, 2026                   │  │
│  │                                                              │  │
│  │ Summary:                                                     │  │
│  │ - Total Revenue: KES 15,500                                 │  │
│  │ - Appointments: 12 completed, 1 cancelled                   │  │
│  │ - Top Service: Braiding (3 bookings, KES 7,500)             │  │
│  │ - Top Stylist: Mia (8 appointments, KES 10,000)             │  │
│  │                                                              │  │
│  │ [Detailed Tables & Charts Below]                            │  │
│  └──────────────────────────────────────────────────────────────┘ │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

#### Payment Processing Flow
```
Client Books Appointment
         ↓
Select Payment Method
         ├─ Cash
         ├─ M-Pesa
         └─ Card
         ↓
If M-Pesa: Enter Phone → STK Prompt → Confirm
If Card: Redirect to Stripe/Pesapal → 3DS → Return
If Cash: Complete booking, Payment pending
         ↓
Payment Status Callback/Confirmation
         ↓
Update Appointment Status
         ↓
Generate Receipt
         ↓
Send Notification (SMS/Email)
         ↓
Update Daily Revenue Summary
```

---

## Business Logic & Features

### 1. Appointment Lifecycle

```
State Machine:
  BOOKED → IN_PROGRESS → COMPLETED
    ↓
  CANCELLED (with refund logic)
  
  NO_SHOW (if client doesn't arrive within grace period)

Events:
1. Booking Created
   - Check availability
   - Reserve time slot
   - Send confirmation SMS/Email
   - Create calendar reminder

2. Appointment Started (Auto or Manual)
   - Update status to IN_PROGRESS
   - Display stylist notes
   - Log product usage

3. Appointment Completed
   - Update status to COMPLETED
   - Process payment
   - Generate receipt
   - Send review request
   - Award loyalty points
   - Update stylist metrics

4. Appointment Cancelled
   - Refund payment (if paid)
   - Free up time slot
   - Send cancellation notification
   - Log cancellation reason
   - Offer rescheduling

5. Appointment No-Show
   - Mark as NO_SHOW
   - Retain payment (if paid)
   - Send feedback SMS
```

### 2. Payment Processing Logic

```
Payment State Machine:
  UNPAID → PENDING → PAID
    ↓         ↓
  FAILED → RETRY → PAID
    ↓
  REFUNDED (after completion)

Processing by Method:
  
  CASH:
  1. Appointment completes
  2. Present payment amount
  3. Accept cash
  4. Generate receipt
  5. Mark as PAID
  6. Update daily cash balance

  M-PESA:
  1. Initiate STK push via Daraja API
  2. Store checkout_request_id
  3. Set status to PENDING
  4. Wait for callback
  5. On success callback: Mark as PAID
  6. On failure: Mark as FAILED, offer retry
  7. Generate receipt

  CARD:
  1. Redirect to Stripe/Pesapal
  2. Store transaction token
  3. Set status to PENDING
  4. Handle 3DS if required
  5. Callback updates status
  6. Generate receipt

Refund Logic:
  1. Refund triggered (e.g., appointment cancellation)
  2. Check payment status (only refund if PAID)
  3. Initiate reversal via payment processor
  4. Update payment status to REFUNDED
  5. Send refund notification
  6. Record in audit log

Daily Reconciliation:
  1. Sum all payments for the day by method
  2. Compare to actual cash received (for cash)
  3. Flag discrepancies
  4. Generate reconciliation report
  5. Mark day as reconciled
```

### 3. Inventory Management Logic

```
Stock Tracking:
  
  On Product Addition:
  1. Create product record
  2. Initialize stock_levels with quantity_on_hand
  3. Calculate reorder point: quantity / usage_rate
  4. Log initial stock movement

  On Appointment Completion:
  1. Get products used from appointment
  2. For each product:
     - Decrement quantity_on_hand
     - Create stock_movement record (type=out)
     - Check if below reorder point
     - If below: Trigger low-stock alert

  On Purchase Order Received:
  1. Create stock_movement (type=in)
  2. Update quantity_on_hand
  3. Update purchase_order status to DELIVERED
  4. Clear low-stock alerts for items
  5. Log received quantity vs ordered

  Expiry Tracking:
  1. Check products daily for expiry
  2. If expiry within 30 days: Alert admin
  3. If expired: Create adjustment movement
  4. Deduct from available stock

Low Stock Alerts:
  1. Check if quantity_on_hand < reorder_point
  2. If yes: Create purchase order draft
  3. Notify admin via SMS/Email
  4. Suggest reorder quantity
  5. Track alert history

Stock Adjustment:
  1. Admin initiates adjustment
  2. Enter reason (damage, loss, count correction)
  3. Enter adjustment amount (+/-)
  4. Create stock_movement (type=adjustment)
  5. Update quantity_on_hand
  6. Notify affected parties
```

### 4. Revenue Calculation Logic

```
Daily Revenue:
  Total = SUM(amount_paid WHERE date = TODAY AND status = COMPLETED)
  
  By Method:
  Cash = SUM(amount_paid WHERE date = TODAY AND payment_method = 'cash')
  MPesa = SUM(amount_paid WHERE date = TODAY AND payment_method = 'mpesa')
  Card = SUM(amount_paid WHERE date = TODAY AND payment_method = 'card')

  By Stylist:
  Stylist Revenue = SUM(amount_paid WHERE stylist_id = X AND date = TODAY)
  
  By Service:
  Service Revenue = SUM(amount_paid WHERE service_id = X AND date = TODAY)

Monthly Revenue:
  For each day in month: Calculate daily revenue
  Sum all daily revenues
  Calculate weekly averages
  Compare to previous months
  Calculate growth %

Profit Calculation:
  Total Revenue = Daily/Monthly revenue sum
  Total Costs = Inventory costs + Staff costs
  Profit = Total Revenue - Total Costs
  
  Inventory Cost:
  Sum(product cost * quantity_used) for all products used in period

Staff Costs:
  Sum(stylist salaries + commissions) for period

Commission Calculation:
  For each stylist:
  Base Commission = Stylist Revenue * Commission %
  If min_revenue_threshold set:
    If revenue < threshold: Commission = 0
    Else: Commission = Base
  Bonus = If revenue > bonus_threshold: Additional %

Daily Reconciliation:
  1. Sum expected revenue from appointments
  2. Sum actual payments received
  3. Calculate discrepancy
  4. If discrepancy > threshold: Flag for manual review
  5. Generate reconciliation report
  6. Mark day as reconciled with admin sign-off
```

### 5. Report Generation Logic

```
Daily Sales Report:
  1. Query appointments WHERE date = TODAY AND status IN ('completed','cancelled')
  2. Group by payment_method, service, stylist
  3. Calculate totals, averages, percentages
  4. Generate charts (pie, bar, line)
  5. Format with header, summary, tables
  6. Export to PDF/Excel if requested
  7. Archive report

Monthly Revenue Report:
  1. Loop through each day of month
  2. Calculate daily revenue
  3. Aggregate weekly summaries
  4. Compare to previous 3 months
  5. Calculate growth metrics
  6. Generate trend charts
  7. Include profit analysis
  8. Generate with date filters

Staff Performance Report:
  For each stylist:
  1. Count appointments (completed, cancelled, no-show)
  2. Sum revenue generated
  3. Calculate commission
  4. Calculate average rating
  5. Count repeat clients
  6. List top services
  7. Rank stylists by performance metric
  8. Generate comparison charts

Customer Report:
  1. Count total active customers
  2. Identify new customers (this month)
  3. Calculate repeat rate %
  4. Calculate customer lifetime value (CLV)
  5. Segment customers (new, regular, loyal, inactive)
  6. List top 10 customers by revenue
  7. Calculate churn rate
  8. Generate retention charts

Inventory Report:
  1. List low-stock items
  2. Calculate inventory value
  3. Identify slow-moving items (not used 90 days)
  4. List items near expiry
  5. Calculate turnover rate
  6. Identify reorder needs
  7. Compare to previous periods
  8. Generate purchase recommendations

Export Logic:
  PDF:
    1. Generate HTML report
    2. Convert to PDF via TCPDF/mPDF
    3. Add header, footer, page numbers
    4. Embed charts/images
    5. Save to reports/ directory
    6. Return download link

  Excel:
    1. Create workbook with multiple sheets
    2. Sheet 1: Summary/Overview
    3. Sheet 2-N: Detailed data tables
    4. Format cells (currency, dates, colors)
    5. Add charts
    6. Freeze header rows
    7. Set column widths
    8. Save to reports/ directory
    9. Return download link
```

---

## Code Organization & Architecture

### Layer Architecture

```
┌─────────────────────────────────────────────┐
│         Presentation Layer (UI)             │
│  HTML/CSS/JavaScript - Client Facing       │
├─────────────────────────────────────────────┤
│         Routing Layer                       │
│  Route requests to appropriate handler     │
├─────────────────────────────────────────────┤
│         Business Logic Layer                │
│  Appointment Scheduler, Payment Processor,  │
│  Inventory Manager, Report Generator       │
├─────────────────────────────────────────────┤
│         Data Access Layer (DAL)             │
│  PDO queries, data mapping, transactions   │
├─────────────────────────────────────────────┤
│         Database Layer                      │
│  MySQL - Data Storage                      │
└─────────────────────────────────────────────┘
```

### Class Structure (Recommended)

```php
// includes/classes/
├── User.php                  // User authentication, roles
├── Customer.php              // Customer profile & history
├── Stylist.php               // Stylist profile & performance
├── Appointment.php           // Appointment management
├── Service.php               // Service CRUD
├── Payment.php               // Payment processing
├── PaymentProcessor.php      // Factory for payment methods
├── CashPayment.php           // Cash payment handler
├── MpesaPayment.php          // M-Pesa payment handler
├── CardPayment.php           // Card payment handler
├── Inventory.php             // Inventory management
├── Product.php               // Product CRUD
├── StockMovement.php         // Stock tracking
├── Report.php                // Base report class
├── DailySalesReport.php      // Daily sales report
├── MonthlyRevenueReport.php  // Monthly report
├── StaffPerformanceReport.php // Staff report
├── Notification.php          // Multi-channel notifications
├── Email.php                 // Email sending
├── SMS.php                   // SMS sending
└── Database.php              // Database connection wrapper
```

### Helper Functions (includes/)

```php
// includes/helpers.php
- getCurrentUser()
- requireRole($role)
- formatCurrency($amount)
- formatDate($date)
- sendNotification($user, $message, $channels = ['sms', 'email'])
- generateReceiptNumber()
- calculateCommission($revenue, $stylist_id)
- checkAvailability($stylist_id, $date, $time, $duration)
- calculateDailyRevenue($date)
- validatePaymentMethod($method)
- generateReportPDF($data, $template)
- generateReportExcel($data, $template)
```

### API Endpoints (api/)

```
REST API Endpoints:

GET  /api/appointments          # List appointments
POST /api/appointments          # Create appointment
GET  /api/appointments/{id}     # Get appointment
PUT  /api/appointments/{id}     # Update appointment
DELETE /api/appointments/{id}   # Cancel appointment

GET  /api/payments              # List payments
POST /api/payments              # Process payment
GET  /api/payments/{id}         # Get payment
POST /api/payments/{id}/refund  # Refund payment

GET  /api/inventory             # List products
POST /api/inventory             # Add product
PUT  /api/inventory/{id}        # Update product
POST /api/inventory/{id}/stock  # Adjust stock

GET  /api/reports/{type}        # Generate report
GET  /api/reports/{type}/export # Export report
POST /api/reports/schedule      # Schedule report

Response Format:
{
  "success": true|false,
  "data": {...},
  "error": "error message",
  "timestamp": "2026-06-16T10:30:00Z"
}
```

### Middleware Stack

```php
// Middleware execution order:
1. ErrorHandler.php          # Catch and log errors
2. RequestValidator.php      # Validate input
3. Authentication.php        # Check if logged in
4. Authorization.php         # Check role permissions
5. RateLimiter.php           # Prevent abuse
6. RequestLogger.php         # Log all requests
7. Handler.php               # Execute business logic
8. ResponseFormatter.php     # Format output
9. SecurityHeaders.php       # Add security headers
```

---

## Implementation Roadmap

### Phase 1: Core Payment Processing (Week 1-2)
**Priority: HIGH**

1. **Database Enhancement**
   - Create `payments`, `receipts`, `payment_discounts` tables
   - Create `daily_cash_reconciliation` table
   - Alter `appointments` to link to payments table
   - **Time: 2 hours**

2. **Unified Payment Processor**
   - Create `PaymentProcessor.php` class
   - Implement factory pattern for payment methods
   - Create `CashPayment.php` handler
   - Create `PaymentReceipt.php` generator
   - **Time: 6 hours**

3. **Card Payment Integration**
   - Stripe/Pesapal API integration
   - `CardPayment.php` handler
   - 3D Secure handling
   - Tokenization for recurring payments
   - **Time: 8 hours**

4. **Payment Pages**
   - `admin/revenue_reconciliation.php` - Daily reconciliation
   - `admin/settings/payment_config.php` - Payment settings
   - `client/payment_receipt.php` - Receipt viewing
   - **Time: 4 hours**

**Total Phase 1: 20 hours**

---

### Phase 2: Inventory Management (Week 2-3)
**Priority: HIGH**

1. **Database Setup**
   - Create product, stock, supplier tables
   - Create purchase order tables
   - **Time: 2 hours**

2. **Inventory Manager Class**
   - `Inventory.php` - Main class
   - Stock movement tracking
   - Low-stock alerts
   - Expiry tracking
   - **Time: 8 hours**

3. **Purchase Order System**
   - PO creation, approval, delivery
   - Automatic PO suggestion from low-stock items
   - Supplier management
   - **Time: 6 hours**

4. **Admin Pages**
   - `admin/manage_inventory.php` - Inventory CRUD
   - `admin/manage_suppliers.php` - Supplier management
   - `admin/purchase_orders.php` - PO management
   - **Time: 6 hours**

5. **Stock Usage Tracking**
   - Link product usage to appointments
   - Auto-decrement on appointment completion
   - Product recommendations based on service
   - **Time: 4 hours**

**Total Phase 2: 26 hours**

---

### Phase 3: Advanced Reporting (Week 3-4)
**Priority: HIGH**

1. **Report Infrastructure**
   - Base `Report.php` class
   - PDF export (TCPDF/mPDF)
   - Excel export (PhpSpreadsheet)
   - CSV export
   - **Time: 8 hours**

2. **Report Classes**
   - `DailySalesReport.php`
   - `MonthlyRevenueReport.php`
   - `AppointmentReport.php`
   - `CustomerReport.php`
   - `StaffPerformanceReport.php`
   - `InventoryStockReport.php`
   - **Time: 12 hours**

3. **Report Pages**
   - `admin/reports/daily_sales.php`
   - `admin/reports/monthly_revenue.php`
   - `admin/reports/appointments.php`
   - `admin/reports/customers.php`
   - `admin/reports/staff_performance.php`
   - `admin/reports/inventory_stock.php`
   - **Time: 10 hours**

4. **Report Scheduling & Distribution**
   - `admin/reports/report_scheduler.php`
   - Background jobs for scheduled reports
   - Email delivery integration
   - **Time: 6 hours**

**Total Phase 3: 36 hours**

---

### Phase 4: Enhanced Business Logic (Week 4-5)
**Priority: MEDIUM**

1. **Customer Management**
   - `customer_preferences` table
   - `customer_loyalty` table
   - Loyalty points system
   - Customer segmentation
   - **Time: 8 hours**

2. **Stylist Commission**
   - `stylist_commission` table
   - Commission calculation engine
   - Commission tracking page
   - **Time: 6 hours**

3. **Service History & Feedback**
   - Multi-service appointments
   - Product usage tracking
   - Service feedback form
   - Customer service history view
   - **Time: 8 hours**

4. **Advanced Scheduling**
   - `working_hours` table
   - `staff_breaks` table
   - Leave management (`stylist_leave`)
   - Availability calendar optimization
   - **Time: 8 hours**

**Total Phase 4: 30 hours**

---

### Phase 5: Dashboard & Analytics (Week 5)
**Priority: MEDIUM**

1. **Financial Dashboard**
   - Real-time revenue metrics
   - Profit/loss analysis
   - Revenue by payment method
   - Revenue by stylist/service
   - **Time: 8 hours**

2. **Performance Dashboard**
   - Stylist metrics
   - Customer metrics
   - Appointment metrics
   - Inventory metrics
   - **Time: 8 hours**

3. **Optimization Pages**
   - `admin/financial_dashboard.php`
   - `admin/staff_performance.php`
   - Stylist commission tracker
   - **Time: 6 hours**

**Total Phase 5: 22 hours**

---

### Phase 6: Frontend Enhancement & Testing (Week 6)
**Priority: MEDIUM**

1. **Client Pages**
   - Enhanced `client/my_appointments.php` with history
   - `client/service_history.php`
   - `client/loyalty_points.php`
   - `client/my_profile.php` with preferences
   - **Time: 8 hours**

2. **Stylist Pages**
   - `stylist/commission_tracker.php`
   - `stylist/performance_metrics.php`
   - `stylist/leave_requests.php`
   - **Time: 6 hours**

3. **Testing & QA**
   - Unit tests for business logic
   - Integration tests for API endpoints
   - UI/UX testing
   - Performance testing
   - **Time: 12 hours**

4. **Documentation**
   - API documentation
   - User manuals
   - Admin guides
   - **Time: 8 hours**

**Total Phase 6: 34 hours**

---

### Implementation Timeline

```
Week 1:
- Days 1-2: Phase 1 - Payment Processing (20 hours)
- Days 3-4: Phase 2 - Inventory (13 hours initial)

Week 2:
- Days 1-3: Phase 2 - Inventory (13 remaining hours)
- Days 4-5: Phase 3 - Reports (18 hours initial)

Week 3:
- Days 1-2: Phase 3 - Reports (18 remaining hours)
- Days 3-5: Phase 4 - Business Logic (20 hours initial)

Week 4:
- Days 1-2: Phase 4 - Business Logic (10 remaining hours)
- Days 3-5: Phase 5 - Dashboards (22 hours)

Week 5:
- Days 1-3: Phase 6 - Frontend (18 hours initial)
- Days 4-5: Optimization & Buffer

Week 6:
- Buffer for delays, testing, refinement

Total Estimated: ~200 developer hours (~5 weeks for 1 developer)
```

---

## Integration Points

### External APIs/Services

```
1. M-Pesa (Daraja API)
   - Status: ✅ Implemented (STK push)
   - Callback handler: /client/mpesa_callback.php
   - Config: .env MPESA_* variables

2. Stripe Payment API
   - Status: ❌ Not implemented
   - Endpoint: https://api.stripe.com/v1/...
   - Config needed: STRIPE_KEY, STRIPE_SECRET

3. Pesapal Payment API
   - Status: ❌ Not implemented
   - Endpoint: https://pesapal.com/api/...
   - Config needed: PESAPAL_KEY, PESAPAL_SECRET

4. TCPDF/mPDF Library
   - Status: ❌ Not installed
   - Use for PDF generation
   - Installation: composer require tecnickcom/tcpdf

5. PhpSpreadsheet Library
   - Status: ❌ Not installed
   - Use for Excel generation
   - Installation: composer require phpoffice/phpspreadsheet

6. Swift Mailer (Email)
   - Status: ⚠️ Partial - uses PHP mail()
   - Upgrade for SMTP support
   - Installation: composer require swiftmailer/swiftmailer

7. Chart.js (Frontend Charts)
   - Status: ✅ Can use CDN
   - For report visualizations
   - No installation needed (use CDN)

8. FullCalendar (Appointment Calendar)
   - Status: ❌ Not implemented
   - For visual scheduling
   - Installation: npm install @fullcalendar/core
```

### System Dependencies

```
Software:
- PHP 8.3+            ✅ Installed
- MySQL 8.0+          ✅ Installed
- cURL                ✅ Installed
- PDO MySQL           ✅ Installed
- Composer            ❓ Check if installed
- Node.js (npm)       ✅ Installed

Composer Packages to Install:
composer require tcpdf/tcpdf
composer require phpoffice/phpspreadsheet
composer require swiftmailer/swiftmailer
composer require stripe/stripe-php
composer require pesapal/pesapal-php-sdk
composer require monolog/monolog
composer require psr/log
```

### Configuration Requirements

```
.env Variables Needed:

Payment Methods:
MPESA_ENVIRONMENT=sandbox
MPESA_CONSUMER_KEY=
MPESA_CONSUMER_SECRET=
MPESA_SHORTCODE=
MPESA_PASSKEY=
MPESA_CALLBACK_URL=http://localhost:8000/client/mpesa_callback.php

STRIPE_PUBLIC_KEY=
STRIPE_SECRET_KEY=

PESAPAL_KEY=
PESAPAL_SECRET=

Email:
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls

SMS (Twilio or similar):
SMS_PROVIDER=twilio
SMS_ACCOUNT_SID=
SMS_AUTH_TOKEN=
SMS_FROM_NUMBER=

Business Settings:
TAX_RATE=0.16
CURRENCY=KES
BUSINESS_NAME=MUSA Beauty
BUSINESS_PHONE=+254700000000
BUSINESS_EMAIL=info@musabeauty.com
```

---

## Summary

This comprehensive implementation plan covers:
- ✅ **19 modules** across Core Beauty, Payments, and Reports
- ✅ **Database design** with 30+ new tables
- ✅ **UI structure** and component organization
- ✅ **Business logic** for payment processing, inventory, and reporting
- ✅ **Code architecture** with recommended patterns and structure
- ✅ **6-phase implementation roadmap** (~200 developer hours)
- ✅ **Integration points** for external APIs and dependencies

**Recommended Next Steps:**
1. Review and approve this plan
2. Set up development environment and install composer dependencies
3. Begin Phase 1 (Payment Processing) immediately
4. Conduct code reviews after each phase
5. Perform user acceptance testing in parallel with development
