# Business Directory - Professional Web Application

## Project Overview
A comprehensive, SEO-optimized business directory web application built with Core PHP, MySQL, HTML, CSS, and JavaScript. Allows users to search and discover businesses by category and location with ratings, reviews, and contact information.

## Architecture

### Frontend
- **Framework:** None (vanilla HTML/CSS/JavaScript)
- **Server:** PHP 8.2 built-in development server
- **Port:** 5000
- **Responsive Design:** Mobile-first, fully responsive UI
- **SEO:** Full implementation with schema markup, meta tags, sitemaps

### Backend
- **Language:** PHP 8.2 (Core PHP, no frameworks)
- **Database:** MySQL (remote or local)
- **Query Type:** Prepared statements for security
- **Performance:** Pagination, indexing, caching headers

## Project Structure

```
/
├── config/
│   └── db.php              # Database connection and helper functions
├── includes/
│   ├── header.php          # Global header with navigation
│   ├── footer.php          # Global footer
│   └── functions.php       # Utility functions (slug generation, etc)
├── pages/
│   ├── search.php          # Search results page
│   ├── business-detail.php # Individual business details
│   ├── categories.php      # Category listing page
│   ├── locations.php       # Location listing page
│   └── sitemap.php         # XML sitemap generation
├── admin/
│   ├── index.php           # Admin login
│   ├── dashboard.php       # Admin dashboard with stats
│   ├── businesses.php      # Manage businesses
│   ├── categories.php      # View categories
│   ├── locations.php       # View locations
│   └── logout.php          # Logout handler
├── assets/
│   ├── css/
│   │   └── style.css       # All styling (responsive)
│   └── js/
│       └── main.js         # JavaScript utilities
├── setup/
│   ├── database.sql        # Database schema and sample data
│   └── setup.php           # Database setup script
├── index.php               # Homepage with search
├── robots.txt              # SEO robots file
├── .htaccess               # URL rewriting and security headers
└── README.md               # Setup instructions
```

## Key Features

### Public Features
- **Homepage** with featured businesses and search bar
- **Search System** - Filter by business name, category, and location
- **Category Pages** - Browse all business categories
- **Location Pages** - Browse businesses by city
- **Business Details** - Full business information with:
  - Address, phone, website, rating, reviews
  - Google Map embed
  - Call and WhatsApp buttons
  - Related businesses section
  - Schema markup for SEO

### SEO Features
- Dynamic meta tags and descriptions
- JSON-LD LocalBusiness schema
- Clean URLs via .htaccess
- XML sitemap generation
- Robots.txt
- Internal linking
- Mobile-responsive design
- Open Graph tags ready

### Admin Panel
- Secure login (demo: admin/password123)
- Dashboard with statistics:
  - Total businesses
  - Total categories
  - Total locations
  - Average rating
- Manage businesses list
- View categories
- View locations

### Performance
- Pagination for large datasets
- Database indexing on key columns
- Prepared statements for security
- Gzip compression via .htaccess
- Cache headers for static assets
- Lazy loading ready

## Database Schema

### businesses table
- id (PK)
- name
- category
- address, city, state
- phone, website, email
- description (LONGTEXT)
- rating, reviews_count
- latitude, longitude
- status (active/inactive)
- created_at, updated_at

Indexes: category, city, rating, status, FULLTEXT search

## Running the Application

### Prerequisites
- PHP 8.2+
- MySQL 5.7+
- Web server (Apache/Nginx)

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repo-url>
   cd business-directory
   ```

2. **Configure database connection**
   - Edit `config/db.php`
   - Update DB_HOST, DB_USER, DB_PASS, DB_NAME
   - For Hostinger: Use remote credentials

3. **Create database tables**
   - Import `setup/database.sql` into MySQL
   - OR run `php setup/setup.php`

4. **Start PHP server**
   ```bash
   php -S 0.0.0.0:5000
   ```

5. **Access the application**
   - Homepage: `http://localhost:5000/`
   - Admin: `http://localhost:5000/admin/`
   - Login: admin / password123

## API/Database Functions (config/db.php)

- `executeQuery($query, $params, $types)` - Execute prepared statement
- `getRow($query, $params, $types)` - Get single row
- `getRows($query, $params, $types)` - Get multiple rows
- `execute($query, $params, $types)` - Insert/update/delete

## Utility Functions (includes/functions.php)

- `esc($text)` - XSS protection
- `generateSlug($str)` - URL slug generation
- `formatPhone($phone)` - Phone formatting
- `paginate($total, $per_page, $current_page)` - Pagination helper
- `displayRating($rating)` - Star rating display
- `baseUrl()` - Get base URL
- `redirect($path)` - Redirect function

## Workflow Configuration

- **Name:** Start application
- **Command:** `php -S 0.0.0.0:5000`
- **Port:** 5000
- **Output Type:** webview
- **Auto Start:** Yes

## Security Measures

- Prepared statements to prevent SQL injection
- HTML escaping to prevent XSS
- Admin panel requires login
- Strong password hashing ready (can upgrade)
- CORS headers optional via .htaccess

## SEO Optimization

✓ Dynamic meta tags
✓ Schema markup (LocalBusiness)
✓ XML sitemap
✓ Robots.txt
✓ Clean URLs (.htaccess)
✓ Mobile responsive
✓ Fast loading (compression, caching)
✓ Internal linking
✓ Keyword optimization

## Deployment

### Production Deployment

1. **Use production-ready server:** Nginx or Apache (not built-in PHP server)

2. **Database:** Host on Hostinger or similar
   ```php
   define('DB_HOST', 'hostinger-server.com');
   define('DB_USER', 'your-user');
   define('DB_PASS', 'your-password');
   ```

3. **Environment variables:**
   - Set `ENVIRONMENT` to 'production' to hide error messages

4. **SSL/TLS:** Enable HTTPS via hosting panel

5. **Optimization:**
   - Enable gzip compression
   - Set proper cache headers
   - Use CDN for static assets
   - Monitor database queries

## Admin Credentials

**Demo Admin Panel:**
- URL: `/admin/`
- Username: admin
- Password: password123

⚠️ **IMPORTANT:** Change these in production!

## Next Steps

1. Set up MySQL database on Hostinger
2. Update database credentials in `config/db.php`
3. Import sample data
4. Customize branding/colors
5. Add more businesses
6. Upgrade admin auth to use hashed passwords
7. Add image uploads for businesses
8. Consider caching layer (Redis)

## Support

For help with database setup or configuration, refer to the database.sql file comments.

## License

All rights reserved © 2024
