# Business Directory

A professional, scalable, and SEO-optimized business directory web application built with Core PHP and MySQL.

## Quick Start

### Prerequisites
- PHP 8.2+
- MySQL 5.7+

### Installation

1. **Clone the repository:**
   ```bash
   git clone <repo-url>
   cd business-directory
   ```

2. **Configure database connection:**
   Edit `config/db.php` and update:
   ```php
   define('DB_HOST', 'your-host');
   define('DB_USER', 'your-user');
   define('DB_PASS', 'your-password');
   define('DB_NAME', 'your-database');
   ```

3. **Create database tables:**
   ```bash
   php setup/setup.php
   ```

4. **Start the server:**
   ```bash
   php -S 0.0.0.0:5000
   ```

5. **Visit the application:**
   - Frontend: `http://localhost:5000/`
   - Admin: `http://localhost:5000/admin/`
   - Login: `admin / password123`

## Features

### Public Website
- 🔍 Advanced search by business name, category, location
- ⭐ Business ratings and reviews
- 📍 Google Maps integration
- 📱 Fully responsive mobile design
- 🚀 SEO optimized

### Admin Panel
- 📊 Dashboard with statistics
- 📂 Manage businesses
- 🏷️ Category management
- 📍 Location management

### Technical Features
- ✓ Prepared statements (SQL injection protection)
- ✓ HTML escaping (XSS protection)
- ✓ Pagination for performance
- ✓ JSON-LD Schema markup
- ✓ XML Sitemap generation
- ✓ Clean URLs (.htaccess)
- ✓ Gzip compression
- ✓ Cache headers

## Database Schema

```sql
CREATE TABLE businesses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    phone VARCHAR(20),
    website VARCHAR(255),
    email VARCHAR(255),
    description LONGTEXT,
    rating DECIMAL(3,2) DEFAULT 0,
    reviews_count INT DEFAULT 0,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_city (city),
    INDEX idx_rating (rating),
    FULLTEXT INDEX ft_search (name, description)
);
```

## Project Structure

```
business-directory/
├── config/          # Database configuration
├── includes/        # Reusable components (header, footer, functions)
├── pages/           # Public pages (search, business detail, categories)
├── admin/           # Admin panel
├── assets/          # CSS, JavaScript
├── setup/           # Database schema and setup
├── index.php        # Homepage
├── robots.txt       # SEO
├── .htaccess        # URL rewriting
└── README.md        # This file
```

## Configuration

### Database Connection (config/db.php)
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'business_directory');
define('DB_PORT', 3306);
```

### Admin Credentials (admin/index.php)
```php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'password123');
```

⚠️ Change these in production!

## API Functions

### Database Functions (config/db.php)
- `executeQuery($query, $params, $types)` - Execute prepared statement
- `getRow($query, $params, $types)` - Get single row
- `getRows($query, $params, $types)` - Get multiple rows
- `execute($query, $params, $types)` - INSERT/UPDATE/DELETE

### Utility Functions (includes/functions.php)
- `esc($text)` - HTML escape for XSS protection
- `generateSlug($str)` - Create URL-friendly slugs
- `formatPhone($phone)` - Format phone numbers
- `paginate($total, $per_page, $page)` - Pagination helper
- `baseUrl()` - Get application base URL
- `redirect($path)` - Redirect to path

## SEO Optimization

✓ Dynamic meta tags and descriptions
✓ JSON-LD LocalBusiness schema markup
✓ XML sitemap generation (pages/sitemap.php)
✓ Robots.txt with sitemap reference
✓ Clean URLs via .htaccess rewriting
✓ Mobile-responsive design
✓ Fast loading with gzip compression
✓ Cache control headers
✓ Internal linking between pages

## Security

- **SQL Injection:** Prepared statements with parameterized queries
- **XSS Protection:** HTML escaping all user input and output
- **Admin Security:** Login required for admin panel
- **Input Validation:** Basic validation on searches
- **Security Headers:** X-Frame-Options, X-Content-Type-Options, X-XSS-Protection

## Performance Optimization

- **Pagination:** Large datasets split into pages (12 items per page)
- **Indexing:** Indexes on category, city, rating, status
- **Full-Text Search:** FULLTEXT index on name and description
- **Caching:** Browser cache headers for static assets
- **Compression:** Gzip compression for text content
- **Database Queries:** Optimized with LIMIT and proper WHERE clauses

## Deployment

### Using Hostinger or Similar Hosting

1. **Update database credentials** in `config/db.php`
2. **Use production server** (Nginx/Apache, not PHP built-in)
3. **Enable HTTPS** via hosting control panel
4. **Set environment to production** to hide error details
5. **Update admin credentials** with secure password
6. **Configure domain** in hosting settings

## Troubleshooting

### Database Connection Error
- Check credentials in `config/db.php`
- Verify MySQL service is running
- Check database name exists

### Admin Panel Not Accessible
- Check session support is enabled
- Verify credentials (admin/password123)
- Check if login cookies are enabled

### Search Not Working
- Verify database has sample data
- Check FULLTEXT index exists
- Review prepared statement syntax

## Support & Documentation

See `replit.md` for detailed documentation.

## License

All rights reserved © 2024

---

**Happy coding!** 🚀
