<?php
session_start();

include('../include/connected.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صفحة الخدمات</title>
    <link rel="stylesheet" href="style.css">
</head>
<body><br>
<style>
  /* end product css */
#form_control{
    width: 80%;
    padding: 12px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.update{
    color: white;
    font-size: 18px;
    background-color: rgb(3, 228, 100);
    padding: 8px 18px;
    border-radius: 2px;
    border: 1px solid rgb(154, 240, 182);
    margin-right: 5px;
}
.update:hover{
    background-color: rgb(8, 94, 23);
    color: white;
}
  img {
    width: 80px;
    height: 80px;
} 
</style>
<?php
// start delete
@$id =$_GET['id'];
if(isset($id)){
  $query ="DELETE FROM product WHERE id='$id'";
  $delete = mysqli_query($con , $query);
  if(isset($delete)){
     echo '<script>alert ("تم حزف الخدمة بنجاح");</script>';

  }else{
    echo '<script>alert ("لم تتم حزف الخدمة");</script>';
  }
}

// end delete
?>

   <div class ="sidebar_container">
<table dir="rtl">
  <tr>
<td>رقم الخدمة</td>
<td>صورة الخدمة</td>
<td>عنوان الخدمة</td>
<td>سعر الخدمة</td>
<td>انواع الخدمة</td>
<td>تفاصيل الخدمة</td>
<td>توفر الخدمة</td>
<td>حزف الخدمة</td>
<td>تعديل الخدمة</td>
  </tr> 
  <?php
$query="SELECT *  FROM product";
$result =mysqli_query($con,$query);
while ($row=mysqli_fetch_assoc($result)){
?>

  <tr>
<th><?PHP echo $row['id'];?></th>
<!------img---->
<th><img src="../uploads/img//<?PHP echo $row['proimg'];?>";</th>
<!------img---->
<th> <?PHP echo $row['proname'];?></th>
<th> <?PHP echo $row['proprice'];?></th>
<th> <?PHP echo $row['prosection'];?></th>
<th> <?PHP echo $row['prodescrip'];?></th>
<th> <?PHP echo $row['prounv'];?></th>


    <td><a href="services.php? id= <?php echo $row['id']; ?>"><button type="submit" class="delete">حزف الخدمة</button></a></td>
    <td><a href="update.php? id= <?php echo $row['id']; ?>"><button type="submit" class="update">تعديل الخدمة</button></a></td>

  </tr> 
  <!-- <button onclick="window.print()" style="
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
">
    🖨️ طباعة
</button>
</style> -->
  </dive>
<?php
}
?>
</body>
</html>