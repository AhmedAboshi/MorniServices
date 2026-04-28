<?php
include('../include/core.php');

include('../include/connected.php');

?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">;
       

    <title><?= __('Manage Website Sections') ?></title>
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
    margin-bottom: 25px;
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
    margin-bottom: 25px;
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
    margin-top: 25px;
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
a{
    margin-top:25px;
}
</style>

<body>
     
     <a href="?lang=ar">🇸🇦 عربي</a>
<a href="?lang=en">🇬🇧 English</a>
     <?php
     @$sectionname= $_POST['sectionname'];
     @$sacadd=$_POST['sacadd'];
     @$id=$_GET['id'];
     if (isset($sacadd)) {

    if (empty($sectionname)) {
        echo "<script>alert('" . __('Please fill in the field') . "');</script>";

    } elseif (strlen($sectionname) > 50) {
        echo "<script>alert('" . __('The section name must not exceed 50 characters') . "');</script>";

    } else {

        $query = "INSERT INTO section (sectionname) VALUES ('$sectionname')";
        $result = mysqli_query($con, $query);

        echo "<script>
                alert('" . __('The active section has been added') . "');
                window.location.href = 'sectionadmin.php';
              </script>";
        exit();
    }
}
    
     ?>
     <?php
     #Delete section
      if(isset($id)){
      $query="DELETE From section WHERE id='$id'";
       $delete= mysqli_query($con,$query);
       if(isset($delete)){
         echo "<script>alert('" . __('done Deleted successfully') . "');</script>";
    }else{
     echo "<script>alert('" . __('It was not deleted') . "');</script>";
      }
      header("Location: sectionadmin.php");
  exit();
      }
     ?>


<!-----section start---->
<div class="content_sec">
<form action="sectionadmin.php" method="post">
  <label for="section"><?= __('New section') ?></label>
  <input type="text" name="sectionname" id="ectio">
  <br>
  <button class="add" type="submit" name="sacadd"><?= __('Add a section') ?></button>
</form>
<br>
<!-----table start---->
<table dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">

  <tr>
<th><?= __('Serial Number') ?></th>
<th><?= __('Section Name') ?></th>
<th><?= __('Delete Section') ?></th>
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
    <td><a href="sectionadmin.php? id= <?php echo $row['id']; ?>"><button type="submit" class="delete"><?= __('Delete Section') ?></button></a></td>
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