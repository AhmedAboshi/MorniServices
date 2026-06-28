<?php
/**
 * ============================================
 * Morni Services
 * Order Functions
 * ============================================
 */

if (!defined('MORNI_SYSTEM')) {
    exit('Direct access not allowed.');
}

/*-----------------------------------------
1- تنظيف البيانات
-----------------------------------------*/
function clean($value)
{
    if(is_array($value)){
        return array_map('clean',$value);
    }

    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}


/*-----------------------------------------
2- توليد رقم الطلب
MS-20260628-0001
-----------------------------------------*/
function generateOrderNumber($con)
{
    $today = date('Ymd');

    $sql = "SELECT COUNT(*) total
            FROM orders
            WHERE DATE(created_at)=CURDATE()";

    $result = $con->query($sql);

    $row = $result->fetch_assoc();

    $number = str_pad(($row['total'] + 1),4,"0",STR_PAD_LEFT);

    return "MS-{$today}-{$number}";
}

/*-----------------------------------------
3- جلب بيانات الطلب
-----------------------------------------*/
function getOrder($con,$id)
{
    $stmt = $con->prepare("
        SELECT
            o.*,
            d.name AS driver_name,
            d.phone AS driver_phone
        FROM orders o

        LEFT JOIN drivers d
            ON d.id=o.driver_id

        WHERE o.id=?

        LIMIT 1
    ");

    $stmt->bind_param("i",$id);

    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/*-----------------------------------------
4- جلب جميع الطلبات
-----------------------------------------*/
function getOrders($con,$where="")
{

    $sql="
        SELECT
            o.*,
            d.name driver_name

        FROM orders o

        LEFT JOIN drivers d
            ON d.id=o.driver_id
    ";

    if(!empty($where)){
        $sql.=" WHERE ".$where;
    }

    $sql.=" ORDER BY o.id DESC";

    $result=$con->query($sql);

    $orders=[];

    while($row=$result->fetch_assoc()){

        $orders[]=$row;

    }

    return $orders;

}

/*-----------------------------------------
5- جلب سعر النقل
-----------------------------------------*/
function getTransportPrice(
    $con,
    $from_city,
    $to_city,
    $truck_type
){}

/*-----------------------------------------
6- حساب السعر النهائي
-----------------------------------------*/
function calculateOrderPrice(){}

/*-----------------------------------------
7- إضافة طلب
-----------------------------------------*/
function createOrder(){}

/*-----------------------------------------
8- تحديث طلب
-----------------------------------------*/
function updateOrder(){}

/*-----------------------------------------
9- حذف طلب
-----------------------------------------*/
function deleteOrder(){}

/*-----------------------------------------
10- تغيير حالة الطلب
-----------------------------------------*/
function updateOrderStatus(){}

/*-----------------------------------------
11- تعيين سائق
-----------------------------------------*/
function assignDriver(){}

/*-----------------------------------------
12- تعيين مركبة
-----------------------------------------*/
function assignVehicle(){}

/*-----------------------------------------
13- حساب ربح الطلب
-----------------------------------------*/
function calculateProfit(){}

/*-----------------------------------------
14- إحصائيات الطلبات
-----------------------------------------*/
function getOrderStatistics(){}

/*-----------------------------------------
15- جلب آخر الطلبات
-----------------------------------------*/
function latestOrders(){}

/*-----------------------------------------
16- البحث
-----------------------------------------*/
function searchOrders(){}

/*-----------------------------------------
17- تنسيق حالة الطلب
-----------------------------------------*/
function orderStatusBadge(){}

/*-----------------------------------------
18- تنسيق نوع السطحة
-----------------------------------------*/
function truckTypeName(){}

/*-----------------------------------------
19- تنسيق طريقة الدفع
-----------------------------------------*/
function paymentMethodName(){}

/*-----------------------------------------
20- تسجيل الحركة
-----------------------------------------*/
function logOrderActivity(){}