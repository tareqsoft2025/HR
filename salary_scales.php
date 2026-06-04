<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$message = '';

// إضافة شريحة جديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_scale'])) {
    $year = intval($_POST['year']);
    $grade = trim($_POST['grade']);
    $level = trim($_POST['level']);
    
    $s1 = floatval($_POST['s1'] ?? 0);
    $s2 = floatval($_POST['s2'] ?? 0);
    $s3 = floatval($_POST['s3'] ?? 0);
    $s4 = floatval($_POST['s4'] ?? 0);
    $s5 = floatval($_POST['s5'] ?? 0);
    $s6 = floatval($_POST['s6'] ?? 0);
    $s7 = floatval($_POST['s7'] ?? 0);
    $s8 = floatval($_POST['s8'] ?? 0);

    if ($year && $grade && $level) {
        $db->prepare("INSERT INTO salary_scale_entries (year, grade, level, s1, s2, s3, s4, s5, s6, s7, s8) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
           ->execute([$year, $grade, $level, $s1, $s2, $s3, $s4, $s5, $s6, $s7, $s8]);
        
        $message = 'تمت إضافة الشريحة الجديدة بنجاح';
        logActivity('إضافة شريحة جديدة في سلم الأجور', "$grade - $level");
    }
}

// جلب الشرائح المضافة ديناميكياً
$custom_scales = $db->query("SELECT * FROM salary_scale_entries ORDER BY year, grade, level")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>سلم الأجور | جمعية أصدقاء البيئة الفلسطينية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .salary-table th { background: #2e7d32; color: white; text-align: center; }
        .salary-table td { text-align: center; vertical-align: middle; }
        .salary-table .level-header { background: #1b5e20; color: white; font-weight: bold; }
        .salary-value { font-weight: 600; color: #1b5e20; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">نظام HR - جمعية أصدقاء البيئة الفلسطينية</a>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="bi bi-cash-stack me-2"></i>سلم الأجور</h3>
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addScaleModal">
                <i class="bi bi-plus-circle me-2"></i>إضافة شريحة جديدة
            </button>
            <a href="dashboard.php" class="btn btn-outline-success">العودة</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <div class="alert alert-success">
        <strong>ملاحظة:</strong> هذا سلم الأجور الرسمي لجمعية أصدقاء البيئة الفلسطينية (2026)
    </div>

    <!-- الجدول الرئيسي الجميل -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover salary-table">
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle">السنة</th>
                    <th rowspan="2" class="align-middle">الدرجة</th>
                    <th rowspan="2" class="align-middle">المستوى</th>
                    <th colspan="8" class="text-center level-header">الفئات (s1 - s8)</th>
                </tr>
                <tr>
                    <th class="level-header">s1</th>
                    <th class="level-header">s2</th>
                    <th class="level-header">s3</th>
                    <th class="level-header">s4</th>
                    <th class="level-header">s5</th>
                    <th class="level-header">s6</th>
                    <th class="level-header">s7</th>
                    <th class="level-header">s8</th>
                </tr>
            </thead>
            <tbody>
                <!-- السنة 1 - الدرجة 5 -->
                <tr>
                    <td rowspan="5" class="fw-bold bg-light">السنة 1</td>
                    <td rowspan="5" class="fw-bold">الدرجة 5</td>
                    <td>المستوى 1</td>
                    <td class="salary-value">2,800</td>
                    <td class="salary-value">2,950</td>
                    <td class="salary-value">3,100</td>
                    <td class="salary-value">3,250</td>
                    <td class="salary-value">3,400</td>
                    <td class="salary-value">3,550</td>
                    <td class="salary-value">3,700</td>
                    <td class="salary-value">3,850</td>
                </tr>
                <tr>
                    <td>المستوى 2</td>
                    <td class="salary-value">3,000</td>
                    <td class="salary-value">3,150</td>
                    <td class="salary-value">3,300</td>
                    <td class="salary-value">3,450</td>
                    <td class="salary-value">3,600</td>
                    <td class="salary-value">3,750</td>
                    <td class="salary-value">3,900</td>
                    <td class="salary-value">4,050</td>
                </tr>
                <tr>
                    <td>المستوى 3</td>
                    <td class="salary-value">3,200</td>
                    <td class="salary-value">3,350</td>
                    <td class="salary-value">3,500</td>
                    <td class="salary-value">3,650</td>
                    <td class="salary-value">3,800</td>
                    <td class="salary-value">3,950</td>
                    <td class="salary-value">4,100</td>
                    <td class="salary-value">4,250</td>
                </tr>
                <tr>
                    <td>المستوى 4</td>
                    <td class="salary-value">3,400</td>
                    <td class="salary-value">3,550</td>
                    <td class="salary-value">3,700</td>
                    <td class="salary-value">3,850</td>
                    <td class="salary-value">4,000</td>
                    <td class="salary-value">4,150</td>
                    <td class="salary-value">4,300</td>
                    <td class="salary-value">4,450</td>
                </tr>
                <tr>
                    <td>المستوى 5</td>
                    <td class="salary-value">3,600</td>
                    <td class="salary-value">3,750</td>
                    <td class="salary-value">3,900</td>
                    <td class="salary-value">4,050</td>
                    <td class="salary-value">4,200</td>
                    <td class="salary-value">4,350</td>
                    <td class="salary-value">4,500</td>
                    <td class="salary-value">4,650</td>
                </tr>

                <!-- السنة 2 - الدرجة 6 -->
                <tr>
                    <td rowspan="5" class="fw-bold bg-light">السنة 2</td>
                    <td rowspan="5" class="fw-bold">الدرجة 6</td>
                    <td>المستوى 1</td>
                    <td class="salary-value">3,800</td>
                    <td class="salary-value">3,950</td>
                    <td class="salary-value">4,100</td>
                    <td class="salary-value">4,250</td>
                    <td class="salary-value">4,400</td>
                    <td class="salary-value">4,550</td>
                    <td class="salary-value">4,700</td>
                    <td class="salary-value">4,850</td>
                </tr>
                <tr>
                    <td>المستوى 2</td>
                    <td class="salary-value">4,000</td>
                    <td class="salary-value">4,150</td>
                    <td class="salary-value">4,300</td>
                    <td class="salary-value">4,450</td>
                    <td class="salary-value">4,600</td>
                    <td class="salary-value">4,750</td>
                    <td class="salary-value">4,900</td>
                    <td class="salary-value">5,050</td>
                </tr>
                <tr>
                    <td>المستوى 3</td>
                    <td class="salary-value">4,200</td>
                    <td class="salary-value">4,350</td>
                    <td class="salary-value">4,500</td>
                    <td class="salary-value">4,650</td>
                    <td class="salary-value">4,800</td>
                    <td class="salary-value">4,950</td>
                    <td class="salary-value">5,100</td>
                    <td class="salary-value">5,250</td>
                </tr>
                <tr>
                    <td>المستوى 4</td>
                    <td class="salary-value">4,400</td>
                    <td class="salary-value">4,550</td>
                    <td class="salary-value">4,700</td>
                    <td class="salary-value">4,850</td>
                    <td class="salary-value">5,000</td>
                    <td class="salary-value">5,150</td>
                    <td class="salary-value">5,300</td>
                    <td class="salary-value">5,450</td>
                </tr>
                <tr>
                    <td>المستوى 5</td>
                    <td class="salary-value">4,600</td>
                    <td class="salary-value">4,750</td>
                    <td class="salary-value">4,900</td>
                    <td class="salary-value">5,050</td>
                    <td class="salary-value">5,200</td>
                    <td class="salary-value">5,350</td>
                    <td class="salary-value">5,500</td>
                    <td class="salary-value">5,650</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- الشرائح المضافة يدوياً -->
    <?php if (!empty($custom_scales)): ?>
    <h5 class="mt-4">الشرائح المضافة يدوياً</h5>
    <table class="table table-bordered">
        <thead class="table-success">
            <tr>
                <th>السنة</th>
                <th>الدرجة</th>
                <th>المستوى</th>
                <th>s1</th><th>s2</th><th>s3</th><th>s4</th><th>s5</th><th>s6</th><th>s7</th><th>s8</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($custom_scales as $row): ?>
            <tr>
                <td><?= $row['year'] ?></td>
                <td><?= $row['grade'] ?></td>
                <td><?= $row['level'] ?></td>
                <td><?= number_format($row['s1'], 0) ?></td>
                <td><?= number_format($row['s2'], 0) ?></td>
                <td><?= number_format($row['s3'], 0) ?></td>
                <td><?= number_format($row['s4'], 0) ?></td>
                <td><?= number_format($row['s5'], 0) ?></td>
                <td><?= number_format($row['s6'], 0) ?></td>
                <td><?= number_format($row['s7'], 0) ?></td>
                <td><?= number_format($row['s8'], 0) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- مودال إضافة شريحة جديدة -->
<div class="modal fade" id="addScaleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">إضافة شريحة جديدة في سلم الأجور</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">السنة</label>
                            <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">الدرجة</label>
                            <input type="text" name="grade" class="form-control" placeholder="مثال: الدرجة 8" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">المستوى</label>
                            <input type="text" name="level" class="form-control" placeholder="مثال: المستوى 3" required>
                        </div>
                        
                        <div class="col-12"><hr></div>
                        
                        <div class="col-md-3">
                            <label class="form-label">s1</label>
                            <input type="number" step="0.01" name="s1" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">s2</label>
                            <input type="number" step="0.01" name="s2" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">s3</label>
                            <input type="number" step="0.01" name="s3" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">s4</label>
                            <input type="number" step="0.01" name="s4" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">s5</label>
                            <input type="number" step="0.01" name="s5" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">s6</label>
                            <input type="number" step="0.01" name="s6" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">s7</label>
                            <input type="number" step="0.01" name="s7" class="form-control" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">s8</label>
                            <input type="number" step="0.01" name="s8" class="form-control" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="add_scale" class="btn btn-success">حفظ الشريحة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>