-- JEMA Water Management System Database
-- Complete database structure for water supply management in Somalia

-- Create Database
CREATE DATABASE IF NOT EXISTS jema_water_system;
USE jema_water_system;

-- 1. Users Table (Admin/Staff)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    phone VARCHAR(20),
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Customers Table
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address VARCHAR(255),
    district VARCHAR(50) NOT NULL,
    area VARCHAR(50),
    meter_number VARCHAR(30) UNIQUE NOT NULL,
    meter_type ENUM('manual', 'digital') NOT NULL DEFAULT 'manual',
    connection_date DATE,
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    account_balance DECIMAL(10, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Water Sources Table
CREATE TABLE IF NOT EXISTS water_sources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    source_name VARCHAR(100) NOT NULL,
    source_type ENUM('well', 'tank', 'truck', 'borehole', 'reservoir') NOT NULL,
    location VARCHAR(100),
    capacity DECIMAL(10, 2),
    capacity_unit VARCHAR(10) DEFAULT 'liters',
    status ENUM('active', 'maintenance', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Water Supply Table (Daily Input)
CREATE TABLE IF NOT EXISTS water_supply (
    id INT PRIMARY KEY AUTO_INCREMENT,
    supply_date DATE NOT NULL,
    source_id INT NOT NULL,
    quantity_received DECIMAL(10, 2) NOT NULL,
    unit VARCHAR(10) DEFAULT 'liters',
    cost DECIMAL(10, 2),
    notes TEXT,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_id) REFERENCES water_sources(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- 5. Water Usage Table (Manual Meter Readings)
CREATE TABLE IF NOT EXISTS water_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    reading_date DATE NOT NULL,
    previous_reading DECIMAL(10, 2),
    current_reading DECIMAL(10, 2) NOT NULL,
    quantity_used DECIMAL(10, 2),
    unit VARCHAR(10) DEFAULT 'liters',
    recorded_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- 6. Bills Table
CREATE TABLE IF NOT EXISTS bills (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bill_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    billing_period_start DATE NOT NULL,
    billing_period_end DATE NOT NULL,
    quantity_used DECIMAL(10, 2) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    service_fee DECIMAL(10, 2) DEFAULT 0,
    taxes DECIMAL(10, 2) DEFAULT 0,
    grand_total DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('unpaid', 'partially_paid', 'paid') NOT NULL DEFAULT 'unpaid',
    due_date DATE,
    issued_date DATE DEFAULT CURDATE(),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- 7. Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bill_id INT NOT NULL,
    customer_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'cheque') NOT NULL,
    payment_date DATE DEFAULT CURDATE(),
    payment_time TIME DEFAULT CURTIME(),
    transaction_reference VARCHAR(100),
    recorded_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bills(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);

-- 8. Alerts Table
CREATE TABLE IF NOT EXISTS alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    alert_type ENUM('low_water', 'high_usage', 'unpaid_bill', 'leak_suspected', 'system_alert') NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    title VARCHAR(200) NOT NULL,
    description TEXT,
    related_customer_id INT,
    status ENUM('new', 'acknowledged', 'resolved') NOT NULL DEFAULT 'new',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (related_customer_id) REFERENCES customers(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- 9. Complaints/Issues Table
CREATE TABLE IF NOT EXISTS complaints (
    id INT PRIMARY KEY AUTO_INCREMENT,
    complaint_number VARCHAR(30) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    complaint_type ENUM('leak', 'low_pressure', 'billing_issue', 'meter_issue', 'service_quality', 'other') NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255),
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    assigned_to INT,
    resolution_notes TEXT,
    resolved_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- 10. Settings Table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 11. Audit Log Table
CREATE TABLE IF NOT EXISTS audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    table_name VARCHAR(50),
    record_id INT,
    changes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert Default Admin User (password: admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@jema.so', '$2y$10$N9qo8uLOickgx2ZMRZoHyeIGg5eU7.n2.F3Z.Z3Z3Z3Z3Z3Z3Z3Z3Z', 'Admin User', 'admin');

-- Insert Default Water Sources
INSERT INTO water_sources (source_name, source_type, location, capacity) VALUES
('Main Well', 'well', 'District 1', 50000),
('Storage Tank', 'tank', 'Downtown', 100000),
('Backup Well', 'borehole', 'District 2', 30000);

-- Insert Default Settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('price_per_liter', '0.5', 'Price per liter in Somali Shilling'),
('company_name', 'JEMA Water Management', 'Company name'),
('company_address', 'Mogadishu, Somalia', 'Company address'),
('company_phone', '+252 61 XXX XXXX', 'Company phone number'),
('company_email', 'info@jema.so', 'Company email'),
('service_fee', '5000', 'Monthly service fee in Somali Shilling'),
('tax_rate', '10', 'Tax rate percentage'),
('low_water_alert_level', '20000', 'Alert when water below this liters'),
('high_usage_alert_level', '500', 'Alert when customer usage exceeds this liters'),
('system_currency', 'SOS', 'System currency'),
('language', 'en', 'Default language (en/so)');

-- Create Indexes for Performance
CREATE INDEX idx_customer_meter ON customers(meter_number);
CREATE INDEX idx_water_usage_date ON water_usage(reading_date);
CREATE INDEX idx_bills_customer ON bills(customer_id);
CREATE INDEX idx_bills_status ON bills(payment_status);
CREATE INDEX idx_payments_date ON payments(payment_date);
CREATE INDEX idx_alerts_status ON alerts(status);
CREATE INDEX idx_alerts_type ON alerts(alert_type);
CREATE INDEX idx_complaints_status ON complaints(status);
