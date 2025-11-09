<?php
// Database configuration for Vision Drive
class DatabaseConfig {
    const DB_HOST = 'localhost';
    const DB_NAME = 'vision_drive';
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
            try {
                $this->pdo = new PDO("sqlite:vision_drive.db");
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e2) {
                die("Database connection failed: " . $e2->getMessage());
            }
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}

class VisionDriveDatabase {
    private $db;
    
    public function __construct() {
        $config = DatabaseConfig::getInstance();
        $this->db = $config->getConnection();
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    public function getCourses($status = 'active') {
        try {
            $where = '';
            if ($status === 'active') { $where = 'WHERE is_active = 1'; }
            elseif ($status === 'inactive') { $where = 'WHERE is_active = 0'; }
            $stmt = $this->db->query("SELECT 
                course_id as id, 
                course_name as name, 
                description, 
                CONCAT(duration_hours, ' hours') as duration,
                price,
                max_capacity,
                CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as status
                FROM courses 
                $where
                ORDER BY course_name");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [
                ['id' => 1, 'name' => 'Forklift Operator', 'description' => 'Basic forklift training', 'duration' => '8 hours', 'price' => 350],
                ['id' => 2, 'name' => 'Forklift Refresher', 'description' => 'Refresher course', 'duration' => '4 hours', 'price' => 180],
                ['id' => 3, 'name' => 'Class 2 Truck', 'description' => 'Heavy vehicle training', 'duration' => '16 hours', 'price' => 750]
            ];
        }
    }
    
    public function getCampuses($status = 'active') {
        try {
            $where = '';
            if ($status === 'active') { $where = 'WHERE is_active = 1'; }
            elseif ($status === 'inactive') { $where = 'WHERE is_active = 0'; }
            $stmt = $this->db->query("SELECT 
                campus_id as id, 
                campus_name as name, 
                CONCAT(city, ', ', region) as location,
                address,
                phone,
                email,
                CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as status
                FROM campuses 
                $where
                ORDER BY campus_name");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [
                ['id' => 1, 'name' => 'Auckland', 'location' => 'Auckland, New Zealand'],
                ['id' => 2, 'name' => 'Hamilton', 'location' => 'Hamilton, New Zealand'],
                ['id' => 3, 'name' => 'Christchurch', 'location' => 'Christchurch, New Zealand']
            ];
        }
    }
    
    public function createBooking($data) {
        try {
            $this->db->beginTransaction();
            
            // Check if user exists, if not create one
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Create new user - provide all required fields
                $stmt = $this->db->prepare("INSERT INTO users (email, password_hash, first_name, last_name, phone, region, role) VALUES (?, ?, ?, ?, ?, ?, 'trainee')");
                $nameParts = explode(' ', $data['student_name'], 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';
                $defaultPassword = password_hash('temppass123', PASSWORD_DEFAULT); // Temporary password
                $defaultRegion = 'New Zealand'; // Default region
                $stmt->execute([$data['email'], $defaultPassword, $firstName, $lastName, $data['phone'], $defaultRegion]);
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
            $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'trainee' AND status = 'active'");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getTotalBookings() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM bookings WHERE booking_date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    public function getStats() {
        try {
            $stats = [];
            
            // Count active courses
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM courses WHERE is_active = 1");
            $stats['courses'] = $stmt->fetch()['count'];
            
            // Count active users (trainees)
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainee' AND status = 'active'");
            $stats['users'] = $stmt->fetch()['count'];
            
            // Count scheduled sessions
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM training_sessions WHERE status = 'scheduled'");
            $stats['active_sessions'] = $stmt->fetch()['count'];
            
            // Count this month's bookings
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM bookings WHERE booking_date >= DATE_FORMAT(NOW(), '%Y-%m-01')");
            $stats['bookings'] = $stmt->fetch()['count'];
            
            return $stats;
        } catch (Exception $e) {
            return [
                'courses' => 0,
                'users' => 0, 
                'active_sessions' => 0,
                'bookings' => 0
            ];
        }
    }
    
    public function getTrainingSessions() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    ts.session_id,
                    c.course_name,
                    cam.campus_name,
                    ts.session_date,
                    ts.start_time,
                    ts.end_time,
                    ts.current_participants as enrolled_count,
                    ts.max_participants,
                    ts.instructor_name,
                    ts.status
                FROM training_sessions ts
                JOIN courses c ON ts.course_id = c.course_id  
                JOIN campuses cam ON ts.campus_id = cam.campus_id
                WHERE ts.status IN ('scheduled', 'ongoing')
                ORDER BY ts.session_date ASC, ts.start_time ASC
                LIMIT 5
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [
                [
                    'course_name' => 'Forklift Operator',
                    'campus_name' => 'Auckland Central', 
                    'session_date' => date('Y-m-d', strtotime('+1 day')),
                    'start_time' => '09:00:00',
                    'enrolled_count' => 5,
                    'max_participants' => 10,
                    'status' => 'scheduled'
                ]
            ];
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
            // Extract hours from duration string (e.g., "8 hours" -> 8)
            $durationHours = intval($data['duration']);
            $isActive = ($data['status'] ?? 'active') === 'active' ? 1 : 0;
            
            $stmt = $this->db->prepare("INSERT INTO courses (course_name, description, duration_hours, price, max_capacity, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['name'],
                $data['description'], 
                $durationHours,
                $data['price'],
                $data['max_capacity'] ?? 10,
                $isActive
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
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
    
    public function authenticateAdmin($username, $password) {
        try {
            // Check if admin exists in users table
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password_hash'])) {
                return $admin;
            }
            
            return false;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function createDefaultAdmin($username, $password) {
        try {
            // Check if admin already exists
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$username]);
            
            if (!$stmt->fetch()) {
                // Create default admin
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("INSERT INTO users (email, first_name, last_name, password_hash, role) VALUES (?, 'Admin', 'User', ?, 'admin')");
                $stmt->execute([$username, $hashedPassword]);
                return $this->db->lastInsertId();
            }
        } catch (Exception $e) {
            // Ignore errors for fallback
            return false;
        }
    }
    
    // Course Management Methods
    public function getCourseById($id) {
        try {
            $stmt = $this->db->prepare("SELECT 
                course_id as id, 
                course_name as name, 
                description, 
                CONCAT(duration_hours, ' hours') as duration,
                duration_hours,
                price,
                max_capacity,
                CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as status
                FROM courses 
                WHERE course_id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function updateCourse($id, $data) {
        try {
            // Extract hours from duration string (e.g., "8 hours" -> 8)
            $durationHours = intval($data['duration']);
            $isActive = ($data['status'] ?? 'active') === 'active' ? 1 : 0;
            
            $stmt = $this->db->prepare("UPDATE courses SET course_name = ?, description = ?, duration_hours = ?, price = ?, max_capacity = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE course_id = ?");
            return $stmt->execute([
                $data['name'],
                $data['description'],
                $durationHours,
                $data['price'],
                $data['max_capacity'] ?? 10,
                $isActive,
                $id
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function deleteCourse($id) {
        try {
            // Check if course is used in any training sessions that have bookings
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM bookings b
                INNER JOIN training_sessions ts ON b.session_id = ts.session_id
                WHERE ts.course_id = ?
            ");
            $stmt->execute([$id]);
            $bookingCount = $stmt->fetchColumn();
            
            if ($bookingCount > 0) {
                // Don't delete, just set as inactive
                $stmt = $this->db->prepare("UPDATE courses SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE course_id = ?");
                return $stmt->execute([$id]);
            } else {
                // Check if course has any training sessions (even without bookings)
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM training_sessions WHERE course_id = ?");
                $stmt->execute([$id]);
                $sessionCount = $stmt->fetchColumn();
                
                if ($sessionCount > 0) {
                    // Has sessions but no bookings - set as inactive
                    $stmt = $this->db->prepare("UPDATE courses SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE course_id = ?");
                    return $stmt->execute([$id]);
                } else {
                    // No sessions at all - safe to delete
                    $stmt = $this->db->prepare("DELETE FROM courses WHERE course_id = ?");
                    return $stmt->execute([$id]);
                }
            }
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Campus Management Methods
    public function getCampusById($id) {
        try {
            $stmt = $this->db->prepare("SELECT 
                campus_id as id, 
                campus_name as name, 
                CONCAT(city, ', ', region) as location,
                address,
                phone,
                email,
                city,
                region,
                CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END as status
                FROM campuses 
                WHERE campus_id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function addCampus($data) {
        try {
            $isActive = ($data['status'] ?? 'active') === 'active' ? 1 : 0;
            
            // Parse location to get city and region
            $locationParts = explode(',', $data['location']);
            $city = trim($locationParts[0] ?? $data['name']);
            $region = trim($locationParts[1] ?? 'New Zealand');
            
            $stmt = $this->db->prepare("INSERT INTO campuses (campus_name, address, city, region, phone, email, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $data['name'],
                $data['address'] ?? '',
                $city,
                $region,
                $data['phone'] ?? '',
                strtolower(str_replace(' ', '', $data['name'])) . '@visiondrive.com',
                $isActive
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function updateCampus($id, $data) {
        try {
            $stmt = $this->db->prepare("UPDATE campuses SET name = ?, location = ?, address = ?, phone = ?, status = ? WHERE id = ?");
            return $stmt->execute([
                $data['name'],
                $data['location'],
                $data['address'] ?? '',
                $data['phone'] ?? '',
                $data['status'] ?? 'active',
                $id
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function deleteCampus($id) {
        try {
            // Check if campus is used in any schedules
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM training_sessions WHERE campus_id = ?");
            $stmt->execute([$id]);
            $sessionCount = $stmt->fetchColumn();
            
            if ($sessionCount > 0) {
                // Don't delete, just set as inactive
                $stmt = $this->db->prepare("UPDATE campuses SET status = 'inactive' WHERE id = ?");
                return $stmt->execute([$id]);
            } else {
                // Safe to delete
                $stmt = $this->db->prepare("DELETE FROM campuses WHERE id = ?");
                return $stmt->execute([$id]);
            }
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Document Management Methods
    public function getStudentDocuments($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT document_id, document_type, file_name, file_path, file_size, uploaded_at
                FROM documents 
                WHERE user_id = ? 
                ORDER BY uploaded_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function uploadDocument($userId, $documentType, $originalName, $filePath, $fileSize, $mimeType) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO documents (user_id, document_type, original_name, file_path, file_size, mime_type, uploaded_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$userId, $documentType, $originalName, $filePath, $fileSize, $mimeType]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function deleteDocument($documentId) {
        try {
            // Get file path first
            $stmt = $this->db->prepare("SELECT file_path FROM documents WHERE document_id = ?");
            $stmt->execute([$documentId]);
            $document = $stmt->fetch();
            
            if ($document && file_exists($document['file_path'])) {
                unlink($document['file_path']); // Delete physical file
            }
            
            // Delete database record
            $stmt = $this->db->prepare("DELETE FROM documents WHERE document_id = ?");
            return $stmt->execute([$documentId]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getStudentsWithDocuments() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    u.user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name,
                    u.email,
                    u.phone,
                    u.region,
                    u.status,
                    u.created_at,
                    u.last_login,
                    COUNT(d.document_id) as document_count
                FROM users u
                LEFT JOIN documents d ON u.user_id = d.user_id
                WHERE u.role = 'trainee'
                GROUP BY u.user_id
                ORDER BY u.created_at DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getAvailableSchedules($courseId = 0, $campusId = 0, $startDate = null, $endDate = null) {
        try {
            $params = [];
            $where_conditions = [];
            
            // Base query
            $sql = "SELECT 
                        ts.session_id,
                        ts.course_id,
                        c.course_name,
                        ts.campus_id,
                        ca.campus_name,
                        CONCAT(ca.city, ', ', ca.region) as campus_location,
                        ts.session_date,
                        ts.session_time, 
                        CONCAT(c.duration_hours, ' hours') as duration,
                        ts.instructor,
                        ts.max_capacity,
                        COALESCE(enrolled.count, 0) as enrolled_count,
                        (ts.max_capacity - COALESCE(enrolled.count, 0)) as available_spots,
                        CONCAT('$', c.price) as price,
                        ts.status
                    FROM training_sessions ts
                    JOIN courses c ON ts.course_id = c.course_id
                    JOIN campuses ca ON ts.campus_id = ca.campus_id
                    LEFT JOIN (
                        SELECT session_id, COUNT(*) as count 
                        FROM enrollments 
                        WHERE status IN ('enrolled', 'confirmed')
                        GROUP BY session_id
                    ) enrolled ON ts.session_id = enrolled.session_id";
            
            // Add where conditions
            $where_conditions[] = "ts.status = 'scheduled'";
            $where_conditions[] = "ts.session_date >= CURDATE()";
            
            if ($courseId > 0) {
                $where_conditions[] = "ts.course_id = ?";
                $params[] = $courseId;
            }
            
            if ($campusId > 0) {
                $where_conditions[] = "ts.campus_id = ?";
                $params[] = $campusId;
            }
            
            if ($startDate) {
                $where_conditions[] = "ts.session_date >= ?";
                $params[] = $startDate;
            }
            
            if ($endDate) {
                $where_conditions[] = "ts.session_date <= ?";
                $params[] = $endDate;
            }
            
            if (!empty($where_conditions)) {
                $sql .= " WHERE " . implode(" AND ", $where_conditions);
            }
            
            $sql .= " HAVING available_spots > 0 ORDER BY ts.session_date, ts.session_time";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Database error in getAvailableSchedules: " . $e->getMessage());
            return [];
        }
    }
    
    public function createBookingWithSchedule($bookingData) {
        try {
            $this->db->beginTransaction();
            
            // Check if schedule is still available
            $stmt = $this->db->prepare("SELECT 
                ts.max_capacity,
                COALESCE(enrolled.count, 0) as enrolled_count,
                (ts.max_capacity - COALESCE(enrolled.count, 0)) as available_spots
                FROM training_sessions ts
                LEFT JOIN (
                    SELECT session_id, COUNT(*) as count 
                    FROM enrollments 
                    WHERE status IN ('enrolled', 'confirmed')
                    GROUP BY session_id
                ) enrolled ON ts.session_id = enrolled.session_id
                WHERE ts.session_id = ? AND ts.status = 'scheduled'");
            $stmt->execute([$bookingData['schedule_id']]);
            $scheduleInfo = $stmt->fetch();
            
            if (!$scheduleInfo || $scheduleInfo['available_spots'] <= 0) {
                $this->db->rollBack();
                return false;
            }
            
            // Check if user exists
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$bookingData['email']]);
            $user = $stmt->fetch();
            
            if ($user) {
                $userId = $user['user_id'];
            } else {
                // Create new user
                $stmt = $this->db->prepare("
                    INSERT INTO users (first_name, last_name, email, phone, role, status, created_at) 
                    VALUES (?, '', ?, ?, 'trainee', 'active', NOW())
                ");
                $stmt->execute([
                    $bookingData['student_name'],
                    $bookingData['email'],
                    $bookingData['phone']
                ]);
                $userId = $this->db->lastInsertId();
            }
            
            // Create enrollment record
            $stmt = $this->db->prepare("
                INSERT INTO enrollments (user_id, session_id, enrollment_date, status, special_requirements, created_at)
                VALUES (?, ?, NOW(), 'enrolled', ?, NOW())
            ");
            $stmt->execute([
                $userId, 
                $bookingData['schedule_id'],
                $bookingData['special_requirements'] ?? ''
            ]);
            $enrollmentId = $this->db->lastInsertId();
            
            $this->db->commit();
            
            return [
                'booking_id' => $enrollmentId,
                'user_id' => $userId,
                'success' => true
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Database error in createBookingWithSchedule: " . $e->getMessage());
            return false;
        }
    }
}
?>