<?php

include('../../include/connected.php');

session_start();

/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';


/* =========================
   حماية الدخول
========================= */

// if(!isset($_SESSION['admin_id'])){
//     header("Location: ../admin.php");
//     exit;
// }


/* =========================
   البحث
========================= */

$search = trim($_GET['search'] ?? '');

$where = "WHERE commission_rules.status <> 'deleted'";

$params = [];
$types = "";

if($search != ""){

    $where .= "
    AND (
        commission_rules.rule_name LIKE ?
        OR commission_rules.nationality LIKE ?
        OR commission_services.service_name LIKE ?
    )";

    $keyword = "%{$search}%";

    $params = [
        $keyword,
        $keyword,
        $keyword
    ];

    $types = "sss";
}


/* =========================
   جلب السياسات
========================= */

$sql = "
SELECT 
    commission_rules.*,
    commission_services.service_name

FROM commission_rules

LEFT JOIN commission_services

ON commission_rules.service_id = commission_services.id

$where

ORDER BY
priority ASC,
orders_from ASC,
id DESC
";

$stmt = $con->prepare($sql);

if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();


/* =========================
   إحصائيات الصفحة
========================= */

$totalRules = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM commission_rules
WHERE status <> 'deleted'
"))['total'];

$activeRules = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM commission_rules
WHERE status='active'
AND status <> 'deleted'
"))['total'];

$inactiveRules = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM commission_rules
WHERE status='inactive'
"))['total'];

?>
<!DOCTYPE html>

<html lang="<?= $lang ?>" dir="<?= $lang=='ar' ? 'rtl':'ltr' ?>">

<head>

<meta charset="UTF-8">

<title>

<?= $lang=='ar'
? 'سياسات العمولات'
: 'Commission Rules'
?>

</title>

<link rel="stylesheet" href="../assets/css/system.css?v=<?= time() ?>">

</head>

<body>

<div class="container">

<div class="page-header">

    <div>

        <h2 class="page-title">
            💰 سياسات العمولات
        </h2>

        <p class="page-subtitle">
            إدارة سياسات احتساب عمولات السائقين
        </p>

    </div>

    <div>

        <a href="commission_rule_add.php"
           class="btn btn-primary">

            ➕ إضافة سياسة

        </a>

    </div>

</div>

<!-- =========================
     بطاقات الإحصائيات
========================= -->

<div class="cards">

    <div class="stat-card stat-blue">

        <div class="stat-header">

            <div class="stat-icon">
                📋
            </div>

            <div class="stat-title">
                إجمالي السياسات
            </div>

        </div>

        <div class="stat-value">
            <?= number_format($totalRules) ?>
        </div>

    </div>


    <div class="stat-card stat-green">

        <div class="stat-header">

            <div class="stat-icon">
                ✅
            </div>

            <div class="stat-title">
                السياسات النشطة
            </div>

        </div>

        <div class="stat-value">
            <?= number_format($activeRules) ?>
        </div>

    </div>


    <div class="stat-card stat-red">

        <div class="stat-header">

            <div class="stat-icon">
                ⛔
            </div>

            <div class="stat-title">
                السياسات غير النشطة
            </div>

        </div>

        <div class="stat-value">
            <?= number_format($inactiveRules) ?>
        </div>

    </div>

</div>



<!-- =========================
     البحث
========================= -->

<form method="GET" class="search-box">

    <input
        type="text"
        name="search"
        class="form-control"
        placeholder="البحث باسم السياسة أو الجنسية أو نوع الخدمة..."
        value="<?= htmlspecialchars($search) ?>"
    >

    <button
        type="submit"
        class="btn btn-primary">

        🔍 بحث

    </button>

    <?php if($search != ""){ ?>

        <a href="commission_rules.php"
           class="btn btn-secondary">

            إلغاء

        </a>

    <?php } ?>

</form>



<!-- =========================
     جدول السياسات
========================= -->

<div class="table-responsive">

<table class="table">

<thead>

<tr>

    <th>#</th>

    <th>اسم السياسة</th>

    <th>الجنسية</th>

    <th>نوع الخدمة</th>

    <th>عدد الطلبات</th>

    <th>العمولة</th>

    <th>المكافأة</th>

    <th>الخصم</th>

    <th>الحالة</th>

    <th width="180">
        الإجراءات
    </th>

</tr>

</thead>

<tbody>

<?php

if($result->num_rows > 0){

while($row = $result->fetch_assoc()){

?>

<tr>

    <td>
        <?= $row['id'] ?>
    </td>

    <td>
        <strong>
            <?= htmlspecialchars($row['rule_name']) ?>
        </strong>
    </td>

    <td>
        <?= htmlspecialchars($row['nationality']) ?>
    </td>

    <td>
    <?= htmlspecialchars($row['service_name'] ?? '-') ?>
</td>

    <td>

        <?= $row['orders_from'] ?>

        -

        <?= $row['orders_to'] ?>

    </td>

    <td>

        <?= number_format($row['commission_amount'],2) ?>

    </td>

    <td class="text-success">

        <?= number_format($row['bonus'],2) ?>

    </td>

    <td class="text-danger">

        <?= number_format($row['deduction'],2) ?>

    </td>

    <td>

        <?php if($row['status']=="active"){ ?>

            <span class="badge badge-success">

                نشطة

            </span>

        <?php }else{ ?>

            <span class="badge badge-danger">

                موقفة

            </span>

        <?php } ?>

    </td>

    <td>

        <a href="commission_rule_edit.php?id=<?= $row['id'] ?>"
           class="btn btn-warning btn-sm">

            ✏️

        </a>

        <a href="commission_rule_toggle.php?id=<?= $row['id'] ?>"
           class="btn btn-info btn-sm">

            🔄

        </a>

        <a href="commission_rule_delete.php?id=<?= $row['id'] ?>"
   class="btn btn-danger btn-sm"
   onclick="return confirm('هل أنت متأكد من حذف هذه السياسة؟ يمكن استعادتها لاحقاً.')">

🗑 حذف

</a>

    </td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="10" style="text-align:center;padding:40px;">

لا توجد سياسات عمولات.

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</body>

</html>

<?php

$stmt->close();

$con->close();

?>