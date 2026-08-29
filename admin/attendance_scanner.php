<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>ماسح حضور السائق</title>

<script src="https://unpkg.com/html5-qrcode@2.3.8"></script>

<style>
body{
    font-family:Tahoma,Arial;
    background:#f4f6f9;
    margin:0;
    padding:20px;
}

.box{
    max-width:600px;
    margin:auto;
    background:#fff;
    padding:25px;
    border-radius:20px;
    text-align:center;
}

#reader{
    width:100%;
    margin:20px auto;
}

#result{
    margin-top:20px;
    padding:20px;
    background:#eee;
    border-radius:12px;
    font-size:20px;
}

button{
    background:#0d6efd;
    color:#fff;
    border:0;
    padding:12px 25px;
    border-radius:10px;
    font-size:16px;
}
</style>
</head>

<body>

<div class="box">

<h2>📷 ماسح حضور السائق</h2>

<div id="reader"></div>

<button onclick="startScanner()">
تشغيل الكاميرا
</button>

<div id="result">
بانتظار القراءة...
</div>

<form
    id="goForm"
    action="scan_attendance.php"
    method="GET"
    style="display:none;"
>

<input
    type="hidden"
    name="driver"
    id="driverValue"
>

</form>

</div>

<script>

let scanner = null;
let done = false;

function startScanner(){

    scanner = new Html5Qrcode("reader");

    scanner.start(
        { facingMode: "environment" },
        {
            fps:10,
            qrbox:250
        },

        function(decodedText){

            if(done){
                return;
            }

            done = true;

            console.log(
                "QR RESULT:",
                decodedText
            );

            document.getElementById(
                "result"
            ).innerHTML =
                "<b>تمت قراءة:</b><br>" +
                decodedText;

            /*
             * إذا كانت القيمة DRIVER_35
             */

            if(
                /^DRIVER[_-]\d+$/i.test(
                    decodedText.trim()
                )
            ){

                document.getElementById(
                    "driverValue"
                ).value =
                    decodedText.trim();

                /*
                 * انتقال مباشر إلى PHP
                 */

                setTimeout(function(){

                    document
                        .getElementById("goForm")
                        .submit();

                },1000);

            }else{

                document.getElementById(
                    "result"
                ).innerHTML +=
                    "<br><br>❌ QR ليس QR سائق";

                done = false;
            }

        },

        function(error){

            // تجاهل أخطاء البحث المستمرة

        }

    ).catch(function(error){

        document.getElementById(
            "result"
        ).innerHTML =
            "❌ خطأ تشغيل الكاميرا<br>" +
            error;

    });
}

</script>

</body>
</html>