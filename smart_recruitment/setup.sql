-- ============================================================
-- StudentGig - Complete Database Setup Script
-- Run this ONCE in phpMyAdmin > SQL tab
-- ============================================================

-- 1. Create database (skip if already exists)
CREATE DATABASE IF NOT EXISTS smart_recruitment;
USE smart_recruitment;

-- 2. USERS table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('candidate','employer','admin') DEFAULT 'candidate',
    skills VARCHAR(255),
    bio TEXT,
    location VARCHAR(100),
    university VARCHAR(150),
    current_institution VARCHAR(150),
    high_school VARCHAR(150),
    grad_school VARCHAR(150),
    study_status VARCHAR(50) DEFAULT 'Studying',
    technical_skills TEXT,
    soft_skills TEXT,
    portfolio_bio TEXT,
    is_banned TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. TASKS table
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    price DECIMAL(10,2) DEFAULT 0,
    location VARCHAR(100) DEFAULT 'Remote',
    status ENUM('open','assigned','completed') DEFAULT 'open',
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. APPLICATIONS table
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    candidate_id INT NOT NULL,
    bid_amount DECIMAL(10,2),
    delivery_days INT,
    proposal_text TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    is_selected TINYINT(1) DEFAULT 0,
    submission_link VARCHAR(500),
    submission_note TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. REVIEWS table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- 6. PASSWORD RESETS table (for Forgot Password feature)
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. PORTFOLIO ITEMS table (for student profile gallery)
CREATE TABLE IF NOT EXISTS portfolio_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    image_path VARCHAR(500),
    caption VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- ADD NEW COLUMNS (safe to run even if columns already exist)
-- Run each ALTER TABLE separately if you get errors
-- ============================================================

-- Add deadline to tasks (if not exists)
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS deadline DATE;

-- Add category to tasks (if not exists)
ALTER TABLE tasks ADD COLUMN IF NOT EXISTS category VARCHAR(100);

-- Add is_banned to users (if not exists)
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) DEFAULT 0;

-- Add created_at to users (if not exists)
ALTER TABLE users ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- ============================================================
-- CREATE DEFAULT ADMIN ACCOUNT
-- Login: admin@studentgig.com / Password: Admin@1234
-- CHANGE THIS PASSWORD after first login!
-- ============================================================
INSERT IGNORE INTO users (name, email, password, role)
VALUES (
    'Admin',
    'admin@studentgig.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

-- ============================================================
-- DONE! Your database is ready.
-- ============================================================
