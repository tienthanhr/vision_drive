// Database connection and management for Vision Drive
// This file handles data operations for both user and admin interfaces

class VisionDriveDB {
    constructor() {
        this.courses = [
            {
                id: 1,
                name: "Forklift Operator",
                description: "Basic forklift operation training for beginners",
                duration: "8 hours",
                price: 350,
                maxCapacity: 10,
                status: "active",
                image: "🏗️"
            },
            {
                id: 2,
                name: "Forklift Refresher",
                description: "Refresher course for experienced operators",
                duration: "4 hours",
                price: 180,
                maxCapacity: 10,
                status: "active",
                image: "🏗️"
            },
            {
                id: 3,
                name: "Class 2 Truck",
                description: "Heavy vehicle license training",
                duration: "16 hours",
                price: 750,
                maxCapacity: 10,
                status: "active",
                image: "🚛"
            }
        ];

        this.campuses = [
            { id: 1, name: "Auckland", address: "Auckland Campus, New Zealand" },
            { id: 2, name: "Hamilton", address: "Hamilton Campus, New Zealand" },
            { id: 3, name: "Christchurch", address: "Christchurch Campus, New Zealand" }
        ];

        this.trainingSessions = [
            {
                id: 1,
                courseId: 1,
                courseName: "Forklift Operator",
                campusId: 1,
                campusName: "Auckland",
                date: "2024-11-01",
                enrolled: 5,
                maxCapacity: 10,
                status: "scheduled"
            },
            {
                id: 2,
                courseId: 2,
                courseName: "Forklift Refresher",
                campusId: 2,
                campusName: "Hamilton",
                date: "2025-10-01",
                enrolled: 9,
                maxCapacity: 10,
                status: "scheduled"
            },
            {
                id: 3,
                courseId: 3,
                courseName: "Class 2 Truck",
                campusId: 3,
                campusName: "Christchurch",
                date: "2025-10-01",
                enrolled: 1,
                maxCapacity: 10,
                status: "scheduled"
            }
        ];

        this.students = [];
        this.bookings = [];
        
        // Initialize from localStorage if available
        this.loadFromStorage();
    }

    // Save data to localStorage
    saveToStorage() {
        localStorage.setItem('visionDriveDB', JSON.stringify({
            courses: this.courses,
            campuses: this.campuses,
            trainingSessions: this.trainingSessions,
            students: this.students,
            bookings: this.bookings
        }));
    }

    // Load data from localStorage
    loadFromStorage() {
        const stored = localStorage.getItem('visionDriveDB');
        if (stored) {
            const data = JSON.parse(stored);
            this.courses = data.courses || this.courses;
            this.campuses = data.campuses || this.campuses;
            this.trainingSessions = data.trainingSessions || this.trainingSessions;
            this.students = data.students || [];
            this.bookings = data.bookings || [];
        }
    }

    // Course management
    getCourses() {
        return this.courses;
    }

    getCourseById(id) {
        return this.courses.find(course => course.id === parseInt(id));
    }

    addCourse(courseData) {
        const newCourse = {
            id: this.courses.length > 0 ? Math.max(...this.courses.map(c => c.id)) + 1 : 1,
            ...courseData,
            status: 'active'
        };
        this.courses.push(newCourse);
        this.saveToStorage();
        return newCourse;
    }

    updateCourse(id, courseData) {
        const index = this.courses.findIndex(course => course.id === parseInt(id));
        if (index !== -1) {
            this.courses[index] = { ...this.courses[index], ...courseData };
            this.saveToStorage();
            return this.courses[index];
        }
        return null;
    }

    deleteCourse(id) {
        const index = this.courses.findIndex(course => course.id === parseInt(id));
        if (index !== -1) {
            this.courses.splice(index, 1);
            this.saveToStorage();
            return true;
        }
        return false;
    }

    // Campus management
    getCampuses() {
        return this.campuses;
    }

    getCampusById(id) {
        return this.campuses.find(campus => campus.id === parseInt(id));
    }

    addCampus(campusData) {
        const newCampus = {
            id: this.campuses.length > 0 ? Math.max(...this.campuses.map(c => c.id)) + 1 : 1,
            ...campusData,
            status: campusData.status || 'active'
        };
        this.campuses.push(newCampus);
        this.saveToStorage();
        return newCampus;
    }

    updateCampus(id, campusData) {
        const index = this.campuses.findIndex(campus => campus.id === parseInt(id));
        if (index !== -1) {
            this.campuses[index] = { ...this.campuses[index], ...campusData };
            this.saveToStorage();
            return this.campuses[index];
        }
        return null;
    }

    deleteCampus(id) {
        const index = this.campuses.findIndex(campus => campus.id === parseInt(id));
        if (index !== -1) {
            this.campuses.splice(index, 1);
            this.saveToStorage();
            return true;
        }
        return false;
    }

    // Course management methods
    addCourse(courseData) {
        const newCourse = {
            id: this.courses.length > 0 ? Math.max(...this.courses.map(c => c.id)) + 1 : 1,
            ...courseData,
            status: courseData.status || 'active',
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        };
        this.courses.push(newCourse);
        this.saveToStorage();
        return newCourse;
    }

    updateCourse(id, courseData) {
        const index = this.courses.findIndex(course => course.id === parseInt(id));
        if (index !== -1) {
            this.courses[index] = { 
                ...this.courses[index], 
                ...courseData,
                updatedAt: new Date().toISOString()
            };
            this.saveToStorage();
            return this.courses[index];
        }
        return null;
    }

    deleteCourse(id) {
        const index = this.courses.findIndex(course => course.id === parseInt(id));
        if (index !== -1) {
            this.courses.splice(index, 1);
            this.saveToStorage();
            return true;
        }
        return false;
    }

    getCourseById(id) {
        return this.courses.find(course => course.id === parseInt(id));
    }

    searchCourses(query) {
        const lowerQuery = query.toLowerCase();
        return this.courses.filter(course => 
            course.name.toLowerCase().includes(lowerQuery) ||
            course.description.toLowerCase().includes(lowerQuery) ||
            (course.code && course.code.toLowerCase().includes(lowerQuery)) ||
            (course.category && course.category.toLowerCase().includes(lowerQuery))
        );
    }

    getCoursesByCategory(category) {
        return this.courses.filter(c => c.category === category);
    }

    getCoursesByCampus(campusId) {
        return this.courses.filter(c => c.campuses && c.campuses.includes(parseInt(campusId)));
    }

    // Training session management
    getTrainingSessions() {
        return this.trainingSessions;
    }

    getTrainingSessionById(id) {
        return this.trainingSessions.find(session => session.id === parseInt(id));
    }

    addTrainingSession(sessionData) {
        const course = this.getCourseById(sessionData.courseId);
        const campus = this.getCampusById(sessionData.campusId);
        
        const newSession = {
            id: this.trainingSessions.length > 0 ? Math.max(...this.trainingSessions.map(s => s.id)) + 1 : 1,
            courseName: course ? course.name : '',
            campusName: campus ? campus.name : '',
            enrolled: 0,
            maxCapacity: course ? course.maxCapacity : 10,
            status: 'scheduled',
            ...sessionData
        };
        
        this.trainingSessions.push(newSession);
        this.saveToStorage();
        return newSession;
    }

    updateTrainingSession(id, sessionData) {
        const index = this.trainingSessions.findIndex(session => session.id === parseInt(id));
        if (index !== -1) {
            this.trainingSessions[index] = { ...this.trainingSessions[index], ...sessionData };
            this.saveToStorage();
            return this.trainingSessions[index];
        }
        return null;
    }

    deleteTrainingSession(id) {
        const index = this.trainingSessions.findIndex(session => session.id === parseInt(id));
        if (index !== -1) {
            this.trainingSessions.splice(index, 1);
            this.saveToStorage();
            return true;
        }
        return false;
    }

    // Student management
    getStudents() {
        return this.students;
    }

    addStudent(studentData) {
        const newStudent = {
            id: this.students.length > 0 ? Math.max(...this.students.map(s => s.id)) + 1 : 1,
            registrationDate: new Date().toISOString(),
            status: 'active',
            ...studentData
        };
        
        this.students.push(newStudent);
        this.saveToStorage();
        return newStudent;
    }

    // Booking management
    createBooking(bookingData) {
        const confirmationCode = this.generateConfirmationCode();
        
        const newBooking = {
            id: this.bookings.length > 0 ? Math.max(...this.bookings.map(b => b.id)) + 1 : 1,
            confirmationCode: confirmationCode,
            bookingDate: new Date().toISOString(),
            status: 'confirmed',
            ...bookingData
        };
        
        this.bookings.push(newBooking);
        
        // Update enrolled count for training session
        const sessionIndex = this.trainingSessions.findIndex(s => s.id === parseInt(bookingData.sessionId));
        if (sessionIndex !== -1) {
            this.trainingSessions[sessionIndex].enrolled += 1;
        }
        
        this.saveToStorage();
        return newBooking;
    }

    getBookings() {
        return this.bookings;
    }

    getBookingByConfirmationCode(code) {
        return this.bookings.find(booking => booking.confirmationCode === code);
    }

    // Utility methods
    generateConfirmationCode() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const random = String(Math.floor(Math.random() * 10000)).padStart(4, '0');
        
        return `VD${year}${month}${day}${random}`;
    }

    // Statistics for dashboard
    getStatistics() {
        return {
            totalBookings: this.bookings.length,
            totalStudents: this.students.length,
            activeCourses: this.courses.filter(c => c.status === 'active').length,
            totalCampuses: this.campuses.length,
            upcomingTrainingSessions: this.trainingSessions.filter(s => {
                const sessionDate = new Date(s.date);
                const now = new Date();
                return sessionDate >= now;
            }).length
        };
    }

    // Search functionality
    searchCourses(query) {
        const lowerQuery = query.toLowerCase();
        return this.courses.filter(course => 
            course.name.toLowerCase().includes(lowerQuery) ||
            course.description.toLowerCase().includes(lowerQuery)
        );
    }

    searchTrainingSessions(query) {
        const lowerQuery = query.toLowerCase();
        return this.trainingSessions.filter(session => 
            session.courseName.toLowerCase().includes(lowerQuery) ||
            session.campusName.toLowerCase().includes(lowerQuery) ||
            session.status.toLowerCase().includes(lowerQuery)
        );
    }

    searchStudents(query) {
        const lowerQuery = query.toLowerCase();
        return this.students.filter(student => 
            (student.firstName && student.firstName.toLowerCase().includes(lowerQuery)) ||
            (student.lastName && student.lastName.toLowerCase().includes(lowerQuery)) ||
            (student.email && student.email.toLowerCase().includes(lowerQuery))
        );
    }

    // Authentication (simple implementation)
    authenticate(username, password) {
        // In a real application, this would check against a secure user database
        const validCredentials = [
            { username: 'admin', password: 'password123', role: 'admin' },
            { username: 'staff', password: 'staff123', role: 'staff' }
        ];
        
        return validCredentials.find(cred => 
            cred.username === username && cred.password === password
        );
    }

    // Data export functionality
    exportData(type) {
        let data;
        let filename;
        
        switch(type) {
            case 'courses':
                data = this.courses;
                filename = 'vision_drive_courses.json';
                break;
            case 'students':
                data = this.students;
                filename = 'vision_drive_students.json';
                break;
            case 'bookings':
                data = this.bookings;
                filename = 'vision_drive_bookings.json';
                break;
            case 'sessions':
                data = this.trainingSessions;
                filename = 'vision_drive_sessions.json';
                break;
            default:
                data = {
                    courses: this.courses,
                    students: this.students,
                    bookings: this.bookings,
                    trainingSessions: this.trainingSessions,
                    campuses: this.campuses
                };
                filename = 'vision_drive_complete_export.json';
        }
        
        return {
            data: JSON.stringify(data, null, 2),
            filename: filename
        };
    }

    // Data import functionality
    importData(jsonData, type) {
        try {
            const data = JSON.parse(jsonData);
            
            switch(type) {
                case 'courses':
                    this.courses = data;
                    break;
                case 'students':
                    this.students = data;
                    break;
                case 'bookings':
                    this.bookings = data;
                    break;
                case 'sessions':
                    this.trainingSessions = data;
                    break;
                case 'complete':
                    this.courses = data.courses || this.courses;
                    this.students = data.students || this.students;
                    this.bookings = data.bookings || this.bookings;
                    this.trainingSessions = data.trainingSessions || this.trainingSessions;
                    this.campuses = data.campuses || this.campuses;
                    break;
            }
            
            this.saveToStorage();
            return true;
        } catch (error) {
            console.error('Error importing data:', error);
            return false;
        }
    }
}

// Create global database instance
window.visionDB = new VisionDriveDB();

// Utility functions for UI
window.VisionDriveUtils = {
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('en-NZ', {
            style: 'currency',
            currency: 'NZD'
        }).format(amount);
    },

    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-NZ', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    validatePhone: function(phone) {
        const re = /^[\d\s\-\+\(\)]+$/;
        return re.test(phone) && phone.length >= 8;
    },

    showNotification: function(message, type = 'info') {
        // Simple notification system
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
            color: white;
            border-radius: 5px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    },

    downloadFile: function(content, filename) {
        const blob = new Blob([content], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
};

console.log('Vision Drive Database System initialized');
console.log('Available courses:', window.visionDB.getCourses().length);
console.log('Available campuses:', window.visionDB.getCampuses().length);