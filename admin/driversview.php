<?php
include('../include/connected.php');
// جلب البيانات


$result = $con->query("SELECT * FROM drivers");

$drivers = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>السائقين</title>
<style>
body { font-family: Arial; direction: rtl; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { padding: 10px; border: 1px solid #ccc; text-align: center; }
th { background: #eee; }
a { text-decoration: none; color: blue; }
</style>
</head>
<body>

<h2>📋 قائمة السائقين</h2>

<table>
<tr>
  <th>الاسم</th>
  <th>الجوال</th>
  <th>الشاحنة</th>
  <th>اللوحة</th>
  <th>المنطقة</th>
  <th>تفاصيل</th>
</tr>

<?php foreach($drivers as $d): ?>
<tr>
  <td><?= htmlspecialchars($d['name']) ?></td>
  <td><?= htmlspecialchars($d['phone']) ?></td>
  <td><?= htmlspecialchars($d['truck_type']) ?></td>
  <td><?= htmlspecialchars($d['plate_number']) ?></td>
  <td><?= htmlspecialchars($d['work_area']) ?></td>
  <td>
    <a href="driver_details.php?id=<?= $d['id'] ?>">عرض</a>
  </td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>