<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$employee_id = intval($_GET['id'] ?? 0);

if ($employee_id <= 0) {
    die('رقم الموظف غير صحيح');
}

// جلب بيانات الموظف مع المسمى والقسم
$stmt = $db->prepare("
    SELECT e.*, 
           jt.title as job_title,
           d.name as department_name,
           a.direct_manager_id,
           m.name_ar as manager_name
    FROM employees e
    LEFT JOIN job_titles jt ON e.job_title_id = jt.id
    LEFT JOIN employee_assignments a ON e.id = a.employee_id
    LEFT JOIN departments d ON a.department_id = d.id
    LEFT JOIN employees m ON a.direct_manager_id = m.id
    WHERE e.id = ?
");
$stmt->execute([$employee_id]);
$emp = $stmt->fetch();

if (!$emp) {
    die('الموظف غير موجود');
}

// حساب الخبرة داخل المؤسسة
$years_in_company = 0;
if ($emp['appointment_date']) {
    $appointment = new DateTime($emp['appointment_date']);
    $now = new DateTime();
    $diff = $appointment->diff($now);
    $years_in_company = $diff->y + ($diff->m / 12);
}

// حساب الخبرة الخارجية (من السيرة الذاتية)
$external_experience = 0;
$exp_stmt = $db->prepare("SELECT from_date, to_date FROM employee_experiences WHERE employee_id = ?");
$exp_stmt->execute([$employee_id]);
$experiences = $exp_stmt->fetchAll();

foreach ($experiences as $exp) {
    if ($exp['from_date'] && $exp['to_date']) {
        $from = new DateTime($exp['from_date']);
        $to = new DateTime($exp['to_date']);
        $external_experience += $from->diff($to)->y;
    }
}

// حالة الموظف (تحت التعيين)
$is_probation = false;
$probation_note = '';
if ($emp['appointment_date']) {
    $appointment = new DateTime($emp['appointment_date']);
    $now = new DateTime();
    $months_diff = ($now->diff($appointment)->y * 12) + $now->diff($appointment)->m;
    
    if ($months_diff < 3) {
        $is_probation = true;
        $probation_note = "الموظف تحت التعيين - يستحق التقييم في الشهر الثالث من تاريخ التعيين";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الملف الوظيفي - <?= $emp['name_ar'] ?> | نظام HR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .file-card { border-left: 5px solid #2e7d32; }
        .probation-badge { background: #ffc107; color: #000; }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-success mb-1">الملف الوظيفي</h3>
            <h5 class="text-muted"><?= $emp['name_ar'] ?> (<?= $emp['employee_number'] ?>)</h5>
        </div>
        <div>
            <a href="employee_profile.php?id=<?= $employee_id ?>" class="btn btn-outline-success">
                <i class="bi bi-person-vcard me-2"></i> عرض السيرة الذاتية
            </a>
        </div>
    </div>

    <!-- حالة الموظف -->
    <?php if ($is_probation): ?>
    <div class="alert alert-warning d-flex align-items-center mb-4">
        <i class="bi bi-exclamation-triangle fs-4 me-3"></i>
        <div>
            <strong>الموظف تحت التعيين</strong><br>
            <small><?= $probation_note ?></small>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- البيانات الأساسية -->
        <div class="col-lg-6">
            <div class="card file-card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>البيانات الأساسية</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="fw-bold text-muted" width="140">رقم الموظف</td>
                            <td><strong><?= $emp['employee_number'] ?></strong></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">رقم الهوية</td>
                            <td><?= $emp['id_number'] ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">المسمى الوظيفي</td>
                            <td><span class="badge bg-primary fs-6"><?= $emp['job_title'] ?: 'غير محدد' ?></span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">القسم / الدائرة</td>
                            <td><?= $emp['department_name'] ?: 'غير محدد' ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">الرئيس المباشر</td>
                            <td><?= $emp['manager_name'] ?: 'غير محدد' ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-muted">البريد الإلكتروني</td>
                            <td><?= $emp['email'] ?? 'غير مسجل' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- الخبرة -->
        <div class="col-lg-6">
            <div class="card file-card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>الخبرة العملية</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <div class="display-6 fw-bold text-success"><?= number_format($years_in_company, 1) ?></div>
                            <div class="text-muted">سنوات الخبرة داخل المؤسسة</div>
                            <small class="text-muted">من تاريخ التعيين: <?= $emp['appointment_date'] ?: 'غير محدد' ?></small>
                        </div>
                        <div class="col-6">
                            <div class="display-6 fw-bold text-primary"><?= number_format($external_experience, 1) ?></div>
                            <div class="text-muted">سنوات الخبرة خارج المؤسسة</div>
                            <small class="text-muted">(من السيرة الذاتية)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ملف العقوبات -->
    <div class="card file-card shadow-sm mb-4">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-exclamation-octagon me-2"></i>ملف العقوبات</h5>
            <span class="badge bg-light text-dark">تطوير قريباً</span>
        </div>
        <div class="card-body text-center py-5">
            <i class="bi bi-tools fs-1 text-muted"></i>
            <p class="text-muted mt-3 mb-0">هذه الميزة قيد التطوير (التنبيهات والإنذارات)</p>
        </div>
    </div>

    <!-- ملف التقييم -->
    <div class="card file-card shadow-sm mb-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-star me-2"></i>ملف التقييم</h5>
            <span class="badge bg-light text-dark">تطوير قريباً</span>
        </div>
        <div class="card-body text-center py-5">
            <i class="bi bi-tools fs-1 text-muted"></i>
            <p class="text-muted mt-3 mb-0">مسؤول القسم يقوم بتقييم المرؤوسين (قيد التطوير)</p>
        </div>
    </div>

    <!-- معاملات الموظف -->
    <div class="card file-card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-folder2-open me-2"></i>معاملات الموظف</h5>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="bi bi-plus-circle me-1"></i> إضافة معاملة
            </button>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                يمكنك إضافة معاملات ورفع ملفات خاصة بالموظف (مثل: إجازات، قرارات، شهادات...).
            </div>
            
            <div class="text-center py-4 text-muted">
                <i class="bi bi-folder fs-1"></i>
                <p class="mt-3 mb-0">لا توجد معاملات مسجلة بعد</p>
            </div>
        </div>
    </div>

    <!-- الملفات الخاصة بالموظف -->
    <div class="card file-card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-files me-2"></i>الملفات الخاصة بالموظف</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center">
                        <i class="bi bi-file-earmark-pdf fs-2 text-danger"></i>
                        <div class="mt-2">السيرة الذاتية</div>
                        <button class="btn btn-sm btn-outline-primary mt-2">عرض</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center">
                        <i class="bi bi-file-earmark-image fs-2 text-primary"></i>
                        <div class="mt-2">صورة الهوية</div>
                        <button class="btn btn-sm btn-outline-primary mt-2">عرض</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center">
                        <i class="bi bi-file-earmark-text fs-2 text-success"></i>
                        <div class="mt-2">المؤهلات العلمية</div>
                        <button class="btn btn-sm btn-outline-primary mt-2">عرض</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال إضافة معاملة -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">إضافة معاملة جديدة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label class="form-label">نوع المعاملة</label>
                        <select class="form-select">
                            <option>إجازة سنوية</option>
                            <option>إجازة مرضية</option>
                            <option>قرار إداري</option>
                            <option>شهادة تقدير</option>
                            <option>أخرى</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التاريخ</label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الملاحظات</label>
                        <textarea class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رفع ملف</label>
                        <input type="file" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary">حفظ المعاملة</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>