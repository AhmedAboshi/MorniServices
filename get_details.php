<?php



include('include/connected.php');

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);
if($id == 0){
    die("❌ لا يوجد ID مرسل");
}


function notFound(){
    echo "<div class='detail-card'>لا توجد بيانات</div>";
    exit;
}
?>
<style>
.detail-card{
    background:#fff;
    border-radius:15px;
    padding:20px;
    text-align:center;
    box-shadow:0 5px 20px rgba(0,0,0,0.1);
    margin:10px;
    animation: fadeIn 0.3s ease-in-out;
}

/* 🔥 حاوية الصورة أفضل من التحكم المباشر بالصورة */
.img-box{
    width: 100%;
    max-width: 420px;
    height: 240px;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f8f8;
    border-radius: 12px;
    border: 2px solid #eee;
    overflow: hidden;
}

.detail-img{
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;
}

.info{
    text-align:right;
    line-height:2;
    font-size:15px;
    color:#444;
}

.info p{
    margin:5px 0;
}

hr{
    border:0;
    border-top:1px solid #eee;
    margin:15px 0;
}

@keyframes fadeIn{
    from{opacity:0; transform:scale(0.95);}
    to{opacity:1; transform:scale(1);}
}
</style>

<?php
/* =======================
   📄 الإقامة
======================= */
if($type == "iqama"){

    $stmt = $con->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if(!$data) notFound();

    echo "
    <div class='detail-card'>
        <h3>📄 تفاصيل الإقامة</h3>

        <img src='uploads/{$data['imagedriver']}' class='detail-img'>

        <div class='info'>
            <p><b>الاسم:</b> {$data['name']}</p>
            <p><b>رقم الإقامة:</b> {$data['national_id']}</p>
            <p><b>تاريخ الانتهاء:</b> {$data['iqama_expiry_date']}</p>
        </div>
    </div>";
}

/* =======================
   🚗 بطاقة السائق
======================= */
elseif($type == "card"){

    $stmt = $con->prepare("SELECT * FROM drivers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if(!$data) notFound();

    echo "
    <div class='detail-card'>
        <h3>🚗 تفاصيل بطاقة السائق</h3>

        <img src='uploads/{$data['imagedriver']}' class='detail-img'>

        <div class='info'>
            <p><b>الاسم:</b> {$data['name']}</p>
            <p><b>الجوال:</b> {$data['phone']}</p>
            <p><b>تاريخ الانتهاء:</b> {$data['driver_card_expiration_date']}</p>
        </div>
    </div>";
}

/* =======================
   🚙 الفحص الدوري
======================= */
elseif($type == "fleet"){

    $stmt = $con->prepare("SELECT * FROM fleet WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if(!$data) notFound();

    echo "
    <div class='detail-card'>
        <h3>🚙 الفحص الدوري</h3>

        <img src='fleetimg/img/{$data['imgfleet']}' class='detail-img'>

        <div class='info'>
            <p><b>السائق:</b> {$data['driver']}</p>
            <p><b>اللوحة:</b> {$data['plate']}</p>
            <p><b>الموديل:</b> {$data['model']}</p>
            <p><b>منطقة العمل:</b> {$data['work']}</p>
        </div>
    </div>";
}

/* =======================
   📋 كرت التشغيل
======================= */
elseif($type == "operation"){

    $stmt = $con->prepare("SELECT * FROM fleet WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if(!$data) notFound();

    echo "
    <div class='detail-card'>
        <h3>📋 كرت التشغيل</h3>

        <img src='fleetimg/img/{$data['imgfleet']}' class='detail-img'>

        <div class='info'>
            <p><b>السائق:</b> {$data['driver']}</p>
            <p><b>اللوحة:</b> {$data['plate']}</p>
            <p><b>الموديل:</b> {$data['model']}</p>
            <p><b>تاريخ الانتهاء:</b> {$data['operation_expiry']}</p>
        </div>
    </div>";
}
/* =======================
   🚨 تفاصيل الحادث
======================= */
elseif($type == "accident"){

    $stmt = $con->prepare("
    SELECT a.*, d.name AS driver_name, f.plate
    FROM accidents a
    LEFT JOIN drivers d ON a.driver_id = d.id
    LEFT JOIN fleet f ON a.vehicle_id = f.id
    WHERE a.id = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

    

    if(!$data) notFound();

    echo "
    <div class='detail-card'>
        <h3>🚨 تفاصيل الحادث</h3>

        

        <div class='info'>
            <p><b>السائق:</b> {$data['driver_name']}</p>
            <p><b>رقم اللوحة:</b> {$data['plate']}</p>
            <p><b>تاريخ الحادث:</b> {$data['accident_date']}</p>
            <p><b>الموقع:</b> {$data['location']}</p>
            <p><b>الوصف:</b> {$data['description']}</p>
           
        </div>
    </div>";
}

elseif($type == "license"){

    $stmt = $con->prepare("
        SELECT * 
        FROM drivers 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if(!$data){
        echo "❌ لا يوجد بيانات";
        exit;
    }

    $expired = (strtotime($data['license_expiry_date']) < time());

    echo "
    <div class='detail-card'>
        <h3>🚗 تفاصيل الرخصة</h3>

        <img src='uploads/{$data['imagedriver']}' class='detail-img'>

        <div class='info'>
            <p><b>الاسم:</b> {$data['name']}</p>
            <p><b> جوال:</b> {$data['phone']}</p>
            <p><b>تاريخ الانتهاء:</b> {$data['license_expiry_date']}</p>
            <p><b>الحالة:</b> " . 
                ($expired 
                    ? "<span style='color:red;font-weight:bold'>منتهية ❌</span>" 
                    : "<span style='color:green;font-weight:bold'>سارية ✅</span>"
                ) . "
            </p>
        </div>
    </div>";

    
}

else{
    echo "<div class='detail-card'>نوع غير معروف</div>";
}
?>