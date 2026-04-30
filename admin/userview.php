
<?php
session_start();
include('../include/core.php');
include('../include/connected.php');

/* 🔍 البحث */
$search = $_GET['search'] ?? '';

if ($search != '') {
    $search_safe = mysqli_real_escape_string($con, $search);
    $query = "SELECT * FROM users 
              WHERE username LIKE '%$search_safe%' 
              OR email LIKE '%$search_safe%'";
} else {
    $query = "SELECT * FROM users";
}

$result = mysqli_query($con, $query);

/* 🗑️ حذف */
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    mysqli_query($con, "DELETE FROM users WHERE id=$id");
    header("Location: userview.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= t('title') ?></title>

<style>
body {
    font-family: 'Cairo', sans-serif;
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

/* ➕ زر إضافة */
.add-btn {
    display: block;
    width: 200px;
    margin: 20px auto;
    text-align: center;
    background: #27ae60;
    color: white;
    padding: 10px;
    border-radius: 8px;
    text-decoration: none;
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

/* 🔘 الأزرار */
.delete {
    background: #e74c3c;
    color: white;
    padding: 6px 10px;
    border: none;
    border-radius: 5px;
}

.update {
    background: #27ae60;
    color: white;
    padding: 6px 10px;
    border: none;
    border-radius: 5px;
}
</style>
</head>

<body>

<a href="?lang=ar">🇸🇦 عربي</a>
<a href="?lang=en">🇬🇧 English</a>

<!-- ➕ إضافة عميل -->
<a href="adduser.php" class="add-btn">➕ <?= t('add_user') ?></a>

<!-- 🔍 البحث -->

<div class="search-box">

<form method="GET">
<input type="text" name="search" placeholder="<?= t('search_placeholder') ?>" value="<?php echo htmlspecialchars($search); ?>">
<button type="submit"><?= t('search') ?></button>
</form>
</div>

<!-- 📊 الجدول -->
<table dir="rtl">
<thead>
<tr>
<th><?= t('id') ?></th>
<th><?= t('username') ?></th>
<th><?= t('email') ?></th>
<th><?= t('actions') ?></th>
<th><?= t('edit') ?></th>
</tr>
</thead>

<tbody>
<?php while ($row = mysqli_fetch_assoc($result)) { ?>
<tr>

<td><?php echo $row['id']; ?></td>
<td><?php echo $row['username']; ?></td>
<td><?php echo $row['email']; ?></td>

<td>
<a href="userview.php?id=<?php echo $row['id']; ?>" 
onclick="return confirm('<?= t('confirm_delete') ?>')">
<button class="delete"><?= t('delete') ?></button>
</a>
</td>

<td>
<a href="updateuser.php?id=<?php echo $row['id']; ?>">
<button class="update"><?= t('edit') ?></button>
</a>
</td>

</tr>
<?php } ?>
</tbody>

</table>

</body>
</html>
