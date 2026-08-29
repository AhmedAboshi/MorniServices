<div class="row mb-4">


    <div class="col-md-3">

        <div class="card shadow-sm border-0">

            <div class="card-body text-center">

                <h6 class="text-muted">
                    🛞 عدد الإطارات
                </h6>

                <h3>
                    <?= $tire_stats['total'] ?? 0 ?>
                </h3>

            </div>

        </div>

    </div>



    <div class="col-md-3">

        <div class="card shadow-sm border-0">

            <div class="card-body text-center">

                <h6 class="text-muted">
                    💰 إجمالي التكلفة
                </h6>

                <h3>
                    <?= number_format($tire_stats['total_cost'] ?? 0,2) ?>
                    ريال
                </h3>

            </div>

        </div>

    </div>



    <div class="col-md-3">

        <div class="card shadow-sm border-0">

            <div class="card-body text-center">

                <h6 class="text-muted">
                    📅 آخر تغيير
                </h6>

                
                    <h5>
<?= $tire_stats['last_change'] ?? 'لا يوجد'; ?>
</h5>
                

            </div>

        </div>

    </div>
<div class="col-md-3">
    <div class="card shadow-sm border-0">
        <div class="card-body text-center">
            <h6 class="text-muted">⏳ القادم</h6>
            <h5>
<?= isset($next_tire['next_km']) && $next_tire['next_km']
    ? number_format($next_tire['next_km']) . ' كم'
    : 'لا يوجد'; ?>
</h5>
        </div>
    </div>
</div>

</div>



<?php if($tires && $tires->num_rows > 0){ ?>


<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">


<thead class="table-dark">
<tr>
    <th>التاريخ</th>
    <th>السائق</th>
    <th>نوع الإطار</th>
    <th>العداد الحالي</th>
    <th>التغيير القادم</th>
    <th>التاريخ القادم</th>
    <th>التكلفة</th>
    <th>الملاحظات</th>
</tr>
</thead>

<tbody>

<?php while($t = $tires->fetch_assoc()){ ?>

<tr>

    <td><?= $t['change_date']; ?></td>

    <td><?= $t['driver']; ?></td>

    <td><?= $t['tire_type']; ?></td>

    <td><?= number_format($t['current_km']); ?> كم</td>

    <td><?= number_format($t['next_km']); ?> كم</td>

    <td><?= $t['next_change']; ?></td>

    <td><?= number_format($t['cost']); ?> ريال</td>

    <td><?= $t['notes']; ?></td>

</tr>

<?php } ?>

</tbody>

</div>



<?php } else { ?>


<div class="alert alert-info text-center">

<i class="bi bi-info-circle"></i>

لا توجد سجلات إطارات لهذه المركبة

</div>


<?php } ?>