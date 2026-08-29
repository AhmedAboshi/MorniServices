
<?php

include('../../include/connected.php');
session_start();

/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';

$isArabic = ($lang === 'ar');


/* =========================
   حماية الدخول
========================= */

// عند الانتهاء من نظام تسجيل الدخول
// فعّل الحماية التالية:
//
// if(!isset($_SESSION['admin_id'])){
//     header("Location: ../admin.php");
//     exit;
// }


/* =========================
   أدوات مساعدة
========================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================
   الفلاتر
========================= */

$search = trim($_GET['search'] ?? '');

$payment_method = $_GET['payment_method'] ?? '';

$payment_status = $_GET['payment_status'] ?? '';

$date_from = $_GET['date_from'] ?? '';

$date_to = $_GET['date_to'] ?? '';


/* =========================
   بطاقات الإحصائيات
========================= */

$total_paid = 0;
$weekly_paid = 0;
$pending_payments = 0;
$payment_count = 0;
$cash_payments = 0;
$bank_transfer_payments = 0;


/* =========================
   إجمالي المدفوعات
========================= */

$stmt = $con->prepare("
    SELECT COALESCE(SUM(payment_amount),0)
    FROM commission_payments
    WHERE payment_status = 'paid'
");

$stmt->execute();
$stmt->bind_result($total_paid);
$stmt->fetch();
$stmt->close();


/* =========================
   مدفوعات هذا الأسبوع
========================= */

$stmt = $con->prepare("
    SELECT COALESCE(SUM(payment_amount),0)
    FROM commission_payments
    WHERE payment_status = 'paid'
    AND YEARWEEK(payment_date,1) = YEARWEEK(CURDATE(),1)
");

$stmt->execute();
$stmt->bind_result($weekly_paid);
$stmt->fetch();
$stmt->close();


/* =========================
   المدفوعات المعلقة
========================= */

$stmt = $con->prepare("
    SELECT COUNT(*)
    FROM commission_payments
    WHERE payment_status = 'pending'
");

$stmt->execute();
$stmt->bind_result($pending_payments);
$stmt->fetch();
$stmt->close();


/* =========================
   عدد عمليات الصرف
========================= */

$stmt = $con->prepare("
    SELECT COUNT(*)
    FROM commission_payments
    WHERE payment_status = 'paid'
");

$stmt->execute();
$stmt->bind_result($payment_count);
$stmt->fetch();
$stmt->close();


/* =========================
   المدفوعات النقدية
========================= */

$stmt = $con->prepare("
    SELECT COALESCE(SUM(payment_amount),0)
    FROM commission_payments
    WHERE payment_status = 'paid'
    AND payment_method = 'cash'
");

$stmt->execute();
$stmt->bind_result($cash_payments);
$stmt->fetch();
$stmt->close();


/* =========================
   المدفوعات البنكية والتحويلات
========================= */

$stmt = $con->prepare("
    SELECT COALESCE(SUM(payment_amount),0)
    FROM commission_payments
    WHERE payment_status = 'paid'
    AND payment_method IN ('bank','transfer')
");

$stmt->execute();
$stmt->bind_result($bank_transfer_payments);
$stmt->fetch();
$stmt->close();


/* =========================
   بناء الاستعلام
========================= */

$sql = "
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

    dc.commission_no,
    dc.week_number,
    dc.year_number,
    dc.period_start,
    dc.period_end,
    dc.total_orders,
    dc.base_commission,
    dc.total_bonus,
    dc.total_deduction,
    dc.net_commission

FROM commission_payments cp

LEFT JOIN drivers d
    ON d.id = cp.driver_id

LEFT JOIN driver_commissions dc
    ON dc.id = cp.commission_id

WHERE 1=1
";


$params = [];
$types = '';


/* =========================
   البحث
========================= */

if($search !== ''){

    $sql .= "
        AND (
            d.name LIKE ?
            OR dc.commission_no LIKE ?
            OR CAST(cp.id AS CHAR) LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= 'sss';
}


/* =========================
   طريقة الدفع
========================= */

if(in_array($payment_method, ['cash','bank','transfer'], true)){

    $sql .= " AND cp.payment_method = ? ";

    $params[] = $payment_method;

    $types .= 's';
}


/* =========================
   حالة الدفع
========================= */

if(in_array($payment_status, ['pending','paid','cancelled'], true)){

    $sql .= " AND cp.payment_status = ? ";

    $params[] = $payment_status;

    $types .= 's';
}


/* =========================
   من تاريخ
========================= */

if($date_from !== ''){

    $sql .= " AND cp.payment_date >= ? ";

    $params[] = $date_from;

    $types .= 's';
}


/* =========================
   إلى تاريخ
========================= */

if($date_to !== ''){

    $sql .= " AND cp.payment_date <= ? ";

    $params[] = $date_to;

    $types .= 's';
}


$sql .= "
ORDER BY cp.id DESC
";


/* =========================
   تنفيذ الاستعلام
========================= */

$stmt = $con->prepare($sql);

if(!empty($params)){

    $stmt->bind_param($types, ...$params);

}

$stmt->execute();

$result = $stmt->get_result();

$payments = [];

while($row = $result->fetch_assoc()){

    $payments[] = $row;

}

$stmt->close();


/* =========================
   أسماء طرق الدفع
========================= */

function paymentMethodLabel($method, $isArabic)
{

    if($isArabic){

        return match($method){

            'cash' =>
                'نقدي',

            'bank' =>
                'بنكي',

            'transfer' =>
                'تحويل',

            default =>
                $method
        };

    }

    return match($method){

        'cash' =>
            'Cash',

        'bank' =>
            'Bank',

        'transfer' =>
            'Transfer',

        default =>
            $method
    };
}


/* =========================
   أسماء حالات الدفع
========================= */

function paymentStatusLabel($status, $isArabic)
{

    if($isArabic){

        return match($status){

            'pending' =>
                'معلق',

            'paid' =>
                'مدفوع',

            'cancelled' =>
                'ملغي',

            default =>
                $status
        };

    }

    return match($status){

        'pending' =>
            'Pending',

        'paid' =>
            'Paid',

        'cancelled' =>
            'Cancelled',

        default =>
            $status
    };
}

?>

<!DOCTYPE html>

<html
    lang="<?= e($lang) ?>"
    dir="<?= $isArabic ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>

<?= $isArabic
    ? 'مدفوعات العمولات'
    : 'Commission Payments'
?>

</title>


<link
    rel="stylesheet"
    href="../assets/css/system.css"
>


<style>

/* =========================
   صفحة المدفوعات
========================= */

.payment-page{
    width:100%;
}


/* =========================
   الفلاتر
========================= */

.filters-card{
    background:#fff;
    border-radius:12px;
    padding:20px;
    margin-top:25px;
    margin-bottom:25px;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
}

.filters-grid{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:15px;
    align-items:end;
}

.filter-group{
    display:flex;
    flex-direction:column;
    gap:7px;
}

.filter-group label{
    font-weight:600;
}

.filter-group input,
.filter-group select{
    width:100%;
    padding:10px 12px;
    border:1px solid #ddd;
    border-radius:8px;
    background:#fff;
}

.filter-buttons{
    display:flex;
    gap:8px;
}

.filter-buttons button,
.filter-buttons a{
    min-height:42px;
    display:flex;
    align-items:center;
    justify-content:center;
}


/* =========================
   جدول المدفوعات
========================= */

.payments-card{
    background:#fff;
    border-radius:12px;
    padding:20px;
    margin-top:25px;
    box-shadow:0 2px 10px rgba(0,0,0,.05);
}

.table-wrapper{
    width:100%;
    overflow-x:auto;
}

.payments-table{
    width:100%;
    border-collapse:collapse;
    min-width:1100px;
}

.payments-table th,
.payments-table td{
    padding:13px 12px;
    border-bottom:1px solid #eee;
    text-align:center;
    white-space:nowrap;
}

.payments-table th{
    font-weight:700;
}

.payments-table tbody tr:hover{
    background:#fafafa;
}


/* =========================
   حالة الدفع
========================= */

.payment-status{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:5px 11px;
    border-radius:20px;
    font-size:13px;
    font-weight:600;
}

.status-paid{
    background:#e8f7ee;
    color:#198754;
}

.status-pending{
    background:#fff4d6;
    color:#b77900;
}

.status-cancelled{
    background:#fdeaea;
    color:#dc3545;
}


/* =========================
   طريقة الدفع
========================= */

.method-badge{
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:5px 10px;
    border-radius:7px;
    background:#f4f5f7;
}


/* =========================
   السائق
========================= */

.driver-name{
    font-weight:700;
}

.commission-number{
    font-size:12px;
    opacity:.65;
    margin-top:3px;
}


/* =========================
   المبلغ
========================= */

.payment-amount{
    font-weight:700;
}


/* =========================
   لا توجد بيانات
========================= */

.empty-state{
    text-align:center;
    padding:50px 20px;
}

.empty-icon{
    font-size:45px;
    margin-bottom:10px;
}

.empty-title{
    font-size:18px;
    font-weight:700;
}

.empty-text{
    opacity:.7;
    margin-top:8px;
}


/* =========================
   Responsive
========================= */

@media(max-width:1100px){

    .filters-grid{
        grid-template-columns:repeat(2,1fr);
    }

}

@media(max-width:700px){

    .filters-grid{
        grid-template-columns:1fr;
    }

    .filter-buttons{
        flex-direction:column;
    }

}

</style>

</head>


<body>


<div class="container payment-page">


<!-- =========================
     العنوان
========================= -->

<h2 class="page-title">

💰

<?= $isArabic
    ? 'مدفوعات العمولات'
    : 'Commission Payments'
?>

</h2>


<p class="page-description">

<?= $isArabic

    ? 'سجل عمليات صرف العمولات للسائقين ومتابعة حالة المدفوعات.'

    : 'Track driver commission payments and payment status.'

?>

</p>


<!-- =========================
     البطاقات
========================= -->

<div class="cards">


<!-- إجمالي المدفوعات -->

<div class="stat-card stat-blue">

<div class="stat-header">

<div class="stat-icon">
💰
</div>

<div class="stat-title">

<?= $isArabic
    ? 'إجمالي المدفوعات'
    : 'Total Payments'
?>

</div>

</div>


<div class="stat-value">

<?= number_format((float)$total_paid,2) ?>

</div>


<div class="stat-footer">

<?= $isArabic
    ? 'إجمالي المبالغ المصروفة'
    : 'Total paid amount'
?>

</div>

</div>


<!-- هذا الأسبوع -->

<div class="stat-card stat-green">

<div class="stat-header">

<div class="stat-icon">
📅
</div>

<div class="stat-title">

<?= $isArabic
    ? 'هذا الأسبوع'
    : 'This Week'
?>

</div>

</div>


<div class="stat-value">

<?= number_format((float)$weekly_paid,2) ?>

</div>


<div class="stat-footer">

<?= $isArabic
    ? 'المدفوعات هذا الأسبوع'
    : 'Payments this week'
?>

</div>

</div>


<!-- بانتظار الصرف -->

<div class="stat-card stat-purple">

<div class="stat-header">

<div class="stat-icon">
⏳
</div>

<div class="stat-title">

<?= $isArabic
    ? 'بانتظار الصرف'
    : 'Pending'
?>

</div>

</div>


<div class="stat-value">

<?= number_format((int)$pending_payments) ?>

</div>


<div class="stat-footer">

<?= $isArabic
    ? 'عمليات بانتظار الدفع'
    : 'Payments awaiting processing'
?>

</div>

</div>


<!-- عمليات الصرف -->

<div class="stat-card stat-orange">

<div class="stat-header">

<div class="stat-icon">
✅
</div>

<div class="stat-title">

<?= $isArabic
    ? 'عمليات الصرف'
    : 'Payments Count'
?>

</div>

</div>


<div class="stat-value">

<?= number_format((int)$payment_count) ?>

</div>


<div class="stat-footer">

<?= $isArabic
    ? 'عمليات صرف مكتملة'
    : 'Completed payments'
?>

</div>

</div>


<!-- نقدي -->

<div class="stat-card stat-blue">

<div class="stat-header">

<div class="stat-icon">
💵
</div>

<div class="stat-title">

<?= $isArabic
    ? 'نقدي'
    : 'Cash'
?>

</div>

</div>


<div class="stat-value">

<?= number_format((float)$cash_payments,2) ?>

</div>


<div class="stat-footer">

<?= $isArabic
    ? 'إجمالي المدفوعات النقدية'
    : 'Total cash payments'
?>

</div>

</div>


<!-- بنكي وتحويل -->

<div class="stat-card stat-green">

<div class="stat-header">

<div class="stat-icon">
🏦
</div>

<div class="stat-title">

<?= $isArabic
    ? 'بنكي / تحويل'
    : 'Bank / Transfer'
?>

</div>

</div>


<div class="stat-value">

<?= number_format((float)$bank_transfer_payments,2) ?>

</div>


<div class="stat-footer">

<?= $isArabic
    ? 'إجمالي المدفوعات البنكية'
    : 'Total bank payments'
?>

</div>

</div>


</div>


<!-- =========================
     الفلاتر
========================= -->

<div class="filters-card">


<div class="card-title">

🔎

<?= $isArabic
    ? 'بحث وتصفية المدفوعات'
    : 'Search & Filter Payments'
?>

</div>


<form method="GET">


<input
    type="hidden"
    name="lang"
    value="<?= e($lang) ?>"
>


<div class="filters-grid">


<!-- البحث -->

<div class="filter-group">

<label>

<?= $isArabic
    ? 'بحث'
    : 'Search'
?>

</label>


<input
    type="text"
    name="search"
    value="<?= e($search) ?>"
    placeholder="<?= $isArabic
        ? 'اسم السائق أو رقم الكشف'
        : 'Driver or commission number'
    ?>"
>

</div>


<!-- طريقة الدفع -->

<div class="filter-group">

<label>

<?= $isArabic
    ? 'طريقة الدفع'
    : 'Payment Method'
?>

</label>


<select name="payment_method">


<option value="">

<?= $isArabic
    ? 'كل الطرق'
    : 'All Methods'
?>

</option>


<option
    value="cash"
    <?= $payment_method === 'cash' ? 'selected' : '' ?>
>

<?= $isArabic ? 'نقدي' : 'Cash' ?>

</option>


<option
    value="bank"
    <?= $payment_method === 'bank' ? 'selected' : '' ?>
>

<?= $isArabic ? 'بنكي' : 'Bank' ?>

</option>


<option
    value="transfer"
    <?= $payment_method === 'transfer' ? 'selected' : '' ?>
>

<?= $isArabic ? 'تحويل' : 'Transfer' ?>

</option>


</select>

</div>


<!-- الحالة -->

<div class="filter-group">

<label>

<?= $isArabic
    ? 'حالة الدفع'
    : 'Payment Status'
?>

</label>


<select name="payment_status">


<option value="">

<?= $isArabic
    ? 'كل الحالات'
    : 'All Statuses'
?>

</option>


<option
    value="pending"
    <?= $payment_status === 'pending' ? 'selected' : '' ?>
>

<?= $isArabic ? 'معلق' : 'Pending' ?>

</option>


<option
    value="paid"
    <?= $payment_status === 'paid' ? 'selected' : '' ?>
>

<?= $isArabic ? 'مدفوع' : 'Paid' ?>

</option>


<option
    value="cancelled"
    <?= $payment_status === 'cancelled' ? 'selected' : '' ?>
>

<?= $isArabic ? 'ملغي' : 'Cancelled' ?>

</option>


</select>

</div>


<!-- من تاريخ -->

<div class="filter-group">

<label>

<?= $isArabic
    ? 'من تاريخ'
    : 'From Date'
?>

</label>


<input
    type="date"
    name="date_from"
    value="<?= e($date_from) ?>"
>

</div>


<!-- إلى تاريخ -->

<div class="filter-group">

<label>

<?= $isArabic
    ? 'إلى تاريخ'
    : 'To Date'
?>

</label>


<input
    type="date"
    name="date_to"
    value="<?= e($date_to) ?>"
>

</div>


</div>


<br>


<div class="filter-buttons">


<button
    type="submit"
    class="btn-system"
>

🔎

<?= $isArabic
    ? 'بحث'
    : 'Search'
?>

</button>


<a
    href="commission_payments.php?lang=<?= e($lang) ?>"
    class="btn-system"
>

↻

<?= $isArabic
    ? 'إعادة ضبط'
    : 'Reset'
?>

</a>


</div>


</form>

</div>


<!-- =========================
     جدول المدفوعات
========================= -->

<div class="payments-card">


<div class="card-title">

💳

<?= $isArabic
    ? 'سجل المدفوعات'
    : 'Payment History'
?>

<span style="float:<?= $isArabic ? 'left' : 'right' ?>;opacity:.65;font-size:14px;">

<?= number_format(count($payments)) ?>

<?= $isArabic ? 'عملية' : 'payments' ?>

</span>

</div>


<div class="table-wrapper">


<?php if(!empty($payments)): ?>


<table class="payments-table">


<thead>

<tr>

<th>#</th>

<th>
<?= $isArabic ? 'رقم الدفع' : 'Payment #' ?>
</th>

<th>
<?= $isArabic ? 'رقم الكشف' : 'Commission #' ?>
</th>

<th>
<?= $isArabic ? 'السائق' : 'Driver' ?>
</th>

<th>
<?= $isArabic ? 'عدد الطلبات' : 'Orders' ?>
</th>

<th>
<?= $isArabic ? 'مبلغ الدفع' : 'Payment Amount' ?>
</th>

<th>
<?= $isArabic ? 'طريقة الدفع' : 'Method' ?>
</th>

<th>
<?= $isArabic ? 'الحالة' : 'Status' ?>
</th>

<th>
<?= $isArabic ? 'تاريخ الدفع' : 'Payment Date' ?>
</th>

<th>
<?= $isArabic ? 'الإجراء' : 'Action' ?>
</th>

</tr>

</thead>


<tbody>


<?php foreach($payments as $index => $payment): ?>


<tr>


<!-- الرقم -->

<td>

<?= $index + 1 ?>

</td>


<!-- رقم الدفع -->

<td>

<strong>

#<?= (int)$payment['id'] ?>

</strong>

</td>


<!-- رقم الكشف -->

<td>

<?php if(!empty($payment['commission_no'])): ?>

<strong>

<?= e($payment['commission_no']) ?>

</strong>

<?php else: ?>

#

<?= (int)$payment['commission_id'] ?>

<?php endif; ?>

</td>


<!-- السائق -->

<td>

<div class="driver-name">

<?= e($payment['driver_name'] ?? '—') ?>

</div>

</td>


<!-- الطلبات -->

<td>

<?= number_format((int)($payment['total_orders'] ?? 0)) ?>

</td>


<!-- المبلغ -->

<td>

<span class="payment-amount">

<?= number_format((float)$payment['payment_amount'],2) ?>

</span>

</td>


<!-- الطريقة -->

<td>

<span class="method-badge">

<?php

if($payment['payment_method'] === 'cash'){
    echo '💵';
}
elseif($payment['payment_method'] === 'bank'){
    echo '🏦';
}
elseif($payment['payment_method'] === 'transfer'){
    echo '💳';
}
?>

<?= e(
    paymentMethodLabel(
        $payment['payment_method'],
        $isArabic
    )
) ?>

</span>

</td>


<!-- الحالة -->

<td>

<?php

$statusClass = match($payment['payment_status']){

    'paid' =>
        'status-paid',

    'pending' =>
        'status-pending',

    'cancelled' =>
        'status-cancelled',

    default =>
        ''
};

?>

<span class="payment-status <?= $statusClass ?>">

<?= e(
    paymentStatusLabel(
        $payment['payment_status'],
        $isArabic
    )
) ?>

</span>

</td>


<!-- التاريخ -->

<td>

<?= !empty($payment['payment_date'])
    ? e($payment['payment_date'])
    : '—'
?>

</td>


<!-- الإجراء -->

<td>

<a
    href="commission_payment_details.php?id=<?= (int)$payment['id'] ?>&lang=<?= e($lang) ?>"
    class="btn-system"
    style="padding:7px 12px;font-size:13px;"
>

👁

<?= $isArabic
    ? 'التفاصيل'
    : 'Details'
?>

</a>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


<?php else: ?>


<div class="empty-state">


<div class="empty-icon">
💳
</div>


<div class="empty-title">

<?= $isArabic
    ? 'لا توجد عمليات دفع'
    : 'No Payment Records'
?>

</div>


<div class="empty-text">

<?= $isArabic
    ? 'لم يتم العثور على عمليات صرف مطابقة للبحث الحالي.'
    : 'No payment records match the current filters.'
?>

</div>


</div>


<?php endif; ?>


</div>

</div>


</div>


</body>

</html>

