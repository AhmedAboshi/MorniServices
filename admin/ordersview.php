<?php
include('../include/connected.php');

/* =========================
   🗑️ حذف مباشر
========================= */
if(isset($_GET['delete'])){
    $id = (int) $_GET['delete'];

    mysqli_query($con, "DELETE FROM orders WHERE id=$id");

    header("Location: ordersview.php");
    exit;
}

/* =========================
   🔍 بحث + فلتر
========================= */
$search = mysqli_real_escape_string($con, $_GET['search'] ?? '');
$status = mysqli_real_escape_string($con, $_GET['status'] ?? '');

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

/* =========================
   📦 جلب البيانات
========================= */
$query = "SELECT * FROM orders $where ORDER BY id DESC";
$result = mysqli_query($con, $query);
?>



<div class="container mt-4">

<h3>🚚 لوحة إدارة الطلبات</h3>

<!-- 🔍 البحث -->
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

<!-- 📋 الجدول -->
<table class="table table-bordered table-hover text-center">

<tr class="table-dark">
<th>#</th>
<th>العميل</th>
<th>الجوال</th>
<th>من</th>
<th>إلى</th>
<th>السعر</th>
<th>الحالة</th>
<th>التاريخ</th>
<th>إجراءات</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<?php
$status = $row['status'];

$statusClass = match($status) {
    'pending'   => 'warning',
    'assigned'  => 'primary',
    'done'      => 'success',
    'cancelled' => 'danger',
    default     => 'secondary'
};

$statusText = match($status) {
    'pending'   => '⏳ قيد الانتظار',
    'assigned'  => '🚚 تم التعيين',
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

<td><?= $row['created_at'] ?></td>

<td>

<!-- 🔍 تفاصيل -->
<a href="order_details.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">
تفاصيل
</a>

<!-- ✏️ تعديل -->
<a href="edit_order.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">
تعديل
</a>

<!-- 🗑️ حذف -->
<a href="ordersview.php?delete=<?= $row['id'] ?>" 
class="btn btn-danger btn-sm"
onclick="return confirm('هل أنت متأكد من الحذف؟')">
حذف
</a>

</td>

</tr>

<?php } ?>

</table>

</div>