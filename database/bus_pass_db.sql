-- =====================================================
-- Bus Pass Management System - Database Schema
-- Project: BCA Semester 5 Project
-- Database: bus_pass_db
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS bus_pass_db;
USE bus_pass_db;

-- =====================================================
-- Table 1: admins
-- Stores admin login credentials and details
-- =====================================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table 2: users
-- Stores student registration and profile data
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) DEFAULT NULL,
    gender ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    dob DATE DEFAULT NULL,
    age INT DEFAULT NULL,
    mobile VARCHAR(10) DEFAULT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    college_name VARCHAR(150) DEFAULT NULL,
    course VARCHAR(50) DEFAULT NULL,
    semester VARCHAR(10) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(50) DEFAULT NULL,
    state VARCHAR(50) DEFAULT NULL,
    pincode VARCHAR(6) DEFAULT NULL,
    aadhar_number VARCHAR(12) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT 'default-avatar.png',
    security_question VARCHAR(255) DEFAULT NULL,
    security_answer VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table 3: bus_routes
-- Stores bus route information (source to destination)
-- =====================================================
CREATE TABLE IF NOT EXISTS bus_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_code VARCHAR(20) NOT NULL UNIQUE,
    source VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    distance_km DECIMAL(6,2) DEFAULT NULL,
    fare DECIMAL(8,2) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_routes_source (source),
    INDEX idx_routes_destination (destination),
    INDEX idx_routes_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table 4: buses
-- Stores individual bus information
-- =====================================================
CREATE TABLE IF NOT EXISTS buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    bus_number VARCHAR(20) NOT NULL UNIQUE,
    bus_name VARCHAR(100) NOT NULL,
    bus_type ENUM('AC', 'Non-AC', 'Sleeper', 'Semi-Sleeper') DEFAULT 'Non-AC',
    capacity INT DEFAULT 50,
    driver_name VARCHAR(100) DEFAULT NULL,
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    pass_available ENUM('Yes', 'No') DEFAULT 'Yes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE CASCADE,
    INDEX idx_buses_route (route_id),
    INDEX idx_buses_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table 5: timetable
-- Stores bus schedule (departure, arrival, days)
-- =====================================================
CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    bus_id INT NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    days_of_operation VARCHAR(100) DEFAULT 'Mon-Sat',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE CASCADE,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    INDEX idx_timetable_route (route_id),
    INDEX idx_timetable_bus (bus_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table 6: applications
-- Stores bus pass applications submitted by students
-- =====================================================
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    route_id INT NOT NULL,
    application_number VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    dob DATE NOT NULL,
    age INT NOT NULL,
    mobile VARCHAR(10) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50) NOT NULL,
    pincode VARCHAR(6) NOT NULL,
    aadhar_number VARCHAR(12) NOT NULL,
    boarding_point VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    pass_duration ENUM('1-month', '3-month', '6-month', '1-year') NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    student_id_doc VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'pending_payment', 'approved', 'rejected') DEFAULT 'pending',
    remarks TEXT DEFAULT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES bus_routes(id) ON DELETE CASCADE,
    INDEX idx_applications_user (user_id),
    INDEX idx_applications_status (status),
    INDEX idx_applications_date (applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table 7: passes
-- Stores issued bus passes (generated after approval)
-- =====================================================
CREATE TABLE IF NOT EXISTS passes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    pass_number VARCHAR(20) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('active', 'expired', 'renewed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_passes_user (user_id),
    INDEX idx_passes_status (status),
    INDEX idx_passes_expiry (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table 7b: payments
-- Tracks user payments for approved applications
-- =====================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('completed', 'failed', 'pending') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_payments_app (application_id),
    INDEX idx_payments_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table 8: notifications
-- Stores notifications for users
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table 9: feedback
-- Stores user feedback and admin replies
-- =====================================================
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    admin_reply TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_feedback_user (user_id),
    INDEX idx_feedback_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Table 10: activity_logs
-- Stores login and action audit trail
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('user', 'admin') NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_logs_user (user_type, user_id),
    INDEX idx_logs_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SEED DATA: Default Admin Account
-- Username: admin | Password: admin
-- =====================================================
INSERT INTO admins (username, password, full_name, email, phone) VALUES
('admin', '$2y$12$EMEHA/KTAaUGE3WI2cVx6e2PCLokxyVlMCaMZg6Xu/cLsbkYlhkSS', 'System Administrator', 'admin@buspass.com', '9876543210');

-- =====================================================
-- SEED DATA: Sample Users (Password: Student@123 for all)
-- =====================================================
INSERT INTO users (username, password, full_name, father_name, gender, dob, age, mobile, email, address, city, state, pincode, aadhar_number, security_question, security_answer) VALUES
('rahul.sharma', '$2y$10$bnO1jivy7fXUdcULECNAsuZ82uE6NLZCSHej02190LGBEQRfVixhe', 'Rahul Sharma', 'Mahesh Sharma', 'Male', '2003-05-15', 23, '9876543211', 'rahul.sharma@email.com', '12, Navrangpura', 'Ahmedabad', 'Gujarat', '380009', '123456789012', 'What is your pet name?', 'bruno'),
('priya.patel', '$2y$10$bnO1jivy7fXUdcULECNAsuZ82uE6NLZCSHej02190LGBEQRfVixhe', 'Priya Patel', 'Rajesh Patel', 'Female', '2004-02-20', 22, '9876543212', 'priya.patel@email.com', '45, Alkapuri', 'Vadodara', 'Gujarat', '390007', '234567890123', 'What is your birth city?', 'vadodara'),
('amit.joshi', '$2y$10$bnO1jivy7fXUdcULECNAsuZ82uE6NLZCSHej02190LGBEQRfVixhe', 'Amit Joshi', 'Suresh Joshi', 'Male', '2003-11-10', 22, '9876543213', 'amit.joshi@email.com', '78, University Road', 'Rajkot', 'Gujarat', '360005', '345678901234', 'What is your school name?', 'dps');

-- =====================================================
-- SEED DATA: Bus Routes (Gujarat)
-- =====================================================
INSERT INTO bus_routes (route_code, source, destination, distance_km, fare) VALUES
('RT001', 'Ahmedabad', 'Rajkot', 220.00, 250.00),
('RT002', 'Ahmedabad', 'Vadodara', 112.00, 150.00),
('RT003', 'Ahmedabad', 'Surat', 265.00, 300.00),
('RT004', 'Vadodara', 'Surat', 160.00, 180.00),
('RT005', 'Vadodara', 'Ahmedabad', 112.00, 150.00),
('RT006', 'Rajkot', 'Junagadh', 102.00, 120.00),
('RT007', 'Rajkot', 'Ahmedabad', 220.00, 250.00),
('RT008', 'Surat', 'Vadodara', 160.00, 180.00),
('RT009', 'Surat', 'Ahmedabad', 265.00, 300.00),
('RT010', 'Ahmedabad', 'Gandhinagar', 30.00, 40.00),
('RT011', 'Ahmedabad', 'Mehsana', 80.00, 100.00),
('RT012', 'Vadodara', 'Anand', 45.00, 60.00),
('RT013', 'Rajkot', 'Jamnagar', 90.00, 110.00),
('RT014', 'Surat', 'Vapi', 170.00, 200.00),
('RT015', 'Surat', 'Valsad', 120.00, 140.00),
('RT016', 'Ahmedabad', 'Bhavnagar', 200.00, 230.00),
('RT017', 'Vadodara', 'Bharuch', 72.00, 90.00),
('RT018', 'Ahmedabad', 'Palanpur', 140.00, 160.00);

-- =====================================================
-- SEED DATA: Buses
-- =====================================================
INSERT INTO buses (route_id, bus_number, bus_name, bus_type, capacity, driver_name, pass_available) VALUES
(1, 'GJ-01-AB-1234', 'Ahmedabad Express', 'AC', 45, 'Ramesh Patel', 'Yes'),
(1, 'GJ-01-CD-5678', 'Saurashtra Mail', 'Non-AC', 55, 'Sunil Mehta', 'Yes'),
(2, 'GJ-06-EF-9012', 'Vadodara Volvo', 'AC', 40, 'Dinesh Shah', 'Yes'),
(2, 'GJ-06-GH-3456', 'Baroda Express', 'Non-AC', 55, 'Kiran Desai', 'Yes'),
(3, 'GJ-01-IJ-7890', 'Surat Superfast', 'AC', 45, 'Manoj Kumar', 'Yes'),
(4, 'GJ-06-KL-2345', 'Golden Bridge', 'Non-AC', 50, 'Bharat Rana', 'Yes'),
(6, 'GJ-03-MN-6789', 'Gir Express', 'Non-AC', 55, 'Jayesh Solanki', 'Yes'),
(7, 'GJ-03-OP-0123', 'Rajkot Rider', 'AC', 45, 'Nilesh Joshi', 'Yes'),
(10, 'GJ-01-QR-4567', 'Capital Connect', 'Non-AC', 50, 'Ashok Thakor', 'Yes'),
(11, 'GJ-01-ST-8901', 'Mehsana Express', 'Non-AC', 55, 'Vijay Pandya', 'Yes'),
(13, 'GJ-03-UV-2345', 'Jamnagar Jet', 'AC', 40, 'Pravin Doshi', 'Yes'),
(14, 'GJ-05-WX-6789', 'South Gujarat Express', 'Semi-Sleeper', 45, 'Kamlesh Naik', 'Yes');

-- =====================================================
-- SEED DATA: Timetable
-- =====================================================
INSERT INTO timetable (route_id, bus_id, departure_time, arrival_time, days_of_operation) VALUES
(1, 1, '06:00:00', '10:00:00', 'Mon-Sat'),
(1, 2, '08:30:00', '13:00:00', 'Mon-Sat'),
(2, 3, '07:00:00', '09:00:00', 'Mon-Sat'),
(2, 4, '09:30:00', '11:30:00', 'Mon-Fri'),
(3, 5, '06:30:00', '12:00:00', 'Daily'),
(4, 6, '07:30:00', '10:30:00', 'Mon-Sat'),
(6, 7, '08:00:00', '10:00:00', 'Mon-Sat'),
(7, 8, '14:00:00', '18:00:00', 'Mon-Sat'),
(10, 9, '07:00:00', '07:45:00', 'Mon-Fri'),
(11, 10, '06:30:00', '08:30:00', 'Mon-Sat'),
(13, 11, '09:00:00', '11:00:00', 'Mon-Sat'),
(14, 12, '07:00:00', '11:00:00', 'Daily');

-- =====================================================
-- SEED DATA: Sample Notifications
-- =====================================================
INSERT INTO notifications (user_id, title, message) VALUES
(1, 'Welcome to Bus Pass System', 'Your account has been created successfully. You can now apply for a bus pass.'),
(1, 'Application Reminder', 'Don\'t forget to apply for your bus pass before the semester starts.'),
(2, 'Welcome to Bus Pass System', 'Your account has been created successfully. You can now apply for a bus pass.');

-- =====================================================
-- END OF DATABASE SCHEMA
-- =====================================================
