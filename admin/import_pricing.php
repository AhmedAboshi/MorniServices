<?php
session_start();
include('../include/connected.php');

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$msg = '';

if(isset($_POST['import'])){

    if(!isset($_FILES['excel']) || $_FILES['excel']['error'] != 0){
        $msg = "اختر ملف Excel";
    }else{

        $file = $_FILES['excel']['tmp_name'];

        try{

            $spreadsheet = IOFactory::load($file);

            $sheet = $spreadsheet->getSheetByName('Between cities');

            if(!$sheet){
                die("لم يتم العثور على صفحة Between cities");
            }

            $rows = $sheet->toArray();

            $last_from = '';

foreach($rows as $index => $row){

    if($index < 3){
        continue;
    }

    $from = trim($row[0] ?? '');
    $to   = trim($row[1] ?? '');

    // إذا كانت الخلية فارغة استخدم آخر مدينة
    if(!empty($from)){
        $last_from = $from;
    }else{
        $from = $last_from;
    }

    if(empty($from) || empty($to)){
        continue;
    }

    $regular_customer   = floatval(str_replace(',','',$row[2] ?? 0));
    $hydraulic_customer = floatval(str_replace(',','',$row[3] ?? 0));
    $covered_customer   = floatval(str_replace(',','',$row[4] ?? 0));

    $regular_driver     = floatval(str_replace(',','',$row[10] ?? 0));
    $hydraulic_driver   = floatval(str_replace(',','',$row[11] ?? 0));
    $covered_driver     = floatval(str_replace(',','',$row[12] ?? 0));

    $check = $con->prepare("
        SELECT id
        FROM transport_pricing
        WHERE from_city=? AND to_city=?
    ");

    $check->bind_param("ss",$from,$to);
    $check->execute();

    $result = $check->get_result();

    if($result->num_rows){

        $pricing = $result->fetch_assoc();

        $update = $con->prepare("
            UPDATE transport_pricing SET
            regular_customer=?,
            hydraulic_customer=?,
            covered_customer=?,
            regular_driver=?,
            hydraulic_driver=?,
            covered_driver=?
            WHERE id=?
        ");

        $update->bind_param(
            "ddddddi",
            $regular_customer,
            $hydraulic_customer,
            $covered_customer,
            $regular_driver,
            $hydraulic_driver,
            $covered_driver,
            $pricing['id']
        );

        $update->execute();

    }else{

        $insert = $con->prepare("
            INSERT INTO transport_pricing
            (
                from_city,
                to_city,
                regular_customer,
                hydraulic_customer,
                covered_customer,
                regular_driver,
                hydraulic_driver,
                covered_driver
            )
            VALUES (?,?,?,?,?,?,?,?)
        ");

        $insert->bind_param(
            "ssdddddd",
            $from,
            $to,
            $regular_customer,
            $hydraulic_customer,
            $covered_customer,
            $regular_driver,
            $hydraulic_driver,
            $covered_driver
        );

        $insert->execute();
    }

    $count++;
}

            $msg = "تم استيراد {$count} مسار بنجاح";

        }catch(Exception $e){

            $msg = $e->getMessage();

        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<title>استيراد الأسعار</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body class="bg-light">

<div class="container mt-5">

    <div class="card shadow">

        <div class="card-header">
            استيراد أسعار النقل بين المدن
        </div>

        <div class="card-body">

            <?php if($msg){ ?>
                <div class="alert alert-info">
                    <?= $msg ?>
                </div>
            <?php } ?>

            <form method="post" enctype="multipart/form-data">

                <div class="mb-3">
                    <label class="form-label">
                        ملف Excel
                    </label>

                    <input
                        type="file"
                        name="excel"
                        class="form-control"
                        accept=".xlsx,.xls"
                        required>
                </div>

                <button
                    type="submit"
                    name="import"
                    class="btn btn-success">

                    استيراد الأسعار

                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>