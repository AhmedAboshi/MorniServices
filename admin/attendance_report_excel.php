<?php

session_start();

include('../include/connected.php');
include('../include/settings.php');

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
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
   الفلاتر - نفس attendance_report.php
========================================================= */

$search = trim($_GET['search'] ?? '');

$driver_id = (int)($_GET['driver_id'] ?? 0);

$status_filter = trim($_GET['status'] ?? '');

$from = trim($_GET['from'] ?? '');

$to = trim($_GET['to'] ?? '');

/* =========================================================
   النصوص
========================================================= */

$text = [

    'ar' => [

        'title'             => 'تقرير الحضور والانصراف',
        'company_report'    => 'تقرير حضور وانصراف السائقين',

        'id'                => '#',
        'image'             => 'الصورة',
        'name'              => 'اسم السائق',
        'phone'             => 'الجوال',
        'work_area'         => 'منطقة العمل',
        'date'              => 'التاريخ',
        'check_in'          => 'الحضور',
        'check_out'         => 'الانصراف',
        'status'            => 'الحالة',

        'present'           => 'حاضر',
        'late'              => 'متأخر',
        'absent'            => 'غائب',

        'filters'           => 'الفلاتر المستخدمة',
        'search_filter'     => 'البحث',
        'driver_filter'    => 'السائق',
        'status_filter'    => 'الحالة',
        'from_filter'      => 'من',
        'to_filter'        => 'إلى',

        'all_records'       => 'جميع السجلات',
        'all_drivers'       => 'جميع السائقين',
        'all_status'        => 'جميع الحالات',

        'total_records'     => 'إجمالي السجلات',
        'total_present'     => 'إجمالي الحضور',
        'total_late'        => 'إجمالي التأخير',
        'total_absent'      => 'إجمالي الغياب',
        'total_drivers'     => 'عدد السائقين',

        'no_data'           => 'لا توجد سجلات مطابقة للفلاتر المحددة',

        'generated_at'      => 'تاريخ إنشاء التقرير'

    ],

    'en' => [

        'title'             => 'Attendance Report',
        'company_report'    => 'Driver Attendance Report',

        'id'                => '#',
        'image'             => 'Image',
        'name'              => 'Driver Name',
        'phone'             => 'Phone',
        'work_area'         => 'Work Area',
        'date'              => 'Date',
        'check_in'          => 'Check In',
        'check_out'         => 'Check Out',
        'status'            => 'Status',

        'present'           => 'Present',
        'late'              => 'Late',
        'absent'            => 'Absent',

        'filters'           => 'Applied Filters',
        'search_filter'     => 'Search',
        'driver_filter'    => 'Driver',
        'status_filter'    => 'Status',
        'from_filter'      => 'From',
        'to_filter'        => 'To',

        'all_records'       => 'All Records',
        'all_drivers'       => 'All Drivers',
        'all_status'        => 'All Statuses',

        'total_records'     => 'Total Records',
        'total_present'     => 'Total Present',
        'total_late'        => 'Total Late',
        'total_absent'      => 'Total Absent',
        'total_drivers'     => 'Total Drivers',

        'no_data'           => 'No attendance records match the selected filters',

        'generated_at'      => 'Generated At'

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

    /*
     * قاعدة البيانات قد تحتوي على:
     * present / late / absent
     * أو القيم العربية
     */

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

    /* توحيد الحالات */

    if (
        strtolower($status) === 'late' ||
        $status === 'متأخر'
    ) {

        $row['status_text'] =
            $t['late'];

        $row['status_color'] =
            'F59E0B';

        $lateCount++;

    } elseif (
        strtolower($status) === 'absent' ||
        $status === 'غائب'
    ) {

        $row['status_text'] =
            $t['absent'];

        $row['status_color'] =
            'DC3545';

        $absentCount++;

    } else {

        $row['status_text'] =
            $t['present'];

        $row['status_color'] =
            '198754';

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
   معلومات الفلتر المحدد
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

$companyLogo =
    $settingsData['company_logo']
    ?? '';

/* =========================================================
   Spreadsheet
========================================================= */

$spreadsheet =
    new Spreadsheet();

$sheet =
    $spreadsheet
        ->getActiveSheet();

$sheet->setTitle(
    $lang === 'ar'
        ? 'تقرير الحضور'
        : 'Attendance Report'
);

$sheet->setRightToLeft(
    $lang === 'ar'
);

/* =========================================================
   شعار الشركة
========================================================= */

$logoPath = '';

if ($companyLogo !== '') {

    $logoCandidates = [

        __DIR__ .
        '/../uploads/logo/' .
        basename($companyLogo),

        __DIR__ .
        '/../' .
        ltrim(
            str_replace(
                '\\',
                '/',
                $companyLogo
            ),
            '/'
        )

    ];

    foreach (
        $logoCandidates
        as $candidate
    ) {

        $real =
            realpath($candidate);

        if (
            $real !== false &&
            is_file($real)
        ) {

            $logoPath =
                $real;

            break;
        }
    }
}

if ($logoPath !== '') {

    $drawing =
        new Drawing();

    $drawing->setName(
        'Company Logo'
    );

    $drawing->setDescription(
        $companyName
    );

    $drawing->setPath(
        $logoPath
    );

    $drawing->setHeight(60);

    $drawing->setCoordinates(
        'J1'
    );

    $drawing->setWorksheet(
        $sheet
    );
}

/* =========================================================
   العنوان
========================================================= */

$sheet->mergeCells(
    'A1:I1'
);

$sheet->setCellValue(
    'A1',
    $companyName
);

$sheet->mergeCells(
    'A2:I2'
);

$sheet->setCellValue(
    'A2',
    $t['company_report']
);

$sheet->mergeCells(
    'A3:I3'
);

$sheet->setCellValue(
    'A3',
    $t['generated_at'] .
    ': ' .
    date('Y-m-d H:i')
);

/* =========================================================
   تنسيق العنوان
========================================================= */

$sheet
    ->getStyle('A1:I1')
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
    ->getStyle('A2:I2')
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
    ->getStyle('A3:I3')
    ->applyFromArray([

        'font' => [

            'size' => 10,

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
    ->setRowHeight(35);

$sheet
    ->getRowDimension(2)
    ->setRowHeight(28);

$sheet
    ->getRowDimension(3)
    ->setRowHeight(22);

/* =========================================================
   الفلاتر المستخدمة
========================================================= */

$filterText = [];

/* البحث */

if ($search !== '') {

    $filterText[] =
        $t['search_filter'] .
        ': ' .
        $search;
}

/* السائق */

if ($selectedDriverName !== '') {

    $filterText[] =
        $t['driver_filter'] .
        ': ' .
        $selectedDriverName;
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

    $filterText[] =
        $t['status_filter'] .
        ': ' .
        $statusLabel;
}

/* من */

if ($from !== '') {

    $filterText[] =
        $t['from_filter'] .
        ': ' .
        $from;
}

/* إلى */

if ($to !== '') {

    $filterText[] =
        $t['to_filter'] .
        ': ' .
        $to;
}

$filterString =
    !empty($filterText)
        ? implode(
            ' | ',
            $filterText
        )
        : $t['all_records'];

/* =========================================================
   عرض الفلاتر
========================================================= */

$sheet->mergeCells(
    'A4:I4'
);

$sheet->setCellValue(
    'A4',
    $filterString
);

$sheet
    ->getStyle('A4:I4')
    ->applyFromArray([

        'font' => [

            'italic' => true,

            'size' => 10,

            'color' => [
                'rgb' => '666666'
            ]
        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [
                'rgb' => 'F1F5F9'
            ]
        ],

        'alignment' => [

            'horizontal' =>
                Alignment::HORIZONTAL_CENTER,

            'vertical' =>
                Alignment::VERTICAL_CENTER,

            'wrapText' => true
        ]

    ]);

$sheet
    ->getRowDimension(4)
    ->setRowHeight(28);

/* =========================================================
   ملخص
========================================================= */

$sheet->setCellValue(
    'A5',
    $t['total_records']
);

$sheet->setCellValue(
    'B5',
    $totalRecords
);

$sheet->setCellValue(
    'C5',
    $t['total_present']
);

$sheet->setCellValue(
    'D5',
    $presentCount
);

$sheet->setCellValue(
    'E5',
    $t['total_late']
);

$sheet->setCellValue(
    'F5',
    $lateCount
);

$sheet->setCellValue(
    'G5',
    $t['total_absent']
);

$sheet->setCellValue(
    'H5',
    $absentCount
);

$sheet->setCellValue(
    'I5',
    $totalDrivers
);

$sheet
    ->getStyle('A5:I5')
    ->applyFromArray([

        'font' => [

            'bold' => true
        ],

        'fill' => [

            'fillType' =>
                Fill::FILL_SOLID,

            'startColor' => [
                'rgb' => 'E9ECEF'
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

/* =========================================================
   رؤوس الجدول
========================================================= */

$headers = [

    'A7' => $t['id'],

    'B7' => $t['image'],

    'C7' => $t['name'],

    'D7' => $t['phone'],

    'E7' => $t['work_area'],

    'F7' => $t['date'],

    'G7' => $t['check_in'],

    'H7' => $t['check_out'],

    'I7' => $t['status']

];

foreach (
    $headers
    as $cell => $value
) {

    $sheet->setCellValue(
        $cell,
        $value
    );
}

$sheet
    ->getStyle('A7:I7')
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
                'rgb' => '343A40'
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
                    'rgb' => 'CCCCCC'
                ]
            ]
        ]

    ]);

$sheet
    ->getRowDimension(7)
    ->setRowHeight(30);

/* =========================================================
   البيانات
========================================================= */

$rowNumber = 8;

$counter = 1;

if (empty($rows)) {

    $sheet->mergeCells(
        'A8:I8'
    );

    $sheet->setCellValue(
        'A8',
        $t['no_data']
    );

    $sheet
        ->getStyle('A8:I8')
        ->applyFromArray([

            'font' => [

                'bold' => true,

                'color' => [
                    'rgb' => '777777'
                ]
            ],

            'alignment' => [

                'horizontal' =>
                    Alignment::HORIZONTAL_CENTER,

                'vertical' =>
                    Alignment::VERTICAL_CENTER
            ]

        ]);

    $rowNumber = 9;

} else {

    foreach ($rows as $row) {

        /* ---------------------------------------------
           رقم
        --------------------------------------------- */

        $sheet->setCellValue(
            "A{$rowNumber}",
            $counter
        );

        /* ---------------------------------------------
           الصورة
        --------------------------------------------- */

        $imageFile = '';

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
        }

        /*
         * جعل الصف أكبر للصورة
         */

        $sheet
            ->getRowDimension(
                $rowNumber
            )
            ->setRowHeight(60);

        if ($imageFile !== '') {

            $drawing =
                new Drawing();

            $drawing->setName(
                'Driver Image ' .
                $counter
            );

            $drawing->setDescription(
                $row['name'] ?? 'Driver'
            );

            $drawing->setPath(
                $imageFile
            );

            $drawing->setHeight(50);

            $drawing->setCoordinates(
                "B{$rowNumber}"
            );

            $drawing->setOffsetX(5);

            $drawing->setOffsetY(5);

            $drawing->setWorksheet(
                $sheet
            );
        } else {

            $sheet->setCellValue(
                "B{$rowNumber}",
                '-'
            );
        }

        /* ---------------------------------------------
           الاسم
        --------------------------------------------- */

        $sheet->setCellValue(
            "C{$rowNumber}",
            $row['name'] ?? '-'
        );

        /* ---------------------------------------------
           الجوال
        --------------------------------------------- */

        $sheet->setCellValue(
            "D{$rowNumber}",
            $row['phone'] ?? '-'
        );

        /* ---------------------------------------------
           منطقة العمل
        --------------------------------------------- */

        $sheet->setCellValue(
            "E{$rowNumber}",
            $row['work_area'] ?? '-'
        );

        /* ---------------------------------------------
           التاريخ
        --------------------------------------------- */

        $sheet->setCellValue(
            "F{$rowNumber}",
            $row['attendance_date'] ?? ''
        );

        /* ---------------------------------------------
           الحضور
        --------------------------------------------- */

        $sheet->setCellValue(
            "G{$rowNumber}",
            $row['check_in'] ?? '-'
        );

        /* ---------------------------------------------
           الانصراف
        --------------------------------------------- */

        $sheet->setCellValue(
            "H{$rowNumber}",
            $row['check_out'] ?? '-'
        );

        /* ---------------------------------------------
           الحالة
        --------------------------------------------- */

        $sheet->setCellValue(
            "I{$rowNumber}",
            $row['status_text'] ?? $t['present']
        );

        /* ---------------------------------------------
           تنسيق
        --------------------------------------------- */

        $sheet
            ->getStyle(
                "A{$rowNumber}:I{$rowNumber}"
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

        /* لون الحالة */

        $statusColor =
            $row['status_color']
            ?? '198754';

        $sheet
            ->getStyle(
                "I{$rowNumber}"
            )
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle(
                "I{$rowNumber}"
            )
            ->getFont()
            ->getColor()
            ->setRGB(
                $statusColor
            );

        /* تلوين الصف */

        if (
            ($row['status_key'] ?? '')
            === 'late'
        ) {

            $sheet
                ->getStyle(
                    "A{$rowNumber}:I{$rowNumber}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                );

            $sheet
                ->getStyle(
                    "A{$rowNumber}:I{$rowNumber}"
                )
                ->getFill()
                ->getStartColor()
                ->setRGB(
                    'FFF8E1'
                );

        } elseif (
            ($row['status_key'] ?? '')
            === 'absent'
        ) {

            $sheet
                ->getStyle(
                    "A{$rowNumber}:I{$rowNumber}"
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                );

            $sheet
                ->getStyle(
                    "A{$rowNumber}:I{$rowNumber}"
                )
                ->getFill()
                ->getStartColor()
                ->setRGB(
                    'FFF1F2'
                );
        }

        $rowNumber++;

        $counter++;
    }
}

/* =========================================================
   أحجام الأعمدة
========================================================= */

$widths = [

    'A' => 8,

    'B' => 18,

    'C' => 25,

    'D' => 18,

    'E' => 25,

    'F' => 18,

    'G' => 15,

    'H' => 15,

    'I' => 16

];

foreach (
    $widths
    as $column => $width
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
   تجميد
========================================================= */

$sheet->freezePane(
    'A8'
);

/* =========================================================
   AutoFilter
========================================================= */

$lastDataRow =
    max(
        7,
        $rowNumber - 1
    );

$sheet->setAutoFilter(
    "A7:I{$lastDataRow}"
);

/* =========================================================
   إعدادات الطباعة
========================================================= */

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
    ->setRowsToRepeatAtTopByStartAndEnd(
        1,
        7
    );

$sheet
    ->getPageSetup()
    ->setPrintArea(
        "A1:I{$rowNumber}"
    );

/* =========================================================
   اسم الملف
========================================================= */

$fileName =
    'attendance_report_' .
    date('Y-m-d_H-i-s') .
    '.xlsx';

/* =========================================================
   تنظيف Output
========================================================= */

if (ob_get_length()) {
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

$stmt->close();

exit;