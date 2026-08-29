<?php
include('../include/connected.php');

if(!isset($_GET['id'])){
    die("رقم المستند غير موجود");
}

$id = (int)$_GET['id'];

$result = mysqli_query($con,"
SELECT *
FROM vehicle_documents
WHERE id='$id'
");

if(mysqli_num_rows($result)==0){
    die("المستند غير موجود");
}

$document = mysqli_fetch_assoc($result);



if(isset($_POST['replace_file'])){


    if(isset($_FILES['new_file']) && $_FILES['new_file']['error'] == 0){


        // معلومات الملف الجديد

        $file_tmp  = $_FILES['new_file']['tmp_name'];
        $file_name = $_FILES['new_file']['name'];


        // امتدادات مسموحة

        $allowed = [
            'pdf',
            'jpg',
            'jpeg',
            'png'
        ];


        $extension = strtolower(
            pathinfo($file_name, PATHINFO_EXTENSION)
        );


        if(!in_array($extension,$allowed)){

            die("نوع الملف غير مسموح");

        }



        // إنشاء اسم جديد للملف

        $new_file_name = time().'_'.$file_name;



        // مجلد حفظ المستندات

        $upload_dir = "../uploads/documents/";



        // إنشاء المجلد إذا لم يكن موجود

        if(!is_dir($upload_dir)){

            mkdir($upload_dir,0777,true);

        }



        $new_path = $upload_dir.$new_file_name;



        // رفع الملف

        if(move_uploaded_file($file_tmp,$new_path)){



            // حذف الملف القديم

            if(!empty($document['file_path'])){


                $old_file = "../".$document['file_path'];


                if(file_exists($old_file)){

                    unlink($old_file);

                }

            }



            // حفظ المسار بدون ../

            $db_path = "uploads/documents/".$new_file_name;



            mysqli_query($con,"
            
            UPDATE vehicle_documents
            SET
            file_name='$new_file_name',
            file_path='$db_path'
            WHERE id='$id'

            ");



            echo "
<script>
alert('تم استبدال المستند بنجاح');
window.location='../admin/fleet_details.php?id=".$document['car_id']."';
</script>
";

exit;


        }else{


            die('فشل رفع الملف');

        }



    }else{


        die('لم يتم اختيار ملف');

    }



}


// جلب بيانات المركبة المرتبطة بالمستند

$car_id = $document['car_id'];

$car_result = mysqli_query($con,"
SELECT *
FROM fleet
WHERE id='$car_id'
");

$car = mysqli_fetch_assoc($car_result);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">
<title>استبدال المستند</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

</head>

<body class="bg-light">


<div class="container mt-5">


<div class="card shadow">

<div class="card-header bg-primary text-white">

<h5 class="mb-0">
<i class="bi bi-file-earmark-arrow-up"></i>
استبدال المستند
</h5>

</div>


<div class="card-body">


<div class="row">


<div class="col-md-6">


<h6 class="text-primary mb-3">
بيانات المستند الحالي
</h6>


<table class="table table-bordered">


<tr>
<th width="40%">نوع المستند</th>
<td>
<?= htmlspecialchars($document['document_type']); ?>
</td>
</tr>


<tr>
<th>رقم المستند</th>
<td>
<?= htmlspecialchars($document['document_number']); ?>
</td>
</tr>


<tr>
<th>تاريخ الإصدار</th>
<td>
<?= $document['issue_date']; ?>
</td>
</tr>


<tr>
<th>تاريخ الانتهاء</th>
<td>
<?= $document['expiry_date']; ?>
</td>
</tr>


<tr>
<th>المركبة</th>
<td>
<?= htmlspecialchars($car['plate'] ?? ''); ?>
</td>
</tr>


</table>


</div>



<div class="col-md-6">


<h6 class="text-success mb-3">
الملف الحالي
</h6>


<?php if(!empty($document['file_path'])): ?>


<a href="<?= '../'.$document['file_path']; ?>" 
target="_blank"
class="btn btn-outline-primary">

<i class="bi bi-eye"></i>
عرض الملف الحالي

</a>


<?php else: ?>


<div class="alert alert-warning">
لا يوجد ملف مرفوع لهذا المستند
</div>


<?php endif; ?>


</div>


</div>


<hr>


<form method="POST" 
      enctype="multipart/form-data">


<div class="card border-success">


<div class="card-header bg-success text-white">

<i class="bi bi-upload"></i>

رفع الملف الجديد

</div>


<div class="card-body">


<div class="mb-3">

<label class="form-label">
اختر الملف الجديد
</label>


<input type="file"
       name="new_file"
       class="form-control"
       required>

</div>


<div class="text-center">

<button type="submit"
        name="replace_file"
        class="btn btn-success">

<i class="bi bi-arrow-repeat"></i>

استبدال المستند

</button>

</div>


</div>


</div>


</form>


</div>

</div>


</div>


</body>

</html>