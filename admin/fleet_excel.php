<?php
session_start();

include('../include/connected.php');
include('../include/settings.php');

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id <= 0){
    die("رقم المركبة غير صحيح");
}

/* ==========================
   بيانات المركبة
========================== */

$stmt = $con->prepare("
SELECT *
FROM fleet
WHERE id=?
LIMIT 1
");

$stmt->bind_param("i",$id);
$stmt->execute();

$fleet = $stmt->get_result()->fetch_assoc();

if(!$fleet){
    die("المركبة غير موجودة");
}

/* ==========================
   إعدادات الشركة
========================== */

$settings = [];

$result = mysqli_query($con,"
SELECT setting_key,setting_value
FROM settings
");

while($row=mysqli_fetch_assoc($result)){
    $settings[$row['setting_key']]=$row['setting_value'];
}

$companyName = $settings['company_name'] ?? '';
$systemName  = $settings['system_name'] ?? '';

$reportDate = date('Y-m-d H:i');

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();
/* ==========================
   شعار الشركة
========================== */

$companyLogo = $settings['company_logo'] ?? '';

$logoPath = __DIR__ . '/../uploads/logo/' . $companyLogo;


if(
    !empty($companyLogo)
    &&
    file_exists($logoPath)
){

    $drawing = new Drawing();

    $drawing->setName('Logo');

    $drawing->setDescription($companyName);

    $drawing->setPath($logoPath);

    $drawing->setHeight(70);

    $drawing->setCoordinates('F1');

    $drawing->setWorksheet($sheet);

}

$sheet->setTitle('ملف المركبة');


/* اتجاه الورقة */

$sheet->setRightToLeft(true);


/* ==========================
   رأس التقرير
========================== */


$sheet->mergeCells('A1:F1');

$sheet->setCellValue(
    'A1',
    $companyName
);


$sheet->mergeCells('A2:F2');

$sheet->setCellValue(
    'A2',
    'الملف الإلكتروني للمركبة'
);


$sheet->mergeCells('A3:F3');

$sheet->setCellValue(
    'A3',
    'تاريخ التقرير : '.$reportDate
);



/* تنسيق العنوان */

$sheet->getStyle('A1:A3')
->getFont()
->setBold(true)
->setSize(14);
$sheet->getRowDimension(1)->setRowHeight(35);

$sheet->getStyle('A1:A3')
->getAlignment()
->setHorizontal(
    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
);
$row = 5;


$data = [

    'رقم اللوحة' => $fleet['plate'],
    'نوع المركبة' => $fleet['typefleet'],
    'التصنيف' => $fleet['classify'],
    'الموديل' => $fleet['model'],
    'اللون' => $fleet['colorfleet'],
    'منطقة العمل' => $fleet['work'],
    'السائق' => $fleet['driver']

];


foreach($data as $key=>$value){

    $sheet->setCellValue('A'.$row,$key);
    $sheet->setCellValue('B'.$row,$value);

    $row++;

}
/* ==========================
   ملخص المركبة
========================== */

$row = 5;

$sheet->mergeCells("A{$row}:F{$row}");

$sheet->setCellValue(
    "A{$row}",
    "ملخص حالة المركبة"
);


$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true)
->setSize(13);


$row++;


/* جلب آخر صيانة */

$lastMaintenance = mysqli_fetch_assoc(mysqli_query($con,"
SELECT maintenance_date, maintenance_type
FROM maintenance
WHERE plate_number='".mysqli_real_escape_string($con,$fleet['plate'])."'
ORDER BY id DESC
LIMIT 1
"));


/* آخر تغيير زيت */

$lastOil = mysqli_fetch_assoc(mysqli_query($con,"
SELECT change_date, oil_type
FROM oil_changes
WHERE car_id='$id'
ORDER BY id DESC
LIMIT 1
"));


/* آخر تغيير إطار */

$lastTire = mysqli_fetch_assoc(mysqli_query($con,"
SELECT change_date, tire_type
FROM tires
WHERE car_id='$id'
ORDER BY id DESC
LIMIT 1
"));


/* ==========================
   حالة المركبة
========================== */

$today = strtotime(date('Y-m-d'));

$status = '🟢 سارية';


$dates = [

    $fleet['operation_expiry'],
    $fleet['insurance_expiration_date'],
    $fleet['inspection_expiry']

];


foreach($dates as $date){

    if(empty($date)){
        continue;
    }


    $days = floor(
        (strtotime($date)-$today)/86400
    );


    if($days < 0){

        $status = '🔴 منتهية';
        break;

    }


    if($days <= 30){

        $status = '🟡 قريب الانتهاء';

    }

}

$summary = [

    'رقم اللوحة' => $fleet['plate'],

    'نوع المركبة' => $fleet['typefleet'],

    'حالة المركبة' => $status,

    'آخر صيانة' =>
        ($lastMaintenance['maintenance_date'] ?? '-')
        .' '
        .($lastMaintenance['maintenance_type'] ?? ''),

    'آخر تغيير زيت' =>
        ($lastOil['change_date'] ?? '-')
        .' '
        .($lastOil['oil_type'] ?? ''),

    'آخر تغيير إطار' =>
        ($lastTire['change_date'] ?? '-')
        .' '
        .($lastTire['tire_type'] ?? '')

];


foreach($summary as $title=>$value){

    $sheet->setCellValue(
        "A{$row}",
        $title
    );

    $sheet->setCellValue(
        "B{$row}",
        $value
    );

    $row++;

}

/* ==========================
   مستندات المركبة
========================== */


$row += 2;


$sheet->mergeCells("A{$row}:F{$row}");

$sheet->setCellValue(
    "A{$row}",
    "مستندات المركبة"
);


$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true)
->setSize(13);


$row++;


/* رؤوس الجدول */

$headers = [
    'المستند',
    'تاريخ الانتهاء',
    'الأيام المتبقية',
    'الحالة'
];


$sheet->setCellValue("A{$row}",$headers[0]);
$sheet->setCellValue("B{$row}",$headers[1]);
$sheet->setCellValue("C{$row}",$headers[2]);
$sheet->setCellValue("D{$row}",$headers[3]);


$sheet->getStyle("A{$row}:D{$row}")
->getFont()
->setBold(true);



$row++;


/* جلب المستندات */


$docs = mysqli_query($con,"
SELECT *
FROM vehicle_documents
WHERE car_id='$id'
");


$today = strtotime(date('Y-m-d'));


while($doc = mysqli_fetch_assoc($docs)){


    $expiry = $doc['expiry_date'] ?? '';

    $days = '';

    $status = '';


    if(!empty($expiry)){


        $days = floor(
            (strtotime($expiry)-$today)
            /86400
        );


        if($days < 0){

            $status = 'منتهي';

        }
        elseif($days <= 30){

            $status = 'قريب الانتهاء';

        }
        else{

            $status = 'ساري';

        }

    }


    $sheet->setCellValue(
        "A{$row}",
        $doc['document_type']
    );


    $sheet->setCellValue(
        "B{$row}",
        $expiry
    );


    $sheet->setCellValue(
        "C{$row}",
        $days
    );


    $sheet->setCellValue(
        "D{$row}",
        $status
    );


    $row++;

}
/* ==========================
   سجل الصيانة
========================== */


$row += 2;


$sheet->mergeCells("A{$row}:F{$row}");

$sheet->setCellValue(
    "A{$row}",
    "سجل صيانة المركبة"
);


$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true)
->setSize(13);


$row++;


/* رؤوس الجدول */

$maintenanceHeaders = [

    'تاريخ الصيانة',
    'نوع الصيانة',
    'الملاحظات',
    'التكلفة',
    'السائق'

];


foreach($maintenanceHeaders as $key=>$header){

    $sheet->setCellValue(
        chr(65+$key).$row,
        $header
    );

}


$sheet->getStyle("A{$row}:E{$row}")
->getFont()
->setBold(true);


$row++;


/* ==========================
   جلب بيانات الصيانة
========================== */

$maintenance = mysqli_query($con,"
SELECT *
FROM maintenance
WHERE plate_number='".mysqli_real_escape_string($con,$fleet['plate'])."'
ORDER BY maintenance_date DESC
");


while($item = mysqli_fetch_assoc($maintenance)){


    $sheet->setCellValue(
        "A{$row}",
        $item['maintenance_date']
    );


    $sheet->setCellValue(
        "B{$row}",
        $item['maintenance_type']
    );


    $sheet->setCellValue(
        "C{$row}",
        $item['notes']
    );


    $sheet->setCellValue(
        "D{$row}",
        $item['cost']
    );


    $sheet->setCellValue(
        "E{$row}",
        $item['driver']
    );


    $row++;

}

/* ==========================
   سجل تغيير الزيت
========================== */


$row += 2;


$sheet->mergeCells("A{$row}:F{$row}");

$sheet->setCellValue(
    "A{$row}",
    "سجل تغيير الزيت"
);


$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true)
->setSize(13);


$row++;


/* رؤوس الجدول */

$oilHeaders = [

    'تاريخ التغيير',
    'نوع الزيت',
    'العداد الحالي',
    'العداد القادم',
    'التغيير القادم',
    'التكلفة'

];


foreach($oilHeaders as $key=>$header){

    $sheet->setCellValue(
        chr(65+$key).$row,
        $header
    );

}


$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true);


$row++;


/* جلب بيانات الزيت */

$oil = mysqli_query($con,"
SELECT *
FROM oil_changes
WHERE car_id='$id'
ORDER BY change_date DESC
");


while($item = mysqli_fetch_assoc($oil)){


    $sheet->setCellValue(
        "A{$row}",
        $item['change_date']
    );


    $sheet->setCellValue(
        "B{$row}",
        $item['oil_type']
    );


    $sheet->setCellValue(
        "C{$row}",
        $item['current_km']
    );


    $sheet->setCellValue(
        "D{$row}",
        $item['next_km']
    );


    $sheet->setCellValue(
        "E{$row}",
        $item['next_change']
    );


    $sheet->setCellValue(
        "F{$row}",
        $item['cost']
    );


    $row++;

}
/* ==========================
   سجل الإطارات
========================== */


$row += 2;


$sheet->mergeCells("A{$row}:F{$row}");

$sheet->setCellValue(
    "A{$row}",
    "سجل الإطارات"
);


$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true)
->setSize(13);


$row++;


/* رؤوس الجدول */

$tireHeaders = [

    'تاريخ التغيير',
    'نوع الإطار',
    'العداد الحالي',
    'العداد القادم',
    'التغيير القادم',
    'التكلفة'

];


foreach($tireHeaders as $key=>$header){

    $sheet->setCellValue(
        chr(65+$key).$row,
        $header
    );

}


$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true);


$row++;


/* جلب بيانات الإطارات */

$tires = mysqli_query($con,"
SELECT *
FROM tires
WHERE car_id='$id'
ORDER BY change_date DESC
");


while($item = mysqli_fetch_assoc($tires)){


    $sheet->setCellValue(
        "A{$row}",
        $item['change_date']
    );


    $sheet->setCellValue(
        "B{$row}",
        $item['tire_type']
    );


    $sheet->setCellValue(
    "C{$row}",
    $item['current_km']
);

    $sheet->setCellValue(
        "D{$row}",
        $item['next_km']
    );


    $sheet->setCellValue(
        "E{$row}",
        $item['next_change']
    );


    $sheet->setCellValue(
        "F{$row}",
        $item['cost']
    );


    $row++;

}




foreach(range('A','F') as $col){

    $sheet->getColumnDimension($col)
    ->setAutoSize(true);

}
/* ==========================
   التنسيق النهائي
========================== */


/* توسيع الأعمدة */

foreach(range('A','F') as $column){

    $sheet->getColumnDimension($column)
          ->setAutoSize(true);

}


/* توسيط كل البيانات */

$sheet->getStyle(
    'A1:F'.$row
)
->getAlignment()
->setHorizontal(
    Alignment::HORIZONTAL_CENTER
)
->setVertical(
    Alignment::VERTICAL_CENTER
);


/* حدود الجداول */

$sheet->getStyle(
    'A5:F'.$row
)
->getBorders()
->getAllBorders()
->setBorderStyle(
    Border::BORDER_THIN
);


/* عناوين الأقسام */

$sheet->getStyle('A1:F3')
->getFill()
->setFillType(Fill::FILL_SOLID)
->getStartColor()
->setARGB('D9EAF7');


/* تثبيت بداية الملف */

$sheet->freezePane('A5');


/* ارتفاع الصفوف */

for($i=1;$i<=$row;$i++){

    $sheet->getRowDimension($i)
          ->setRowHeight(22);

}

/* ==========================
   إعدادات الطباعة
========================== */


$sheet->getPageSetup()
      ->setOrientation(
          \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
      );


$sheet->getPageSetup()
      ->setPaperSize(
          \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
      );


$sheet->getPageMargins()
      ->setTop(0.5)
      ->setRight(0.5)
      ->setLeft(0.5)
      ->setBottom(0.5);



/* تكرار العنوان عند تعدد الصفحات */

$sheet->getPageSetup()
      ->setRowsToRepeatAtTopByStartAndEnd(1,3);



/* محاذاة النص الطويل */

$sheet->getStyle("A1:F".$row)
      ->getAlignment()
      ->setWrapText(true);



/* تحديد منطقة الطباعة */

$sheet->getPageSetup()
      ->setPrintArea(
          "A1:F".$row
      );
      
/* سيتم بناء التقرير هنا */
$writer = new Xlsx($spreadsheet);

$fileName = 'Fleet_'.$fleet['plate'].'.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$fileName.'"');
header('Cache-Control: max-age=0');
foreach(range('A','F') as $col){

    $sheet->getColumnDimension($col)
    ->setAutoSize(true);

}
$writer->save('php://output');
exit;