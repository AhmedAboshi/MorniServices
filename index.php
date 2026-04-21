 <?php
session_start();

if(!isset($_SESSION['user_id'])){
    echo '<script>
    alert("يرجى تسجيل الدخول أولاً لإضافة الخدمة إلى خدماتي");
    window.location.href="user/login.php";
    </script>';
    exit();
}

$user_id = $_SESSION['user_id'];
if($user_id <=0){
     echo '<script>
    alert("ستخدم غير صحيح");
    window.location.href="user/login.php";
    </script>';
}
?>

 <?php
include('file/header.php');
 ?>
   </div>
   <main>
<?php
$query="SELECT * FROM product ";
$result=mysqli_query($con,$query);
while($row=mysqli_fetch_assoc($result)){

?>

<!----start product---->
    <div class="product">
        <!----img---->
        <div class="product_img"><a href="detalis.php?id=<?php echo $row['id']?>">
            <img src="uploads/img//<?PHP echo $row['proimg'];?>">
            <span class="unvaiable"><?PHP echo $row['prounv'];?></span>
          <a href="detalis.php?id=<?php echo $row['id']?>"></a>
        </div>
                    <!----section---->
        <div class="product_section">
            <a href="section.php?section=<?php echo $row['prosection'];?>">
                <?PHP echo $row['prosection'];?></a>
        </div>
             <!---- end section---->
        <!----name---->
        <div class="product_name">
            <a href="detalis.php?id=<?php echo $row['id']?>"><?PHP echo $row['proname'];?> </a>
        </div>
        <!----price---->
        <div class="product_price">
            <a href="detalis.php?id=<?php echo $row['id']?>"><?PHP echo $row['proprice'];?>$ &nbsp; السعر</a>
        </div>
        <!----description---->
        <div class="product_description">
            <a href="detalis.php?id=<?php echo $row['id']?>"><i class="fa fa-truck" aria-hidden="true"></i>
            لتفاصيل الخدمة اضغط هنا
</a>
        </div>
        <!----Quantity---->
        <div class="qty_input">
            <form action="cart.php?action<?php echo $row['id'];?>"method="post">
         <button class="gty_count_mins">-</button>
         <input type="number" id="quantity" name="quantity" value="1" min="0" max="6">
         <input type="hidden" name="product_id" value="<?php echo $row['id'];?>">
         <input type="hidden" name="h_name" value="<?php echo $row['proname'];?>">
         <input type="hidden" name="h_price" value="<?php echo $row['proprice'];?>">
         <input type="hidden" name="h_img" value="<?php echo $row['proimg'];?>">
         <button class="gty_count_add">+</button>
        </div><br>
        <!---submit--->
        <div class="submit">
        <a href="">
            <button class="addto_cart" type="submit" name="add" value="add_cart">
                <i class="fa fa-truck" aria-hidden="true"></i>
                &nbsp; &nbsp;
                اضف الي خدماتي
            </button>
        </a>
        </div>
</form>
    </div>
    <!----end product---->


<?php
}
?>
   </main>
   <!----end product---->
   <?php
include('file/foter.php');
 ?>
 