
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
   الفلاتر
========================================================= */

$search    = trim($_GET['search'] ?? '');
$from      = trim($_GET['from'] ?? '');
$to        = trim($_GET['to'] ?? '');
$orderType = trim($_GET['order_type'] ?? '');

/* =========================================================
   الترجمة
========================================================= */

$t = [

    'ar' => [

        'company'       => 'شركة الشرق لخدمات السيارات',
        'title'         => 'تقرير الفواتير',

        'period'        => 'الفترة',
        'all_period'    => 'كل الفترات',
        'report_date'   => 'تاريخ التقرير',

        'search'        => 'البحث',
        'invoice_no'    => 'رقم الفاتورة',
        'invoice_date'  => 'تاريخ الفاتورة',
        'customer'      => 'العميل',
        'phone'         => 'الهاتف',
        'total'         => 'الإجمالي',
        'type'          => 'نوع الطلب',

        'summary'       => 'ملخص التقرير',
        'count'         => 'عدد الفواتير',
        'sum'           => 'إجمالي المبالغ',
        'average'       => 'متوسط الفاتورة',

        'no_data'       => 'لا توجد فواتير مطابقة للفلاتر',

        'sar'           => 'ريال'
    ],

    'en' => [

        'company'       => 'Al Sharq Automotive Services Company',
        'title'         => 'Invoices Report',

        'period'        => 'Period',
        'all_period'    => 'All Periods',
        'report_date'   => 'Report Date',

        'search'        => 'Search',
        'invoice_no'    => 'Invoice Number',
        'invoice_date'  => 'Invoice Date',
        'customer'      => 'Customer',
        'phone'         => 'Phone',
        'total'         => 'Total',
        'type'          => 'Order Type',

        'summary'       => 'Report Summary',
        'count'         => 'Invoice Count',
        'sum'           => 'Total Amount',
        'average'       => 'Average Invoice',

        'no_data'       => 'No invoices match the selected filters',

        'sar'           => 'SAR'
    ]
];

$tr = $t[$lang];

/* =========================================================
   بناء شروط SQL
========================================================= */

$where = [];

$params = [];

$types = '';

if ($search !== '') {

    $where[] = "
        (
            invoices.invoice_number LIKE ?
            OR orders.full_name LIKE ?
            OR orders.phone LIKE ?
        )
    ";

    $value = '%' . $search . '%';

    $params[] = $value;
    $params[] = $value;
    $params[] = $value;

    $types .= 'sss';
}

if ($from !== '') {

    $where[] =
        "DATE(invoices.created_at) >= ?";

    $params[] = $from;

    $types .= 's';
}

if ($to !== '') {

    $where[] =
        "DATE(invoices.created_at) <= ?";

    $params[] = $to;

    $types .= 's';
}

if ($orderType !== '') {

    $where[] =
        "orders.order_type = ?";

    $params[] = $orderType;

    $types .= 's';
}

$whereSql = '';

if (!empty($where)) {

    $whereSql =
        'WHERE ' .
        implode(
            ' AND ',
            $where
        );
}

/* =========================================================
   جلب الفواتير
========================================================= */

$sql = "

    SELECT

        invoices.id,

        invoices.invoice_number,

        invoices.created_at,

        invoices.total_with_vat,

        orders.full_name,

        orders.phone,

        orders.order_type

    FROM invoices

    LEFT JOIN orders
        ON invoices.order_id = orders.id

    $whereSql

    ORDER BY
        invoices.id DESC
";

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        'SQL Error: ' .
        htmlspecialchars(
            $con->error
        )
    );
}

if (!empty($params)) {

    if (
        strlen($types) !== count($params)
    ) {

        die(
            'Filter parameters mismatch.'
        );
    }

    $stmt->bind_param(
        $types,
        ...$params
    );
}

if (!$stmt->execute()) {

    die(
        'Execute Error: ' .
        htmlspecialchars(
            $stmt->error
        )
    );
}

$result =
    $stmt->get_result();

$rows = [];

$totalInvoices = 0;

$totalAmount = 0;

while ($row = $result->fetch_assoc()) {

    $row['total_with_vat'] =
        (float)(
            $row['total_with_vat']
            ?? 0
        );

    $rows[] = $row;

    $totalInvoices++;

    $totalAmount +=
        $row['total_with_vat'];
}

$stmt->close();

$averageInvoice =
    $totalInvoices > 0
        ? $totalAmount / $totalInvoices
        : 0;

/* =========================================================
   الفترة
========================================================= */

$periodText =

    ($from !== '' || $to !== '')

    ? (
        ($from !== ''
            ? $from
            : '...')
        .
        ' - '
        .
        ($to !== ''
            ? $to
            : '...')
    )

    : $tr['all_period'];

/* =========================================================
   Excel
========================================================= */

$spreadsheet =
    new Spreadsheet();

$sheet =
    $spreadsheet->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'تقرير الفواتير'
        : 'Invoices Report'
);

$sheet->setRightToLeft(
    $lang === 'ar'
);

/* =========================================================
   العناوين
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
   تنسيق العنوان
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
                Alignment::HORIZONTAL_CENTER,

            'vertical' =>
                Alignment::VERTICAL_CENTER
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
                Alignment::HORIZONTAL_CENTER,

            'vertical' =>
                Alignment::VERTICAL_CENTER
        ]
    ]);

$sheet
    ->getRowDimension(1)
    ->setRowHeight(32);

$sheet
    ->getRowDimension(2)
    ->setRowHeight(28);

/* =========================================================
   الفلاتر المستخدمة
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

if ($orderType !== '') {

    $sheet->setCellValue(
        "A{$row}",
        $tr['type']
    );

    $sheet->mergeCells(
        "B{$row}:G{$row}"
    );

    $sheet->setCellValue(
        "B{$row}",
        $orderType
    );

    $row++;
}

/* =========================================================
   ملخص
========================================================= */

$row += 1;

$sheet->mergeCells(
    "A{$row}:G{$row}"
);

$sheet->setCellValue(
    "A{$row}",
    $tr['summary']
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

$summaryStart = $row;

$summary = [

    $tr['count']
        => $totalInvoices,

    $tr['sum']
        => $totalAmount,

    $tr['average']
        => $averageInvoice
];

foreach (
    $summary
    as $label => $value
) {

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
   جدول الفواتير
========================================================= */

$row += 2;

$headers = [

    '#',

    $tr['invoice_no'],

    $tr['invoice_date'],

    $tr['customer'],

    $tr['phone'],

    $tr['total'],

    $tr['type']
];

foreach (
    $headers
    as $index => $header
) {

    $sheet->setCellValue(
        chr(65 + $index) . $row,
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

    foreach (
        $rows
        as $invoice
    ) {

        $sheet->setCellValue(
            "A{$row}",
            $counter++
        );

        $sheet->setCellValue(
            "B{$row}",
            $invoice['invoice_number']
                ?? '-'
        );

        $sheet->setCellValue(
            "C{$row}",
            $invoice['created_at']
                ?? '-'
        );

        $sheet->setCellValue(
            "D{$row}",
            $invoice['full_name']
                ?? '-'
        );

        $sheet->setCellValue(
            "E{$row}",
            $invoice['phone']
                ?? '-'
        );

        $sheet->setCellValue(
            "F{$row}",
            (float)(
                $invoice['total_with_vat']
                ?? 0
            )
        );

        $sheet->setCellValue(
            "G{$row}",
            $invoice['order_type']
                ?? '-'
        );

        $row++;
    }
}

/* =========================================================
   تنسيق الجدول
========================================================= */

$lastDataRow =
    $row - 1;

$sheet
    ->getStyle(
        "A{$headerRow}:G{$lastDataRow}"
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
            "F" .
            ($headerRow + 1) .
            ":F{$lastDataRow}"
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
    "A{$row}:E{$row}"
);

$sheet->setCellValue(
    "A{$row}",
    $tr['sum']
);

$sheet->setCellValue(
    "F{$row}",
    $totalAmount
);

$sheet->setCellValue(
    "G{$row}",
    $tr['sar']
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
        "F{$row}"
    )
    ->getNumberFormat()
    ->setFormatCode(
        '#,##0.00'
    );

/* =========================================================
   أحجام الأعمدة
========================================================= */

$widths = [

    'A' => 8,
    'B' => 22,
    'C' => 20,
    'D' => 28,
    'E' => 20,
    'F' => 18,
    'G' => 20
];

foreach (
    $widths
    as $column =>
    $width
) {

    $sheet
        ->getColumnDimension(
            $column
        )
        ->setWidth(
            $width
        );
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
    'invoices_report_' .
    date('Y-m-d_H-i-s') .
    '.xlsx';

/* =========================================================
   تنظيف Output
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
   إنشاء Excel
========================================================= */

$writer =
    new Xlsx(
        $spreadsheet
    );

$writer->save(
    'php://output'
);

exit;
?>

