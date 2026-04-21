<?php
session_start();

include('../include/connected.php');
?>
<?php
//select start
@$id =$_GET['id'];
if(isset($_GET['id'])){
$query = "SELECT * FROM users WHERE id ='$id'";
$result = mysqli_query($con ,$query);
if(isset($result)){
    $row = mysqli_fetch_assoc($result);
    
}
}
if(isset($_POST['update_pro'])){
    if(isset($_GET['id_new'])){
      @$username=$_POST['username'];
@$email=$_POST['email'];
@$password=$_POST['password'];
@$id_new = $_GET['id_new'];


@$proadd=$_POST['proadd'];
if(empty($username)){
    echo '<script>alert ("الرجاء ملئ الحقل ");</script>';
}
else{
   
   $query = "UPDATE users SET
    username = '$username',
    email = '$email',
    `password` = '$password'
WHERE id = '$id_new'";
$result = mysqli_query($con,$query);
if(isset($result)){
     echo '<script>alert ("تم تعديل بيانات العميل بنجاح");</script>';
     header("LOCATION:userview.php");
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
            
                <h1>تعديل بيانات العميل</h1>
                <form action="updateuser.php?id_new=<?php echo $row['id'];?>" method="post" >

                 <label for="username">اسم العميل</label>
                <input type="text" name="username" id="username" value="<?PHP echo @$row['username'];?>">
            

                                <label for="email">الايميل</label>
                <input type="text" name="email" id="email"  value="<?PHP echo @$row['email'];?>">

                <label for="password">كلمة المرور</label>
                <input type="text" name="password" id="password" value=" <?PHP echo @$row['password'];?>">

                

                
                 <input class="button" type="submit" name="update_pro" value="تعديل">
                   

</input>
                </form>

                
            </div>

</maim>
    </center>
</body>
</html>