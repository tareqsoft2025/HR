<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$employees = $db->query("SELECT * FROM employees ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قائمة الموظفين | جمعية أصدقاء البيئة الفلسطينية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">نظام HR - جمعية أصدقاء البيئة</a>
    </div>
</nav>

<div class="container py-4">
    <h3 class="mb-4">قائمة الموظفين</h3>
    
    <table class="table table-striped table-hover">
        <thead class="table-success">
            <tr>
                <th>#</th>
                <th>الرقم الوظيفي</th>
                <th>الاسم</th>
                <th>رقم الهوية</th>
                <th>الحالة</th>
                <th>تاريخ الإضافة</th>
                <th>الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $emp): ?>
            <tr>
                <td><?= $emp['id'] ?></td>
                <td><strong><?= $emp['employee_number'] ?></strong></td>
                <td><?= htmlspecialchars($emp['name_ar']) ?></td>
                <td><?= $emp['id_number'] ?></td>
                <td>
                    <?php
                    $status = $emp['status'];
                    $badge = match($status) {
                        'active' => 'success',
                        'pending_approval' => 'warning',
                        'rejected' => 'danger',
                        default => 'secondary'
                    };
                    echo "<span class='badge bg-$badge'>$status</span>";
                    ?>
                </td>
                <td><?= date('Y-m-d', strtotime($emp['created_at'])) ?></td>
                <td>
                    <a href="employee_profile.php?id=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-primary">عرض السيرة الذاتية</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>