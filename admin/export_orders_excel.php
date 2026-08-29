<?php

/* =========================================================
   EXPORT ORDERS TO EXCEL
   AlSharqPlatform
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . '/../include/connected.php';


/* =========================================================
   PHP SPREADSHEET
========================================================= */

$autoload = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoload)) {

    die(
        'خطأ: لم يتم العثور على Composer Autoload.<br>' .
        'تأكد من وجود vendor/autoload.php'
    );
}

require_once $autoload;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;


/* =========================================================
   CHARSET
========================================================= */

mysqli_set_charset($con, 'utf8mb4');


/* =========================================================
   LANGUAGE
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}


/* =========================================================
   FILTERS
========================================================= */

$search =
    trim($_GET['search'] ?? '');

$status =
    trim($_GET['status'] ?? '');

$filter =
    trim($_GET['filter'] ?? 'all');

$approval_status =
    trim($_GET['approval_status'] ?? '');

$order_type =
    trim($_GET['order_type'] ?? '');


/* =========================================================
   WHERE
========================================================= */

$where = "WHERE 1=1";

$params = [];
$types  = '';


/* =========================================================
   SEARCH
========================================================= */

if ($search !== '') {

    $where .= "
        AND (
            o.full_name LIKE ?
            OR o.phone LIKE ?
            OR o.from_city LIKE ?
            OR o.to_city LIKE ?
            OR o.order_number LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'sssss';
}


/* =========================================================
   STATUS
========================================================= */

$allowedStatus = [
    'pending',
    'assigned',
    'done',
    'cancelled'
];

if (
    $status !== '' &&
    in_array($status, $allowedStatus, true)
) {

    $where .= "
        AND o.status = ?
    ";

    $params[] = $status;

    $types .= 's';
}


/* =========================================================
   BOOKING TYPE
========================================================= */

if ($filter === 'instant') {

    $where .= "
        AND o.booking_type = 'instant'
    ";

}

elseif ($filter === 'scheduled') {

    $where .= "
        AND o.booking_type = 'scheduled'
    ";
}


/* =========================================================
   ORDER TYPE
========================================================= */

if ($order_type !== '') {

    $where .= "
        AND o.order_type = ?
    ";

    $params[] = $order_type;

    $types .= 's';
}


/* =========================================================
   APPROVAL STATUS
========================================================= */

$allowedApproval = [
    'pending',
    'approved',
    'rejected'
];

if (
    $approval_status !== '' &&
    in_array(
        $approval_status,
        $allowedApproval,
        true
    )
) {

    $where .= "
        AND o.approval_status = ?
    ";

    $params[] = $approval_status;

    $types .= 's';
}


/* =========================================================
   GET ORDERS
========================================================= */

$sql = "

    SELECT

        o.id,
        o.order_number,

        o.user_id,

        o.full_name,
        o.email,
        o.phone,

        o.from_city,
        o.to_city,

        o.price,

        o.status,
        o.approval_status,

        o.booking_type,

        o.scheduled_date,
        o.scheduled_time,

        o.order_type,

        o.created_at,

        d.name AS driver_name,

        u.username AS user_username,
        u.email AS user_email

    FROM orders o

    LEFT JOIN drivers d
        ON d.id = o.driver_id

    LEFT JOIN users u
        ON u.id = o.user_id

    $where

    ORDER BY o.id DESC
";


$stmt = $con->prepare($sql);


if (!$stmt) {

    die(
        'Database Error: ' .
        htmlspecialchars($con->error)
    );
}


/* =========================================================
   BIND PARAMETERS
========================================================= */

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


$stmt->execute();

$result = $stmt->get_result();


/* =========================================================
   CREATE EXCEL
========================================================= */

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();


/* =========================================================
   SHEET SETTINGS
========================================================= */

$sheet->setTitle(
    $lang === 'ar'
        ? 'الطلبات'
        : 'Orders'
);

$sheet->setRightToLeft(
    $lang === 'ar'
);


/* =========================================================
   TITLE
========================================================= */

$sheet->mergeCells('A1:O1');

$sheet->setCellValue(
    'A1',
    $lang === 'ar'
        ? 'تقرير الطلبات - منصة الشرق'
        : 'Orders Report - AlSharqPlatform'
);

$sheet->getStyle('A1')->applyFromArray([

    'font' => [
        'bold' => true,
        'size' => 18
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical'   => Alignment::VERTICAL_CENTER
    ],

    'fill' => [
        'fillType' =>
            Fill::FILL_SOLID,

        'startColor' => [
            'rgb' => '1F4E78'
        ]
    ]

]);


$sheet
    ->getStyle('A1')
    ->getFont()
    ->getColor()
    ->setRGB('FFFFFF');


$sheet->getRowDimension(1)->setRowHeight(35);


/* =========================================================
   EXPORT DATE
========================================================= */

$sheet->mergeCells('A2:O2');

$sheet->setCellValue(
    'A2',
    $lang === 'ar'
        ? 'تاريخ التصدير: ' . date('Y-m-d H:i:s')
        : 'Export Date: ' . date('Y-m-d H:i:s')
);

$sheet->getStyle('A2')->applyFromArray([

    'font' => [
        'italic' => true,
        'size' => 10
    ],

    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER
    ]

]);


/* =========================================================
   FILTER SUMMARY
========================================================= */

$filterText = [];

if ($search !== '') {
    $filterText[] =
        ($lang === 'ar' ? 'البحث: ' : 'Search: ') .
        $search;
}

if ($status !== '') {
    $filterText[] =
        ($lang === 'ar' ? 'الحالة: ' : 'Status: ') .
        $status;
}

if ($filter === 'instant') {
    $filterText[] =
        $lang === 'ar'
            ? 'نوع الحجز: فوري'
            : 'Booking: Instant';
}

if ($filter === 'scheduled') {
    $filterText[] =
        $lang === 'ar'
            ? 'نوع الحجز: مجدول'
            : 'Booking: Scheduled';
}

if ($approval_status !== '') {
    $filterText[] =
        ($lang === 'ar'
            ? 'الموافقة: '
            : 'Approval: ') .
        $approval_status;
}

if ($order_type !== '') {
    $filterText[] =
        ($lang === 'ar'
            ? 'نوع الطلب: '
            : 'Order Type: ') .
        $order_type;
}


$filterSummary =
    !empty($filterText)
        ? implode(' | ', $filterText)
        : (
            $lang === 'ar'
                ? 'جميع الطلبات'
                : 'All Orders'
        );


$sheet->mergeCells('A3:O3');

$sheet->setCellValue(
    'A3',
    $filterSummary
);

$sheet->getStyle('A3')->applyFromArray([

    'font' => [
        'bold' => true
    ],

    'alignment' => [
        'horizontal' =>
            Alignment::HORIZONTAL_CENTER
    ]

]);


/* =========================================================
   HEADERS
========================================================= */

$headersAr = [

    'A' => '#',
    'B' => 'رقم الطلب',
    'C' => 'العميل',
    'D' => 'البريد الإلكتروني',
    'E' => 'الجوال',
    'F' => 'من',
    'G' => 'إلى',
    'H' => 'السعر',
    'I' => 'الحالة',
    'J' => 'حالة الموافقة',
    'K' => 'مزود الخدمة',
    'L' => 'نوع الحجز',
    'M' => 'التاريخ المجدول',
    'N' => 'الوقت المجدول',
    'O' => 'تاريخ الطلب'

];


$headersEn = [

    'A' => '#',
    'B' => 'Order Number',
    'C' => 'Customer',
    'D' => 'Email',
    'E' => 'Phone',
    'F' => 'From',
    'G' => 'To',
    'H' => 'Price',
    'I' => 'Status',
    'J' => 'Approval',
    'K' => 'Provider',
    'L' => 'Booking Type',
    'M' => 'Scheduled Date',
    'N' => 'Scheduled Time',
    'O' => 'Order Date'

];


$headers =
    $lang === 'ar'
        ? $headersAr
        : $headersEn;


foreach ($headers as $column => $header) {

    $sheet->setCellValue(
        $column . '5',
        $header
    );
}


/* =========================================================
   HEADER STYLE
========================================================= */

$sheet->getStyle('A5:O5')->applyFromArray([

    'font' => [
        'bold' => true,
        'color' => [
            'rgb' => 'FFFFFF'
        ]
    ],

    'fill' => [
        'fillType' =>
            Fill::FILL_SOLID,

        'startColor' => [
            'rgb' => '4472C4'
        ]
    ],

    'alignment' => [
        'horizontal' =>
            Alignment::HORIZONTAL_CENTER,

        'vertical' =>
            Alignment::VERTICAL_CENTER,

        'wrapText' => true
    ],

    'borders' => [

        'allBorders' => [

            'borderStyle' =>
                Border::BORDER_THIN,

            'color' => [
                'rgb' => 'D9E2F3'
            ]

        ]

    ]

]);


$sheet->getRowDimension(5)->setRowHeight(30);


/* =========================================================
   STATUS TRANSLATION
========================================================= */

function orderStatusText($status, $lang)
{
    if ($lang === 'en') {

        return match ($status) {

            'pending' =>
                'Pending',

            'assigned' =>
                'Assigned',

            'done' =>
                'Completed',

            'cancelled' =>
                'Cancelled',

            default =>
                $status
        };
    }


    return match ($status) {

        'pending' =>
            'انتظار',

        'assigned' =>
            'تم الإسناد',

        'done' =>
            'مكتمل',

        'cancelled' =>
            'ملغي',

        default =>
            $status
    };
}


function approvalText($status, $lang)
{
    if ($lang === 'en') {

        return match ($status) {

            'pending' =>
                'Pending Approval',

            'approved' =>
                'Approved',

            'rejected' =>
                'Rejected',

            default =>
                $status
        };
    }


    return match ($status) {

        'pending' =>
            'بانتظار الموافقة',

        'approved' =>
            'تمت الموافقة',

        'rejected' =>
            'مرفوض',

        default =>
            $status
    };
}


/* =========================================================
   WRITE DATA
========================================================= */

$rowNumber = 6;

$totalPrice = 0;

$totalOrders = 0;


while ($row = $result->fetch_assoc()) {

    $totalOrders++;


    $customerName =
        trim(
            $row['full_name']
            ??
            $row['user_username']
            ??
            ''
        );


    $customerEmail =
        trim(
            $row['email']
            ??
            $row['user_email']
            ??
            ''
        );


    $price =
        (float)($row['price'] ?? 0);


    $totalPrice += $price;


    /* ==============================================
       البيانات
    ============================================== */

    $sheet->setCellValue(
        'A' . $rowNumber,
        (int)$row['id']
    );


    $sheet->setCellValue(
        'B' . $rowNumber,
        $row['order_number']
            ?? ('#' . $row['id'])
    );


    $sheet->setCellValue(
        'C' . $rowNumber,
        $customerName
    );


    $sheet->setCellValue(
        'D' . $rowNumber,
        $customerEmail
    );


    $sheet->setCellValue(
        'E' . $rowNumber,
        $row['phone'] ?? ''
    );


    $sheet->setCellValue(
        'F' . $rowNumber,
        $row['from_city'] ?? ''
    );


    $sheet->setCellValue(
        'G' . $rowNumber,
        $row['to_city'] ?? ''
    );


    $sheet->setCellValue(
        'H' . $rowNumber,
        $price
    );


    $sheet->setCellValue(
        'I' . $rowNumber,
        orderStatusText(
            $row['status'] ?? '',
            $lang
        )
    );


    $sheet->setCellValue(
        'J' . $rowNumber,
        approvalText(
            $row['approval_status'] ?? '',
            $lang
        )
    );


    $sheet->setCellValue(
        'K' . $rowNumber,
        $row['driver_name']
            ?? (
                $lang === 'ar'
                    ? 'غير محدد'
                    : 'Not Assigned'
            )
    );


    $bookingType =
        $row['booking_type'] ?? '';


    if ($lang === 'ar') {

        $bookingText =
            $bookingType === 'instant'
                ? 'فوري'
                : (
                    $bookingType === 'scheduled'
                        ? 'مجدول'
                        : $bookingType
                );

    } else {

        $bookingText =
            $bookingType === 'instant'
                ? 'Instant'
                : (
                    $bookingType === 'scheduled'
                        ? 'Scheduled'
                        : $bookingType
                );
    }


    $sheet->setCellValue(
        'L' . $rowNumber,
        $bookingText
    );


    $sheet->setCellValue(
        'M' . $rowNumber,
        $row['scheduled_date'] ?? ''
    );


    $sheet->setCellValue(
        'N' . $rowNumber,
        $row['scheduled_time'] ?? ''
    );


    $sheet->setCellValue(
        'O' . $rowNumber,
        $row['created_at'] ?? ''
    );


    $rowNumber++;
}


/* =========================================================
   TOTAL ROW
========================================================= */

$totalRow = $rowNumber;


$sheet->mergeCells(
    'A' . $totalRow . ':G' . $totalRow
);


$sheet->setCellValue(
    'A' . $totalRow,
    $lang === 'ar'
        ? 'الإجمالي'
        : 'TOTAL'
);


$sheet->setCellValue(
    'H' . $totalRow,
    $totalPrice
);


$sheet->setCellValue(
    'I' . $totalRow,
    $lang === 'ar'
        ? 'عدد الطلبات: ' . $totalOrders
        : 'Orders: ' . $totalOrders
);


$sheet->getStyle(
    'A' . $totalRow . ':O' . $totalRow
)->applyFromArray([

    'font' => [
        'bold' => true
    ],

    'fill' => [
        'fillType' =>
            Fill::FILL_SOLID,

        'startColor' => [
            'rgb' => 'E2F0D9'
        ]
    ],

    'borders' => [

        'allBorders' => [

            'borderStyle' =>
                Border::BORDER_THIN,

            'color' => [
                'rgb' => 'A9D18E'
            ]

        ]

    ]

]);


/* =========================================================
   NUMBER FORMAT
========================================================= */

if ($totalOrders > 0) {

    $sheet
        ->getStyle(
            'H6:H' . $totalRow
        )
        ->getNumberFormat()
        ->setFormatCode(
            '#,##0.00'
        );
}


/* =========================================================
   BORDERS + ALIGNMENT
========================================================= */

$sheet->getStyle(
    'A5:O' . $totalRow
)->applyFromArray([

    'borders' => [

        'allBorders' => [

            'borderStyle' =>
                Border::BORDER_THIN,

            'color' => [
                'rgb' => 'D9D9D9'
            ]

        ]

    ],

    'alignment' => [

        'vertical' =>
            Alignment::VERTICAL_CENTER,

        'horizontal' =>
            Alignment::HORIZONTAL_CENTER

    ]

]);


/* =========================================================
   AUTO FILTER
========================================================= */

$sheet->setAutoFilter(
    'A5:O' . max(5, $totalRow - 1)
);


/* =========================================================
   FREEZE HEADER
========================================================= */

$sheet->freezePane('A6');


/* =========================================================
   COLUMN WIDTH
========================================================= */

$widths = [

    'A' => 8,
    'B' => 18,
    'C' => 25,
    'D' => 30,
    'E' => 18,
    'F' => 20,
    'G' => 20,
    'H' => 15,
    'I' => 18,
    'J' => 22,
    'K' => 25,
    'L' => 18,
    'M' => 18,
    'N' => 18,
    'O' => 22

];


foreach ($widths as $column => $width) {

    $sheet
        ->getColumnDimension($column)
        ->setWidth($width);
}


/* =========================================================
   WRAP TEXT
========================================================= */

$sheet
    ->getStyle(
        'A5:O' . $totalRow
    )
    ->getAlignment()
    ->setWrapText(true);


/* =========================================================
   OUTPUT
========================================================= */

$filename =
    'orders_' .
    date('Y-m-d_H-i-s') .
    '.xlsx';


/* تنظيف أي Output سابق */

if (ob_get_length()) {
    ob_end_clean();
}


header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' .
    $filename .
    '"'
);

header(
    'Cache-Control: max-age=0'
);

header(
    'Expires: 0'
);

header(
    'Pragma: public'
);


/* =========================================================
   SAVE
========================================================= */

$writer =
    new Xlsx($spreadsheet);

$writer->save('php://output');


$stmt->close();

exit;