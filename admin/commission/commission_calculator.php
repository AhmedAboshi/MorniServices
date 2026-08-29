<?php

include('../../include/connected.php');
session_start();

/* =========================
   اللغة
========================= */
$lang = $_GET['lang'] ?? 'ar';

/* =========================
   حماية الدخول
========================= */
// if(!isset($_SESSION['admin_id'])){
//     header("Location: admin.php");
//     exit;
// }

/* =========================
   تحميل السائقين
========================= */
$drivers = [];

$stmt = $con->prepare("
SELECT
    id,
    name
FROM drivers
ORDER BY name ASC
");

$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $drivers[] = $row;
}

$stmt->close();


/* =========================
   تحميل الخدمات
========================= */
$services = [];

$stmt = $con->prepare("
SELECT
    id,
    service_name,
    calculation_type
FROM commission_services
WHERE status='active'
ORDER BY service_name ASC
");

$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $services[] = $row;
}

$stmt->close();


/* =========================
   تحميل الجنسيات
========================= */
$nationalities = [];

$stmt = $con->prepare("
SELECT DISTINCT nationality
FROM commission_rules
WHERE status='active'
ORDER BY nationality ASC
");

$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $nationalities[] = $row['nationality'];
}

$stmt->close();


/* =========================
   إحصائيات أعلى الصفحة
========================= */

$totalDrivers = count($drivers);

$totalServices = count($services);

$totalRules = 0;

$stmt = $con->prepare("
SELECT COUNT(*) total
FROM commission_rules
WHERE status='active'
");

$stmt->execute();

$stmt->bind_result($totalRules);

$stmt->fetch();

$stmt->close();


$commissionResult = null;

$appliedRule = null;

$commissionPreview = null;

$bonus_extra = 0;

$deduction_extra = 0;

$bonus_reason = '';

$deduction_reason = '';




if(isset($_POST['calculate'])){

$bonus_reason = $_POST['bonus_reason'] ?? '';

$deduction_reason = $_POST['deduction_reason'] ?? '';

    $driver_id = intval($_POST['driver_id']);

    $service_id = intval($_POST['service_id']);

    $nationality = $_POST['nationality'];

    $orders_count = intval($_POST['orders_count']);

    

    $bonus_extra = floatval($_POST['bonus_extra'] ?? 0);

$deduction_extra = floatval($_POST['deduction_extra'] ?? 0);


$bonus_reason = $_POST['bonus_reason'] ?? '';

$deduction_reason = $_POST['deduction_reason'] ?? '';



    /*
       البحث عن سياسة العمولة
    */

   $stmt = $con->prepare("

SELECT 

commission_rules.*,

commission_services.calculation_type

FROM commission_rules

LEFT JOIN commission_services

ON commission_rules.service_id = commission_services.id

WHERE commission_rules.status='active'

AND commission_rules.service_id=?

AND commission_rules.nationality=?

AND commission_rules.orders_from <= ?

AND commission_rules.orders_to >= ?

ORDER BY 
commission_rules.orders_from DESC,
commission_rules.priority ASC

LIMIT 1

");


    $stmt->bind_param(
        "isii",
        $service_id,
        $nationality,
        $orders_count,
        $orders_count
    );


    $stmt->execute();


    $rule = $stmt->get_result()->fetch_assoc();


    if($rule){


       $commissionAmount = floatval($rule['commission_amount']);


if($rule['calculation_type'] == 'orders'){

    // عمولة حسب عدد الطلبات

    $base = $orders_count * $commissionAmount;


}else{

    // عمولة ثابتة

    $base = $commissionAmount;

}


$bonus = floatval($rule['bonus']);

$deduction = floatval($rule['deduction']);


$bonus = floatval($rule['bonus']) + $bonus_extra;

$deduction = floatval($rule['deduction']) + $deduction_extra;


$net = ($base + $bonus) - $deduction;


        $commissionResult = [

'base'=>$base,

'bonus'=>$bonus,

'deduction'=>$deduction,

'bonus_extra'=>$bonus_extra,

'deduction_extra'=>$deduction_extra,

'bonus_reason'=>$bonus_reason,

'deduction_reason'=>$deduction_reason,

'net'=>$net

];

$commissionPreview = [

    'driver_id' => $driver_id,

    'orders_count' => $orders_count,

    'base' => $base,

    'bonus' => $bonus,

    'deduction' => $deduction,

    'bonus_reason' => $bonus_reason,

    'deduction_reason' => $deduction_reason,

    'net' => $net

];


        $appliedRule=$rule;


    }


}
/* =========================
   حفظ كشف العمولة
========================= */

if(isset($_POST['save_commission'])){

    $driver_id        = intval($_POST['driver_id'] ?? 0);
    $service_id       = intval($_POST['service_id'] ?? 0);
    $nationality      = trim($_POST['nationality'] ?? '');

    $orders_count     = intval($_POST['orders_count'] ?? 0);

    $commission_rate  = floatval($_POST['commission_rate'] ?? 0);
    $base_commission  = floatval($_POST['base_commission'] ?? 0);
    $total_bonus      = floatval($_POST['total_bonus'] ?? 0);
    $total_deduction  = floatval($_POST['total_deduction'] ?? 0);
    $net_commission   = floatval($_POST['net_commission'] ?? 0);

    $bonus_reason     = trim($_POST['bonus_reason'] ?? '');
    $deduction_reason = trim($_POST['deduction_reason'] ?? '');


    /* =========================
       التحقق من البيانات
    ========================= */

    if($driver_id <= 0){

        die('خطأ: لم يتم اختيار السائق.');

    }

    if($service_id <= 0){

        die('خطأ: لم يتم اختيار الخدمة.');

    }

    if($orders_count < 0){

        die('خطأ: عدد الطلبات غير صحيح.');

    }


  

/* =========================
   تحديد الأسبوع والفترة
   نظام العمولات:
   الأحد → السبت
   7 أيام كاملة
========================= */


/* تاريخ اليوم */

$today = new DateTime();


/* السنة الحالية */

$year_number = (int)$today->format('Y');


/*
 * رقم الأسبوع الخاص بنظامنا
 *
 * الأحد = بداية الأسبوع
 * السبت = نهاية الأسبوع
 */


/* أول يوم في السنة */

$year_start = new DateTime(
    $year_number . '-01-01'
);


/* رقم يوم الأسبوع
   الأحد = 0
   الاثنين = 1
   ...
   السبت = 6
*/

$day_of_week = (int)$year_start->format('w');


/* أول أحد في السنة */

$first_sunday = clone $year_start;


if ($day_of_week !== 0) {

    $first_sunday->modify(
        '+' . (7 - $day_of_week) . ' days'
    );

}


/* تحديد بداية الأسبوع الحالي */

$current_sunday = clone $today;


/* الأحد = 0 */

$current_day = (int)$current_sunday->format('w');


$current_sunday->modify(
    '-' . $current_day . ' days'
);


/* نهاية الأسبوع = السبت */

$current_saturday = clone $current_sunday;

$current_saturday->modify('+6 days');


/* =========================
   رقم الأسبوع
========================= */


/*
 * الفرق بين أول أحد في السنة
 * وبداية الأسبوع الحالي
 */

$days_difference =
    $first_sunday->diff($current_sunday)->days;


/*
 * الأسبوع الأول = 1
 */

$week_number =
    (int)floor($days_difference / 7) + 1;


/* =========================
   التواريخ النهائية
========================= */

$period_start =
    $current_sunday->format('Y-m-d');


$period_end =
    $current_saturday->format('Y-m-d');




    /* =========================
       إنشاء رقم الكشف
    ========================= */

    $year_prefix = date('Y');

    $stmt = $con->prepare("
        SELECT COUNT(*) 
        FROM driver_commissions
        WHERE year_number = ?
    ");

    $stmt->bind_param(
        "i",
        $year_number
    );

    $stmt->execute();

    $stmt->bind_result($commission_count);

    $stmt->fetch();

    $stmt->close();


    $commission_count++;

    $commission_no = 'COM-' . $year_prefix . '-' . str_pad(
        $commission_count,
        6,
        '0',
        STR_PAD_LEFT
    );


    /* =========================
       الموظف الحالي
    ========================= */

$created_by = intval($_SESSION['admin_id'] ?? 1);

    /* =========================
       بدء Transaction
    ========================= */

    $con->begin_transaction();
/* =========================
   التحقق من وجود كشف سابق
========================= */

$stmt = $con->prepare("
    SELECT
        id,
        commission_no,
        status,
        net_commission
    FROM driver_commissions
    WHERE driver_id = ?
    AND week_number = ?
    AND year_number = ?
    LIMIT 1
");

$stmt->bind_param(
    "iii",
    $driver_id,
    $week_number,
    $year_number
);

$stmt->execute();

$existingCommission = $stmt->get_result()->fetch_assoc();

$stmt->close();


if($existingCommission){

    echo "<div style='
        margin:30px;
        padding:25px;
        background:#fff7ed;
        color:#9a3412;
        border:1px solid #fed7aa;
        border-radius:12px;
        font-family:Arial;
        line-height:1.8;
    '>";

    echo "<h3>⚠️ يوجد كشف عمولة سابق</h3>";

    echo "هذا السائق لديه بالفعل كشف عمولة لهذا الأسبوع.<br><br>";

    echo "<strong>رقم الكشف:</strong> "
        . htmlspecialchars($existingCommission['commission_no'])
        . "<br>";

    echo "<strong>حالة الكشف:</strong> "
        . htmlspecialchars($existingCommission['status'])
        . "<br>";

    echo "<strong>صافي العمولة:</strong> "
        . number_format(
            $existingCommission['net_commission'],
            2
        )
        . " ريال<br><br>";

    echo "<a href='commission_details.php?id="
        . intval($existingCommission['id'])
        . "&lang="
        . urlencode($lang)
        . "' style='
            display:inline-block;
            padding:10px 18px;
            background:#2563eb;
            color:white;
            text-decoration:none;
            border-radius:8px;
        '>";

    echo "📄 عرض الكشف الحالي";

    echo "</a>";

    echo "</div>";

    exit;
}

    try {


        /* =========================
           حفظ كشف العمولة
        ========================= */

        $stmt = $con->prepare("

            INSERT INTO driver_commissions
            (
                commission_no,
                driver_id,
                service_id,
                nationality,
                week_number,
                year_number,
                period_start,
                period_end,
                total_orders,
                commission_rate,
                base_commission,
                total_bonus,
                total_deduction,
                net_commission,
                status,
                created_by
            )

            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'draft',
                ?
            )

        ");


        $stmt->bind_param(
            "siisiiisidddddi",
            $commission_no,
            $driver_id,
            $service_id,
            $nationality,
            $week_number,
            $year_number,
            $period_start,
            $period_end,
            $orders_count,
            $commission_rate,
            $base_commission,
            $total_bonus,
            $total_deduction,
            $net_commission,
            $created_by
        );


        if(!$stmt->execute()){

            throw new Exception(
                'فشل حفظ كشف العمولة.'
            );

        }


        $commission_id = $stmt->insert_id;

        $stmt->close();


        /* =========================
           حفظ المكافأة
        ========================= */

        if($total_bonus > 0){

            $reason = $bonus_reason ?: 'مكافأة إضافية';

            $adjustment_type = 'bonus';

            $added_by = $created_by;


            $stmt = $con->prepare("

                INSERT INTO commission_adjustments
                (
                    driver_id,
                    commission_id,
                    adjustment_type,
                    amount,
                    reason,
                    added_by
                )

                VALUES
                (?, ?, ?, ?, ?, ?)

            ");


            $stmt->bind_param(
                "iisdsi",
                $driver_id,
                $commission_id,
                $adjustment_type,
                $total_bonus,
                $reason,
                $added_by
            );


            if(!$stmt->execute()){

                throw new Exception(
                    'فشل حفظ المكافأة.'
                );

            }

            $stmt->close();

        }


        /* =========================
           حفظ الخصم
        ========================= */

        if($total_deduction > 0){

            $reason = $deduction_reason ?: 'خصم إضافي';

            $adjustment_type = 'deduction';

            $added_by = $created_by;


            $stmt = $con->prepare("

                INSERT INTO commission_adjustments
                (
                    driver_id,
                    commission_id,
                    adjustment_type,
                    amount,
                    reason,
                    added_by
                )

                VALUES
                (?, ?, ?, ?, ?, ?)

            ");


            $stmt->bind_param(
                "iisdsi",
                $driver_id,
                $commission_id,
                $adjustment_type,
                $total_deduction,
                $reason,
                $added_by
            );


            if(!$stmt->execute()){

                throw new Exception(
                    'فشل حفظ الخصم.'
                );

            }

            $stmt->close();

        }


        /* =========================
           تسجيل العملية
        ========================= */

        $action = 'create';

        $details =
            'تم إنشاء كشف العمولة رقم ' .
            $commission_no;


        $stmt = $con->prepare("

            INSERT INTO commission_logs
            (
                commission_id,
                action,
                details,
                admin_id
            )

            VALUES
            (?, ?, ?, ?)

        ");


        $stmt->bind_param(
            "issi",
            $commission_id,
            $action,
            $details,
            $created_by
        );


        if(!$stmt->execute()){

            throw new Exception(
                'فشل تسجيل العملية.'
            );

        }

        $stmt->close();


        /* =========================
           نجاح العملية
        ========================= */

        $con->commit();


        /* =========================
           الانتقال إلى التفاصيل
        ========================= */

        header(
            "Location: commission_details.php?id=" .
            $commission_id .
            "&lang=" .
            urlencode($lang)
        );

        exit;


    } catch(Exception $e){


        /* =========================
           إلغاء العملية
        ========================= */

        $con->rollback();


        echo "<div style='
            margin:30px;
            padding:20px;
            background:#fee2e2;
            color:#991b1b;
            border-radius:10px;
            font-family:Arial;
        '>";

        echo "<strong>حدث خطأ أثناء حفظ كشف العمولة:</strong><br><br>";

        echo htmlspecialchars(
            $e->getMessage()
        );

        echo "</div>";

        exit;

    }

}
?>

<!DOCTYPE html>

<html lang="<?= $lang ?>" dir="<?= $lang=='ar' ? 'rtl':'ltr' ?>">

<head>

<meta charset="UTF-8">

<title>
<?= $lang=='ar'
    ? 'حاسبة عمولات السائقين'
    : 'Driver Commission Calculator'
?>
</title>

<link rel="stylesheet" href="../assets/css/system.css">

</head>

<body>

<div class="container">

<h2 class="page-title">

🧮
<?= $lang=='ar'
    ? 'حاسبة عمولات السائقين'
    : 'Driver Commission Calculator'
?>

</h2>

<p class="page-description">

<?= $lang=='ar'
? 'حساب عمولات السائقين يدوياً حسب سياسات الشركة.'
: 'Calculate driver commissions according to company rules.'
?>

</p>
<div class="cards">

<div class="stat-card stat-blue">

<div class="stat-header">

<div class="stat-icon">
👨‍✈️
</div>

<div class="stat-title">
<?= $lang=='ar' ? 'السائقون' : 'Drivers' ?>
</div>

</div>

<div class="stat-value">

<?= $totalDrivers ?>

</div>

<div class="stat-footer">

<?= $lang=='ar'
? 'إجمالي السائقين'
: 'Total Drivers'
?>

</div>

</div>



<div class="stat-card stat-green">

<div class="stat-header">

<div class="stat-icon">
🚚
</div>

<div class="stat-title">
<?= $lang=='ar' ? 'الخدمات' : 'Services' ?>
</div>

</div>

<div class="stat-value">

<?= $totalServices ?>

</div>

<div class="stat-footer">

<?= $lang=='ar'
? 'الخدمات النشطة'
: 'Active Services'
?>

</div>

</div>




<div class="stat-card stat-purple">

<div class="stat-header">

<div class="stat-icon">
📑
</div>

<div class="stat-title">
<?= $lang=='ar'
? 'السياسات'
: 'Rules'
?>

</div>

</div>

<div class="stat-value">

<?= $totalRules ?>

</div>

<div class="stat-footer">

<?= $lang=='ar'
? 'السياسات الفعالة'
: 'Active Rules'
?>

</div>

</div>

</div>


    

    



        
       



    

  
<form method="POST" action="">

<div class="form-card">

    <div class="card-title">

        <?= $lang=='ar'
            ? 'بيانات الحساب'
            : 'Calculation Information'
        ?>

    </div>


    <div class="form-grid">


        <!-- السائق -->

        <div class="form-group">

            <label>

                <?= $lang=='ar'
                    ? 'السائق'
                    : 'Driver'
                ?>

            </label>

            <select name="driver_id" required>

                <option value="">
                    <?= $lang=='ar'
                        ? 'اختر السائق'
                        : 'Select Driver'
                    ?>
                </option>

                <?php foreach($drivers as $driver): ?>

                <option value="<?= $driver['id'] ?>">

                    <?= htmlspecialchars($driver['name']) ?>

                </option>

                <?php endforeach; ?>

            </select>

        </div>



        <!-- الخدمة -->

        <div class="form-group">

            <label>

                <?= $lang=='ar'
                    ? 'الخدمة'
                    : 'Service'
                ?>

            </label>

            <select name="service_id" required>

                <option value="">
                    <?= $lang=='ar'
                        ? 'اختر الخدمة'
                        : 'Select Service'
                    ?>
                </option>

                <?php foreach($services as $service): ?>

                <option 
value="<?= $service['id'] ?>"
<?= (isset($_POST['service_id']) && $_POST['service_id']==$service['id'])?'selected':'' ?>
>

                    <?= htmlspecialchars($service['service_name']) ?>

                </option>

                <?php endforeach; ?>

            </select>

        </div>



        <!-- الجنسية -->

        <div class="form-group">

            <label>

                <?= $lang=='ar'
                    ? 'الجنسية'
                    : 'Nationality'
                ?>

            </label>

            <select name="nationality" required>

<option value="">
    اختر الجنسية
</option>

<?php foreach($nationalities as $nat): ?>

<option
    value="<?= htmlspecialchars($nat) ?>"
    <?= (isset($_POST['nationality']) && $_POST['nationality']==$nat) ? 'selected' : '' ?>>
    <?= htmlspecialchars($nat) ?>
</option>

<?php endforeach; ?>

</select>

        </div>



        <!-- عدد الطلبات -->

        <div class="form-group">

            <label>

                <?= $lang=='ar'
                    ? 'عدد الطلبات'
                    : 'Orders Count'
                ?>

            </label>

            <input
type="number"
name="orders_count"
value="<?= $_POST['orders_count'] ?? 0 ?>"
min="0"
required>

        </div>

    </div>

    <!-- مكافأة إضافية -->

<div class="form-group">

<label>
<?= $lang=='ar'
? 'مكافأة إضافية'
: 'Extra Bonus'
?>
</label>

<input
type="number"
step="0.01"
name="bonus_extra"
value="<?= $_POST['bonus_extra'] ?? 0 ?>"
min="0">

<div class="form-group">

<label>

<?= $lang=='ar'
? 'سبب المكافأة'
: 'Bonus Reason'
?>

</label>


<select name="bonus_reason" class="form-control">


<option value="">

<?= $lang=='ar'
? 'اختر سبب المكافأة'
: 'Select Bonus Reason'
?>

</option>


<option value="أداء مميز">

أداء مميز

</option>


<option value="تحقيق الهدف الأسبوعي">

تحقيق الهدف الأسبوعي

</option>


<option value="تقييم ممتاز">

تقييم ممتاز

</option>


<option value="انضباط والتزام">

انضباط والتزام

</option>


<option value="مكافأة من الإدارة">

مكافأة من الإدارة

</option>


<option value="أخرى">

أخرى

</option>


</select>

</div>
</div>


<!-- خصم إضافي -->

<div class="form-group">

<label>
<?= $lang=='ar'
? 'خصم إضافي'
: 'Extra Deduction'
?>
</label>

<input
type="number"
step="0.01"
name="deduction_extra"
value="<?= $_POST['deduction_extra'] ?? 0 ?>"
min="0">

</div>
<div class="form-group">

<label>

<?= $lang=='ar'
? 'سبب الخصم'
: 'Deduction Reason'
?>

</label>


<select name="deduction_reason" class="form-control">


<option value="">

<?= $lang=='ar'
? 'اختر سبب الخصم'
: 'Select Deduction Reason'
?>

</option>


<option value="مخالفة مرورية">

مخالفة مرورية

</option>


<option value="تأخير">

تأخير

</option>


<option value="غياب">

غياب

</option>


<option value="سلفة">

سلفة

</option>


<option value="تلف أو أضرار">

تلف أو أضرار

</option>


<option value="مخالفة تعليمات العمل">

مخالفة تعليمات العمل

</option>


<option value="أخرى">

أخرى

</option>


</select>

</div>
    <br>

    <button
        type="submit"
        name="calculate"
        class="btn-system">

        🧮

        <?= $lang=='ar'
            ? 'حساب العمولة'
            : 'Calculate Commission'
        ?>

    </button>

</div>

</form>

<div class="result-card">

    <div class="card-title">

        <?= $lang=='ar'
            ? 'نتيجة الحساب'
            : 'Calculation Result'
        ?>

    </div>

    <table class="result-table">

        <tr>

            <td><?= $lang=='ar' ? 'العمولة الأساسية' : 'Base Commission' ?></td>

            <td>
<?= $commissionResult['base'] ?? '0.00' ?>
</td>

        </tr>

        <tr>

            <td><?= $lang=='ar' ? 'المكافأة' : 'Bonus' ?></td>

            <td>
<?= $commissionResult['bonus'] ?? '0.00' ?>


</td>

        </tr>

        <tr>

            <td><?= $lang=='ar' ? 'الخصم' : 'Deduction' ?></td>

            <td>
<?= $commissionResult['deduction'] ?? '0.00' ?>
</td>

        </tr>

        <tr>

            <td><?= $lang=='ar' ? 'صافي العمولة' : 'Net Commission' ?></td>

           <td>
<strong>
<?= $commissionResult['net'] ?? '0.00' ?>
</strong>
</td>

        </tr>

    </table>

</div>
<?php if($commissionResult): ?>

<div class="info-card">


<div class="card-title">

تفاصيل التعديلات

</div>

<?php if($commissionPreview): ?>

<div class="info-card">


<div class="card-title">

📄

<?= $lang=='ar'
?'كشف العمولة قبل الاعتماد'
:'Commission Preview'
?>

</div>



<table class="result-table">


<tr>

<td>
<?= $lang=='ar'?'عدد الطلبات':'Orders' ?>
</td>

<td>
<?= $commissionPreview['orders_count'] ?>
</td>

</tr>



<tr>

<td>
<?= $lang=='ar'?'العمولة الأساسية':'Base Commission' ?>
</td>

<td>
<?= number_format($commissionPreview['base'],2) ?>
</td>

</tr>



<tr>

<td>
<?= $lang=='ar'?'المكافأة':'Bonus' ?>
</td>

<td>
+
<?= number_format($commissionPreview['bonus'],2) ?>
</td>

</tr>



<tr>

<td>
<?= $lang=='ar'?'سبب المكافأة':'Bonus Reason' ?>
</td>

<td>
<?= htmlspecialchars($commissionPreview['bonus_reason']) ?>
</td>

</tr>



<tr>

<td>
<?= $lang=='ar'?'الخصم':'Deduction' ?>
</td>

<td>
-
<?= number_format($commissionPreview['deduction'],2) ?>
</td>

</tr>



<tr>

<td>
<?= $lang=='ar'?'سبب الخصم':'Deduction Reason' ?>
</td>

<td>
<?= htmlspecialchars($commissionPreview['deduction_reason']) ?>
</td>

</tr>



<tr>

<td>
<strong>
<?= $lang=='ar'?'الصافي المستحق':'Net Commission' ?>
</strong>
</td>

<td>

<strong>

<?= number_format($commissionPreview['net'],2) ?>

</strong>

</td>

</tr>


</table>


</div>

<?php endif; ?>


<table class="result-table">


<tr>

<td>
المكافأة الإضافية
</td>

<td>
+
<?= number_format($commissionResult['bonus_extra'],2) ?>
</td>

</tr>



<tr>

<td>
سبب المكافأة
</td>

<td>
<?= htmlspecialchars($commissionResult['bonus_reason']) ?>
</td>

</tr>



<tr>

<td>
الخصم الإضافي
</td>

<td>
-
<?= number_format($commissionResult['deduction_extra'],2) ?>
</td>

</tr>



<tr>

<td>
سبب الخصم
</td>

<td>
<?= htmlspecialchars($commissionResult['deduction_reason']) ?>
</td>

</tr>


</table>


</div>


<?php endif; ?>

<?php if($commissionResult): ?>

<form method="POST">

<input type="hidden" name="bonus_reason"
value="<?= htmlspecialchars($bonus_reason) ?>">

<input type="hidden" name="deduction_reason"
value="<?= htmlspecialchars($deduction_reason) ?>">

<input type="hidden" name="save_commission" value="1">

<input type="hidden" name="driver_id"
value="<?= $driver_id ?>">

<input type="hidden" name="service_id"
value="<?= $service_id ?>">

<input type="hidden" name="nationality"
value="<?= htmlspecialchars($nationality) ?>">

<input type="hidden" name="orders_count"
value="<?= $orders_count ?>">

<input type="hidden" name="commission_rate"
value="<?= $rule['commission_amount'] ?>">

<input type="hidden" name="base_commission"
value="<?= $base ?>">

<input type="hidden" name="total_bonus"
value="<?= $bonus_extra ?>">

<input type="hidden" name="total_deduction"
value="<?= $deduction_extra ?>">

<input type="hidden" name="net_commission"
value="<?= $net ?>">

<input type="hidden" name="bonus_reason"
value="<?= htmlspecialchars($bonus_reason) ?>">

<input type="hidden" name="deduction_reason"
value="<?= htmlspecialchars($deduction_reason) ?>">

<button
class="btn btn-success btn-system"
type="submit">

💾

<?= $lang=='ar'
? 'حفظ كشف العمولة'
: 'Save Commission Voucher'
?>

</button>

</form>

<?php endif; ?>


<div class="info-card">

    <div class="card-title">

        <?= $lang=='ar'
            ? 'السياسة المطبقة'
            : 'Applied Rule'
        ?>

    </div>

    <?php if($appliedRule): ?>

<table class="result-table">

<tr>
<td>
<?= $lang=='ar'?'اسم السياسة':'Rule Name' ?>
</td>

<td>
<?= htmlspecialchars($appliedRule['rule_name']) ?>
</td>


</tr>


<tr>
<td>
<?= $lang=='ar'?'من الطلبات':'Orders From' ?>
</td>

<td>
<?= $appliedRule['orders_from'] ?>
</td>
</tr>


<tr>
<td>
<?= $lang=='ar'?'إلى الطلبات':'Orders To' ?>
</td>

<td>
<?= $appliedRule['orders_to'] ?>
</td>
</tr>

<tr>
<td>
<?= $lang=='ar'?'قيمة العمولة لكل طلب':'Orders To' ?>
</td>

<td>
<?= $appliedRule['commission_amount'] ?>
</td>
</tr>

</table>

<?php else: ?>

<p>
<?= $lang=='ar'
?'لم يتم العثور على سياسة مطابقة'
:'No matching rule found'
?>
</p>

<?php endif; ?>

</div>

</div>

</body>

</html>