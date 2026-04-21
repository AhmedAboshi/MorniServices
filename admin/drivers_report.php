<?php
include('../include/connected.php');

/* =========================
   🔍 الفلاتر
========================= */
$work_area = $_GET['work_area'] ?? '';
$truck_type = $_GET['truck_type'] ?? '';

$where = [];

if($work_area){
    $where[] = "work_area LIKE '%$work_area%'";
}

if($truck_type){
    $where[] = "truck_type LIKE '%$truck_type%'";
}

$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT name, national_id, truck_type, work_area 
        FROM drivers 
        $where_sql
        ORDER BY name ASC";

/* =========================
   📥 Excel Export
========================= */
if(isset($_GET['excel'])){

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=drivers_report.xls");

$res = mysqli_query($con, $sql);

echo "<table border='1'>
<tr>
<th>الاسم</th>
<th>الهوية</th>
<th>نوع السطحة</th>
<th>منطقة العمل</th>
</tr>";

while($r = mysqli_fetch_assoc($res)){
echo "<tr>
<td>{$r['name']}</td>
<td>{$r['national_id']}</td>
<td>{$r['truck_type']}</td>
<td>{$r['work_area']}</td>
</tr>";
}

echo "</table>";
exit();
}

/* =========================
   📊 البيانات
========================= */
$result = mysqli_query($con, $sql);

/* =========================
   📍 إحصائيات المناطق
========================= */
$area_stats = mysqli_query($con,"
SELECT work_area, COUNT(*) as total
FROM drivers
GROUP BY work_area
");

/* =========================
   🚛 إحصائيات السطحات
========================= */
$truck_stats = mysqli_query($con,"
SELECT truck_type, COUNT(*) as total
FROM drivers
GROUP BY truck_type
");
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تقرير السائقين</title>

<style>
body{font-family:Arial;background:#f4f6f9;}

.container{width:95%;margin:auto;}

form{
background:#fff;
padding:15px;
margin:20px auto;
border-radius:10px;
text-align:center;
}

input{
padding:10px;
margin:5px;
width:200px;
}

button{
padding:10px 15px;
background:#28a745;
color:#fff;
border:none;
border-radius:5px;
cursor:pointer;
}

table{
width:100%;
background:#fff;
border-collapse:collapse;
margin-top:20px;
}

th,td{
padding:10px;
border:1px solid #ddd;
text-align:center;
}

th{
background:#28a745;
color:#fff;
}

.box{
background:#fff;
padding:15px;
margin:10px auto;
border-radius:10px;
}
</style>

</head>
<body>

<div class="container">

<h2 style="text-align:center;">🚗 تقرير السائقين</h2>

<!-- 🔍 البحث -->
<form method="get">

<input type="text" name="work_area" placeholder="منطقة العمل"
value="<?php echo $work_area; ?>">

<input type="text" name="truck_type" placeholder="نوع السطحة"
value="<?php echo $truck_type; ?>">

<button type="submit">🔍 بحث</button>

</form>

<!-- 📥 PDF -->
<div style="text-align:center;">
<button onclick="window.print()">📄 PDF / طباعة</button>

<form method="get" style="display:inline;">
<input type="hidden" name="work_area" value="<?php echo $work_area; ?>">
<input type="hidden" name="truck_type" value="<?php echo $truck_type; ?>">
<button name="excel" value="1">📥 Excel</button>
</form>
</div>

<!-- 📍 حسب المنطقة -->
<div class="box">
<h3>📍 عدد السائقين حسب المنطقة</h3>
<ul>
<?php while($a = mysqli_fetch_assoc($area_stats)){ ?>
<li><?php echo $a['work_area']; ?> : <b><?php echo $a['total']; ?></b></li>
<?php } ?>
</ul>
</div>

<!-- 🚛 حسب السطحة -->
<div class="box">
<h3>🚛 عدد السطحات حسب النوع</h3>
<ul>
<?php while($t = mysqli_fetch_assoc($truck_stats)){ ?>
<li><?php echo $t['truck_type']; ?> : <b><?php echo $t['total']; ?></b></li>
<?php } ?>
</ul>
</div>

<!-- 📋 الجدول -->
<table>
<tr>
<th>الاسم</th>
<th>الهوية</th>
<th>نوع السطحة</th>
<th>منطقة العمل</th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>
<tr>
<td><?php echo $row['name']; ?></td>
<td><?php echo $row['national_id']; ?></td>
<td><?php echo $row['truck_type']; ?></td>
<td><?php echo $row['work_area']; ?></td>
</tr>
<?php } ?>

</table>

</div>

</body>
</html>