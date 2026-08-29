<?php

session_start();

include('../include/connected.php');

$id = intval($_GET['id'] ?? 0);

if($id <= 0){
    die("رقم المستند غير صحيح");
}


/*==================================
جلب بيانات المستند والسائق
==================================*/

$sql = "
SELECT
    dd.*,
    d.name AS driver_name
FROM driver_documents dd
INNER JOIN drivers d
ON d.id = dd.driver_id
WHERE dd.id = ?
LIMIT 1
";

$stmt = $con->prepare($sql);
$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();
$doc = $result->fetch_assoc();

if(!$doc){
    die("المستند غير موجود");
}


/*==================================
مسار الملف
==================================*/

$file = "../uploads/drivers/".$doc['driver_id']."/".$doc['file_name'];

$file_exists = file_exists($file);

$extension = strtolower(pathinfo($doc['file_name'],PATHINFO_EXTENSION));

if(isset($_POST['save'])){

    $document_number = trim($_POST['document_number']);
    $issue_date = !empty($_POST['issue_date']) ? $_POST['issue_date'] : NULL;
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;
    $notes = trim($_POST['notes']);

    // في هذه المرحلة سنحدث البيانات فقط
    // واستبدال الملف سنضيفه في الخطوة التالية

    /*==================================
استبدال الملف (اختياري)
==================================*/

$new_file_name = $doc['file_name'];

if(isset($_FILES['new_file']) && $_FILES['new_file']['error'] == 0){

    $allowed = ['pdf','jpg','jpeg','png'];

    $extension = strtolower(pathinfo($_FILES['new_file']['name'], PATHINFO_EXTENSION));

    if(!in_array($extension,$allowed)){

        die("نوع الملف غير مسموح.");

    }

    $folder = "../uploads/drivers/".$doc['driver_id']."/";

    if(!is_dir($folder)){
        mkdir($folder,0777,true);
    }

    $new_file_name = time().'_'.basename($_FILES['new_file']['name']);

    $new_path = $folder.$new_file_name;

    if(move_uploaded_file($_FILES['new_file']['tmp_name'],$new_path)){

        // حذف الملف القديم
        $old_file = $folder.$doc['file_name'];

        if(file_exists($old_file)){
            unlink($old_file);
        }

    }else{

        die("فشل رفع الملف الجديد.");

    }

}

   $update = $con->prepare("
UPDATE driver_documents
SET
document_number=?,
issue_date=?,
expiry_date=?,
notes=?,
file_name=?
WHERE id=?
");

$update->bind_param(
"sssssi",
$document_number,
$issue_date,
$expiry_date,
$notes,
$new_file_name,
$id
);

    if($update->execute()){

        header("Location: driver_profile.php?id=".$doc['driver_id']."&msg=updated");
        exit;

    }else{

        echo "<div class='alert alert-danger'>حدث خطأ أثناء الحفظ.</div>";

    }

}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>تعديل مستند السائق</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f5f6fa;
}

.card{
    max-width:900px;
    margin:30px auto;
    border-radius:15px;
}

.preview{
    max-width:250px;
    border-radius:10px;
    border:1px solid #ddd;
}

</style>

</head>

<body>

<div class="card shadow">

<div class="card-header bg-warning">

<h4 class="mb-0">
♻️ تعديل مستند السائق
</h4>

</div>

<div class="card-body">

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">
اسم السائق
</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($doc['driver_name']) ?>"
readonly>

</div>


<div class="mb-3">

<label class="form-label">
نوع المستند
</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($doc['document_type']) ?>"
readonly>

</div>

</div>



<div class="col-md-6 text-center">

<?php

if($file_exists){

    if(in_array($extension,['jpg','jpeg','png'])){

        ?>

        <img
        src="<?= $file ?>"
        class="preview">

        <?php

    }else{

        echo "<h1>📄</h1>";
        echo "<p>".$doc['file_name']."</p>";

    }

}else{

    echo "<div class='alert alert-danger'>الملف غير موجود</div>";

}

?>

</div>

</div>
<form method="post" enctype="multipart/form-data">

<div class="row">

<div class="col-md-6">

<div class="mb-3">
<label class="form-label">رقم المستند</label>
<input
type="text"
name="document_number"
class="form-control"
value="<?= htmlspecialchars($doc['document_number']) ?>">
</div>

<div class="mb-3">
<label class="form-label">تاريخ الإصدار</label>
<input
type="date"
name="issue_date"
class="form-control"
value="<?= $doc['issue_date'] ?>">
</div>

<div class="mb-3">
<label class="form-label">تاريخ الانتهاء</label>
<input
type="date"
name="expiry_date"
class="form-control"
value="<?= $doc['expiry_date'] ?>">
</div>

</div>


<div class="col-md-6">

<div class="mb-3">
<label class="form-label">
استبدال الملف (اختياري)
</label>

<input
type="file"
name="new_file"
class="form-control">

<small class="text-muted">
اتركه فارغاً إذا كنت تريد تعديل البيانات فقط.
</small>

</div>

<div class="mb-3">

<label class="form-label">
ملاحظات
</label>

<textarea
name="notes"
class="form-control"
rows="6"><?= htmlspecialchars($doc['notes']) ?></textarea>

</div>

</div>

</div>

<hr>

<button
type="submit"
name="save"
class="btn btn-success">

💾 حفظ التعديلات

</button>

<a
href="driver_profile.php?id=<?= $doc['driver_id'] ?>"
class="btn btn-secondary">

رجوع

</a>

</form>