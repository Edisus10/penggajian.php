-- Sistem Penggajian Karyawan Database Schema
CREATE DATABASE IF NOT EXISTS payroll_system;
USE payroll_system;

-- Tabel Admin
CREATE TABLE admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Karyawan
CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    position ENUM('sales', 'regular') NOT NULL,
    basic_salary DECIMAL(12,2) NOT NULL,
    commission_rate DECIMAL(5,2) DEFAULT 0, -- Untuk sales
    phone VARCHAR(20),
    address TEXT,
    hire_date DATE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel Gaji Bulanan
CREATE TABLE monthly_salaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    month TINYINT NOT NULL,
    year SMALLINT NOT NULL,
    basic_salary DECIMAL(12,2) NOT NULL,
    sales_amount DECIMAL(12,2) DEFAULT 0, -- Untuk sales
    commission DECIMAL(12,2) DEFAULT 0, -- Komisi sales
    total_bon DECIMAL(12,2) DEFAULT 0, -- Total BON bulan ini
    gross_salary DECIMAL(12,2) NOT NULL, -- Gaji kotor
    net_salary DECIMAL(12,2) NOT NULL, -- Gaji bersih setelah dipotong BON
    payment_date DATE,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_employee_month_year (employee_id, month, year)
);

-- Tabel BON (Pinjaman/Utang Karyawan)
CREATE TABLE employee_bons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description TEXT,
    bon_date DATE NOT NULL,
    deduction_month TINYINT NOT NULL, -- Bulan dipotong
    deduction_year SMALLINT NOT NULL, -- Tahun dipotong
    status ENUM('pending', 'deducted') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Tabel Sales (untuk tracking penjualan sales)
CREATE TABLE sales_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    sales_amount DECIMAL(12,2) NOT NULL,
    sales_date DATE NOT NULL,
    description TEXT,
    month TINYINT NOT NULL,
    year SMALLINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Insert default admin
INSERT INTO admin (username, password, name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');
-- Password: password

-- Sample data
INSERT INTO employees (employee_code, name, position, basic_salary, commission_rate, phone, address, hire_date) VALUES
('EMP001', 'John Doe', 'regular', 5000000.00, 0, '081234567890', 'Jakarta', '2024-01-01'),
('SALES001', 'Jane Smith', 'sales', 4000000.00, 0.05, '081234567891', 'Bandung', '2024-01-01'),
('EMP002', 'Bob Wilson', 'regular', 4500000.00, 0, '081234567892', 'Surabaya', '2024-02-01'),
('SALES002', 'Alice Johnson', 'sales', 3500000.00, 0.07, '081234567893', 'Medan', '2024-02-01');
