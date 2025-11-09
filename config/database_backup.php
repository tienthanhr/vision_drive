<?php
// Database configuration for Vision Drive
// This file contains database connection settings

class DatabaseConfig {
    // Database connection parameters
    const DB_HOST = 'localhost';
    const DB_NAME = 'vision_drive';  // Change this to your actual database name
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';
    
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
        } catch (PDOException $e) {
            // Fallback to SQLite if MySQL is not available
            try {
                $this->pdo = new PDO("sqlite:vision_drive.db");
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->createSQLiteTables();
            } catch (PDOException $e2) {
                die("Database connection failed: " . $e2->getMessage());
            }
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    private function createSQLiteTables() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS courses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                duration VARCHAR(100),
                price DECIMAL(10,2),
                max_capacity INT DEFAULT 10,
                status VARCHAR(50) DEFAULT 'active',
                image VARCHAR(10) DEFAULT '🏗️',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS campuses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                address TEXT,
                status VARCHAR(50) DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS training_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                course_id INT,
                campus_id INT,
                session_date DATE,
                max_capacity INT DEFAULT 10,
                enrolled_count INT DEFAULT 0,
                status VARCHAR(50) DEFAULT 'scheduled',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id),
                FOREIGN KEY (campus_id) REFERENCES campuses(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                phone VARCHAR(50),
                license_number VARCHAR(100),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS bookings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INT,
                session_id INT,
                status VARCHAR(50) DEFAULT 'confirmed',
                booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                notes TEXT,
                FOREIGN KEY (student_id) REFERENCES students(id),
                FOREIGN KEY (session_id) REFERENCES training_sessions(id)
            )",
            
            "CREATE TABLE IF NOT EXISTS documents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                student_id INT,
                filename VARCHAR(255),
                original_name VARCHAR(255),
                file_type VARCHAR(50),
                file_size INT,
                status VARCHAR(50) DEFAULT 'pending',
                uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id)
            )"
        ];
        
        foreach ($tables as $sql) {
            $this->pdo->exec($sql);
        }
        
        $this->insertSampleData();
    }
    
    private function insertSampleData() {
        // Check if data already exists
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM courses");
        if ($stmt->fetchColumn() > 0) {
            return; // Data already exists
        }
        
        // Insert sample courses
        $courses = [
            ['Forklift Operator', 'Basic forklift operation training for beginners', '8 hours', 350, 10, 'active', '🏗️'],
            ['Forklift Refresher', 'Refresher course for experienced operators', '4 hours', 180, 10, 'active', '🏗️'],
            ['Class 2 Truck', 'Heavy vehicle license training', '16 hours', 750, 10, 'active', '🚛']
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO courses (name, description, duration, price, max_capacity, status, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($courses as $course) {
            $stmt->execute($course);
        }
        
        // Insert sample campuses
        $campuses = [
            ['Auckland', 'Auckland Campus, New Zealand'],
            ['Hamilton', 'Hamilton Campus, New Zealand'],
            ['Christchurch', 'Christchurch Campus, New Zealand']
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO campuses (name, address) VALUES (?, ?)");
        foreach ($campuses as $campus) {
            $stmt->execute($campus);
        }
        
        // Insert sample sessions
        $sessions = [
            [1, 1, '2024-11-01', 10, 5],
            [2, 2, '2025-10-01', 10, 9],
            [3, 3, '2025-10-01', 10, 1]
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO training_sessions (course_id, campus_id, session_date, max_capacity, enrolled_count) VALUES (?, ?, ?, ?, ?)");
        foreach ($sessions as $session) {
            $stmt->execute($session);
        }
    }
}

// Database operations class
class VisionDriveDatabase {
    private $db;
    
    public function __construct() {
        $this->db = DatabaseConfig::getInstance()->getConnection();
    }
    
    // Course operations
    public function getCourses() {
        $stmt = $this->db->query("SELECT course_id as id, course_name as name, description, duration_hours as duration, price, max_capacity, is_active as status FROM courses WHERE is_active = 1 ORDER BY course_name");
        $courses = $stmt->fetchAll();
        
        // Add formatted duration and image
        foreach ($courses as &$course) {
            $course['duration'] = $course['duration'] . ' hours';
            $course['image'] = $this->getCourseIcon($course['name']);
            $course['maxCapacity'] = $course['max_capacity'];
        }
        
        return $courses;
    }
    
    private function getCourseIcon($courseName) {
        if (stripos($courseName, 'forklift') !== false) return '🏗️';
        if (stripos($courseName, 'truck') !== false) return '🚛';
        if (stripos($courseName, 'safety') !== false) return '⚠️';
        return '📋';
    }
    
    public function getCourseById($id) {
        $stmt = $this->db->prepare("SELECT course_id as id, course_name as name, description, duration_hours as duration, price, max_capacity, is_active as status FROM courses WHERE course_id = ?");
        $stmt->execute([$id]);
        $course = $stmt->fetch();
        
        if ($course) {
            $course['duration'] = $course['duration'] . ' hours';
            $course['image'] = $this->getCourseIcon($course['name']);
            $course['maxCapacity'] = $course['max_capacity'];
        }
        
        return $course;
    }
    
    public function updateCourse($id, $data) {
        $fields = [];
        $values = [];
        
        if (isset($data['name'])) {
            $fields[] = "course_name = ?";
            $values[] = $data['name'];
        }
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $values[] = $data['description'];
        }
        if (isset($data['duration'])) {
            $duration = (int)filter_var($data['duration'], FILTER_SANITIZE_NUMBER_INT);
            $fields[] = "duration_hours = ?";
            $values[] = $duration;
        }
        if (isset($data['price'])) {
            $fields[] = "price = ?";
            $values[] = $data['price'];
        }
        if (isset($data['maxCapacity'])) {
            $fields[] = "max_capacity = ?";
            $values[] = $data['maxCapacity'];
        }
        
        $values[] = $id;
        
        $sql = "UPDATE courses SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE course_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    // Campus operations
    public function getCampuses() {
        $stmt = $this->db->query("SELECT campus_id as id, campus_name as name, address, city, region, phone, email FROM campuses WHERE is_active = 1 ORDER BY campus_name");
        return $stmt->fetchAll();
    }
    
    public function getCampusById($id) {
        $stmt = $this->db->prepare("SELECT campus_id as id, campus_name as name, address, city, region, phone, email FROM campuses WHERE campus_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Training session operations
    public function getTrainingSessions() {
        $sql = "SELECT ts.session_id as id, ts.course_id, ts.campus_id, ts.session_date as date, 
                       ts.start_time, ts.end_time, ts.max_participants as maxCapacity, 
                       ts.current_participants as enrolled, ts.status, ts.instructor_name,
                       c.course_name as courseName, cp.campus_name as campusName
                FROM training_sessions ts 
                JOIN courses c ON ts.course_id = c.course_id 
                JOIN campuses cp ON ts.campus_id = cp.campus_id 
                ORDER BY ts.session_date";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getTrainingSessionById($id) {
        $sql = "SELECT ts.session_id as id, ts.course_id, ts.campus_id, ts.session_date as date,
                       ts.start_time, ts.end_time, ts.max_participants as maxCapacity, 
                       ts.current_participants as enrolled, ts.status, ts.instructor_name,
                       c.course_name as courseName, cp.campus_name as campusName
                FROM training_sessions ts 
                JOIN courses c ON ts.course_id = c.course_id 
                JOIN campuses cp ON ts.campus_id = cp.campus_id 
                WHERE ts.session_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function updateTrainingSession($id, $data) {
        $fields = [];
        $values = [];
        
        if (isset($data['courseId'])) {
            $fields[] = "course_id = ?";
            $values[] = $data['courseId'];
        }
        if (isset($data['campusId'])) {
            $fields[] = "campus_id = ?";
            $values[] = $data['campusId'];
        }
        if (isset($data['date'])) {
            $fields[] = "session_date = ?";
            $values[] = $data['date'];
        }
        if (isset($data['maxCapacity'])) {
            $fields[] = "max_participants = ?";
            $values[] = $data['maxCapacity'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $values[] = $data['status'];
        }
        
        $values[] = $id;
        
        $sql = "UPDATE training_sessions SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE session_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    // Statistics
    public function getStatistics() {
        $stats = [];
        
        // Total students (users with role trainee)
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'trainee'");
        $stats['total_students'] = $stmt->fetchColumn();
        
        // Total courses
        $stmt = $this->db->query("SELECT COUNT(*) FROM courses WHERE is_active = 1");
        $stats['total_courses'] = $stmt->fetchColumn();
        
        // Total sessions
        $stmt = $this->db->query("SELECT COUNT(*) FROM training_sessions");
        $stats['total_sessions'] = $stmt->fetchColumn();
        
        // Pending documents
        $stmt = $this->db->query("SELECT COUNT(*) FROM documents WHERE status = 'pending'");
        $stats['pending_documents'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    // Document operations
    public function getDocuments() {
        $sql = "SELECT d.document_id as id, d.user_id as student_id, d.document_type, d.file_name, 
                       d.file_path, d.file_size, d.mime_type, d.status, d.uploaded_at, 
                       u.first_name, u.last_name,
                       CONCAT(u.first_name, ' ', u.last_name) as student_name
                FROM documents d 
                LEFT JOIN users u ON d.user_id = u.user_id 
                ORDER BY d.uploaded_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getDocumentStatistics() {
        $stats = [];
        
        // Total documents
        $stmt = $this->db->query("SELECT COUNT(*) FROM documents");
        $stats['total_documents'] = $stmt->fetchColumn();
        
        // Pending review
        $stmt = $this->db->query("SELECT COUNT(*) FROM documents WHERE status = 'pending'");
        $stats['pending_review'] = $stmt->fetchColumn();
        
        // Approved (verified)
        $stmt = $this->db->query("SELECT COUNT(*) FROM documents WHERE status = 'verified'");
        $stats['approved'] = $stmt->fetchColumn();
        
        // Storage used (approximate)
        $stmt = $this->db->query("SELECT SUM(file_size) FROM documents");
        $totalSize = $stmt->fetchColumn() ?: 0;
        $stats['storage_used'] = round($totalSize / (1024 * 1024), 1) . ' MB';
        
        return $stats;
    }
    
    public function updateDocumentStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE documents SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE document_id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    // Booking operations
    public function getBookings() {
        $sql = "SELECT b.booking_id as id, b.user_id, b.session_id, b.booking_date,
                       b.status, b.payment_status, b.confirmation_code,
                       u.first_name, u.last_name, u.email,
                       CONCAT(u.first_name, ' ', u.last_name) as student_name,
                       ts.session_date, c.course_name, cp.campus_name
                FROM bookings b
                JOIN users u ON b.user_id = u.user_id
                JOIN training_sessions ts ON b.session_id = ts.session_id
                JOIN courses c ON ts.course_id = c.course_id
                JOIN campuses cp ON ts.campus_id = cp.campus_id
                ORDER BY b.booking_date DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    public function createBooking($data) {
        try {
            $this->db->beginTransaction();
            
            // First check if user exists, if not create one
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Create new user
                $stmt = $this->db->prepare("INSERT INTO users (email, first_name, last_name, phone, role) VALUES (?, ?, ?, ?, 'student')");
                $nameParts = explode(' ', $data['student_name'], 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';
                $stmt->execute([$data['email'], $firstName, $lastName, $data['phone']]);
                $userId = $this->db->lastInsertId();
            } else {
                $userId = $user['user_id'];
            }
            
            // Find or create training session
            $stmt = $this->db->prepare("SELECT session_id FROM training_sessions WHERE course_id = ? AND campus_id = ? AND session_date = ?");
            $stmt->execute([$data['course_id'], $data['campus_id'], $data['training_date']]);
            $session = $stmt->fetch();
            
            if (!$session) {
                // Create new training session
                $stmt = $this->db->prepare("INSERT INTO training_sessions (course_id, campus_id, session_date, max_participants, status) VALUES (?, ?, ?, 20, 'scheduled')");
                $stmt->execute([$data['course_id'], $data['campus_id'], $data['training_date']]);
                $sessionId = $this->db->lastInsertId();
            } else {
                $sessionId = $session['session_id'];
            }
            
            // Create booking
            $confirmationCode = 'VD' . strtoupper(uniqid());
            $stmt = $this->db->prepare("INSERT INTO bookings (user_id, session_id, status, payment_status, confirmation_code, notes) VALUES (?, ?, 'confirmed', 'unpaid', ?, ?)");
            $stmt->execute([$userId, $sessionId, $confirmationCode, $data['special_requirements'] ?? '']);
            $bookingId = $this->db->lastInsertId();
            
            $this->db->commit();
            return ['booking_id' => $bookingId, 'user_id' => $userId];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function saveDocument($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO documents (user_id, document_type, file_name, file_path, file_size, mime_type, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            
            $fileSize = file_exists($data['file_path']) ? filesize($data['file_path']) : 0;
            $mimeType = mime_content_type($data['file_path']);
            
            return $stmt->execute([
                $data['user_id'],
                $data['document_type'],
                $data['original_name'], 
                $data['file_path'],
                $fileSize,
                $mimeType
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function getTotalStudents() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getTotalBookings() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM bookings");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getStudents() {
        try {
            $stmt = $this->db->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function addCourse($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO courses (name, description, duration, price) VALUES (?, ?, ?, ?)");
            return $stmt->execute([
                $data['name'],
                $data['description'], 
                $data['duration'],
                $data['price']
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getAllBookings() {
        try {
            $stmt = $this->db->query("
                SELECT b.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as student_name,
                       c.name as course_name,
                       ca.name as campus_name
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.user_id 
                LEFT JOIN training_sessions ts ON b.session_id = ts.session_id
                LEFT JOIN courses c ON ts.course_id = c.id 
                LEFT JOIN campuses ca ON ts.campus_id = ca.id 
                ORDER BY b.booking_date DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>