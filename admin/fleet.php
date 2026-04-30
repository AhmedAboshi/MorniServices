
<?php
session_start();
include('../include/core.php');
include('../include/connected.php');

/* 🔍 البحث */
$search = $_GET['search'] ?? '';

if ($search != '') {
    $search_safe = mysqli_real_escape_string($con, $search);
    $query = "SELECT * FROM fleet 
              WHERE driver LIKE '%$search_safe%' 
              OR plate LIKE '%$search_safe%' 
              OR classify LIKE '%$search_safe%'";
} else {
    $query = "SELECT * FROM fleet";
}

$result = mysqli_query($con, $query);

/* 🗑️ حذف */
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    mysqli_query($con, "DELETE FROM fleet WHERE id=$id");
    header("Location: fleet.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= t('fleet_title') ?></title>

<style>
body {
    font-family: 'Cairo', sans-serif;
    background: #f4f6f9;
    margin: 0;
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
    cursor: pointer;
}

/* 📊 الجدول */
table {
    width: 95%;
    margin: auto;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

th {
    background: #2c3e50;
    color: white;
    padding: 12px;
}

td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

tr:nth-child(even){
    background: #f9f9f9;
}

tr:hover{
    background: #eef5ff;
}

/* 🖼️ الصور */
img {
    width: 70px;
    height: 70px;
    border-radius: 6px;
    object-fit: cover;
}

/* 🔘 الأزرار */
.delete {
    background: #e74c3c;
    color: white;
    padding: 6px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.update {
    background: #27ae60;
    color: white;
    padding: 6px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
</style>
</head>

<body>

<a href="?lang=ar">🇸🇦 عربي</a>
<a href="?lang=en">🇬🇧 English</a>

<!-- 🔍 البحث -->
<div class="search-box">
<form method="GET">
<input type="text" name="search" placeholder="<?= t('search_vehicle') ?>" value="<?php echo htmlspecialchars($search); ?>">
<button type="submit"><?= t('search') ?></button>
</form>
</div>

<!-- 📊 الجدول -->
<table dir="rtl">
<thead>
<tr>
<th><?= t('id') ?></th>
<th><?= t('image') ?></th>
<th><?= t('driver') ?></th>
<th><?= t('plate') ?></th>
<th><?= t('type') ?></th>
<th><?= t('class') ?></th>
<th><?= t('model') ?></th>
<th><?= t('color') ?></th>
<th><?= t('work') ?></th>
<th><?= t('delete') ?></th>
<th><?= t('edit') ?></th>
</tr>
</thead>

<tbody>
<?php while ($row = mysqli_fetch_assoc($result)) { ?>
<tr>

<td><?php echo $row['id']; ?></td>

<td>
<a href="fleet_details.php?id=<?= $row['id'] ?>">
    <img src="../fleetimg/img/<?= $row['imgfleet']; ?>">
</a>
</td>

<td><?php echo $row['driver']; ?></td>
<td><?php echo $row['plate']; ?></td>
<td><?php echo $row['typefleet']; ?></td>
<td><?php echo $row['classify']; ?></td>
<td><?php echo $row['model']; ?></td>
<td><?php echo $row['colorfleet']; ?></td>
<td><?php echo $row['work']; ?></td>

<td>
<a href="fleet.php?id=<?php echo $row['id']; ?>" 
onclick="return confirm('?<?= t('confirm_delete_vehicle') ?>')">
<button class="delete">حذف</button>
</a>
</td>

<td>
<a href="updatefleet.php?id=<?php echo $row['id']; ?>">
<button class="update"><?= t('delete') ?></button>
</a>
</td>

</tr>
<?php } ?>
</tbody>

</table>

</body>
</html>

