<?php
session_start();
include('../include/connected.php');

/* =========================
   🌐 اللغة
========================= */
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';
/* =========================
   🚚 المركبة القادمة من التفاصيل
========================= */

$car = null;

if(isset($_GET['car_id'])){

    $car_id = (int)$_GET['car_id'];

    $stmt = $con->prepare("
        SELECT *
        FROM fleet
        WHERE id=?
        LIMIT 1
    ");

    $stmt->bind_param("i",$car_id);
    $stmt->execute();

    $car = $stmt->get_result()->fetch_assoc();
    $vehicle_data = $car;

$vehicle_driver_id = 0;

if($vehicle_data){

    $stmt = $con->prepare("
        SELECT id
        FROM drivers
        WHERE name=?
        LIMIT 1
    ");

    $stmt->bind_param("s",$vehicle_data['driver']);
    $stmt->execute();

    $driver = $stmt->get_result()->fetch_assoc();

    if($driver){
        $vehicle_driver_id = $driver['id'];
    }
}
}

$car_id = intval($_GET['car_id'] ?? 0);
$vehicle_data = null;
$vehicle_driver_id = 0;

if($car_id > 0){

    $stmt = $con->prepare("
    SELECT 
        fleet.*,
        drivers.id AS fleet_driver_id
    FROM fleet
    LEFT JOIN drivers 
        ON fleet.driver = drivers.name
    WHERE fleet.id=?
    LIMIT 1
    ");

    $stmt->bind_param("i",$car_id);
    $stmt->execute();

    $vehicle_data = $stmt->get_result()->fetch_assoc();

    if($vehicle_data){
        $vehicle_driver_id = $vehicle_data['fleet_driver_id'];
    }
    
}
$translations = include(__DIR__ . "/../include/lang/$lang.php");

function t($key){
    global $translations;
    return $translations[$key] ?? $key;
}

/* =========================
   👤 جلب السائقين
========================= */
$drivers_result = mysqli_query($con, "SELECT id, name FROM drivers");

$drivers = [];
while($row = mysqli_fetch_assoc($drivers_result)){
    $drivers[] = $row;
}

/* =========================
   🗑️ حذف
========================= */
if(isset($_POST['delete_id'])){
    $id = (int) $_POST['delete_id'];

    $stmt = $con->prepare("DELETE FROM maintenance WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

   header("Location: maintenanceview.php?lang=" . urlencode($lang) . "&success=1");
exit();
}

/* =========================
   ✏️ تعديل
========================= */
$edit_row = null;

if(isset($_GET['edit'])){
    $id = (int) $_GET['edit'];

    $stmt = $con->prepare("SELECT * FROM maintenance WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $edit_row = $stmt->get_result()->fetch_assoc();
}

/* =========================
   💾 حفظ
========================= */
if(isset($_POST['save'])){

    $id = (int) ($_POST['id'] ?? 0);
    $driver_id = (int)$_POST['driver_id'];
    $vehicle_name = trim($_POST['vehicle_name']);
    $plate_number = trim($_POST['plate_number']);
    $maintenance_type = trim($_POST['maintenance_type']);
    $cost = (float) $_POST['cost'];
    $notes = trim($_POST['notes'] ?? '');
    $maintenance_date = $_POST['maintenance_date'];
     
   if($id > 0){

    $stmt = $con->prepare("
        UPDATE maintenance SET
            driver_id = ?,
            vehicle_name = ?,
            plate_number = ?,
            maintenance_type = ?,
            cost = ?,
            notes = ?,
            maintenance_date = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "isssdssi",
        $driver_id,
        $vehicle_name,
        $plate_number,
        $maintenance_type,
        $cost,
        $notes,
        $maintenance_date,
        $id
    );

}

    else {

        $stmt = $con->prepare("
    INSERT INTO maintenance
    (
        car_id,
        driver_id,
        vehicle_name,
        plate_number,
        maintenance_type,
        cost,
        notes,
        maintenance_date
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iisssdss",
    $car_id,
    $driver_id,
    $vehicle_name,
    $plate_number,
    $maintenance_type,
    $cost,
    $notes,
    $maintenance_date
);
    }

    $stmt->execute();

    header("Location: maintenanceview.php?lang=$lang&success=1");
    exit();
}

/* =========================
   📊 عرض
========================= */
$result = mysqli_query($con, "
SELECT maintenance.*, drivers.name AS driver_name
FROM maintenance
LEFT JOIN drivers ON maintenance.driver_id = drivers.id
ORDER BY maintenance.id DESC
");

$result = mysqli_query($con, "
SELECT maintenance.*, drivers.name AS driver_name
FROM maintenance
LEFT JOIN drivers ON maintenance.driver_id = drivers.id
ORDER BY maintenance.id DESC
");
/* =========================
   🚗 جلب مركبات السائق
========================= */

if (isset($_GET['get_driver_vehicles'])) {

    header('Content-Type: application/json; charset=utf-8');

    $driver_id = (int)$_GET['get_driver_vehicles'];

    $vehicles = [];

    $stmt = $con->prepare("
        SELECT
            id,
            typefleet,
            plate,
            driver
        FROM fleet
        WHERE driver = (
            SELECT name
            FROM drivers
            WHERE id = ?
            LIMIT 1
        )
        ORDER BY id DESC
    ");

    $stmt->bind_param("i", $driver_id);
    $stmt->execute();

    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {

        $vehicles[] = [
            'id' => (int)$row['id'],
            'name' => $row['typefleet'] ?? '',
            'plate' => $row['plate'] ?? ''
        ];
    }

    echo json_encode(
        $vehicles,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang == 'ar' ? 'rtl' : 'ltr' ?>">

<head>
<meta charset="UTF-8">
<title><?= t('maintenance') ?></title>

<style>
body {font-family:Arial;background:#f4f6f9;}

.container {
    width:95%;
    margin:20px auto;
    background:#fff;
    padding:20px;
    border-radius:10px;
}

input, select, textarea {
    width:100%;padding:10px;margin:5px 0;
    border:1px solid #ccc;border-radius:6px;
}

button {
    padding:8px;border:none;border-radius:5px;cursor:pointer;
}

.save {background:green;color:#fff;width:100%;}
.edit {background:orange;color:#fff;text-decoration:none;padding:5px 10px;border-radius:5px;}
.delete {background:red;color:#fff;}

table {width:100%;border-collapse:collapse;margin-top:20px;}
th, td {padding:10px;border:1px solid #ddd;text-align:center;}
th {background:#007bff;color:#fff;}

.success {color:green;text-align:center;font-weight:bold;}

.lang a {margin:0 5px;text-decoration:none;font-weight:bold;}
.lang-switch a{
    padding: 6px 12px;
    border-radius: 20px;
    text-decoration: none;
    color: #333;
    background: #eee;
    transition: 0.3s;
}

.lang-switch a:hover{
    background: #28a745;
    color: #fff;
}

.lang-switch .active{
    background: #28a745;
    color: #fff;
    font-weight: bold;
}

.lang-switch span{
    color: #999;
}
</style>
</head>

<body>

<div class="container">

<!-- 🌍 اللغة -->
<div class="lang-switch">
    <a href="?lang=ar" class="<?= $lang=='ar'?'active':'' ?>">🌍🇸🇦 عربي</a>
    <span>|</span>
    <a href="?lang=en" class="<?= $lang=='en'?'active':'' ?>">🌍🇬🇧 English</a>
</div>


<h2><?= $edit_row ? t('edit_maintenance') : t('add_maintenance') ?></h2>

<?php if(isset($_GET['success'])): ?>
<p class="success"><?= t('success') ?></p>
<?php endif; ?>

<!-- نموذج الإضافة / التعديل -->
<form method="post" id="maintenanceForm">

    <input type="hidden"
           name="id"
           value="<?= (int)($edit_row['id'] ?? 0) ?>">

    

    <!-- السائق -->
    <label>
        <?= $lang === 'ar' ? 'السائق' : 'Driver' ?>
    </label>

    <select name="driver_id" id="driver_id" required>

        <option value="">
            <?= $lang === 'ar' ? 'اختر السائق' : 'Select Driver' ?>
        </option>

        <?php foreach($drivers as $d): ?>

            <option
                value="<?= (int)$d['id'] ?>"
                <?= (
                    isset($edit_row['driver_id']) &&
                    (int)$edit_row['driver_id'] === (int)$d['id']
                ) ? 'selected' : '' ?>
            >
                <?= htmlspecialchars($d['name']) ?>
            </option>

        <?php endforeach; ?>

    </select>


    <!-- المركبة -->
    <label>
        <?= $lang === 'ar' ? 'المركبة' : 'Vehicle' ?>
    </label>

    <select name="vehicle_select" id="vehicle_select" required>

        <option value="">
            <?= $lang === 'ar' ? 'اختر السائق أولاً' : 'Select driver first' ?>
        </option>

    </select>


    <!-- اسم المركبة الذي سيتم حفظه -->
    <input
        type="hidden"
        name="vehicle_name"
        id="vehicle_name"
        value="<?= htmlspecialchars(
            $edit_row['vehicle_name'] ?? '',
            ENT_QUOTES
        ) ?>"
    >


    <!-- رقم اللوحة -->
    <label>
        <?= $lang === 'ar' ? 'رقم اللوحة' : 'Plate Number' ?>
    </label>

    <input
        type="text"
        name="plate_number"
        id="plate_number"
        value="<?= htmlspecialchars(
            $edit_row['plate_number'] ?? '',
            ENT_QUOTES
        ) ?>"
        readonly
    >


    <!-- نوع الصيانة -->
    <label>
        <?= t('maintenance_type') ?>
    </label>

    <input
        type="text"
        name="maintenance_type"
        placeholder="<?= t('maintenance_type') ?>"
        value="<?= htmlspecialchars(
            $edit_row['maintenance_type'] ?? '',
            ENT_QUOTES
        ) ?>"
        required
    >


    <!-- التكلفة -->
    <label>
        <?= t('cost') ?>
    </label>

    <input
        type="number"
        step="0.01"
        min="0"
        name="cost"
        placeholder="<?= t('cost') ?>"
        value="<?= htmlspecialchars(
            $edit_row['cost'] ?? '',
            ENT_QUOTES
        ) ?>"
        required
    >


    <!-- التاريخ -->
    <label>
        <?= $lang === 'ar' ? 'تاريخ الصيانة' : 'Maintenance Date' ?>
    </label>

    <input
        type="date"
        name="maintenance_date"
        value="<?= htmlspecialchars(
            $edit_row['maintenance_date']
            ?? date('Y-m-d'),
            ENT_QUOTES
        ) ?>"
        required
    >


    <!-- الملاحظات -->
    <label>
        <?= t('notes') ?>
    </label>

    <textarea
        name="notes"
        rows="4"
        placeholder="<?= t('notes') ?>"
    ><?= htmlspecialchars(
        $edit_row['notes'] ?? '',
        ENT_QUOTES
    ) ?></textarea>


    <!-- الحفظ -->
    <button
        class="save"
        name="save"
        type="submit"
    >
        <?= $edit_row ? '💾 ' . t('update') : '💾 ' . t('add') ?>
    </button>

</form>

<!-- الجدول -->
<table>
<tr>
    <th><?= t('driver') ?></th>
    <th><?= t('workshop') ?></th>
    <th><?= t('plate_number') ?></th>
    <th><?= t('maintenance_type') ?></th>
    <th><?= t('cost') ?></th>
    <th><?= t('date') ?></th>
    <th><?= t('actions') ?></th>
</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>
<tr>
    <td><?= htmlspecialchars($row['driver_name'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['vehicle_name']) ?></td>
    <td><?= htmlspecialchars($row['plate_number']) ?></td>
    <td><?= htmlspecialchars($row['maintenance_type']) ?></td>
    <td><?= htmlspecialchars($row['cost']) ?></td>
    <td><?= htmlspecialchars($row['maintenance_date']) ?></td>

    <td>
        <a class="edit" href="?edit=<?= $row['id'] ?>&lang=<?= $lang ?>">
            <?= t('edit') ?>
        </a>

        <form method="post" style="display:inline;">
            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
            <button class="delete" type="submit" onclick="return confirm('Are you sure?')">
                <?= t('delete') ?>
            </button>
        </form>
    </td>
</tr>
<?php } ?>

</table>

</div>
<script>

document.addEventListener('DOMContentLoaded', function () {

    const driverSelect  = document.getElementById('driver_id');
    const vehicleSelect = document.getElementById('vehicle_select');

    const vehicleName   = document.getElementById('vehicle_name');
    const plateNumber   = document.getElementById('plate_number');


    /*
     * لا نستخدم car_id لأن جدول maintenance
     * لا يحتوي على هذا العمود
     */


    function loadVehicles(driverId) {

        vehicleSelect.innerHTML = '';


        if (!driverId) {

            vehicleSelect.innerHTML =
                '<option value="">اختر السائق أولاً</option>';

            vehicleName.value = '';
            plateNumber.value = '';

            return;
        }


        vehicleSelect.innerHTML =
            '<option value="">جاري تحميل المركبات...</option>';


        fetch(
            'maintenance.php?get_driver_vehicles=' +
            encodeURIComponent(driverId)
        )

        .then(response => {

            if (!response.ok) {
                throw new Error('HTTP Error');
            }

            return response.json();

        })

        .then(vehicles => {

            vehicleSelect.innerHTML =
                '<option value="">اختر المركبة</option>';


            if (!vehicles.length) {

                vehicleSelect.innerHTML =
                    '<option value="">لا توجد مركبات مرتبطة بهذا السائق</option>';

                vehicleName.value = '';
                plateNumber.value = '';

                return;
            }


            vehicles.forEach(vehicle => {

                const option =
                    document.createElement('option');


                /*
                 * قيمة الخيار = رقم المركبة
                 */
                option.value = vehicle.id;


                /*
                 * النص الظاهر
                 */
                option.textContent =
                    vehicle.name +
                    ' - ' +
                    vehicle.plate;


                /*
                 * بيانات المركبة
                 */
                option.dataset.name =
                    vehicle.name;

                option.dataset.plate =
                    vehicle.plate;


                vehicleSelect.appendChild(option);

            });


            /*
             * إذا كانت هناك مركبة واحدة فقط
             * نختارها تلقائياً
             */
            if (vehicles.length === 1) {

                vehicleSelect.value =
                    vehicles[0].id;

                vehicleName.value =
                    vehicles[0].name;

                plateNumber.value =
                    vehicles[0].plate;

            }

        })

        .catch(error => {

            console.error(
                'Vehicle loading error:',
                error
            );

            vehicleSelect.innerHTML =
                '<option value="">حدث خطأ في تحميل المركبات</option>';

            vehicleName.value = '';
            plateNumber.value = '';

        });

    }


    /*
     * عند تغيير السائق
     */
    driverSelect.addEventListener(
        'change',
        function () {

            loadVehicles(this.value);

        }
    );


    /*
     * عند اختيار المركبة
     */
    vehicleSelect.addEventListener(
        'change',
        function () {

            const option =
                this.options[this.selectedIndex];


            if (!option || !option.value) {

                vehicleName.value = '';
                plateNumber.value = '';

                return;
            }


            vehicleName.value =
                option.dataset.name || '';

            plateNumber.value =
                option.dataset.plate || '';

        }
    );


    /*
     * عند فتح الصفحة
     * إذا كان السائق محدداً مسبقاً
     */
    if (driverSelect.value) {

        loadVehicles(
            driverSelect.value
        );

    }

});

</script>
</body>
</html>