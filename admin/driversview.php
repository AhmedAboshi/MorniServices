
<?php
include('../include/connected.php');

/* 🔍 البحث */
$search = $_GET['search'] ?? '';

if($search != ''){
    $search_safe = $con->real_escape_string($search);
    $result = $con->query("
        SELECT * FROM drivers
        WHERE name LIKE '%$search_safe%' 
        OR phone LIKE '%$search_safe%' 
        OR plate_number LIKE '%$search_safe%'
    ");
}else{
    $result = $con->query("SELECT * FROM drivers");
}

$drivers = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>السائقين</title>

<style>
body {
    font-family: 'Cairo', sans-serif;
    direction: rtl;
    background: #f4f6f9;
}

/* 🔍 البحث */
.search-box {
    text-align: center;
    margin: 20px;
}

.search-box input {
    width: 300px;
    padding: 10px;
    border-radius: 25px;
    border: 1px solid #ccc;
}

.search-box button {
    padding: 10px 15px;
    border: none;
    background: #3498db;
    color: white;
    border-radius: 20px;
}

/* 📊 الجدول */
table {
    width: 95%;
    margin: auto;
    border-collapse: collapse;
    background: #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

th {
    background: #2c3e50;
    color: white;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    text-align: center;
}

tr:nth-child(even){
    background: #f9f9f9;
}

tr:hover{
    background: #eef5ff;
}

/* 🔘 زر */
.btn {
    background: #27ae60;
    color: white;
    padding: 6px 12px;
    border-radius: 5px;
    text-decoration: none;
}
</style>
</head>

<body>

<h2 style="text-align:center;">📋 قائمة السائقين</h2>

<!-- 🔍 البحث -->
<div class="search-box">
<form method="GET">
<input type="text" name="search" placeholder="ابحث عن سائق..." value="<?= htmlspecialchars($search) ?>">
<button type="submit">بحث</button>
</form>
</div>

<table>
<tr>
  <th>الاسم</th>
  <th>الجوال</th>
  <th>الشاحنة</th>
  <th>اللوحة</th>
  <th>المنطقة</th>
  <th>تفاصيل</th>
</tr>

<?php if(count($drivers) > 0): ?>
<?php foreach($drivers as $d): ?>
<tr>
  <td><?= htmlspecialchars($d['name']) ?></td>
  <td><?= htmlspecialchars($d['phone']) ?></td>
  <td><?= htmlspecialchars($d['truck_type']) ?></td>
  <td><?= htmlspecialchars($d['plate_number']) ?></td>
  <td><?= htmlspecialchars($d['work_area']) ?></td>
  <td>
    <a class="btn" href="driver_details.php?id=<?= (int)$d['id'] ?>">
        عرض
    </a>
  </td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="6">لا يوجد نتائج</td>
</tr>
<?php endif; ?>

</table>

</body>
</html>

