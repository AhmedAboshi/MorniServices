<?php
session_start();

include('../include/connected.php');
?>
<?php
//select start
@$id =$_GET['id'];
if(isset($_GET['id'])){
$query = "SELECT * FROM product WHERE id ='$id'";
$result = mysqli_query($con ,$query);
if(isset($result)){
    $row = mysqli_fetch_assoc($result);
    
}
}
if(isset($_POST['update_pro'])){
    if(isset($_GET['id_new'])){
      @$proname=$_POST['proname'];
@$proprice=$_POST['proprice'];
@$prosection=$_POST['prosection'];
@$prodescrip=$_POST['prodescrip'];
@$prounv=$_POST['prounv'];
@$proadd=$_POST['proadd'];
// start imge
@$imgname =$_FILES['proimg']['name'];
@$imgeTmp =$_FILES['proimg']['tmp_name'];
@$id_new = $_GET['id_new'];
// end img  
if(empty($prodescrip)){
    echo '<script>alert ("الرجاء ملئ الحقل ");</script>';
}
else{
    @$proimg = rand(0,5000) . "_" . $imgname;

    move_uploaded_file($imgeTmp, '../uploads/img/' . $proimg);

    $query = "UPDATE  product  SET
    proname = '$proname',
    proimg = '$proimg',
    proprice = '$proprice', 
    prosection= '$prosection',
    prodescrip = '$prodescrip',
    
    prounv = '$prounv'
    WHERE id = '$id_new'
    ";
$result = mysqli_query($con, $query);
if(isset($result)){
     echo '<script>alert ("تم تعديل الخدمة بنجاح");</script>';
     header("LOCATION:../index.php");
}else{
     echo '<script>alert ("لم يتم تعديل الخدمة ");</script>';
}

    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل خدمات</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <center>
        <main>
            <div class="form_product">
            
                <h1>اضافة خدمة</h1>
                <form action="update.php?id_new=<?php echo $row['id'];?>" method="post" enctype="multipart/form-data">

                 <label for="name">عنوان الخدمة</label>
                <input type="text" name="proname" id="name" value="<?PHP echo @$row['proname'];?>">
            

                <label for="file">صورة  الخدمة</label>
                <input type="file" name="proimg" id="file" value="<?PHP echo @$row['proimg']?>";>

                <label for="price">سعر الخدمة </label>
                <input type="text" name="proprice" id="price"  value="<?PHP echo @$row['proprice'];?>">

                <label for="description">تفاصيل الخدمة</label>
                <input type="text" name="prodescrip" id="description" value=" <?PHP echo @$row['prodescrip'];?>">

                

                <label for="unv">توفر الخدمة</label>
                <input type="text" name="prounv" id="unv" value="<?PHP echo @$row['prounv'];?>">
                  <!------- start sectione---->
                 <div>
                    <label for="form_control">الاقسام  </label>
                    <select name="prosection" id="form_control" value="<?PHP echo @$row['prosection'];?>">

                    <?php
                    $query="SELECT *  FROM section";
                    $result =mysqli_query($con, $query);
    while ($row=mysqli_fetch_assoc($result)){
        echo '<option name="section">'.$row['sectionname'].'</option>';
    }
                    ?>
                        
                 </div><br>
                 <br>
                 <!------- end sectione---->
                 <input class="button" type="submit" name="update_pro" value="UPDATE">
                   

</input>
                </form>

                
            </div>

</maim>
    </center>
</body>
</html>