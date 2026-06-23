-- MUSA Beauty Management System database schema

CREATE DATABASE IF NOT EXISTS musa_beauty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE musa_beauty;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  phone VARCHAR(20),
  password VARCHAR(255) NOT NULL,
  role ENUM('client','stylist','admin') NOT NULL DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(100) NOT NULL,
  price INT NOT NULL DEFAULT 0,
  duration VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  image VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stylists (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  specialization VARCHAR(255) DEFAULT 'Beauty specialist',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  stylist_id INT NOT NULL,
  service_id INT NOT NULL,
  appointment_date DATETIME NOT NULL,
  status ENUM('booked','cancelled','completed') NOT NULL DEFAULT 'booked',
  reminder_sent TINYINT(1) NOT NULL DEFAULT 0,
  payment_method ENUM('cash','mpesa') NOT NULL DEFAULT 'cash',
  payment_status ENUM('pending','paid','failed','unpaid') NOT NULL DEFAULT 'unpaid',
  amount_paid INT NOT NULL DEFAULT 0,
  mpesa_checkout_request_id VARCHAR(255) DEFAULT NULL,
  mpesa_receipt_number VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (stylist_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mpesa_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  merchant_request_id VARCHAR(255) DEFAULT NULL,
  checkout_request_id VARCHAR(255) DEFAULT NULL,
  result_code VARCHAR(50) DEFAULT NULL,
  result_desc TEXT DEFAULT NULL,
  amount VARCHAR(50) DEFAULT NULL,
  mpesa_receipt_number VARCHAR(100) DEFAULT NULL,
  phone_number VARCHAR(30) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  stylist_id INT NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (stylist_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO users (name, email, phone, password, role)
VALUES ('Salon Admin', 'admin@example.com', '+254700000000', '$2y$10$gzeWCCEwPURPImJyctvr0e/GEeXwtA6hLL8H5Xla884z3IApKtV7G', 'admin');

INSERT IGNORE INTO services (name, category, price, duration, description, image) VALUES
('Haircut (Ladies)', 'Hair', 1500, '45 mins', 'Classic ladies haircut with precision styling.', NULL),
('Haircut (Men)', 'Hair', 500, '30 mins', 'Clean, tailored men''s haircut designed for your look.', NULL),
('Wash & Blow-dry', 'Hair', 1000, '45 mins', 'Luxury wash and blow-dry for smooth shine.', NULL),
('Braiding', 'Hair', 2500, '2 hours', 'Detailed braids for stylish long-lasting definition.', NULL),
('Knotless Braids', 'Hair', 3000, '3 hours', 'Soft knotless braids for a comfortable, natural finish.', NULL),
('Dreadlocks Installation', 'Hair', 4000, '4 hours', 'Professional dreadlocks installation for a bold statement.', NULL),
('Hair Coloring', 'Hair', 3500, '2 hours', 'Custom hair color application with quality pigments.', NULL),
('Hair Treatment', 'Hair', 3000, '1 hour', 'Revitalizing hair treatment for strength and shine.', NULL),
('Bridal Hairstyling', 'Hair', 4500, '2 hours', 'Elegant bridal styling designed for your special day.', NULL),
('Manicure', 'Nails', 1500, '45 mins', 'Beautiful manicure with nail shaping and polish.', NULL),
('Pedicure', 'Nails', 2000, '1 hour', 'Relaxing pedicure for soft, smooth feet.', NULL),
('Gel Polish', 'Nails', 2500, '1 hour', 'Long-lasting gel polish with a high-gloss finish.', NULL),
('Acrylic Nails', 'Nails', 3500, '2 hours', 'Custom acrylic nail extensions with detailed styling.', NULL),
('Nail Refill', 'Nails', 2000, '1 hour', 'Refill and refresh your existing nail enhancements.', NULL),
('Nail Art', 'Nails', 1000, '30 mins', 'Creative nail art detailing for a polished look.', NULL),
('Basic Makeup', 'Makeup', 3000, '1 hour', 'Everyday polished makeup for a fresh glow.', NULL),
('Event Makeup', 'Makeup', 5000, '1.5 hours', 'Elegant makeup for special events and occasions.', NULL),
('Bridal Makeup', 'Makeup', 8000, '2 hours', 'Full bridal makeup look for your wedding day.', NULL),
('Makeup + Lashes', 'Makeup', 4000, '1.5 hours', 'Makeup application with lash enhancements.', NULL),
('Strip Lashes', 'Makeup', 1000, '20 mins', 'Professional strip lash application for added glamour.', NULL),
('Eyebrow Shaping', 'Beauty', 500, '20 mins', 'Precision eyebrow shaping to frame your face.', NULL),
('Henna Brows', 'Beauty', 1500, '45 mins', 'Henna brow tinting for fuller, natural-looking brows.', NULL),
('Lash Extensions', 'Beauty', 3000, '1.5 hours', 'Volume lash extensions for a dramatic finish.', NULL),
('Lash Refill', 'Beauty', 2000, '1 hour', 'Perfect lash refill to maintain your look.', NULL),
('Basic Facial', 'Spa', 3500, '1 hour', 'Refreshing facial treatment for glowing skin.', NULL),
('Advanced Facial', 'Spa', 6000, '1.5 hours', 'Deep cleansing and rejuvenating facial treatment.', NULL),
('Full Body Massage', 'Spa', 4000, '1 hour', 'Relaxing full body massage for total comfort.', NULL),
('Deep Tissue Massage', 'Spa', 5000, '1 hour', 'Deep tissue massage to relieve muscle tension.', NULL),
('Spa Package', 'Spa', 12000, '3 hours', 'Premium spa package featuring multiple treatments.', NULL),
('Face Waxing', 'Grooming', 800, '20 mins', 'Smoother skin with gentle face waxing.', NULL),
('Underarm Waxing', 'Grooming', 1500, '30 mins', 'Clean underarm waxing for a smooth finish.', NULL),
('Half Leg Waxing', 'Grooming', 2000, '45 mins', 'Half leg waxing for neat, silky legs.', NULL),
('Full Leg Waxing', 'Grooming', 3000, '1 hour', 'Full leg waxing for complete smoothness.', NULL),
('Brazilian Wax', 'Grooming', 3500, '1 hour', 'Brazilian waxing for precise grooming.', NULL),
('Bridal Package', 'Package', 10000, '3 hours', 'Comprehensive bridal package for your special day.', NULL),
('Full Grooming Package', 'Package', 8000, '2.5 hours', 'Complete grooming package with beauty essentials.', NULL),
('Premium Spa Package', 'Package', 12500, '3 hours', 'Luxury spa package with premium treatments.', NULL);

INSERT IGNORE INTO users (name, email, password, role) VALUES
('Mia Stylists', 'mia@beauty.com', '$2y$10$J4Rb0DGqlf9YswLYwANgMe8Hb8QNofNN2fJJkHynpClCLUDqUcLK6', 'stylist');

INSERT IGNORE INTO stylists (user_id, specialization) VALUES
((SELECT id FROM users WHERE email = 'mia@beauty.com'), 'Hair styling and color');
