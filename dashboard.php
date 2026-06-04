<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// إحصائيات
$totalEmployees = $db->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$pending = $db->query("SELECT COUNT(*) FROM employees WHERE status IN ('pending_approval', 'pending_director')")->fetchColumn() + 
           $db->query("SELECT COUNT(*) FROM candidates WHERE status = 'pending'")->fetchColumn();
$active = $db->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();
$rejected = $db->query("SELECT COUNT(*) FROM employees WHERE status = 'rejected'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم | جمعية أصدقاء البيئة الفلسطينية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fc; }
        .stat-card { 
            transition: transform 0.3s ease, box-shadow 0.3s ease; 
            border: none;
            border-radius: 16px;
        }
        .stat-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        .modern-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1" style="color: #1a237e;">مرحباً، <?= $_SESSION['full_name'] ?>!</h2>
            <p class="text-muted mb-0">لوحة تحكم نظام إدارة الموارد البشرية • الإصدار 3.0</p>
        </div>
        <div>
            <span class="badge bg-primary fs-6 px-3 py-2">
                <i class="bi bi-person-check me-1"></i> <?= $_SESSION['role'] ?>
            </span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-people-fill text-primary fs-2"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">إجمالي الموظفين</h6>
                        <h2 class="fw-bold mb-0" style="color: #1a237e;"><?= $totalEmployees ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-clock-history text-warning fs-2"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">بانتظار الموافقة</h6>
                        <h2 class="fw-bold mb-0 text-warning"><?= $pending ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-check-circle text-success fs-2"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">موظفين نشيطين</h6>
                        <h2 class="fw-bold mb-0 text-success"><?= $active ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="flex-shrink-0">
                        <div class="bg-danger bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-x-circle text-danger fs-2"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="text-muted mb-1">مرفوضين</h6>
                        <h2 class="fw-bold mb-0 text-danger"><?= $rejected ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card modern-card shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 fw-bold" style="color: #1a237e;"><i class="bi bi-lightning-charge me-2"></i>إجراءات سريعة</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="add_candidates.php" class="btn btn-primary w-100 py-3 rounded-3">
                        <i class="bi bi-person-plus fs-4 d-block mb-2"></i>
                        إضافة مرشحين جدد
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="interview_sessions.php" class="btn btn-outline-primary w-100 py-3 rounded-3">
                        <i class="bi bi-clipboard-data fs-4 d-block mb-2"></i>
                        جلسات المقابلات
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="waiting_list.php" class="btn btn-warning w-100 py-3 rounded-3">
                        <i class="bi bi-clock-history fs-4 d-block mb-2"></i>
                        قوائم الانتظار
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="approvals.php" class="btn btn-success w-100 py-3 rounded-3">
                        <i class="bi bi-check2-square fs-4 d-block mb-2"></i>
                        الموافقات
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>