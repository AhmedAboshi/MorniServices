<?php
session_start();

include('../include/connected.php');
?>
<?php

@$email=$_POST['email'];
@$password=$_POST['password'];
@$proadd=$_POST['proadd'];


if(isset($proadd)){
    if( empty($email)  || empty($password))
    {
     echo '<script>alert ("الرجاء ملئ الحقل ");</script>';
}
else{
   

$query="INSERT INTO admin (email,password) VALUES ('$email','$password')";
$result =mysqli_query($con, $query);
if(isset($result)){
        echo '<script>alert ("تم اضافة مستخدم بنجاح");</script>';

}else{
     echo '<script>alert ("لم تتم اضافة  ");</script>';
}
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اضافة مستخدم</title>
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
            
                <h1>اضافة مستخدم</h1>
                <form action="addadmin.php" method="post">

                 
            

                <label for="price"> الايميل </label>
                <input type="text" name="email" id="email">

                <label for="password">كلمة المرور </label>
                <input type="text" name="password" id="password">

                

    
                 <input class="button" type="submit" name="proadd">
                   

</input>
                </form>

                
            </div>
</maim>
    </center>
</body>
</html>