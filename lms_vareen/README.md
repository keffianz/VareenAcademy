# VEREEN Academy MVP - Online Learning Platform

A modern, mobile-responsive online academy platform built with HTML5, CSS3, JavaScript, and PHP.

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache server with mod_rewrite enabled

### Installation

### Hostinger database configuration

After importing the shared-hosting SQL file, create
`src/config/local_db.php` on the server. Do not commit this file.

```php
<?php
return [
	'host' => 'localhost',
	'user' => 'YOUR_HOSTINGER_DATABASE_USER',
	'pass' => 'YOUR_HOSTINGER_DATABASE_PASSWORD',
	'name' => 'u374397808_vereen_academy',
];
```

The application also accepts the `DB_HOST`, `DB_USER`, `DB_PASS`, and
`DB_NAME` environment variables. The MySQL database and user must be created
and attached in Hostinger hPanel before testing login.

1. **Clone or download the project**
```bash
cd vereen-academy
```

2. **Create database**
```bash
# Import the schema
mysql -u root -p < database/schema.sql

# Or manually create database
CREATE DATABASE vereen_academy;
```

3. **Configure database connection**
Edit `src/config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'vereen_academy');
```

4. **Set up file permissions**
```bash
chmod 755 assets/uploads/
chmod 755 assets/uploads/profiles/
chmod 755 assets/uploads/assignments/
chmod 755 assets/uploads/recordings/
```

5. **Start local server**
```bash
php -S localhost:8000
```

6. **Access the platform**
Open `http://localhost:8000` in your browser

## 📁 Project Structure

```
vereen-academy/
├── database/
│   └── schema.sql              # Database schema
├── public/
│   ├── css/
│   │   ├── styles.css          # Main styles
│   │   └── responsive.css      # Mobile responsive
│   ├── js/
│   │   ├── main.js             # Main JavaScript
│   │   └── auth.js             # Authentication JS
│   └── img/                    # Images
├── assets/
│   └── uploads/                # User uploads directory
├── src/
│   ├── api/                    # API endpoints
│   │   └── auth.php            # Auth API
│   ├── config/
│   │   └── database.php        # Database config
│   ├── middleware/
│   │   └── auth.php            # Auth middleware
│   └── classes/
│       ├── Database.php        # Database class
│       └── User.php            # User class
├── views/
│   ├── auth/
│   │   ├── login.php
│   │   ├── signup.php
│   │   └── password-reset.php
│   ├── student/
│   ├── teacher/
│   ├── admin/
│   └── home.php
├── index.php                   # Main entry point
├── .htaccess                   # URL rewriting
└── README.md
```

## 🔐 Phase 1: Authentication System (COMPLETED ✅)

### Features Implemented:
- ✅ User registration (signup)
- ✅ User login with session management
- ✅ Password reset with email token
- ✅ Password change functionality
- ✅ Email validation
- ✅ Secure password hashing (bcrypt)
- ✅ Session timeout (30 minutes)
- ✅ Protected routes and role-based access
- ✅ User profile management
- ✅ Mobile-responsive auth forms

### Database Tables Created:
- `users` - User accounts
- `password_resets` - Password reset tokens
- All supporting tables for future phases

### API Endpoints:
- `POST /src/api/auth.php?action=signup` - Register new user
- `POST /src/api/auth.php?action=login` - Login user
- `POST /src/api/auth.php?action=logout` - Logout user
- `POST /src/api/auth.php?action=check_email` - Check email availability
- `POST /src/api/auth.php?action=change_password` - Change password
- `POST /src/api/auth.php?action=request_reset` - Request password reset
- `POST /src/api/auth.php?action=reset_password` - Reset password with token

### Usage:
1. Navigate to `/index.php?page=signup` to create account
2. Navigate to `/index.php?page=login` to login
3. Navigate to `/index.php?page=password-reset` to reset password

## 🔄 Upcoming Phases

### Phase 2: Student Dashboard
- Enrolled courses display
- Course progress tracking
- Upcoming live classes
- Assignment deadlines
- Notifications panel

### Phase 3: Course Module System
- Course creation and management
- Module and lesson structure
- Video lessons
- Downloadable resources
- Progress tracking

### Phase 4: Teacher Dashboard
- Course management (CRUD)
- Lesson management
- Analytics and reporting
- Student tracking

### Phase 5: Live Class System
- Schedule live classes
- Zoom/Google Meet integration
- Join class interface
- Recording replay

### Phase 6: Assignment System
- Create and upload assignments
- Student submission
- Grading and feedback

### Phase 7: Quiz System
- Quiz builder
- Multiple choice questions
- Auto-grading
- Results display

### Phase 8: Notifications System
- Real-time notifications
- Email alerts
- Notification center

### Phase 9: Mobile Optimization
- Further responsive refinements
- Performance optimization

### Phase 10: Testing & Polish
- Bug fixes
- Security audit
- Cross-browser testing

## 🎨 Design Features

### User Interface
- Modern gradient design (purple/blue theme)
- Card-based layout
- Clean typography with Poppins font
- Responsive grid system
- Smooth animations and transitions

### Responsive Design
- Mobile-first approach
- Tablet optimization
- Desktop experience
- Touch-friendly buttons (44px minimum)
- Adaptive navigation

### Accessibility
- ARIA labels ready
- Semantic HTML
- Keyboard navigation support
- High contrast colors
- Print-friendly styles

## 🔒 Security Features

- **Password Security**: Bcrypt hashing (PHP's password_hash)
- **Session Management**: 30-minute timeout
- **CSRF Protection Ready**: Foundation in place
- **Input Validation**: Server-side validation
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Prevention**: Output escaping ready

## 🚀 Performance

- **Lazy Loading**: Image lazy loading support
- **Optimized CSS**: Minified and organized
- **Smooth Scrolling**: Native scroll behavior
- **Debouncing**: Resize and scroll handlers
- **LocalStorage**: Client-side caching

## 📱 Mobile Optimization

- Responsive navigation with hamburger menu
- Touch-friendly form inputs
- Optimized images
- Fast loading with minimal assets
- Landscape mode support
- High-DPI screen support

## 🛠️ Development

### Adding New Pages
1. Create view file in `/views/[page_name].php`
2. Add page name to allowed_pages in `index.php`
3. Update navigation if needed

### Adding New API Endpoints
1. Create action in `/src/api/auth.php`
2. Add handler in switch statement
3. Return JSON response

### Styling
- Add global styles to `public/css/styles.css`
- Add responsive styles to `public/css/responsive.css`
- Follow BEM naming convention where possible

### JavaScript
- General utilities go in `public/js/main.js`
- Authentication functions in `public/js/auth.js`
- Export to window object for global access

## 📊 Test Accounts

Once you've run the database schema, you can create test accounts:

**Teacher Account:**
- Email: teacher@example.com
- Password: password123

**Student Account:**
- Email: student@example.com
- Password: password123

Use signup form to create accounts, or manually insert into database.

## ✋ Session Messages

View login parameters:
- `?msg=signup_success` - Show signup success
- `?msg=session_expired` - Show session expired

## 🤝 Contributing

This is an MVP project. Future phases will add more features.

## 📝 License

VEREEN Academy - Educational Use

## 📞 Support

For issues or questions, please create an issue in the repository.

---

**Current Version**: 1.0.0 - MVP Phase 1 (Authentication)
**Last Updated**: 2024
