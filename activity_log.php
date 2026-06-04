<?php
require_once 'config.php';
requireLogin();

if (!hasRole(['admin'])) {
    die('غير مصرح لك بالوصول إلى سجل الحركات');
}

$db = getDB();
$logs = $db->query("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سجل الحركات | نظام HR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="dashboard.php">سجل الحركات</a></div></nav>

<div class="container py-4">
    <h3>سجل الحركات (آخر 100 حركة)</h3>
    <p class="text-muted">كل حركة مسجلة مع: المستخدم + IP + الوقت</p>

    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>الوقت</th>
                <th>المستخدم</th>
                <th>الإجراء</th>
                <th>التفاصيل</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                <td><?= $log['username'] ?></td>
                <td><?= $log['action'] ?></td>
                <td><?= htmlspecialchars($log['details']) ?></td>
                <td><?= $log['ip_address'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>