<?php
session_start();

include('../include/core.php');
include('../include/connected.php');
include('../include/settings.php');

/*==================================
        حماية الصفحة
==================================*/

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit();
}

/*==================================
        التحقق من ID
==================================*/

if(
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
){
    die("رقم المدير غير صحيح");
}

$id = intval($_GET['id']);

/*==================================
        جلب بيانات المدير
==================================*/

$stmt = $con->prepare("
SELECT *
FROM admin
WHERE id=?
LIMIT 1
");

$stmt->bind_param("i",$id);
$stmt->execute();

$admin = $stmt->get_result()->fetch_assoc();

if(!$admin){
    die("المدير غير موجود");
}
?>
<?php

$currentImage = "../uploads/logo/" . setting('company_logo');

if(
    !empty($admin['image']) &&
    file_exists("../uploads/admin/".$admin['image'])
){
    $currentImage =
    "../uploads/admin/".$admin['image'];
}

?>
<?php

if(isset($_POST['update'])){

    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];
    $status   = $_POST['status'];

    /*==============================
        التحقق من البريد
    ==============================*/

    $check = $con->prepare("
        SELECT id
        FROM admin
        WHERE email=?
        AND id<>?
    ");

    $check->bind_param("si",$email,$id);
    $check->execute();

    if($check->get_result()->num_rows){

        $msg = "البريد الإلكتروني مستخدم من مدير آخر";

    }else{

        /*==============================
            الصورة
        ==============================*/

        $image = $admin['image'];

        if(!empty($_FILES['image']['name'])){

            $folder = "../uploads/admin/";

            if(!is_dir($folder)){
                mkdir($folder,0777,true);
            }

            $ext = strtolower(pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION));

            $allow = ['jpg','jpeg','png','gif','webp'];

            if(in_array($ext,$allow)){

                $newImage = time().rand(1000,9999).".".$ext;

                if(move_uploaded_file($_FILES['image']['tmp_name'],$folder.$newImage)){

                    /* حذف الصورة القديمة */

                    if(
                        !empty($admin['image']) &&
                        file_exists($folder.$admin['image'])
                    ){
                        @unlink($folder.$admin['image']);
                    }

                    $image = $newImage;

                }

            }

        }

        /*==============================
            تحديث البيانات
        ==============================*/

        if(!empty($password)){

            $stmt = $con->prepare("
            UPDATE admin SET

                name=?,
                email=?,
                phone=?,
                password=?,
                image=?,
                role=?,
                status=?

            WHERE id=?
            ");

            $stmt->bind_param(

                "sssssssi",

                $name,
                $email,
                $phone,
                $password,
                $image,
                $role,
                $status,
                $id

            );

        }else{

            $stmt = $con->prepare("
            UPDATE admin SET

                name=?,
                email=?,
                phone=?,
                image=?,
                role=?,
                status=?

            WHERE id=?
            ");

            $stmt->bind_param(

                "ssssssi",

                $name,
                $email,
                $phone,
                $image,
                $role,
                $status,
                $id

            );

        }

        if($stmt->execute()){
$admin['image'] = $image;
            header("Location: adminview.php?updated=1");
            exit();

        }else{

            $msg = "حدث خطأ أثناء التحديث";

        }

    }

}
?>
<?php if(isset($msg)){ ?>

<div class="alert alert-danger">

<?= $msg ?>

</div>

<?php } ?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>Edit Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{background:#f4f6f9;}
.card{border:none;border-radius:12px;}
</style>
</head>

<body>

<div class="container py-4">

<div class="card shadow p-4">

<h3>✏️ تعديل الأدمن</h3>
<hr>

<form method="POST" enctype="multipart/form-data">

<div class="text-center mb-4">

    <img
        id="preview"
        src="<?= $currentImage ?>"
        class="rounded-circle shadow"
        style="
            width:170px;
            height:170px;
            object-fit:cover;
            border:4px solid #0d6efd;
            background:#fff;
            padding:4px;
        ">

    <br><br>

    <input
        type="file"
        name="image"
        id="image"
        class="form-control">

</div>


<div class="mb-3">

<label class="form-label">
اسم المدير
</label>

<input
type="text"
name="name"
class="form-control"
required
value="<?= htmlspecialchars($admin['name']) ?>">

</div>


<div class="mb-3">

<label class="form-label">
البريد الإلكتروني
</label>

<input
type="email"
name="email"
class="form-control"
required
value="<?= htmlspecialchars($admin['email']) ?>">

</div>


<div class="mb-3">

<label class="form-label">
رقم الجوال
</label>

<input
type="text"
name="phone"
class="form-control"
value="<?= htmlspecialchars($admin['phone']) ?>">

</div>


<div class="mb-3">

<label class="form-label">
كلمة المرور الجديدة
</label>

<input
type="password"
name="password"
class="form-control"
placeholder="اتركها فارغة إذا لا تريد تغييرها">

<small class="text-muted">

لن يتم تغيير كلمة المرور إذا تركت الحقل فارغاً

</small>

</div>


<div class="mb-3">

<label class="form-label">

الصلاحية

</label>

<select
name="role"
class="form-select">

<option
value="Super Admin"
<?= $admin['role']=="Super Admin"?"selected":"" ?>>
Super Admin
</option>

<option
value="Admin"
<?= $admin['role']=="Admin"?"selected":"" ?>>
Admin
</option>

<option
value="Supervisor"
<?= $admin['role']=="Supervisor"?"selected":"" ?>>
Supervisor
</option>

</select>

</div>


<div class="mb-4">

<label class="form-label">

الحالة

</label>

<select
name="status"
class="form-select">

<option
value="Active"
<?= $admin['status']=="Active"?"selected":"" ?>>
نشط
</option>

<option
value="Inactive"
<?= $admin['status']=="Inactive"?"selected":"" ?>>
موقوف
</option>

</select>

</div>


<div class="text-center">

<button
type="submit"
name="update"
class="btn btn-primary btn-lg">

💾 حفظ التعديلات

</button>

<a
href="adminview.php"
class="btn btn-secondary btn-lg">

↩ رجوع

</a>

</div>

</form>


</div>

</div>
<script>

const imageInput=document.getElementById("image");
const preview=document.getElementById("preview");

imageInput.addEventListener("change",function(){

    if(!this.files.length) return;

    const reader=new FileReader();

    reader.onload=function(e){

        preview.src=e.target.result;

    }

    reader.readAsDataURL(this.files[0]);

});

</script>
</body>
</html>