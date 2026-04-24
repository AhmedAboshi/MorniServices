<?php
session_start();
include('include/connected.php');

// التحقق من تسجيل الدخول
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// التحقق من وجود id
if(!isset($_GET['id'])){
    echo "طلب غير موجود";
    exit();
}

$order_id = intval($_GET['id']);

// جلب تفاصيل الطلب مع التأكد أنه يخص نفس المستخدم (مهم للأمان)
$stmt = $con->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    echo "لا يوجد طلب أو ليس لديك صلاحية لعرضه";
    exit();
}

$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل الطلب</title>
    <style>
        body{
            font-family: Arial;
            direction: rtl;
            background: #f5f5f5;
            text-align: center;
        }
        .box{
            width: 50%;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        h2{
            margin-bottom: 20px;
        }
        p{
            font-size: 18px;
            margin: 10px 0;
        }
        .completed{ color: green; font-weight: bold; }
        .pending{ color: orange; font-weight: bold; }
    </style>
</head>

<body>

<div class="box">
    <h2>تفاصيل الطلب #<?php echo $order['id']; ?></h2>

    <p><strong>السعر:</strong> <?php echo $order['price']; ?> ريال</p>

    <p><strong>الحالة:</strong>
        <?php if($order['status'] == 'completed'): ?>
            <span class="completed">مكتمل</span>
        <?php else: ?>
            <span class="pending">قيد التنفيذ</span>
        <?php endif; ?>
    </p>

    <p><strong>تاريخ الطلب:</strong> <?php echo $order['created_at']; ?></p>

    <a href="myorders.php">⬅ الرجوع للطلبات</a>
</div>

</body>
</html>