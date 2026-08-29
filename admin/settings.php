<?php
include('../include/core.php');
include('../include/connected.php');
include('../include/settings.php');

/* =========================
   حفظ الإعدادات
========================= */

if(isset($_POST['save'])){

    updateSetting('system_name',$_POST['system_name']);
    updateSetting('company_name',$_POST['company_name']);
    updateSetting('company_phone',$_POST['company_phone']);
    updateSetting('company_email',$_POST['company_email']);
    updateSetting('company_website',$_POST['company_website']);
    updateSetting('company_address',$_POST['company_address']);
    updateSetting('currency',$_POST['currency']);
    updateSetting('default_language',$_POST['default_language']);
    updateSetting('default_theme',$_POST['default_theme']);
    updateSetting('tax_percent',$_POST['tax_percent']);
    updateSetting('service_fee',$_POST['service_fee']);

    /*==================================
رفع شعار الشركة
==================================*/

if(
    isset($_FILES['company_logo']) &&
    $_FILES['company_logo']['error']==0
){

    $ext = strtolower(pathinfo(
        $_FILES['company_logo']['name'],
        PATHINFO_EXTENSION
    ));

    $allowed = ['png','jpg','jpeg','webp','svg'];

    if(in_array($ext,$allowed)){

        $logo = 'logo_'.time().'.'.$ext;

        move_uploaded_file(
            $_FILES['company_logo']['tmp_name'],
            "../uploads/logo/".$logo
        );

        updateSetting(
            'company_logo',
            $logo
        );

    }

}

/*==================================
رفع Favicon
==================================*/

if(
    isset($_FILES['company_favicon']) &&
    $_FILES['company_favicon']['error']==0
){

    $ext = strtolower(pathinfo(
        $_FILES['company_favicon']['name'],
        PATHINFO_EXTENSION
    ));

    $allowed = ['png','ico'];

    if(in_array($ext,$allowed)){

        $fav = 'favicon.'.$ext;

        move_uploaded_file(
            $_FILES['company_favicon']['tmp_name'],
            "../uploads/logo/".$fav
        );

        updateSetting(
            'company_favicon',
            $fav
        );

    }

}

    header("Location: settings.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>"
dir="<?= $lang=='ar'?'rtl':'ltr' ?>">

<head>

<meta charset="utf-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>⚙️ <?= __('settings') ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet"
href="assets/dark-mode.css">

<style>

body{
    background:#f4f6f9;
}

.page-header{
    background:linear-gradient(135deg,#0d6efd,#0b5ed7);
    color:#fff;
    padding:25px;
    border-radius:18px;
    margin-bottom:25px;
    box-shadow:0 10px 30px rgba(0,0,0,.12);
}

.card{
    border:none;
    border-radius:18px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

body.dark-mode .card{
    background:#1f1f1f;
    color:#fff;
}

.form-control,
.form-select{
    border-radius:12px;
}

</style>

</head>

<body>

<div class="container py-4">

<!-- Header -->

<div class="page-header d-flex justify-content-between align-items-center">

<div>

<h2>
<i class="bi bi-gear-fill"></i>
إعدادات النظام
</h2>

<p class="mb-0">
إدارة بيانات المشروع والشركة
</p>

</div>

<div>

<button onclick="toggleDarkMode()"
class="btn btn-warning">

🌙

</button>

</div>

</div>

<?php if(isset($_GET['success'])){ ?>

<div class="alert alert-success">

✅ تم حفظ الإعدادات بنجاح

</div>

<?php } ?>

<ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">

<li class="nav-item">
<button class="nav-link active"
data-bs-toggle="tab"
data-bs-target="#company">
🏢 الشركة
</button>
</li>

<li class="nav-item">
<button class="nav-link"
data-bs-toggle="tab"
data-bs-target="#identity">
🖼️ الهوية
</button>
</li>

<li class="nav-item">
<button class="nav-link"
data-bs-toggle="tab"
data-bs-target="#transport">
🚚 النقل
</button>
</li>

<li class="nav-item">
<button class="nav-link"
data-bs-toggle="tab"
data-bs-target="#system">
⚙️ النظام
</button>
</li>

<li class="nav-item">
<button class="nav-link"
data-bs-toggle="tab"
data-bs-target="#backup">
💾 النسخ الاحتياطي
</button>
</li>

<li class="nav-item">
<button class="nav-link"
data-bs-toggle="tab"
data-bs-target="#about">
ℹ️ معلومات النظام
</button>
</li>

</ul>

    
<form method="POST" enctype="multipart/form-data">
<div class="tab-content">




<!-- الشركة -->

<div class="tab-pane fade show active"
id="company">

<div class="card">

<div class="card-header">

🏢 بيانات الشركة

</div>

<div class="card-body">

<div class="row">

<div class="col-md-6 mb-3">

<label>اسم الشركة</label>

<input
class="form-control"
name="company_name"
value="<?= setting('company_name') ?>">

</div>

<div class="col-md-6 mb-3">

<label>الهاتف</label>

<input
class="form-control"
name="company_phone"
value="<?= setting('company_phone') ?>">

</div>

<div class="col-md-6 mb-3">

<label>البريد الإلكتروني</label>

<input
class="form-control"
name="company_email"
value="<?= setting('company_email') ?>">

</div>

<div class="col-md-6 mb-3">

<label>الموقع</label>

<input
class="form-control"
name="company_website"
value="<?= setting('company_website') ?>">

</div>

<div class="col-md-12">

<label>العنوان</label>

<textarea
class="form-control"
rows="3"
name="company_address"><?= setting('company_address') ?></textarea>

</div>

</div>

</div>

</div>

</div>
<!-- الهوية -->

<div class="tab-pane fade" id="identity">

<div class="card">

<div class="card-header">
🖼️ الهوية البصرية
</div>

<div class="card-body">

<div class="row">

<div class="col-md-6">

<label class="form-label">
شعار الشركة
</label>

<input
type="file"
name="company_logo"
class="form-control">

<?php if(setting('company_logo')){ ?>

<div class="mt-3">

<img
src="../uploads/logo/<?= setting('company_logo') ?>"
style="max-height:120px"
class="img-thumbnail">

</div>

<?php } ?>

</div>

<div class="col-md-6">

<label class="form-label">
Favicon
</label>

<input
type="file"
name="company_favicon"
class="form-control">

<?php if(setting('company_favicon')){ ?>

<div class="mt-3">

<img
src="../uploads/logo/<?= setting('company_favicon') ?>"
style="max-height:64px"
class="img-thumbnail">

</div>

<?php } ?>

</div>

</div>

</div>

</div>

</div>
<!-- النظام -->

<div class="tab-pane fade"
id="system">

<div class="card">

<div class="card-header">

⚙️ إعدادات النظام

</div>

<div class="card-body">

<div class="row">

<div class="col-md-4">

<label>اسم النظام</label>

<input
class="form-control"
name="system_name"
value="<?= setting('system_name') ?>">

</div>

<div class="col-md-4">

<label>العملة</label>

<input
class="form-control"
name="currency"
value="<?= setting('currency') ?>">

</div>

<div class="col-md-4">

<label>اللغة</label>

<select
class="form-select"
name="default_language">

<option value="ar"
<?= setting('default_language')=='ar'?'selected':'' ?>>

العربية

</option>

<option value="en"
<?= setting('default_language')=='en'?'selected':'' ?>>

English

</option>

</select>

</div>

</div>

</div>

</div>

</div>
<div class="row mt-3">

<div class="col-md-4">

<label>الوضع الافتراضي</label>

<select
name="default_theme"
class="form-select">

<option value="light"
<?= setting('default_theme')=='light'?'selected':'' ?>>

فاتح

</option>

<option value="dark"
<?= setting('default_theme')=='dark'?'selected':'' ?>>

داكن

</option>

</select>

</div>

<div class="col-md-4">

<label>الضريبة %</label>

<input
type="number"
step="0.01"
name="tax_percent"
class="form-control"
value="<?= setting('tax_percent') ?>">

</div>

</div>
<!-- النقل -->

<div class="tab-pane fade"
id="transport">

<div class="card">

<div class="card-header">

🚚 إعدادات النقل

</div>

<div class="card-body">

<div class="row">

<div class="col-md-4">

<label>سعر الكيلومتر</label>

<input
type="number"
step="0.01"
class="form-control"
name="km_price"
value="<?= setting('km_price') ?>">

</div>

<div class="col-md-4">

<label>الحد الأدنى</label>

<input
type="number"
step="0.01"
class="form-control"
name="minimum_order"
value="<?= setting('minimum_order') ?>">

</div>

<div class="col-md-4">

<label>رسوم الخدمة</label>

<input
type="number"
step="0.01"
class="form-control"
name="service_fee"
value="<?= setting('service_fee') ?>">

</div>

</div>

</div>

</div>

</div>

</div>
<div class="tab-pane fade" id="backup">

<div class="card">

<div class="card-body text-center">

<h4>💾 النسخ الاحتياطي</h4>

<p>سيتم تطوير هذه الصفحة لاحقاً.</p>

</div>

</div>

</div>
<div class="tab-pane fade" id="about">

<div class="card">

<div class="card-body">

<h4>ℹ️ معلومات النظام</h4>

<table class="table">

<tr>

<td>PHP</td>

<td><?= PHP_VERSION ?></td>

</tr>

<tr>

<td>MySQL</td>

<td><?= mysqli_get_server_info($con) ?></td>

</tr>

<tr>

<td>اسم النظام</td>

<td><?= setting('system_name') ?></td>

</tr>

</table>

</div>

</div>

</div>
<div class="text-center mt-4">

<button
class="btn btn-success btn-lg"
name="save">

💾 حفظ الإعدادات

</button>

</div>

</form>
</div>

<script src="assets/dark-mode.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>