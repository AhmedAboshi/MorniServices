<?php
session_start();
include('../include/core.php');
include('../include/connected.php');

/* 📌 التحقق من ID */
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die(t('not_found'));
}

/* 📊 جلب البيانات */
$stmt = $con->prepare("SELECT * FROM fleet WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die(t('not_found'));
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<title><?= t('fleet_details') ?></title>

<style>
body{
    font-family:'Cairo';
    background:#f4f6f9;
}

/* 📦 كرت */
.card{
    width:60%;
    margin:40px auto;
    background:#fff;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
    overflow:hidden;
}

/* 🖼️ صورة */
.card img{
    width:100%;
    height:300px;
    object-fit:cover;
}

/* 📄 محتوى */
.card-content{
    padding:20px;
}

.card-content h2{
    margin-bottom:15px;
}

.info{
    display:flex;
    justify-content:space-between;
    padding:10px 0;
    border-bottom:1px solid #eee;
}

.back{
    display:block;
    width:200px;
    margin:20px auto;
    text-align:center;
    background:#3498db;
    color:#fff;
    padding:10px;
    border-radius:8px;
    text-decoration:none;
}
</style>

</head>

<body>

<a href="fleet.php" class="back"><?= t('back') ?></a>

<div class="card">

<img src="../fleetimg/img/<?= htmlspecialchars($row['imgfleet']) ?>">

<div class="card-content">

<h2><?= htmlspecialchars($row['driver']) ?></h2>

<div class="info">
<span><?= t('plate') ?></span>
<span><?= htmlspecialchars($row['plate']) ?></span>
</div>

<div class="info">
<span><?= t('type') ?></span>
<span><?= htmlspecialchars($row['typefleet']) ?></span>
</div>

<div class="info">
<span><?= t('class') ?></span>
<span><?= htmlspecialchars($row['classify']) ?></span>
</div>

<div class="info">
<span><?= t('model') ?></span>
<span><?= htmlspecialchars($row['model']) ?></span>
</div>

<div class="info">
<span><?= t('color') ?></span>
<span><?= htmlspecialchars($row['colorfleet']) ?></span>
</div>

<div class="info">
<span><?= t('work') ?></span>
<span><?= htmlspecialchars($row['work']) ?></span>
</div>

</div>
</div>

</body>
</html>