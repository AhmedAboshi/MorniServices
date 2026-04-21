<?php
include('../include/connected.php');

$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';
$car    = $_GET['car_id'] ?? '';
$driver = $_GET['driver'] ?? '';

$query = "SELECT * FROM tires WHERE 1";

if(!empty($from) && !empty($to)){
    $query .= " AND change_date BETWEEN '$from' AND '$to'";
}

if(!empty($car)){
    $query .= " AND car_id LIKE '%$car%'";
}

if(!empty($driver)){
    $query .= " AND driver LIKE '%$driver%'";
}

$query .= " ORDER BY change_date DESC";

$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تقرير الإطارات</title>

<style>
body { font-family: Arial; background:#f4f6f9; }
.container { width:90%; margin:auto; }
h2 { text-align:center; }

table {
    width:100%;
    background:white;
    border-collapse:collapse;
}

th, td {
    padding:10px;
    text-align:center;
    border-bottom:1px solid #ddd;
}

th {
    background:#007bff;
    color:white;
}

.filter {
    text-align:center;
    margin:20px;
}
</style>
<script>
function printPage(){ window.print(); }

function exportExcel(){
    let table = document.getElementById("tbl").outerHTML;
    let file = "data:application/vnd.ms-excel," + encodeURIComponent(table);
    let a = document.createElement("a");
    a.href = file;
    a.download = "report.xls";
    a.click();
}
</script>
</head>

<body>

<div class="container">

<h2>🛞 تقرير الإطارات</h2>

<div class="filter">
<form method="get">
    من: <input type="date" name="from" value="<?= $from ?>">
    إلى: <input type="date" name="to" value="<?= $to ?>">
    <input type="text" name="car_id" placeholder="لوحة السيارة" value="<?= $car ?>">
    <input type="text" name="driver" placeholder="المزود" value="<?= $driver ?>">
    <button type="submit">بحث</button>
    <button onclick="printPage()">🖨️ طباعة</button>
<button onclick="exportExcel()">📥 Excel</button>
</form>
</div>

<table dir="rtl">
<tr>
    <th>التاريخ</th>
    <th>لوحة السيارة</th>
    <th>المزود</th>
    <th>نوع الإطار</th>
    <th>التكلفة</th>
</tr>

<?php 
$total = 0;
while($row = mysqli_fetch_assoc($result)){ 
    $total += $row['cost'];
?>
<tr>
    <td><?= $row['change_date']; ?></td>
    <td><?= $row['car_id']; ?></td>
    <td><?= $row['driver']; ?></td>
    <td><?= $row['tire_type']; ?></td>
    <td><?= $row['cost']; ?> ريال</td>
</tr>
<?php } ?>

<tr>
    <td colspan="4"><b>الإجمالي</b></td>
    <td><b><?= $total ?> ريال</b></td>
</tr>

</table>

</div>

</body>
</html>