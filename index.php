<?php
// Lấy dữ liệu từ database đơn giản
require_once 'config/database.php';

try {
    $db = new VisionDriveDatabase();
    $courses = $db->getCourses();
    $campuses = $db->getCampuses();
} catch (Exception $e) {
    // Fallback data nếu database lỗi
    $courses = [
        ['id' => 1, 'name' => 'Forklift Operator', 'description' => 'Basic forklift operation training', 'duration' => '8 hours', 'price' => 350],
        ['id' => 2, 'name' => 'Forklift Refresher', 'description' => 'Refresher course for experienced operators', 'duration' => '4 hours', 'price' => 180],
        ['id' => 3, 'name' => 'Class 2 Truck', 'description' => 'Heavy vehicle training course', 'duration' => '16 hours', 'price' => 750]
    ];
    
    $campuses = [
        ['id' => 1, 'name' => 'Auckland', 'location' => 'Auckland, New Zealand'],
        ['id' => 2, 'name' => 'Hamilton', 'location' => 'Hamilton, New Zealand'],  
        ['id' => 3, 'name' => 'Christchurch', 'location' => 'Christchurch, New Zealand']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision Drive - Book Your Training Today</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="includes/user-styles.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
        }

        /* Vision Drive Brand Colors */
        :root {
            --primary-blue: #00bcd4;
            --secondary-blue: #4fc3f7;
            --dark-gray: #424242;
            --light-gray: #f5f5f5;
            --text-dark: #2c2c2c;
            --text-light: #666;
            --white: #ffffff;
        }

        /* Header */
        .header {
            background: white;
            padding: 15px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .logo-subtitle {
            font-size: 18px;
            font-weight: 400;
            color: var(--dark-gray);
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: var(--primary-blue);
        }

        .book-now-btn {
            background: var(--primary-blue);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .book-now-btn:hover {
            background: var(--secondary-blue);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 120px 40px 80px; /* Add top padding for fixed header */
            text-align: center;
            position: relative;
        }

        .hero-content h1 {
            font-size: 48px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 20px;
        }

        .hero-content .subtitle {
            font-size: 32px;
            font-weight: 400;
            color: var(--text-dark);
            margin-bottom: 40px;
        }

        .individual-booking-btn {
            background: var(--primary-blue);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .individual-booking-btn:hover {
            background: var(--secondary-blue);
            transform: translateY(-2px);
        }

        .hero-vehicles {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
        }

        .vehicle-icon {
            width: 120px;
            height: 120px;
            background: #ddd;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
        }

        /* Course Cards */
        .courses-section {
            padding: 60px 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .course-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .course-card:hover {
            transform: translateY(-5px);
        }

        .course-image {
            width: 100px;
            height: 100px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .course-image img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .course-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .course-description {
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .course-duration, .course-price {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-light);
            font-weight: 500;
        }

        .course-price {
            color: var(--primary-blue);
            font-weight: 600;
            font-size: 18px;
        }

        /* Footer */
        .footer {
            background: var(--primary-blue);
            color: white;
            padding: 40px;
            margin-top: 60px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo {
            font-size: 20px;
            font-weight: 700;
        }

        .footer-info {
            display: flex;
            gap: 40px;
            align-items: center;
        }

        .footer-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .nav-menu {
                gap: 20px;
            }

            .hero {
                padding: 40px 20px;
            }

            .hero-content h1 {
                font-size: 32px;
            }

            .hero-content .subtitle {
                font-size: 24px;
            }

            .courses-section {
                padding: 40px 20px;
            }

            .courses-grid {
                grid-template-columns: 1fr;
            }

            .footer-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            .footer-info {
                flex-direction: column;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-vehicles">
            <div class="vehicle-icon">🚚</div>
            <div class="hero-content">
                <h1>Welcome to Vision Drive</h1>
                <div class="subtitle">Book Your Training Today!</div>
                <button class="individual-booking-btn" onclick="window.location.href='booking.php'">Individual booking</button>
            </div>
            <div class="vehicle-icon">🚛</div>
        </div>
    </section>

    <!-- Courses Section -->
    <section class="courses-section">
        <div class="courses-grid">
            <?php foreach ($courses as $course): 
                // Determine image based on course name
                $courseImage = 'forklift.png'; // default
                if (stripos($course['name'], 'truck') !== false) {
                    $courseImage = 'truck.png';
                } elseif (stripos($course['name'], 'forklift') !== false) {
                    $courseImage = 'forklift.png';
                }
            ?>
                <div class="course-card" onclick="window.location.href='booking.php?courseId=<?= $course['id'] ?>'">
                    <div class="course-image">
                        <img src="images/<?= $courseImage ?>" alt="<?= htmlspecialchars($course['name']) ?>">
                    </div>
                    <div class="course-title"><?= htmlspecialchars($course['name']) ?></div>
                    <div class="course-description"><?= htmlspecialchars($course['description']) ?></div>
                    <div class="course-meta">
                        <div class="course-duration">
                            <span>⏱️</span>
                            <span><?= htmlspecialchars($course['duration']) ?></span>
                        </div>
                        <div class="course-price">
                            <span>💰</span>
                            <span>$<?= number_format($course['price']) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>