<?php
include('file/header.php');

if(!isset($con)){
    die("Database connection error");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>البحث</title>

<style>
.notification{
    width:90%;
    margin:50px auto;
    background:#fff3cd;
    border:2px solid #ff0000;
    padding:15px;
    font-size:22px;
    text-align:center;
}

.product{
    width:300px;
    background:#fff;
    margin:10px;
    display:inline-block;
    padding:10px;
    border-radius:10px;
    box-shadow:0 0 5px #ccc;
}
.product img{width:100%}
</style>

</head>

<body>

<?php
if(isset($_GET['btn_search'])){

    $search = mysqli_real_escape_string($con, $_GET['search']);

    $query = "SELECT * FROM product 
    WHERE prodescrip LIKE '%$search%' 
    OR proname LIKE '%$search%' 
    OR id LIKE '%$search%' 
    OR proprice LIKE '%$search%'";

    $result = mysqli_query($con, $query);

    if(mysqli_num_rows($result) > 0){

        while($row = mysqli_fetch_assoc($result)){
?>

<div class="product">

    <img src="uploads/img/<?php echo $row['proimg']; ?>">

    <h3><?php echo $row['proname']; ?></h3>

    <p><?php echo $row['proprice']; ?> السعر</p>

    <p><?php echo $row['prodescrip']; ?></p>

    <button>اضف الى خدماتي</button>

</div>

<?php
        }

    } else {
        echo '<div class="notification">❌ الخدمة غير موجودة</div>';
    }
}
?>

</body>
</html>

<?php include('file/foter.php'); ?>