<?php
include('../include/connected.php');

/* =========================
   🚨 الإقامات المنتهية
========================= */
$iqama = $con->query("
SELECT id,name
FROM drivers
WHERE iqama_expiry_date < CURDATE()
");

$stmt = $con->prepare("
INSERT IGNORE INTO notifications
(type, ref_id, title, message)
VALUES (?, ?, ?, ?)
");

$type = 'iqama';
$title = 'إقامة منتهية';

while($i = $iqama->fetch_assoc()){

    $message = $i['name'];

    $stmt->bind_param("siss",
        $type,
        $i['id'],
        $title,
        $message
    );

    $stmt->execute();
}


/* =========================
   🆔 كرت السائق
========================= */
$card = $con->query("
SELECT id,name
FROM drivers
WHERE driver_card_expiration_date < CURDATE()
");

$type = 'card';
$title = 'كرت سائق منتهي';

while($c = $card->fetch_assoc()){

    $message = $c['name'];

    $stmt->bind_param("siss",
        $type,
        $c['id'],
        $title,
        $message
    );

    $stmt->execute();
}


/* =========================
   🚛 الفحص الدوري
========================= */
$fleet = $con->query("
SELECT id
FROM fleet
WHERE inspection_expiry < CURDATE()
");

$type = 'fleet';
$title = 'فحص دوري منتهي';

while($f = $fleet->fetch_assoc()){

    $message = "المركبة رقم ".$f['id'];

    $stmt->bind_param("siss",
        $type,
        $f['id'],
        $title,
        $message
    );

    $stmt->execute();
}


/* =========================
   🚛 كرت التشغيل
========================= */
$operation = $con->query("
SELECT id
FROM fleet
WHERE operation_expiry < CURDATE()
");

$type = 'operation';
$title = 'كرت تشغيل منتهي';

while($o = $operation->fetch_assoc()){

    $message = "المركبة رقم ".$o['id'];

    $stmt->bind_param("siss",
        $type,
        $o['id'],
        $title,
        $message
    );

    $stmt->execute();
}

echo "تم إنشاء الإشعارات بنجاح";
?>