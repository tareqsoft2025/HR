<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$employee_id = intval($_GET['id'] ?? 0);

if ($employee_id <= 0) {
    die('رقم الموظف غير صحيح');
}

// جلب بيانات الموظف
$stmt = $db->prepare("SELECT e.*, jt.title as job_title 
                      FROM employees e 
                      LEFT JOIN job_titles jt ON e.job_title_id = jt.id 
                      WHERE e.id = ?");
$stmt->execute([$employee_id]);
$emp = $stmt->fetch();

if (!$emp) {
    die('الموظف غير موجود');
}

// جلب البيانات الفرعية
$qualifications = $db->prepare("SELECT * FROM employee_qualifications WHERE employee_id = ?");
$qualifications->execute([$employee_id]);
$quals = $qualifications->fetchAll();

$trainings = $db->prepare("SELECT * FROM employee_trainings WHERE employee_id = ?");
$trainings->execute([$employee_id]);
$trains = $trainings->fetchAll();

$experiences = $db->prepare("SELECT * FROM employee_experiences WHERE employee_id = ?");
$experiences->execute([$employee_id]);
$exps = $experiences->fetchAll();

$languages = $db->prepare("SELECT * FROM employee_languages WHERE employee_id = ?");
$languages->execute([$employee_id]);
$langs = $languages->fetchAll();

$skills = $db->prepare("SELECT * FROM employee_skills WHERE employee_id = ?");
$skills->execute([$employee_id]);
$sks = $skills->fetchAll();

// حساب الإحصائيات
$total_training_hours = 0;
foreach ($trains as $t) $total_training_hours += $t['hours'];

$years_experience = 0;
foreach ($exps as $e) {
    if ($e['from_date'] && $e['to_date']) {
        $from = new DateTime($e['from_date']);
        $to = new DateTime($e['to_date']);
        $years_experience += $from->diff($to)->y;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>السيرة الذاتية - <?= $emp['name_ar'] ?> | جمعية أصدقاء البيئة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .cv-header { background: linear-gradient(135deg, #2e7d32, #1b5e20); color: white; }
        .stat-box { background: #e8f5e9; border-radius: 12px; padding: 15px; text-align: center; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">نظام HR - جمعية أصدقاء البيئة الفلسطينية</a>
    </div>
</nav>

<div class="container py-4">
    <!-- Header -->
    <div class="cv-header p-4 rounded mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1"><?= $emp['name_ar'] ?></h2>
                <h5 class="mb-0 opacity-75"><?= $emp['name_en'] ?: '—' ?></h5>
            </div>
            <div class="text-end">
                <div class="badge bg-light text-dark fs-6">الرقم الوظيفي: <?= $emp['employee_number'] ?></div><br>
                <div class="badge bg-light text-dark fs-6 mt-1">رقم الهوية: <?= $emp['id_number'] ?></div>
            </div>
        </div>
    </div>

    <!-- إحصائيات مميزة -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-box">
                <i class="bi bi-clock-history fs-2 text-success"></i>
                <h4 class="mt-2 mb-0"><?= $total_training_hours ?></h4>
                <small>إجمالي ساعات التدريب</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box">
                <i class="bi bi-briefcase fs-2 text-success"></i>
                <h4 class="mt-2 mb-0"><?= $years_experience ?> سنة</h4>
                <small>سنوات الخبرة العملية</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box">
                <i class="bi bi-mortarboard fs-2 text-success"></i>
                <h4 class="mt-2 mb-0"><?= count($quals) ?></h4>
                <small>مؤهلات علمية</small>
            </div>
        </div>
    </div>

    <!-- البيانات الشخصية -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white"><strong>البيانات الشخصية</strong></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6"><strong>الاسم:</strong> <?= $emp['name_ar'] ?></div>
                <div class="col-md-6"><strong>رقم الهوية:</strong> <?= $emp['id_number'] ?></div>
                <div class="col-md-6"><strong>الجنس:</strong> <?= $emp['gender'] ?: '—' ?></div>
                <div class="col-md-6"><strong>الحالة الاجتماعية:</strong> <?= $emp['marital_status'] ?: '—' ?></div>
                <div class="col-md-6"><strong>رقم الجوال:</strong> <?= $emp['mobile'] ?></div>
                <div class="col-md-6"><strong>رقم جوال بديل:</strong> <?= $emp['mobile_alt'] ?: '—' ?></div>
                <div class="col-md-6"><strong>المسمى الوظيفي:</strong> 
                    <span class="badge bg-primary"><?= $emp['job_title'] ?: 'غير محدد' ?></span>
                </div>
                <div class="col-12"><strong>العنوان:</strong> <?= $emp['governorate'] ?> - <?= $emp['city'] ?> - <?= $emp['neighborhood'] ?> (<?= $emp['nearest_landmark'] ?>)</div>
            </div>
        </div>
    </div>

    <!-- المؤهلات العلمية -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white d-flex justify-content-between">
            <strong>المؤهلات العلمية</strong>
            <button class="btn btn-sm btn-light" onclick="addQualificationForm()">+ إضافة مؤهل</button>
        </div>
        <div class="card-body">
            <?php if (empty($quals)): ?>
                <p class="text-muted">لا يوجد مؤهلات مسجلة</p>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($quals as $q): ?>
                        <li class="list-group-item">
                            <strong><?= $q['degree'] ?></strong> - <?= $q['specialization'] ?> 
                            (<?= $q['university'] ?>) - <?= $q['graduation_date'] ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- التدريب والخبرات -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white"><strong>بيانات التدريب</strong></div>
                <div class="card-body">
                    <?php if (empty($trains)): ?>
                        <p class="text-muted">لا يوجد تدريب مسجل</p>
                    <?php else: ?>
                        <?php foreach ($trains as $t): ?>
                            <div class="mb-2">
                                <strong><?= $t['course_name'] ?></strong><br>
                                <small><?= $t['organizer'] ?> - <?= $t['hours'] ?> ساعة - <?= $t['training_date'] ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white"><strong>الخبرات العملية</strong></div>
                <div class="card-body">
                    <?php if (empty($exps)): ?>
                        <p class="text-muted">لا يوجد خبرات مسجلة</p>
                    <?php else: ?>
                        <?php foreach ($exps as $e): ?>
                            <div class="mb-2">
                                <strong><?= $e['institution'] ?></strong> - <?= $e['position'] ?><br>
                                <small><?= $e['from_date'] ?> إلى <?= $e['to_date'] ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- اللغات والمهارات -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white"><strong>اللغات</strong></div>
                <div class="card-body">
                    <?php if (empty($langs)): ?>
                        <p class="text-muted">لا توجد لغات مسجلة</p>
                    <?php else: ?>
                        <?php foreach ($langs as $l): ?>
                            <span class="badge bg-primary me-1"><?= $l['language'] ?> (<?= $l['level'] ?>)</span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white"><strong>المهارات</strong></div>
                <div class="card-body">
                    <?php if (empty($sks)): ?>
                        <p class="text-muted">لا توجد مهارات مسجلة</p>
                    <?php else: ?>
                        <?php foreach ($sks as $s): ?>
                            <span class="badge bg-secondary me-1"><?= $s['skill'] ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="employees_list.php" class="btn btn-outline-success">العودة للقائمة</a>
    </div>
</div>

<script>
function addQualificationForm() {
    alert('هذه الميزة ستكون متاحة في النسخة الكاملة (إضافة مؤهل جديد من هنا)');
}
</script>
</body>
</html>