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
 <?php
@$add=$_POST['add'];
if(isset($_POST['add'])){
    @$ID =$_POST['id'];
    $productname =$_POST['h_name'];
    $productprice =$_POST['h_price'];
    $productimg =$_POST['h_img'];
    $productquantity =$_POST['quantity'];
    @$product_id=$_POST['product_id'];
    @$user_id = $_SESSION['user_id'];

    // التحقق ازا كانت الخدمة موجوده في قاعدة البايانات ام لا
  $add_cart="SELECT * FROM  cart WHERE 	name='$productname' AND user_id='$user_id'";
  $result= mysqli_query($con,$add_cart);
  if(mysqli_num_rows($result) >0){
     echo '<script>alert ("عزرا الخدمة مضافة مسبقا لم تتم الاضافة");</script>';
  }else{
    // اضافة الخدمة ازا لم تضاف مسبقا
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0){
    $insert_cart="INSERT INTO cart (product_id,name,price,img,quantity,user_id) VALUES ('$product_id','$productname','$productprice','$productimg','$productquantity','$user_id')";
    if(mysqli_query($con,$insert_cart) === TRUE){
        echo '<script>alert ("تمت اضافة الخدمة بنجاح الي سلة خدماتي");</script>';
    }else{
        echo '<script>alert ("عزرا لم تتم اضافة الخدمة الي السلة");</script>';
    }
  }   
}
}
// start delete
if (isset($_POST['delete_c'])) {

    $ID = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($ID > 0) {

        $query = "DELETE FROM cart WHERE id='$ID' AND user_id='$user_id'";
        $delete = mysqli_query($con, $query);

        if ($delete) {
            echo '<script>alert("تم الحذف بنجاح");</script>';
        } else {
            echo '<script>alert("عذراً لم يتم الحذف");</script>';
        }

    }
}
// end delete
//start updete
if (isset($_POST['update_qty'])) {

    $ID  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($ID > 0 && $qty > 0) {

        $query = "UPDATE cart SET quantity = $qty WHERE id = $ID AND user_id='$user_id'";
        $update = mysqli_query($con, $query);

        if ($update) {
            echo '<script>alert("تم تحديث الكمية بنجاح");</script>';
            echo '<script>window.location.href="cart.php";</script>';
        } else {
            echo '<script>alert("خطأ في التحديث");</script>';
        }

    }
}
// end uptete
 ?>
 
 <!DOCTYPE html>
 <html lang="en">
 <head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>سلة الخدمات</title>
 </head>
 <style>
  *{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  
  }
  h3{
    font-family: arial ,sans-serif;
    color:black;
  }
  body{
    backgrount-color: #fff;
    color: #333;
  }
.cart_container{
  width: 80%;
  margin: 50px auto;
  background-color: #fff;
  padding:20px;
  box-shadow: rgba(0,0,0,0.2);
  direction:rtl;
}
.cont_head{
  padding : 5px;
  width: 100%;
  height: 100px;
  background-color: rgba(168,168,236);
  margin-top:0px;
}
.cont_head img{
  width: 70px;
  height: 70px;
  float: left;
  border-radiues:20px;
}
.cont_head h1{
float: left;
margin: 20px;
}
.cart_table{
  width: 100%;
  border-collaps: collapse;
  margin_bottom: 20px;
}
.cart_table th, td{
  padding: 15px;
  text-align: center;
  border: 1px solid #ddd;
}
.cart_table th{
  background-color: #d3d8e4;
}
.cart_table img{
  width: 70px;
  height: 70px;
}
.cart_table input{
  width: 50px;
  padding: 5px;
  text-align: center;
}
.remove{
  border:none;
  padding: 10px 10px;
  cursor: pointer;
  color: white;
  background-color: #0a79a5;
}
.update_qty{
  border:none;
  padding: 10px 10px;
  cursor: pointer;
  color: white;
  background-color: #0a79a5;
}
.remove:hover{
  background-color: rgb(4,59,110);
}
.cart_total h6{
color: black;
font-size: large;
}
.cart_total button{
  padding: 10px 40px;
  transition: transform 0.3s ease;
  color: white;
}
.cart_total button a{
  color: #fff;
  text-decoration: none;
}
.cart_total button:hover{
transform: scale(1,2);
}
 </style>
 <body>
  <div class="cart_container">
    <div class="cont_head">
      <img src="img/logo.jpg" alt="">
      <?php
// الاستعلام لاسترجاع اسم المستخدم من قاعدة البايانات
$query = "SELECT username FROM users WHERE id='$user_id'";
$result= mysqli_query($con ,$query);
//التاكد من وجود نتيجة من هزا الاستعلام
if($result){
  if(mysqli_num_rows($result) > 0){
    while($row=mysqli_fetch_assoc($result)){
      //عرض اسم المستخدم الزي تم تسجيل دخوله للموقع
      echo "<h1> ".$row['username']."اهلا بك </h1>";
    }
  }else{
    echo "<h1>لاتوجد نتائج للمستخدم</h1>";
    }
}
      ?>
      
    </div>
    <!----start table---->
    <table class="cart_table">
    <tr>
  <th>صورة الخدمة</th>
  <th>رقم الخدمة</th>
  <th>اسم الخدمة</th>
  <th>الكمية</th>
  <th>السعر</th>
  <th>الاجمالي</th>
  <th>حزف</th>
  <th>تعديل</th>
</tr>
 <?php
  $query= "SELECT * FROM cart WHERE  user_id='$user_id'";
  $result=mysqli_query($con,$query);
  $total =0;
  if(mysqli_num_rows($result) >0){
    while($row=mysqli_fetch_assoc($result)){

    
  ?>
<tr>
 <td><img src="uploads/img//<?PHP echo $row['img'];?>"> </td>
 <td><h3><?PHP echo $row['product_id'];?></h3></td>
 <td><h3><?PHP echo $row['name'];?></h3></td>
 <td><input  value="<?PHP echo $row['quantity'];?>" ></td>
 <td><h3><?PHP echo $row['price'];?></h3></td>
 <td><h3><?PHP echo number_format($row['quantity'] * $row['price'],2);?></h3></td>
    <!----start delete----->
 <td>
  <form method="POST">
  <input type="hidden" name="id" value="<?php echo $row['id'];?>">
  <button type="submit" name="delete_c" class="remove">حذف</button>
</form>
</td>
  <!----end delete----->

  <!----start uptate----->
 <td>
  <form method="POST">
  <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

  <input type="number" name="quantity" value="<?php echo $row['quantity']; ?>" min="1">

  <button type="submit" name="update_qty" class="remove">تحديث</button>
</form>
</td>
<!----end uptate----->
 <?php
 $total +=$row['quantity'] * $row['price'];
    }
  }
 ?>
</tr>
    </table>
    <!----end table---->
    <div class="cart_total">
      <h6> <?PHP echo number_format($total,2);?><span id="total"> الاجمالي </span></h6>
      <button type="submit" class="remove"><a href="order.php"><h2>اتمام الطلب</h2>
    </div>
  </div>
 </body>
 </html>