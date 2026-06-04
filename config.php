<?php
// config.php - نظام إدارة الموارد البشرية - جمعية أصدقاء البيئة الفلسطينية
session_start();

define('DB_FILE', __DIR__ . '/hr_system.db');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// إنشاء مجلد الرفع إذا لم يكن موجوداً
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . DB_FILE);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        createTables($db);
    }
    return $db;
}

function createTables($db) {
    // جدول المستخدمين
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        full_name TEXT,
        role TEXT NOT NULL DEFAULT 'hr_officer',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    // جدول الموظفين الرئيسي
    $db->exec("CREATE TABLE IF NOT EXISTS employees (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_number TEXT UNIQUE,
        name_ar TEXT NOT NULL,
        name_en TEXT,
        id_number TEXT UNIQUE NOT NULL,
        birth_date TEXT,
        gender TEXT,
        marital_status TEXT,
        birth_place TEXT,
        governorate TEXT,
        city TEXT,
        neighborhood TEXT,
        nearest_landmark TEXT,
        family_members INTEGER DEFAULT 0,
        mobile TEXT,
        mobile_alt TEXT,
        appointment_date TEXT,
        job_title_id INTEGER,           -- المسمى الوظيفي (من جدول job_titles)
        status TEXT DEFAULT 'pending_approval',
        created_by INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    // المؤهلات العلمية (يمكن إضافة أكثر من واحد)
    $db->exec("CREATE TABLE IF NOT EXISTS employee_qualifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_id INTEGER,
        degree TEXT,
        specialization TEXT,
        university TEXT,
        graduation_date TEXT,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    // بيانات التدريب
    $db->exec("CREATE TABLE IF NOT EXISTS employee_trainings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_id INTEGER,
        course_name TEXT,
        organizer TEXT,
        hours INTEGER DEFAULT 0,
        training_date TEXT,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    // الخبرات العملية
    $db->exec("CREATE TABLE IF NOT EXISTS employee_experiences (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_id INTEGER,
        institution TEXT,
        position TEXT,
        from_date TEXT,
        to_date TEXT,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    // اللغات
    $db->exec("CREATE TABLE IF NOT EXISTS employee_languages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_id INTEGER,
        language TEXT,
        level TEXT,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    // المهارات
    $db->exec("CREATE TABLE IF NOT EXISTS employee_skills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_id INTEGER,
        skill TEXT,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    // سجل المقابلات والموافقات (Workflow)
    $db->exec("CREATE TABLE IF NOT EXISTS employee_approvals (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_id INTEGER,
        approver_role TEXT,
        status TEXT,
        notes TEXT,
        file_path TEXT,
        approved_by INTEGER,
        approved_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");

    // سجل الحركات (Logging - IP + User + Time)
    $db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        username TEXT,
        action TEXT,
        details TEXT,
        ip_address TEXT,
        user_agent TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    // جدول الأقسام والهيكل الإداري
    $db->exec("CREATE TABLE IF NOT EXISTS departments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        head_id INTEGER,           -- رئيس القسم (من جدول الموظفين)
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    // جدول المسميات الوظيفية
    $db->exec("CREATE TABLE IF NOT EXISTS job_titles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL UNIQUE,
        description TEXT
    )");

    // جدول سلم الأجور (الدرجات والفئات)
    $db->exec("CREATE TABLE IF NOT EXISTS salary_grades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        grade TEXT NOT NULL,           -- مثل G8
        category TEXT NOT NULL,        -- مثل s1, s2, s3, s4, s5
        min_salary REAL,
        max_salary REAL,
        base_salary REAL
    )");

    // جدول تسكين الموظفين (ربط الموظف بالقسم + المسمى + الدرجة + الفئة)
    $db->exec("CREATE TABLE IF NOT EXISTS employee_assignments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        employee_id INTEGER NOT NULL,
        department_id INTEGER,
        job_title_id INTEGER,
        salary_grade_id INTEGER,
        direct_manager_id INTEGER,     -- الرئيس المباشر
        assigned_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        FOREIGN KEY (department_id) REFERENCES departments(id),
        FOREIGN KEY (job_title_id) REFERENCES job_titles(id),
        FOREIGN KEY (salary_grade_id) REFERENCES salary_grades(id)
    )");

    // جدول المرؤوسين في كل قسم (لدعم الهيكل الشجري)
    $db->exec("CREATE TABLE IF NOT EXISTS department_employees (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        department_id INTEGER NOT NULL,
        employee_id INTEGER NOT NULL,
        role_in_dept TEXT DEFAULT 'member',
        assigned_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
        UNIQUE(department_id, employee_id)
    )");

    // جدول سلم الأجور المتقدم (لإضافة شرائح جديدة)
    $db->exec("CREATE TABLE IF NOT EXISTS salary_scale_entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        year INTEGER,
        grade TEXT,
        level TEXT,
        s1 REAL, s2 REAL, s3 REAL, s4 REAL, s5 REAL, s6 REAL, s7 REAL, s8 REAL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    // جدول جلسات المقابلات
    $db->exec("CREATE TABLE IF NOT EXISTS interview_sessions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        interview_code TEXT UNIQUE NOT NULL,
        interview_date TEXT,
        job_title_id INTEGER,
        created_by INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_title_id) REFERENCES job_titles(id)
    )");

    // جدول المرشحين
    $db->exec("CREATE TABLE IF NOT EXISTS candidates (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_id INTEGER,
        name_ar TEXT NOT NULL,
        name_en TEXT,
        id_number TEXT,
        mobile TEXT,
        exam_score REAL DEFAULT 0,
        interview_score REAL DEFAULT 0,
        total_score REAL DEFAULT 0,
        average_score REAL DEFAULT 0,
        rank INTEGER,
        status TEXT DEFAULT 'pending',
        FOREIGN KEY (session_id) REFERENCES interview_sessions(id) ON DELETE CASCADE
    )");

    // جدول عروض الوظائف
    $db->exec("CREATE TABLE IF NOT EXISTS job_offers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        candidate_id INTEGER,
        position TEXT,
        salary REAL,
        start_date TEXT,
        offer_date TEXT DEFAULT CURRENT_TIMESTAMP,
        status TEXT DEFAULT 'sent',
        notes TEXT,
        FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
    )");

    // إنشاء مستخدمين افتراضيين + بيانات أولية
    createDefaultUsers($db);
    createInitialData($db);
}

function createDefaultUsers($db) {
    $users = [
        ['admin', 'admin123', 'مدير النظام', 'admin'],
        ['hr_officer', 'hr123', 'مسؤول الموارد البشرية', 'hr_officer'],
        ['committee', 'committee123', 'لجنة المقابلات', 'interview_committee'],
        ['director', 'director123', 'المدير التنفيذي', 'executive_director']
    ];

    foreach ($users as $u) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$u[0]]);
        if ($stmt->fetchColumn() == 0) {
            $hash = password_hash($u[1], PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)")
               ->execute([$u[0], $hash, $u[2], $u[3]]);
        }
    }
}

function logActivity($action, $details = '') {
    $db = getDB();
    $userId = $_SESSION['user_id'] ?? 0;
    $username = $_SESSION['username'] ?? 'Guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $db->prepare("INSERT INTO activity_logs (user_id, username, action, details, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?)")
       ->execute([$userId, $username, $action, $details, $ip, $userAgent]);
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function hasRole($allowedRoles) {
    if (!isset($_SESSION['role'])) return false;
    return in_array($_SESSION['role'], (array)$allowedRoles);
}

function generateEmployeeNumber() {
    $db = getDB();
    $year = date('Y');
    
    // البحث عن آخر رقم موظف مستخدم في السنة الحالية
    $stmt = $db->prepare("SELECT employee_number FROM employees 
                          WHERE strftime('%Y', created_at) = ? 
                          ORDER BY id DESC LIMIT 1");
    $stmt->execute([$year]);
    $lastCode = $stmt->fetchColumn();
    
    if ($lastCode) {
        $lastNumber = intval(substr($lastCode, -4));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return 'ENV-' . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

function generateInterviewCode() {
    $db = getDB();
    $year = date('Y');
    
    // البحث عن آخر كود مستخدم في السنة الحالية
    $stmt = $db->prepare("SELECT interview_code FROM interview_sessions 
                          WHERE strftime('%Y', created_at) = ? 
                          ORDER BY id DESC LIMIT 1");
    $stmt->execute([$year]);
    $lastCode = $stmt->fetchColumn();
    
    if ($lastCode) {
        // استخراج الرقم من آخر كود
        $lastNumber = intval(substr($lastCode, -4));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return 'INT-' . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

// إنشاء بيانات أولية (مسميات + درجات + أقسام تجريبية)
function createInitialData($db) {
    // المسميات الوظيفية
    $titles = [
        'مدير تنفيذي', 'رئيس قسم', 'مسؤول برنامج', 'منسق مشاريع', 
        'محاسب', 'مسؤول موارد بشرية', 'مهندس بيئي', 'باحث ميداني'
    ];
    foreach ($titles as $title) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM job_titles WHERE title = ?");
        $stmt->execute([$title]);
        if ($stmt->fetchColumn() == 0) {
            $db->prepare("INSERT INTO job_titles (title) VALUES (?)")->execute([$title]);
        }
    }

    // سلم الأجور (درجات + 5 فئات لكل درجة)
    $grades = ['G5', 'G6', 'G7', 'G8', 'G9', 'G10'];
    $categories = ['s1', 's2', 's3', 's4', 's5'];
    
    foreach ($grades as $grade) {
        foreach ($categories as $cat) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM salary_grades WHERE grade = ? AND category = ?");
            $stmt->execute([$grade, $cat]);
            if ($stmt->fetchColumn() == 0) {
                $base = 2500 + (array_search($grade, $grades) * 400) + (array_search($cat, $categories) * 120);
                $db->prepare("INSERT INTO salary_grades (grade, category, min_salary, max_salary, base_salary) 
                              VALUES (?, ?, ?, ?, ?)")
                   ->execute([$grade, $cat, $base - 200, $base + 300, $base]);
            }
        }
    }

    // قسم تجريبي
    $stmt = $db->prepare("SELECT COUNT(*) FROM departments WHERE name = 'الإدارة التنفيذية'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO departments (name, description) VALUES ('الإدارة التنفيذية', 'الإدارة العليا للجمعية')");
    }
}
?>