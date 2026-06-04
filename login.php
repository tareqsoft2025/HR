<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        logActivity('تسجيل الدخول', 'دخول ناجح');
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        logActivity('محاولة دخول فاشلة', 'اسم المستخدم: ' . $username);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول | جمعية أصدقاء البيئة الفلسطينية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: linear-gradient(135deg, #2e7d32, #1b5e20); min-height: 100vh; display: flex; align-items: center; }
        .login-card { max-width: 420px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.3); }
        .logo { font-size: 3.5rem; color: #4caf50; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card login-card border-0">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <i class="bi bi-tree-fill logo"></i>
                        <h3 class="mt-3 fw-bold text-success">جمعية أصدقاء البيئة الفلسطينية</h3>
                        <p class="text-muted">نظام إدارة الموارد البشرية</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <input type="text" name="username" class="form-control form-control-lg" placeholder="اسم المستخدم" required>
                        </div>
                        <div class="mb-4">
                            <input type="password" name="password" class="form-control form-control-lg" placeholder="كلمة المرور" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">تسجيل الدخول</button>
                    </form>

                    <div class="mt-4 small text-muted">
                        <strong>بيانات الدخول التجريبية:</strong><br>
                        hr_officer / hr123<br>
                        committee / committee123<br>
                        director / director123<br>
                        admin / admin123
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>