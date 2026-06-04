<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$message = '';

// جلب الجلسات السابقة
$previous_sessions = $db->query("
    SELECT s.id, s.interview_code, s.interview_date, jt.title as job_title,
           (SELECT COUNT(*) FROM candidates WHERE session_id = s.id) as candidates_count
    FROM interview_sessions s
    LEFT JOIN job_titles jt ON s.job_title_id = jt.id
    ORDER BY s.created_at DESC
    LIMIT 10
")->fetchAll();

// إنشاء جلسة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_new_session'])) {
    $interview_code = generateInterviewCode();
    $interview_date = $_POST['interview_date'];
    $job_title_id = intval($_POST['job_title_id']);

    $db->prepare("INSERT INTO interview_sessions (interview_code, interview_date, job_title_id, created_by) 
                  VALUES (?, ?, ?, ?)")
       ->execute([$interview_code, $interview_date, $job_title_id, $_SESSION['user_id']]);
    
    $session_id = $db->lastInsertId();
    $message = '<div class="alert alert-success">تم إنشاء جلسة جديدة بكود: <strong>'.$interview_code.'</strong></div>';
}

// إضافة مرشحين إلى جلسة موجودة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_existing'])) {
    $session_id = intval($_POST['existing_session_id']);
    $candidates = $_POST['candidates'] ?? [];

    foreach ($candidates as $c) {
        if (!empty($c['name_ar'])) {
            $exam = floatval($c['exam_score'] ?? 0);
            $interview = floatval($c['interview_score'] ?? 0);
            $total = $exam + $interview;
            $average = $total / 2;

            $db->prepare("INSERT INTO candidates (session_id, name_ar, name_en, id_number, mobile, exam_score, interview_score, total_score, average_score) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
               ->execute([$session_id, $c['name_ar'], $c['name_en'] ?? '', $c['id_number'] ?? '', $c['mobile'] ?? '', $exam, $interview, $total, $average]);
        }
    }

    // إعادة ترتيب
    $db->exec("UPDATE candidates SET rank = (
        SELECT COUNT(*) + 1 FROM candidates c2 
        WHERE c2.session_id = candidates.session_id AND c2.total_score > candidates.total_score
    ) WHERE session_id = $session_id");

    $message = '<div class="alert alert-success">تم إضافة المرشحين إلى الجلسة بنجاح!</div>';
}

$job_titles = $db->query("SELECT * FROM job_titles ORDER BY title")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة مرشحين جدد | نظام HR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-success"><i class="bi bi-person-plus me-2"></i>إضافة مرشحين جدد</h3>
        <div>
            <a href="interview_sessions.php" class="btn btn-outline-primary">عرض الجلسات السابقة</a>
        </div>
    </div>

    <?= $message ?>

    <!-- خيار 1: جلسة جديدة -->
    <div class="card shadow-sm mb-4 border-success">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">1. إنشاء جلسة مقابلة جديدة</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">كود المقابلة (تلقائي)</label>
                        <input type="text" class="form-control" value="<?= generateInterviewCode() ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ المقابلة</label>
                        <input type="date" name="interview_date" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">المسمى الوظيفي</label>
                        <select name="job_title_id" class="form-select" required>
                            <option value="">-- اختر --</option>
                            <?php foreach ($job_titles as $jt): ?>
                                <option value="<?= $jt['id'] ?>"><?= $jt['title'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_new_session" class="btn btn-success mt-3">
                    <i class="bi bi-plus-circle me-2"></i> إنشاء جلسة جديدة
                </button>
            </form>
        </div>
    </div>

    <!-- خيار 2: إضافة إلى جلسة سابقة -->
    <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">2. إضافة مرشحين إلى جلسة سابقة</h5>
        </div>
        <div class="card-body">
            <?php if (empty($previous_sessions)): ?>
                <p class="text-muted">لا توجد جلسات سابقة</p>
            <?php else: ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">اختر جلسة سابقة</label>
                    <select name="existing_session_id" class="form-select" id="sessionSelect" required onchange="showSessionDetails()">
                        <?php foreach ($previous_sessions as $s): ?>
                            <option value="<?= $s['id'] ?>" data-code="<?= $s['interview_code'] ?>" data-date="<?= $s['interview_date'] ?>" data-title="<?= $s['job_title'] ?>" data-count="<?= $s['candidates_count'] ?>">
                                <?= $s['interview_code'] ?> - <?= $s['job_title'] ?> (<?= $s['candidates_count'] ?> مرشح)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <!-- تفاصيل الجلسة المختارة -->
                    <div id="sessionDetails" class="mt-2 p-2 bg-light rounded" style="display: none;">
                        <small>
                            <strong>كود الجلسة:</strong> <span id="detailCode"></span><br>
                            <strong>التاريخ:</strong> <span id="detailDate"></span><br>
                            <strong>المسمى:</strong> <span id="detailTitle"></span><br>
                            <strong>عدد المرشحين الحالي:</strong> <span id="detailCount"></span>
                        </small>
                    </div>
                </div>

                <div id="candidates-container">
                    <!-- صفوف المرشحين -->
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addCandidateRow()">
                    + إضافة مرشح آخر
                </button>

                <br>
                <button type="submit" name="add_to_existing" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i> إضافة المرشحين إلى الجلسة
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let candidateIndex = 0;

function addCandidateRow() {
    const container = document.getElementById('candidates-container');
    const div = document.createElement('div');
    div.className = 'border rounded p-3 mb-2 bg-light';
    div.innerHTML = `
        <div class="row g-2">
            <div class="col-md-3">
                <input type="text" name="candidates[${candidateIndex}][name_ar]" class="form-control" placeholder="الاسم بالعربية" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="candidates[${candidateIndex}][name_en]" class="form-control" placeholder="الاسم الإنجليزي">
            </div>
            <div class="col-md-2">
                <input type="text" name="candidates[${candidateIndex}][id_number]" class="form-control" placeholder="رقم الهوية">
            </div>
            <div class="col-md-2">
                <input type="text" name="candidates[${candidateIndex}][mobile]" class="form-control" placeholder="الجوال">
            </div>
            <div class="col-md-1">
                <input type="number" step="0.5" name="candidates[${candidateIndex}][exam_score]" class="form-control" placeholder="امتحان" value="0">
            </div>
            <div class="col-md-1">
                <input type="number" step="0.5" name="candidates[${candidateIndex}][interview_score]" class="form-control" placeholder="مقابلة" value="0">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.border').remove()">×</button>
            </div>
        </div>
    `;
    container.appendChild(div);
    candidateIndex++;
}

// إضافة صف أولي
window.onload = function() {
    const container = document.getElementById('candidates-container');
    if (container) addCandidateRow();
    
    // إظهار تفاصيل الجلسة الأولى افتراضياً
    showSessionDetails();
}

function showSessionDetails() {
    const select = document.getElementById('sessionSelect');
    const detailsDiv = document.getElementById('sessionDetails');
    
    if (!select || !detailsDiv) return;
    
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption) {
        document.getElementById('detailCode').innerText = selectedOption.dataset.code || '';
        document.getElementById('detailDate').innerText = selectedOption.dataset.date || '';
        document.getElementById('detailTitle').innerText = selectedOption.dataset.title || '';
        document.getElementById('detailCount').innerText = selectedOption.dataset.count || '0';
        detailsDiv.style.display = 'block';
    }
}
</script>
</body>
</html>