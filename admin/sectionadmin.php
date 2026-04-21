<?php

session_start();
include('../include/connected.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">;
       

    <title>التحكم باقسام الموقع</title>
</head>
<style>
body {
    font-family: Arial;
    direction: rtl;
    background: #f5f6fa;
    margin: 0;
}

/* الحاوية */
.container {
    width: 60%;
    margin: 40px auto;
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

/* العنوان */
h2 {
    text-align: center;
    margin-bottom: 20px;
}

/* الفورم */
form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

input[type="text"] {
    flex: 1;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

button {
    padding: 10px 20px;
    border: none;
    background: #28a745;
    color: #fff;
    border-radius: 8px;
    cursor: pointer;
}

button:hover {
    background: #218838;
}

/* الجدول */
table {
    width: 100%;
    border-collapse: collapse;
    overflow: hidden;
    border-radius: 10px;
}

th {
    background: #007bff;
    color: white;
    padding: 12px;
}

td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

tr:hover {
    background: #f1f1f1;
}

/* زر الحذف */
.delete {
    color: white;
    background: #dc3545;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
}

.delete:hover {
    background: #c82333;
}
</style>

<body>
     
     
     <?php
     @$sectionname= $_POST['sectionname'];
     @$sacadd=$_POST['sacadd'];
     @$id=$_GET['id'];
     if(isset($sacadd)){
      if(empty($sectionname)){
       echo '<script>alert ("الرجاء ملئ الحقل ");</script>';
      }
      elseif(strlen($sectionname) > 50){
  echo '<script>alert ("اسم القسم يجب ألا يتجاوز 50 حرف");</script>';
}
      else{
        $query="INSERT INTO section (sectionname) VALUES ('$sectionname')";
        $result =mysqli_query($con, $query);
        echo '<script>alert ("تم اضافة القسم بنجاح");</script>';
      }
       header("Location: sectionadmin.php");
    exit();
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
      header("Location: sectionadmin.php");
  exit();
      }
     ?>


<!-----section start---->
<div class="content_sec">
<form action="sectionadmin.php" method="post">
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
    <td><a href="sectionadmin.php? id= <?php echo $row['id']; ?>"><button type="submit" class="delete">حزف القسم</button></a></td>
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