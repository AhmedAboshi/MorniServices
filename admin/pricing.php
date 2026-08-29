<?php
session_start();
include('../include/connected.php');

$result = $con->query("
SELECT *,
(regular_customer-regular_driver) AS regular_profit,
(hydraulic_customer-hydraulic_driver) AS hydraulic_profit,
(covered_customer-covered_driver) AS covered_profit
FROM transport_pricing
ORDER BY from_city,to_city
");

if(isset($_GET['theme'])){
    $_SESSION['theme'] = $_GET['theme'];
}

$theme = $_SESSION['theme'] ?? 'light';
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';
$txt = [

'ar'=>[
'title'=>'إدارة الأسعار',
'from'=>'من',
'to'=>'إلى',
'edit'=>'تعديل',
'delete'=>'حذف',
'profit'=>'الربح'
],

'en'=>[
'title'=>'Pricing Management',
'from'=>'From',
'to'=>'To',
'edit'=>'Edit',
'delete'=>'Delete',
'profit'=>'Profit'
]

];

?>
<style>

body.dark-mode{
    background:#121212 !important;
    color:#fff !important;
}

body.dark-mode .card{
    background:#1e1e1e !important;
    color:#fff !important;
    border-color:#333 !important;
}

body.dark-mode .card-header{
    background:#2c2c2c !important;
    color:#fff !important;
}

body.dark-mode .table{
    color:#fff !important;
}

body.dark-mode table.dataTable{
    color:#fff !important;
}

body.dark-mode table.dataTable tbody tr{
    background:#1e1e1e !important;
}

body.dark-mode table.dataTable tbody tr:nth-child(even){
    background:#252525 !important;
}

body.dark-mode .form-control{
    background:#2c2c2c !important;
    color:#fff !important;
    border:1px solid #555 !important;
}

body.dark-mode .form-select{
    background:#2c2c2c !important;
    color:#fff !important;
    border:1px solid #555 !important;
}

body.dark-mode .dataTables_filter input{
    background:#2c2c2c !important;
    color:#fff !important;
    border:1px solid #555 !important;
}

body.dark-mode .dataTables_length select{
    background:#2c2c2c !important;
    color:#fff !important;
}

body.dark-mode .page-link{
    background:#2c2c2c !important;
    color:#fff !important;
    border-color:#555 !important;
}

body.dark-mode .page-item.active .page-link{
    background:#0d6efd !important;
    border-color:#0d6efd !important;
}

</style>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">

<title><?= $txt[$lang]['title'] ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<a href="?lang=ar" class="btn btn-sm btn-primary">
العربية
</a>

<a href="?lang=en" class="btn btn-sm btn-success">
English
</a>

<a href="?theme=dark" class="btn btn-dark btn-sm">
🌙
</a>

<a href="?theme=light" class="btn btn-light btn-sm">
☀️
</a>
</head>

<body class="<?= ($theme=='dark') ? 'dark-mode' : 'bg-light' ?>">
<div class="container-fluid mt-4">

    <div class="card shadow">

        <div class="card-header d-flex justify-content-between">

            <h4>🚚 إدارة أسعار النقل</h4>

            <a href="pricing_add.php"
               class="btn btn-success">
               إضافة مسار
            </a>

        </div>

        <div class="card-body">
<?php if(isset($_GET['updated'])){ ?>
<div class="alert alert-success">
    تم تحديث السعر بنجاح
</div>
<?php } ?>

<?php if(isset($_GET['deleted'])){ ?>
<div class="alert alert-danger">
    تم حذف السعر بنجاح
</div>
<?php } ?>

            <table id="pricingTable"
                   class="table table-bordered table-striped">

                <thead>

                <tr>

                    <th>من</th>
                    <th>إلى</th>

                    <th>عادي عميل</th>
                    <th>هيدروليك عميل</th>
                    <th>مغطى عميل</th>

                    <th>عادي سائق</th>
                    <th>هيدروليك سائق</th>
                    <th>مغطى سائق</th>
                    <th>ربح العادي</th>
                    <th>ربح الهيدروليك</th>
                      <th>ربح المغطى</th>
                    <th>العمليات</th>

                </tr>

                </thead>

                <tbody>

                <?php while($row = $result->fetch_assoc()){ ?>

                <tr>

                    <td><?= $row['from_city'] ?></td>
                    <td><?= $row['to_city'] ?></td>

                    <td><?= $row['regular_customer'] ?></td>
                    
                    <td><?= $row['hydraulic_customer'] ?></td>
                    <td><?= $row['covered_customer'] ?></td>

                    <td><?= $row['regular_driver'] ?></td>
                    <td><?= $row['hydraulic_driver'] ?></td>
                    <td><?= $row['covered_driver'] ?></td>
<td>
    <span class="badge bg-success">
        <?= number_format($row['regular_profit'],2) ?>
    </span>
</td>

<td>
    <span class="badge bg-primary">
        <?= number_format($row['hydraulic_profit'],2) ?>
    </span>
</td>

<td>
    <span class="badge bg-dark">
        <?= number_format($row['covered_profit'],2) ?>
    </span>
</td>
                    <td>

    <a href="pricing_edit.php?id=<?= $row['id'] ?>"
       class="btn btn-warning btn-sm">
       تعديل
    </a>

    <a href="pricing_delete.php?id=<?= $row['id'] ?>"
       class="btn btn-danger btn-sm"
       onclick="return confirm('هل أنت متأكد من حذف هذا المسار؟')">
       حذف
    </a>

</td>

                </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){

    $('#pricingTable').DataTable({
        pageLength:25
    });

});
</script>

</body>
</html>