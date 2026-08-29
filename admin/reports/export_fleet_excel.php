<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors',1);

/*==================================
 Composer
==================================*/

require_once __DIR__ . '/../../vendor/autoload.php';

/*==================================
 Database
==================================*/

include __DIR__ . '/../../include/connected.php';
include __DIR__ . '/../../include/settings.php';

/*==================================
 PhpSpreadsheet
==================================*/

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/*==================================
 إنشاء ملف Excel
==================================*/

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('بيانات المركبة');

$reportNumber = "FLT-".date('Ymd')."-".$fleet['id'];

$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1',$companyName);

$sheet->mergeCells('A2:F2');
$sheet->setCellValue('A2','ملف المركبة الإلكتروني');

$sheet->mergeCells('A3:F3');
$sheet->setCellValue('A3','رقم التقرير : '.$reportNumber);

$sheet->mergeCells('A4:F4');
$sheet->setCellValue('A4','تاريخ التقرير : '.$reportDate);

$sheet->setTitle('Fleet Report');

$sheet->setRightToLeft(true);


/*==================================
 بيانات الشركة
==================================*/

$companyName = setting(
    'company_name',
    'منصة الشرق الذكية للخدمات وإدارة الأسطول'
);

$companyPhone = setting(
    'company_phone',
    ''
);

$companyEmail = setting(
    'company_email',
    ''
);

$companyAddress = setting(
    'company_address',
    ''
);

$companyLogo = setting(
    'company_logo',
    ''
);


/*==================================
 الشعار
==================================*/

$logoPath = __DIR__
            . '/../../uploads/logo/'
            . $companyLogo;

if(
    !empty($companyLogo)
    &&
    file_exists($logoPath)
){

    $drawing = new Drawing();

    $drawing->setName($companyName);

    $drawing->setDescription($companyName);

    $drawing->setPath($logoPath);

    $drawing->setHeight(70);

    $drawing->setCoordinates('A1');

    $drawing->setWorksheet($sheet);

}


/*==================================
 عنوان التقرير
==================================*/

$sheet->mergeCells('B1:K1');

$sheet->setCellValue(
    'B1',
    $companyName
);

$sheet->mergeCells('B2:K2');

$sheet->setCellValue(
    'B2',
    'تقرير الأسطول'
);
$row = 6;

$data = [

'رقم اللوحة'      => $fleet['plate'],
'السائق'          => $fleet['driver'],
'نوع المركبة'     => $fleet['typefleet'],
'التصنيف'         => $fleet['classify'],
'الموديل'         => $fleet['model'],
'اللون'           => $fleet['colorfleet'],
'منطقة العمل'     => $fleet['work']

];

foreach($data as $title=>$value){

    $sheet->setCellValue("A$row",$title);
    $sheet->setCellValue("B$row",$value);

    $row++;

}
$sheet->getStyle('A1:F4')->getFont()->setBold(true);

$sheet->getStyle('A1:F4')->getAlignment()->setHorizontal(
    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
);

$sheet->getStyle('A6:A20')->getFont()->setBold(true);

foreach(range('A','F') as $col){

    $sheet->getColumnDimension($col)->setAutoSize(true);

}

$sheet->mergeCells('B3:K3');

$sheet->setCellValue(
    'B3',
    'الهاتف : '
    .$companyPhone
    .'     |     '
    .$companyEmail
);

$sheet->mergeCells('B4:K4');

$sheet->setCellValue(
    'B4',
    'العنوان : '
    .$companyAddress
);

$sheet->mergeCells('B5:K5');

$sheet->setCellValue(
    'B5',
    'تاريخ التقرير : '
    .date('Y-m-d H:i')
);
/*==================================
إحصائيات الأسطول
==================================*/

$today = date('Y-m-d');

/* إجمالي المركبات */

$totalVehicles = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
"))['total'];

/* المنتهية */

$expiredVehicles = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE
operation_expiry < '$today'
OR insurance_expiration_date < '$today'
OR inspection_expiry < '$today'
"))['total'];

/* القريبة من الانتهاء */

$warningVehicles = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM fleet
WHERE
operation_expiry BETWEEN '$today' AND DATE_ADD('$today',INTERVAL 30 DAY)
OR insurance_expiration_date BETWEEN '$today' AND DATE_ADD('$today',INTERVAL 30 DAY)
OR inspection_expiry BETWEEN '$today' AND DATE_ADD('$today',INTERVAL 30 DAY)
"))['total'];

/* السارية */

$validVehicles = $totalVehicles - $expiredVehicles;


/*==================================
عرض الإحصائيات
==================================*/

$sheet->setCellValue('A7','إجمالي');
$sheet->setCellValue('B7',$totalVehicles);

$sheet->setCellValue('D7','المنتهية');
$sheet->setCellValue('E7',$expiredVehicles);

$sheet->setCellValue('G7','قريبة الانتهاء');
$sheet->setCellValue('H7',$warningVehicles);

$sheet->setCellValue('J7','السارية');
$sheet->setCellValue('K7',$validVehicles);


/*==================================
تنسيق الإحصائيات
==================================*/

$sheet->getStyle('A7:K7')->getFont()->setBold(true);

$sheet->getStyle('A7:K7')
      ->getAlignment()
      ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('A7:K7')
      ->getFill()
      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()
      ->setARGB('D9EAF7');
      /*==================================
رؤوس الجدول
==================================*/

$headerRow = 9;

$sheet->setCellValue('A'.$headerRow,'#');
$sheet->setCellValue('B'.$headerRow,'المزود');
$sheet->setCellValue('C'.$headerRow,'اللوحة');
$sheet->setCellValue('D'.$headerRow,'النوع');
$sheet->setCellValue('E'.$headerRow,'التصنيف');
$sheet->setCellValue('F'.$headerRow,'الموديل');
$sheet->setCellValue('G'.$headerRow,'اللون');
$sheet->setCellValue('H'.$headerRow,'منطقة العمل');
$sheet->setCellValue('I'.$headerRow,'كرت التشغيل');
$sheet->setCellValue('J'.$headerRow,'التأمين');
$sheet->setCellValue('K'.$headerRow,'الفحص');
$sheet->setCellValue('L'.$headerRow,'الحالة');
/*==================================
تنسيق رؤوس الجدول
==================================*/

$sheet->getStyle('A9:L9')->getFont()->setBold(true);

$sheet->getStyle('A9:L9')
      ->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('A9:L9')
      ->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()
      ->setARGB('0D6EFD');


/*==================================
بيانات الأسطول
==================================*/

$sql = mysqli_query($con,"
SELECT *
FROM fleet
ORDER BY id DESC
");

$row = 10;

while($fleet = mysqli_fetch_assoc($sql)){

    $sheet->setCellValue('A'.$row,$fleet['id']);
    $sheet->setCellValue('B'.$row,$fleet['driver']);
    $sheet->setCellValue('C'.$row,$fleet['plate']);
    $sheet->setCellValue('D'.$row,$fleet['typefleet']);
    $sheet->setCellValue('E'.$row,$fleet['classify']);
    $sheet->setCellValue('F'.$row,$fleet['model']);
    $sheet->setCellValue('G'.$row,$fleet['colorfleet']);
    $sheet->setCellValue('H'.$row,$fleet['work']);
    $sheet->setCellValue('I'.$row,$fleet['operation_expiry']);
    $sheet->setCellValue('J'.$row,$fleet['insurance_expiration_date']);
    $sheet->setCellValue('K'.$row,$fleet['inspection_expiry']);

    $status = '🟢 سارية';

if($rowColor=='F8D7DA'){
    $status='🔴 منتهية';
}
elseif($rowColor=='FFE5B4'){
    $status='🟠 تنبيه 7 أيام';
}
elseif($rowColor=='FFF3CD'){
    $status='🟡 تنبيه 30 يوم';
}

$sheet->setCellValue('L'.$row,$status);
    /*==================================
تحديد حالة المركبة
==================================*/

$today = strtotime(date('Y-m-d'));

$dates = [
    $fleet['operation_expiry'],
    $fleet['insurance_expiration_date'],
    $fleet['inspection_expiry']
];

$rowColor = 'E8F5E9'; // أخضر افتراضي

foreach($dates as $date){

    if(empty($date)){
        continue;
    }

    $days = floor((strtotime($date)-$today)/86400);

    if($days < 0){

        $rowColor = 'F8D7DA'; // أحمر
        break;

    }

    if($days <= 7){

        $rowColor = 'FFE5B4'; // برتقالي
        continue;

    }

    if($days <= 30 && $rowColor!='FFE5B4'){

        $rowColor = 'FFF3CD'; // أصفر

    }

}

/* تلوين الصف */

$sheet->getStyle("A{$row}:L{$row}")
      ->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()
      ->setARGB($rowColor);

    $row++;
}
/*==================================
تنسيق البيانات
==================================*/

$lastRow = $row - 1;

/* محاذاة */

$sheet->getStyle('A9:L'.$lastRow)
      ->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_CENTER)
      ->setVertical(Alignment::VERTICAL_CENTER);

/* حدود */

$sheet->getStyle('A9:L'.$lastRow)
      ->getBorders()
      ->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

/* عرض الأعمدة */

foreach(range('A','L') as $column){

    $sheet->getColumnDimension($column)
          ->setAutoSize(true);

}

/* تثبيت العناوين */

$sheet->freezePane('A10');

/* فلتر */

$sheet->setAutoFilter('A9:L9');

/* ارتفاع الصفوف */

for($i=9;$i<=$lastRow;$i++){

    $sheet->getRowDimension($i)
          ->setRowHeight(24);

}

/*==================================
تنزيل الملف
==================================*/

$fileName = 'Fleet_Report_'.date('Ymd_His').'.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$fileName.'"');
header('Cache-Control: max-age=0');
$fileName = 'Fleet_Report_'.date('Ymd_His').'.xlsx';

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$fileName.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;