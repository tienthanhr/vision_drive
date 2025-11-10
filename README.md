# Vision Drive - Training Booking System

## Project Description
Vision Drive is a driving school training booking system designed according to your mockup. The system includes:

- **User Interface**: Interface for students to book courses
- **Admin Interface**: Administrative interface for admins
- **Database System**: MySQL database system

## Project Structure

```
VISION DRIVE/
├── index.php               # Homepage (User Interface)
├── booking.php             # Course booking page
├── admin-login.php         # Admin login page
├── admin-dashboard.php     # Admin dashboard
├── css/
│   └── styles.css          # Common CSS file
├── js/
│   └── database.js         # Database system
└── config/
    └── database.php        # Database connection
```

## Main Features

### User Interface (Student interface)
1. **Homepage** (`index.php`)
   - Display available courses
   - Detailed information for each course
   - Book course button

2. **Course booking page** (`booking.php`)
   - 3-step registration form:
     - Step 1: Select course and campus
     - Step 2: Enter personal information
     - Step 3: Booking confirmation
   - Upload identity documents
   - Automatic confirmation code generation

### Admin Interface (Administrative interface)
1. **Login page** (`admin-login.php`)
   - Admin login form

2. **Dashboard** (`admin-dashboard.php`)
   - Overview statistics
   - Manage training courses
   - Manage training sessions
   - Search and filter data
   - Add/edit/delete courses

## How to Use

### Launch the system
1. Open file `index.php` in web browser
2. System will automatically load sample data

### Book a course (User)
1. Go to homepage (`index.php`)
2. Select desired course
3. Fill in information in 3 steps
4. Receive confirmation code

### System administration (Admin)
1. Go to `/admin-login.php`
2. Login with:
   - Username: `admin`
   - Password: `password123`
3. Manage data from dashboard

## Admin Login Information

| Username | Password | Role |
|----------|----------|-------|
| admin    | password123 | Administrator |
| staff    | staff123    | Staff |

## Sample Data

### Available courses:
1. **Forklift Operator** - 8 hours - $350
2. **Forklift Refresher** - 4 hours - $180  
3. **Class 2 Truck** - 16 hours - $750

### Available campuses:
1. Auckland
2. Hamilton  
3. Christchurch

## Database Features

### Data storage
- Uses MySQL to store data
- Automatic save when changes occur
- Data restoration when page reloads

### Data management
- CRUD operations for all entities
- Search and filter
- Realtime statistics
- Export/Import data

## API Functions (JavaScript)

### Database Operations
```javascript
// Get courses list
window.visionDB.getCourses()

// Add new course
window.visionDB.addCourse(courseData)

// Create new booking
window.visionDB.createBooking(bookingData)

// Get statistics
window.visionDB.getStatistics()
```

### Utility Functions
```javascript
// Format currency
VisionDriveUtils.formatCurrency(350) // "$350.00"

// Format date
VisionDriveUtils.formatDate("2024-11-01") // "1 Nov 2024"

// Validate email
VisionDriveUtils.validateEmail("test@email.com") // true/false

// Show notification
VisionDriveUtils.showNotification("Success!", "success")
```

## Responsive Design
- Supports desktop, tablet, mobile
- Breakpoints: 1200px, 768px, 480px
- Touch-friendly buttons and forms

## Browser Support
- Chrome (latest)
- Firefox (latest)  
- Safari (latest)
- Edge (latest)

## Customization

### Change colors
Edit CSS variables in `/css/styles.css`:
```css
:root {
    --primary-blue: #00bcd4;
    --secondary-blue: #4fc3f7;
    --dark-gray: #424242;
    /* ... */
}
```

### Add new course
```javascript
window.visionDB.addCourse({
    name: "Course name",
    description: "Description",
    duration: "X hours",
    price: 999,
    maxCapacity: 10,
    image: "🚗"
});
```

## Connect to real Database
To connect with real database (MySQL, PostgreSQL, etc.):
1. Replace LocalStorage with API calls
2. Update `/js/database.js`
3. Add server-side authentication
4. Implement file upload for documents

## Troubleshooting

### Common issues:
1. **Data lost**: Check browser LocalStorage
2. **Admin login fails**: Clear sessionStorage and retry
3. **Form not submitting**: Check JavaScript validation

### Reset data:
```javascript
// Delete all data
localStorage.removeItem('visionDriveDB');
window.location.reload();
```

## Contact & Support
- Email: enquiries@visiondrive.nz
- Phone: 0800 837 484
- Address: 21 Ruakura Road, Hamilton East, 3216

## License
Copyright © 2024 Vision Drive. All rights reserved.