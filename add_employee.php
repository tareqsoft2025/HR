<?php
require_once 'config.php';
requireLogin();

if (!hasRole(['hr_officer', 'admin'])) {
    die('غير مصرح لك بالوصول إلى هذه الصفحة');
}

$db = getDB();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    try {
        $db->beginTransaction();

        // بيانات الموظف الأساسية
        $name_ar = trim($_POST['name_ar'] ?? '');
        $name_en = trim($_POST['name_en'] ?? '');
        $id_number = trim($_POST['id_number'] ?? '');
        $birth_date = $_POST['birth_date'] ?? null;
        $gender = $_POST['gender'] ?? null;
        $marital_status = $_POST['marital_status'] ?? null;
        $birth_place = trim($_POST['birth_place'] ?? '');
        $governorate = $_POST['governorate'] ?? '';
        $city = trim($_POST['city'] ?? '');
        $neighborhood = trim($_POST['neighborhood'] ?? '');
        $nearest_landmark = trim($_POST['nearest_landmark'] ?? '');
        $family_members = intval($_POST['family_members'] ?? 0);
        $mobile = trim($_POST['mobile'] ?? '');
        $mobile_alt = trim($_POST['mobile_alt'] ?? '');
        $appointment_date = $_POST['appointment_date'] ?? null;

        if (empty($name_ar) || empty($id_number)) {
            throw new Exception('الاسم ورقم الهوية مطلوبان');
        }

        $employee_number = generateEmployeeNumber();

        // إدراج الموظف
        $job_title_id = intval($_POST['job_title_id'] ?? 0);
        
        $stmt = $db->prepare("INSERT INTO employees 
            (employee_number, name_ar, name_en, id_number, birth_date, gender, marital_status, birth_place, 
             governorate, city, neighborhood, nearest_landmark, family_members, mobile, mobile_alt, 
             appointment_date, job_title_id, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_approval', ?)");
        
        $stmt->execute([
            $employee_number, $name_ar, $name_en, $id_number, $birth_date, $gender, $marital_status, $birth_place,
            $governorate, $city, $neighborhood, $nearest_landmark, $family_members, $mobile, $mobile_alt,
            $appointment_date, $job_title_id ?: null, $_SESSION['user_id']
        ]);

        $employee_id = $db->lastInsertId();

        // إدراج المؤهلات (يمكن أكثر من واحد)
        if (!empty($_POST['qualifications'])) {
            foreach ($_POST['qualifications'] as $q) {
                if (!empty($q['degree'])) {
                    $db->prepare("INSERT INTO employee_qualifications (employee_id, degree, specialization, university, graduation_date) 
                                  VALUES (?, ?, ?, ?, ?)")
                       ->execute([$employee_id, $q['degree'], $q['specialization'] ?? '', $q['university'] ?? '', $q['graduation_date'] ?? null]);
                }
            }
        }

        // رفع ملف المقابلة / الامتحان
        $file_path = null;
        if (!empty($_FILES['interview_file']['name'])) {
            $ext = pathinfo($_FILES['interview_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'interview_' . $employee_id . '_' . time() . '.' . $ext;
            $file_path = 'uploads/' . $file_name;
            move_uploaded_file($_FILES['interview_file']['tmp_name'], __DIR__ . '/' . $file_path);
        }

        // تسجيل المقابلة الأولية (من HR)
        $db->prepare("INSERT INTO employee_approvals (employee_id, approver_role, status, notes, file_path, approved_by) 
                      VALUES (?, 'hr_officer', 'pending', ?, ?, ?)")
           ->execute([$employee_id, $_POST['interview_notes'] ?? '', $file_path, $_SESSION['user_id']]);

        $db->commit();

        logActivity('إضافة موظف جديد', "رقم الموظف: $employee_number - الاسم: $name_ar");

        $message = "تم إضافة الموظف بنجاح! الرقم الوظيفي: <strong>$employee_number</strong><br>الموظف الآن في انتظار موافقة لجنة المقابلات والمدير التنفيذي.";
        $messageType = 'success';

    } catch (Exception $e) {
        $db->rollBack();
        $message = 'حدث خطأ: ' . $e->getMessage();
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إضافة موظف جديد | جمعية أصدقاء البيئة الفلسطينية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .section-title { color: #2e7d32; border-bottom: 3px solid #4caf50; padding-bottom: 8px; }
        .dynamic-row { background: #f1f8e9; border-radius: 10px; margin-bottom: 10px; padding: 15px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-tree-fill me-2"></i>جمعية أصدقاء البيئة الفلسطينية</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3"><?= $_SESSION['full_name'] ?> (<?= $_SESSION['role'] ?>)</span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">خروج</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-success"><i class="bi bi-person-plus me-2"></i>إضافة موظف جديد</h3>
        <a href="dashboard.php" class="btn btn-outline-success">العودة للوحة التحكم</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="add_employee" value="1">

        <!-- البيانات الشخصية -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-person me-2"></i>البيانات الشخصية</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الاسم بالعربية <span class="text-danger">*</span></label>
                        <input type="text" name="name_ar" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">الاسم بالإنجليزية</label>
                        <input type="text" name="name_en" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم الهوية <span class="text-danger">*</span></label>
                        <input type="text" name="id_number" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">تاريخ الميلاد</label>
                        <input type="date" name="birth_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الجنس</label>
                        <select name="gender" class="form-select">
                            <option value="">اختر</option>
                            <option value="ذكر">ذكر</option>
                            <option value="أنثى">أنثى</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الحالة الاجتماعية</label>
                        <select name="marital_status" class="form-select">
                            <option value="">اختر</option>
                            <option value="أعزب">أعزب</option>
                            <option value="متزوج">متزوج</option>
                            <option value="مطلق">مطلق</option>
                            <option value="أرمل">أرمل</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">مكان الميلاد</label>
                        <input type="text" name="birth_place" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- العنوان -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>العنوان</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">المحافظة</label>
                        <select name="governorate" class="form-select">
                            <option value="">اختر المحافظة</option>
                            <option value="رفح">رفح</option>
                            <option value="خانيونس">خانيونس</option>
                            <option value="الوسطى">الوسطى</option>
                            <option value="غزة">غزة</option>
                            <option value="الشمال">الشمال</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المدينة</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الحي</label>
                        <input type="text" name="neighborhood" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">أقرب معلم</label>
                        <input type="text" name="nearest_landmark" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- بيانات الاتصال والأسرة -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-telephone me-2"></i>بيانات الاتصال والأسرة</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">عدد أفراد الأسرة</label>
                        <input type="number" name="family_members" class="form-control" value="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                        <input type="tel" name="mobile" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رقم جوال بديل</label>
                        <input type="tel" name="mobile_alt" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- المؤهلات العلمية (ديناميكي) -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white d-flex justify-content-between">
                <h5 class="mb-0"><i class="bi bi-mortarboard me-2"></i>المؤهلات العلمية (يمكن إضافة أكثر من مؤهل)</h5>
                <button type="button" class="btn btn-light btn-sm" onclick="addQualification()">+ إضافة مؤهل</button>
            </div>
            <div class="card-body" id="qualifications-container">
                <!-- يتم إضافة الصفوف ديناميكياً -->
            </div>
        </div>

        <!-- المسمى الوظيفي -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>المسمى الوظيفي</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">المسمى الوظيفي</label>
                        <select name="job_title_id" class="form-select">
                            <option value="">-- اختر المسمى الوظيفي --</option>
                            <?php 
                            $job_titles = $db->query("SELECT * FROM job_titles ORDER BY title")->fetchAll();
                            foreach ($job_titles as $jt): 
                            ?>
                                <option value="<?= $jt['id'] ?>"><?= $jt['title'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">يمكنك إضافة مسميات جديدة من صفحة "المسميات الوظيفية"</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- تاريخ التعيين -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>تاريخ التعيين</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">تاريخ التعيين</label>
                        <input type="date" name="appointment_date" class="form-control" id="appointment_date" onchange="checkProbation()">
                        <small id="probation_note" class="text-danger d-none">هذا الموظف تحت التعيين (أقل من 3 أشهر)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ملف المقابلة والملاحظات -->
        <div class="card mb-4 shadow-sm border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>ملف المقابلة / الامتحان + الملاحظات (إلزامي لبدء الإجراء)</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">رفع ملف المقابلة أو الامتحان (PDF أو صورة)</label>
                    <input type="file" name="interview_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="mb-3">
                    <label class="form-label">ملاحظات المقابلة</label>
                    <textarea name="interview_notes" class="form-control" rows="3" placeholder="اكتب ملاحظاتك هنا..."></textarea>
                </div>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-success btn-lg px-5">
                <i class="bi bi-check-circle me-2"></i>إضافة الموظف وبدء إجراءات الموافقة
            </button>
        </div>
    </form>
</div>

<script>
function addQualification() {
    const container = document.getElementById('qualifications-container');
    const div = document.createElement('div');
    div.className = 'dynamic-row mb-3';
    div.innerHTML = `
        <div class="row g-2">
            <div class="col-md-3">
                <select name="qualifications[][degree]" class="form-select" required>
                    <option value="">الدرجة العلمية</option>
                    <option value="دكتوراه">دكتوراه</option>
                    <option value="ماجستير">ماجستير</option>
                    <option value="بكالوريوس">بكالوريوس</option>
                    <option value="دبلوم">دبلوم</option>
                    <option value="ثانوية عامة">ثانوية عامة</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="qualifications[][specialization]" class="form-control" placeholder="التخصص">
            </div>
            <div class="col-md-3">
                <input type="text" name="qualifications[][university]" class="form-control" placeholder="الجامعة">
            </div>
            <div class="col-md-2">
                <input type="date" name="qualifications[][graduation_date]" class="form-control" placeholder="تاريخ التخرج">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.dynamic-row').remove()">×</button>
            </div>
        </div>
    `;
    container.appendChild(div);
}

// إضافة صف مؤهل افتراضي
window.onload = function() {
    addQualification();
}

function checkProbation() {
    const dateInput = document.getElementById('appointment_date').value;
    const note = document.getElementById('probation_note');
    if (!dateInput) return;
    
    const appointment = new Date(dateInput);
    const today = new Date();
    const diffTime = Math.abs(today - appointment);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 90) {
        note.classList.remove('d-none');
    } else {
        note.classList.add('d-none');
    }
}
</script>
</body>
</html>