<?php 
$total_documents = 0;
$active_documents = 0;
$expiring_documents = 0;
$expired_documents = 0;

$result = mysqli_query($con,"
SELECT *
FROM vehicle_documents
WHERE car_id='$id'
");

$today = date('Y-m-d');

$documents = [];

while($row = mysqli_fetch_assoc($result)){

    $documents[$row['document_type']] = $row;

    $total_documents++;

    if(!empty($row['expiry_date'])){

        if($row['expiry_date'] < $today){

            $expired_documents++;

        }
        elseif(strtotime($row['expiry_date']) <= strtotime('+30 days')){

            $expiring_documents++;

        }
        else{

            $active_documents++;

        }

    }

}
$documentTypes = [
     [ "title" => "استمارة المركبة", 
     "icon" => "bi-card-text",
      "color" => "primary" ],
       [ "title" => "وثيقة التأمين",
        "icon" => "bi-shield-check",
         "color" => "success" ], 
         [ "title" => "الفحص الدوري",
          "icon" => "bi-clipboard-check",
           "color" => "warning" ], 
           [ "title" => "كرت التشغيل", 
           "icon" => "bi-truck",
            "color" => "info" ], 
            [ "title" => "بطاقة السائق", 
            "icon" => "bi-person-vcard", 
            "color" => "secondary" ] ];

             
              ?>

               <div class="d-flex justify-content-between align-items-center mb-4"> 
                <h4 class="mb-0"> <i class="bi bi-folder2-open"></i> مستندات المركبة </h4> 
            </div> 

            <div class="row mb-4">

    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-primary text-center">
            <div class="card-body">
                <h3 class="text-primary">
                    <?= $total_documents; ?>
                </h3>
                <span>إجمالي المستندات</span>
            </div>
        </div>
    </div>


    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-success text-center">
            <div class="card-body">
                <h3 class="text-success">
                    <?= $active_documents; ?>
                </h3>
                <span>سارية</span>
            </div>
        </div>
    </div>


    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-warning text-center">
            <div class="card-body">
                <h3 class="text-warning">
                    <?= $expiring_documents; ?>
                </h3>
                <span>تنتهي قريبًا</span>
            </div>
        </div>
    </div>


    <div class="col-md-3 mb-3">
        <div class="card shadow-sm border-danger text-center">
            <div class="card-body">
                <h3 class="text-danger">
                    <?= $expired_documents; ?>
                </h3>
                <span>منتهية</span>
            </div>
        </div>
    </div>

</div>

            <div class="row"> 
                <?php foreach($documentTypes as $doc){ ?> <div class="col-md-6 col-lg-4 mb-4"> 
                    <?php

$card_border = "";

if(isset($documents[$doc['title']])){

    $expiry = $documents[$doc['title']]['expiry_date'];

    if(empty($expiry)){

        $card_border = "border-secondary";

    }
    elseif($expiry < date('Y-m-d')){

        $card_border = "border-danger";

    }
    elseif(strtotime($expiry) <= strtotime('+30 days')){

        $card_border = "border-warning";

    }
    else{

        $card_border = "border-success";

    }

}

?>

<div class="card shadow-sm border-2 <?= $card_border; ?> document-card">

    <div class="card-header bg-<?= $doc['color']; ?> text-white">
        <h5 class="mb-0 text-center">
            <i class="bi <?= $doc['icon']; ?>"></i>
            <?= $doc['title']; ?>
        </h5>
    </div>


    <div class="card-body text-center">


<?php if(isset($documents[$doc['title']])){ 

$d = $documents[$doc['title']];

?>

<div class="mb-3">

<?php

$thumb = "../".$d['file_path'];

$ext = strtolower(pathinfo($thumb, PATHINFO_EXTENSION));

if(in_array($ext,['jpg','jpeg','png','gif','webp'])){

?>

<a href="view_document.php?id=<?= $d['id']; ?>">

<img src="<?= $thumb ?>"
class="img-fluid rounded shadow-sm"
style="height:160px;width:100%;object-fit:cover;">

</a>


<?php }else{ ?>

<a href="view_document.php?id="<?= $d['id']; ?>">

<i class="bi bi-file-earmark-pdf-fill text-danger"
style="font-size:70px;"></i>

</a>

<?php } ?>

</div>


<?php

$today=date('Y-m-d');


if(empty($d['expiry_date'])){

$status_text="بدون تاريخ انتهاء";
$status_color="secondary";

}
elseif($d['expiry_date'] < $today){

$status_text="منتهي";
$status_color="danger";

}
elseif(strtotime($d['expiry_date']) <= strtotime('+30 days')){

$status_text="ينتهي قريباً";
$status_color="warning";

}
else{

$status_text="ساري";
$status_color="success";

}

?>


<span class="badge bg-<?= $status_color; ?> mb-3 p-2">

<i class="bi bi-info-circle"></i>

<?= $status_text ?>

</span>


<div class="bg-light rounded p-2 mb-3">


<p class="mb-2">

<strong>
<i class="bi bi-hash"></i>
رقم المستند
</strong>

<br>

<?= $d['document_number']; ?>

</p>


<p class="mb-2">

<strong>
<i class="bi bi-calendar-check"></i>
تاريخ الانتهاء
</strong>

<br>

<?= !empty($d['expiry_date']) ? $d['expiry_date'] : '-' ?>

</p>


<?php

if(!empty($d['expiry_date'])){

$today_date=new DateTime();
$expiry=new DateTime($d['expiry_date']);

$days=$today_date->diff($expiry)->days;


if($expiry >= $today_date){

if($days<=30){

$remaining="<span class='badge bg-warning text-dark'>
متبقي $days يوم
</span>";

}else{

$remaining="<span class='badge bg-success'>
متبقي $days يوم
</span>";

}

}else{

$remaining="<span class='badge bg-danger'>
منتهي منذ $days يوم
</span>";

}

}else{

$remaining="<span class='badge bg-secondary'>
لا يوجد تاريخ انتهاء
</span>";

}

?>


<div>
<?= $remaining ?>
</div>


</div>



<div class="row g-2">


<div class="col-6">

<a href="view_document.php?id=<?= $d['id']; ?>"
class="btn btn-primary w-100">

<i class="bi bi-eye"></i>

عرض

</a>

</div>


<div class="col-6">

<a href="../<?= $d['file_path']; ?>"
download
class="btn btn-info w-100">

<i class="bi bi-download"></i>

تحميل

</a>

</div>



<div class="col-6">


<a href="replace_document.php?id=<?= $d['id']; ?>"
class="btn btn-warning w-100">

<i class="bi bi-arrow-repeat"></i>

استبدال

</a>


</div>



<div class="col-6">


<a href="delete_document.php?id=<?= $d['id']; ?>"
class="btn btn-danger w-100"
onclick="return confirm('هل أنت متأكد من حذف هذا المستند؟');">


<i class="bi bi-trash"></i>

حذف


</a>


</div>


</div>



<?php }else{ ?>


<div class="py-4">


<i class="bi <?= $doc['icon']; ?>"
style="font-size:55px;color:#ced4da;"></i>


<p class="text-muted mt-3">

لا يوجد مستند مرفوع

</p>


<a href="add_document.php?car_id=<?= $id; ?>&type=<?= urlencode($doc['title']); ?>"
class="btn btn-<?= $doc['color']; ?> w-100">


<i class="bi bi-upload"></i>

رفع المستند


</a>


</div>


<?php } ?>


</div>

</div>
            </div> 


            <?php } ?>
         </div>
                    
