<?php
include('../include/connected.php');

/* =========================
   🔍 الفلاتر
========================= */
$from   = $_GET['from']   ?? '';
$to     = $_GET['to']     ?? '';
$car_id = $_GET['car_id'] ?? '';
$driver = $_GET['driver'] ?? '';

$where = [];

if($from && $to){
    $where[] = "change_date BETWEEN '$from' AND '$to'";
}
if($car_id){
    $where[] = "car_id LIKE '%$car_id%'";
}
if($driver){
    $where[] = "driver LIKE '%$driver%'";
}

$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";
$query = "SELECT * FROM oil_changes $where_sql ORDER BY change_date DESC";

/* =========================
   📥 تصدير Excel
========================= */
if(isset($_GET['export'])){

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=oil_report.xls");

    $res = mysqli_query($con, $query);

    echo "<table border='1'>
    <tr>
    <th>السيارة</th>
    <th>المزود</th>
    <th>النوع</th>
    <th>التاريخ</th>
    <th>العداد</th>
    <th>التكلفة</th>
    <th>ملاحظات</th>
    </tr>";

    $total = 0;

    while($r = mysqli_fetch_assoc($res)){
        $total += $r['cost'];

        echo "<tr>
        <td>{$r['car_id']}</td>
        <td>{$r['driver']}</td>
        <td>{$r['oil_type']}</td>
        <td>{$r['change_date']}</td>
        <td>{$r['km_change']}</td>
        <td>{$r['cost']}</td>
        <td>{$r['notes']}</td>
        </tr>";
    }

    echo "<tr>
    <td colspan='5'>الإجمالي</td>
    <td colspan='2'>$total</td>
    </tr>";

    echo "</table>";
    exit();
}

/* =========================
   📊 عرض البيانات
========================= */
$result = mysqli_query($con, $query);
$total_cost = 0;
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تقرير الزيت</title>

<style>
body{font-family:Arial;background:#f4f6f9;}

form{
    background:#fff;
    padding:15px;
    width:90%;
    margin:20px auto;
    border-radius:10px;
}

table{
    width:90%;
    margin:auto;
    background:#fff;
    border-collapse:collapse;
}

th,td{
    padding:10px;
    border:1px solid #ddd;
    text-align:center;
}

th{background:#28a745;color:#fff;}

.total{
    text-align:center;
    font-size:20px;
    margin:20px;
}

.btns{
    text-align:center;
    margin:10px;
}

button{
    padding:8px 15px;
    margin:5px;
}
</style>

</head>
<body>

<!-- =========================
     🔍 الفورم
========================= -->
<form method="get">

من:
<input type="date" name="from" value="<?php echo $from; ?>">

إلى:
<input type="date" name="to" value="<?php echo $to; ?>">

<input type="text" name="car_id" placeholder="السيارة" value="<?php echo $car_id; ?>">

<input type="text" name="driver" placeholder="المزود" value="<?php echo $driver; ?>">

<button type="submit">عرض</button>

</form>

<!-- =========================
     🖨️ أزرار
========================= -->
<div class="btns">

<button onclick="window.print()">🖨️ طباعة</button>

<form method="get" style="display:inline;">
    <input type="hidden" name="from" value="<?php echo $from; ?>">
    <input type="hidden" name="to" value="<?php echo $to; ?>">
    <input type="hidden" name="car_id" value="<?php echo $car_id; ?>">
    <input type="hidden" name="driver" value="<?php echo $driver; ?>">
    <button name="export" value="1">📥 Excel</button>
</form>

</div>

<!-- =========================
     📊 الجدول
========================= -->
<table>
<tr>
<th>السيارة</th>
<th>المزود</th>
<th>نوع الزيت</th>
<th>التاريخ</th>
<th>العداد</th>
<th>التكلفة</th>
<th>ملاحظات</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){

$total_cost += $row['cost'];

?>
<tr>
<td><?php echo $row['car_id']; ?></td>
<td><?php echo $row['driver']; ?></td>
<td><?php echo $row['oil_type']; ?></td>
<td><?php echo $row['change_date']; ?></td>
<td><?php echo $row['km_change']; ?></td>
<td><?php echo $row['cost']; ?></td>
<td><?php echo $row['notes']; ?></td>
</tr>
<?php } ?>

</table>

<!-- =========================
     💰 الإجمالي
========================= -->
<div class="total">
💰 إجمالي التكلفة: <strong><?php echo $total_cost; ?></strong>
</div>

</body>
</html>