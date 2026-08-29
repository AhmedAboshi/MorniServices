<?php

include('../../include/connected.php');

session_start();

require_once '../../vendor/autoload.php';

use Mpdf\Mpdf;


/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? 'ar';


/* =========================================================
   إعدادات الشركة
========================================================= */

/* =========================================================
   تحميل إعدادات الشركة
   جدول settings يعمل بنظام:
   setting_key / setting_value
========================================================= */

$settings = [];

$settingsQuery = $con->query("
    SELECT setting_key, setting_value
    FROM settings
");

if ($settingsQuery) {

    while ($setting = $settingsQuery->fetch_assoc()) {

        $settings[
            $setting['setting_key']
        ] = $setting['setting_value'];

    }

}


/* =========================================================
   معلومات الشركة
========================================================= */

$system_name = $settings['system_name'] ?? '';

$company_name =
    $settings['company_name']
    ?? $system_name
    ?? 'منصة الشرق الذكية';

$company_phone =
    $settings['company_phone']
    ?? '';

$company_email =
    $settings['company_email']
    ?? '';

$company_address =
    $settings['company_address']
    ?? '';

$company_website =
    $settings['company_website']
    ?? '';

$company_logo =
    $settings['company_logo']
    ?? '';


/* =========================================================
   الشعار
========================================================= */

/* =========================================================
   شعار الشركة - تحديد المسار بشكل آمن
========================================================= */

$logoHtml = '';

if (!empty($company_logo)) {

    /*
     * مجلد المشروع الرئيسي
     *
     * commissions_pdf.php
     * موجود داخل:
     *
     * admin/commission/
     *
     * لذلك ../../ يرجع إلى:
     *
     * AlSharqPlatform/
     */

    $projectRoot = realpath(__DIR__ . '/../../');


    /*
     * تنظيف قيمة الشعار
     */

    $logoValue = trim($company_logo);

    $logoValue = str_replace(
        ['\\', '//'],
        '/',
        $logoValue
    );


    /*
     * المسارات المحتملة
     */

    $possiblePaths = [];


    /* المسار كما هو داخل المشروع */

    $possiblePaths[] =
        $projectRoot . '/' .
        ltrim($logoValue, '/');


    /* إذا كانت القيمة تحتوي على uploads/ */

    if (
        strpos(
            strtolower($logoValue),
            'uploads/'
        ) === 0
    ) {

        $possiblePaths[] =
            $projectRoot . '/' .
            $logoValue;

    }


    /*
     * إذا كانت القيمة مجرد اسم ملف
     */

    $possiblePaths[] =
        $projectRoot .
        '/uploads/' .
        basename($logoValue);


    /*
     * مسار شائع لشعار الشركة
     */

    $possiblePaths[] =
        $projectRoot .
        '/uploads/settings/' .
        basename($logoValue);


    $logoPath = null;


    /*
     * البحث عن أول مسار موجود
     */

    foreach ($possiblePaths as $path) {

        if (
            is_file($path) &&
            is_readable($path)
        ) {

            $logoPath = $path;

            break;
        }

    }


    /*
     * إنشاء الصورة Base64
     */

    if ($logoPath !== null) {

        $logoData = base64_encode(
            file_get_contents($logoPath)
        );


        $extension = strtolower(
            pathinfo(
                $logoPath,
                PATHINFO_EXTENSION
            )
        );


        $mime = 'image/png';


        switch ($extension) {

            case 'jpg':
            case 'jpeg':

                $mime = 'image/jpeg';

                break;


            case 'gif':

                $mime = 'image/gif';

                break;


            case 'webp':

                $mime = 'image/webp';

                break;


            case 'svg':

                $mime = 'image/svg+xml';

                break;

        }


        $logoHtml = '
            <img
                src="data:' . $mime . ';base64,' . $logoData . '"
                style="
                    width:120px;
                    height:auto;
                    max-height:75px;
                "
            >
        ';

    }

}



/* =========================================================
   الفلاتر
========================================================= */

$where  = [];
$params = [];
$types  = '';


/* البحث */

if (!empty($_GET['search'])) {

    $search = trim($_GET['search']);

    $where[] = "
        (
            dc.commission_no LIKE ?
            OR d.name LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'ss';
}


/* السائق */

if (!empty($_GET['driver_id'])) {

    $where[] = 'dc.driver_id = ?';

    $params[] = (int)$_GET['driver_id'];

    $types .= 'i';
}


/* الخدمة */

if (!empty($_GET['service_id'])) {

    $where[] = 'dc.service_id = ?';

    $params[] = (int)$_GET['service_id'];

    $types .= 'i';
}


/* الجنسية */

if (!empty($_GET['nationality'])) {

    $where[] = 'dc.nationality = ?';

    $params[] = $_GET['nationality'];

    $types .= 's';
}


/* من تاريخ */

if (!empty($_GET['date_from'])) {

    $where[] = 'dc.period_start >= ?';

    $params[] = $_GET['date_from'];

    $types .= 's';
}


/* إلى تاريخ */

if (!empty($_GET['date_to'])) {

    $where[] = 'dc.period_end <= ?';

    $params[] = $_GET['date_to'];

    $types .= 's';
}


/* الحالة */

if (!empty($_GET['status'])) {

    $where[] = 'dc.status = ?';

    $params[] = $_GET['status'];

    $types .= 's';
}


/* =========================================================
   الاستعلام
========================================================= */

$sql = "

    SELECT

        dc.id,
        dc.commission_no,
        dc.driver_id,
        dc.service_id,
        dc.nationality,

        dc.week_number,
        dc.year_number,

        dc.period_start,
        dc.period_end,

        dc.total_orders,
        dc.commission_rate,
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
";


if (!empty($where)) {

    $sql .= ' WHERE ' . implode(' AND ', $where);

}


$sql .= ' ORDER BY dc.id DESC';


$stmt = $con->prepare($sql);


if (!$stmt) {

    die(
        'Database Error: ' .
        $con->error
    );

}


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );

}


$stmt->execute();

$result = $stmt->get_result();


/* =========================================================
   اسم السائق المختار
========================================================= */

$selectedDriverName = 'جميع السائقين';


if (!empty($_GET['driver_id'])) {

    $selectedDriverId = (int)$_GET['driver_id'];

    $driverStmt = $con->prepare("
        SELECT name
        FROM drivers
        WHERE id = ?
        LIMIT 1
    ");

    $driverStmt->bind_param(
        "i",
        $selectedDriverId
    );

    $driverStmt->execute();

    $driverResult = $driverStmt->get_result();

    if ($driverRow = $driverResult->fetch_assoc()) {

        $selectedDriverName =
            $driverRow['name'] ?? 'غير محدد';

    }

    $driverStmt->close();
}


/* =========================================================
   تجميع البيانات
========================================================= */

$rows = [];

$totalBase      = 0;
$totalBonus     = 0;
$totalDeduction = 0;
$totalNet       = 0;
$totalOrders    = 0;


while ($row = $result->fetch_assoc()) {

    $rows[] = $row;

    $totalBase += (float)$row['base_commission'];

    $totalBonus += (float)$row['total_bonus'];

    $totalDeduction += (float)$row['total_deduction'];

    $totalNet += (float)$row['net_commission'];

    $totalOrders += (int)$row['total_orders'];
}


/* =========================================================
   حالة التقرير
========================================================= */

$statusNames = [

    'draft'    => 'مسودة',
    'pending'  => 'قيد الانتظار',
    'approved' => 'معتمدة',
    'paid'     => 'مدفوعة',
    'cancelled'=> 'ملغاة'

];


/* =========================================================
   إنشاء HTML
========================================================= */

ob_start();

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<style>

body {

    font-family: dejavusans;

    direction: rtl;

    font-size: 10px;

    color: #222;

}

.company-header {

    width: 100%;

    border-bottom: 2px solid #2563eb;

    padding-bottom: 12px;

    margin-bottom: 15px;

}

.company-table {

    width: 100%;

    border-collapse: collapse;

}

.company-info {

    text-align: right;

    vertical-align: middle;

}

.company-info h1 {

    margin: 0;

    font-size: 18px;

    color: #1d4ed8;

}

.company-info div {

    margin-top: 4px;

    color: #555;

    font-size: 9px;

}

.report-title {

    text-align: center;

    font-size: 17px;

    font-weight: bold;

    margin: 15px 0;

    color: #111827;

}

.filter-box {

    width: 100%;

    background: #f3f4f6;

    border: 1px solid #ddd;

    padding: 8px;

    margin-bottom: 12px;

}

.filter-table {

    width: 100%;

    border-collapse: collapse;

}

.filter-table td {

    padding: 4px 8px;

}

table.data {

    width: 100%;

    border-collapse: collapse;

    margin-top: 10px;

}

table.data th {

    background: #2563eb;

    color: white;

    padding: 7px 4px;

    border: 1px solid #ddd;

    font-weight: bold;

}

table.data td {

    padding: 6px 4px;

    border: 1px solid #ddd;

    text-align: center;

}

table.data tr:nth-child(even) td {

    background: #f8fafc;

}

.summary {

    margin-top: 15px;

}

.summary-table {

    width: 100%;

    border-collapse: collapse;

}

.summary-table td {

    border: 1px solid #ddd;

    padding: 8px;

    text-align: center;

}

.summary-title {

    font-weight: bold;

    background: #f3f4f6;

}

.net {

    font-weight: bold;

    font-size: 12px;

}

.footer {

    margin-top: 20px;

    border-top: 1px solid #ddd;

    padding-top: 8px;

    text-align: center;

    font-size: 8px;

    color: #777;

}

</style>

</head>

<body>


<!-- =====================================================
     معلومات الشركة
===================================================== -->

<div class="company-header">

<table class="company-table">

<tr>

<td
    style="
        width:25%;
        text-align:right;
        vertical-align:middle;
    "
>

    <?= $logoHtml ?>

</td>

<?= $logoHtml ?>

</td>


<td class="company-info">

<h1>

<?= htmlspecialchars($company_name) ?>

</h1>


<?php if ($company_phone): ?>

<div>

📞 <?= htmlspecialchars($company_phone) ?>

</div>

<?php endif; ?>


<?php if ($company_email): ?>

<div>

✉ <?= htmlspecialchars($company_email) ?>

</div>

<?php endif; ?>


<?php if ($company_address): ?>

<div>

📍 <?= htmlspecialchars($company_address) ?>

</div>

<?php endif; ?>


<?php if ($company_website): ?>

<div>

🌐 <?= htmlspecialchars($company_website) ?>

</div>

<?php endif; ?>


</td>

</tr>

</table>

</div>


<!-- =====================================================
     عنوان التقرير
===================================================== -->

<div class="report-title">

تقرير عمولات السائقين

</div>


<!-- =====================================================
     معلومات التقرير
===================================================== -->

<div class="filter-box">

<table class="filter-table">

<tr>

<td>

<strong>السائق:</strong>

<?= htmlspecialchars($selectedDriverName) ?>

</td>


<td>

<strong>عدد السجلات:</strong>

<?= number_format(count($rows)) ?>

</td>


<td>

<strong>تاريخ التقرير:</strong>

<?= date('Y-m-d H:i') ?>

</td>

</tr>


<tr>

<td>

<strong>من:</strong>

<?= htmlspecialchars($_GET['date_from'] ?? 'كل الفترات') ?>

</td>


<td>

<strong>إلى:</strong>

<?= htmlspecialchars($_GET['date_to'] ?? 'كل الفترات') ?>

</td>


<td>

<strong>البحث:</strong>

<?= htmlspecialchars($_GET['search'] ?? 'بدون') ?>

</td>

</tr>

</table>

</div>


<!-- =====================================================
     جدول العمولات
===================================================== -->

<table class="data">

<thead>

<tr>

<th>#</th>

<th>رقم العمولة</th>

<th>السائق</th>

<th>الخدمة</th>

<th>الجنسية</th>

<th>الأسبوع</th>

<th>الفترة</th>

<th>الطلبات</th>

<th>الأساسية</th>

<th>المكافآت</th>

<th>الخصومات</th>

<th>الصافي</th>

<th>الحالة</th>

</tr>

</thead>


<tbody>

<?php if (!empty($rows)): ?>

<?php $counter = 1; ?>


<?php foreach ($rows as $row): ?>

<tr>

<td>

<?= $counter++ ?>

</td>


<td>

<?= htmlspecialchars(
    $row['commission_no'] ?? '-'
) ?>

</td>


<td>

<?= htmlspecialchars(
    $row['driver_name'] ?? 'غير محدد'
) ?>

</td>


<td>

<?= htmlspecialchars(
    $row['service_name'] ?? 'غير محددة'
) ?>

</td>


<td>

<?= htmlspecialchars(
    $row['nationality'] ?? '-'
) ?>

</td>


<td>

<?= (int)$row['week_number'] ?>

/

<?= (int)$row['year_number'] ?>

</td>


<td>

<?= htmlspecialchars(
    $row['period_start'] ?? '-'
) ?>

<br>

إلى

<br>

<?= htmlspecialchars(
    $row['period_end'] ?? '-'
) ?>

</td>


<td>

<?= number_format(
    (int)$row['total_orders']
) ?>

</td>


<td>

<?= number_format(
    (float)$row['base_commission'],
    2
) ?>

</td>


<td>

+

<?= number_format(
    (float)$row['total_bonus'],
    2
) ?>

</td>


<td>

-

<?= number_format(
    (float)$row['total_deduction'],
    2
) ?>

</td>


<td class="net">

<?= number_format(
    (float)$row['net_commission'],
    2
) ?>

</td>


<td>

<?= htmlspecialchars(
    $statusNames[$row['status']]
    ?? $row['status']
    ?? '-'
) ?>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="13">

لا توجد عمولات مطابقة للفلاتر المحددة.

</td>

</tr>

<?php endif; ?>

</tbody>

</table>


<!-- =====================================================
     الملخص
===================================================== -->

<div class="summary">

<table class="summary-table">

<tr>

<td class="summary-title">

إجمالي الطلبات

</td>

<td>

<?= number_format($totalOrders) ?>

</td>


<td class="summary-title">

العمولة الأساسية

</td>

<td>

<?= number_format($totalBase, 2) ?>

</td>


<td class="summary-title">

المكافآت

</td>

<td>

<?= number_format($totalBonus, 2) ?>

</td>


<td class="summary-title">

الخصومات

</td>

<td>

<?= number_format($totalDeduction, 2) ?>

</td>


<td class="summary-title">

صافي العمولات

</td>

<td class="net">

<?= number_format($totalNet, 2) ?>

</td>

</tr>

</table>

</div>


<!-- =====================================================
     التذييل
===================================================== -->

<div class="footer">

<?= htmlspecialchars($company_name) ?>

-

تقرير عمولات السائقين

-

تم إنشاء التقرير بتاريخ

<?= date('Y-m-d H:i') ?>

</div>


</body>

</html>

<?php

$html = ob_get_clean();


/* =========================================================
   إنشاء PDF
========================================================= */

$mpdf = new Mpdf([

    'mode' => 'utf-8',

    'format' => 'A4-L',

    'orientation' => 'L',

    'margin_top' => 10,

    'margin_bottom' => 12,

    'margin_left' => 8,

    'margin_right' => 8,

    'default_font' => 'dejavusans'

]);


$mpdf->SetTitle(
    'تقرير عمولات السائقين'
);


$mpdf->SetAuthor(
    $company_name
);


$mpdf->WriteHTML(
    $html
);


$filename =
    'driver_commissions_' .
    date('Y-m-d_H-i-s') .
    '.pdf';


$mpdf->Output(
    $filename,
    'D'
);

exit;