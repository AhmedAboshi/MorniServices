<?php

include('../../include/connected.php');

session_start();


/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';


$success = "";
$error = "";


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



/* =========================
   حفظ السياسة
========================= */

if($_SERVER['REQUEST_METHOD']=="POST"){


    $rule_name = trim($_POST['rule_name']);

    $nationality = trim($_POST['nationality']);

    $service_id = intval($_POST['service_id']);


    $orders_from = intval($_POST['orders_from']);

    $orders_to = intval($_POST['orders_to']);


    $commission_amount = floatval($_POST['commission_amount']);

    $bonus = floatval($_POST['bonus']);

    $deduction = floatval($_POST['deduction']);


    $priority = intval($_POST['priority']);

    $status = $_POST['status'];



    if(
        $nationality=="" ||
        $service_id<=0
    ){

        $error="يرجى اختيار الجنسية والخدمة";


    }elseif($orders_from > $orders_to){


        $error="نطاق الطلبات غير صحيح";


    }else{


        $check=$con->prepare("
            SELECT id

            FROM commission_rules

            WHERE nationality=?

            AND service_id=?

            AND orders_from=?

            AND orders_to=?

            LIMIT 1
        ");


        $check->bind_param(
            "siii",
            $nationality,
            $service_id,
            $orders_from,
            $orders_to
        );


        $check->execute();


        $exists=$check->get_result();



        if($exists->num_rows>0){


            $error="هذه السياسة موجودة مسبقاً";


        }else{


            $stmt=$con->prepare("
            
            INSERT INTO commission_rules

            (
                rule_name,
                nationality,
                service_id,
                orders_from,
                orders_to,
                commission_amount,
                bonus,
                deduction,
                priority,
                status
            )

            VALUES
            (?,?,?,?,?,?,?,?,?,?)

            ");



           $stmt->bind_param(
    "ssiiidddis",
    $rule_name,
    $nationality,
    $service_id,
    $orders_from,
    $orders_to,
    $commission_amount,
    $bonus,
    $deduction,
    $priority,
    $status
);
          


            if($stmt->execute()){


                header("Location: commission_rules.php?success=1");

                exit;


            }else{


                $error="حدث خطأ أثناء الحفظ";


            }


        }

    }

}

?>

<!DOCTYPE html>

<html lang="<?= $lang ?>" dir="<?= $lang=='ar'?'rtl':'ltr' ?>">

<head>

<meta charset="UTF-8">

<title>
إضافة سياسة عمولة
</title>

<link rel="stylesheet" href="../assets/css/system.css">

</head>


<body>


<div class="container">


<div class="page-header">

<div>

<h2 class="page-title">
➕ إضافة سياسة عمولة
</h2>

<p class="page-subtitle">
إنشاء قاعدة جديدة لاحتساب عمولات السائقين
</p>

</div>


<div>

<a href="commission_rules.php"
class="btn btn-secondary">

↩ رجوع

</a>

</div>

</div>



<?php if($error!=""){ ?>

<div class="alert alert-danger">
<?= $error ?>
</div>

<?php } ?>



<form method="POST" class="card-form">


<div class="form-group">

<label>
اسم السياسة
</label>

<input 
type="text"
name="rule_name"
id="rule_name"
class="form-control"
required
>

</div>



<div class="form-row">


<div class="form-group">

<label>
الجنسية
</label>

<select name="nationality"
id="nationality"
class="form-control"
required>


<option value="">
اختر الجنسية
</option>


<?php while($nat=mysqli_fetch_assoc($nationalities)){ ?>


<option value="<?= htmlspecialchars($nat['nationality']) ?>">

<?= htmlspecialchars($nat['nationality']) ?>

</option>


<?php } ?>


</select>

</div>



<div class="form-group">

<label>
الخدمة
</label>


<select name="service_id"
id="service_id"
class="form-control"
required>


<option value="">
اختر الخدمة
</option>


<?php while($service=mysqli_fetch_assoc($services)){ ?>


<option value="<?= $service['id'] ?>">

<?= htmlspecialchars($service['service_name']) ?>

</option>


<?php } ?>


</select>


</div>


</div>

<!-- =========================
     نطاق الطلبات
========================= -->

<div class="form-row">


<div class="form-group">

<label>
عدد الطلبات من
</label>

<input
type="number"
name="orders_from"
class="form-control"
value="0"
min="0"
required
>

</div>



<div class="form-group">

<label>
عدد الطلبات إلى
</label>

<input
type="number"
name="orders_to"
class="form-control"
value="0"
min="0"
required
>

</div>


</div>



<!-- =========================
     المبالغ
========================= -->

<div class="form-row">


<div class="form-group">

<label>
قيمة العمولة
</label>

<input
type="number"
step="0.01"
name="commission_amount"
class="form-control"
value="0"
required
>

</div>



<div class="form-group">

<label>
المكافأة
</label>

<input
type="number"
step="0.01"
name="bonus"
class="form-control"
value="0"
>

</div>



<div class="form-group">

<label>
الخصم
</label>

<input
type="number"
step="0.01"
name="deduction"
class="form-control"
value="0"
>

</div>


</div>



<!-- =========================
     الأولوية والحالة
========================= -->

<div class="form-row">


<div class="form-group">

<label>
الأولوية
</label>

<input
type="number"
name="priority"
class="form-control"
value="1"
min="1"
>

</div>



<div class="form-group">

<label>
الحالة
</label>


<select name="status"
class="form-control">


<option value="active">
نشطة
</option>


<option value="inactive">
غير نشطة
</option>


</select>


</div>


</div>



<!-- =========================
     الأزرار
========================= -->


<div class="form-actions">


<button
type="submit"
class="btn btn-primary">

💾 حفظ السياسة

</button>



<a href="commission_rules.php"
class="btn btn-secondary">

إلغاء

</a>


</div>



</form>


</div>


<script>


/* =========================
   إنشاء اسم السياسة تلقائياً
========================= */


const nationality =
document.getElementById('nationality');


const service =
document.getElementById('service_id');


const ruleName =
document.getElementById('rule_name');



function generateRuleName(){


let nat =
nationality.options[nationality.selectedIndex]?.text || "";


let srv =
service.options[service.selectedIndex]?.text || "";



if(nat && srv){


ruleName.value =
nat + " - " + srv;


}


}



nationality.addEventListener(
'change',
generateRuleName
);


service.addEventListener(
'change',
generateRuleName
);


</script>


</body>

</html>