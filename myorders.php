<?php
session_start();
include('include/connected.php');

// التحقق من تسجيل الدخول
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// جلب الطلبات
$stmt = $con->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>طلباتي</title>
    <style>
        body {
            font-family: Arial;
            direction: rtl;
            text-align: center;
            background: #f5f5f5;
        }
        h2 {
            margin-top: 20px;
        }
        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 80%;
            background: #fff;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        th {
            background: #333;
            color: #fff;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .completed {
            color: green;
            font-weight: bold;
        }
        .pending {
            color: orange;
            font-weight: bold;
        }
        .empty {
            margin-top: 20px;
            color: gray;
        }
    </style>
</head>

<body>

<h2>طلباتي</h2>

<?php if($result->num_rows == 0): ?>
    <p class="empty">لا توجد طلبات حالياً</p>
<?php else: ?>

<table>
    <tr>
        <th>رقم الطلب</th>
        <th>السعر</th>
        <th>الحالة</th>
        <th>التاريخ</th>
        <th>عرض التفاصيل</th>

    </tr>

    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?php echo $row['id']; ?></td>
        <td><?php echo $row['price']; ?> ريال</td>
        <td>
            <?php if($row['status'] == 'completed'): ?>
                <span class="completed">مكتمل</span>
            <?php else: ?>
                <span class="pending">قيد التنفيذ</span>
            <?php endif; ?>
        </td>
        <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
        <td>
    <a href="myorderdetails.php?id=<?php echo $row['id']; ?>">
        عرض التفاصيل
    </a>
</td>
    </tr>
    <?php endwhile; ?>

</table>

<?php endif; ?>

</body>
</html>