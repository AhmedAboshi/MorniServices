<?php

include('../../include/connected.php');

session_start();

/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';


/* =========================
   رقم السياسة
========================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0){
    die("رقم السياسة غير صحيح");
}


/* =========================
   جلب بيانات السياسة
========================= */

$stmt = $con->prepare("
SELECT *
FROM commission_rules
WHERE id=?
LIMIT 1
");

$stmt->bind_param("i",$id);

$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows==0){
    die("السياسة غير موجودة");
}

$rule = $result->fetch_assoc();


/* =========================
   جلب الجنسيات
========================= */

$nationalities = mysqli_query($con,"
SELECT DISTINCT nationality
FROM drivers
WHERE nationality IS NOT NULL
AND nationality <> ''
ORDER BY nationality
");


/* =========================
   جلب الخدمات
========================= */

$services = mysqli_query($con,"
SELECT
id,
service_name
FROM commission_services
WHERE status='active'
ORDER BY service_name
");


$error = "";
$success = "";
/* =========================
   حفظ التعديل
========================= */

if($_SERVER['REQUEST_METHOD']=="POST"){

    $rule_name = trim($_POST['rule_name']);

    $nationality = trim($_POST['nationality']);

    $service_id = (int)$_POST['service_id'];

    $orders_from = (int)$_POST['orders_from'];

    $orders_to = (int)$_POST['orders_to'];

    $commission_amount = (float)$_POST['commission_amount'];

    $bonus = (float)$_POST['bonus'];

    $deduction = (float)$_POST['deduction'];

    $priority = (int)$_POST['priority'];

    $status = $_POST['status'];


    if($orders_from > $orders_to){

        $error = "عدد الطلبات غير صحيح.";

    }else{

        /* التأكد من عدم وجود سياسة مشابهة */

        $check = $con->prepare("
        SELECT id
        FROM commission_rules
        WHERE nationality=?
        AND service_id=?
        AND orders_from=?
        AND orders_to=?
        AND id<>?
        LIMIT 1
        ");

        $check->bind_param(
            "siiii",
            $nationality,
            $service_id,
            $orders_from,
            $orders_to,
            $id
        );

        $check->execute();

        if($check->get_result()->num_rows>0){

            $error="توجد سياسة بنفس البيانات.";

        }else{

            $stmt=$con->prepare("
            UPDATE commission_rules
            SET
                rule_name=?,
                nationality=?,
                service_id=?,
                orders_from=?,
                orders_to=?,
                commission_amount=?,
                bonus=?,
                deduction=?,
                priority=?,
                status=?
            WHERE id=?
            ");

            $stmt->bind_param(
                "ssiiidddisi",
                $rule_name,
                $nationality,
                $service_id,
                $orders_from,
                $orders_to,
                $commission_amount,
                $bonus,
                $deduction,
                $priority,
                $status,
                $id
            );

            if($stmt->execute()){

                header("Location: commission_rules.php?updated=1");

                exit;

            }else{

                $error="حدث خطأ أثناء التعديل.";

            }

        }

    }

}
?>
<!DOCTYPE html>

<html lang="<?= $lang ?>" dir="<?= $lang=='ar'?'rtl':'ltr' ?>">

<head>

<meta charset="UTF-8">

<title>تعديل سياسة العمولة</title>

<link rel="stylesheet" href="../assets/css/system.css?v=<?= time() ?>">

</head>

<body>

<div class="container">

<div class="page-header">

<div>

<h2 class="page-title">
✏️ تعديل سياسة العمولة
</h2>

<p class="page-subtitle">
تعديل بيانات سياسة احتساب العمولة
</p>

</div>

<div>

<a href="commission_rules.php" class="btn btn-secondary">
رجوع
</a>

</div>

</div>

<?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<form method="POST">
    <!-- =========================
     بيانات السياسة
========================= -->

<div class="row">


<div class="col-md-6 mb-3">

<label class="form-label">
اسم السياسة
</label>

<input type="text"
       name="rule_name"
       id="rule_name"
       class="form-control"
       value="<?= htmlspecialchars($rule['rule_name']) ?>"
       readonly>

</div>



<div class="col-md-6 mb-3">

<label class="form-label">
الجنسية
</label>

<select name="nationality"
        id="nationality"
        class="form-control"
        onchange="updateRuleName()">


<option value="">
اختر الجنسية
</option>


<?php foreach($nationalities as $nat): ?>

<option value="<?= htmlspecialchars($nat['nationality']) ?>"
<?php if($rule['nationality']==$nat['nationality']) echo 'selected'; ?>>

<?= htmlspecialchars($nat['nationality']) ?>

</option>

<?php endforeach; ?>


</select>

</div>



<div class="col-md-6 mb-3">

<label class="form-label">
نوع الخدمة
</label>


<select name="service_id"  
        id="service_type"
        class="form-control"
        onchange="updateRuleName()">


<option value="">
اختر الخدمة
</option>


<?php foreach($services as $service): ?>


<option value="<?= $service['id'] ?>"
<?php if($rule['service_id']==$service['id']) echo 'selected'; ?>>


<?= htmlspecialchars($service['service_name']) ?>


</option>


<?php endforeach; ?>


</select>


</div>





<div class="col-md-6 mb-3">

<label class="form-label">
من عدد الطلبات
</label>

<input type="number"
       name="orders_from"
       class="form-control"
       value="<?= $rule['orders_from'] ?>">

</div>



<div class="col-md-6 mb-3">

<label class="form-label">
إلى عدد الطلبات
</label>

<input type="number"
       name="orders_to"
       class="form-control"
       value="<?= $rule['orders_to'] ?>">

</div>




<div class="col-md-6 mb-3">

<label class="form-label">
العمولة
</label>

<input type="number"
       step="1"
       min="0"
       name="commission_amount"
       class="form-control"
       value="<?= $rule['commission_amount'] ?>">

</div>




<div class="col-md-6 mb-3">

<label class="form-label">
المكافأة
</label>

<input type="number"
       step="1"
       min="0"
       name="bonus"
       class="form-control"
       value="<?= $rule['bonus'] ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">
الخصم
</label>

<input type="number"
       step="1"
       min="0"
       name="deduction"
       class="form-control"
       value="<?= $rule['deduction'] ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">
الحالة
</label>

<select name="status" class="form-control">

<option value="active"
<?= $rule['status']=='active'?'selected':'' ?>>
فعالة
</option>

<option value="inactive"
<?= $rule['status']=='inactive'?'selected':'' ?>>
موقفة
</option>

</select>

</div>

<div class="mt-4">


<button type="submit" class="btn btn-primary">

<i class="fa fa-save"></i>
حفظ التعديل

</button>



<a href="commissions_dashboard.php"
   class="btn btn-secondary">

إلغاء

</a>


</div>

<script>


function updateRuleName(){


let nationality =
document.getElementById('nationality');


let service =
document.getElementById('service_type');



let nationalityText =
nationality.options[nationality.selectedIndex].text;



let serviceText =
service.options[service.selectedIndex].text;



if(nationality.value && service.value){


document.getElementById('rule_name').value =
"عمولة " + nationalityText + " - " + serviceText;


}



}



</script>