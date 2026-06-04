<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_title'])) {
    $title = trim($_POST['title']);
    if ($title) {
        $db->prepare("INSERT OR IGNORE INTO job_titles (title, description) VALUES (?, ?)")
           ->execute([$title, $_POST['description'] ?? '']);
        $message = 'تمت إضافة المسمى الوظيفي بنجاح';
        logActivity('إضافة مسمى وظيفي', $title);
    }
}

$titles = $db->query("SELECT * FROM job_titles ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>المسميات الوظيفية | نظام HR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-success"><div class="container"><a class="navbar-brand" href="dashboard.php">نظام HR - جمعية أصدقاء البيئة</a></div></nav>

<div class="container py-4">
    <h3>المسميات الوظيفية</h3>
    
    <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">إضافة مسمى وظيفي جديد</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2">
                    <div class="col-md-5">
                        <input type="text" name="title" class="form-control" placeholder="المسمى الوظيفي (مثال: مدير تنفيذي)" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="description" class="form-control" placeholder="وصف مختصر">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_title" class="btn btn-success w-100">إضافة</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <table class="table table-striped">
        <thead><tr><th>#</th><th>المسمى الوظيفي</th><th>الوصف</th></tr></thead>
        <tbody>
            <?php foreach ($titles as $t): ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td><strong><?= $t['title'] ?></strong></td>
                <td><?= $t['description'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>