<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $employee_id = intval($_POST['employee_id']);
    $department_id = intval($_POST['department_id']);
    $job_title_id = intval($_POST['job_title_id']);
    $salary_grade_id = intval($_POST['salary_grade_id']);
    $direct_manager_id = intval($_POST['direct_manager_id'] ?? 0);

    $db->prepare("INSERT INTO employee_assignments (employee_id, department_id, job_title_id, salary_grade_id, direct_manager_id) 
                  VALUES (?, ?, ?, ?, ?)")
       ->execute([$employee_id, $department_id, $job_title_id, $salary_grade_id, $direct_manager_id ?: null]);

    $message = 'تم تسكين الموظف بنجاح في الهيكل الإداري';
    logActivity('تسكين موظف في الهيكل', 'موظف ID: ' . $employee_id);
}

$employees = $db->query("SELECT id, name_ar, employee_number FROM employees WHERE status = 'active' ORDER BY name_ar")->fetchAll();
$departments = $db->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$job_titles = $db->query("SELECT * FROM job_titles ORDER BY title")->fetchAll();
$salary_grades = $db->query("SELECT * FROM salary_grades ORDER BY grade, category")->fetchAll();
$assignments = $db->query("
    SELECT a.*, 
           e.name_ar as emp_name, e.employee_number,
           d.name as dept_name,
           j.title as job_title,
           sg.grade || '-' || sg.category as grade_cat,
           sg.base_salary,
           m.name_ar as manager_name
    FROM employee_assignments a
    LEFT JOIN employees e ON a.employee_id = e.id
    LEFT JOIN departments d ON a.department_id = d.id
    LEFT JOIN job_titles j ON a.job_title_id = j.id
    LEFT JOIN salary_grades sg ON a.salary_grade_id = sg.id
    LEFT JOIN employees m ON a.direct_manager_id = m.id
    ORDER BY a.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسكين الهيكل الإداري | نظام HR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-success"><div class="container"><a class="navbar-brand" href="dashboard.php">نظام HR - تسكين الموظفين</a></div></nav>

<div class="container py-4">
    <h3>تسكين الهيكل الإداري (ربط الموظف بالقسم + المسمى + الدرجة)</h3>
    
    <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

    <!-- نموذج التسكين -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">ربط موظف جديد بالهيكل</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">الموظف</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">اختر موظف</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= $e['name_ar'] ?> (<?= $e['employee_number'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">القسم / الدائرة</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">اختر القسم</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المسمى الوظيفي</label>
                        <select name="job_title_id" class="form-select" required>
                            <option value="">اختر المسمى</option>
                            <?php foreach ($job_titles as $j): ?>
                                <option value="<?= $j['id'] ?>"><?= $j['title'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الدرجة والفئة (سلم الأجور)</label>
                        <select name="salary_grade_id" class="form-select" required>
                            <option value="">اختر الدرجة</option>
                            <?php foreach ($salary_grades as $sg): ?>
                                <option value="<?= $sg['id'] ?>"><?= $sg['grade'] ?>-<?= $sg['category'] ?> (<?= number_format($sg['base_salary'], 2) ?> $)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الرئيس المباشر (اختياري)</label>
                        <select name="direct_manager_id" class="form-select">
                            <option value="">-- بدون رئيس مباشر --</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= $e['name_ar'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" name="assign" class="btn btn-info w-100">تسكين الموظف في الهيكل</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- قائمة التسكينات -->
    <h5>التسكينات الحالية</h5>
    <table class="table table-striped table-bordered">
        <thead class="table-success">
            <tr>
                <th>الموظف</th>
                <th>القسم</th>
                <th>المسمى الوظيفي</th>
                <th>الدرجة</th>
                <th>الراتب</th>
                <th>الرئيس المباشر</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $a): ?>
            <tr>
                <td><strong><?= $a['emp_name'] ?></strong><br><small><?= $a['employee_number'] ?></small></td>
                <td><?= $a['dept_name'] ?: '—' ?></td>
                <td><?= $a['job_title'] ?: '—' ?></td>
                <td><span class="badge bg-primary"><?= $a['grade_cat'] ?: '—' ?></span></td>
                <td class="fw-bold text-success"><?= $a['base_salary'] ? number_format($a['base_salary'], 2) . ' $' : '—' ?></td>
                <td><?= $a['manager_name'] ?: '—' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($assignments)): ?>
            <tr><td colspan="6" class="text-center text-muted">لا توجد تسكينات بعد</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>