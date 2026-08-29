<?php

include('../../include/connected.php');

session_start();

/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';

/* =========================
   حماية الدخول
========================= */

// if(!isset($_SESSION['admin_id'])){
//     header("Location: admin.php");
//     exit;
// }


/* =========================
   إحصائيات العمولات
========================= */


/* =========================
   إجمالي العمولات
========================= */

$stmt = $con->prepare("
    SELECT 
        COALESCE(SUM(net_commission), 0) AS total_commissions
    FROM driver_commissions
");

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$total_commissions = $row['total_commissions'] ?? 0;

$stmt->close();


/* =========================
   عمولات الأسبوع الحالي
========================= */

$current_week = (int)date('W');
$current_year = (int)date('Y');

$stmt = $con->prepare("
    SELECT 
        COALESCE(SUM(net_commission), 0) AS weekly_commissions
    FROM driver_commissions
    WHERE week_number = ?
      AND year_number = ?
");

$stmt->bind_param(
    "ii",
    $current_week,
    $current_year
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$weekly_commissions = $row['weekly_commissions'] ?? 0;

$stmt->close();


/* =========================
   العمولات المسودة
========================= */

$status = "draft";

$stmt = $con->prepare("
    SELECT 
        COALESCE(SUM(net_commission), 0) AS pending_commissions
    FROM driver_commissions
    WHERE status = ?
");

$stmt->bind_param(
    "s",
    $status
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$pending_commissions = $row['pending_commissions'] ?? 0;

$stmt->close();


/* =========================
   العمولات المدفوعة
========================= */

$status = "paid";

$stmt = $con->prepare("
    SELECT 
        COALESCE(SUM(net_commission), 0) AS paid_commissions
    FROM driver_commissions
    WHERE status = ?
");

$stmt->bind_param(
    "s",
    $status
);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$paid_commissions = $row['paid_commissions'] ?? 0;

$stmt->close();


/* =========================
   إجمالي المكافآت
========================= */

$stmt = $con->prepare("
    SELECT 
        COALESCE(SUM(total_bonus), 0) AS total_bonus
    FROM driver_commissions
");

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$total_bonus = $row['total_bonus'] ?? 0;

$stmt->close();


/* =========================
   إجمالي الخصومات
========================= */

$stmt = $con->prepare("
    SELECT 
        COALESCE(SUM(total_deduction), 0) AS total_deductions
    FROM driver_commissions
");

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

$total_deductions = $row['total_deductions'] ?? 0;

$stmt->close();

?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">

<head>

<meta charset="UTF-8">

<title>
<?= $lang == 'ar' ? 'لوحة العمولات' : 'Commission Dashboard' ?>
</title>





<link rel="stylesheet" href="../assets/css/system.css?v=<?= time() ?>">

<style>
.commission-stats-grid {
    display: grid !important;
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    gap: 12px !important;
    width: 100% !important;
    margin: 0 0 20px 0 !important;
    padding: 0 !important;
}

.commission-stats-grid .commission-stat-card {
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
    min-height: 120px !important;
    height: 120px !important;

    padding: 14px !important;
    margin: 0 !important;

    box-sizing: border-box !important;

    display: flex !important;
    flex-direction: column !important;

    background: #ffffff !important;
    border: 1px solid #ddd !important;
    border-radius: 10px !important;

    box-shadow: 0 2px 8px rgba(0,0,0,.08) !important;
}

.commission-stats-grid .stat-header {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    margin: 0 !important;
    padding: 0 !important;
}

.commission-stats-grid .stat-icon {
    width: 34px !important;
    height: 34px !important;
    min-width: 34px !important;
    font-size: 17px !important;
}

.commission-stats-grid .stat-title {
    font-size: 13px !important;
}

.commission-stats-grid .stat-value {
    margin: 10px 0 4px 0 !important;
    font-size: 24px !important;
    line-height: 1.2 !important;
}

.commission-stats-grid .stat-footer {
    margin-top: auto !important;
    font-size: 11px !important;
}
</style>

</head>


<body>


<div class="container">

<h2 class="page-title">
💰 لوحة العمولات
</h2>

<!-- =========================
     بطاقات إحصائيات العمولات
========================= -->

<div class="commission-stats-grid">

    <!-- إجمالي العمولات -->
    <div class="commission-stat-card">
        <div class="stat-header">
            <div class="stat-icon">💰</div>
            <div class="stat-title">إجمالي العمولات</div>
        </div>

        <div class="stat-value">
            <?= number_format($total_commissions, 2) ?>
        </div>

        <div class="stat-footer">
            آخر تحديث : اليوم
        </div>
    </div>


    <!-- هذا الأسبوع -->
    <div class="commission-stat-card">
        <div class="stat-header">
            <div class="stat-icon">📅</div>
            <div class="stat-title">هذا الأسبوع</div>
        </div>

        <div class="stat-value">
            <?= number_format($weekly_commissions, 2) ?>
        </div>

        <div class="stat-footer">
            آخر تحديث : اليوم
        </div>
    </div>


    <!-- مسودة -->
    <div class="commission-stat-card">
        <div class="stat-header">
            <div class="stat-icon">⏳</div>
            <div class="stat-title">مسودة</div>
        </div>

        <div class="stat-value">
            <?= number_format($pending_commissions, 2) ?>
        </div>

        <div class="stat-footer">
            بانتظار الاعتماد
        </div>
    </div>


    <!-- مدفوعة -->
    <div class="commission-stat-card">
        <div class="stat-header">
            <div class="stat-icon">✅</div>
            <div class="stat-title">مدفوعة</div>
        </div>

        <div class="stat-value">
            <?= number_format($paid_commissions, 2) ?>
        </div>

        <div class="stat-footer">
            تم الصرف
        </div>
    </div>


    <!-- المكافآت -->
    <div class="commission-stat-card">
        <div class="stat-header">
            <div class="stat-icon">🎁</div>
            <div class="stat-title">المكافآت</div>
        </div>

        <div class="stat-value">
            <?= number_format($total_bonus, 2) ?>
        </div>

        <div class="stat-footer">
            إجمالي المكافآت
        </div>
    </div>


    <!-- الخصومات -->
    <div class="commission-stat-card">
        <div class="stat-header">
            <div class="stat-icon">➖</div>
            <div class="stat-title">الخصومات</div>
        </div>

        <div class="stat-value">
            <?= number_format($total_deductions, 2) ?>
        </div>

        <div class="stat-footer">
            إجمالي الخصومات
        </div>
    </div>

</div>

<!-- =========================
     جدول العمولات
========================= -->

<!-- =========================
     رأس سجل العمولات
========================= -->

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

    <div class="commission-section-header">

    <div class="commission-section-title">

        <h5>
            📋 سجل العمولات
        </h5>

        <small>
            إدارة ومراجعة عمولات السائقين
        </small>

    </div>


    <div class="commission-actions">

        <a
            href="commission_calculator.php?lang=<?= urlencode($lang) ?>"
            class="btn btn-primary"
        >
            🧮 حاسبة العمولات
        </a>


        <a
            href="commission_payments.php?lang=<?= urlencode($lang) ?>"
            class="btn btn-success"
        >
            💳 المدفوعات
        </a>


        <a
            href="commissions_excel.php?<?= http_build_query($_GET) ?>"
            class="btn btn-outline-success"
        >
            📊 Excel
        </a>


        <a
            href="commissions_pdf.php?<?= http_build_query($_GET) ?>"
            class="btn btn-outline-danger"
        >
            📄 PDF
        </a>

    </div>

</div>

<!-- =========================================================
     فلاتر سجل العمولات
========================================================= -->

<div class="commission-filters">

    <form method="GET" action="commissions_dashboard.php">

        <input
            type="hidden"
            name="lang"
            value="<?= htmlspecialchars($lang) ?>"
        >


        <!-- البحث -->

        <div class="commission-filter-group search">

            <label>
                🔎 البحث
            </label>

            <input
                type="text"
                name="search"
                class="form-control"
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                placeholder="رقم العمولة أو اسم السائق..."
            >

        </div>


        <!-- السائق -->

        <div class="commission-filter-group">

            <label>
                👤 السائق
            </label>

            <select
                name="driver_id"
                class="form-control"
            >

                <option value="">
                    جميع السائقين
                </option>

                <?php foreach ($drivers as $driver): ?>

                    <option
                        value="<?= (int)$driver['id'] ?>"
                        <?= (
                            ($_GET['driver_id'] ?? '') ==
                            $driver['id']
                        ) ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars(
                            $driver['full_name'] ?? '-'
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- الخدمة -->

        <div class="commission-filter-group">

            <label>
                🚚 الخدمة
            </label>

            <select
                name="service_id"
                class="form-control"
            >

                <option value="">
                    جميع الخدمات
                </option>

                <?php foreach ($services as $service): ?>

                    <option
                        value="<?= (int)$service['id'] ?>"
                        <?= (
                            ($_GET['service_id'] ?? '') ==
                            $service['id']
                        ) ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars(
                            $service['service_name'] ?? '-'
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- الجنسية -->

        <div class="commission-filter-group">

            <label>
                🌍 الجنسية
            </label>

            <select
                name="nationality"
                class="form-control"
            >

                <option value="">
                    جميع الجنسيات
                </option>

                <?php foreach ($nationalities as $nationality): ?>

                    <option
                        value="<?= htmlspecialchars($nationality) ?>"
                        <?= (
                            ($_GET['nationality'] ?? '') ==
                            $nationality
                        ) ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($nationality) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <!-- من تاريخ -->

        <div class="commission-filter-group">

            <label>
                📅 من تاريخ
            </label>

            <input
                type="date"
                name="date_from"
                class="form-control"
                value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>"
            >

        </div>


        <!-- إلى تاريخ -->

        <div class="commission-filter-group">

            <label>
                📅 إلى تاريخ
            </label>

            <input
                type="date"
                name="date_to"
                class="form-control"
                value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>"
            >

        </div>


        <!-- الحالة -->

        <div class="commission-filter-group">

            <label>
                📌 الحالة
            </label>

            <select
                name="status"
                class="form-control"
            >

                <option value="">
                    جميع الحالات
                </option>

                <option
                    value="draft"
                    <?= ($_GET['status'] ?? '') === 'draft'
                        ? 'selected'
                        : '' ?>
                >
                    مسودة
                </option>

                <option
                    value="approved"
                    <?= ($_GET['status'] ?? '') === 'approved'
                        ? 'selected'
                        : '' ?>
                >
                    معتمدة
                </option>

                <option
                    value="paid"
                    <?= ($_GET['status'] ?? '') === 'paid'
                        ? 'selected'
                        : '' ?>
                >
                    مدفوعة
                </option>

            </select>

        </div>


        <!-- الأزرار -->

        <div class="commission-filter-actions">

            <button
                type="submit"
                class="btn btn-primary"
            >
                🔍 تطبيق الفلاتر
            </button>


            <a
                href="commissions_dashboard.php?lang=<?= urlencode($lang) ?>"
                class="btn btn-secondary"
            >
                🔄 إعادة تعيين
            </a>

        </div>

    </form>

</div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>رقم العمولة</th>

                        <th>السائق</th>

                        <th>الخدمة</th>

                        <th>الجنسية</th>

                        <th>الفترة</th>

                        <th>الطلبات</th>

                        <th>الأساسية</th>

                        <th>المكافآت</th>

                        <th>الخصومات</th>

                        <th>الصافي</th>

                        <th>الحالة</th>

                        <th>الإجراءات</th>

                    </tr>

                </thead>


                <tbody>

<?php

/* =========================
   فلاتر العمولات
========================= */

$search      = trim($_GET['search'] ?? '');
$driver_id   = (int)($_GET['driver_id'] ?? 0);
$service_id  = (int)($_GET['service_id'] ?? 0);
$nationality = trim($_GET['nationality'] ?? '');
$date_from   = trim($_GET['date_from'] ?? '');
$date_to     = trim($_GET['date_to'] ?? '');
$status      = trim($_GET['status'] ?? '');


/* =========================
   تحميل العمولات
========================= */

$sql = "
    SELECT
        dc.id,
        dc.commission_no,
        dc.driver_id,
        dc.service_id,
        dc.nationality,
        dc.period_start,
        dc.period_end,
        dc.total_orders,
        dc.base_commission,
        dc.total_bonus,
        dc.total_deduction,
        dc.net_commission,
        dc.status,

        d.name AS driver_name,

        cs.service_name

    FROM driver_commissions dc

    LEFT JOIN drivers d
        ON d.id = dc.driver_id

    LEFT JOIN commission_services cs
        ON cs.id = dc.service_id

    WHERE 1 = 1
";


/* =========================
   شروط البحث
========================= */

$params = [];
$types  = '';


/* البحث */

if ($search !== '') {

    $sql .= "
        AND (
            dc.commission_no LIKE ?
            OR d.name LIKE ?
        )
    ";

    $search_like = '%' . $search . '%';

    $params[] = $search_like;
    $params[] = $search_like;

    $types .= 'ss';
}


/* السائق */

if ($driver_id > 0) {

    $sql .= "
        AND dc.driver_id = ?
    ";

    $params[] = $driver_id;

    $types .= 'i';
}


/* الخدمة */

if ($service_id > 0) {

    $sql .= "
        AND dc.service_id = ?
    ";

    $params[] = $service_id;

    $types .= 'i';
}


/* الجنسية */

if ($nationality !== '') {

    $sql .= "
        AND dc.nationality = ?
    ";

    $params[] = $nationality;

    $types .= 's';
}


/* من تاريخ */

if ($date_from !== '') {

    $sql .= "
        AND dc.period_end >= ?
    ";

    $params[] = $date_from;

    $types .= 's';
}


/* إلى تاريخ */

if ($date_to !== '') {

    $sql .= "
        AND dc.period_start <= ?
    ";

    $params[] = $date_to;

    $types .= 's';
}


/* الحالة */

if (
    $status !== '' &&
    in_array($status, ['draft', 'approved', 'paid'], true)
) {

    $sql .= "
        AND dc.status = ?
    ";

    $params[] = $status;

    $types .= 's';
}


/* =========================
   الترتيب
========================= */

$sql .= "
    ORDER BY dc.id DESC
";


/* =========================
   تنفيذ الاستعلام
========================= */

$stmt = $con->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();


$result = $stmt->get_result();


$counter = 1;


/* =========================
   عرض البيانات
========================= */

if($result->num_rows > 0):

    while($row = $result->fetch_assoc()):

?>

                    <tr>

                        <!-- الرقم -->
                        <td>
                            <?= $counter++ ?>
                        </td>


                        <!-- رقم العمولة -->
                        <td>

                            <strong>
                                <?= htmlspecialchars(
                                    $row['commission_no'] ?? '-'
                                ) ?>
                            </strong>

                        </td>


                        <!-- السائق -->
                        <td>

                            <?= htmlspecialchars(
                                $row['driver_name'] ?? 'غير محدد'
                            ) ?>

                        </td>


                        <!-- الخدمة -->
                        <td>

                            <?= htmlspecialchars(
                                $row['service_name'] ?? 'غير محددة'
                            ) ?>

                        </td>


                        <!-- الجنسية -->
                        <td>

                            <?= htmlspecialchars(
                                $row['nationality'] ?? '-'
                            ) ?>

                        </td>


                        <!-- الفترة -->
                       <td>

    <div>
        <?= htmlspecialchars($row['period_start'] ?? 'NULL') ?>
    </div>

    <small class="text-muted">
        إلى
        <?= htmlspecialchars($row['period_end'] ?? 'NULL') ?>
    </small>

</td>


                        <!-- عدد الطلبات -->
                        <td>

                            <?= number_format(
                                (int)$row['total_orders']
                            ) ?>

                        </td>


                        <!-- العمولة الأساسية -->
                        <td>

                            <?= number_format(
                                (float)$row['base_commission'],
                                2
                            ) ?>

                        </td>


                        <!-- المكافآت -->
                        <td class="text-success">

                            +
                            <?= number_format(
                                (float)$row['total_bonus'],
                                2
                            ) ?>

                        </td>


                        <!-- الخصومات -->
                        <td class="text-danger">

                            -
                            <?= number_format(
                                (float)$row['total_deduction'],
                                2
                            ) ?>

                        </td>


                        <!-- الصافي -->
                        <td>

                            <strong>

                                <?= number_format(
                                    (float)$row['net_commission'],
                                    2
                                ) ?>

                            </strong>

                        </td>


                        <!-- الحالة -->
                        <td>

<?php

$status = $row['status'] ?? 'draft';

switch($status){

    case 'draft':

        echo '<span class="badge bg-secondary">
                مسودة
              </span>';

        break;


    case 'pending':

        echo '<span class="badge bg-warning text-dark">
                بانتظار الاعتماد
              </span>';

        break;


    case 'approved':

        echo '<span class="badge bg-info">
                معتمدة
              </span>';

        break;


    case 'paid':

        echo '<span class="badge bg-success">
                مدفوعة
              </span>';

        break;


    case 'cancelled':

        echo '<span class="badge bg-danger">
                ملغاة
              </span>';

        break;


    default:

        echo '<span class="badge bg-secondary">'
            . htmlspecialchars($status)
            . '</span>';

}

?>

                        </td>


                        <!-- الإجراءات -->
                        <td>

                            <div class="d-flex gap-1">

                                <a
                                    href="commission_details.php?id=<?= (int)$row['id'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="التفاصيل"
                                >
                                    👁️
                                </a>


<?php if($status === 'draft' || $status === 'pending'): ?>

                                <a
                                    href="commission_approve.php?id=<?= (int)$row['id'] ?>"
                                    class="btn btn-sm btn-outline-success"
                                    title="اعتماد"
                                    onclick="return confirm('هل تريد اعتماد هذه العمولة؟');"
                                >
                                    ✅
                                </a>

<?php endif; ?>


<?php if($status === 'approved'): ?>

                                <a
                                    href="commission_pay.php?id=<?= (int)$row['id'] ?>"
                                    class="btn btn-sm btn-outline-success"
                                    title="تسجيل الدفع"
                                    onclick="return confirm('هل تريد تسجيل دفع هذه العمولة؟');"
                                >
                                    💵
                                </a>

<?php endif; ?>

                            </div>

                        </td>

                    </tr>

<?php

    endwhile;

else:

?>

                    <tr>

                        <td
                            colspan="13"
                            class="text-center text-muted py-4"
                        >

                            لا توجد عمولات مسجلة حتى الآن.

                        </td>

                    </tr>

<?php

endif;

$stmt->close();

?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>

</html>