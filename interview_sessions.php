<?php
require_once 'config.php';
requireLogin();

$db = getDB();

// معالجة إرسال عرض وظيفي (التنفيذ الفعلي)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_job_offer') {
    $candidate_id = intval($_POST['candidate_id']);
    $position = $_POST['position'];
    $salary = floatval($_POST['salary']);
    $start_date = $_POST['start_date'];
    $notes = $_POST['notes'] ?? '';

    // إنشاء سجل عرض الوظيفة
    $db->prepare("INSERT INTO job_offers (candidate_id, position, salary, start_date, notes) 
                  VALUES (?, ?, ?, ?, ?)")
       ->execute([$candidate_id, $position, $salary, $start_date, $notes]);

    // تحديث حالة المرشح
    $db->prepare("UPDATE candidates SET status = 'offer_sent' WHERE id = ?")
       ->execute([$candidate_id]);

    logActivity('إرسال عرض وظيفي', "مرشح ID: $candidate_id - $position - $salary$");
    echo 'success';
    exit;
}

// جلب كل جلسات المقابلات
$sessions = $db->query("
    SELECT s.*, jt.title as job_title, u.full_name as created_by_name,
           (SELECT COUNT(*) FROM candidates WHERE session_id = s.id) as candidates_count
    FROM interview_sessions s
    LEFT JOIN job_titles jt ON s.job_title_id = jt.id
    LEFT JOIN users u ON s.created_by = u.id
    ORDER BY s.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>جلسات المقابلات | نظام HR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-success"><i class="bi bi-clipboard-data me-2"></i>جلسات المقابلات والمرشحين</h3>
        <a href="add_candidates.php" class="btn btn-success">
            <i class="bi bi-plus-circle me-2"></i> جلسة جديدة
        </a>
    </div>

    <?php if (empty($sessions)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-info-circle fs-1"></i>
            <h5 class="mt-3">لا توجد جلسات مقابلات بعد</h5>
            <a href="add_candidates.php" class="btn btn-success mt-3">ابدأ بإضافة جلسة جديدة</a>
        </div>
    <?php else: ?>
        <?php foreach ($sessions as $session): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <div>
                    <strong>كود المقابلة: <?= $session['interview_code'] ?></strong>
                    <span class="badge bg-light text-dark ms-2"><?= $session['job_title'] ?></span>
                </div>
                <div>
                    <span class="badge bg-info"><?= $session['candidates_count'] ?> مرشح</span>
                    <span class="text-white ms-2"><?= date('Y-m-d', strtotime($session['interview_date'])) ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">تم الإنشاء بواسطة: <?= $session['created_by_name'] ?> | <?= date('Y-m-d H:i', strtotime($session['created_at'])) ?></small>
                </div>

                <!-- المرشحين في هذه الجلسة -->
                <?php
                $candidates = $db->prepare("SELECT * FROM candidates WHERE session_id = ? ORDER BY rank ASC, total_score DESC");
                $candidates->execute([$session['id']]);
                $cands = $candidates->fetchAll();
                ?>

                <?php if (!empty($cands)): ?>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="50">الترتيب</th>
                            <th>الاسم</th>
                            <th>رقم الهوية</th>
                            <th>الجوال</th>
                            <th>امتحان</th>
                            <th>مقابلة</th>
                            <th>المجموع</th>
                            <th>المتوسط</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cands as $cand): ?>
                        <tr>
                            <td><span class="badge bg-primary">#<?= $cand['rank'] ?></span></td>
                            <td><strong><?= $cand['name_ar'] ?></strong></td>
                            <td><?= $cand['id_number'] ?></td>
                            <td><?= $cand['mobile'] ?></td>
                            <td><?= $cand['exam_score'] ?></td>
                            <td><?= $cand['interview_score'] ?></td>
                            <td><strong class="text-success"><?= $cand['total_score'] ?></strong></td>
                            <td><?= number_format($cand['average_score'], 1) ?></td>
                            <td>
                                <?php if ($cand['status'] == 'approved'): ?>
                                    <span class="badge bg-success">مقبول</span>
                                <?php elseif ($cand['status'] == 'rejected'): ?>
                                    <span class="badge bg-danger">مرفوض</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">قيد المراجعة</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cand['status'] == 'pending' && $cand['rank'] <= 3 && hasRole(['hr_officer', 'executive_director', 'admin'])): ?>
                                    <button class="btn btn-sm btn-success" 
                                            onclick="sendJobOffer(<?= $cand['id'] ?>, '<?= $cand['name_ar'] ?>')">
                                        <i class="bi bi-envelope"></i> إرسال عرض وظيفي
                                    </button>
                                <?php elseif ($cand['status'] == 'pending' && $cand['rank'] <= 3): ?>
                                    <span class="text-muted small">غير مصرح</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="text-muted">لا يوجد مرشحين في هذه الجلسة</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script>
function sendJobOffer(candidateId, name) {
    document.getElementById('offerCandidateId').value = candidateId;
    document.getElementById('offerCandidateName').innerText = name;
    new bootstrap.Modal(document.getElementById('jobOfferModal')).show();
}

function submitJobOffer() {
    const candidateId = document.getElementById('offerCandidateId').value;
    const position = document.getElementById('offerPosition').value;
    const salary = document.getElementById('offerSalary').value;
    const startDate = document.getElementById('offerStartDate').value;
    const notes = document.getElementById('offerNotes').value;

    // جمع طرق الإرسال
    let methods = [];
    if (document.getElementById('methodWhatsApp').checked) methods.push('واتساب');
    if (document.getElementById('methodPhone').checked) methods.push('اتصال هاتفي');
    if (document.getElementById('methodEmail').checked) methods.push('البريد الإلكتروني');
    
    const methodsText = methods.length > 0 ? methods.join(' + ') : 'غير محدد';

    if (!position || !salary || !startDate) {
        alert('يرجى ملء جميع الحقول المطلوبة');
        return;
    }

    fetch('interview_sessions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=send_job_offer&candidate_id=${candidateId}&position=${encodeURIComponent(position)}&salary=${salary}&start_date=${startDate}&notes=${encodeURIComponent(notes)}&methods=${encodeURIComponent(methodsText)}`
    })
    .then(response => response.text())
    .then(data => {
        alert('تم إرسال عرض الوظيفة بنجاح!\nطريقة الإرسال: ' + methodsText);
        location.reload();
    });
}
</script>
<!-- مودال إرسال عرض وظيفي -->
<div class="modal fade" id="jobOfferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">إرسال عرض وظيفي</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>إرسال عرض وظيفي للمرشح: <strong id="offerCandidateName"></strong></p>
                
                <input type="hidden" id="offerCandidateId">
                
                <div class="mb-3">
                    <label class="form-label">المنصب الوظيفي</label>
                    <input type="text" id="offerPosition" class="form-control" value="موظف" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">الراتب الشهري ($)</label>
                    <input type="number" id="offerSalary" class="form-control" step="0.01" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">تاريخ بدء العمل</label>
                    <input type="date" id="offerStartDate" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">ملاحظات إضافية</label>
                    <textarea id="offerNotes" class="form-control" rows="3" placeholder="شروط العرض، المزايا..."></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">طريقة إرسال العرض الوظيفي</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="methodWhatsApp" value="whatsapp" checked>
                        <label class="form-check-label" for="methodWhatsApp">واتساب</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="methodPhone" value="phone">
                        <label class="form-check-label" for="methodPhone">اتصال هاتفي</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="methodEmail" value="email">
                        <label class="form-check-label" for="methodEmail">البريد الإلكتروني</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success" onclick="submitJobOffer()">إرسال العرض الوظيفي</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>