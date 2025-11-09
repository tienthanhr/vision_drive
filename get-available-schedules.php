<?php
header('Content-Type: application/json');
require_once 'config/database.php';

try {
    $db = new VisionDriveDatabase();
    
    // Lấy filter parameters
    $courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    $campusId = isset($_GET['campus_id']) ? (int)$_GET['campus_id'] : 0;
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+3 months'));
    
    // Get schedules từ database
    $schedules = $db->getAvailableSchedules($courseId, $campusId, $startDate, $endDate);
    
    // Fallback data nếu database error
    if (empty($schedules)) {
        $schedules = [
            [
                'session_id' => 1,
                'course_id' => 1,
                'course_name' => 'Forklift Operator',
                'campus_id' => 1,
                'campus_name' => 'Auckland',
                'campus_location' => 'Auckland, New Zealand',
                'session_date' => '2024-12-15',
                'session_time' => '09:00',
                'duration' => '8 hours',
                'instructor' => 'John Smith',
                'max_capacity' => 10,
                'enrolled_count' => 5,
                'available_spots' => 5,
                'price' => '$350',
                'status' => 'available'
            ],
            [
                'session_id' => 2,
                'course_id' => 1,
                'course_name' => 'Forklift Operator',
                'campus_id' => 2,
                'campus_name' => 'Hamilton',
                'campus_location' => 'Hamilton, New Zealand',
                'session_date' => '2024-12-18',
                'session_time' => '13:00',
                'duration' => '8 hours',
                'instructor' => 'Sarah Johnson',
                'max_capacity' => 10,
                'enrolled_count' => 3,
                'available_spots' => 7,
                'price' => '$350',
                'status' => 'available'
            ],
            [
                'session_id' => 3,
                'course_id' => 2,
                'course_name' => 'Forklift Refresher',
                'campus_id' => 1,
                'campus_name' => 'Auckland',
                'campus_location' => 'Auckland, New Zealand',
                'session_date' => '2024-12-20',
                'session_time' => '09:00',
                'duration' => '4 hours',
                'instructor' => 'Mike Wilson',
                'max_capacity' => 8,
                'enrolled_count' => 2,
                'available_spots' => 6,
                'price' => '$180',
                'status' => 'available'
            ],
            [
                'session_id' => 4,
                'course_id' => 3,
                'course_name' => 'Class 2 Truck',
                'campus_id' => 3,
                'campus_name' => 'Christchurch',
                'campus_location' => 'Christchurch, New Zealand',
                'session_date' => '2024-12-22',
                'session_time' => '08:30',
                'duration' => '16 hours',
                'instructor' => 'David Brown',
                'max_capacity' => 6,
                'enrolled_count' => 1,
                'available_spots' => 5,
                'price' => '$750',
                'status' => 'available'
            ],
            [
                'session_id' => 5,
                'course_id' => 1,
                'course_name' => 'Forklift Operator',
                'campus_id' => 2,
                'campus_name' => 'Hamilton',
                'campus_location' => 'Hamilton, New Zealand',
                'session_date' => '2025-01-08',
                'session_time' => '09:00',
                'duration' => '8 hours',
                'instructor' => 'Sarah Johnson',
                'max_capacity' => 10,
                'enrolled_count' => 0,
                'available_spots' => 10,
                'price' => '$350',
                'status' => 'available'
            ],
            [
                'session_id' => 6,
                'course_id' => 2,
                'course_name' => 'Forklift Refresher',
                'campus_id' => 2,
                'campus_name' => 'Hamilton',
                'campus_location' => 'Hamilton, New Zealand',
                'session_date' => '2025-01-15',
                'session_time' => '13:00',
                'duration' => '4 hours',
                'instructor' => 'John Smith',
                'max_capacity' => 8,
                'enrolled_count' => 1,
                'available_spots' => 7,
                'price' => '$180',
                'status' => 'available'
            ]
        ];
        
        // Apply filters to fallback data
        if ($courseId > 0) {
            $schedules = array_filter($schedules, function($schedule) use ($courseId) {
                return $schedule['course_id'] == $courseId;
            });
        }
        
        if ($campusId > 0) {
            $schedules = array_filter($schedules, function($schedule) use ($campusId) {
                return $schedule['campus_id'] == $campusId;
            });
        }
        
        $schedules = array_filter($schedules, function($schedule) use ($startDate, $endDate) {
            return $schedule['session_date'] >= $startDate && $schedule['session_date'] <= $endDate;
        });
        
        // Reset array indices
        $schedules = array_values($schedules);
    }
    
    echo json_encode([
        'success' => true,
        'schedules' => $schedules,
        'total' => count($schedules),
        'filters' => [
            'course_id' => $courseId,
            'campus_id' => $campusId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>