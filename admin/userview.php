<?php
session_start();

include('../include/connected.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معلومات العملاء</title>
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
  $query ="DELETE FROM users WHERE id='$id'";
  $delete = mysqli_query($con , $query);
  if(isset($delete)){
     echo '<script>alert ("تم حزف المستخدم بنجاح");</script>';

  }else{
    echo '<script>alert ("لم تتم حزف المستخدم");</script>';
  }
}

// end delete
?>
<div > 
    
    <button type="submit" class="remove"><a href="adduser.php"><h2>اضافة عميل</h2>
  </div>
   <div class ="sidebar_container">
    
<table dir="rtl">
  <tr>
<td>رقم العميل</td>
<td>اسم العميل</td>
<td> الايميل</td>
<td> كلمة المرور</td>
<td>حزف العميل</td>
<td>تعديل بيانات</td>
  </tr> 
  <?php
$query="SELECT *  FROM users";
$result =mysqli_query($con,$query);
while ($row=mysqli_fetch_assoc($result)){
?>

  <tr>
<th><?PHP echo $row['id'];?></th>
<th> <?PHP echo $row['username'];?></th>
<th> <?PHP echo $row['email'];?></th>
<th> <?PHP echo $row['password'];?></th>



    <td><a href="userview.php? id= <?php echo $row['id']; ?>"><button type="submit" class="delete">حزف العميل</button></a></td>
    <td><a href="updateuser.php? id= <?php echo $row['id']; ?>"><button type="submit" class="update">تعديل العميل</button></a></td>

  </tr> 
  
  </dive>
  
<?php
}
?>

</body>
</html>