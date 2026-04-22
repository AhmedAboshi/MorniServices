<?php
$host ="localhost";
$user ="root";
$password ="";
$dbNAME="morniservices";

$con =mysqli_connect($host,$user,$password,$dbNAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" crossorigin="anonymous" />
    
   <title>خدمات مرني</title>
</head>
<body>
<header>
    <div class="logo">
        <h1>خدمات مرني</h1>
        <img src="img/logo.jpg" alt="">
    </div>

    <div class="search">
        <form action="search.php" method="get">
            <input type="text" class="search_input" name="search" placeholder="ادخل كلمــة للبحث">
            <button type="submit" name="btn_search" class="button_search">بحث</button>
        </form>
    </div>
</header>

<nav>
    <div class="social">
        <ul>
            <li><a href="https://www.facebook.com/share/17CRN96Z5v/" target="_blank"><i class="fa-brands fa-facebook"></i></a></li>
            <li><a href="https://www.instagram.com/morniksa/" target="_blank"><i class="fa-brands fa-square-instagram"></i></a></li>
            <li><a href="https://api.whatsapp.com/send/?phone=%2B966550186105" target="_blank"><i class="fa-brands fa-whatsapp"></i></a></li>
            <li><a href="https://x.com/MorniKSA" target="_blank"><i class="fa-brands fa-x-twitter"></i></a></li>
        </ul>
    </div>
    <div class="section">
        <ul>
            <li><a href="index.php">الرئيسية</a></li>
            <?php
            $query = "SELECT * FROM section";
            $result = mysqli_query($con, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                ?>
                 <li><a href="section.php?section=<?php echo $row['sectionname'];?>"> <?php echo $row['sectionname'];?></a>
                
                </li>
                <?php
            }
            ?>

<li>
  <a href="tow_order.php?type=tow_city">
    🚛 سطحة بين المدن
  </a>
</li>
<li>
  <a href="uboutas.php">
    من نحن 
  </a>
</li>

        </ul>
    </div>
</nav>
<div class="last-post">
    <ul>
        <h4>المضاف حديثا</h4>
        <?php
        $query="SELECT * FROM product ORDER BY ID DESC LIMIT 8";
        $result=mysqli_query($con,$query);
        while($row=mysqli_fetch_assoc($result)){
        ?>


        <li>
            <a href="detalis.php?id=<?php echo $row['id'];?>">
            <span class="span-img">
                <img src="uploads/img/<?PHP echo $row['proimg'];?>">
            </span>
</a>
        </li>

<?php
        }
        ?>

    </ul>
    <div class="cart">
    <ul>
        <li><a href="user/logout.php"><i class="fa-solid fa-user"></i></a></li>
        <li>
            <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



$row_count = 0;

$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id > 0) {

    $query = "SELECT COUNT(*) as count FROM cart WHERE user_id = $user_id";
    $result = mysqli_query($con, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $row_count = $row['count'];
    }
}
?>
    <a href="cart.php" style="position:relative;">
    <i class="fa-solid fa-cart-arrow-down"></i>

    <span class="cart-count">
        <?php echo $row_count; ?>
    </span>
</a>
</li>
    </ul>
</div>

</div>




</body>
</html>




