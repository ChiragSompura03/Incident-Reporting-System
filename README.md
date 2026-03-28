# Incident-Reporting-System
A secure Incident Reporting System built with PHP, MySQL, Bootstrap 5, and JavaScript featuring role-based access control, audit logging, and real-time notifications.

# 🛡️ Secure Incident Reporting System

A full-stack cybersecurity **Incident Reporting System** built with **PHP + MySQL + Bootstrap 5 + JavaScript (AJAX)**. Features Role-Based Access Control (RBAC), JWT Authentication, real-time notifications, analytics dashboards, audit logging, and complete user/admin/super-admin workflows.

---

## 📌 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [Setup Instructions](#setup-instructions)
- [Default Login Credentials](#default-login-credentials)
- [Role-Based Access](#role-based-access)
- [Screenshots](#screenshots)
- [API / Route Reference](#api--route-reference)
- [Security Measures](#security-measures)
- [Author](#author)

---

## Overview

The **Secure Incident Reporting System (IRS)** is designed for organizations to report, track, and resolve cybersecurity incidents. It provides three levels of access — **User**, **Admin**, and **Super Admin** — each with their own dashboard and feature set.

- Secure JWT-based authentication with refresh tokens
- Role-Based Access Control (RBAC)
- Real-time notifications via AJAX polling
- Analytics with Chart.js (Pie + Bar charts)
- Audit trail logging with IP tracking
- File evidence upload with validation
- CSV and PDF export
- Bulk incident management

---

## Features

### 🔐 Authentication
- Secure login and registration
- JWT Access Tokens (15-minute expiry)
- JWT Refresh Tokens (7-day expiry) stored in database
- Auto token refresh every 13 minutes (silent, no logout)
- Password hashing with `bcrypt` (cost factor 12)
- Session-based token storage with `HttpOnly` cookies
- Failed login attempt logging with IP address

---

### 👤 User Dashboard
- View personal incident reports in a clean table
- Submit new incidents with full form
- Search, filter, and sort incidents by:
  - Status (Open / In Progress / Resolved)
  - Category (Phishing, Malware, Ransomware, etc.)
  - Date
- View full incident detail page
- Real-time notifications — status badge, stat cards, and toast update **instantly** when admin changes a status (no page reload)
- Notification bell badge updates live
- Sidebar notification count updates live
- View all notifications in a dedicated page

---

### 📋 Incident Reporting Form
- Fields:
  - Title
  - Description
  - Category (Dropdown: Phishing, Malware, Ransomware, Unauthorized Access, Data Breach, DDoS, Insider Threat, Other)
  - Priority (Low / Medium / High)
  - Incident Date (date picker, no future dates)
  - Evidence Upload (Screenshots: JPG, PNG | Documents: PDF)
- Form Validation:
  - All required fields enforced (client-side + server-side)
  - File size limit: **5MB maximum**
  - Accepted formats: JPG, PNG, PDF only
  - MIME type + extension double validation
  - Live file preview before submit

---

### 🛡️ Admin Dashboard
- **Analytics Widgets:**
  - Total Incidents count
  - Open Incidents count
  - In Progress count
  - Average Resolution Time (in hours)
- **Charts:**
  - Pie/Doughnut chart — Open vs In Progress vs Resolved
  - Bar chart — Incidents by Category (horizontally scrollable for many categories)
- **Incident Management Table:**
  - View all incidents across all users
  - Filter by Status and Category
  - Sort by Date, Priority, Status
  - Edit incident — update status, priority, assign to admin
  - Status change automatically notifies the incident owner
- **Bulk Actions:**
  - Select multiple incidents via checkboxes
  - Bulk mark as Resolved
  - Bulk assign to an admin
  - Bulk delete (Super Admin only)
- **Export:**
  - Export incidents as CSV (download)
  - Export incidents as PDF (print-ready, opens in new tab)
  - Filter before export by status or category
- **Audit Logs:**
  - View full audit trail
  - Filter by user, action keyword, date range
  - Shows action, IP address, timestamp

---

### 👑 Super Admin Panel
- All Admin features, plus:
- **User Management:**
  - View all users with search and role/status filters
  - Add new users with role assignment
  - Edit existing users (name, email, role, password)
  - Delete users permanently
  - Block / Unblock users (blocked users cannot log in)
  - Cannot block or delete your own account
- **Audit Logs** — full system-wide audit trail

---

### 🔔 Real-Time Notifications
- Polling every **5 seconds** via AJAX
- When admin changes an incident status:
  - **Toast notification** appears instantly (top-right, slides in/out)
  - **Status badge** in the incidents table updates live (no reload)
  - **Stat cards** (Open / In Progress / Resolved counts) update live
  - **Bell icon badge** count updates
  - **Sidebar Notifications** count updates
- Notifications stored in database and shown in Notifications page

---

### 📊 Audit Logging
- Every action is logged:
  - User login / logout
  - Incident created / updated / deleted
  - User created / updated / deleted / blocked
  - Bulk actions
  - CSV/PDF exports
- Each log entry records:
  - Who performed the action (user ID + name)
  - What action was performed
  - Which table and record ID was affected
  - IP address (IPv4 and IPv6 supported)
  - Timestamp

---

### 🎨 UI/UX
- Fully responsive Bootstrap 5 layout
- Collapsible sidebar with toggle button (state saved in localStorage)
- Role-based sidebar navigation
- Color-coded status and priority badges
- Flash messages with auto-dismiss
- Loading spinner on form submit
- Image preview before file upload
- Pagination on all tables

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x |
| Database | MySQL / MariaDB |
| Frontend | HTML5, Bootstrap 5, JavaScript (ES6) |
| Charts | Chart.js 4 |
| Icons | Font Awesome 6 |
| Authentication | JWT (HS256, pure PHP — no Composer) |
| Styling | Custom CSS on Bootstrap 5 |
| AJAX | Fetch API |

---

## Project Structure

```
incident_system/
│
├── config/
│   ├── config.php          # App settings, DB, JWT, upload config
│   └── db.php              # PDO Singleton database connection
│
├── includes/
│   ├── auth_middleware.php  # Route protection by role
│   ├── audit_helper.php     # Audit log writer
│   ├── jwt_helper.php       # Pure PHP JWT encode/decode
│   ├── notification_helper.php  # Send & fetch notifications
│   └── layout/
│       ├── sidebar.php      # Role-based sidebar nav + toggle
│       └── topbar.php       # Top bar with bell badge
│
├── auth/
│   ├── login.php            # Login page + JWT generation
│   ├── register.php         # Registration with password strength
│   ├── logout.php           # Destroys session + refresh token
│   ├── refresh_token.php    # Silent token refresh endpoint
│   └── unauthorized.php     # 403 page
│
├── user/
│   ├── dashboard.php        # User dashboard + live updates
│   ├── submit_incident.php  # Incident reporting form
│   ├── my_incidents.php     # All user incidents with filters
│   ├── view_incident.php    # Single incident detail
│   ├── notifications.php    # All notifications list
│   └── ajax_notifications.php  # Polling endpoint (returns live data)
│
├── admin/
│   ├── dashboard.php        # Admin analytics + charts
│   ├── incidents.php        # All incidents + bulk actions
│   ├── edit_incident.php    # Update status/priority/assign
│   ├── delete_incident.php  # Permanent delete (super admin)
│   ├── audit_logs.php       # Audit trail viewer
│   └── export.php           # CSV + PDF export
│
├── superadmin/
│   ├── dashboard.php        # Super admin overview
│   └── users.php            # Full user management
│
├── assets/
│   ├── css/
│   │   └── style.css        # Global custom styles
│   ├── js/
│   │   └── app.js           # Real-time polling, toasts, helpers
│   └── uploads/             # Evidence file uploads (auto-created)
│
└── index.php                # Entry point — redirects by role
```

---

## Database Schema

### `users`
| Column | Type | Description |
|---|---|---|
| id | INT AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | Full name |
| email | VARCHAR(150) | Unique email |
| password | VARCHAR(255) | bcrypt hash |
| role | ENUM | `user`, `admin`, `superadmin` |
| is_blocked | TINYINT(1) | 0 = active, 1 = blocked |
| created_at | DATETIME | Account creation time |

### `incidents`
| Column | Type | Description |
|---|---|---|
| id | INT AUTO_INCREMENT | Primary key |
| user_id | INT | FK → users |
| assigned_to | INT | FK → users (admin) |
| title | VARCHAR(255) | Incident title |
| description | TEXT | Full description |
| category | ENUM | Phishing, Malware, etc. |
| priority | ENUM | Low, Medium, High |
| status | ENUM | Open, In Progress, Resolved |
| evidence_path | VARCHAR(500) | Uploaded file name |
| incident_date | DATE | When incident occurred |
| resolved_at | DATETIME | When status set to Resolved |
| created_at | DATETIME | Report submission time |

### `notifications`
| Column | Type | Description |
|---|---|---|
| id | INT AUTO_INCREMENT | Primary key |
| user_id | INT | FK → users (recipient) |
| incident_id | INT | FK → incidents |
| message | VARCHAR(500) | Notification text |
| is_read | TINYINT(1) | 0 = unread, 1 = read |
| created_at | DATETIME | When notification was created |

### `audit_logs`
| Column | Type | Description |
|---|---|---|
| id | INT AUTO_INCREMENT | Primary key |
| user_id | INT | FK → users (who acted) |
| action | VARCHAR(100) | e.g. "Created Incident" |
| target_table | VARCHAR(50) | e.g. "incidents" |
| target_id | INT | Row that was affected |
| description | TEXT | Human-readable detail |
| ip_address | VARCHAR(45) | IPv4 or IPv6 |
| created_at | DATETIME | When action happened |

### `refresh_tokens`
| Column | Type | Description |
|---|---|---|
| id | INT AUTO_INCREMENT | Primary key |
| user_id | INT | FK → users |
| token | VARCHAR(512) | JWT refresh token |
| expires_at | DATETIME | Expiry timestamp |

---

## Setup Instructions

### Prerequisites
- XAMPP / WAMP / LAMP (Apache + MySQL + PHP 8.x)
- Browser (Chrome / Firefox recommended)

---

### Step 1 — Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/incident-reporting-system.git
```

Or download as ZIP and extract.

---

### Step 2 — Copy to Server Root

Copy the `incident_system` folder into your server root:

```
# XAMPP (Windows)
C:\xampp\htdocs\incident_system\

# WAMP (Windows)
C:\wamp64\www\incident_system\

# LAMP (Linux)
/var/www/html/incident_system/
```

---

### Step 3 — Create the Database

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **Import** tab
3. Select the file: `incident_system.sql`
4. Click **Go**

The `incident_db` database will be created with all tables and sample data.

---

### Step 4 — Configure the Application

Open `config/config.php` and update if needed:

```php
define('DB_HOST',  'localhost');
define('DB_USER',  'root');
define('DB_PASS',  '');          // ← Add your MySQL password if set
define('DB_NAME',  'incident_db');
define('BASE_URL', 'http://localhost/incident_system');
```

---

### Step 5 — Create Uploads Folder

Create this folder manually if it doesn't exist:

```
incident_system/assets/uploads/
```

Make sure it has **write permissions**:

```bash
# Linux/Mac
chmod 755 assets/uploads/
```

---

### Step 6 — Run the Application

Open your browser and go to:

```
http://localhost/incident_system
```

You will be automatically redirected to the login page.

---

## Default Login Credentials

| Role | Email | Password |
|---|---|---|
| 👑 Super Admin | superadmin@system.com | Password@123 |
| 🛡️ Admin | admin@system.com | Password@123 |
| 👤 User | user@system.com | Password@123 |

> ⚠️ Change these credentials immediately in a production environment.

---

## Role-Based Access

| Feature | User | Admin | Super Admin |
|---|---|---|---|
| Submit Incident | ✅ | ❌ | ❌ |
| View Own Incidents | ✅ | ❌ | ❌ |
| Real-time Notifications | ✅ | ❌ | ❌ |
| View All Incidents | ❌ | ✅ | ✅ |
| Update Incident Status | ❌ | ✅ | ✅ |
| Assign Incidents | ❌ | ✅ | ✅ |
| Bulk Actions | ❌ | ✅ | ✅ |
| Analytics Dashboard | ❌ | ✅ | ✅ |
| Export CSV / PDF | ❌ | ✅ | ✅ |
| View Audit Logs | ❌ | ✅ | ✅ |
| Delete Incidents | ❌ | ❌ | ✅ |
| User Management | ❌ | ❌ | ✅ |
| Block / Unblock Users | ❌ | ❌ | ✅ |
| Assign Roles | ❌ | ❌ | ✅ |

---

## Security Measures

- **JWT Authentication** — stateless tokens with expiry, HS256 signed
- **Refresh Token Rotation** — stored in DB, deleted on logout
- **bcrypt Password Hashing** — cost factor 12
- **No-cache Headers** — protected pages never cached by browser
- **Input Sanitization** — all user input escaped before DB
- **PDO Prepared Statements** — prevents SQL injection
- **MIME + Extension Validation** — file uploads double-checked
- **Role Enforcement** — every page checks role server-side
- **IP Logging** — all actions logged with real IP (proxy-aware)
- **Session Hardening** — HttpOnly, SameSite=Strict cookies
- **Blocked User Check** — blocked users are rejected even with valid token

---

## Author

**Chirag Sompura**
- Software Developer
- Project Stack: PHP + MySQL + Bootstrap 5 + JavaScript

---

> ⭐ If this project helped you, consider giving it a star on GitHub!
