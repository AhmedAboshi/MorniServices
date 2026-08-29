<?php
session_start();
include('../include/connected.php');

if(!isset($_SESSION['admin_id'])){
    header("Location: admin.php");
    exit();
}

/* =========================
   📊 جلب السجل
========================= */
$result = $con->query("
    SELECT audit_log.*, admin.name 
    FROM audit_log 
    LEFT JOIN admin ON audit_log.user = admin.name
    ORDER BY audit_log.id DESC
");?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>سجل العمليات</title>

<style>
body{
    font-family: 'Cairo', sans-serif;
    background:#f4f6f9;
    margin:0;
}

.container{
    width:90%;
    margin:30px auto;
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th, td{
    padding:12px;
    border-bottom:1px solid #ddd;
    text-align:center;
}

th{
    background:#2980b9;
    color:#fff;
}

tr:hover{
    background:#f2f2f2;
}

/* بحث */
.search-box{
    margin-bottom:15px;
    text-align:center;
}

.search-box input{
    width:50%;
    padding:10px;
    border:1px solid #ccc;
    border-radius:8px;
}
</style>

</head>
<body>

<div class="container">

<h2>📋 سجل العمليات (Audit Log)</h2>

<div class="search-box">
    <input type="text" id="search" placeholder="🔍 بحث...">
</div>

<table id="logTable">

<tr>
    <th>المستخدم</th>
    <th>العملية</th>
    <th>التفاصيل</th>
    <th>التاريخ</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>
<tr>

    <td>
        👤 <?= $row['user'] ?>
    </td>

    <td>
        <?= $row['action'] ?>
    </td>

    <td>
        <?= $row['details'] ?>
    </td>

    <td>
        📅 <?= $row['created_at'] ?>
    </td>

</tr>
<?php } ?>

</table>

</div>

<script>
/* =========================
   🔍 البحث داخل الجدول
========================= */
document.getElementById("search").addEventListener("keyup", function(){

    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#logTable tr");

    rows.forEach((row, index) => {

        if(index === 0) return;

        let text = row.innerText.toLowerCase();

        row.style.display = text.includes(value) ? "" : "none";
    });

});
</script>

</body>
</html>