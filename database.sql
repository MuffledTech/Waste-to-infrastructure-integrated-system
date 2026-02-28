-- Database: waste_management_system


USE if0_41197758_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('citizen', 'collector', 'admin') NOT NULL,
    municipality VARCHAR(100),
    home_number VARCHAR(50),
    citizenship_number VARCHAR(50),
    status ENUM('pending','approved') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- If DB already exists, safely add new columns (run these if upgrading an existing installation)
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS home_number VARCHAR(50);
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS citizenship_number VARCHAR(50);
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('pending','approved') DEFAULT 'approved';
-- UPDATE users SET status = 'approved' WHERE status IS NULL;

-- Waste Reports Table
CREATE TABLE IF NOT EXISTS waste_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    citizen_id INT NOT NULL,
    waste_type VARCHAR(50) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    weight DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('pending', 'accepted', 'collected', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (citizen_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Pickups Table
CREATE TABLE IF NOT EXISTS pickups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    collector_id INT NOT NULL,
    verification_image VARCHAR(255),
    collected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES waste_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (collector_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Reward Points Table
-- NOTE: UNIQUE KEY on user_id is required for ON DUPLICATE KEY UPDATE to work correctly.
CREATE TABLE IF NOT EXISTS reward_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT DEFAULT 0,
    UNIQUE KEY unique_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Infrastructure Projects Table
CREATE TABLE IF NOT EXISTS infrastructure_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    description TEXT,
    funded_amount DECIMAL(15, 2) DEFAULT 0.00,
    municipality VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fund Allocations Table
CREATE TABLE IF NOT EXISTS fund_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    waste_report_id INT NOT NULL,
    amount_allocated DECIMAL(15, 2) NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES infrastructure_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (waste_report_id) REFERENCES waste_reports(id) ON DELETE CASCADE
);

-- Default Admin User (Password: admin123)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (name, email, password, role, municipality, status) VALUES 
('System Admin', 'admin@waste.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Central', 'approved');

-- Default Collector (Password: collector123)
INSERT INTO users (name, email, password, role, municipality, status) VALUES 
('John Collector', 'collector@waste.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'collector', 'Central', 'approved');

-- Default Citizen (Password: citizen123)
INSERT INTO users (name, email, password, role, municipality, status) VALUES 
('Jane Citizen', 'citizen@waste.gov', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'citizen', 'Central', 'approved');
