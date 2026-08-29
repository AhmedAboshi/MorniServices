<?php

session_start();

include('../include/connected.php');


// ========================
// استقبال البيانات
// ========================

$driver_id = intval($_GET['driver_id'] ?? 0);
$type = $_GET['type'] ?? '';

if($driver_id <= 0){
    die("رقم السائق غير صحيح");
}


// ========================
// جلب بيانات السائق
// ========================

$stmt = $con->prepare("
    SELECT id, name
    FROM drivers
    WHERE id = ?
");

$stmt->bind_param("i", $driver_id);

$stmt->execute();

$result = $stmt->get_result();

$driver = $result->fetch_assoc();


if(!$driver){
    die("السائق غير موجود");
}



// ========================
// رفع المستند
// ========================

$message = "";


if(isset($_POST['upload'])){


    $document_type = $_POST['document_type'] ?? '';

$document_number = trim($_POST['document_number'] ?? '');
$issue_date = !empty($_POST['issue_date']) ? $_POST['issue_date'] : null;
$expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
$notes = trim($_POST['notes'] ?? '');

    if(empty($document_type)){
        $message = "اختر نوع المستند";
    }
    elseif(!isset($_FILES['file']) || $_FILES['file']['error'] != 0){

        $message = "اختر ملف للرفع";

    }
    else{


        $allowed = [
            'pdf',
            'jpg',
            'jpeg',
            'png'
        ];


        $file_name = $_FILES['file']['name'];

        $tmp_name = $_FILES['file']['tmp_name'];


        $ext = strtolower(
            pathinfo($file_name, PATHINFO_EXTENSION)
        );


        if(!in_array($ext,$allowed)){

            $message = "نوع الملف غير مسموح";

        }
        else{


            // مجلد السائق

            $folder = "../uploads/drivers/".$driver_id."/";


            if(!is_dir($folder)){

                mkdir($folder,0777,true);

            }



            // اسم جديد للملف

            $new_name =
            time()."_".$file_name;



            $path =
            $folder.$new_name;



            if(move_uploaded_file($tmp_name,$path)){


                $db_path =
                "uploads/drivers/".$driver_id."/".$new_name;



                // حفظ البيانات

               $insert = $con->prepare("
INSERT INTO driver_documents
(
    driver_id,
    document_type,
    document_number,
    issue_date,
    expiry_date,
    file_name,
    notes
)
VALUES (?,?,?,?,?,?,?)
");

$insert->bind_param(
    "issssss",
    $driver_id,
    $document_type,
    $document_number,
    $issue_date,
    $expiry_date,
    $new_name,
    $notes
);

$insert->execute();

                $message = "تم رفع المستند بنجاح";


            }
            else{

                $message = "حدث خطأ أثناء رفع الملف";

            }


        }


    }


}


?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>
رفع مستند للسائق
</title>


<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


<style>

body{
    background:#f5f6fa;
}

.card{

    max-width:600px;
    margin:40px auto;
    border-radius:15px;

}

</style>

</head>


<body>


<div class="card shadow">


<div class="card-header bg-primary text-white">

⬆ رفع مستند للسائق

</div>


<div class="card-body">


<h5>
السائق:
<?= htmlspecialchars($driver['name']) ?>
</h5>


<?php if($message): ?>

<div class="alert alert-info">
<?= $message ?>
</div>

<?php endif; ?>



<form method="post" enctype="multipart/form-data">


<div class="mb-3">

<label class="form-label">
نوع المستند
</label>


<select name="document_type" class="form-control">


<option value="">
اختر النوع
</option>


<option value="license">
رخصة القيادة
</option>


<option value="iqama">
الإقامة
</option>


<option value="driver_card">
بطاقة السائق
</option>


<option value="contract">
العقد
</option>


<option value="other">
أخرى
</option>


</select>


</div>
<div class="mb-3">
    <label class="form-label">رقم المستند</label>
    <input type="text" name="document_number" class="form-control">
</div>

<div class="mb-3">
    <label class="form-label">تاريخ الإصدار</label>
    <input type="date" name="issue_date" class="form-control">
</div>

<div class="mb-3">
    <label class="form-label">تاريخ الانتهاء</label>
    <input type="date" name="expiry_date" class="form-control">
</div>

<div class="mb-3">
    <label class="form-label">ملاحظات</label>
    <textarea name="notes" class="form-control" rows="3"></textarea>
</div>


<div class="mb-3">

<label class="form-label">
الملف
</label>


<input 
type="file"
name="file"
class="form-control"
>


</div>



<button 
class="btn btn-success"
name="upload">

رفع المستند

</button>


<a href="driver_profile.php?id=<?= $driver_id ?>" 
class="btn btn-secondary">

رجوع

</a>



</form>


</div>


</div>


</body>

</html>