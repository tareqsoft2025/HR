<?php
require_once 'config.php';
requireLogin();

if (!hasRole(['interview_committee', 'executive_director', 'admin'])) {
    die('غير مصرح لك بالوصول');
}

$db = getDB();
$message = '';

// معالجة موافقة المرشحين
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_candidate'])) {
    $candidate_id = intval($_POST['candidate_id']);
    $action = $_POST['action']; // approve or reject

    if ($action === 'approve') {
        $db->prepare("UPDATE candidates SET status = 'approved' WHERE id = ?")->execute([$candidate_id]);
        $message = '<div class="alert alert-success">تمت الموافقة على المرشح بنجاح</div>';
    } else {
        $db->prepare("UPDATE candidates SET status = 'rejected' WHERE id = ?")->execute([$candidate_id]);
        $message = '<div class="alert alert-danger">تم رفض المرشح</div>';
    }
    logActivity('موافقة على مرشح', "مرشح ID: $candidate_id - $action");
}

// معالجة قبول/رفض عرض وظيفي
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['handle_offer'])) {
    $offer_id = intval($_POST['offer_id']);
    $action = $_POST['action'];

    if ($action === 'accept') {
        $db->prepare("UPDATE job_offers SET status = 'accepted' WHERE id = ?")->execute([$offer_id]);
        
        // تحويل المرشح إلى موظف فعلي تلقائياً
        $offer = $db->prepare("SELECT * FROM job_offers WHERE id = ?");
        $offer->execute([$offer_id]);
        $offerData = $offer->fetch();
        
        if ($offerData) {
            $candidate = $db->prepare("SELECT * FROM candidates WHERE id = ?");
            $candidate->execute([$offerData['candidate_id']]);
            $candData = $candidate->fetch();
            
            if ($candData) {
                // إنشاء موظف جديد من المرشح
                $employee_number = generateEmployeeNumber();
                $db->prepare("INSERT INTO employees (employee_number, name_ar, name_en, id_number, mobile, job_title_id, status, appointment_date) 
                              VALUES (?, ?, ?, ?, ?, ?, 'active', ?)")
                   ->execute([
                       $employee_number, 
                       $candData['name_ar'], 
                       $candData['name_en'], 
                       $candData['id_number'], 
                       $candData['mobile'], 
                       $offerData['position'],
                       $offerData['start_date']
                   ]);
                
                $new_employee_id = $db->lastInsertId();
                
                // تسجيل طريقة الموافقة
                $db->prepare("UPDATE job_offers SET notes = CONCAT(IFNULL(notes,''), ' | طريقة الموافقة: ', ?) WHERE id = ?")
                   ->execute([$offerData['notes'] ?? 'غير محدد', $offer_id]);
            }
        }
        
        $message = '<div class="alert alert-success">تم قبول العرض الوظيفي وتحويل المرشح إلى موظف فعلي بنجاح!</div>';
    } else {
        $db->prepare("UPDATE job_offers SET status = 'rejected' WHERE id = ?")->execute([$offer_id]);
        $message = '<div class="alert alert-danger">تم رفض العرض الوظيفي</div>';
    }
}

// جلب المرشحين المنتظرين
$pending_candidates = $db->query("
    SELECT c.*, s.interview_code, jt.title as job_title 
    FROM candidates c
    LEFT JOIN interview_sessions s ON c.session_id = s.id
    LEFT JOIN job_titles jt ON s.job_title_id = jt.id
    WHERE c.status = 'pending'
    ORDER BY c.rank ASC
")->fetchAll();

// جلب عروض الوظائف المنتظرة
$pending_offers = $db->query("
    SELECT jo.*, c.name_ar as candidate_name, c.id as candidate_id
    FROM job_offers jo
    LEFT JOIN candidates c ON jo.candidate_id = c.id
    WHERE jo.status = 'sent'
    ORDER BY jo.offer_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الموافقات | نظام HR - جمعية أصدقاء البيئة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <h3 class="fw-bold text-success mb-4"><i class="bi bi-check2-square me-2"></i>الموافقات</h3>

    <?= $message ?>

    <!-- المرشحين المنتظرين -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-person-check me-2"></i>المرشحين المنتظرين للموافقة (<?= count($pending_candidates) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($pending_candidates)): ?>
                <p class="text-muted text-center py-3">لا يوجد مرشحين منتظرين حالياً</p>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الترتيب</th>
                            <th>الاسم</th>
                            <th>كود المقابلة</th>
                            <th>المسمى الوظيفي</th>
                            <th>المجموع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_candidates as $cand): ?>
                        <tr>
                            <td><span class="badge bg-primary">#<?= $cand['rank'] ?></span></td>
                            <td><strong><?= $cand['name_ar'] ?></strong></td>
                            <td><?= $cand['interview_code'] ?></td>
                            <td><?= $cand['job_title'] ?></td>
                            <td><strong class="text-success"><?= $cand['total_score'] ?></strong></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="candidate_id" value="<?= $cand['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" name="approve_candidate" class="btn btn-sm btn-success">موافقة</button>
                                </form>
                                <form method="POST" class="d-inline ms-1">
                                    <input type="hidden" name="candidate_id" value="<?= $cand['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" name="approve_candidate" class="btn btn-sm btn-danger">رفض</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- عروض الوظائف المنتظرة -->
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-envelope me-2"></i>عروض الوظائف المنتظرة (<?= count($pending_offers) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($pending_offers)): ?>
                <p class="text-muted text-center py-3">لا توجد عروض وظائف منتظرة</p>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>المرشح</th>
                            <th>المنصب</th>
                            <th>الراتب</th>
                            <th>تاريخ بدء العمل</th>
                            <th>تاريخ العرض</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_offers as $offer): ?>
                        <tr>
                            <td><strong><?= $offer['candidate_name'] ?></strong></td>
                            <td><?= $offer['position'] ?></td>
                            <td><span class="text-success fw-bold">$<?= number_format($offer['salary'], 2) ?></span></td>
                            <td><?= $offer['start_date'] ?></td>
                            <td><?= date('Y-m-d', strtotime($offer['offer_date'])) ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="offer_id" value="<?= $offer['id'] ?>">
                                    <button type="submit" name="handle_offer" value="accept" class="btn btn-sm btn-success">قبول</button>
                                    <button type="submit" name="handle_offer" value="reject" class="btn btn-sm btn-danger">رفض</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>