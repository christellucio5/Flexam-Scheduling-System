-- Database schema enhancements for department-specific filtering
-- This ensures proper department/program assignment for Program Heads

-- Add college and program columns to existing users table
ALTER TABLE users ADD COLUMN college VARCHAR(100) DEFAULT NULL;
ALTER TABLE users ADD COLUMN program VARCHAR(100) DEFAULT NULL;

-- Create schedules table for exam scheduling
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    course_code VARCHAR(20),
    course_name VARCHAR(255),
    college VARCHAR(100),
    program VARCHAR(100),
    exam_type VARCHAR(50),
    year_level VARCHAR(20),
    date DATE,
    time_slot VARCHAR(50),
    duration VARCHAR(20),
    room_id INT,
    room_name VARCHAR(100),
    proctor_id INT,
    proctor_name VARCHAR(255),
    status VARCHAR(20) DEFAULT 'Pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_by (created_by)
);

-- Create feedbacks table for student feedback
CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT,
    student_name VARCHAR(255),
    student_email VARCHAR(255),
    college VARCHAR(100),
    program VARCHAR(100),
    feedback_text TEXT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    feedback_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_schedule_id (schedule_id)
);

-- Create courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    college VARCHAR(100) NOT NULL,
    program VARCHAR(100) NOT NULL,
    year_level VARCHAR(20),
    semester VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create analytics_data table for tracking metrics
CREATE TABLE IF NOT EXISTS analytics_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college VARCHAR(100),
    program VARCHAR(100),
    metric_type VARCHAR(50), -- 'schedule_count', 'feedback_count', 'room_utilization', etc.
    metric_value DECIMAL(10,2),
    date_recorded DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_college_program ON users(college, program);
CREATE INDEX IF NOT EXISTS idx_schedules_college_program ON schedules(college, program);
CREATE INDEX IF NOT EXISTS idx_feedbacks_college_program ON feedbacks(college, program);
CREATE INDEX IF NOT EXISTS idx_analytics_college_program ON analytics_data(college, program);