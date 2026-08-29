<?php
session_start();

include('../include/connected.php');
include('../include/settings.php');

if(!isset($_SESSION['admin_id'])){
    header("Location: admin.php");
    exit();
}

$fleet_id = intval($_GET['car_id'] ?? 0);
$type = $_GET['type'] ?? '';

if($fleet_id <= 0){
    die("رقم المركبة غير صحيح");
}
if(isset($_POST['save_document'])){

    $document_type   = mysqli_real_escape_string($con,$_POST['document_type']);
    $document_number = mysqli_real_escape_string($con,$_POST['document_number']);
    $issue_date      = $_POST['issue_date'];
    $expiry_date     = $_POST['expiry_date'];
    $notes           = mysqli_real_escape_string($con,$_POST['notes']);

    $file_name = "";
    $file_path = "";

    if(isset($_FILES['document_file']) && $_FILES['document_file']['error']==0){

        $uploadDir = "../uploads/vehicle_documents/";

        if(!is_dir($uploadDir)){
            mkdir($uploadDir,0777,true);
        }

        $extension = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);

        $newName = time()."_".$fleet_id.".".$extension;

        move_uploaded_file(
            $_FILES['document_file']['tmp_name'],
            $uploadDir.$newName
        );

        $file_name = $_FILES['document_file']['name'];
        $file_path = "uploads/vehicle_documents/".$newName;
    }

    $sql = "INSERT INTO vehicle_documents
    (
        car_id,
        document_type,
        document_number,
        issue_date,
        expiry_date,
        file_name,
        file_path,
        notes
    )
    VALUES
    (
        '$fleet_id',
        '$document_type',
        '$document_number',
        '$issue_date',
        '$expiry_date',
        '$file_name',
        '$file_path',
        '$notes'
    )
    ON DUPLICATE KEY UPDATE

        document_number=VALUES(document_number),
        issue_date=VALUES(issue_date),
        expiry_date=VALUES(expiry_date),
        file_name=VALUES(file_name),
        file_path=VALUES(file_path),
        notes=VALUES(notes)
    ";

    mysqli_query($con,$sql);

    header("Location: fleet_details.php?id=".$fleet_id."#documents");
    exit();
}

?>
<div class="container mt-4">

    <div class="card shadow">

        <div class="card-header bg-primary text-white">

            <h4 class="mb-0">
                <i class="bi bi-file-earmark-plus"></i>
                إضافة مستند للمركبة
            </h4>

        </div>

        <div class="card-body">

<form method="POST"
      enctype="multipart/form-data">

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">
نوع المستند
</label>

<select
name="document_type"
class="form-select"
required>

<option value="استمارة المركبة" <?= $type=="استمارة المركبة" ? "selected" : "" ?>>
📄 استمارة المركبة
</option>

<option value="وثيقة التأمين" <?= $type=="وثيقة التأمين" ? "selected" : "" ?>>
🛡️ وثيقة التأمين
</option>

<option value="الفحص الدوري" <?= $type=="الفحص الدوري" ? "selected" : "" ?>>
🚘 الفحص الدوري
</option>

<option value="كرت التشغيل" <?= $type=="كرت التشغيل" ? "selected" : "" ?>>
🚛 كرت التشغيل
</option>

<option value="بطاقة السائق" <?= $type=="بطاقة السائق" ? "selected" : "" ?>>
🪪 بطاقة السائق
</option>
</div>

<div class="col-md-6 mb-3">

<label class="form-label">

رقم المستند

</label>

<input
type="text"
name="document_number"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

تاريخ الإصدار

</label>

<input
type="date"
name="issue_date"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">

تاريخ الانتهاء

</label>

<input
type="date"
name="expiry_date"
class="form-control">

</div>

<div class="col-md-12 mb-3">

<label class="form-label">

رفع الملف

</label>

<input
type="file"
name="document_file"
class="form-control"
accept=".pdf,.jpg,.jpeg,.png">

</div>

<div class="col-md-12 mb-3">

<label class="form-label">

ملاحظات

</label>

<textarea
name="notes"
class="form-control"
rows="4"></textarea>

</div>

</div>

<button
type="submit"
name="save_document"
class="btn btn-success">

<i class="bi bi-check-circle"></i>

حفظ المستند

</button>

<a
href="fleet_details.php?id=<?= $fleet_id ?>"
class="btn btn-secondary">

إلغاء

</a>

</form>

        </div>

    </div>

</div>