-- Library Management System - Database Schema
-- =============================================


-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    address TEXT,
    phone VARCHAR(30),
    password VARCHAR(255) NOT NULL,
    lodge_name VARCHAR(150),
    grand_lodge VARCHAR(150),
    year_of_registration YEAR,
    status ENUM('Member','Founder','Administrator') NOT NULL DEFAULT 'Member',
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Courses Table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    degree ENUM('Public','Apprentice','Fellowcraft','Master Mason') NOT NULL DEFAULT 'Public',
    photo VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Chapters Table
CREATE TABLE IF NOT EXISTS chapters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    youtube_url VARCHAR(500),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Chapter Documents
CREATE TABLE IF NOT EXISTS chapter_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chapter_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255),
    file_size INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
);

-- Chapter Links
CREATE TABLE IF NOT EXISTS chapter_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chapter_id INT NOT NULL,
    url VARCHAR(1000) NOT NULL,
    label VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
);

-- User Course Progress
CREATE TABLE IF NOT EXISTS user_chapter_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chapter_id INT NOT NULL,
    course_id INT NOT NULL,
    is_finished TINYINT(1) DEFAULT 0,
    finished_at TIMESTAMP NULL,
    UNIQUE KEY unique_progress (user_id, chapter_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Chapter Likes
CREATE TABLE IF NOT EXISTS chapter_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chapter_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, chapter_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
);

-- Chapter Comments
CREATE TABLE IF NOT EXISTS chapter_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    chapter_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
);

-- Sessions Table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Default Admin User (password: Admin@1234)
INSERT INTO users (name, surname, email, password, status, lodge_name, grand_lodge, year_of_registration) VALUES
('System', 'Administrator', 'admin@lms.com', '$2y$12$YKB3R6VH2f7M7EyWBm8dv.q3kBqXOjSxAUFfFdlO.VHh7g5QS3oLC', 'Administrator', 'Grand Lodge', 'Grand Lodge of Italy', 2024);

-- Sample Public Course
INSERT INTO courses (title, description, degree, is_active, created_by) VALUES
('Introduction to Our Brotherhood', 'A welcoming course for all members to understand our principles, values, and community guidelines.', 'Public', 1, 1);

INSERT INTO chapters (course_id, title, description, youtube_url, sort_order) VALUES
(1, 'Welcome & Our Mission', 'An introduction to who we are, what we stand for, and how this platform can support your journey.', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 1),
(1, 'Community Guidelines', 'Understanding the rules and etiquette that keep our community respectful and productive.', NULL, 2);
