<?php

session_start();

include('../include/connected.php');
include('../include/settings.php');

require '../vendor/autoload.php';


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;



$id = isset($_GET['id']) ? intval($_GET['id']) : 0;


if($id <= 0){
    die("رقم السائق غير صحيح");
}



/* ==========================
   بيانات السائق
========================== */


$stmt = $con->prepare("
SELECT *
FROM drivers
WHERE id=?
LIMIT 1
");


$stmt->bind_param("i",$id);

$stmt->execute();


$driver = $stmt->get_result()->fetch_assoc();



if(!$driver){

    die("السائق غير موجود");

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

    $settings[$row['setting_key']]
    =
    $row['setting_value'];

}



$companyName = $settings['company_name'] ?? '';

$systemName  = $settings['system_name'] ?? '';



$reportDate = date('Y-m-d H:i');



/* ==========================
   إنشاء ملف Excel
========================== */


$spreadsheet = new Spreadsheet();


$sheet = $spreadsheet->getActiveSheet();


$sheet->setTitle('بيانات السائق');


$sheet->setRightToLeft(true);



/* ==========================
   شعار الشركة
========================== */


$logoName = $settings['company_logo'] ?? '';

$logoPath = __DIR__.
"/../uploads/logo/".
$logoName;



if(!empty($logoName) && file_exists($logoPath)){


    $drawing = new Drawing();

    $drawing->setName('Logo');

    $drawing->setDescription($companyName);

    $drawing->setPath($logoPath);

    $drawing->setHeight(70);

    $drawing->setCoordinates('F1');

    $drawing->setWorksheet($sheet);

}
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
    'الملف الإلكتروني للسائق'
);


$sheet->mergeCells('A3:F3');

$sheet->setCellValue(
    'A3',
    'تاريخ التقرير : '.$reportDate
);



$sheet->getStyle('A1:A3')
->getFont()
->setBold(true)
->setSize(14);



$sheet->getStyle('A1:A3')
->getAlignment()
->setHorizontal(
    Alignment::HORIZONTAL_CENTER
);



$sheet->getRowDimension(1)
->setRowHeight(35);



/* ==========================
   بيانات السائق
========================== */


$row = 5;



$data = [

    'اسم السائق' => $driver['name'],

    'رقم الهوية' => $driver['national_id'],

    'رقم الجوال' => $driver['phone'],

    'منطقة العمل' => $driver['work_area'],

    'نوع المركبة' => $driver['truck_type'],

    'تاريخ إنشاء الملف' => $driver['created_at'] ?? ''

];



foreach($data as $key=>$value){


    $sheet->setCellValue(
        "A".$row,
        $key
    );


    $sheet->setCellValue(
        "B".$row,
        $value
    );


    $row++;

}



/* ==========================
   مستندات السائق
========================== */


$row += 2;



$sheet->mergeCells("A{$row}:F{$row}");


$sheet->setCellValue(
    "A{$row}",
    "مستندات السائق"
);



$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true)
->setSize(13);



$row++;



$headers = [

    'نوع المستند',
    'رقم المستند',
    'تاريخ الإصدار',
    'تاريخ الانتهاء',
    'الأيام المتبقية',
    'الحالة'

];



foreach($headers as $key=>$header){


    $sheet->setCellValue(
        chr(65+$key).$row,
        $header
    );

}



$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true);



$row++;



/* جلب مستندات السائق */


$documents = mysqli_query($con,"
SELECT *
FROM driver_documents
WHERE driver_id='$id'
ORDER BY expiry_date ASC
");



$today = strtotime(date('Y-m-d'));



while($doc=mysqli_fetch_assoc($documents)){


    $days = '';

    $status = '';



    if(!empty($doc['expiry_date'])){


        $days = floor(
            (strtotime($doc['expiry_date'])-$today)
            /86400
        );



        if($days < 0){

            $status='منتهي';

        }elseif($days <=30){

            $status='قريب الانتهاء';

        }else{

            $status='ساري';

        }


    }



    $sheet->setCellValue(
        "A{$row}",
        $doc['document_type']
    );


    $sheet->setCellValue(
        "B{$row}",
        $doc['document_number']
    );


    $sheet->setCellValue(
        "C{$row}",
        $doc['issue_date']
    );


    $sheet->setCellValue(
        "D{$row}",
        $doc['expiry_date']
    );


    $sheet->setCellValue(
        "E{$row}",
        $days
    );


    $sheet->setCellValue(
        "F{$row}",
        $status
    );


    $row++;

}
/* ==========================
   المركبات المرتبطة بالسائق
========================== */


$row += 2;


$sheet->mergeCells("A{$row}:F{$row}");


$sheet->setCellValue(
    "A{$row}",
    "المركبات المرتبطة بالسائق"
);



$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true)
->setSize(13);



$row++;



$vehicleHeaders = [

    'اللوحة',
    'نوع المركبة',
    'الموديل',
    'اللون',
    'منطقة العمل',
    'الحالة'

];



foreach($vehicleHeaders as $key=>$header){


    $sheet->setCellValue(
        chr(65+$key).$row,
        $header
    );

}



$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true);



$row++;



/* جلب مركبات السائق */


$fleet = mysqli_query($con,"
SELECT *
FROM fleet
WHERE driver='".mysqli_real_escape_string($con,$driver['name'])."'
ORDER BY id DESC
");



while($car=mysqli_fetch_assoc($fleet)){


    $status = "سارية";


    $dates = [

        $car['operation_expiry'],

        $car['insurance_expiration_date'],

        $car['inspection_expiry']

    ];



    foreach($dates as $date){


        if(!empty($date)){


            $days = floor(
                (strtotime($date)-time())/86400
            );


            if($days < 0){

                $status="منتهية";

            }
            elseif($days <=30){

                $status="قريب الانتهاء";

            }


        }


    }



    $sheet->setCellValue(
        "A{$row}",
        $car['plate']
    );


    $sheet->setCellValue(
        "B{$row}",
        $car['typefleet']
    );


    $sheet->setCellValue(
        "C{$row}",
        $car['model']
    );


    $sheet->setCellValue(
        "D{$row}",
        $car['colorfleet']
    );


    $sheet->setCellValue(
        "E{$row}",
        $car['work']
    );


    $sheet->setCellValue(
        "F{$row}",
        $status
    );


    $row++;

}
/* ==========================
   طلبات السائق
========================== */


$row += 2;


$sheet->mergeCells("A{$row}:F{$row}");


$sheet->setCellValue(
    "A{$row}",
    "طلبات السائق"
);



$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true)
->setSize(13);



$row++;



$orderHeaders = [

    'رقم الطلب',
    'المسار',
    'المبلغ',
    'الحالة',
    'التاريخ',
    'رقم الفاتورة'

];



foreach($orderHeaders as $key=>$header){

    $sheet->setCellValue(
        chr(65+$key).$row,
        $header
    );

}



$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true);



$row++;



/* جلب طلبات السائق */


$orders = mysqli_query($con,"
SELECT 
orders.*,
invoices.id AS invoice_id

FROM orders

LEFT JOIN invoices
ON invoices.order_id = orders.id

WHERE orders.driver_id='$id'

ORDER BY orders.id DESC

");



while($order=mysqli_fetch_assoc($orders)){


    $status = match($order['status']){

        'done'=>'مكتمل',

        'cancelled'=>'ملغي',

        'assigned'=>'معين',

        'on_the_way'=>'بالطريق',

        default=>'انتظار'

    };



    $route = 
    ($order['from_city'] ?? '')
    ." ➡️ ".
    ($order['to_city'] ?? '');



    $sheet->setCellValue(
        "A{$row}",
        $order['order_number'] ?? '#'.$order['id']
    );


    $sheet->setCellValue(
        "B{$row}",
        $route
    );


    $sheet->setCellValue(
        "C{$row}",
        $order['price']
    );


    $sheet->setCellValue(
        "D{$row}",
        $status
    );


    $sheet->setCellValue(
        "E{$row}",
        $order['created_at']
    );


    $sheet->setCellValue(
        "F{$row}",
        $order['invoice_id'] ?? '-'
    );


    $row++;

}





/* ==========================
   سجل الحضور
========================== */


$row += 2;


$sheet->mergeCells("A{$row}:F{$row}");


$sheet->setCellValue(
    "A{$row}",
    "سجل حضور السائق"
);



$sheet->getStyle("A{$row}:F{$row}")
->getFont()
->setBold(true)
->setSize(13);



$row++;



$attendanceHeaders = [

    'التاريخ',
    'الدخول',
    'الخروج',
    'الحالة'

];



foreach($attendanceHeaders as $key=>$header){

    $sheet->setCellValue(
        chr(65+$key).$row,
        $header
    );

}



$sheet->getStyle("A{$row}:D{$row}")
->getFont()
->setBold(true);



$row++;



/* جلب الحضور */


$attendance = mysqli_query($con,"
SELECT *

FROM attendance

WHERE driver_id='$id'

ORDER BY attendance_date DESC

");



while($att=mysqli_fetch_assoc($attendance)){


    $status = match($att['status']){

        'present'=>'حاضر',

        'late'=>'متأخر',

        'absent'=>'غائب',

        default=>$att['status']

    };



    $sheet->setCellValue(
        "A{$row}",
        $att['attendance_date']
    );


    $sheet->setCellValue(
        "B{$row}",
        $att['check_in']
    );


    $sheet->setCellValue(
        "C{$row}",
        $att['check_out']
    );


    $sheet->setCellValue(
        "D{$row}",
        $status
    );


    $row++;

}
/* ==========================
   التنسيق النهائي
========================== */


/* توسيع الأعمدة */

foreach(range('A','F') as $col){

    $sheet->getColumnDimension($col)
          ->setAutoSize(true);

}


/* توسيط البيانات */

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


/* التفاف النص */

$sheet->getStyle(
    'A1:F'.$row
)
->getAlignment()
->setWrapText(true);



/* الحدود */

$sheet->getStyle(
    'A5:F'.$row
)
->getBorders()
->getAllBorders()
->setBorderStyle(
    Border::BORDER_THIN
);



/* تلوين رأس التقرير */

$sheet->getStyle('A1:F3')
->getFill()
->setFillType(
    Fill::FILL_SOLID
)
->getStartColor()
->setARGB('D9EAF7');



/* تثبيت أعلى التقرير */

$sheet->freezePane('A5');



/* إعدادات الطباعة */

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



/* تكرار عنوان التقرير */

$sheet->getPageSetup()
->setRowsToRepeatAtTopByStartAndEnd(1,3);



/* تحديد منطقة الطباعة */

$sheet->getPageSetup()
->setPrintArea(
    "A1:F".$row
);



/* ==========================
   إخراج ملف Excel
========================== */


$writer = new Xlsx($spreadsheet);


$fileName = 'Driver_'.$driver['id'].'.xlsx';



header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');


header(
'Content-Disposition: attachment;filename="'.$fileName.'"'
);


header('Cache-Control: max-age=0');



$writer->save('php://output');


exit;