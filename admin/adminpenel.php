<?php

session_start();
include('../include/connected.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">;
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" href="style.css">

    <title>لوحة تحكم الادارة</title>
</head>
<body>
     <?php
      if(!isset( $_SESSION['EMAIL'])){
        header('loccation:../index.php');
      }
    else {
    
     ?>
     
     <?php
     @$sectionname= $_POST['sectionname'];
     @$sacadd=$_POST['sacadd'];
     @$id=$_GET['id'];
     if(isset($sacadd)){
      if(empty($sectionname)){
       echo '<script>alert ("الرجاء ملئ الحقل ");</script>';
      }
      elseif($sectionname < 50){
        echo '<script>alert ("اسم القسم طويل جدا");</script>';
      }
      else{
        $query="INSERT INTO section (sectionname) VALUES ('$sectionname')";
        $result =mysqli_query($con, $query);
        echo '<script>alert ("تم اضافة القسم بنجاح");</script>';
      }
       
     }
    }
     ?>
     <?php
     #Delete section
      if(isset($id)){
      $query="DELETE From section WHERE id='$id'";
       $delete= mysqli_query($con,$query);
       if(isset($delete)){
          echo '<script>alert ("تم الحزف بنجاح");</script>';
    }else{
      echo '<script>alert ("لم يتم الحزف");</script>';
      }
      }
     ?>
<!-------sidebar start---->
<div class ="sidebar_container">

<div class ="sidebar">
<h1>لوحة تحكم الادارة</h1>
<ul>
  <li><a href="../index.php" target_blank>الصفحة الرئيسية<i class="fa-solid fa-house"></i></a></li>
  <li><a href="services.php" target_blank>خدمات الشركه<i class="fa fa-truck" aria-hidden="true"></i></a></li>
  <li><a href="addproduct.php" target_blank>اضافة منتج<i class="fa-solid fa-folder-plus"></i></a></li>
  <li><a href="../index.php" target_blank>معلومات العملاء<i class="fa-solid fa-users"></i></a></li>
  <li><a href="../index.php" target_blank>طلبات العملاء <i class="fa-solid fa-folder-open"></i></a></li>
  <li><a href="../index.php" target_blank>مركبات الشركة<i class="fa fa-truck" aria-hidden="true"></i></a></li>
  <li><a href="../index.php" target_blank> معلومات المزودين<i class="fa-regular fa-id-card"></i></a></li>
  <li><a href="../index.php" target_blank> صيانة المركبات<i class="fa-sharp fa-solid fa-car-wrench"></i></a></li>
   <li><a href="logout.php" target_blank> تسجيل الخروج <i class="fa-sharp fa-solid fa-arrow-right-from-bracket"></i></a></li>

</UL>
</div>
<!-----sidebar end---->

<!-----section start---->
<div class="content_sec">
<form action="adminpenel.php" method="post">
  <label for="section">قسم جديد</label>
  <input type="text" name="sectionname" id="ectio">
  <br>
  <button class="add" type="submit" name="sacadd">اضافة قسم</button>
</form>
<br>
<!-----table start---->
<table dir="rtl">
  <tr>
<th>الرقم التسلسلي</th>
<th>اسم القسم</th>
<th>حزف القسم</th>
  </tr>
  <tr>
    <?php
    $query="SELECT *  FROM section";
   $result =mysqli_query($con, $query);
    while ($row=mysqli_fetch_assoc($result)){
      # code...
      ?>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['sectionname']; ?></td>
    <td><a href="adminpenel.php? id= <?php echo $row['id']; ?>"><button type="submit" class="delete">حزف القسم</button></a></td>
  </tr>

  

 <?php
    }
     ?>

</table>
</div>


<!-----section end---->

</div >
</div >
     <?php
     //CLOSE ELSE
    
    ?>
</body>
</html>