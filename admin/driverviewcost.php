<?php
include(__DIR__ . '/../include/connected.php');

/* =========================
   🔍 الفلاتر
========================= */
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$driver_id = $_GET['driver_id'] ?? '';

$driver_id = $driver_id ? (int)$driver_id : 0;

/* =========================
   شروط التاريخ
========================= */
$dateOil = ($from && $to) ? "AND o.change_date BETWEEN '$from' AND '$to'" : "";
$dateTire = ($from && $to) ? "AND t.change_date BETWEEN '$from' AND '$to'" : "";
$dateMaint = ($from && $to) ? "AND m.maintenance_date BETWEEN '$from' AND '$to'" : "";

/* =========================
   فلتر السائق
========================= */
$driverFilter = $driver_id ? "AND d.id = $driver_id" : "";

/* =========================
   📊 جلب السائقين
========================= */
$drivers = mysqli_query($con, "
SELECT 
d.id,
d.name,
d.plate_number,

COALESCE(SUM(o.cost),0) AS oil,
COALESCE(SUM(t.cost),0) AS tire,
COALESCE(SUM(m.cost),0) AS maint

FROM drivers d

LEFT JOIN oil_changes o ON o.driver_id = d.id $dateOil
LEFT JOIN tires t ON t.driver_id = d.id $dateTire
LEFT JOIN maintenance m ON m.driver_id = d.id $dateMaint

WHERE 1=1 $driverFilter

GROUP BY d.id
");

/* =========================
   أعلى سائق صرف 🔴
========================= */
$topQ = mysqli_query($con, "
SELECT 
d.id,
COALESCE(SUM(o.cost),0)+COALESCE(SUM(t.cost),0)+COALESCE(SUM(m.cost),0) AS total
FROM drivers d
LEFT JOIN oil_changes o ON o.driver_id = d.id
LEFT JOIN tires t ON t.driver_id = d.id
LEFT JOIN maintenance m ON m.driver_id = d.id
GROUP BY d.id
");

$max = 0;
$top_id = 0;

while($t = mysqli_fetch_assoc($topQ)){
    if($t['total'] > $max){
        $max = $t['total'];
        $top_id = $t['id'];
    }
}

/* =========================
   قائمة السائقين للبحث
========================= */
$allDrivers = mysqli_query($con, "SELECT id, name FROM drivers");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تقرير شامل</title>

<style>
body{font-family:Arial;background:#f4f6f9;margin:0}
.container{width:95%;margin:auto;padding:20px}

table{width:100%;background:#fff;border-collapse:collapse;margin-top:10px}
th,td{padding:10px;border:1px solid #ddd;text-align:center}
th{background:#007bff;color:#fff}

form{
    background:#fff;
    padding:10px;
    border-radius:8px;
    margin-bottom:10px;
}

button{
    padding:8px 12px;
    border:none;
    border-radius:5px;
    cursor:pointer;
    margin:3px;
}

.print{background:#28a745;color:#fff}
.excel{background:#17a2b8;color:#fff}
.details{background:#ffc107;color:#000}

.total-box{
    margin-top:15px;
    background:#fff;
    padding:10px;
    border-radius:8px;
}

@media print{
    button, form {display:none;}
}
</style>
</head>

<body>

<div class="container">

<h2>📊 تقرير التكاليف</h2>

<!-- =========================
   🔍 بحث
========================= -->
<form method="GET">

من:
<input type="date" name="from" value="<?= $from ?>">

إلى:
<input type="date" name="to" value="<?= $to ?>">

<select name="driver_id">
    <option value="">كل السائقين</option>
    <?php while($d = mysqli_fetch_assoc($allDrivers)){ ?>
        <option value="<?= $d['id'] ?>"
        <?= ($driver_id == $d['id']) ? 'selected' : '' ?>>
            <?= $d['name'] ?>
        </option>
    <?php } ?>
</select>

<button type="submit">بحث 🔍</button>

</form>

<!-- =========================
   🖨️ أدوات
========================= -->
<button class="print" onclick="window.print()">🖨️ طباعة</button>
<button class="excel" onclick="exportTable()">📥 Excel</button>

<!-- =========================
   📊 الجدول
========================= -->
<table id="tableData">

<tr>
    <th>السائق</th>
    <th>اللوحة</th>
    <th>الزيت</th>
    <th>الإطارات</th>
    <th>الصيانة</th>
    <th>الإجمالي</th>
    <th>تفاصيل</th>
</tr>

<?php 
$total_oil = 0;
$total_tire = 0;
$total_maint = 0;

while($d = mysqli_fetch_assoc($drivers)) {

$total = $d['oil'] + $d['tire'] + $d['maint'];

$total_oil += $d['oil'];
$total_tire += $d['tire'];
$total_maint += $d['maint'];

$style = ($d['id'] == $top_id)
? "background:#ffdddd;color:red;font-weight:bold;"
: "";

?>

<tr style="<?= $style ?>">

    <td><?= htmlspecialchars($d['name']) ?></td>
    <td><?= htmlspecialchars($d['plate_number']) ?></td>
    <td><?= number_format($d['oil'],2) ?></td>
    <td><?= number_format($d['tire'],2) ?></td>
    <td><?= number_format($d['maint'],2) ?></td>
    <td><b><?= number_format($total,2) ?></b></td>

    <td>
        <a class="details" href="drivercost.php?id=<?= $d['id'] ?>">
            تفاصيل
        </a>
    </td>

</tr>

<?php } ?>

</table>

<!-- =========================
   📊 الإجماليات
========================= -->
<div class="total-box">

<table width="100%">
<tr>
    <th>إجمالي الزيت</th>
    <th>إجمالي الإطارات</th>
    <th>إجمالي الصيانة</th>
    <th>الإجمالي الكلي</th>
</tr>

<tr>
    <td><?= number_format($total_oil,2) ?></td>
    <td><?= number_format($total_tire,2) ?></td>
    <td><?= number_format($total_maint,2) ?></td>
    <td style="color:green;font-weight:bold;">
        <?= number_format($total_oil + $total_tire + $total_maint,2) ?>
    </td>
</tr>

</table>

</div>

</div>

<!-- =========================
   📥 Excel
========================= -->
<script>
function exportTable(){
    let table = document.getElementById("tableData").outerHTML;
    let blob = new Blob([table], {type:"application/vnd.ms-excel"});
    let a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "report.xls";
    a.click();
}
</script>

</body>
</html>