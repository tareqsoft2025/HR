<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$message = '';

// إضافة قسم جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');
    $head_id = intval($_POST['head_id'] ?? 0);

    if ($name) {
        $db->prepare("INSERT INTO departments (name, description, head_id) VALUES (?, ?, ?)")
           ->execute([$name, $description, $head_id ?: null]);
        $message = 'تم إضافة القسم بنجاح';
        logActivity('إضافة قسم إداري', $name);
    }
}

// إضافة مرؤوسين لقسم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subordinates'])) {
    $dept_id = intval($_POST['department_id']);
    $sub_ids = $_POST['subordinate_ids'] ?? [];

    foreach ($sub_ids as $emp_id) {
        if ($emp_id > 0) {
            $db->prepare("INSERT OR IGNORE INTO department_employees (department_id, employee_id, role_in_dept) VALUES (?, ?, 'member')")
               ->execute([$dept_id, $emp_id]);
        }
    }
    $message = 'تم إضافة المرؤوسين بنجاح';
    logActivity('إضافة مرؤوسين لقسم', 'قسم ID: ' . $dept_id);
}

$departments = $db->query("SELECT d.*, e.name_ar as head_name FROM departments d LEFT JOIN employees e ON d.head_id = e.id ORDER BY d.id")->fetchAll();
$employees = $db->query("SELECT id, name_ar, employee_number FROM employees WHERE status = 'active' ORDER BY name_ar")->fetchAll();

// دالة لجلب المرؤوسين
function getSubordinates($db, $dept_id) {
    $stmt = $db->prepare("SELECT e.id, e.name_ar, e.employee_number FROM department_employees de 
                          JOIN employees e ON de.employee_id = e.id 
                          WHERE de.department_id = ?");
    $stmt->execute([$dept_id]);
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الهيكل الإداري | جمعية أصدقاء البيئة الفلسطينية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .tree { list-style: none; padding-left: 25px; }
        .tree li { margin: 6px 0; position: relative; }
        .tree li:before { content: "├─ "; color: #2e7d32; font-weight: bold; }
        .tree li:last-child:before { content: "└─ "; }
        .dept-card { border-left: 6px solid #2e7d32; }
        .head-badge { background: #1b5e20; color: white; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-tree-fill me-2"></i>جمعية أصدقاء البيئة - الهيكل الإداري</a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-diagram-3 me-2"></i>الهيكل الإداري (شجرة السلطات)</h3>
        <a href="dashboard.php" class="btn btn-outline-success">العودة</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <!-- إضافة قسم جديد -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <strong>إضافة قسم أو دائرة جديدة</strong>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="name" class="form-control" placeholder="اسم القسم / الدائرة" required>
                </div>
                <div class="col-md-4">
                    <select name="head_id" class="form-select">
                        <option value="">-- اختر رئيس القسم --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= $emp['name_ar'] ?> (<?= $emp['employee_number'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="description" class="form-control" placeholder="وصف مختصر">
                </div>
                <div class="col-md-1">
                    <button type="submit" name="add_department" class="btn btn-primary w-100">+</button>
                </div>
            </form>
        </div>
    </div>

    <!-- عرض الهيكل الشجري -->
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <strong>شجرة الهيكل الإداري</strong>
        </div>
        <div class="card-body">
            <?php if (empty($departments)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-diagram-3 fs-1"></i>
                    <p class="mt-3">لا توجد أقسام بعد. ابدأ بإضافة قسم جديد.</p>
                </div>
            <?php else: ?>
                <ul class="tree">
                    <?php foreach ($departments as $dept): 
                        $subs = getSubordinates($db, $dept['id']);
                    ?>
                    <li>
                        <div class="dept-card card mb-2 p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-1"><?= $dept['name'] ?></h5>
                                    <?php if ($dept['head_name']): ?>
                                        <span class="badge head-badge">
                                            <i class="bi bi-person-badge me-1"></i>رئيس القسم: <?= $dept['head_name'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- زر إضافة مرؤوسين -->
                                <button class="btn btn-sm btn-outline-success" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#addSubsModal<?= $dept['id'] ?>">
                                    <i class="bi bi-people me-1"></i> إضافة مرؤوسين
                                </button>
                            </div>
                            
                            <?php if (!empty($subs)): ?>
                                <div class="mt-2 ps-3 border-start border-success">
                                    <small class="text-muted">المرؤوسين (<?= count($subs) ?>):</small><br>
                                    <?php foreach ($subs as $sub): ?>
                                        <span class="badge bg-light text-dark me-1"><?= $sub['name_ar'] ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>

                    <!-- مودال إضافة مرؤوسين -->
                    <div class="modal fade" id="addSubsModal<?= $dept['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title">إضافة مرؤوسين لـ: <?= $dept['name'] ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="department_id" value="<?= $dept['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label">اختر المرؤوسين (يمكن اختيار أكثر من واحد)</label>
                                            <select name="subordinate_ids[]" class="form-select" multiple size="8">
                                                <?php foreach ($employees as $emp): ?>
                                                    <option value="<?= $emp['id'] ?>"><?= $emp['name_ar'] ?> (<?= $emp['employee_number'] ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="text-muted">اضغط Ctrl لاختيار أكثر من موظف</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                        <button type="submit" name="add_subordinates" class="btn btn-success">إضافة المرؤوسين</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>