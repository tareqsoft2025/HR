<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// جلب جميع جلسات المقابلات مع عدد المرشحين
$sessions = $db->query("
    SELECT s.*, 
           jt.title as job_title,
           COUNT(c.id) as candidates_count,
           MAX(c.total_score) as top_score
    FROM interview_sessions s
    LEFT JOIN job_titles jt ON s.job_title_id = jt.id
    LEFT JOIN candidates c ON s.id = c.session_id
    GROUP BY s.id
    ORDER BY s.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>جلسات المقابلات والمرشحين | نظام HR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-success"><i class="bi bi-list-ul me-2"></i>جلسات المقابلات والمرشحين</h3>
        <a href="add_candidates.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i> جلسة جديدة
        </a>
    </div>

    <?php if (empty($sessions)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <h5>لا توجد جلسات مقابلات بعد</h5>
            <a href="add_candidates.php" class="btn btn-primary mt-3">إنشاء جلسة جديدة</a>
        </div>
    <?php else: ?>
        <?php foreach ($sessions as $session): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <div>
                    <strong>كود المقابلة:</strong> <?= $session['interview_code'] ?> 
                    <span class="badge bg-light text-dark ms-2"><?= $session['interview_date'] ?></span>
                </div>
                <div>
                    <span class="badge bg-light text-dark"><?= $session['job_title'] ?></span>
                    <span class="badge bg-primary ms-2"><?= $session['candidates_count'] ?> مرشح</span>
                </div>
            </div>
            <div class="card-body">
                <?php
                // جلب المرشحين لهذه الجلسة
                $stmt = $db->prepare("SELECT * FROM candidates WHERE session_id = ? ORDER BY total_score DESC");
                $stmt->execute([$session['id']]);
                $candidates = $stmt->fetchAll();
                ?>
                
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th>الاسم</th>
                            <th>رقم الهوية</th>
                            <th>الجوال</th>
                            <th class="text-center">امتحان</th>
                            <th class="text-center">مقابلة</th>
                            <th class="text-center">المجموع</th>
                            <th class="text-center">المتوسط</th>
                            <th class="text-center">الترتيب</th>
                            <th>الحالة</th>
                            <th width="120">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidates as $index => $c): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><strong><?= $c['name_ar'] ?></strong></td>
                            <td><?= $c['id_number'] ?></td>
                            <td><?= $c['mobile'] ?></td>
                            <td class="text-center"><?= $c['exam_score'] ?></td>
                            <td class="text-center"><?= $c['interview_score'] ?></td>
                            <td class="text-center"><strong><?= $c['total_score'] ?></strong></td>
                            <td class="text-center"><?= number_format($c['average_score'], 1) ?></td>
                            <td class="text-center">
                                <span class="badge bg-success">#<?= $c['rank'] ?></span>
                            </td>
                            <td>
                                <?php if ($c['status'] == 'pending'): ?>
                                    <span class="badge bg-warning">قيد المراجعة</span>
                                <?php elseif ($c['status'] == 'approved'): ?>
                                    <span class="badge bg-success">تمت الموافقة</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">مرفوض</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['status'] == 'pending'): ?>
                                    <a href="approvals.php?candidate_id=<?= $c['id'] ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-check"></i> موافقة
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>