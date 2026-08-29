<?php
include('../include/connected.php');

$alertDays = 30;

/* =========================
   دالة منع التكرار
========================= */
function notify($con,$title,$message,$type){

    $check = $con->prepare("
        SELECT id FROM notifications
        WHERE title=? AND message=? AND DATE(created_at)=CURDATE()
    ");
    $check->bind_param("ss",$title,$message);
    $check->execute();

    if($check->get_result()->num_rows == 0){

        $stmt = $con->prepare("
            INSERT INTO notifications(title,message,type)
            VALUES (?,?,?)
        ");
        $stmt->bind_param("sss",$title,$message,$type);
        $stmt->execute();
    }
}

/* =========================
   🔴 الإقامات
========================= */
$q = $con->query("
SELECT * FROM drivers
WHERE iqama_expiry_date < CURDATE()
");

while($d = $q->fetch_assoc()){
    notify($con,"إقامة منتهية","{$d['name']}","danger");
}

/* =========================
   🟠 الرخص
========================= */
$q = $con->query("
SELECT * FROM drivers
WHERE license_expiry_date <= DATE_ADD(CURDATE(), INTERVAL $alertDays DAY)
");

while($d = $q->fetch_assoc()){
    notify($con,"رخصة","{$d['name']} تحتاج متابعة","warning");
}

/* =========================
   🚛 الفحص
========================= */
$q = $con->query("
SELECT * FROM fleet
WHERE inspection_expiry_date <= DATE_ADD(CURDATE(), INTERVAL $alertDays DAY)
");

while($f = $q->fetch_assoc()){
    notify($con,"فحص مركبة","المركبة {$f['id']} تحتاج فحص","warning");
}
?>