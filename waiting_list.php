<?php
require_once 'config.php';
requireLogin();

$db = getDB();

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$job_title_filter = intval($_GET['job_title_id'] ?? 0);

// بناء الاستعلام مع الفلاتر (موظفين + مرشحين)
$sql = "
    SELECT 
        'employee' as type,
        e.id,
        e.employee_number as code,
        e.name_ar,
        e.name_en,
        e.id_number,
        e.mobile,
        e.created_at,
        u.full_name as created_by_name,
        jt.title as job_title,
        e.status
    FROM employees e 
    LEFT JOIN users u ON e.created_by = u.id 
    LEFT JOIN job_titles jt ON e.job_title_id = jt.id 
    WHERE e.status IN ('pending_approval', 'pending_director')

    UNION ALL

    SELECT 
        'candidate' as type,
        c.id,
        s.interview_code as code,
        c.name_ar,
        c.name_en,
        c.id_number,
        c.mobile,
        s.created_at,
        u.full_name as created_by_name,
        jt.title as job_title,
        c.status
    FROM candidates c
    LEFT JOIN interview_sessions s ON c.session_id = s.id
    LEFT JOIN users u ON s.created_by = u.id
    LEFT JOIN job_titles jt ON s.job_title_id = jt.id
    WHERE c.status = 'pending'
";

$params = [];

if ($search) {
    $sql .= " AND (e.name_ar LIKE ? OR e.name_en LIKE ? OR e.id_number LIKE ? OR e.mobile LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($status_filter) {
    $sql .= " AND e.status = ?";
    $params[] = $status_filter;
}

if ($job_title_filter) {
    $sql .= " AND e.job_title_id = ?";
    $params[] = $job_title_filter;
}

$sql .= " ORDER BY e.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$pending_employees = $stmt->fetchAll();

// جلب المسميات للفلتر
$job_titles = $db->query("SELECT * FROM job_titles ORDER BY title")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>قوائم الانتظار | نظام HR - جمعية أصدقاء البيئة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-clock-history me-2"></i>قوائم الانتظار</a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-clock-history me-2"></i>قوائم الانتظار</h3>
        <span class="badge bg-warning fs-6"><?= count($pending_employees) ?> موظف في الانتظار</span>
    </div>

    <!-- البحث -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <strong><i class="bi bi-search me-2"></i>البحث والتصفية</strong>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">بحث سريع</label>
                    <input type="text" name="search" class="form-control" placeholder="الاسم، رقم الهوية، الجوال..." value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">الكل</option>
                        <option value="pending_approval" <?= $status_filter == 'pending_approval' ? 'selected' : '' ?>>بانتظار لجنة المقابلات</option>
                        <option value="pending_director" <?= $status_filter == 'pending_director' ? 'selected' : '' ?>>بانتظار المدير التنفيذي</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">المسمى الوظيفي</label>
                    <select name="job_title_id" class="form-select">
                        <option value="">الكل</option>
                        <?php foreach ($job_titles as $jt): ?>
                            <option value="<?= $jt['id'] ?>" <?= $job_title_filter == $jt['id'] ? 'selected' : '' ?>>
                                <?= $jt['title'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-search me-1"></i> بحث
                    </button>
                </div>
                
                <div class="col-12 text-end">
                    <a href="waiting_list.php" class="btn btn-outline-secondary btn-sm">مسح الفلاتر</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($pending_employees)): ?>
        <div class="alert alert-success text-center py-5">
            <i class="bi bi-check-circle fs-1 d-block mb-3"></i>
            <h5>لا توجد نتائج مطابقة للبحث</h5>
            <p class="mb-0">جرب تغيير كلمات البحث أو الفلاتر.</p>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <strong>الموظفين المنتظرين للموافقة</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>النوع</th>
                                <th>الكود / الجلسة</th>
                                <th>الاسم</th>
                                <th>المسمى الوظيفي</th>
                                <th>الجوال</th>
                                <th>الحالة</th>
                                <th>تاريخ الطلب</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_employees as $item): ?>
                            <tr>
                                <td><?= $item['id'] ?></td>
                                <td>
                                    <?php if ($item['type'] == 'candidate'): ?>
                                        <span class="badge bg-info">مرشح</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">موظف</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= $item['code'] ?></strong></td>
                                <td><?= htmlspecialchars($item['name_ar']) ?></td>
                                <td>
                                    <?php if ($item['job_title']): ?>
                                        <span class="badge bg-primary"><?= $item['job_title'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">غير محدد</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $item['mobile'] ?></td>
                                <td>
                                    <?php if ($item['status'] == 'pending_approval' || $item['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">بانتظار لجنة المقابلات</span>
                                    <?php elseif ($item['status'] == 'pending_director'): ?>
                                        <span class="badge bg-info">بانتظار المدير التنفيذي</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('Y-m-d', strtotime($item['created_at'])) ?></td>
                                <td>
                                    <?php if ($item['type'] == 'employee'): ?>
                                        <a href="employee_profile.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="interview_sessions.php" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasRole(['interview_committee', 'executive_director', 'admin'])): ?>
                                        <a href="approvals.php" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-circle"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-4 text-center">
        <a href="dashboard.php" class="btn btn-outline-success">العودة للوحة التحكم</a>
    </div>
</div>
</body>
</html>