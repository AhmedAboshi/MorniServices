<?php
session_start();

include('../include/connected.php');
?>
<?php
//select start
@$id =$_GET['id'];
if(isset($_GET['id'])){
$query = "SELECT * FROM fleet WHERE id ='$id'";
$result = mysqli_query($con ,$query);
if(isset($result)){
    $row = mysqli_fetch_assoc($result);
    
}
}
if(isset($_POST['update_pro'])){
    if(isset($_GET['id_new'])){
      @$driver=$_POST['driver'];
@$plate=$_POST['plate'];
@$typefleet=$_POST['typefleet'];
@$classify=$_POST['classify'];
@$model=$_POST['model'];
@$colorfleet=$_POST['colorfleet'];
@$work=$_POST['work'];

@$proadd=$_POST['proadd'];
// start imge
@$imgname =$_FILES['imgfleet']['name'];
@$imgeTmp =$_FILES['imgfleet']['tmp_name'];
@$id_new = $_GET['id_new'];
// end img  
if(empty($driver)){
    echo '<script>alert ("الرجاء ملئ الحقل ");</script>';
}
else{
    @$imgfleet = rand(0,5000) . "_" . $imgname;

    move_uploaded_file($imgeTmp, '../fleetimg/img/' . $imgfleet);

    $query = "UPDATE  fleet  SET
    driver = '$driver',
    imgfleet = '$imgfleet',
    plate = '$plate', 
    typefleet= '$typefleet',
    classify = '$classify',
    model = '$model',
colorfleet = '$colorfleet',
work = '$work'
    WHERE id = '$id_new'
    ";
$result = mysqli_query($con,$query);
if(isset($result)){
     echo '<script>alert ("تم تعديل بيانات المركبة بنجاح");</script>';
     header("LOCATION:fleet.php");
}else{
     echo '<script>alert ("لم يتم التعديل  ");</script>';
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
    <title> تعديل بيانات المركبة</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <center>
        <main>
            <div class="form_product">
            
                <h1>تعديل بيانات المركبة</h1>
                <form action="updatefleet.php?id_new=<?php echo $row['id'];?>" method="post" enctype="multipart/form-data">

                 <label for="driver">المزود</label>
                <input type="text" name="driver" id="name" value="<?PHP echo @$row['driver'];?>">
            

                <label for="file">صورة  السطحة</label>
                <input type="file" name="imgfleet" id="file" value="<?PHP echo @$row['imgfleet']?>";>

                <label for="plate">لوحة السطحة </label>
                <input type="text" name="plate" id="plate"  value="<?PHP echo @$row['plate'];?>">

                <label for="typefleet">طراز المركبة</label>
                <input type="text" name="typefleet" id="typefleet" value=" <?PHP echo @$row['typefleet'];?>">

                

                <label for="classify">نوع السطحة</label>
                <input type="text" name="classify" id="classify" value="<?PHP echo @$row['classify'];?>">

                <label for="model">موديل السطحة</label>
                <input type="text" name="model" id="model" value="<?PHP echo @$row['model'];?>">

                <label for="colorfleet">لون السطحة</label>
                <input type="text" name="colorfleet" id="colorfleet" value="<?PHP echo @$row['colorfleet'];?>">

                  <!------- start sectione---->
                 <div>
                    <label for="form_control">منطقة العمل</label>
                    <select name="work" id="form_control" value="<?PHP echo @$row['work'];?>">

                    <?php
                    $query="SELECT *  FROM fleet";
                    $result =mysqli_query($con, $query);
    while ($row=mysqli_fetch_assoc($result)){
        echo '<option name="work">'.$row['work'].'</option>';
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