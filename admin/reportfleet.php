<?php
include('../include/connected.php');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Fleet Report</title>

    <style>
        body { font-family: Arial; direction: rtl; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background: #007bff; color: white; }

        .btn {
            padding: 10px;
            margin-top: 10px;
            background: green;
            color: white;
            border: none;
            cursor: pointer;
    
        }

        .search-box {
            margin: 10px 0;
        }
        .search{
           background: #007bff; color: white; 
        }
    </style>
</head>

<body>

<h2>تقرير المركبات</h2>

<!-- 🔍 Search Form -->
<form method="post" class="search-box">
    <input type="text" name="driver" placeholder="اسم السائق">
    <input type="text" name="plate" placeholder="رقم اللوحة">
    <input type="text" name="work" placeholder="المدينة">
    <button type="submit" name="search">بحث</button>
</form>

<!-- 🖨️ Print Button -->
<button class="btn" onclick="window.print()">طباعة التقرير</button>

<a href="export_fleet.php">
    <button class="btn">Download Excel</button>
</a>
<?php
$sql = "SELECT * FROM fleet WHERE 1=1";


if (isset($_POST['search'])) {

    $driver = $_POST['driver'] ?? '';
    $plate = $_POST['plate'] ?? '';
    $work = $_POST['work'] ?? '';

    if (!empty($driver)) {
        $sql .= " AND driver LIKE '%$driver%'";
    }

    if (!empty($plate)) {
        $sql .= " AND plate LIKE '%$plate%'";
    }

    if (!empty($work)) {
        $sql .= " AND work LIKE '%$work%'";
    }
}

$result = mysqli_query($con, $sql);

echo "<table>";
echo "<tr>
        <th>ID</th>
        <th>Driver</th>
        <th>Plate</th>
        <th>Type</th>
        <th>Model</th>
        <th>Color</th>
        <th>Work</th>
        <th>imgfleet</th>
      </tr>";

while ($row = mysqli_fetch_assoc($result)) {

    echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['driver']}</td>
            <td>{$row['plate']}</td>
            <td>{$row['typefleet']}</td>
            <td>{$row['model']}</td>
            <td>{$row['colorfleet']}</td>
            <td>{$row['work']}</td>
            <td><img src='../fleetimg/img/{$row['imgfleet']}' width='80'></td>
          </tr>";
}


echo "</table>";
?>

</body>
</html>