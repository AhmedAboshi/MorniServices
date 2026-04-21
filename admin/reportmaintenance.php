<?php
include('../include/connected.php');

$plate = $_GET['plate'] ?? '';
$driver = $_GET['driver'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';


$sql = "SELECT * FROM maintenance WHERE 1=1";

if ($plate != '') {
    $sql .= " AND plate_number LIKE '%$plate%'";
}

if ($driver != '') {
    $sql .= " AND vehicle_name LIKE '%$driver%'";
}



if ($from != '' && $to != '') {
    $sql .= " AND maintenance_date BETWEEN '$from' AND '$to'";
}

$result = mysqli_query($con, $sql);

$costs = [];
$labels = [];
$total_cost = 0;
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تقرير الصيانة</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { font-family: Arial; direction: rtl; }
table { width:100%; border-collapse: collapse; margin-top:15px;}
th,td { border:1px solid #ccc; padding:8px; text-align:center;}
button { margin:5px; padding:8px; }
.logo { width:120px; }
h2{
    display: block;
    text-algin: center;
    direction:center;
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


<img src="../img/logo.jpg" class="logo">
<h2>تقرير صيانة المركبات</h2>

<form method="GET">
    <input type="text" name="plate" placeholder="رقم اللوحة">
    <input type="text" name="driver" placeholder="المركبة">
    
    من: <input type="date" name="from">
    إلى: <input type="date" name="to">
    <button type="submit">بحث</button>
</form>

<button onclick="printPage()">🖨️ طباعة</button>
<button onclick="exportExcel()">📥 Excel</button>

<table id="tbl">
<tr>
<th>الورشة</th>
<th>اللوحة</th>
<th>المزود</th>
<th>الصيانة</th>
<th>التكلفة</th>
<th>التاريخ</th>
<th>واتساب</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)) {

    $labels[] = $row['vehicle_name'];
    $costs[] = $row['cost'];
     
     $total_cost += $row['cost'];
    $message = "تقرير صيانة المركبة: {$row['vehicle_name']} - التكلفة: {$row['cost']}";
    $url = "https://wa.me/966550186105?text=" . urlencode($message);
    
?>

<tr>
    <td><?= $row['vehicle_name'] ?></td>
    <td><?= $row['plate_number'] ?></td>
    <td><?= $row['driver'] ?></td>
    <td><?= $row['maintenance_type'] ?></td>
    <td><?= $row['cost'] ?></td>
    <td><?= $row['maintenance_date'] ?></td>
    <td>
        <a target="_blank" href="<?= $url ?>">إرسال واتساب</a>
    </td>
</tr>

<?php } ?>

</table>
<div style="margin-top:20px; font-size:18px; font-weight:bold; color:green;">
💰 إجمالي التكلفة: <?= number_format($total_cost,2) ?> ريال
</div>
<canvas id="chart"></canvas>

<script>
new Chart(document.getElementById('chart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'تكلفة الصيانة',
            data: <?= json_encode($costs) ?>,
            backgroundColor: 'green'
        }]
    }
});
</script>

</body>
</html>