<?php
session_start();

include('../include/connected.php');
?>
<?php
@$proname=$_POST['proname'];
@$proprice=$_POST['proprice'];
@$prosection=$_POST['prosection'];
@$prodescrip=$_POST['prodescrip'];

@$prounv=$_POST['prounv'];
@$proadd=$_POST['proadd'];
// start imge
@$imgname =$_FILES['proimg']['name'];
@$imgeTmp =$_FILES['proimg']['tmp_name'];
// end img
if(isset($proadd)){
    if(empty($proname) || empty($proprice)  || empty($prosection) || empty($prosection) || empty($prodescrip))
    {
     echo '<script>alert ("الرجاء ملئ الحقل ");</script>';
}
else{
    @$proimg = rand(0,5000) . "_" . $imgname;

    move_uploaded_file($imgeTmp, '../uploads/img/' . $proimg);

$query="INSERT INTO product (proname,proimg,proprice,prosection,prodescrip,prounv) VALUES ('$proname','$proimg','$proprice','$prosection','$prodescrip','$prounv')";
$result =mysqli_query($con, $query);
if(isset($result)){
        echo '<script>alert ("تم اضافة الخدمة بنجاح");</script>';

}else{
     echo '<script>alert ("لم تتم اضافة الخدمة ");</script>';
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اضافة خدمات</title>
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
            
                <h1>اضافة خدمة</h1>
                <form action="addproduct.php" method="post" enctype="multipart/form-data">

                 <label for="name">عنوان الخدمة</label>
                <input type="text" name="proname" id="name">
            

                <label for="file">صورة  الخدمة</label>
                <input type="file" name="proimg" id="file">

                <label for="price">سعر الخدمة </label>
                <input type="text" name="proprice" id="price">

                <label for="description">تفاصيل الخدمة</label>
                <input type="text" name="prodescrip" id="description">

                <label for="prounv">توفر الخدمة</label>
                <input type="text" name="prounv" id="prounv">

                
                  <!------- start sectione---->
                 <div>
                    <label for="form_control">الاقسام  </label>
                    <select name="prosection" id="form_control">

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
                 <input class="button" type="submit" name="proadd">
                   

</input>
                </form>

                
            </div>
</maim>
    </center>
</body>
</html>