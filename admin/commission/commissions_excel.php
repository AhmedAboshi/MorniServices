<?php

include('../../include/connected.php');

session_start();

require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;


/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? 'ar';


/* =========================================================
   تحميل إعدادات الشركة
========================================================= */

$settingsFile = '../../include/settings.php';

if (file_exists($settingsFile)) {
    include_once $settingsFile;
}

$settings = $settings ?? [];


/* =========================================================
   معلومات الشركة
========================================================= */

$companyName    = $settings['company_name']    ?? 'منصة الشرق الذكية للخدمات وإدارة الأسطول';
$companyLogo    = $settings['company_logo']    ?? '';
$companyPhone   = $settings['company_phone']   ?? '';
$companyEmail   = $settings['company_email']   ?? '';
$companyAddress = $settings['company_address'] ?? '';
$companyWebsite = $settings['company_website'] ?? '';
$currency       = $settings['currency']        ?? '';


/* =========================================================
   الفلاتر
========================================================= */

$where  = [];
$params = [];
$types  = '';


/* =========================================================
   البحث
========================================================= */

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


/* =========================================================
   السائق
========================================================= */

$selectedDriverId = 0;

$selectedDriverName = 'جميع السائقين';

if (!empty($_GET['driver_id'])) {

    $selectedDriverId = (int)$_GET['driver_id'];

    $where[] = 'dc.driver_id = ?';

    $params[] = $selectedDriverId;

    $types .= 'i';


    /* جلب اسم السائق */

    $driverStmt = $con->prepare("
        SELECT name
        FROM drivers
        WHERE id = ?
        LIMIT 1
    ");

    if ($driverStmt) {

        $driverStmt->bind_param(
            'i',
            $selectedDriverId
        );

        $driverStmt->execute();

        $driverResult = $driverStmt->get_result();

        if ($driverRow = $driverResult->fetch_assoc()) {
            $selectedDriverName = $driverRow['name'] ?? 'غير محدد';
        }

        $driverStmt->close();
    }
}


/* =========================================================
   الخدمة
========================================================= */

if (!empty($_GET['service_id'])) {

    $where[] = 'dc.service_id = ?';

    $params[] = (int)$_GET['service_id'];

    $types .= 'i';
}


/* =========================================================
   الجنسية
========================================================= */

if (!empty($_GET['nationality'])) {

    $where[] = 'dc.nationality = ?';

    $params[] = $_GET['nationality'];

    $types .= 's';
}


/* =========================================================
   من تاريخ
========================================================= */

if (!empty($_GET['date_from'])) {

    $where[] = 'dc.period_start >= ?';

    $params[] = $_GET['date_from'];

    $types .= 's';
}


/* =========================================================
   إلى تاريخ
========================================================= */

if (!empty($_GET['date_to'])) {

    $where[] = 'dc.period_end <= ?';

    $params[] = $_GET['date_to'];

    $types .= 's';
}


/* =========================================================
   الحالة
========================================================= */

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


/* =========================================================
   WHERE
========================================================= */

if (!empty($where)) {

    $sql .= ' WHERE ' . implode(' AND ', $where);

}


/* =========================================================
   الترتيب
========================================================= */

$sql .= ' ORDER BY dc.id DESC';


/* =========================================================
   تنفيذ الاستعلام
========================================================= */

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


/* =========================
   اسم السائق المختار
========================= */

$selected_driver_name = 'جميع السائقين';

if (!empty($_GET['driver_id'])) {

    $first_row = $result->fetch_assoc();

    if ($first_row) {

        $selected_driver_name =
            $first_row['driver_name'] ?? 'غير محدد';

        /* إعادة المؤشر لأول سجل */
        $result->data_seek(0);
    }
}

/* =========================================================
   إنشاء Excel
========================================================= */

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('العمولات');

$sheet->setRightToLeft(true);


/* =========================================================
   الألوان
========================================================= */

$headerFill = '0D6EFD';
$lightFill  = 'F3F6FA';
$borderColor = 'D9E1E8';


/* =========================================================
   الشعار
========================================================= */

$currentRow = 1;

$logoPath = '';

if (!empty($companyLogo)) {

    $possiblePaths = [

        '../../' . ltrim($companyLogo, '/\\'),

        '../' . ltrim($companyLogo, '/\\'),

        $companyLogo

    ];

    foreach ($possiblePaths as $path) {

        if (file_exists($path)) {

            $logoPath = $path;

            break;
        }
    }
}


/* =========================================================
   وضع الشعار
========================================================= */

if (!empty($logoPath)) {

    try {

        $drawing = new Drawing();

        $drawing->setName('Company Logo');
        $drawing->setDescription($companyName);
        $drawing->setPath($logoPath);

        $drawing->setHeight(70);

        $drawing->setCoordinates('N1');

        $drawing->setWorksheet($sheet);

    } catch (Exception $e) {

        // تجاهل مشكلة الشعار والاستمرار في إنشاء الملف
    }
}


/* =========================================================
   اسم الشركة
========================================================= */

$sheet->mergeCells('A1:M1');

$sheet->setCellValue(
    'A1',
    $companyName
);

$sheet->getStyle('A1')
    ->getFont()
    ->setBold(true)
    ->setSize(18);

$sheet->getStyle('A1')
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    )
    ->setVertical(
        Alignment::VERTICAL_CENTER
    );

$sheet->getRowDimension(1)
    ->setRowHeight(35);


/* =========================================================
   معلومات الشركة
========================================================= */

$companyInfo = [];

if (!empty($companyPhone)) {
    $companyInfo[] = 'الجوال: ' . $companyPhone;
}

if (!empty($companyEmail)) {
    $companyInfo[] = 'البريد: ' . $companyEmail;
}

if (!empty($companyAddress)) {
    $companyInfo[] = 'العنوان: ' . $companyAddress;
}

if (!empty($companyWebsite)) {
    $companyInfo[] = 'الموقع: ' . $companyWebsite;
}


if (!empty($companyInfo)) {

    $sheet->mergeCells('A2:N2');

    $sheet->setCellValue(
        'A2',
        implode('   |   ', $companyInfo)
    );

    $sheet->getStyle('A2')
        ->getFont()
        ->setSize(10)
        ->getColor()
        ->setARGB('666666');

    $sheet->getStyle('A2')
        ->getAlignment()
        ->setHorizontal(
            Alignment::HORIZONTAL_CENTER
        );

}


/* =========================================================
   عنوان التقرير
========================================================= */

$sheet->mergeCells('A4:N4');

$sheet->setCellValue(
    'A4',
    'كشف عمولات السائقين'
);

$sheet->getStyle('A4')
    ->getFont()
    ->setBold(true)
    ->setSize(16);

$sheet->getStyle('A4')
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    )
    ->setVertical(
        Alignment::VERTICAL_CENTER
    );

$sheet->getRowDimension(4)
    ->setRowHeight(28);


/* =========================================================
   معلومات التقرير
========================================================= */

$sheet->mergeCells('A5:F5');

$sheet->setCellValue(
    'A5',
    'السائق: ' . $selectedDriverName
);


$reportDate = date('Y-m-d');

$sheet->mergeCells('G5:I5');

$sheet->setCellValue(
    'G5',
    'تاريخ التقرير: ' . $reportDate
);


$periodFrom = $_GET['date_from'] ?? '';

$periodTo = $_GET['date_to'] ?? '';


if ($periodFrom === '' && $periodTo === '') {

    $periodText = 'حسب السجلات';

} elseif ($periodFrom !== '' && $periodTo !== '') {

    $periodText = $periodFrom . ' إلى ' . $periodTo;

} elseif ($periodFrom !== '') {

    $periodText = 'من ' . $periodFrom;

} else {

    $periodText = 'إلى ' . $periodTo;
}


$sheet->mergeCells('J5:N5');

$sheet->setCellValue(
    'J5',
    'الفترة: ' . $periodText
);


$sheet->getStyle('A5:N5')
    ->getFont()
    ->setBold(true);

$sheet->getStyle('A5:N5')
    ->getAlignment()
    ->setVertical(
        Alignment::VERTICAL_CENTER
    );


/* =========================================================
   الإشعار
========================================================= */

$noticeRow = 7;

$sheet->mergeCells(
    'A' . $noticeRow . ':N' . $noticeRow
);

$sheet->setCellValue(
    'A' . $noticeRow,
    'إشعار: هذا الكشف صادر من نظام إدارة العمولات، وتم احتساب العمولات وفقًا لسياسات الشركة والبيانات المسجلة بالنظام، وتشمل المكافآت والخصومات المسجلة على العمولة.'
);

$sheet->getStyle(
    'A' . $noticeRow . ':N' . $noticeRow
)
    ->getFill()
    ->setFillType(
        Fill::FILL_SOLID
    )
    ->getStartColor()
    ->setARGB('FFF3CD');

$sheet->getStyle(
    'A' . $noticeRow . ':N' . $noticeRow
)
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_RIGHT
    )
    ->setVertical(
        Alignment::VERTICAL_CENTER
    )
    ->setWrapText(true);

$sheet->getRowDimension($noticeRow)
    ->setRowHeight(38);


/* =========================================================
   رؤوس الأعمدة
========================================================= */

$headerRow = 9;

$headers = [

    'A' => '#',
    'B' => 'رقم العمولة',
    'C' => 'السائق',
    'D' => 'الخدمة',
    'E' => 'الجنسية',
    'F' => 'رقم الأسبوع',
    'G' => 'السنة',
    'H' => 'بداية الفترة',
    'I' => 'نهاية الفترة',
    'J' => 'عدد الطلبات',
    'K' => 'العمولة الأساسية',
    'L' => 'المكافآت',
    'M' => 'الخصومات',
    'N' => 'الصافي'

];


foreach ($headers as $column => $value) {

    $sheet->setCellValue(
        $column . $headerRow,
        $value
    );

}


$sheet->getStyle(
    'A' . $headerRow . ':N' . $headerRow
)
    ->getFont()
    ->setBold(true)
    ->getColor()
    ->setARGB('FFFFFF');


$sheet->getStyle(
    'A' . $headerRow . ':N' . $headerRow
)
    ->getFill()
    ->setFillType(
        Fill::FILL_SOLID
    )
    ->getStartColor()
    ->setARGB($headerFill);


$sheet->getStyle(
    'A' . $headerRow . ':N' . $headerRow
)
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    )
    ->setVertical(
        Alignment::VERTICAL_CENTER
    )
    ->setWrapText(true);

$sheet->getRowDimension($headerRow)
    ->setRowHeight(30);


/* =========================================================
   البيانات
========================================================= */

$rowNumber = $headerRow + 1;

$counter = 1;

$totalBase = 0;
$totalBonus = 0;
$totalDeduction = 0;
$totalNet = 0;
$totalOrders = 0;


while ($row = $result->fetch_assoc()) {

    $base      = (float)$row['base_commission'];
    $bonus     = (float)$row['total_bonus'];
    $deduction = (float)$row['total_deduction'];
    $net       = (float)$row['net_commission'];
    $orders    = (int)$row['total_orders'];


    $sheet->setCellValue(
        'A' . $rowNumber,
        $counter++
    );

    $sheet->setCellValue(
        'B' . $rowNumber,
        $row['commission_no'] ?? '-'
    );

    $sheet->setCellValue(
        'C' . $rowNumber,
        $row['driver_name'] ?? 'غير محدد'
    );

    $sheet->setCellValue(
        'D' . $rowNumber,
        $row['service_name'] ?? 'غير محددة'
    );

    $sheet->setCellValue(
        'E' . $rowNumber,
        $row['nationality'] ?? '-'
    );

    $sheet->setCellValue(
        'F' . $rowNumber,
        (int)$row['week_number']
    );

    $sheet->setCellValue(
        'G' . $rowNumber,
        (int)$row['year_number']
    );

    $sheet->setCellValue(
        'H' . $rowNumber,
        $row['period_start'] ?? '-'
    );

    $sheet->setCellValue(
        'I' . $rowNumber,
        $row['period_end'] ?? '-'
    );

    $sheet->setCellValue(
        'J' . $rowNumber,
        $orders
    );

    $sheet->setCellValue(
        'K' . $rowNumber,
        $base
    );

    $sheet->setCellValue(
        'L' . $rowNumber,
        $bonus
    );

    $sheet->setCellValue(
        'M' . $rowNumber,
        $deduction
    );

    $sheet->setCellValue(
        'N' . $rowNumber,
        $net
    );


    $totalBase += $base;
    $totalBonus += $bonus;
    $totalDeduction += $deduction;
    $totalNet += $net;
    $totalOrders += $orders;


    $rowNumber++;
}


/* =========================================================
   تنسيق البيانات
========================================================= */

$lastDataRow = $rowNumber - 1;

if ($lastDataRow >= $headerRow) {

    $sheet->getStyle(
        'A' . $headerRow . ':N' . $lastDataRow
    )
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    )
    ->setVertical(
        Alignment::VERTICAL_CENTER
    );


    $sheet->getStyle(
        'A' . $headerRow . ':N' . $lastDataRow
    )
    ->getBorders()
    ->getAllBorders()
    ->setBorderStyle(
        Border::BORDER_THIN
    )
    ->getColor()
    ->setARGB($borderColor);


    if ($lastDataRow >= $headerRow + 1) {

        $sheet->getStyle(
            'K' . ($headerRow + 1) . ':N' . $lastDataRow
        )
        ->getNumberFormat()
        ->setFormatCode('#,##0.00');

    }

}


/* =========================================================
   صف الإجماليات
========================================================= */

$totalRow = $rowNumber + 1;

$sheet->mergeCells(
    'A' . $totalRow . ':I' . $totalRow
);

$sheet->setCellValue(
    'A' . $totalRow,
    'الإجمالي'
);

$sheet->setCellValue(
    'J' . $totalRow,
    $totalOrders
);

$sheet->setCellValue(
    'K' . $totalRow,
    $totalBase
);

$sheet->setCellValue(
    'L' . $totalRow,
    $totalBonus
);

$sheet->setCellValue(
    'M' . $totalRow,
    $totalDeduction
);

$sheet->setCellValue(
    'N' . $totalRow,
    $totalNet
);


$sheet->getStyle(
    'A' . $totalRow . ':N' . $totalRow
)
    ->getFont()
    ->setBold(true);


$sheet->getStyle(
    'A' . $totalRow . ':N' . $totalRow
)
    ->getFill()
    ->setFillType(
        Fill::FILL_SOLID
    )
    ->getStartColor()
    ->setARGB('E8F1FF');


$sheet->getStyle(
    'J' . $totalRow . ':N' . $totalRow
)
    ->getNumberFormat()
    ->setFormatCode('#,##0.00');


$sheet->getStyle(
    'A' . $totalRow . ':N' . $totalRow
)
    ->getBorders()
    ->getAllBorders()
    ->setBorderStyle(
        Border::BORDER_THIN
    );


/* عدد الطلبات بدون كسور */

$sheet->getStyle(
    'J' . $totalRow
)
->getNumberFormat()
->setFormatCode('#,##0');


/* =========================================================
   العملة
========================================================= */

if (!empty($currency)) {

    $sheet->getStyle(
        'K' . ($headerRow + 1) . ':N' . $totalRow
    )
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00 "' . $currency . '"'
    );

}


/* =========================================================
   عرض الأعمدة
========================================================= */

$widths = [

    'A' => 8,
    'B' => 23,
    'C' => 25,
    'D' => 25,
    'E' => 18,
    'F' => 12,
    'G' => 10,
    'H' => 15,
    'I' => 15,
    'J' => 14,
    'K' => 18,
    'L' => 15,
    'M' => 15,
    'N' => 18

];


foreach ($widths as $column => $width) {

    $sheet
        ->getColumnDimension($column)
        ->setWidth($width);

}


/* =========================================================
   التفاف النص
========================================================= */

$sheet->getStyle(
    'A1:N' . $totalRow
)
->getAlignment()
->setVertical(
    Alignment::VERTICAL_CENTER
);


/* =========================================================
   تجميد رؤوس الأعمدة
========================================================= */

$sheet->freezePane(
    'A' . ($headerRow + 1)
);


/* =========================================================
   Auto Filter
========================================================= */

if ($lastDataRow >= $headerRow + 1) {

    $sheet->setAutoFilter(
        'A' . $headerRow . ':N' . $lastDataRow
    );

}


/* =========================================================
   إعدادات الطباعة
========================================================= */

$sheet->getPageSetup()
    ->setOrientation(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
    );

$sheet->getPageSetup()
    ->setPaperSize(
        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
    );

$sheet->getPageSetup()
    ->setFitToWidth(1);

$sheet->getPageSetup()
    ->setFitToHeight(0);

$sheet->getPageMargins()
    ->setTop(0.4)
    ->setRight(0.3)
    ->setLeft(0.3)
    ->setBottom(0.4);


/* =========================================================
   إخراج الملف
========================================================= */

$filename =
    'driver_commissions_' .
    date('Y-m-d_H-i-s') .
    '.xlsx';


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


$writer = new Xlsx($spreadsheet);

$writer->save('php://output');

exit;