CREATE DATABASE IF NOT EXISTS habit_tracker;
USE habit_tracker;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    profile_pic VARCHAR(255) DEFAULT 'assets/default-avatar.png',
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Routines Table
CREATE TABLE IF NOT EXISTS routines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    created_at DATE DEFAULT CURRENT_DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Routine Logs Table
CREATE TABLE IF NOT EXISTS routine_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    routine_id INT NOT NULL,
    status ENUM('done', 'skipped') NOT NULL,
    logged_date DATE NOT NULL,
    FOREIGN KEY (routine_id) REFERENCES routines(id) ON DELETE CASCADE
);

-- 4. Habit Categories Table (Admin)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Habits Table
CREATE TABLE IF NOT EXISTS habits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    target_frequency VARCHAR(50) DEFAULT 'daily',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- 6. Habit Logs Table
CREATE TABLE IF NOT EXISTS habit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    habit_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    points_awarded INT DEFAULT 0,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 7. Content Moderation Table
CREATE TABLE IF NOT EXISTS content_moderation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_type ENUM('habit', 'log', 'user') NOT NULL,
    item_id INT NOT NULL,
    reason TEXT,
    status ENUM('flagged', 'approved', 'removed') DEFAULT 'flagged',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed Default Admin Account (Email: admin@example.com | Password: admin123)
INSERT INTO users (username, email, password, role) 
VALUES ('admin', 'admin@example.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe11i.Z.rA3/i/KkXhE15Q3WpZgqE0R7S', 'admin')
ON DUPLICATE KEY UPDATE id=id;