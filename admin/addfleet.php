<?php
session_start();

include('../include/connected.php');
?>
<?php
@$driver=$_POST['driver'];
@$plate=$_POST['plate'];
@$typefleet=$_POST['typefleet'];
@$classify=$_POST['classify'];
@$model=$_POST['model'];
@$colorfleet	=$_POST['colorfleet'];
@$work=$_POST['work'];
@$fleetadd=$_POST['fleetadd'];
// start imge
@$imgname =$_FILES['imgfleet']['name'];
@$imgeTmp =$_FILES['imgfleet']['tmp_name'];
// end img
if(isset($fleetadd)){
    if(empty($driver) || empty($plate)  || empty($typefleet) || empty($classify) || empty($model))
    {
     echo '<script>alert ("الرجاء ملئ الحقل ");</script>';
}
else{
    @$imgfleet = rand(0,5000) . "_" . $imgname;

    move_uploaded_file($imgeTmp, '../fleetimg/img/' . $imgfleet);

$query="INSERT INTO fleet (driver,imgfleet,plate,typefleet,classify,model,colorfleet,work) VALUES ('$driver','$imgfleet','$plate','$typefleet','$classify','$model','$colorfleet','$work')";
$result =mysqli_query($con,$query);
if(isset($result)){
        echo '<script>alert ("تم اضافة المركبة بنجاح");</script>';

}else{
     echo '<script>alert ("لم تتم اضافة المركبة ");</script>';
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اضافة سطحة</title>
    <link rel="stylesheet" href="style.css">
</head>
<style>
    /* start product css */
.form_product{
    width: 70%;
    margin: 5px;
    box-shadow: 0 5px 10px rgp(0,0,0,1);
}
h1{
    padding: 10px;
}
label{
    display: block;
    margin-bottom: 5px;
    font-size: 25px;
}
input{
    width: 80%;
    padding: 12px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.button{
    width: 90%;
    padding: 10px;
    margin-bottom: 15px;
    background-color: #007bff;
    border: none;
    font-size: 28px;
}
button:hover{
    background-color: #0056b3;
    color: white;
}



/* end product css */
#form_control{
    width: 80%;
    padding: 12px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
}


</style>
<body>
    <center>
        <main>
            <div class="form_product">
            
                <h1>اضافة مركبة</h1>
                <form action="addfleet.php" method="post" enctype="multipart/form-data">

                 <label for="name">المزود</label>
                <input type="text" name="driver" id="name">
            

                <label for="file">صورة  السطحة</label>
                <input type="file" name="imgfleet" id="file">

                <label for="plate">لوحة السطحة</label>
                <input type="text" name="plate" id="plate">

                <label for="typefleet">طراز السطحة</label>
                <input type="text" name="typefleet" id="typefleet">

                <label for="classify">نوع السطحة</label>
                <input type="text" name="classify" id="classify">

                <label for="model">موديل السطحة</label>
                <input type="text" name="model" id="model">

                <label for="colorfleet">لون السطحة</label>
                <input type="text" name="colorfleet" id="colorfleet">
                  <!------- start work fleet---->
                 <div>
                    <label for="form_control">عمل السطحة</label>
                    <select name="work" id="form_control">

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
                 <input class="button" type="submit" name="fleetadd">
                   

</input>
                </form>

                
            </div>
</maim>
    </center>
</body>
</html>