<?php
include('../include/connected.php');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=orders.xls");
header("Pragma: no-cache");
header("Expires: 0");

$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "
SELECT * FROM orders
WHERE full_name LIKE '%$search%'
ORDER BY id DESC
";

$result = mysqli_query($con, $query);

echo "<table border='1'>";
echo "<tr>
<th>ID</th>
<th>الاسم</th>
<th>الهاتف</th>
<th>المدينة</th>
<th>الإجمالي</th>
<th>الحالة</th>
</tr>";

while($row = mysqli_fetch_assoc($result)){

    // حساب الإجمالي من order_details
    $order_id = $row['id'];

    $totalQ = mysqli_query($con,"
        SELECT SUM(quantity * price) AS total 
        FROM order_details 
        WHERE order_id='$order_id'
    ");

    $totalRow = mysqli_fetch_assoc($totalQ);
    $total = $totalRow['total'] ?? 0;

    echo "<tr>
    <td>{$row['id']}</td>
    <td>{$row['full_name']}</td>
    <td>{$row['phone']}</td>
    <td>{$row['city']}</td>
    <td>{$total}</td>
    <td>{$row['status']}</td>
    </tr>";
}

echo "</table>";
?>