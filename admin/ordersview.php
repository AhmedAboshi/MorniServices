<?php
include('../include/core.php');
include('../include/connected.php');


/* 🗑️ حذف */
if(isset($_GET['delete'])){
    $id = (int) $_GET['delete'];
    mysqli_query($con, "DELETE FROM orders WHERE id=$id");
    header("Location: ordersview.php");
    exit;
}

/* 🚚 تعيين سائق + تغيير حالة */
if(isset($_POST['order_id'])){

    $order_id  = (int) $_POST['order_id'];
    $driver_id = (int) $_POST['driver_id'];
    $new_status = mysqli_real_escape_string($con, $_POST['status']);

    if($order_id > 0){

        mysqli_query($con, "
            UPDATE orders 
            SET driver_id = $driver_id,
                status = '$new_status'
            WHERE id = $order_id
        ");
    }

    header("Location: ordersview.php?success=1");
    exit;
}

/* 🔍 بحث */
$search = mysqli_real_escape_string($con, $_GET['search'] ?? '');
$filter_status = mysqli_real_escape_string($con, $_GET['status'] ?? '');

$where = "WHERE 1";

if ($search != '') {
   $where .= " AND (orders.full_name LIKE '%$search%' 
            OR orders.phone LIKE '%$search%'
            OR orders.from_city LIKE '%$search%'
            OR orders.to_city LIKE '%$search%')";
}

if ($filter_status != '') {
    $where .= " AND orders.status='$filter_status'";
}

/* 📦 جلب الطلبات */
$query = "
SELECT orders.*, drivers.name AS driver_name
FROM orders
LEFT JOIN drivers ON drivers.id = orders.driver_id
$where
ORDER BY orders.id DESC
";

$result = mysqli_query($con, $query);

/* 🚚 السائقين */
$drivers_result = mysqli_query($con, "SELECT * FROM drivers");
$drivers_list = [];
while($d = mysqli_fetch_assoc($drivers_result)){
    $drivers_list[] = $d;
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">

<title><?= __('title') ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background: #f7f7f7;
    direction: rtl;
}
.table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
}
.driver-form {
    display: flex;
    gap: 5px;
}
</style>

</head>

<body>

<div class="container mt-4">

<h3 class="mb-3">🚚 لوحة إدارة الطلبات</h3>
<a href="?lang=ar" class="btn btn-primary btn-sm">العربية</a>
<a href="?lang=en" class="btn btn-dark btn-sm">English</a>
<?php if(isset($_GET['success'])): ?>
<div class="alert alert-success">✅ تم التحديث بنجاح</div>
<?php endif; ?>

<!-- 🔍 البحث -->
<form method="GET" class="row mb-3">

<div class="col-md-6">
<input type="text" name="search" class="form-control"
placeholder="بحث بالاسم / الجوال / المدينة"
value="<?= htmlspecialchars($search) ?>">
</div>

<div class="col-md-3">
<select name="status" class="form-select">
<option value="">كل الحالات</option>
<option value="pending"   <?= $filter_status=='pending'?'selected':'' ?>>قيد الانتظار</option>
<option value="assigned"  <?= $filter_status=='assigned'?'selected':'' ?>>تم التعيين</option>
<option value="done"      <?= $filter_status=='done'?'selected':'' ?>>مكتمل</option>
<option value="cancelled" <?= $filter_status=='cancelled'?'selected':'' ?>>ملغي</option>
</select>
</div>

<div class="col-md-3">
<button class="btn btn-primary w-100">بحث</button>
</div>

</form>

<!-- 📋 الجدول -->
<table class="table table-bordered table-hover text-center align-middle">

<thead class="table-dark">
<tr>
<th>#</th>
<th>العميل</th>
<th>الجوال</th>
<th>من</th>
<th>إلى</th>
<th>السعر</th>
<th>الحالة</th>
<th>السائق</th>
<th>التاريخ</th>
<th>إجراءات</th>
<th>تحديث</th>
</tr>
</thead>

<tbody>

<?php while($row = mysqli_fetch_assoc($result)){ 

$status = $row['status'];

$statusClass = match($status) {
    'pending'   => 'warning',
    'assigned'  => 'primary',
    'done'      => 'success',
    'cancelled' => 'danger',
    default     => 'secondary'
};

$statusText = match($status) {
    'pending'   => '⏳ انتظار',
    'assigned'  => '🚚 معين',
    'done'      => '✅ مكتمل',
    'cancelled' => '❌ ملغي',
    default     => $status
};
?>

<tr>

<td><?= $row['id'] ?></td>
<td><?= $row['full_name'] ?></td>
<td><?= $row['phone'] ?></td>
<td><?= $row['from_city'] ?></td>
<td><?= $row['to_city'] ?></td>
<td><?= $row['price'] ?> ريال</td>

<td>
<span class="badge bg-<?= $statusClass ?>">
<?= $statusText ?>
</span>
</td>

<td>
<?= $row['driver_name'] ?? '<span class="text-muted">غير محدد</span>' ?>
</td>

<td><?= $row['created_at'] ?></td>

<td>
<a href="order_details.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">تفاصيل</a>
<a href="edit_order.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">تعديل</a>
<a href="ordersview.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
onclick="return confirm('هل أنت متأكد؟')">حذف</a>
</td>

<td>

<form action="ordersview.php" method="POST" class="driver-form">

<input type="hidden" name="order_id" value="<?= $row['id'] ?>">

<select name="driver_id" class="form-select form-select-sm">
<option value="">🚚 سائق</option>
<?php foreach($drivers_list as $d): ?>
<option value="<?= $d['id'] ?>"
<?= ($row['driver_id'] == $d['id']) ? 'selected' : '' ?>>
<?= $d['name'] ?>
</option>
<?php endforeach; ?>
</select>

<select name="status" class="form-select form-select-sm">
<option value="pending"   <?= $row['status']=='pending'?'selected':'' ?>>⏳</option>
<option value="assigned"  <?= $row['status']=='assigned'?'selected':'' ?>>🚚</option>
<option value="done"      <?= $row['status']=='done'?'selected':'' ?>>✅</option>
<option value="cancelled" <?= $row['status']=='cancelled'?'selected':'' ?>>❌</option>
</select>

<button class="btn btn-success btn-sm">✔</button>

</form>

</td>

</tr>

<?php } ?>

</tbody>
</table>

</div>

</body>
</html>