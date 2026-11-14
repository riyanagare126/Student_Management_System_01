# EduManage - Student Management System (SMS)

A modern, full-featured **Student Management System** built with **PHP 8+**, **MySQL / PDO**, **Bootstrap 5**, and **JavaScript**. Designed for academic institutions, schools, and colleges with a multi-role architecture (**Admin**, **Teacher**, and **Student**).

---

## 🌟 Key Features

### 1. Multi-Role Authentication & Access Control
- **Secure Password Hashing:** Uses PHP `password_hash()` and `password_verify()` with `PASSWORD_DEFAULT` (bcrypt).
- **Role-Based Access Control (RBAC):** Strict session guards prevent unauthorized URL access; users are routed to their designated dashboards.
- **Session Fixation & CSRF Protection:** Session IDs are regenerated upon login; CSRF tokens guard state-changing POST requests.
- **1-Click Demo Buttons:** Instant autofill buttons on the login screen for rapid testing without manual typing.

### 2. Admin Dashboard & Operations
- **Key Metrics Overview:** Total enrolled students, active courses/classes, faculty members, total billed revenue, and pending dues.
- **Today's Attendance Snapshot:** Real-time ratio bar and counters for *Present*, *Absent*, *Late*, and *Excused*.
- **Student CRUD Management:**
  - Enroll students with roll numbers, personal details, contact info, and custom portrait uploads.
  - Optional 1-switch automated portal login creation for new students.
  - Search by student name, roll number, email, or phone.
  - Class/Batch filtering with pagination.
  - 360-degree Student Profile showing bio, attendance records, exam marks, and fee invoices.
- **Class / Course Management:** Create, edit, and delete curriculum grades, sections, and room assignments.
- **Faculty / Teacher Management:** Register teachers with departments and assign portal login accounts.
- **Daily Roll Call (Attendance):**
  - Select class and date.
  - Interactive radio toggles for *Present*, *Absent*, *Late*, *Excused*.
  - "Mark All Present" 1-click helper.
  - Remarks input per student.
- **Marks & Examination System:**
  - Record evaluations for Midterms, Finals, Quizzes, or Unit Tests.
  - Automated Percentage, GPA (4.0 scale), and Grade calculation (A+, A, B, C, D, F).
  - Printable official Academic Report Card with institution header, grades, remarks, and signature lines.
- **Fee Management & Invoicing:**
  - Issue fee invoices (Tuition, Lab, Library, etc.).
  - 1-click "Mark as Paid" with automated receipt number generation (`REC-YYYY-XXXXX`).
  - Printable official Payment Receipt with school watermark, stamp, and authorized signature.

### 3. Student Portal
- **Personal Dashboard:** Real-time personal attendance percentage, cumulative GPA, and fee due status.
- **My Profile:** View legal registration details, roll ID, and class assignments.
- **My Attendance:** Calendar log of all sessions, attendance ratio, and absence tracking.
- **My Marks & Results:** Subject breakdown, grades, and direct print access to official Academic Report Card.
- **My Fees & Receipts:** Payment history, outstanding invoices, and printable receipts.

### 4. Teacher Portal
- Overview of active classes and enrolled students.
- Fast roll call attendance marking.
- Examination marks and score recording.

---

## 🗂️ Project Directory Structure

```
student_management_system/
├── config/
│   └── db.php                  # Database connection (PDO) & app constants
├── includes/
│   ├── auth.php                # Authentication functions, RBAC, CSRF & grade helper
│   ├── header.php              # Global HTML head, fonts, and Bootstrap CSS
│   ├── navbar.php              # Top bar with date and profile dropdown
│   ├── sidebar.php             # Role-aware navigation sidebar
│   └── footer.php              # Global scripts and layout closing
├── assets/
│   ├── css/
│   │   └── style.css           # Modern theme styling, badge styles & print rules
│   ├── js/
│   │   └── main.js             # Form validation, live table filter & print triggers
│   └── uploads/
│       └── students/           # Upload folder for student photos (with default avatar)
│           ├── default-avatar.svg
│           ├── .htaccess       # Blocks PHP execution in uploads
│           └── index.php       # Directory listing protection
├── admin/
│   ├── dashboard.php           # Admin analytics dashboard
│   ├── students.php            # Student list with search, filter & pagination
│   ├── student_add.php         # Student registration form with photo upload
│   ├── student_edit.php        # Edit student details and avatar
│   ├── student_view.php        # 360-degree student profile
│   ├── classes.php             # Class / course management
│   ├── attendance.php          # Daily attendance marking sheet
│   ├── marks.php               # Marks entry & grade records
│   ├── report_card.php         # Printable official student report card
│   ├── fees.php                # Fee management & invoicing
│   ├── fee_receipt.php         # Printable official fee receipt
│   └── teachers.php            # Faculty management
├── student/
│   ├── dashboard.php           # Student overview portal
│   ├── profile.php             # Student personal details
│   ├── attendance.php          # Student attendance history
│   ├── marks.php               # Student marks and report card
│   └── fees.php                # Student fee status and receipts
├── teacher/
│   ├── dashboard.php           # Teacher overview
│   ├── attendance.php          # Teacher attendance marking
│   └── marks.php               # Teacher marks entry
├── index.php                   # Entry router
├── login.php                   # Split-screen login with 1-click demo buttons
├── logout.php                  # Secure session termination
├── database.sql                # Full database schema and sample data
└── README.md                   # Setup guide and documentation
```

---

## 🚀 Step-by-Step Setup Guide (XAMPP / WAMP)

### Step 1: Copy Project to Server Root
1. Copy the `student_management_system` folder into your web server document root:
   - **XAMPP (Windows):** `C:\xampp\htdocs\student_management_system`
   - **WAMP (Windows):** `C:\wamp64\www\student_management_system`
   - **MAMP (Mac):** `/Applications/MAMP/htdocs/student_management_system`
   - **Linux (Apache):** `/var/www/html/student_management_system`

### Step 2: Start Services
- Launch **XAMPP Control Panel** (or WAMP).
- Click **Start** for **Apache** and **MySQL** (both should turn green).

### Step 3: Import Database
1. Open your browser and go to [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Click on the **Import** tab in the top navigation bar.
3. Click **Choose File** / **Browse** and select `database.sql` from the `student_management_system/` project directory.
4. Click **Go** / **Import** at the bottom of the page.
5. The database `student_management_db` will be created automatically with all 7 tables, foreign keys, and complete sample seed data.

### Step 4: Verify Database Connection (Optional)
The default database settings in `config/db.php` match standard XAMPP/WAMP installations:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'student_management_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // default in XAMPP is empty
```
If your MySQL server has a root password, open `config/db.php` and update `DB_PASS`.

### Step 5: Launch the Application
Open your browser and navigate to:
```
http://localhost/student_management_system/
```

---

## 🔑 Default Login Credentials

You can use the **1-Click Quick Demo Login buttons** on the login page or type the credentials manually:

| Role | Username | Password | Access Rights |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` | Full access to students, classes, faculty, attendance, marks, fees & receipts |
| **Teacher** | `teacher1` | `teacher123` | Can mark attendance and enter subject marks |
| **Student (1)** | `student1` | `student123` | Alexander Hayes: View profile, attendance, marks, report card & fee receipts |
| **Student (2)** | `student2` | `student123` | Sophia Ramirez: View profile, attendance, marks & unpaid fee invoice |
| **Student (3)** | `student3` | `student123` | Ethan Miller: View profile, CS marks & cleared laboratory fee |

---

## 🔒 Security Best Practices Implemented

1. **SQL Injection Prevention:** 100% of user-supplied data in queries passes through PDO prepared statements with parameter binding (`$stmt->prepare()` and `$stmt->execute()`).
2. **Password Security:** Passwords hashed with `PASSWORD_DEFAULT` (bcrypt). Plain passwords used in migrations are automatically and transparently upgraded to bcrypt on first login.
3. **Session Hijacking & Fixation Defense:** `session_regenerate_id(true)` is called upon login; sessions are cleared cleanly on logout.
4. **Cross-Site Scripting (XSS) Prevention:** All dynamic outputs are sanitized using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
5. **Cross-Site Request Forgery (CSRF):** State-changing POST forms carry random CSRF tokens verified on submission.
6. **File Upload Hardening:** Strict extension checking (JPG, JPEG, PNG, WEBP), file size limit (2MB), unique randomized filenames (`bin2hex(random_bytes(4))`), and `.htaccess` rule disallowing script execution in `assets/uploads/`.
