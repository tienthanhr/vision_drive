<?php
// Create a simple SQLite database for testing
try {
    $pdo = new PDO('sqlite:vision_drive.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS courses (
        course_id INTEGER PRIMARY KEY AUTOINCREMENT,
        course_name VARCHAR(100) NOT NULL,
        description TEXT,
        duration_hours INTEGER DEFAULT 8,
        price DECIMAL(10,2) NOT NULL,
        max_capacity INTEGER DEFAULT 10,
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS campuses (
        campus_id INTEGER PRIMARY KEY AUTOINCREMENT,
        campus_name VARCHAR(100) NOT NULL,
        city VARCHAR(50),
        region VARCHAR(50),
        address TEXT,
        phone VARCHAR(20),
        email VARCHAR(100),
        is_active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS training_sessions (
        session_id INTEGER PRIMARY KEY AUTOINCREMENT,
        course_id INTEGER,
        campus_id INTEGER,
        session_date DATE NOT NULL,
        session_time TIME NOT NULL,
        instructor VARCHAR(100),
        max_capacity INTEGER DEFAULT 10,
        status VARCHAR(20) DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(course_id),
        FOREIGN KEY (campus_id) REFERENCES campuses(campus_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50),
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        role VARCHAR(20) DEFAULT 'trainee',
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS enrollments (
        enrollment_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        session_id INTEGER,
        enrollment_date DATE NOT NULL,
        status VARCHAR(20) DEFAULT 'enrolled',
        special_requirements TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (session_id) REFERENCES training_sessions(session_id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
        document_id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        document_type VARCHAR(50),
        original_name VARCHAR(255),
        file_path VARCHAR(500),
        file_size INTEGER,
        mime_type VARCHAR(100),
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )");
    
    // Insert sample data
    $pdo->exec("INSERT OR IGNORE INTO courses (course_id, course_name, description, duration_hours, price) VALUES 
        (1, 'Forklift Operator', 'Basic forklift training course', 8, 350.00),
        (2, 'Forklift Refresher', 'Refresher course for experienced operators', 4, 180.00),
        (3, 'Class 2 Truck', 'Heavy vehicle training course', 16, 750.00)");
    
    $pdo->exec("INSERT OR IGNORE INTO campuses (campus_id, campus_name, city, region) VALUES 
        (1, 'Auckland', 'Auckland', 'New Zealand'),
        (2, 'Hamilton', 'Hamilton', 'New Zealand'),
        (3, 'Christchurch', 'Christchurch', 'New Zealand')");
    
    // Insert sample training sessions
    $pdo->exec("INSERT OR IGNORE INTO training_sessions (session_id, course_id, campus_id, session_date, session_time, instructor, max_capacity) VALUES 
        (1, 1, 1, '2024-12-15', '09:00', 'John Smith', 10),
        (2, 1, 2, '2024-12-18', '13:00', 'Sarah Johnson', 10),
        (3, 2, 1, '2024-12-20', '09:00', 'Mike Wilson', 8),
        (4, 3, 3, '2024-12-22', '08:30', 'David Brown', 6),
        (5, 1, 2, '2025-01-08', '09:00', 'Sarah Johnson', 10),
        (6, 2, 2, '2025-01-15', '13:00', 'John Smith', 8)");
    
    echo "Database setup completed successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>