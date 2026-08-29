<?php

include('../../include/connected.php');
session_start();

/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';


/* =========================
   رقم عملية الدفع
========================= */

$payment_id = intval($_GET['id'] ?? 0);

if($payment_id <= 0){

    die(
        $lang == 'ar'
        ? 'رقم عملية الدفع غير صحيح.'
        : 'Invalid payment ID.'
    );

}


/* =========================
   جلب بيانات الدفع
========================= */

$stmt = $con->prepare("

SELECT

    cp.id,
    cp.driver_id,
    cp.commission_id,
    cp.payment_amount,
    cp.payment_method,
    cp.payment_status,
    cp.payment_date,
    cp.notes,
    cp.paid_by,
    cp.created_at,

    d.name AS driver_name,

    c.commission_no,
    c.week_number,
    c.year_number,
    c.period_start,
    c.period_end,
    c.total_orders,
    c.commission_rate,
    c.base_commission,
    c.total_bonus,
    c.total_deduction,
    c.net_commission,
    c.status AS commission_status

FROM commission_payments cp

LEFT JOIN drivers d
    ON cp.driver_id = d.id

LEFT JOIN driver_commissions c
    ON cp.commission_id = c.id

WHERE cp.id = ?

LIMIT 1

");


$stmt->bind_param("i", $payment_id);

$stmt->execute();

$result = $stmt->get_result();

$payment = $result->fetch_assoc();

$stmt->close();


/* =========================
   التحقق من وجود العملية
========================= */

if(!$payment){

    die(

        $lang == 'ar'

        ? 'لم يتم العثور على عملية الدفع.'

        : 'Payment record not found.'

    );

}


/* =========================
   بيانات العرض
========================= */

$paymentMethod = [

    'cash' => $lang == 'ar' ? 'نقدي' : 'Cash',

    'bank' => $lang == 'ar' ? 'بنكي' : 'Bank',

    'transfer' => $lang == 'ar' ? 'تحويل بنكي' : 'Bank Transfer'

];


$paymentStatus = [

    'pending' => $lang == 'ar' ? 'معلق' : 'Pending',

    'paid' => $lang == 'ar' ? 'مدفوع' : 'Paid',

    'cancelled' => $lang == 'ar' ? 'ملغي' : 'Cancelled'

];


$statusClass = [

    'pending' => 'status-warning',

    'paid' => 'status-success',

    'cancelled' => 'status-danger'

];


$currentPaymentMethod =
    $paymentMethod[$payment['payment_method']]
    ?? $payment['payment_method'];


$currentPaymentStatus =
    $paymentStatus[$payment['payment_status']]
    ?? $payment['payment_status'];


$currentStatusClass =
    $statusClass[$payment['payment_status']]
    ?? 'status-warning';


?>

<!DOCTYPE html>

<html
lang="<?= $lang ?>"
dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>

<?= $lang == 'ar'
    ? 'تفاصيل عملية الدفع'
    : 'Payment Details'
?>

</title>

<link
rel="stylesheet"
href="../assets/css/system.css"
>


<style>

/* =========================
   Payment Details
========================= */

.payment-details-grid{

    display:grid;

    grid-template-columns:
    repeat(2, minmax(0,1fr));

    gap:20px;

}


.payment-detail-card{

    background:#fff;

    border-radius:14px;

    padding:22px;

    box-shadow:
    0 4px 15px rgba(0,0,0,.06);

}


.payment-detail-card.full{

    grid-column:1 / -1;

}


.detail-title{

    font-size:18px;

    font-weight:700;

    margin-bottom:20px;

}


.detail-row{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

    padding:13px 0;

    border-bottom:1px solid #eee;

}


.detail-row:last-child{

    border-bottom:none;

}


.detail-label{

    color:#777;

    font-weight:600;

}


.detail-value{

    font-weight:600;

    text-align:left;

}


.payment-amount{

    font-size:30px;

    font-weight:800;

}


.status-badge{

    display:inline-block;

    padding:7px 14px;

    border-radius:20px;

    font-size:13px;

    font-weight:700;

}


.status-success{

    background:#e8f7ee;

    color:#198754;

}


.status-warning{

    background:#fff4d6;

    color:#a66a00;

}


.status-danger{

    background:#fde8e8;

    color:#c62828;

}


.actions{

    display:flex;

    gap:10px;

    flex-wrap:wrap;

    margin-bottom:20px;

}


.btn-back{

    display:inline-block;

    padding:10px 18px;

    border-radius:8px;

    text-decoration:none;

    background:#f1f3f5;

    color:#333;

    font-weight:600;

}


@media(max-width:768px){

    .payment-details-grid{

        grid-template-columns:1fr;

    }

    .payment-detail-card.full{

        grid-column:auto;

    }

    .detail-row{

        flex-direction:column;

        align-items:flex-start;

    }

}

</style>

</head>


<body>


<div class="container">


<!-- =========================
     العنوان
========================= -->

<h2 class="page-title">

💳

<?= $lang == 'ar'
    ? 'تفاصيل عملية الدفع'
    : 'Payment Details'
?>

</h2>


<p class="page-description">

<?= $lang == 'ar'

    ? 'عرض جميع تفاصيل عملية صرف العمولة والسجل المرتبط بها.'

    : 'View payment transaction and related commission details.'

?>

</p>



<!-- =========================
     أزرار
========================= -->

<div class="actions">

<a
href="commission_payments.php?lang=<?= $lang ?>"
class="btn-back"
>

⬅️

<?= $lang == 'ar'
    ? 'العودة إلى المدفوعات'
    : 'Back to Payments'
?>

</a>


<?php if(!empty($payment['commission_id'])): ?>

<a
href="commission_details.php?id=<?= $payment['commission_id'] ?>&lang=<?= $lang ?>"
class="btn-back"
>

📄

<?= $lang == 'ar'
    ? 'تفاصيل كشف العمولة'
    : 'Commission Details'
?>

</a>

<?php endif; ?>

</div>



<!-- =========================
     البطاقات
========================= -->

<div class="payment-details-grid">



<!-- =========================
     بيانات الدفع
========================= -->

<div class="payment-detail-card">

<div class="detail-title">

💳

<?= $lang == 'ar'
    ? 'بيانات الدفع'
    : 'Payment Information'
?>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'رقم العملية'
    : 'Payment ID'
?>

</span>

<span class="detail-value">

#<?= $payment['id'] ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'رقم كشف العمولة'
    : 'Commission No.'
?>

</span>

<span class="detail-value">

<?= htmlspecialchars(
    $payment['commission_no']
    ?? '#'.$payment['commission_id']
) ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'مبلغ الصرف'
    : 'Payment Amount'
?>

</span>

<span class="detail-value payment-amount">

<?= number_format(
    $payment['payment_amount'],
    2
) ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'طريقة الدفع'
    : 'Payment Method'
?>

</span>

<span class="detail-value">

<?= htmlspecialchars(
    $currentPaymentMethod
) ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'حالة الدفع'
    : 'Payment Status'
?>

</span>

<span class="detail-value">

<span class="status-badge <?= $currentStatusClass ?>">

<?= htmlspecialchars(
    $currentPaymentStatus
) ?>

</span>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'تاريخ الصرف'
    : 'Payment Date'
?>

</span>

<span class="detail-value">

<?= $payment['payment_date']
    ? htmlspecialchars($payment['payment_date'])
    : '-'
?>

</span>

</div>

</div>



<!-- =========================
     بيانات السائق
========================= -->

<div class="payment-detail-card">

<div class="detail-title">

👨‍✈️

<?= $lang == 'ar'
    ? 'بيانات السائق'
    : 'Driver Information'
?>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'اسم السائق'
    : 'Driver Name'
?>

</span>

<span class="detail-value">

<?= htmlspecialchars(
    $payment['driver_name']
    ?? '-'
) ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'رقم السائق'
    : 'Driver ID'
?>

</span>

<span class="detail-value">

#<?= $payment['driver_id'] ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'رقم كشف العمولة'
    : 'Commission ID'
?>

</span>

<span class="detail-value">

#<?= $payment['commission_id'] ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'أسبوع العمولة'
    : 'Commission Week'
?>

</span>

<span class="detail-value">

<?= $payment['week_number'] ?? '-' ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'السنة'
    : 'Year'
?>

</span>

<span class="detail-value">

<?= $payment['year_number'] ?? '-' ?>

</span>

</div>

</div>



<!-- =========================
     تفاصيل كشف العمولة
========================= -->

<div class="payment-detail-card full">

<div class="detail-title">

📊

<?= $lang == 'ar'
    ? 'تفاصيل كشف العمولة'
    : 'Commission Statement'
?>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'فترة الكشف'
    : 'Commission Period'
?>

</span>

<span class="detail-value">

<?= $payment['period_start'] ?? '-' ?>



&nbsp; — &nbsp;



<?= $payment['period_end'] ?? '-' ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'عدد الطلبات'
    : 'Total Orders'
?>

</span>

<span class="detail-value">

<?= number_format(
    $payment['total_orders'] ?? 0
) ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'قيمة العمولة لكل طلب'
    : 'Commission Rate'
?>

</span>

<span class="detail-value">

<?= number_format(
    $payment['commission_rate'] ?? 0,
    2
) ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'العمولة الأساسية'
    : 'Base Commission'
?>

</span>

<span class="detail-value">

<?= number_format(
    $payment['base_commission'] ?? 0,
    2
) ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

🎁

<?= $lang == 'ar'
    ? 'المكافآت'
    : 'Bonuses'
?>

</span>

<span class="detail-value">

+

<?= number_format(
    $payment['total_bonus'] ?? 0,
    2
) ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

➖

<?= $lang == 'ar'
    ? 'الخصومات'
    : 'Deductions'
?>

</span>

<span class="detail-value">

-

<?= number_format(
    $payment['total_deduction'] ?? 0,
    2
) ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<strong>

<?= $lang == 'ar'
    ? 'صافي العمولة'
    : 'Net Commission'
?>

</strong>

</span>

<span class="detail-value payment-amount">

<?= number_format(
    $payment['net_commission'] ?? 0,
    2
) ?>

</span>

</div>

</div>



<!-- =========================
     ملاحظات
========================= -->

<div class="payment-detail-card">

<div class="detail-title">

📝

<?= $lang == 'ar'
    ? 'ملاحظات الدفع'
    : 'Payment Notes'
?>

</div>


<div>

<?= !empty($payment['notes'])

    ? nl2br(
        htmlspecialchars($payment['notes'])
      )

    : (

        $lang == 'ar'
        ? 'لا توجد ملاحظات.'
        : 'No notes.'

      )

?>

</div>

</div>



<!-- =========================
     معلومات العملية
========================= -->

<div class="payment-detail-card">

<div class="detail-title">

ℹ️

<?= $lang == 'ar'
    ? 'معلومات العملية'
    : 'Transaction Information'
?>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'المستخدم الذي قام بالصرف'
    : 'Paid By'
?>

</span>

<span class="detail-value">

#<?= $payment['paid_by'] ?>

</span>

</div>


<div class="detail-row">

<span class="detail-label">

<?= $lang == 'ar'
    ? 'تاريخ إنشاء السجل'
    : 'Created At'
?>

</span>

<span class="detail-value">

<?= htmlspecialchars(
    $payment['created_at']
) ?>

</span>

</div>

</div>


</div>


</div>

</body>

</html>