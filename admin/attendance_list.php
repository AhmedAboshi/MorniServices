<?php

session_start();

include('../include/connected.php');

date_default_timezone_set('Asia/Riyadh');

// الفلاتر

$search = $_GET['search'] ?? '';

$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to'] ?? '';

$status_filter = $_GET['status'] ?? '';

// عدد السجلات في الصفحة

$limit = 25;


// الصفحة الحالية

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;


if($page < 1){
    $page = 1;
}


$offset = ($page - 1) * $limit;

/*==================================
إحصائيات اليوم
==================================*/

$today = date('Y-m-d');

$totalToday = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM attendance
WHERE attendance_date='$today'
"))['total'];

$presentToday = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM attendance
WHERE attendance_date='$today'
AND status='present'
"))['total'];

$lateToday = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM attendance
WHERE attendance_date='$today'
AND status='late'
"))['total'];

$absentToday = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) total
FROM attendance
WHERE attendance_date='$today'
AND status='absent'
"))['total'];


/*==================================
جلب سجلات الحضور
==================================*/

$where = " WHERE 1 ";


if($search != ''){

$search_safe = mysqli_real_escape_string($con,$search);

$where .= "
AND drivers.name LIKE '%$search_safe%'
";

}



if($date_from != '' && $date_to != ''){

    $where .= "
    AND attendance.attendance_date 
    BETWEEN '$date_from' AND '$date_to'
    ";

}
elseif($date_from != ''){

    $where .= "
    AND attendance.attendance_date >= '$date_from'
    ";

}
elseif($date_to != ''){

    $where .= "
    AND attendance.attendance_date <= '$date_to'
    ";

}



if($status_filter != ''){

$where .= "
AND attendance.status='$status_filter'
";

}



$sql = "
SELECT
attendance.*,
drivers.name,
drivers.imagedriver

FROM attendance

LEFT JOIN drivers
ON attendance.driver_id = drivers.id

$where

ORDER BY attendance.attendance_date DESC,
attendance.id DESC

LIMIT $offset,$limit
";
$totalRows = mysqli_fetch_assoc(mysqli_query($con,"
SELECT COUNT(*) AS total

FROM attendance

LEFT JOIN drivers
ON attendance.driver_id = drivers.id

$where

"))['total'];


$totalPages = ceil($totalRows / $limit);

$result = mysqli_query($con,$sql);

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>
سجل حضور السائقين
</title>

<style>

body{

font-family:Arial;
background:#f4f6f9;
margin:0;
padding:20px;

}

.container{

width:95%;
margin:auto;
background:#fff;
padding:20px;
border-radius:15px;
box-shadow:0 5px 20px rgba(0,0,0,.08);

}

h2{

margin-bottom:20px;
color:#0d6efd;

}

table{

width:100%;
border-collapse:collapse;

}

table th{

background:#0d6efd;
color:#fff;
padding:12px;

}

table td{

padding:10px;
border-bottom:1px solid #eee;
text-align:center;

}

.badge{

padding:6px 12px;
border-radius:20px;
color:#fff;
font-size:14px;

}

.present{

background:#198754;

}

.late{

background:#ffc107;
color:#000;

}

.absent{

background:#dc3545;

}

.cards{

display:flex;
gap:20px;
margin-bottom:25px;
flex-wrap:wrap;

}

.card{

flex:1;
min-width:180px;
background:#fff;
border-radius:15px;
padding:20px;
text-align:center;
box-shadow:0 3px 10px rgba(0,0,0,.08);

}

.card h3{

margin:0;
font-size:32px;

}

.card p{

margin-top:10px;
color:#666;
font-size:16px;

}

.blue{

border-top:5px solid #0d6efd;

}

.green{

border-top:5px solid #198754;

}

.orange{

border-top:5px solid #ffc107;

}

.red{

border-top:5px solid #dc3545;

}

.pagination{

margin-top:25px;
text-align:center;

}


.pagination a{

display:inline-block;
padding:8px 15px;
margin:3px;
background:#0d6efd;
color:white;
text-decoration:none;
border-radius:8px;

}


.pagination .active{

background:#198754;

}

.filters{

background:#fff;
padding:20px;
border-radius:15px;
margin-bottom:25px;
box-shadow:0 3px 15px rgba(0,0,0,.08);

display:flex;
gap:15px;
align-items:center;
flex-wrap:wrap;

}


.filters input,
.filters select{

padding:12px 15px;
border:1px solid #ddd;
border-radius:10px;
font-size:15px;
min-width:200px;

}


.filters button{

background:#0d6efd;
color:white;
border:none;
padding:12px 25px;
border-radius:10px;
cursor:pointer;

}


.reset-btn{

background:#6c757d;
color:white;
padding:12px 20px;
border-radius:10px;
text-decoration:none;

}

.attendance-filter-card{

    background:#fff;
    padding:20px;
    border-radius:15px;
    box-shadow:0 3px 15px rgba(0,0,0,.08);
    margin-bottom:25px;

}


.filter-title{

    font-size:18px;
    font-weight:bold;
    margin-bottom:20px;
    color:#333;

}



.attendance-filter-form{

    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:15px;
    align-items:end;

}



.filter-item{

    display:flex;
    flex-direction:column;

}



.filter-item label{

    font-size:14px;
    font-weight:600;
    margin-bottom:7px;

}



.filter-item input,
.filter-item select{

    height:42px;
    border:1px solid #ddd;
    border-radius:10px;
    padding:0 12px;
    background:#fafafa;

}



.filter-actions{

    display:flex;
    gap:10px;

}



.filter-actions button{

    height:42px;
    border:none;
    background:#0d6efd;
    color:white;
    border-radius:10px;
    padding:0 25px;
    cursor:pointer;

}



.reset-btn{

    height:42px;
    display:flex;
    align-items:center;
    padding:0 18px;
    background:#6c757d;
    color:white;
    border-radius:10px;
    text-decoration:none;

}



/* للجوال */

@media(max-width:768px){

.attendance-filter-form{

    grid-template-columns:1fr;

}

}

.attendance-tools{

    display:flex;
    justify-content:flex-end;
    gap:12px;
    margin-bottom:20px;
    flex-wrap:wrap;

}

.attendance-tools a{

    text-decoration:none;
    color:#fff;
    padding:11px 18px;
    border-radius:10px;
    font-weight:600;
    transition:.25s;

}

.scan-btn{
    background:#0d6efd;
}

.scan-btn:hover{
    background:#0b5ed7;
}

.excel-btn{
    background:#198754;
}

.excel-btn:hover{
    background:#157347;
}

.pdf-btn{
    background:#dc3545;
}

.pdf-btn:hover{
    background:#bb2d3b;
}

.table-responsive{

overflow-x:auto;

}

.attendance-table{

width:100%;
min-width:1100px;
border-collapse:collapse;
background:#fff;
border-radius:12px;
overflow:hidden;
box-shadow:0 2px 12px rgba(0,0,0,.08);

}

.attendance-table th{

padding:18px 15px;
font-size:17px;
background:#0d6efd;
color:#fff;
padding:14px;
text-align:center;

}

.attendance-table td{

padding:18px 15px;
font-size:17px;
text-align:center;
border-bottom:1px solid #eee;

}

.attendance-table tbody tr:hover{

background:#f8fbff;

}

.driver-cell{

display:flex;
align-items:center;
gap:10px;

}

.driver-photo{

width:55px;
height:55px;
border-radius:50%;
object-fit:cover;
border:2px solid #ddd;
margin-left:10px;

}

.driver-name{

font-weight:600;

}

.badge{

padding:6px 12px;
border-radius:20px;
color:#fff;
font-size:13px;

}

.badge.present{

background:#198754;

}

.badge.late{

background:#ffc107;
color:#000;

}

.badge.absent{

background:#dc3545;

}

.action-buttons{

display:flex;
justify-content:center;
gap:8px;

}

.action-buttons a{

width:36px;
height:36px;

display:flex;
align-items:center;
justify-content:center;

border-radius:8px;
text-decoration:none;

font-size:16px;

transition:.25s;

}

.btn-view{

background:#0d6efd;
color:#fff;

}

.btn-view:hover{

background:#0b5ed7;
transform:scale(1.05);

}

.btn-edit{

background:#ffc107;
color:#000;

}

.btn-edit:hover{

background:#ffca2c;
transform:scale(1.05);

}

.btn-delete{

background:#dc3545;
color:#fff;

}

.btn-delete:hover{

background:#bb2d3b;
transform:scale(1.05);

}
.attendance-table th,
.attendance-table td{
    vertical-align: middle;
}
.attendance-table th:nth-child(2),
.attendance-table td:nth-child(2){
    width:320px;
}
.attendance-table th:nth-child(4),
.attendance-table td:nth-child(4),

.attendance-table th:nth-child(5),
.attendance-table td:nth-child(5){

    width:110px;
}

.action-buttons{

    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;

}

.action-buttons a{

    width:38px;
    height:38px;

    display:flex;
    align-items:center;
    justify-content:center;

}
</style>

</head>

<body>

<div class="container">

<h2>
📅 سجل حضور السائقين
</h2>

<div class="filters">

<div class="attendance-filter-card">

    <div class="filter-title">
        <i>🔍</i>
        البحث في سجل الحضور
    </div>


    <form method="get" class="attendance-filter-form">


        <div class="filter-item">

            <label>
                اسم السائق
            </label>

            <input 
            type="text"
            name="search"
            placeholder="👤 بحث باسم السائق"
            value="<?=htmlspecialchars($search)?>"
            >

        </div>



        <div>
<label>من تاريخ</label>
<input type="date" name="date_from"
value="<?= $_GET['date_from'] ?? '' ?>">
</div>


<div>
<label>إلى تاريخ</label>
<input type="date" name="date_to"
value="<?= $_GET['date_to'] ?? '' ?>">
</div>


        <div class="filter-item">

            <label>
                الحالة
            </label>

            <select name="status">

                <option value="">
                📊 كل الحالات
                </option>


                <option value="present"
                <?=($status_filter=='present')?'selected':''?>
                >
                🟢 حاضر
                </option>


                <option value="late"
                <?=($status_filter=='late')?'selected':''?>
                >
                🟡 متأخر
                </option>


                <option value="absent"
                <?=($status_filter=='absent')?'selected':''?>
                >
                🔴 غائب
                </option>


            </select>

        </div>



        <div class="filter-actions">

            <button type="submit">
                🔍 بحث
            </button>


            <a class="reset-btn" href="attendance_list.php">
                🔄 إعادة
            </a>

        </div>


    </form>

</div>



<div class="cards">

<div class="card blue">

<h3><?= $totalToday ?></h3>

<p>👥 سجلات اليوم</p>

</div>

<div class="card green">

<h3><?= $presentToday ?></h3>

<p>✅ حاضر</p>

</div>

<div class="card orange">

<h3><?= $lateToday ?></h3>

<p>🟡 متأخر</p>

</div>

<div class="card red">

<h3><?= $absentToday ?></h3>

<p>🔴 غائب</p>

</div>

</div>

<div class="attendance-tools">

<a href="scan_attendance.php" class="scan-btn">
    📷 ماسح QR
</a>


<a href="attendance_excel.php?
search=<?=urlencode($search)?>
&date_from=<?=$date_from?>
&date_to=<?=$date_to?>
&status=<?=$status_filter?>"
class="excel-btn">
    📄 Excel
</a>


<a href="attendance_pdf.php?
search=<?=urlencode($search)?>
&date_from=<?=$date_from?>
&date_to=<?=$date_to?>
&status=<?=$status_filter?>"
class="pdf-btn">
    🖨 PDF
</a>

</div>

<div class="table-responsive">

<table class="attendance-table">

<thead>

<tr>

<th>#</th>



<th width="320">السائق</th>

<th>التاريخ</th>

<th>الدخول</th>

<th>الخروج</th>

<th>مدة العمل</th>

<th>الحالة</th>

<th width="140">الإجراءات</th>

</tr>

</thead>

<tbody>

<?php

$i=1;

while($row=mysqli_fetch_assoc($result)){

// حساب مدة العمل
    $workDuration = '-';

    if(!empty($row['check_in']) && !empty($row['check_out'])){

        $start = new DateTime($row['check_in']);
        $end   = new DateTime($row['check_out']);

        $diff = $start->diff($end);

        $workDuration =
            $diff->h . " ساعة " .
            $diff->i . " دقيقة";
    }


$statusClass=$row['status'];

$statusText="";

switch($row['status']){

case 'present':
$statusText="🟢 حاضر";
break;

case 'late':
$statusText="🟡 متأخر";
break;

case 'absent':
$statusText="🔴 غائب";
break;

default:
$statusText=$row['status'];

}



$defaultImage = "../assets/images/user.png";

$image = $defaultImage;

if (!empty($row['imagedriver'])) {

    $file = "../uploads/" . $row['imagedriver'];

    if (file_exists($file)) {
        $image = $file;
    }

}
?>


<tr>

<td><?= $i++ ?></td>

<td>

<div class="driver-info">

    <img src="<?= $image ?>" class="driver-photo" alt="صورة السائق">

    <div>

        <div class="driver-name">
            <?= htmlspecialchars($row['name']) ?>
        </div>

        <div class="driver-id">
            #<?= $row['driver_id'] ?>
        </div>

    </div>

</div>

</td>

<td>

<?= date('Y-m-d',strtotime($row['attendance_date'])) ?>

</td>

<td>

<?= $row['check_in'] ?: '-' ?>

</td>

<td>

<?= $row['check_out'] ?: '-' ?>

</td>

<td>
    <?= $workDuration ?>
</td>

<td>

<span class="badge <?= $statusClass ?>">

<?= $statusText ?>

</span>

</td>

<td>

<div class="action-buttons">

    <a href="attendance_view.php?id=<?= $row['id'] ?>"
       class="btn-view"
       title="عرض">
        👁
    </a>

    <a href="attendance_edit.php?id=<?= $row['id'] ?>"
       class="btn-edit"
       title="تعديل">
        ✏️
    </a>

    <a href="attendance_delete.php?id=<?= $row['id'] ?>"
       class="btn-delete"
       title="حذف"
       onclick="return confirm('هل تريد حذف سجل الحضور؟');">
        🗑
    </a>

</div>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

<div class="pagination">

<?php for($p=1;$p<=$totalPages;$p++){ ?>


<a 
href="?page=<?=$p?>"
class="<?=($page==$p)?'active':''?>"
>

<?=$p?>

</a>


<?php } ?>

</div>

</body>

</html>