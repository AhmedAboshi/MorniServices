
<?php

session_start();

include(__DIR__ . '/../include/connected.php');

mysqli_set_charset($con, 'utf8mb4');

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/* =========================================================
   اللغة
========================================================= */

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'ar');

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   الفلاتر - نفس orders_report.php
========================================================= */

$from_date  = trim($_GET['from_date'] ?? '');
$to_date    = trim($_GET['to_date'] ?? '');
$order_type = trim($_GET['order_type'] ?? '');
$driver_id  = (int)($_GET['driver_id'] ?? 0);
$search     = trim($_GET['search'] ?? '');

/* =========================================================
   الترجمة
========================================================= */

$t = [

    'ar' => [

        'company'       => 'شركة الشرق لخدمات السيارات',
        'title'         => 'تقرير الطلبات',

        'period'        => 'الفترة',
        'all_period'    => 'كل الفترات',
        'report_date'   => 'تاريخ التقرير',

        'driver_code'   => 'رمز السائق',
        'driver'        => 'السائق',
        'name'          => 'اسم السائق',
        'phone'         => 'الجوال',
        'type'          => 'نوع الطلب',
        'orders'        => 'عدد الطلبات',
        'total'         => 'الإجمالي',

        'intercity'     => 'بين المدن',
        'cart'          => 'نقل',
        'tow'           => 'سطحة / سحب',

        'search'        => 'البحث',
        'selected_driver' => 'السائق المحدد',

        'grand_orders'  => 'إجمالي الطلبات',
        'grand_revenue' => 'إجمالي الإيرادات',
        'drivers_count' => 'عدد السائقين',
        'avg_order'     => 'متوسط الطلب',
        'top_driver'    => 'أعلى سائق إيراداً',

        'no_data'       => 'لا توجد نتائج مطابقة للفلاتر',

        'sar'           => 'ريال'
    ],

    'en' => [

        'company'       => 'Al Sharq Automotive Services Company',
        'title'         => 'Orders Report',

        'period'        => 'Period',
        'all_period'    => 'All Periods',
        'report_date'   => 'Report Date',

        'driver_code'   => 'Driver Code',
        'driver'        => 'Driver',
        'name'          => 'Driver Name',
        'phone'         => 'Phone',
        'type'          => 'Order Type',
        'orders'        => 'Orders',
        'total'         => 'Total',

        'intercity'     => 'Intercity',
        'cart'          => 'Transport',
        'tow'           => 'Tow',

        'search'        => 'Search',
        'selected_driver' => 'Selected Driver',

        'grand_orders'  => 'Total Orders',
        'grand_revenue' => 'Total Revenue',
        'drivers_count' => 'Drivers',
        'avg_order'     => 'Average Order',
        'top_driver'    => 'Top Revenue Driver',

        'no_data'       => 'No results match the selected filters',

        'sar'           => 'SAR'
    ]
];

$tr = $t[$lang];

/* =========================================================
   أنواع الطلبات
========================================================= */

$orderTypes = [

    'intercity' => $tr['intercity'],
    'cart'      => $tr['cart'],
    'tow'       => $tr['tow']
];

/* =========================================================
   شروط البحث
========================================================= */

$where = [];

$params = [];

$types = '';

/* البحث */

if ($search !== '') {

    $where[] = "
        (
            d.name LIKE ?
            OR d.phone LIKE ?
            OR CAST(d.id AS CHAR) LIKE ?
            OR o.order_type LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'ssss';
}

/* التاريخ */

if ($from_date !== '') {

    $where[] = "DATE(o.created_at) >= ?";

    $params[] = $from_date;

    $types .= 's';
}

if ($to_date !== '') {

    $where[] = "DATE(o.created_at) <= ?";

    $params[] = $to_date;

    $types .= 's';
}

/* نوع الطلب */

if (
    $order_type !== '' &&
    array_key_exists($order_type, $orderTypes)
) {

    $where[] = "o.order_type = ?";

    $params[] = $order_type;

    $types .= 's';
}

/* السائق */

if ($driver_id > 0) {

    $where[] = "d.id = ?";

    $params[] = $driver_id;

    $types .= 'i';
}

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' .
        implode(' AND ', $where);
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "

SELECT

    d.id AS driver_code,

    d.name,

    d.phone,

    o.order_type,

    COUNT(DISTINCT o.id) AS total_orders,

    COALESCE(
        SUM(o.price),
        0
    ) AS total_revenue

FROM drivers d

INNER JOIN orders o
    ON d.id = o.driver_id

$whereSql

GROUP BY
    d.id,
    d.name,
    d.phone,
    o.order_type

ORDER BY
    total_revenue DESC,
    d.name ASC
";

/* =========================================================
   التنفيذ
========================================================= */

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars($con->error)
    );
}

if (!empty($params)) {

    if (strlen($types) !== count($params)) {

        die('Filter parameters mismatch.');
    }

    $stmt->bind_param(
        $types,
        ...$params
    );
}

if (!$stmt->execute()) {

    die(
        'Execute Error: ' .
        htmlspecialchars($stmt->error)
    );
}

$result = $stmt->get_result();

/* =========================================================
   قراءة النتائج
========================================================= */

$rows = [];

$totalOrders = 0;

$totalRevenue = 0;

$driversSet = [];

$driverTotals = [];

while ($row = $result->fetch_assoc()) {

    $row['total_orders'] =
        (int)($row['total_orders'] ?? 0);

    $row['total_revenue'] =
        (float)($row['total_revenue'] ?? 0);

    $rows[] = $row;

    $totalOrders +=
        $row['total_orders'];

    $totalRevenue +=
        $row['total_revenue'];

    $driversSet[
        $row['driver_code']
    ] = true;

    if (
        !isset(
            $driverTotals[
                $row['driver_code']
            ]
        )
    ) {

        $driverTotals[
            $row['driver_code']
        ] = [

            'name' =>
                $row['name'],

            'revenue' =>
                0
        ];
    }

    $driverTotals[
        $row['driver_code']
    ]['revenue']
        += $row['total_revenue'];
}

$stmt->close();

$totalDrivers =
    count($driversSet);

$averageOrder =
    $totalOrders > 0
        ? $totalRevenue / $totalOrders
        : 0;

/* =========================================================
   أعلى سائق
========================================================= */

$topDriverName = '-';

$topDriverRevenue = 0;

foreach ($driverTotals as $driverTotal) {

    if (
        $driverTotal['revenue']
        >
        $topDriverRevenue
    ) {

        $topDriverRevenue =
            $driverTotal['revenue'];

        $topDriverName =
            $driverTotal['name'];
    }
}

/* =========================================================
   اسم السائق المحدد
========================================================= */

$selectedDriverName = '';

if ($driver_id > 0) {

    $driverStmt = $con->prepare("
        SELECT
            name,
            phone
        FROM drivers
        WHERE id = ?
        LIMIT 1
    ");

    if ($driverStmt) {

        $driverStmt->bind_param(
            'i',
            $driver_id
        );

        $driverStmt->execute();

        $selectedDriver =
            $driverStmt
                ->get_result()
                ->fetch_assoc();

        $driverStmt->close();

        if ($selectedDriver) {

            $selectedDriverName =
                $selectedDriver['name']
                .
                ' - '
                .
                ($selectedDriver['phone'] ?? '');
        }
    }
}

/* =========================================================
   Excel
========================================================= */

$spreadsheet =
    new Spreadsheet();

$sheet =
    $spreadsheet
        ->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'تقرير الطلبات'
        : 'Orders Report'
);

$sheet->setRightToLeft(
    $lang === 'ar'
);

/* =========================================================
   العنوان
========================================================= */

$sheet->mergeCells('A1:G1');

$sheet->setCellValue(
    'A1',
    $tr['company']
);

$sheet->mergeCells('A2:G2');

$sheet->setCellValue(
    'A2',
    $tr['title']
);

$periodText =
    ($from_date !== '' || $to_date !== '')
        ? (
            ($from_date !== ''
                ? $from_date
                : '...')
            .
            ' - '
            .
            ($to_date !== ''
                ? $to_date
                : '...')
        )
        : $tr['all_period'];

$sheet->mergeCells('A3:G3');

$sheet->setCellValue(
    'A3',
    $tr['period']
    . ': '
    . $periodText
    . ' | '
    . $tr['report_date']
    . ': '
    . date('Y-m-d H:i')
);

/* =========================================================
   تنسيق العناوين
========================================================= */

$sheet
    ->getStyle('A1:G1')
    ->applyFromArray([

        'font' => [

            'bold' => true,

            'size' => 16,

            'color' => [
                'rgb' => 'FFFFFF'
            ]
        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [
                'rgb' => '0D6EFD'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER,

            'vertical' =>
                Alignment::VERTICAL_CENTER
        ]
    ]);

$sheet
    ->getStyle('A2:G2')
    ->applyFromArray([

        'font' => [

            'bold' => true,

            'size' => 14,

            'color' => [
                'rgb' => 'FFFFFF'
            ]
        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [
                'rgb' => '198754'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER
        ]
    ]);

$sheet
    ->getStyle('A3:G3')
    ->applyFromArray([

        'font' => [

            'italic' => true,

            'color' => [
                'rgb' => '666666'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER
        ]
    ]);

/* =========================================================
   معلومات الفلاتر
========================================================= */

$row = 5;

if ($search !== '') {

    $sheet->setCellValue(
        "A{$row}",
        $tr['search']
    );

    $sheet->mergeCells(
        "B{$row}:G{$row}"
    );

    $sheet->setCellValue(
        "B{$row}",
        $search
    );

    $row++;
}

if ($order_type !== '') {

    $sheet->setCellValue(
        "A{$row}",
        $tr['type']
    );

    $sheet->mergeCells(
        "B{$row}:G{$row}"
    );

    $sheet->setCellValue(
        "B{$row}",
        $orderTypes[$order_type]
            ?? $order_type
    );

    $row++;
}

if ($selectedDriverName !== '') {

    $sheet->setCellValue(
        "A{$row}",
        $tr['selected_driver']
    );

    $sheet->mergeCells(
        "B{$row}:G{$row}"
    );

    $sheet->setCellValue(
        "B{$row}",
        $selectedDriverName
    );

    $row++;
}

/* =========================================================
   الإحصائيات
========================================================= */

$row += 2;

$sheet->mergeCells(
    "A{$row}:G{$row}"
);

$sheet->setCellValue(
    "A{$row}",
    $lang === 'ar'
        ? 'ملخص التقرير'
        : 'Report Summary'
);

$sheet
    ->getStyle(
        "A{$row}:G{$row}"
    )
    ->applyFromArray([

        'font' => [

            'bold' => true,

            'size' => 13,

            'color' => [
                'rgb' => 'FFFFFF'
            ]
        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [
                'rgb' => '343A40'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER
        ]
    ]);

$row++;

$summary = [

    $tr['grand_orders']
        => $totalOrders,

    $tr['grand_revenue']
        => $totalRevenue,

    $tr['drivers_count']
        => $totalDrivers,

    $tr['avg_order']
        => $averageOrder
];

$summaryStart = $row;

foreach ($summary as $label => $value) {

    $sheet->setCellValue(
        "A{$row}",
        $label
    );

    $sheet->mergeCells(
        "B{$row}:F{$row}"
    );

    $sheet->setCellValue(
        "B{$row}",
        $value
    );

    $sheet->setCellValue(
        "G{$row}",
        $tr['sar']
    );

    $row++;
}

$sheet
    ->getStyle(
        "A{$summaryStart}:G" .
        ($row - 1)
    )
    ->applyFromArray([

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER,

            'vertical' =>
                Alignment::VERTICAL_CENTER
        ],

        'borders' => [

            'allBorders' => [

                'borderStyle' =>
                    Border::BORDER_THIN,

                'color' => [
                    'rgb' => 'CCCCCC'
                ]
            ]
        ]
    ]);

$sheet
    ->getStyle(
        "B{$summaryStart}:F" .
        ($row - 1)
    )
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

/* =========================================================
   أعلى سائق
========================================================= */

$row += 1;

$sheet->setCellValue(
    "A{$row}",
    $tr['top_driver']
);

$sheet->mergeCells(
    "B{$row}:F{$row}"
);

$sheet->setCellValue(
    "B{$row}",
    $topDriverName
);

$sheet->setCellValue(
    "G{$row}",
    $topDriverRevenue
);

$sheet
    ->getStyle(
        "A{$row}:G{$row}"
    )
    ->applyFromArray([

        'font' => [

            'bold' => true,

            'color' => [
                'rgb' => '6F42C1'
            ]
        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [
                'rgb' => 'F3E8FF'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER
        ],

        'borders' => [

            'allBorders' => [

                'borderStyle' =>
                    Border::BORDER_THIN,

                'color' => [
                    'rgb' => 'CCCCCC'
                ]
            ]
        ]
    ]);

$sheet
    ->getStyle(
        "G{$row}"
    )
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

/* =========================================================
   جدول التقرير
========================================================= */

$row += 2;

$headers = [

    '#',

    $tr['driver_code'],

    $tr['name'],

    $tr['phone'],

    $tr['type'],

    $tr['orders'],

    $tr['total']
];

foreach ($headers as $i => $header) {

    $sheet->setCellValue(
        chr(65 + $i) . $row,
        $header
    );
}

$headerRow = $row;

$sheet
    ->getStyle(
        "A{$row}:G{$row}"
    )
    ->applyFromArray([

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
                'rgb' => '007BFF'
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
                    'rgb' => 'CCCCCC'
                ]
            ]
        ]
    ]);

$row++;

/* =========================================================
   البيانات
========================================================= */

if (empty($rows)) {

    $sheet->mergeCells(
        "A{$row}:G{$row}"
    );

    $sheet->setCellValue(
        "A{$row}",
        $tr['no_data']
    );

    $row++;

} else {

    $counter = 1;

    foreach ($rows as $item) {

        $sheet->setCellValue(
            "A{$row}",
            $counter++
        );

        $sheet->setCellValue(
            "B{$row}",
            'DRV-' .
            (int)$item['driver_code']
        );

        $sheet->setCellValue(
            "C{$row}",
            $item['name'] ?? '-'
        );

        $sheet->setCellValue(
            "D{$row}",
            $item['phone'] ?? '-'
        );

        $sheet->setCellValue(
            "E{$row}",
            $orderTypes[
                $item['order_type']
            ]
            ??
            $item['order_type']
            ??
            '-'
        );

        $sheet->setCellValue(
            "F{$row}",
            (int)$item['total_orders']
        );

        $sheet->setCellValue(
            "G{$row}",
            (float)$item['total_revenue']
        );

        $row++;
    }
}

/* =========================================================
   تنسيق جدول البيانات
========================================================= */

$lastRow = $row - 1;

$sheet
    ->getStyle(
        "A{$headerRow}:G{$lastRow}"
    )
    ->applyFromArray([

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
                    'rgb' => 'DDDDDD'
                ]
            ]
        ]
    ]);

if (!empty($rows)) {

    $sheet
        ->getStyle(
            "G" .
            ($headerRow + 1) .
            ":G{$lastRow}"
        )
        ->getNumberFormat()
        ->setFormatCode(
            '#,##0.00'
        );
}

/* =========================================================
   الإجمالي النهائي
========================================================= */

$row += 2;

$sheet->mergeCells(
    "A{$row}:F{$row}"
);

$sheet->setCellValue(
    "A{$row}",
    $tr['grand_revenue']
);

$sheet->setCellValue(
    "G{$row}",
    $totalRevenue
);

$sheet
    ->getStyle(
        "A{$row}:G{$row}"
    )
    ->applyFromArray([

        'font' => [

            'bold' => true,

            'size' => 14,

            'color' => [
                'rgb' => '198754'
            ]
        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [
                'rgb' => 'E9F7EF'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER,

            'vertical' =>
                Alignment::VERTICAL_CENTER
        ],

        'borders' => [

            'allBorders' => [

                'borderStyle' =>
                    Border::BORDER_THIN,

                'color' => [
                    'rgb' => '198754'
                ]
            ]
        ]
    ]);

$sheet
    ->getStyle(
        "G{$row}"
    )
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

/* =========================================================
   أعمدة
========================================================= */

$widths = [

    'A' => 8,
    'B' => 16,
    'C' => 28,
    'D' => 20,
    'E' => 20,
    'F' => 16,
    'G' => 20
];

foreach ($widths as $column => $width) {

    $sheet
        ->getColumnDimension($column)
        ->setWidth($width);
}

/* =========================================================
   إعدادات الصفحة
========================================================= */

$sheet->freezePane(
    "A{$headerRow}"
);

$sheet
    ->getPageSetup()
    ->setOrientation(
        PageSetup::ORIENTATION_LANDSCAPE
    );

$sheet
    ->getPageSetup()
    ->setPaperSize(
        PageSetup::PAPERSIZE_A4
    );

$sheet
    ->getPageMargins()
    ->setTop(0.5)
    ->setBottom(0.5)
    ->setLeft(0.5)
    ->setRight(0.5);

$sheet
    ->getPageSetup()
    ->setPrintArea(
        "A1:G{$row}"
    );

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'orders_report_' .
    date('Y-m-d_H-i-s') .
    '.xlsx';

/* =========================================================
   تنظيف الإخراج
========================================================= */

while (ob_get_level()) {
    ob_end_clean();
}

/* =========================================================
   Headers
========================================================= */

header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);

header(
    'Content-Disposition: attachment; filename="' .
    $fileName .
    '"'
);

header(
    'Cache-Control: max-age=0'
);

header(
    'Pragma: public'
);

/* =========================================================
   إنشاء الملف
========================================================= */

$writer =
    new Xlsx(
        $spreadsheet
    );

$writer->save(
    'php://output'
);

exit;

