# Sacred Library — Library Management System
## Installation & Setup Guide

---

## Requirements

| Component | Version |
|-----------|---------|
| PHP | 8.0 or higher |
| MySQL | 5.7 / MariaDB 10.3+ |
| Apache | 2.4+ with mod_rewrite |
| Browser | Any modern browser |

---

## Installation Steps

### 1. Copy Files
Upload the entire `lms/` folder to your web server's document root (e.g. `/var/www/html/lms` or `htdocs/lms`).

### 2. Create the Database
Open your MySQL client (phpMyAdmin, MySQL Workbench, or CLI) and run:

```sql
SOURCE /path/to/lms/database.sql;
```

Or copy-paste the contents of `database.sql` into your SQL console.

### 3. Configure the Application
Open `includes/config.php` and update:

```php
define('DB_HOST', 'localhost');      // your DB host
define('DB_USER', 'root');           // your DB username
define('DB_PASS', '');              // your DB password
define('DB_NAME', 'lms_db');        // database name
define('APP_URL', 'http://localhost/lms');  // your public URL
```

### 4. Set File Permissions
Ensure the uploads directory is writable:

```bash
chmod -R 755 assets/uploads/
chown -R www-data:www-data assets/uploads/   # Linux/Apache
```

### 5. Enable mod_rewrite (Apache)
Make sure `mod_rewrite` is enabled and `AllowOverride All` is set in your Apache virtual host config.

### 6. Access the Application
Open your browser and navigate to your `APP_URL`.

---

## Default Admin Credentials

| Field | Value |
|-------|-------|
| Email | admin@lms.com |
| Password | Admin@1234 |

**⚠️ Change the admin password immediately after first login!**

---

## Application Structure

```
lms/
├── index.php              ← Login / Registration page
├── dashboard.php          ← User dashboard (active courses)
├── courses.php            ← All courses listing
├── course.php             ← Course detail with chapters
├── profile.php            ← User profile & password change
├── logout.php             ← Session destroy
├── database.sql           ← Database schema & seed data
├── .htaccess              ← Apache security rules
│
├── includes/
│   ├── config.php         ← DB config, auth helpers, utilities
│   └── layout.php         ← Sidebar, topbar, HTML head/foot partials
│
├── admin/
│   ├── users.php          ← Manage users (CRUD)
│   ├── courses.php        ← Manage courses (CRUD + photo upload)
│   └── chapters.php       ← Manage chapters + docs + links
│
├── api/
│   ├── chapter.php        ← Chapter detail (JSON)
│   ├── like.php           ← Like/unlike toggle (JSON)
│   ├── finish.php         ← Mark chapter finished (JSON)
│   └── comment.php        ← Post comment (JSON)
│
└── assets/
    ├── css/app.css        ← Main stylesheet (Navy/Gold theme)
    ├── js/app.js          ← Main JavaScript
    └── uploads/           ← User avatars, course photos, documents
        ├── avatars/
        ├── courses/
        └── documents/
```

---

## Role-Based Access Control (RBAC)

| Feature | Public (unauthenticated) | Member | Founder | Administrator |
|---------|--------------------------|--------|---------|---------------|
| View login/register | ✅ | — | — | — |
| Dashboard | ❌ | ✅ | ✅ | ✅ |
| Public degree courses | ❌ | ✅ | ✅ | ✅ |
| Degree-gated courses | ❌ | ✅ | ✅ | ✅ |
| Like chapters | ❌ | ✅ | ✅ | ✅ |
| Comment on chapters | ❌ | ✅ | ✅ | ✅ |
| Mark chapters finished | ❌ | ✅ | ✅ | ✅ |
| Edit own profile | ❌ | ✅ | ✅ | ✅ |
| Manage users | ❌ | ❌ | ❌ | ✅ |
| Manage courses | ❌ | ❌ | ❌ | ✅ |
| Manage chapters | ❌ | ❌ | ❌ | ✅ |

**Annual Fees:**
- Member: €10/year
- Founder: €50/year
- Administrator: N/A

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8+ (PDO, password_hash BCrypt) |
| Database | MySQL / MariaDB |
| Frontend | HTML5, CSS3, Bootstrap 5.3 |
| Icons | Font Awesome 6.5 |
| Fonts | Google Fonts (Cinzel + Lato) |
| JS | Vanilla JavaScript (ES6+) |
| Media | YouTube embed API |

---

## Security Features

- BCrypt password hashing (cost 12)
- PDO prepared statements (SQL injection prevention)
- Session-based authentication with HTTP-only cookies
- Input sanitization & output escaping (XSS prevention)
- RBAC enforced server-side on every page
- File upload validation (type, size, extension whitelist)
- PHP execution blocked in uploads directory via `.htaccess`
- Directory listing disabled

---

## Customization

### Change the App Name
Edit `APP_NAME` in `includes/config.php` and update the logo text in `includes/layout.php`.

### Change Colors
All colors are CSS variables at the top of `assets/css/app.css`. The main palette:
- `--primary`: Deep Navy `#1a2744`
- `--gold`: Masonic Gold `#c9a84c`
- `--cream`: Warm Cream `#f5f0e8`

### Add Payment Integration
The membership status field (`Member`, `Founder`) can be connected to Stripe or PayPal webhooks to auto-update user status upon successful payment.

---

## Support

For issues or customizations, review the inline code comments throughout each file. The codebase is intentionally simple and well-structured for easy modification.
