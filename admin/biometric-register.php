<?php
session_start();
include('../include/connected.php');

if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تفعيل البصمة</title>

<style>
body{
    font-family:tahoma;
    background:#f5f5f5;
    text-align:center;
    padding-top:100px;
}
button{
    background:#007bff;
    color:#fff;
    border:none;
    padding:15px 30px;
    border-radius:10px;
    cursor:pointer;
    font-size:18px;
}
</style>

</head>
<body>

<h2>تفعيل تسجيل الدخول بالبصمة</h2>

<button onclick="registerBiometric()">
    تفعيل البصمة
</button>

<script>

async function registerBiometric(){

    try{

        const publicKey = {
            challenge: new Uint8Array(32),
            rp: {
                name: "<?= setting('system_name') ?>"
            },
            user: {
                id: new Uint8Array([<?php echo $admin_id; ?>]),
                name: "admin",
                displayName: "Admin"
            },
            pubKeyCredParams: [
                {
                    type: "public-key",
                    alg: -7
                }
            ],
            authenticatorSelection: {
                authenticatorAttachment: "platform",
                userVerification: "required"
            },
            timeout: 60000,
            attestation: "direct"
        };

        const credential = await navigator.credentials.create({
            publicKey
        });

        const rawId = btoa(String.fromCharCode(...new Uint8Array(credential.rawId)));

        fetch("biometric-save.php",{
            method:"POST",
            headers:{
                "Content-Type":"application/x-www-form-urlencoded"
            },
            body:"credential_id="+encodeURIComponent(rawId)
        })
        .then(res=>res.text())
        .then(data=>{
            alert(data);
        });

    }catch(error){
        alert("الجهاز لا يدعم البصمة أو تم الإلغاء");
    }

}

</script>

</body>
</html>