<?php
include('file/header.php');
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البحث</title>
</head>
<body>
    <style>
        .notification{
    width: 1000px;
    height: 50px;
    background-color: wheat;
    border: 2px solid red;
    margin: 140px 130px;
    padding: 10px;
    font-size: 40px;
    color: black;
    text-align: center;
}
    </style>
</body>
</html>

<?php
if(isset($_GET['btn_search'])){
    $search= $_GET['search'];
    $query = "SELECT * FROM  product WHERE prodescrip LIKE '%$search%' OR proname  LIKE '%$search%'
    or id LIKE '%$search% ' or proprice LIKE '%$search%'";
    $result =mysqli_query($con, $query);
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            echo '
            <div class="product">
        <!----img---->
        <div class="product_img">
            <img src="uploads/img//' .$row['proimg'].'">
            <span class="unvaiable">' .$row['prounv'].'</span>
          <a href=""></a>
        </div>
                    <!----section---->
        <div class="product_section">
            
            <a href="">' .$row['prosection'].'</a>
        </div>
        <!----name---->
        <div class="product_name">
            <a href="">' .$row['proname'].'</a>
        </div>
        <!----price---->
        <div class="product_price">
            <a href="">' .$row['proprice'].' &nbsp; السعر</a>
        </div>
        <!----description---->
        <div class="product_description">
            <a href="">' .$row['prodescrip'].'<i class="fa fa-truck" aria-hidden="true"></i>
            لتفاصيل الخدمة اضغط هنا
</a>
        </div>
        <!----Quantity---->
        <div class="qty_input">
         <button class="gty_count_mins">-</button>
         <input type="number" id="quantity" name="" value="1" min="0" max="6">
         <button class="gty_count_add">+</button>
        </div><br>
        <!---submit--->
        <div class="submit">
        <a href="">
            <button class="addto_cart" type="submit" name="">
                <i class="fa fa-truck" aria-hidden="true"></i>
                &nbsp; &nbsp;
                اضف الي خدماتي
            </button>
        </a>
        </div>
    </div>
    
            
            ';
        }
    }else{
        echo '<div class="notification">الخدمة التي تبحث عنها غير موجودة</div>';
    }
}
?>


<?php
include('file/foter.php');
 ?>