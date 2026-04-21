<?php
session_start();

include('../include/connected.php');


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اسطول الشركة</title>
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
  $query ="DELETE FROM fleet WHERE id='$id'";
  $delete = mysqli_query($con , $query);
  if(isset($delete)){
     echo '<script>alert ("تم حزف السطحة بنجاح");</script>';

  }else{
    echo '<script>alert ("لم تتم حزف السطحة");</script>';
  }
}

// end delete
?>

   <div class ="sidebar_container">
<table dir="rtl">
  <tr>
<td>رقم المركبة</td>
<td>صورة المركبة</td>
<td>المزود</td>
<td> لوحة المركبة</td>
<td>طراز المركبة</td>
<td>نوع المركبة</td>
<td>موديل المركبة</td>
<td>لون المركبة</td>
<td>عمل المركبة</td>
<td>حزف المركبة</td>
<td>تعديل المركبة</td>
  </tr> 
  <?php
$query="SELECT *  FROM fleet";
$result =mysqli_query($con,$query);
while ($row=mysqli_fetch_assoc($result)){
?>

  <tr>
<th><?PHP echo $row['id'];?></th>

<th><img src="../fleetimg/img//<?PHP echo $row['imgfleet'];?>";</th>
</th>
<th> <?PHP echo $row['driver'];?></th>
<th> <?PHP echo $row['plate'];?></th>
<th> <?PHP echo $row['typefleet'];?></th>
<th> <?PHP echo $row['classify'];?></th>
<th> <?PHP echo $row['model'];?></th>
<th> <?PHP echo $row['colorfleet'];?></th>
<th> <?PHP echo $row['work'];?></th>

    <td><a href="fleet.php? id= <?php echo $row['id']; ?>"><button type="submit" class="delete">حزف الخدمة</button></a></td>
    <td><a href="updatefleet.php? id= <?php echo $row['id']; ?>"><button type="submit" class="update">تعديل الخدمة</button></a></td>

  </tr> 
  
  </dive>
  
<?php
}
?>
</body>
</html>