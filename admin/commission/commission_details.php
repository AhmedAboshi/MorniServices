<?php

include('../../include/connected.php');
session_start();

/* =========================
   اللغة
========================= */

$lang = $_GET['lang'] ?? 'ar';

$isArabic = ($lang === 'ar');


/* =========================
   رقم الكشف
========================= */

$commission_id = intval($_GET['id'] ?? 0);

if($commission_id <= 0){

    die(
        $isArabic
        ? 'رقم كشف العمولة غير صحيح.'
        : 'Invalid commission ID.'
    );

}

/* =========================
   اعتماد كشف العمولة
========================= */

$approval_message = '';
$approval_error = '';

if(isset($_POST['approve_commission'])){

    $approve_id = intval($_POST['commission_id'] ?? 0);

    if($approve_id <= 0){

        $approval_error = $isArabic
            ? 'رقم كشف العمولة غير صحيح.'
            : 'Invalid commission ID.';

    }else{

        /*
         * نتحقق من حالة الكشف أولاً
         */
        $stmt = $con->prepare("
            SELECT id, status
            FROM driver_commissions
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            "i",
            $approve_id
        );

        $stmt->execute();

        $currentCommission = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();


        if(!$currentCommission){

            $approval_error = $isArabic
                ? 'كشف العمولة غير موجود.'
                : 'Commission voucher not found.';

        }elseif($currentCommission['status'] !== 'draft'
             && $currentCommission['status'] !== 'pending'){

            $approval_error = $isArabic
                ? 'لا يمكن اعتماد هذا الكشف لأن حالته الحالية هي: '
                  . $currentCommission['status']
                : 'This commission cannot be approved because its current status is: '
                  . $currentCommission['status'];

        }else{

            /*
             * تحديد المستخدم الذي قام بالاعتماد
             *
             * إذا كان تسجيل الدخول مفعلًا نستخدم admin_id
             * وإذا لم يكن موجودًا نتركه NULL مؤقتًا
             */
            $approved_by = !empty($_SESSION['admin_id'])
                ? intval($_SESSION['admin_id'])
                : null;


            /*
             * تنفيذ الاعتماد
             */

            if($approved_by !== null){

                $stmt = $con->prepare("
                    UPDATE driver_commissions

                    SET
                        status = 'approved',
                        approved_by = ?,
                        approved_at = NOW(),
                        updated_at = NOW()

                    WHERE id = ?

                    AND status IN ('draft','pending')
                ");

                $stmt->bind_param(
                    "ii",
                    $approved_by,
                    $approve_id
                );

            }else{

                /*
                 * مؤقتًا إذا لم يكن تسجيل دخول الإدارة مفعلًا
                 */

                $stmt = $con->prepare("
                    UPDATE driver_commissions

                    SET
                        status = 'approved',
                        approved_by = NULL,
                        approved_at = NOW(),
                        updated_at = NOW()

                    WHERE id = ?

                    AND status IN ('draft','pending')
                ");

                $stmt->bind_param(
                    "i",
                    $approve_id
                );

            }


            if($stmt->execute()){

                $stmt->close();

                /*
                 * إعادة تحميل الصفحة بعد الاعتماد
                 */
                header(
                    "Location: commission_details.php?id="
                    . $approve_id
                    . "&lang="
                    . urlencode($lang)
                    . "&approved=1"
                );

                exit;

            }else{

                $approval_error = $isArabic
                    ? 'حدث خطأ أثناء اعتماد كشف العمولة.'
                    : 'An error occurred while approving the commission.';

                $stmt->close();

            }

        }

    }

}

/* =========================
   صرف كشف العمولة
========================= */

$payment_message = '';
$payment_error = '';

/* =========================
   صرف كشف العمولة
   وتسجيل عملية الدفع
========================= */

$payment_message = '';
$payment_error = '';

if(isset($_POST['pay_commission'])){

    $pay_id = intval($_POST['commission_id'] ?? 0);

    $payment_method = $_POST['payment_method'] ?? 'cash';


    /*
     * طرق الدفع المسموحة فقط
     */

    $allowed_methods = [
        'cash',
        'bank',
        'transfer'
    ];


    if(!in_array($payment_method, $allowed_methods, true)){

        $payment_error = $isArabic
            ? 'طريقة الدفع غير صحيحة.'
            : 'Invalid payment method.';

    }elseif($pay_id <= 0){

        $payment_error = $isArabic
            ? 'رقم كشف العمولة غير صحيح.'
            : 'Invalid commission ID.';

    }else{


        /*
         * =====================================
         * بدء Transaction
         * =====================================
         */

        $con->begin_transaction();


        try{


            /*
             * =====================================
             * جلب الكشف
             * =====================================
             */

            $stmt = $con->prepare("

                SELECT

                    id,
                    driver_id,
                    net_commission,
                    status

                FROM driver_commissions

                WHERE id = ?

                LIMIT 1

                FOR UPDATE

            ");


            $stmt->bind_param(
                "i",
                $pay_id
            );


            $stmt->execute();


            $commissionData =
                $stmt->get_result()->fetch_assoc();


            $stmt->close();


            /*
             * =====================================
             * التحقق من وجود الكشف
             * =====================================
             */

            if(!$commissionData){

                throw new Exception(
                    $isArabic
                    ? 'كشف العمولة غير موجود.'
                    : 'Commission voucher not found.'
                );

            }


            /*
             * =====================================
             * يجب أن يكون معتمدًا
             * =====================================
             */

            if($commissionData['status'] !== 'approved'){

                throw new Exception(
                    $isArabic
                    ? 'لا يمكن صرف العمولة. يجب أن يكون الكشف معتمدًا.'
                    : 'The commission must be approved before payment.'
                );

            }


            /*
             * =====================================
             * منع تكرار الدفع
             * =====================================
             */

            $stmt = $con->prepare("

                SELECT id

                FROM commission_payments

                WHERE commission_id = ?

                AND payment_status = 'paid'

                LIMIT 1

                FOR UPDATE

            ");


            $stmt->bind_param(
                "i",
                $pay_id
            );


            $stmt->execute();


            $existingPayment =
                $stmt->get_result()->fetch_assoc();


            $stmt->close();


            if($existingPayment){

                throw new Exception(
                    $isArabic
                    ? 'تم صرف هذا الكشف مسبقًا.'
                    : 'This commission has already been paid.'
                );

            }


            /*
             * =====================================
             * المستخدم الذي قام بالصرف
             * =====================================
             */

            $paid_by = !empty($_SESSION['admin_id'])
                ? intval($_SESSION['admin_id'])
                : null;


            /*
             * =====================================
             * بما أن paid_by في الجدول NOT NULL
             * يجب أن يكون لدينا مستخدم
             * =====================================
             */

            if(!$paid_by){

                throw new Exception(
                    $isArabic
                    ? 'تعذر تحديد المستخدم الذي قام بالصرف. يرجى تسجيل الدخول كمسؤول.'
                    : 'Unable to determine the paying user. Please log in as administrator.'
                );

            }


            /*
             * =====================================
             * مبلغ الدفع
             * =====================================
             */

            $payment_amount =
                floatval(
                    $commissionData['net_commission']
                );


            if($payment_amount < 0){

                throw new Exception(
                    $isArabic
                    ? 'صافي العمولة غير صحيح.'
                    : 'Invalid net commission amount.'
                );

            }


            /*
             * =====================================
             * تسجيل الدفع
             * =====================================
             */

            $payment_status = 'paid';

            $payment_date = date('Y-m-d');


            $stmt = $con->prepare("

                INSERT INTO commission_payments

                (
                    driver_id,
                    commission_id,
                    payment_amount,
                    payment_method,
                    payment_status,
                    payment_date,
                    notes,
                    paid_by
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
                    ?
                )

            ");


            $notes = $isArabic
                ? 'صرف عمولة كشف رقم ' . $pay_id
                : 'Commission payment for voucher #' . $pay_id;


            $stmt->bind_param(

                "iidssssi",

                $commissionData['driver_id'],
                $pay_id,
                $payment_amount,
                $payment_method,
                $payment_status,
                $payment_date,
                $notes,
                $paid_by

            );


            if(!$stmt->execute()){

                throw new Exception(
                    $isArabic
                    ? 'فشل تسجيل عملية الدفع.'
                    : 'Failed to record payment.'
                );

            }


            $payment_id = $stmt->insert_id;

            $stmt->close();


            /*
             * =====================================
             * تحديث كشف العمولة
             * =====================================
             */

            $stmt = $con->prepare("

                UPDATE driver_commissions

                SET

                    status = 'paid',

                    paid_by = ?,

                    paid_at = NOW(),

                    updated_at = NOW()

                WHERE id = ?

                AND status = 'approved'

            ");


            $stmt->bind_param(

                "ii",

                $paid_by,
                $pay_id

            );


            if(!$stmt->execute()){

                throw new Exception(
                    $isArabic
                    ? 'فشل تحديث حالة كشف العمولة.'
                    : 'Failed to update commission status.'
                );

            }


            /*
             * التأكد من تحديث سجل واحد
             */

            if($stmt->affected_rows !== 1){

                throw new Exception(
                    $isArabic
                    ? 'لم يتم تحديث حالة الكشف.'
                    : 'Commission status was not updated.'
                );

            }


            $stmt->close();


            /*
             * =====================================
             * نجاح العمليتين
             * =====================================
             */

            $con->commit();


            /*
             * إعادة التوجيه لمنع التكرار
             */

            header(

                "Location: commission_details.php?id="
                . $pay_id
                . "&lang="
                . urlencode($lang)
                . "&paid=1"

            );

            exit;


        }catch(Exception $e){


            /*
             * =====================================
             * فشل العملية
             * =====================================
             */

            $con->rollback();


            $payment_error =
                $e->getMessage();

        }

    }

}


/* =========================
   رسالة نجاح الصرف
========================= */

if(
    isset($_GET['paid'])
    &&
    $_GET['paid'] == '1'
){

    $payment_message = $isArabic

        ? 'تم صرف العمولة وتسجيل عملية الدفع بنجاح.'

        : 'Commission paid and payment recorded successfully.';

}


/* =========================
   رسالة نجاح الاعتماد
========================= */

if(isset($_GET['approved']) && $_GET['approved'] == '1'){

    $approval_message = $isArabic
        ? 'تم اعتماد كشف العمولة بنجاح.'
        : 'Commission voucher approved successfully.';

}

/* =========================
   تحميل كشف العمولة
========================= */

$stmt = $con->prepare("

SELECT

    dc.*,

    d.name AS driver_name,

    cs.service_name

FROM driver_commissions dc

LEFT JOIN drivers d
    ON dc.driver_id = d.id

LEFT JOIN commission_services cs
    ON dc.service_id = cs.id

WHERE dc.id = ?

LIMIT 1

");

$stmt->bind_param(
    "i",
    $commission_id
);

$stmt->execute();

$commission = $stmt->get_result()->fetch_assoc();

$stmt->close();


/* =========================
   التحقق من وجود الكشف
========================= */

if(!$commission){

    die(
        $isArabic
        ? 'لم يتم العثور على كشف العمولة.'
        : 'Commission voucher not found.'
    );

}


/* =========================
   تحميل التعديلات
========================= */

$adjustments = [];

$stmt = $con->prepare("

SELECT

    id,
    adjustment_type,
    amount,
    reason,
    added_by,
    created_at

FROM commission_adjustments

WHERE commission_id = ?

ORDER BY created_at ASC, id ASC

");

$stmt->bind_param(
    "i",
    $commission_id
);

$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){

    $adjustments[] = $row;

}

$stmt->close();


/* =========================
   فصل المكافآت والخصومات
========================= */

$bonuses = [];

$deductions = [];

foreach($adjustments as $adjustment){

    if($adjustment['adjustment_type'] === 'bonus'){

        $bonuses[] = $adjustment;

    }else{

        $deductions[] = $adjustment;

    }

}


/* =========================
   حالة الكشف
========================= */

$statusLabels = [

    'draft' => [
        'ar' => 'مسودة',
        'en' => 'Draft'
    ],

    'pending' => [
        'ar' => 'بانتظار الاعتماد',
        'en' => 'Pending'
    ],

    'approved' => [
        'ar' => 'معتمد',
        'en' => 'Approved'
    ],

    'paid' => [
        'ar' => 'مدفوع',
        'en' => 'Paid'
    ],

    'cancelled' => [
        'ar' => 'ملغي',
        'en' => 'Cancelled'
    ]

];


$status = $commission['status'] ?? 'draft';

$statusText = $statusLabels[$status][$lang]
    ?? $status;


/* =========================
   حساب الإجماليات من التفاصيل
========================= */

$bonusDetailsTotal = 0;

foreach($bonuses as $bonus){

    $bonusDetailsTotal += floatval(
        $bonus['amount']
    );

}


$deductionDetailsTotal = 0;

foreach($deductions as $deduction){

    $deductionDetailsTotal += floatval(
        $deduction['amount']
    );

}

?>

<!DOCTYPE html>

<html
lang="<?= $lang ?>"
dir="<?= $isArabic ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>

<?= $isArabic
    ? 'تفاصيل كشف العمولة'
    : 'Commission Details'
?>

</title>


<link
rel="stylesheet"
href="../assets/css/system.css"
>


<style>

/* =========================
   Commission Details
========================= */

.details-header{

    display:flex;

    justify-content:space-between;

    align-items:center;

    gap:20px;

    margin-bottom:25px;

    flex-wrap:wrap;

}


.commission-number{

    font-size:15px;

    color:#64748b;

}


.commission-number strong{

    color:#0f172a;

}


.status-badge{

    display:inline-block;

    padding:8px 16px;

    border-radius:20px;

    font-size:14px;

    font-weight:600;

}


.status-draft{

    background:#fef3c7;

    color:#92400e;

}


.status-pending{

    background:#dbeafe;

    color:#1e40af;

}


.status-approved{

    background:#dcfce7;

    color:#166534;

}


.status-paid{

    background:#d1fae5;

    color:#065f46;

}


.status-cancelled{

    background:#fee2e2;

    color:#991b1b;

}


.details-grid{

    display:grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:15px;

    margin-bottom:25px;

}


.detail-box{

    background:#fff;

    border:1px solid #e5e7eb;

    border-radius:12px;

    padding:18px;

}


.detail-label{

    color:#64748b;

    font-size:13px;

    margin-bottom:8px;

}


.detail-value{

    color:#0f172a;

    font-size:16px;

    font-weight:600;

}


.amount-grid{

    display:grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap:15px;

    margin-bottom:25px;

}


.amount-card{

    background:#fff;

    border:1px solid #e5e7eb;

    border-radius:12px;

    padding:20px;

}


.amount-card .label{

    color:#64748b;

    font-size:13px;

    margin-bottom:10px;

}


.amount-card .amount{

    font-size:22px;

    font-weight:700;

}


.amount-base{

    color:#2563eb;

}


.amount-bonus{

    color:#16a34a;

}


.amount-deduction{

    color:#dc2626;

}


.amount-net{

    color:#7c3aed;

}


.section-card{

    background:#fff;

    border:1px solid #e5e7eb;

    border-radius:14px;

    padding:20px;

    margin-bottom:25px;

}


.section-title{

    font-size:18px;

    font-weight:700;

    margin-bottom:18px;

}


.details-table{

    width:100%;

    border-collapse:collapse;

}


.details-table th{

    background:#f8fafc;

    color:#475569;

    font-size:13px;

    padding:12px;

    text-align:right;

}


.details-table td{

    padding:12px;

    border-top:1px solid #e5e7eb;

}


.bonus-amount{

    color:#16a34a;

    font-weight:700;

}


.deduction-amount{

    color:#dc2626;

    font-weight:700;

}


.empty-message{

    padding:20px;

    text-align:center;

    color:#64748b;

}


.action-bar{

    display:flex;

    gap:10px;

    flex-wrap:wrap;

    margin-top:25px;

}


.btn-back{

    display:inline-block;

    padding:10px 18px;

    background:#64748b;

    color:#fff;

    border-radius:8px;

    text-decoration:none;

}


@media(max-width:1000px){

    .details-grid,
    .amount-grid{

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media(max-width:600px){

    .details-grid,
    .amount-grid{

        grid-template-columns:1fr;

    }

}

</style>

</head>


<body>


<div class="container">

<?php if($approval_message): ?>

<div class="alert alert-success"
     style="
        padding:15px;
        margin-bottom:20px;
        border-radius:10px;
        background:#dcfce7;
        color:#166534;
        border:1px solid #bbf7d0;
     ">

✅

<?= htmlspecialchars($approval_message) ?>

</div>

<?php endif; ?>


<?php if($approval_error): ?>

<div class="alert alert-danger"
     style="
        padding:15px;
        margin-bottom:20px;
        border-radius:10px;
        background:#fee2e2;
        color:#991b1b;
        border:1px solid #fecaca;
     ">

❌

<?= htmlspecialchars($approval_error) ?>

</div>

<?php endif; ?>

<?php if($payment_message): ?>

<div
    class="alert alert-success"
    style="
        padding:15px;
        margin-bottom:20px;
        border-radius:10px;
        background:#dcfce7;
        color:#166534;
        border:1px solid #bbf7d0;
    "
>

💰

<?= htmlspecialchars($payment_message) ?>

</div>

<?php endif; ?>


<?php if($payment_error): ?>

<div
    class="alert alert-danger"
    style="
        padding:15px;
        margin-bottom:20px;
        border-radius:10px;
        background:#fee2e2;
        color:#991b1b;
        border:1px solid #fecaca;
    "
>

❌

<?= htmlspecialchars($payment_error) ?>

</div>

<?php endif; ?>
<!-- =========================
     رأس الصفحة
========================= -->

<div class="details-header">


<div>

<h2 class="page-title">

📄

<?= $isArabic
    ? 'تفاصيل كشف العمولة'
    : 'Commission Details'
?>

</h2>


<div class="commission-number">

<?= $isArabic
    ? 'رقم الكشف'
    : 'Commission No.'
?>

:

<strong>

<?= htmlspecialchars(
    $commission['commission_no']
    ?? ('#' . $commission['id'])
) ?>

</strong>

</div>

</div>


<div>

<span class="status-badge status-<?= htmlspecialchars($status) ?>">

<?= htmlspecialchars($statusText) ?>

</span>

</div>


</div>


<!-- =========================
     بيانات الكشف
========================= -->

<div class="details-grid">


<div class="detail-box">

<div class="detail-label">

<?= $isArabic
    ? 'السائق'
    : 'Driver'
?>

</div>

<div class="detail-value">

<?= htmlspecialchars(
    $commission['driver_name']
    ?? '-'
) ?>

</div>

</div>


<div class="detail-box">

<div class="detail-label">

<?= $isArabic
    ? 'الخدمة'
    : 'Service'
?>

</div>

<div class="detail-value">

<?= htmlspecialchars(
    $commission['service_name']
    ?? '-'
) ?>

</div>

</div>


<div class="detail-box">

<div class="detail-label">

<?= $isArabic
    ? 'الجنسية'
    : 'Nationality'
?>

</div>

<div class="detail-value">

<?= htmlspecialchars(
    $commission['nationality']
    ?? '-'
) ?>

</div>

</div>


<div class="detail-box">

<div class="detail-label">

<?= $isArabic
    ? 'عدد الطلبات'
    : 'Total Orders'
?>

</div>

<div class="detail-value">

<?= number_format(
    intval($commission['total_orders'])
) ?>

</div>

</div>


<div class="detail-box">

<div class="detail-label">

<?= $isArabic
    ? 'الأسبوع'
    : 'Week'
?>

</div>

<div class="detail-value">

<?= intval(
    $commission['week_number']
) ?>

</div>

</div>


<div class="detail-box">

<div class="detail-label">

<?= $isArabic
    ? 'السنة'
    : 'Year'
?>

</div>

<div class="detail-value">

<?= intval(
    $commission['year_number']
) ?>

</div>

</div>


<div class="detail-box">

<div class="detail-label">

<?= $isArabic
    ? 'بداية الفترة'
    : 'Period Start'
?>

</div>

<div class="detail-value">

<?= htmlspecialchars(
    $commission['period_start']
) ?>

</div>

</div>


<div class="detail-box">

<div class="detail-label">

<?= $isArabic
    ? 'نهاية الفترة'
    : 'Period End'
?>

</div>

<div class="detail-value">

<?= htmlspecialchars(
    $commission['period_end']
) ?>

</div>

</div>

</div>


<!-- =========================
     ملخص المبالغ
========================= -->

<div class="amount-grid">


<div class="amount-card">

<div class="label">

<?= $isArabic
    ? 'العمولة الأساسية'
    : 'Base Commission'
?>

</div>

<div class="amount amount-base">

<?= number_format(
    floatval($commission['base_commission']),
    2
) ?>

 ريال

</div>

</div>


<div class="amount-card">

<div class="label">

<?= $isArabic
    ? 'إجمالي المكافآت'
    : 'Total Bonus'
?>

</div>

<div class="amount amount-bonus">

+

<?= number_format(
    floatval($commission['total_bonus']),
    2
) ?>

 ريال

</div>

</div>


<div class="amount-card">

<div class="label">

<?= $isArabic
    ? 'إجمالي الخصومات'
    : 'Total Deduction'
?>

</div>

<div class="amount amount-deduction">

-

<?= number_format(
    floatval($commission['total_deduction']),
    2
) ?>

 ريال

</div>

</div>


<div class="amount-card">

<div class="label">

<?= $isArabic
    ? 'صافي العمولة'
    : 'Net Commission'
?>

</div>

<div class="amount amount-net">

<?= number_format(
    floatval($commission['net_commission']),
    2
) ?>

 ريال

</div>

</div>

</div>


<!-- =========================
     تفاصيل المكافآت
========================= -->

<div class="section-card">


<div class="section-title">

🎁

<?= $isArabic
    ? 'تفاصيل المكافآت'
    : 'Bonus Details'
?>

</div>


<?php if(count($bonuses) > 0): ?>

<table class="details-table">

<thead>

<tr>

<th>
<?= $isArabic ? 'المبلغ' : 'Amount' ?>
</th>

<th>
<?= $isArabic ? 'السبب' : 'Reason' ?>
</th>

<th>
<?= $isArabic ? 'التاريخ' : 'Date' ?>
</th>

</tr>

</thead>


<tbody>

<?php foreach($bonuses as $bonus): ?>

<tr>

<td class="bonus-amount">

+

<?= number_format(
    floatval($bonus['amount']),
    2
) ?>

 ريال

</td>

<td>

<?= htmlspecialchars(
    $bonus['reason']
) ?>

</td>

<td>

<?= htmlspecialchars(
    $bonus['created_at']
) ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>


<tfoot>

<tr>

<td colspan="3">

<strong>

<?= $isArabic
    ? 'إجمالي المكافآت'
    : 'Total Bonus'
?>

:

<?= number_format(
    $bonusDetailsTotal,
    2
) ?>

 ريال

</strong>

</td>

</tr>

</tfoot>

</table>

<?php else: ?>

<div class="empty-message">

<?= $isArabic
    ? 'لا توجد مكافآت إضافية لهذا الكشف.'
    : 'No additional bonuses for this commission.'
?>

</div>

<?php endif; ?>

</div>


<!-- =========================
     تفاصيل الخصومات
========================= -->

<div class="section-card">


<div class="section-title">

➖

<?= $isArabic
    ? 'تفاصيل الخصومات'
    : 'Deduction Details'
?>

</div>


<?php if(count($deductions) > 0): ?>

<table class="details-table">

<thead>

<tr>

<th>
<?= $isArabic ? 'المبلغ' : 'Amount' ?>
</th>

<th>
<?= $isArabic ? 'السبب' : 'Reason' ?>
</th>

<th>
<?= $isArabic ? 'التاريخ' : 'Date' ?>
</th>

</tr>

</thead>


<tbody>

<?php foreach($deductions as $deduction): ?>

<tr>

<td class="deduction-amount">

-

<?= number_format(
    floatval($deduction['amount']),
    2
) ?>

 ريال

</td>

<td>

<?= htmlspecialchars(
    $deduction['reason']
) ?>

</td>

<td>

<?= htmlspecialchars(
    $deduction['created_at']
) ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>


<tfoot>

<tr>

<td colspan="3">

<strong>

<?= $isArabic
    ? 'إجمالي الخصومات'
    : 'Total Deduction'
?>

:

<?= number_format(
    $deductionDetailsTotal,
    2
) ?>

 ريال

</strong>

</td>

</tr>

</tfoot>

</table>

<?php else: ?>

<div class="empty-message">

<?= $isArabic
    ? 'لا توجد خصومات إضافية لهذا الكشف.'
    : 'No additional deductions for this commission.'
?>

</div>

<?php endif; ?>

</div>


<!-- =========================
     ملاحظات
========================= -->

<?php if(!empty($commission['notes'])): ?>

<div class="section-card">

<div class="section-title">

📝

<?= $isArabic
    ? 'ملاحظات'
    : 'Notes'
?>

</div>

<p>

<?= nl2br(
    htmlspecialchars(
        $commission['notes']
    )
) ?>

</p>

</div>

<?php endif; ?>


<!-- =========================
     أزرار
========================= -->

<div class="action-bar">


<a
    href="commission_calculator.php?lang=<?= urlencode($lang) ?>"
    class="btn-back"
>

↩️

<?= $isArabic
    ? 'العودة إلى حاسبة العمولات'
    : 'Back to Calculator'
?>

</a>


<!-- =========================
     اعتماد الكشف
========================= -->

<?php if(
    $status === 'draft' ||
    $status === 'pending'
): ?>

<form
    method="POST"
    action="commission_details.php?id=<?= $commission_id ?>&lang=<?= urlencode($lang) ?>"
    onsubmit="return confirm('<?= $isArabic
        ? 'هل أنت متأكد من اعتماد كشف العمولة؟'
        : 'Are you sure you want to approve this commission?'
    ?>');"
>

<input
    type="hidden"
    name="commission_id"
    value="<?= $commission_id ?>"
>

<button
    type="submit"
    name="approve_commission"
    class="btn-system"
>

✅

<?= $isArabic
    ? 'اعتماد كشف العمولة'
    : 'Approve Commission'
?>

</button>

</form>

<?php endif; ?>


<!-- =========================
     صرف العمولة
========================= -->

<?php if($status === 'approved'): ?>

<form
    method="POST"
    action="commission_details.php?id=<?= $commission_id ?>&lang=<?= urlencode($lang) ?>"
    onsubmit="return confirm('<?= $isArabic
        ? 'هل أنت متأكد من صرف هذه العمولة؟'
        : 'Are you sure you want to pay this commission?'
    ?>');"
>

<input
    type="hidden"
    name="commission_id"
    value="<?= $commission_id ?>"
>


<div class="form-group"
     style="margin-bottom:15px;">

<label>

<?= $isArabic
    ? 'طريقة الدفع'
    : 'Payment Method'
?>

</label>


<select
    name="payment_method"
    class="form-control"
    required
>

<option value="cash">

<?= $isArabic
    ? '💵 نقدي'
    : '💵 Cash'
?>

</option>


<option value="bank">

<?= $isArabic
    ? '🏦 بنكي'
    : '🏦 Bank'
?>

</option>


<option value="transfer">

<?= $isArabic
    ? '💳 تحويل'
    : '💳 Transfer'
?>

</option>

</select>

</div>


<button
    type="submit"
    name="pay_commission"
    class="btn-system"
    style="
        background:#16a34a;
        border-color:#16a34a;
    "
>

💰

<?= $isArabic
    ? 'صرف العمولة'
    : 'Pay Commission'
?>

</button>

</form>

<?php endif; ?>


<!-- =========================
     الكشف مدفوع
========================= -->

<?php if($status === 'paid'): ?>

<div
    class="status-badge status-paid"
    style="
        padding:12px 18px;
        font-size:15px;
    "
>

💰

<?= $isArabic
    ? 'تم صرف العمولة'
    : 'Commission Paid'
?>

</div>

<?php endif; ?>


</div>


</div>

</body>

</html>