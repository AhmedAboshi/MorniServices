<?php
session_start();
include('../include/connected.php');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
</head>
<style>
    body{
        margin:0;
        padding: 0;
        background-color: #f4f4f4;
    }
    .container{
        width: 400px;
        margin: 80px auto;
        background-color: #fff;
        box-shadow:0 0 10px rgba(0,0,0,0.1);
    }
    h1{
        text-align: center;
        margin-bottom:20px;
    }
    form{
        display:flex;
        flex-direction: column;
        align-items: center;
    }

    label{
        display: block;
        margin-bottom:5px;
    }
    img{
        width:50PX;
        heigh: 100px;
        border-radius: 30px;
        text-algin: center;
        position: center;
       "display: flex;
        justify-content: center; 
        align-items: center;
          
         border: 1px solid #ccc;
    

    } 
    input[type="text"],[type="email"]{
        width: 70%;
        padding: 10px 20px;
        border:1px solid #ccc;
        margin-bottom: 15px;
    }
    button{
        width: 100%;
        padding:10px 20px;
        margin-top: 15px;
        background-color: #007bff;
        color: #fff;
        border:none;
    }
</style>
<body>
<main>
    <?php
    @$ADemail =$_POST['email'];
    @$ADpassword =$_POST['password'];
    @$ADadd =$_POST['add'];

    if(isset($ADadd)){
        if(empty($ADemail)  ||empty($ADpassword)){
            echo '<script>alert ("الرجاء ادخال الايميل وكلمة المرور");</script>';
        }
        else{
            $query="SELECT *FROM admin WHERE email='$ADemail' AND password='$ADpassword' ";
            $result=mysqli_query($con,$query);
            if(mysqli_num_rows($result) ==1){
                $_SESSION['EMAIL']=$ADemail;
                echo '<script>alert ("مرحبا بكم السيد المدير بموقع الشركه سيتم توجيهك الى لوحة تحكم الموقع");</script>';
                header("REFRESH:1; URL = newadmin.php");

            }
            else{
                echo '<script>alert ("مرحبا بكم عزرا غير مسموح لكم دخول الصفحه سيتم تحويلكم الي الصفحة الرئيسيه للموقع");</script>';
                header("REFRESH:2; URL =../index.php");
            }
        }
    }

?>

    <div class="container">
       <img src="../img/logo.jpg" alt="">
        <h1>تسجيل الدخول</h1>
    <form action="admin.php" method="post">
        
    <label for="em">البريد الالكتروني</label>
    <input type="email" name="email" id="em">
    <label for="">كلمة المرور</label>
    <input type="text" name="password" id="pass">
    <br>
    <button type="submit" name="add">تسجيل الدخول</button>
    </form>
    </div>
</main>
</body>
</html>