<?php
include('../include/connected.php');

/* =========================
   الفلاتر
========================= */
$search = mysqli_real_escape_string($con, $_GET['search'] ?? '');
$status = mysqli_real_escape_string($con, $_GET['status'] ?? '');

/* =========================
   جلب الطلبات
========================= */
$where = "WHERE 1";

if ($search != '') {
    $where .= " AND (full_name LIKE '%$search%' 
                OR phone LIKE '%$search%'
                OR from_city LIKE '%$search%'
                OR to_city LIKE '%$search%')";
}

if ($status != '') {
    $where .= " AND status='$status'";
}

$query = "
SELECT * FROM orders
$where
ORDER BY id DESC
";

$result = mysqli_query($con, $query);
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-4">

<h3>🚚 لوحة إدارة الطلبات</h3>

<!-- 🔍 بحث + فلتر -->
<form method="GET" class="row mb-3">

<div class="col-md-6">
<input type="text" name="search" class="form-control"
placeholder="بحث بالاسم / الجوال / المدينة"
value="<?= htmlspecialchars($search) ?>">
</div>

<div class="col-md-3">
<select name="status" class="form-control">
<option value="">كل الحالات</option>
<option value="pending">قيد الانتظار</option>
<option value="assigned">تم التعيين</option>
<option value="done">مكتمل</option>
<option value="cancelled">ملغي</option>
</select>
</div>

<div class="col-md-3">
<button class="btn btn-primary w-100">بحث</button>
</div>

</form>

<table class="table table-hover table-bordered text-center">

<tr class="table-dark">
<th>#</th>
<th>العميل</th>
<th>الجوال</th>
<th>من</th>
<th>إلى</th>
<th>النوع</th>
<th>السعر</th>
<th>الحالة</th>
<th>التاريخ</th>
<th>إجراءات</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<?php
$statusColor = match($row['status']) {
    'pending' => 'warning',
    'assigned' => 'primary',
    'done' => 'success',
    'cancelled' => 'danger',
    default => 'secondary'
};

$typeLabel = ($row['order_type'] == 'tow') ? '🚚 سطحة' : '🛒 سلة';
?>

<tr>

<td><?= $row['id'] ?></td>
<td><?= $row['full_name'] ?></td>
<td><?= $row['phone'] ?></td>

<td><?= $row['from_city'] ?? '-' ?></td>
<td><?= $row['to_city'] ?? '-' ?></td>

<td><?= $typeLabel ?></td>

<td><?= $row['price'] ?? 0 ?> ريال</td>

<td>
<span class="badge bg-<?= $statusColor ?>">
<?= $row['status'] ?>
</span>
</td>

<td><?= $row['created_at'] ?></td>

<td>

<!-- 🔍 تفاصيل -->
<a href="order_details.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">
تفاصيل
</a>

</td>

</tr>

<?php } ?>

</table>

</div>