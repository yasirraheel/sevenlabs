# GEO ENTERPRISES - Prize Bond Booking System

![GEO ENTERPRISES](https://img.shields.io/badge/GEO%20ENTERPRISES-Prize%20Bond%20System-blue)
![Laravel](https://img.shields.io/badge/Laravel-10.x-red)
![PHP](https://img.shields.io/badge/PHP-8.1+-green)
![License](https://img.shields.io/badge/License-MIT-yellow)

A comprehensive and professional prize bond booking and management system built with Laravel. This system provides a complete solution for managing prize bond transactions, customer relationships, and administrative operations.

## 🎯 Overview

GEO ENTERPRISES Prize Bond Booking System is a full-featured web application designed to streamline prize bond operations. The system offers secure transaction management, customer relationship tools, and comprehensive administrative controls.

## ✨ Key Features

### 🏢 Admin Panel
- **Dashboard Analytics**: Real-time statistics and revenue tracking
- **Customer Management**: Complete customer database with profile management
- **Game Categories**: Organize and manage different prize bond categories
- **Payment Methods**: Multiple payment gateway integration
- **Deposit Management**: Approve/reject customer deposits with admin notes
- **Settings Management**: Comprehensive system configuration
- **Theme Customization**: Light/dark mode support

### 👥 Customer Features
- **User Registration & Authentication**: Secure account creation and login
- **Profile Management**: Personal information and account settings
- **Recharge/Deposit System**: Easy fund addition with payment proof upload
- **Transaction History**: Complete record of all transactions
- **Responsive Design**: Mobile-friendly interface

### 💰 Financial Management
- **Multiple Payment Methods**: Support for various payment gateways
- **Balance Management**: Real-time balance tracking
- **Deposit Approval System**: Admin-controlled deposit verification
- **Transaction Records**: Comprehensive financial reporting

### 🎮 Game Management
- **Category System**: Organize prize bonds by categories
- **Subcategory Management**: Detailed classification system
- **Date & Time Tracking**: Schedule management for draws
- **Status Management**: Active/inactive game control

## 🛠️ Technology Stack

- **Backend**: Laravel 10.x
- **Frontend**: Bootstrap 5, HTML5, CSS3, JavaScript
- **Database**: MySQL/MariaDB
- **PHP Version**: 8.1+
- **Web Server**: Apache/Nginx
- **Additional**: Composer, Artisan CLI

## 📋 System Requirements

### Server Requirements
- PHP >= 8.1
- MySQL >= 5.7 or MariaDB >= 10.2
- Apache/Nginx web server
- SSL Certificate (recommended)
- Composer

### PHP Extensions
- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yasirraheel/GEO-ENTERPRISES.git
cd GEO-ENTERPRISES
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
cp .env.example .env
```

Edit the `.env` file with your database and application settings:
```env
APP_NAME="GEO ENTERPRISES - Prize Bond Booking System"
APP_ENV=production
APP_KEY=base64:your-app-key-here
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=geo_enterprises
DB_USERNAME=your_username
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 6. Storage Setup
```bash
php artisan storage:link
```

### 7. Set Permissions
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### 8. Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

## 🔧 Configuration

### Admin Account Setup
After installation, create your admin account:
```bash
php artisan make:command CreateAdmin
```

### Payment Gateway Configuration
Configure your payment gateways in the admin panel:
1. Go to Admin Panel → Settings
2. Configure payment methods
3. Add bank account details
4. Set up payment gateway credentials

### Email Configuration
Set up email settings for notifications:
1. Configure SMTP settings in `.env`
2. Test email functionality
3. Set up email templates

## 📱 Usage

### Admin Panel Access
- URL: `https://yourdomain.com/panel/admin`
- Default admin credentials (change after first login)

### Customer Registration
- URL: `https://yourdomain.com/register`
- Customers can create accounts and start using the system

### Key Workflows

#### Customer Deposit Process
1. Customer logs in to their account
2. Navigates to "Recharge/Deposit" section
3. Selects payment method
4. Enters amount and transaction details
5. Uploads payment proof
6. Admin reviews and approves/rejects

#### Game Category Management
1. Admin creates game categories
2. Sets up subcategories with dates/times
3. Manages active/inactive status
4. Customers can view available games

## 🔐 Security Features

- **CSRF Protection**: All forms protected against CSRF attacks
- **SQL Injection Prevention**: Parameterized queries
- **XSS Protection**: Input sanitization and output escaping
- **Secure Authentication**: Laravel's built-in authentication
- **File Upload Security**: Validated file types and sizes
- **Admin Access Control**: Role-based permissions

## 📊 Database Structure

### Key Tables
- `users` - Customer information
- `admin_settings` - System configuration
- `categories` - Game categories
- `subcategories` - Game subcategories
- `deposits` - Customer deposits
- `payment_methods` - Payment gateway configuration
- `notifications` - System notifications

## 🌐 API Endpoints

### User API
- `GET /api/user/balance` - Get user balance
- `POST /api/user/deposit` - Submit deposit request

### Admin API
- `GET /panel/admin/deposits` - List all deposits
- `POST /panel/admin/deposits/approve` - Approve deposit
- `POST /panel/admin/deposits/reject` - Reject deposit

## 🎨 Customization

### Themes
The system supports light/dark themes:
- Admin can set default theme
- Users can toggle themes
- Responsive design for all devices

### Language Support
- English (default)
- Spanish
- Easy to add more languages

### Branding
- Customizable logo and favicon
- Company name configuration
- Color scheme customization

## 📈 Performance Optimization

- **Database Indexing**: Optimized queries
- **Caching**: Laravel's caching system
- **Image Optimization**: Compressed images
- **CDN Ready**: Static asset optimization

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

## 📝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 🐛 Bug Reports

If you find a bug, please create an issue with:
- Description of the bug
- Steps to reproduce
- Expected vs actual behavior
- Screenshots (if applicable)
- System information

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Contact: [Your Contact Information]
- Documentation: [Link to Documentation]

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Laravel Framework
- Bootstrap
- All contributors and testers

## 📋 Changelog

### Version 1.0.0
- Initial release
- Complete prize bond booking system
- Admin panel with full functionality
- Customer management system
- Payment integration
- Multi-language support

---

**GEO ENTERPRISES** - Professional Prize Bond Booking Solutions

*Built with ❤️ using Laravel*