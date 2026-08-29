<div class="row">

<div class="col-md-6 mb-3">
<div class="card border-0 shadow-sm">
<div class="card-body">

<h6 class="text-muted">رقم اللوحة</h6>

<h5><?= htmlspecialchars($row['plate']) ?></h5>

</div>
</div>
</div>

<div class="col-md-6 mb-3">
<div class="card border-0 shadow-sm">
<div class="card-body">

<h6 class="text-muted">نوع المركبة</h6>

<h5><?= htmlspecialchars($row['typefleet']) ?></h5>

</div>
</div>
</div>

<div class="col-md-6 mb-3">
<div class="card border-0 shadow-sm">
<div class="card-body">

<h6 class="text-muted">الفئة</h6>

<h5><?= htmlspecialchars($row['classify']) ?></h5>

</div>
</div>
</div>

<div class="col-md-6 mb-3">
<div class="card border-0 shadow-sm">
<div class="card-body">

<h6 class="text-muted">الموديل</h6>

<h5><?= htmlspecialchars($row['model']) ?></h5>

</div>
</div>
</div>

<div class="col-md-6 mb-3">
<div class="card border-0 shadow-sm">
<div class="card-body">

<h6 class="text-muted">اللون</h6>

<h5><?= htmlspecialchars($row['colorfleet']) ?></h5>

</div>
</div>
</div>

<div class="col-md-6 mb-3">
<div class="card border-0 shadow-sm">
<div class="card-body">

<h6 class="text-muted">نوع العمل</h6>

<h5><?= htmlspecialchars($row['work']) ?></h5>

</div>
</div>
</div>

<?php if(!empty($row['inspection_expiry'])){ ?>

<div class="col-md-6 mb-3">

<div class="card border-0 shadow-sm">

<div class="card-body">

<h6 class="text-muted">

انتهاء الفحص

</h6>

<h5>

<?= htmlspecialchars($row['inspection_expiry']) ?>

</h5>

</div>

</div>

</div>

<?php } ?>

<?php if(!empty($row['operation_expiry'])){ ?>

<div class="col-md-6 mb-3">

<div class="card border-0 shadow-sm">

<div class="card-body">

<h6 class="text-muted">

انتهاء كرت التشغيل

</h6>

<h5>

<?= htmlspecialchars($row['operation_expiry']) ?>

</h5>

</div>

</div>

</div>

<?php } ?>

</div>