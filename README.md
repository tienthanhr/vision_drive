# Vision Drive - Training Booking System

## Mô tả dự án
Vision Drive là hệ thống đặt chỗ khóa học đào tạo lái xe được thiết kế theo hình ảnh mockup của bạn. Hệ thống bao gồm:

- **User Interface**: Giao diện cho học viên đặt khóa học
- **Admin Interface**: Giao diện quản trị cho admin
- **Database System**: Hệ thống cơ sở dữ liệu LocalStorage

## Cấu trúc dự án

```
VISION DRIVE/
├── index.html              # Trang chủ (User Interface)
├── booking.html            # Trang đặt khóa học  
├── admin-login.html        # Trang đăng nhập admin
├── admin-dashboard.html    # Dashboard admin
├── css/
│   └── styles.css          # File CSS chung
├── js/
│   └── database.js         # Hệ thống database
└── VisionDrive_Complete_Database.xlsx  # Database Excel gốc
```

## Tính năng chính

### User Interface (Giao diện học viên)
1. **Trang chủ** (`index.html`)
   - Hiển thị các khóa học có sẵn
   - Thông tin chi tiết từng khóa học
   - Nút đặt khóa học

2. **Trang đặt khóa học** (`booking.html`)
   - Form đăng ký 3 bước:
     - Bước 1: Chọn khóa học và campus
     - Bước 2: Nhập thông tin cá nhân
     - Bước 3: Xác nhận đặt chỗ thành công
   - Upload file giấy tờ tùy thân
   - Tạo mã xác nhận tự động

### Admin Interface (Giao diện quản trị)
1. **Trang đăng nhập** (`admin-login.html`)
   - Form đăng nhập admin

2. **Dashboard** (`admin-dashboard.html`)
   - Thống kê tổng quan
   - Quản lý danh sách khóa học đào tạo
   - Quản lý các buổi đào tạo
   - Tìm kiếm và lọc dữ liệu
   - Thêm/sửa/xóa khóa học

## Cách sử dụng

### Khởi chạy hệ thống
1. Mở file `index.html` trong trình duyệt web
2. Hệ thống sẽ tự động load dữ liệu mẫu

### Đặt khóa học (User)
1. Vào trang chủ (`index.html`)
2. Chọn khóa học muốn đăng ký
3. Điền thông tin theo 3 bước
4. Nhận mã xác nhận

### Quản trị hệ thống (Admin)
1. Vào `/admin-login.html`
2. Đăng nhập với:
   - Username: `admin`
   - Password: `password123`
3. Quản lý dữ liệu từ dashboard

## Thông tin đăng nhập Admin

| Username | Password | Quyền |
|----------|----------|-------|
| admin    | password123 | Administrator |
| staff    | staff123    | Staff |

## Dữ liệu mẫu

### Khóa học có sẵn:
1. **Forklift Operator** - 8 giờ - $350
2. **Forklift Refresher** - 4 giờ - $180  
3. **Class 2 Truck** - 16 giờ - $750

### Campus có sẵn:
1. Auckland
2. Hamilton  
3. Christchurch

## Tính năng Database

### Lưu trữ dữ liệu
- Sử dụng LocalStorage để lưu trữ dữ liệu
- Tự động save khi có thay đổi
- Khôi phục dữ liệu khi reload trang

### Quản lý dữ liệu
- CRUD operations cho tất cả entities
- Tìm kiếm và lọc
- Thống kê realtime
- Export/Import dữ liệu

## API Functions (JavaScript)

### Database Operations
```javascript
// Lấy danh sách khóa học
window.visionDB.getCourses()

// Thêm khóa học mới
window.visionDB.addCourse(courseData)

// Tạo booking mới
window.visionDB.createBooking(bookingData)

// Lấy thống kê
window.visionDB.getStatistics()
```

### Utility Functions
```javascript
// Format tiền tệ
VisionDriveUtils.formatCurrency(350) // "$350.00"

// Format ngày
VisionDriveUtils.formatDate("2024-11-01") // "1 Nov 2024"

// Validate email
VisionDriveUtils.validateEmail("test@email.com") // true/false

// Hiển thị thông báo
VisionDriveUtils.showNotification("Success!", "success")
```

## Responsive Design
- Hỗ trợ desktop, tablet, mobile
- Breakpoints: 1200px, 768px, 480px
- Touch-friendly buttons và forms

## Browser Support
- Chrome (latest)
- Firefox (latest)  
- Safari (latest)
- Edge (latest)

## Customization

### Thay đổi màu sắc
Chỉnh sửa CSS variables trong `/css/styles.css`:
```css
:root {
    --primary-blue: #00bcd4;
    --secondary-blue: #4fc3f7;
    --dark-gray: #424242;
    /* ... */
}
```

### Thêm khóa học mới
```javascript
window.visionDB.addCourse({
    name: "Tên khóa học",
    description: "Mô tả",
    duration: "X giờ",
    price: 999,
    maxCapacity: 10,
    image: "🚗"
});
```

## Kết nối Database thực
Để kết nối với database thực (MySQL, PostgreSQL, etc.):
1. Thay thế LocalStorage bằng API calls
2. Cập nhật `/js/database.js`
3. Thêm authentication server-side
4. Implement file upload cho documents

## Troubleshooting

### Vấn đề thường gặp:
1. **Dữ liệu bị mất**: Kiểm tra LocalStorage browser
2. **Admin không đăng nhập được**: Xóa sessionStorage và thử lại
3. **Form không submit**: Kiểm tra validation JavaScript

### Reset dữ liệu:
```javascript
// Xóa tất cả dữ liệu
localStorage.removeItem('visionDriveDB');
window.location.reload();
```

## Contact & Support
- Email: enquiries@visiondrive.nz
- Phone: 0800 837 484
- Address: 21 Ruakura Road, Hamilton East, 3216

## License
Copyright © 2024 Vision Drive. All rights reserved.