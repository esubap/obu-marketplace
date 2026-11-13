# OBU Student Marketplace

> **Official Full-Stack University Student Marketplace for Oda Bultum University (OBU)**  
> *Developed with PHP 8.2+ / PDO, MySQL 8+, HTML5/CSS3/Vanilla JS, and Bootstrap 5.*

---

## 📌 Project Overview

**OBU Student Marketplace** is a centralized, secure, multi-language, database-driven web application designed for students and faculty of **Oda Bultum University** in Chiro, West Hararghe, Ethiopia. The platform enables university community members to buy and sell textbooks, electronics, engineering tools, lab coats, and dorm essentials directly and safely within campus grounds.

### Key Capabilities

- **Role-Based Access Control (RBAC)**: Distinct permissions for **Students**, **Campus Moderators**, and **Platform Administrators**.
- **Trilingual Localization**:
  1. **English (EN)**
  2. **Amharic (አማርኛ - AM)**
  3. **Afaan Oromo (Afaan Oromoo - OM)**
- **Theme Modes**:
  - ⚡ **Bright Mode (Default)**: Vibrant university campus styling with high-contrast energy and mint/sapphire accents.
  - ☀️ **Light Mode**: Crisp classic academic slate styling.
  - 🌙 **Dark Mode**: Sleek deep navy and charcoal for evening dormitory study.
- **In-Person Safe Exchange Cycle**: Zero online payment commissions or card harvesting; built specifically for student in-person safety and physical inspection before cash handover.
- **Internal Messaging & Notification Engine**: Student-to-student conversations, purchase offers, unread counters, and administrative bulletins.
- **Cybersecurity Hardening**: PDO prepared statements, bcrypt password hashing, session fixation prevention, login rate limiting, CSRF verification tokens, upload MIME/extension/size validation, and `.htaccess` protection.

---

## 🏗️ Project Architecture

```
obu-marketplace/
│
├── index.php                 # Dynamic Homepage with Hero, Search, Categories, Latest & Featured Items
├── login.php                 # Rate-limited Student & Staff Login with Demo Switcher
├── register.php              # Verified Student Registration Form (Student ID, Dept, Phone)
├── logout.php                # Secure Session Invalidation & Cleanup
├── forgot-password.php       # Password Reset Request Generator (expiring single-use tokens)
├── reset-password.php        # Secure New Password Entry
├── products.php              # Full Catalog Discovery with Filtering & Pagination
├── product-details.php       # Product Details, Image Switcher, Seller Bio, Contact & Offers
├── category.php              # Taxonomy Gateway (redirects to filtered products)
├── search.php                # Universal Search Handler
├── how-it-works.php          # Step-by-Step 4-Stage Student Trade Guide
├── safety-tips.php           # Campus Safety Rules & Recommended Meeting Locations
├── about.php                 # OBU Marketplace Mission & Academic Background
├── contact.php               # Campus Support Desk & Student Affairs Inquiries
├── install.php               # One-Click Web Installer & Database Initializer
├── 404.php                   # Custom Responsive 404 Not Found Page
├── .htaccess                 # Security Headers, Directory Listing Prevention & Rewrite Rules
├── README.md                 # Complete Installation & Deployment Manual
│
├── config/
│   ├── app.php               # Application Bootstrapper, Secure Sessions & Timezones
│   ├── database.php          # Resilient PDO Database Singleton Connection Handler
│   └── constants.php         # System Constants, Roles, Themes, File Restrictions
│
├── includes/
│   ├── auth.php              # RBAC Middleware & Authentication Helpers
│   ├── functions.php         # Flash Alerts, Formatting, Notifications & Audit Logging
│   ├── csrf.php              # Anti-CSRF Token Generation & Verification
│   ├── validation.php        # XSS Output Escaping, Regex & Strict File Upload Validation
│   ├── language.php          # Language & Visual Theme Session/Cookie Initializer
│   ├── header.php            # Master Header (Top Bar, Theme Dropdown, Lang Selector)
│   ├── navbar.php            # Main Sticky Navigation Bar
│   ├── sidebar.php           # Student Dashboard Menu with Unread Counter Badges
│   └── footer.php            # Responsive Campus Footer with Security Notices
│
├── languages/
│   ├── en.php                # Complete English Interface Pack
│   ├── am.php                # Complete Amharic Interface Pack (አማርኛ)
│   └── om.php                # Complete Afaan Oromoo Interface Pack (Afaan Oromoo)
│
├── student/
│   ├── dashboard.php         # Student Central Overview, KPI Statistics & Recent Activity
│   ├── add-product.php       # Multi-Photo Product Publishing Form with Live Preview
│   ├── my-products.php       # Manage Own Listings (Active, Sold, Hidden)
│   ├── edit-product.php      # Ownership-Protected Listing Editor
│   ├── delete-product.php    # Secure Listing & Image Deletion Handler
│   ├── purchase-requests.php # Incoming Buyer Offers & Outgoing Purchase Requests
│   ├── messages.php          # Active Chat List & Unread Counter
│   ├── conversation.php      # Student-to-Student Live Chat Thread
│   ├── favorites.php         # Bookmarked Products with Availability Tracking
│   ├── notifications.php     # System Alerts, Offer Updates & Moderation Notices
│   ├── profile.php           # Verified Academic Identity & Activity Summary
│   ├── edit-profile.php      # Update Phone, Department, Year & Photo
│   └── settings.php          # Visual Theme Switcher, Language Selection & Password Change
│
├── moderator/
│   ├── dashboard.php         # Incident Queue, Pending Reports & Rapid Hide Actions
│   ├── reports.php           # Moderation Reports Filtered by Status
│   ├── review-report.php     # Detailed Incident Investigation View
│   └── moderation-actions.php# Record Resolutions, Dismissals & Hide Actions
│
├── admin/
│   ├── dashboard.php         # Master Dashboard with Chart Breakdowns & Metrics
│   ├── students.php          # Student Account Management (Activate / Suspend)
│   ├── products.php          # University-wide Catalog Moderation
│   ├── categories.php        # Multilingual Category Taxonomy Manager
│   ├── reports.php           # Disciplinary Oversight & Incident Logs
│   ├── moderators.php        # Appoint & Revoke Campus Staff/Student Moderators
│   ├── announcements.php     # Publish Trilingual Campus Broadcasts
│   ├── analytics.php         # Trading Volume & Faculty Department Analytics
│   ├── activity-logs.php     # Security Audit Trail (IP, Timestamps, Actions)
│   ├── settings.php          # System Boundaries & Default Theme Configuration
│   ├── profile.php           # Master Administrator Security Credentials
│   └── login.php             # Admin Authentication Entrypoint
│
├── actions/
│   ├── register-action.php   # Registration Validator & Bcrypt Hasher
│   ├── login-action.php      # Rate-Limited Authentication Processor
│   ├── product-action.php    # Listing Creation, Update & Ownership Check
│   ├── purchase-action.php   # Purchase Offer Lifecycle (Accept, Decline, Complete)
│   ├── message-action.php    # Chat Creator & Messenger
│   ├── favorite-action.php   # AJAX/Form Favorite Toggle
│   ├── report-action.php     # Student Incident Submissions
│   ├── notification-action.php # Mark Read & Mark All Handlers
│   └── user-settings-action.php # Profile, Password & Theme State Synchronizer
│
├── database/
│   └── university_marketplace.sql # Complete Database Schema, Constraints & Seed Data
│
├── assets/
│   ├── css/
│   │   ├── style.css         # Theme Engine (CSS Variables for Bright, Light, Dark)
│   │   ├── auth.css          # Login/Register Styling & Password Strength Meter
│   │   ├── dashboard.css     # Dashboard Cards, Chat Bubbles & Tables
│   │   └── responsive.css    # 360px Mobile, 768px Tablet, 1366px Desktop Viewports
│   ├── js/
│   │   ├── main.js           # Theme Switcher, LocalStorage Sync, Auto-Dismiss Alerts
│   │   ├── validation.js     # Form Validation, Password Confirmation Check
│   │   └── marketplace.js    # Multi-Image Preview, AJAX Favorites & Chat Scroll
│   └── images/
│       ├── logo.svg          # Official OBU Marketplace Vector Logo
│       └── placeholders/     # Crisp High-Res SVG Item & Avatar Placeholders
│
├── uploads/
│   ├── .htaccess             # Script Execution Blocker in Uploads
│   ├── products/             # Secure Product Photos Storage
│   └── profiles/             # Student Profile Pictures Storage
│
└── scripts/
    └── create-admin.php      # Secure CLI Script for Creating / Resetting Admin Accounts
```

---

## 🚀 Local Installation Guide (Using XAMPP on Windows)

### Step 1: Install XAMPP
Download and install **XAMPP for Windows** (with PHP 8.0+ and MySQL/MariaDB) from [Apache Friends](https://www.apachefriends.org/).

### Step 2: Start Apache and MySQL
1. Launch the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

### Step 3: Copy Project into htdocs
Copy the `obu-marketplace` folder into your XAMPP web root directory:
```
C:\xampp\htdocs\obu-marketplace
```

### Step 4: Import Database
You have two easy ways to set up the database:

#### Method A: Automated Web Setup Wizard (Recommended)
1. Open your web browser and navigate to:
   ```
   http://localhost/obu-marketplace/install.php
   ```
2. The installer will automatically verify server extensions, test MySQL connectivity, create the `university_marketplace` database, and seed all sample data with one click!

#### Method B: Manual phpMyAdmin Import
1. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
2. Click **New** and create a database named:
   ```sql
   university_marketplace
   ```
   Set collation to `utf8mb4_unicode_ci`.
3. Click on the newly created database, open the **Import** tab, browse to:
   ```
   C:\xampp\htdocs\obu-marketplace\database\university_marketplace.sql
   ```
4. Click **Import** at the bottom.

### Step 5: Verify Database Credentials
Open `config/database.php` and verify your local settings (default for XAMPP):
```php
private static string $host = '127.0.0.1';
private static string $port = '3306';
private static string $dbname = 'university_marketplace';
private static string $username = 'root';
private static string $password = '';
```

### Step 6: Create or Reset Administrator Account (CLI)
You can create or update an administrator account via the command line:
```powershell
cd "C:\xampp\htdocs\obu-marketplace"
C:\xampp\php\php.exe scripts/create-admin.php
```

### Step 7: Open the Application
Visit the marketplace in your browser:
```
http://localhost/obu-marketplace/
```

---

## 🔑 Default Test Accounts

For evaluation and testing, the seed data comes with pre-configured accounts:

| Role | Email | Password | Student / Staff ID | Description |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | `admin@obu.edu.et` | `Password123!` | `ADMIN-001` | Full administrative control, user moderation, analytics |
| **Campus Moderator** | `moderator@obu.edu.et` | `Password123!` | `MOD-001` | Review complaints, hide suspicious listings |
| **Student (Buyer/Seller)** | `student1@obu.edu.et` | `Password123!` | `OBU/0452/14` | Computer Science 3rd Year (Tolosa Bekele) |
| **Student (Buyer/Seller)** | `student2@obu.edu.et` | `Password123!` | `OBU/0889/15` | Software Engineering 2nd Year (Hanan Mohammed) |
| **Student (Buyer/Seller)** | `student3@obu.edu.et` | `Password123!` | `OBU/1123/13` | Electrical Engineering 4th Year (Yonas Abera) |

---

## 🌐 Multilingual System

The marketplace provides native interface translation in three languages:
- **English**: `/languages/en.php`
- **Amharic (አማርኛ)**: `/languages/am.php`
- **Afaan Oromoo**: `/languages/om.php`

Translations are invoked via the global helper:
```php
t('key_name', 'Default Fallback')
```
And dynamic database taxonomy fields:
```php
localized($category, 'name') // returns name_en, name_am, or name_om depending on active session
```
The user's language choice is stored in `$_SESSION['lang']`, cookies, and saved to their user record in MySQL.

---

## 🎨 Theme Customization (Bright, Light, Dark)

The visual design is powered by CSS custom properties and switches instantly:
- `data-theme="bright"`: Vibrant, high-contrast, modern campus palette.
- `data-theme="light"`: Understated clean academic slate.
- `data-theme="dark"`: Late-night dormitory study palette.

Users can toggle themes from the top navigation dropdown or in **Settings**, with seamless synchronization across sessions and devices.

---

## 🛡️ Cybersecurity & Defensive Engineering

1. **SQL Injection Prevention**: All queries use PDO prepared statements with parameter binding. `PDO::ATTR_EMULATE_PREPARES` is set to `false`.
2. **Cross-Site Scripting (XSS)**: All user-supplied content rendered in HTML is sanitized through `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
3. **Cross-Site Request Forgery (CSRF)**: State-changing POST forms require a cryptographically secure token checked with `hash_equals()`.
4. **Session Fixation Prevention**: Session IDs are regenerated via `session_regenerate_id(true)` immediately upon authentication.
5. **Brute Force Rate Limiting**: The `login_attempts` table tracks failed logins and enforces a 15-minute temporary lockout after 5 consecutive failures.
6. **Secure Image Uploads**:
   - File extension validation (`.jpg`, `.jpeg`, `.png`, `.webp`).
   - Binary MIME verification with `finfo_file` and `getimagesize()`.
   - File size capped at 5MB.
   - Files renamed to cryptographically random hashes (`prod_<token>_<timestamp>.<ext>`).
   - `.htaccess` in `uploads/` prevents any executable PHP script execution.
7. **Strict Role-Based Authorization (RBAC)**: Backend verification on every protected endpoint ensures unauthorized users cannot perform admin or moderator tasks.

---

## ☁️ Production Deployment Instructions

1. **Hosting Requirements**: Apache 2.4+ with `mod_rewrite`, `mod_headers`, PHP 8.2+, MySQL 8.0+ or MariaDB 10.4+.
2. **File Permissions**:
   - Web files: `644` for files, `755` for directories.
   - `uploads/`: `775` (writable by web server process).
3. **Database Configuration**: Configure database credentials in `config/database.php` or using environment variables. Never commit production passwords to version control.
4. **HTTPS Enforcement**: Configure SSL certificate (Let's Encrypt or Commercial SSL). The application automatically detects HTTPS and enables the `secure` flag on session cookies.
5. **Disable Error Output**: In `config/app.php`, set `display_errors` to `0` and configure PHP error logging to a private log file.

---

## 📄 License & Academic Attribution
Developed for **Oda Bultum University** student community exchange.  
Copyright &copy; 2026 Oda Bultum University. All Rights Reserved.
