<?php
session_start();

include('../include/connected.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صيانة المركبات</title>
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
.remove{
  color: white;
    font-size: 18px;
    background-color: rgb(3, 228, 100);
    padding: 8px 18px;
    border-radius: 2px;
    border: 1px solid rgb(154, 240, 182);
    margin-right: 5px;
    margin-bottom: 15px;
}

</style>
<?php
// start delete
@$id =$_GET['id'];
if(isset($id)){
  $query ="DELETE FROM maintenance WHERE id='$id'";
  $delete = mysqli_query($con , $query);
  if(isset($delete)){
     echo '<script>alert ("تم حزف المستخدم بنجاح");</script>';

  }else{
    echo '<script>alert ("لم تتم حزف المستخدم");</script>';
  }
}

// end delete
?>
   <div class ="sidebar_container">
    
<table dir="rtl">
  <tr>
<td>رقم العملية</td>
<td>اسم الورشة</td>
<td> لوحة السطحة</td>
<td>المزود</td>
<td>نوع الصيانة</td>
<td>التكلفة</td>
<td> ملاحظة</td>
<td> تاريخ الصيانة</td>


  </tr> 
  <?php
  $total_cost = 0;
$query="SELECT *  FROM maintenance";
$result =mysqli_query($con,$query);
while ($row=mysqli_fetch_assoc($result)){
?>

  <tr>
<th><?PHP echo $row['id'];?></th>
<th> <?PHP echo $row['vehicle_name'];?></th>
<th> <?PHP echo $row['plate_number'];?></th>
<th> <?PHP echo $row['driver'];?></th>
<th><?PHP echo $row['maintenance_type'];?></th>
<th> <?PHP echo $row['cost'];?></th>
<th> <?PHP echo $row['notes'];?></th>
<th> <?PHP echo $row['maintenance_date'];?></th>


    
  </tr> 
  
  </dive>
  
<?php
}
?>

</body>
</html>