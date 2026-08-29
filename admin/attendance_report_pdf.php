<?php

session_start();

include('../include/connected.php');
include('../include/settings.php');

require_once __DIR__ . '/../vendor/autoload.php';

use Mpdf\Mpdf;

/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   الفلاتر - نفس attendance_report.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$driver_id = (int)($_GET['driver_id'] ?? 0);

$status_filter = trim($_GET['status'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'title'            => 'تقرير الحضور والانصراف',
        'company_report'   => 'تقرير حضور وانصراف السائقين',

        'id'               => '#',
        'image'            => 'الصورة',
        'name'             => 'اسم السائق',
        'phone'            => 'الجوال',
        'work_area'        => 'منطقة العمل',
        'date'             => 'التاريخ',
        'check_in'         => 'الحضور',
        'check_out'        => 'الانصراف',
        'status'            => 'الحالة',

        'present'          => 'حاضر',
        'late'             => 'متأخر',
        'absent'           => 'غائب',

        'filters'          => 'الفلاتر المستخدمة',
        'search_filter'    => 'البحث',
        'driver_filter'    => 'السائق',
        'status_filter'    => 'الحالة',
        'from_filter'      => 'من',
        'to_filter'        => 'إلى',

        'all_records'      => 'جميع السجلات',

        'total_records'    => 'إجمالي السجلات',
        'total_present'    => 'إجمالي الحضور',
        'total_late'       => 'إجمالي التأخير',
        'total_absent'     => 'إجمالي الغياب',
        'total_drivers'    => 'عدد السائقين',

        'no_data'          => 'لا توجد سجلات مطابقة للفلاتر المحددة',

        'generated_at'     => 'تاريخ إنشاء التقرير',

        'sar'              => 'ريال'

    ],

    'en' => [

        'title'            => 'Attendance Report',
        'company_report'   => 'Driver Attendance Report',

        'id'               => '#',
        'image'            => 'Image',
        'name'             => 'Driver Name',
        'phone'            => 'Phone',
        'work_area'        => 'Work Area',
        'date'             => 'Date',
        'check_in'         => 'Check In',
        'check_out'        => 'Check Out',
        'status'            => 'Status',

        'present'          => 'Present',
        'late'             => 'Late',
        'absent'           => 'Absent',

        'filters'          => 'Applied Filters',
        'search_filter'    => 'Search',
        'driver_filter'   => 'Driver',
        'status_filter'   => 'Status',
        'from_filter'     => 'From',
        'to_filter'       => 'To',

        'all_records'      => 'All Records',

        'total_records'    => 'Total Records',
        'total_present'    => 'Total Present',
        'total_late'       => 'Total Late',
        'total_absent'     => 'Total Absent',
        'total_drivers'    => 'Total Drivers',

        'no_data'          => 'No attendance records match the selected filters',

        'generated_at'     => 'Generated At',

        'sar'              => 'SAR'

    ]

];

$t = $text[$lang];

/* =========================================================
   WHERE
========================================================= */

$where = " WHERE 1 = 1 ";

$params = [];

$types = "";

/* =========================================================
   البحث
========================================================= */

if ($search !== '') {

    $where .= "
        AND (
            d.name LIKE ?
            OR d.phone LIKE ?
            OR d.work_area LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= "sss";
}

/* =========================================================
   السائق
========================================================= */

if ($driver_id > 0) {

    $where .= "
        AND a.driver_id = ?
    ";

    $params[] = $driver_id;

    $types .= "i";
}

/* =========================================================
   الحالة
========================================================= */

if ($status_filter !== '') {

    if ($status_filter === 'present') {

        $where .= "
            AND (
                a.status = 'present'
                OR a.status = 'حاضر'
                OR a.status = ''
                OR a.status IS NULL
            )
        ";

    } elseif ($status_filter === 'late') {

        $where .= "
            AND (
                a.status = 'late'
                OR a.status = 'متأخر'
            )
        ";

    } elseif ($status_filter === 'absent') {

        $where .= "
            AND (
                a.status = 'absent'
                OR a.status = 'غائب'
            )
        ";
    }
}

/* =========================================================
   من تاريخ
========================================================= */

if ($from !== '') {

    $where .= "
        AND a.attendance_date >= ?
    ";

    $params[] = $from;

    $types .= "s";
}

/* =========================================================
   إلى تاريخ
========================================================= */

if ($to !== '') {

    $where .= "
        AND a.attendance_date <= ?
    ";

    $params[] = $to;

    $types .= "s";
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "

    SELECT

        a.id,
        a.driver_id,
        a.attendance_date,
        a.check_in,
        a.check_out,
        a.status,

        d.name,
        d.phone,
        d.work_area,
        d.imagedriver

    FROM attendance a

    INNER JOIN drivers d
        ON d.id = a.driver_id

    $where

    ORDER BY
        a.attendance_date DESC,
        a.id DESC
";

/* =========================================================
   Prepare
========================================================= */

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

/* =========================================================
   Bind
========================================================= */

if (!empty($params)) {

    if (strlen($types) !== count($params)) {

        die(
            'Filter parameters mismatch.'
        );
    }

    $stmt->bind_param(
        $types,
        ...$params
    );
}

/* =========================================================
   Execute
========================================================= */

if (!$stmt->execute()) {

    die(
        'Execute Error: ' .
        htmlspecialchars($stmt->error)
    );
}

$result = $stmt->get_result();

/* =========================================================
   البيانات والإحصائيات
========================================================= */

$rows = [];

$presentCount = 0;

$lateCount = 0;

$absentCount = 0;

while ($row = $result->fetch_assoc()) {

    $status =
        trim(
            (string)(
                $row['status'] ?? ''
            )
        );

    if (
        strtolower($status) === 'late'
        || $status === 'متأخر'
    ) {

        $row['status_text'] =
            $t['late'];

        $row['status_class'] =
            'status-late';

        $row['status_color'] =
            '#dc3545';

        $lateCount++;

    } elseif (
        strtolower($status) === 'absent'
        || $status === 'غائب'
    ) {

        $row['status_text'] =
            $t['absent'];

        $row['status_class'] =
            'status-absent';

        $row['status_color'] =
            '#dc3545';

        $absentCount++;

    } else {

        $row['status_text'] =
            $t['present'];

        $row['status_class'] =
            'status-present';

        $row['status_color'] =
            '#198754';

        $presentCount++;
    }

    $rows[] = $row;
}

$totalRecords =
    count($rows);

/* =========================================================
   إجمالي السائقين
========================================================= */

$totalDrivers = 0;

$q = $con->query("
    SELECT COUNT(*) AS total
    FROM drivers
");

if ($q) {

    $totalDrivers =
        (int)(
            $q->fetch_assoc()['total']
            ?? 0
        );
}

/* =========================================================
   بيانات السائق المحدد
========================================================= */

$selectedDriverName = '';

if ($driver_id > 0) {

    $driverStmt = $con->prepare("
        SELECT name
        FROM drivers
        WHERE id = ?
        LIMIT 1
    ");

    if ($driverStmt) {

        $driverStmt->bind_param(
            "i",
            $driver_id
        );

        $driverStmt->execute();

        $driverRow =
            $driverStmt
                ->get_result()
                ->fetch_assoc();

        if ($driverRow) {

            $selectedDriverName =
                $driverRow['name'];
        }

        $driverStmt->close();
    }
}

/* =========================================================
   إعدادات الشركة
========================================================= */

$settingsData = [];

$settingsResult = $con->query("
    SELECT
        setting_key,
        setting_value
    FROM settings
");

if ($settingsResult) {

    while (
        $setting =
        $settingsResult->fetch_assoc()
    ) {

        $settingsData[
            $setting['setting_key']
        ] =
            $setting['setting_value'];
    }
}

$companyName =
    $settingsData['company_name']
    ?? 'AlSharqPlatform';

/* =========================================================
   إنشاء mPDF
========================================================= */

$mpdf = new Mpdf([

    'mode' =>
        'utf-8',

    'format' =>
        'A4-L',

    'orientation' =>
        'L',

    'margin_left' =>
        6,

    'margin_right' =>
        6,

    'margin_top' =>
        10,

    'margin_bottom' =>
        10,

    'default_font' =>
        'dejavusans'

]);

$mpdf->SetTitle(
    $t['title']
);

$mpdf->SetAuthor(
    'AlSharqPlatform'
);

/* =========================================================
   Header
========================================================= */

$mpdf->SetHTMLHeader(
    '
    <div style="
        text-align:center;
        font-family:dejavusans;
        font-size:8px;
        color:#777;
        border-bottom:1px solid #ddd;
        padding-bottom:4px;
    ">
        ' .
        htmlspecialchars(
            $companyName
        ) .
        '
    </div>
    '
);

/* =========================================================
   Footer
========================================================= */

$mpdf->SetHTMLFooter(
    '
    <div style="
        text-align:center;
        font-family:dejavusans;
        font-size:7px;
        color:#777;
        border-top:1px solid #ddd;
        padding-top:4px;
    ">
        {PAGENO}
    </div>
    '
);

/* =========================================================
   اتجاه التقرير
========================================================= */

$direction =
    $lang === 'ar'
        ? 'rtl'
        : 'ltr';

/* =========================================================
   CSS
========================================================= */

$css = '
<style>

body {

    font-family: dejavusans;

    direction: ' .
    $direction .
    ';

    font-size: 8px;

    color: #222;
}

.header {

    text-align:center;

    border-bottom:
        2px solid #198754;

    padding-bottom:
        8px;

    margin-bottom:
        9px;
}

.header h1 {

    margin:0;

    color:#198754;

    font-size:18px;
}

.header h2 {

    margin:3px 0;

    color:#555;

    font-size:11px;
}

.generated {

    color:#777;

    font-size:7.5px;
}

.summary {

    width:100%;

    border-collapse:collapse;

    margin-bottom:9px;
}

.summary td {

    width:20%;

    border:1px solid #ddd;

    background:#f8f9fa;

    padding:6px;

    text-align:center;
}

.summary-label {

    font-size:7.5px;

    color:#666;
}

.summary-value {

    font-size:12px;

    font-weight:bold;
}

.summary-present {

    color:#198754;
}

.summary-late {

    color:#d97706;
}

.summary-absent {

    color:#dc3545;
}

.filters {

    border:1px solid #ddd;

    background:#fafafa;

    padding:5px;

    margin-bottom:9px;
}

.filters-title {

    font-weight:bold;

    margin-bottom:4px;
}

.filter-item {

    display:inline-block;

    border:1px solid #ddd;

    background:#fff;

    padding:2px 5px;

    margin:1px;

    font-size:7.5px;
}

.report {

    width:100%;

    border-collapse:collapse;
}

.report thead {

    display:table-header-group;
}

.report th {

    background:#343a40;

    color:#fff;

    border:1px solid #222;

    padding:5px 3px;

    text-align:center;

    font-size:7.5px;
}

.report td {

    border:1px solid #ddd;

    padding:4px 3px;

    text-align:center;

    vertical-align:middle;

    font-size:7px;
}

.report tr:nth-child(even) td {

    background:#f8f9fa;
}

.driver-image {

    width:38px;

    height:38px;

    border-radius:50%;

    object-fit:cover;
}

.image-placeholder {

    width:38px;

    height:38px;

    border-radius:50%;

    background:#e9ecef;

    color:#777;

    display:inline-block;

    text-align:center;

    line-height:38px;

    font-size:15px;
}

.status-present {

    color:#198754;

    background:#d1e7dd;

    padding:3px 5px;

    border-radius:4px;

    font-weight:bold;
}

.status-late {

    color:#856404;

    background:#fff3cd;

    padding:3px 5px;

    border-radius:4px;

    font-weight:bold;
}

.status-absent {

    color:#842029;

    background:#f8d7da;

    padding:3px 5px;

    border-radius:4px;

    font-weight:bold;
}

.total-row td {

    background:#e9ecef !important;

    font-weight:bold;
}

.no-data {

    text-align:center;

    padding:25px;

    color:#777;
}

</style>
';

/* =========================================================
   كتابة CSS فقط
========================================================= */

$mpdf->WriteHTML(
    $css,
    \Mpdf\HTMLParserMode::HEADER_CSS
);

/* =========================================================
   رأس التقرير
========================================================= */

$mpdf->WriteHTML(
    '
    <div class="header">

        <h1>
            ' .
            htmlspecialchars(
                $t['title']
            ) .
            '
        </h1>

        <h2>
            ' .
            htmlspecialchars(
                $companyName
            ) .
            '
        </h2>

        <div class="generated">
            ' .
            htmlspecialchars(
                $t['generated_at']
            ) .
            ':
            ' .
            date('Y-m-d H:i') .
            '
        </div>

    </div>
    '
);

/* =========================================================
   الملخص
========================================================= */

$mpdf->WriteHTML(
    '
    <table class="summary">

        <tr>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['total_records']
                    ) .
                    '
                </div>

                <div class="summary-value">
                    ' .
                    number_format(
                        $totalRecords
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['total_present']
                    ) .
                    '
                </div>

                <div class="summary-value summary-present">
                    ' .
                    number_format(
                        $presentCount
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['total_late']
                    ) .
                    '
                </div>

                <div class="summary-value summary-late">
                    ' .
                    number_format(
                        $lateCount
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['total_absent']
                    ) .
                    '
                </div>

                <div class="summary-value summary-absent">
                    ' .
                    number_format(
                        $absentCount
                    ) .
                    '
                </div>

            </td>

            <td>

                <div class="summary-label">
                    ' .
                    htmlspecialchars(
                        $t['total_drivers']
                    ) .
                    '
                </div>

                <div class="summary-value">
                    ' .
                    number_format(
                        $totalDrivers
                    ) .
                    '
                </div>

            </td>

        </tr>

    </table>
    '
);

/* =========================================================
   الفلاتر
========================================================= */

$filterItems = [];

/* البحث */

if ($search !== '') {

    $filterItems[] = [

        'label' =>
            $t['search_filter'],

        'value' =>
            $search
    ];
}

/* السائق */

if ($selectedDriverName !== '') {

    $filterItems[] = [

        'label' =>
            $t['driver_filter'],

        'value' =>
            $selectedDriverName
    ];
}

/* الحالة */

if ($status_filter !== '') {

    $statusLabel =
        $status_filter;

    if (
        $status_filter === 'present'
    ) {

        $statusLabel =
            $t['present'];

    } elseif (
        $status_filter === 'late'
    ) {

        $statusLabel =
            $t['late'];

    } elseif (
        $status_filter === 'absent'
    ) {

        $statusLabel =
            $t['absent'];
    }

    $filterItems[] = [

        'label' =>
            $t['status_filter'],

        'value' =>
            $statusLabel
    ];
}

/* من */

if ($from !== '') {

    $filterItems[] = [

        'label' =>
            $t['from_filter'],

        'value' =>
            $from
    ];
}

/* إلى */

if ($to !== '') {

    $filterItems[] = [

        'label' =>
            $t['to_filter'],

        'value' =>
            $to
    ];
}

/* =========================================================
   HTML الفلاتر
========================================================= */

$filtersHtml = '

<div class="filters">

    <div class="filters-title">

        ' .
        htmlspecialchars(
            $t['filters']
        ) .
        '

    </div>
';

if (!empty($filterItems)) {

    foreach (
        $filterItems
        as $item
    ) {

        $filtersHtml .= '

        <span class="filter-item">

            <strong>
                ' .
                htmlspecialchars(
                    $item['label']
                ) .
                ':
            </strong>

            ' .
            htmlspecialchars(
                $item['value']
            ) .
            '

        </span>
        ';
    }

} else {

    $filtersHtml .= '

        <span class="filter-item">

            ' .
            htmlspecialchars(
                $t['all_records']
            ) .
            '

        </span>
    ';
}

$filtersHtml .= '
</div>
';

$mpdf->WriteHTML(
    $filtersHtml
);

/* =========================================================
   بداية جدول التقرير
========================================================= */

$mpdf->WriteHTML(
    '
    <table class="report">

        <thead>

            <tr>

                <th width="4%">
                    ' .
                    htmlspecialchars(
                        $t['id']
                    ) .
                    '
                </th>

                <th width="8%">
                    ' .
                    htmlspecialchars(
                        $t['image']
                    ) .
                    '
                </th>

                <th width="18%">
                    ' .
                    htmlspecialchars(
                        $t['name']
                    ) .
                    '
                </th>

                <th width="13%">
                    ' .
                    htmlspecialchars(
                        $t['phone']
                    ) .
                    '
                </th>

                <th width="18%">
                    ' .
                    htmlspecialchars(
                        $t['work_area']
                    ) .
                    '
                </th>

                <th width="12%">
                    ' .
                    htmlspecialchars(
                        $t['date']
                    ) .
                    '
                </th>

                <th width="9%">
                    ' .
                    htmlspecialchars(
                        $t['check_in']
                    ) .
                    '
                </th>

                <th width="9%">
                    ' .
                    htmlspecialchars(
                        $t['check_out']
                    ) .
                    '
                </th>

                <th width="9%">
                    ' .
                    htmlspecialchars(
                        $t['status']
                    ) .
                    '
                </th>

            </tr>

        </thead>

        <tbody>
    '
);

/* =========================================================
   البيانات
========================================================= */

if (empty($rows)) {

    $mpdf->WriteHTML(
        '
        <tr>

            <td
                colspan="9"
                class="no-data"
            >

                ' .
                htmlspecialchars(
                    $t['no_data']
                ) .
                '

            </td>

        </tr>
        '
    );

} else {

    $counter = 1;

    foreach (
        $rows
        as $row
    ) {

        /* ---------------------------------------------
           مسار صورة السائق
        --------------------------------------------- */

        $imageHtml =
            '<span class="image-placeholder">👤</span>';

        $imageName =
            trim(
                (string)(
                    $row['imagedriver']
                    ?? ''
                )
            );

        if ($imageName !== '') {

            $candidates = [

                __DIR__ .
                '/../uploads/' .
                basename($imageName),

                __DIR__ .
                '/../uploads/drivers/' .
                basename($imageName),

                __DIR__ .
                '/../uploads/driver/' .
                basename($imageName),

                __DIR__ .
                '/../fleetimg/img/' .
                basename($imageName)

            ];

            $imageFile = '';

            foreach (
                $candidates
                as $candidate
            ) {

                $real =
                    realpath(
                        $candidate
                    );

                if (
                    $real !== false &&
                    is_file($real)
                ) {

                    $imageFile =
                        $real;

                    break;
                }
            }

            if ($imageFile !== '') {

                /*
                 * استخدام file:// لتوافق mPDF
                 */

                $imageHtml =
                    '<img
                        class="driver-image"
                        src="' .
                        htmlspecialchars(
                            'file://' .
                            str_replace(
                                '\\',
                                '/',
                                $imageFile
                            )
                        ) .
                        '"
                    >';
            }
        }

        /* ---------------------------------------------
           الحالة
        --------------------------------------------- */

        $statusClass =
            $row['status_class']
            ?? 'status-present';

        $statusText =
            $row['status_text']
            ?? $t['present'];

        /* ---------------------------------------------
           الصف
        --------------------------------------------- */

        $rowHtml = '

        <tr>

            <td>
                #' .
                $counter .
                '
            </td>

            <td>

                ' .
                $imageHtml .
                '

            </td>

            <td>

                <strong>
                    ' .
                    htmlspecialchars(
                        $row['name']
                        ?? '-'
                    ) .
                    '
                </strong>

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['phone']
                    ?? '-'
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['work_area']
                    ?? '-'
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['attendance_date']
                    ?? '-'
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['check_in']
                    ?? '-'
                ) .
                '

            </td>

            <td>

                ' .
                htmlspecialchars(
                    $row['check_out']
                    ?? '-'
                ) .
                '

            </td>

            <td>

                <span class="' .
                    htmlspecialchars(
                        $statusClass
                    ) .
                '">

                    ' .
                    htmlspecialchars(
                        $statusText
                    ) .
                    '

                </span>

            </td>

        </tr>
        ';

        /*
         * كل صف منفصل لتجنب pcre.backtrack_limit
         */

        $mpdf->WriteHTML(
            $rowHtml
        );

        $counter++;
    }
}

/* =========================================================
   نهاية الجدول
========================================================= */

$mpdf->WriteHTML(
    '
        </tbody>

    </table>
    '
);

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'attendance_report_' .
    date('Y-m-d_H-i-s') .
    '.pdf';

/* =========================================================
   إخراج PDF
========================================================= */

$mpdf->Output(
    $fileName,
    'I'
);

$stmt->close();

exit;