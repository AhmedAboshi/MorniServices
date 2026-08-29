<div class="tab-pane fade" id="maintenance" role="tabpanel">


<div class="d-flex justify-content-between align-items-center mb-3">

    <h5>
        <i class="bi bi-tools"></i>
        سجل الصيانة
    </h5>


    <a href="add_maintenance.php?plate=<?= $plate ?>"
       class="btn btn-success btn-sm">

        <i class="bi bi-plus-circle"></i>
        إضافة صيانة

    </a>

</div>
<pre><?php print_r($maintenance_stats); ?></pre>
<div class="row mb-4">

    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">🛠 عدد الصيانات</h6>
                <h3><?= $maintenance_stats['total']; ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">💰 إجمالي التكلفة</h6>
                <h3><?= number_format($maintenance_stats['total_cost'],2); ?> ريال</h3>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted">📅 آخر صيانة</h6>
                <h5>
                    <?= $maintenance_stats['last_date'] ?: 'لا يوجد'; ?>
                </h5>
            </div>
        </div>
    </div>

</div>

<?php if($maintenance && $maintenance->num_rows > 0){ ?>



<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">


<thead class="table-dark">

<tr>

<th>التاريخ</th>
<th>نوع الصيانة</th>
<th>السائق</th>
<th>التكلفة</th>
<th>الملاحظات</th>

</tr>

</thead>


<tbody>


<?php while($m = $maintenance->fetch_assoc()){ ?>

<tr>

<td>
<?= $m['maintenance_date'] ?>
</td>


<td>
<?= $m['maintenance_type'] ?>
</td>


<td>
<?= $m['driver'] ?>
</td>


<td>
<?= number_format($m['cost'],2) ?>
</td>


<td>
<?= $m['notes'] ?>
</td>


</tr>


<?php } ?>


</tbody>


</table>

</div>


<?php } else { ?>


<div class="alert alert-info text-center">

<i class="bi bi-info-circle"></i>

لا توجد سجلات صيانة لهذه المركبة

</div>


<?php } ?>


</div>