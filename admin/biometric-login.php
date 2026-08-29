<?php
session_start();
include('../include/connected.php');
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>الدخول بالبصمة</title>

<style>

body{
    font-family:tahoma;
    background:#f2f2f2;
    text-align:center;
    padding-top:100px;
}

button{
    background:#28a745;
    color:#fff;
    border:none;
    padding:15px 30px;
    border-radius:10px;
    cursor:pointer;
    font-size:20px;
}

</style>

</head>
<body>

<h2>تسجيل الدخول بالبصمة</h2>

<button onclick="loginBiometric()">
    الدخول بالبصمة
</button>

<script>

async function loginBiometric(){

    try{

        const publicKey = {
            challenge: new Uint8Array(32),
            timeout: 60000,
            userVerification: "required"
        };

        const assertion = await navigator.credentials.get({
            publicKey
        });

        const rawId = btoa(String.fromCharCode(...new Uint8Array(assertion.rawId)));

        fetch("biometric-verify.php",{
            method:"POST",
            headers:{
                "Content-Type":"application/x-www-form-urlencoded"
            },
            body:"credential_id="+encodeURIComponent(rawId)
        })
        .then(res=>res.text())
        .then(data=>{

            if(data === "success"){
                window.location.href="index.php";
            }else{
                alert("فشل التحقق");
            }

        });

    }catch(error){
        alert("فشل تسجيل الدخول بالبصمة");
    }

}

</script>

</body>
</html>