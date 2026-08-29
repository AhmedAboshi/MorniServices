<?php

session_start();

include(__DIR__ . '/../include/connected.php');

/* =========================================================
   اللغة
========================================================= */

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'])) {
    $lang = 'ar';
}

/* =========================================================
   الفلاتر - نفس oile.php
========================================================= */

$from      = trim($_GET['from'] ?? '');
$to        = trim($_GET['to'] ?? '');
$car_id    = (int)($_GET['car_id'] ?? 0);
$driver_id = (int)($_GET['driver_id'] ?? 0);
$search    = trim($_GET['search'] ?? '');

/* =========================================================
   الترجمة
========================================================= */

$text = [

    'ar' => [

        'title'        => 'تقرير تغييرات الزيت',
        'company'      => 'منصة الشرق الذكية للخدمات وإدارة الأسطول',

        'id'           => '#',
        'car'          => 'المركبة',
        'plate'        => 'رقم اللوحة',
        'driver'        => 'السائق',
        'oil_type'     => 'نوع الزيت',
        'change_date'  => 'تاريخ التغيير',
        'next_change'  => 'التغيير القادم',
        'current_km'   => 'العداد الحالي',
        'next_km'      => 'العداد القادم',
        'cost'         => 'التكلفة',
        'notes'        => 'الملاحظات',

        'total_records' => 'إجمالي السجلات',
        'total_cost'    => 'إجمالي التكلفة',

        'print'        => 'طباعة',

        'all'          => 'الكل',
        'from'         => 'من',
        'to'           => 'إلى',

        'no_data'      => 'لا توجد سجلات مطابقة للفلاتر المحددة',

        'sar'          => 'ريال',

    ],

    'en' => [

        'title'        => 'Oil Changes Report',
        'company'      => 'AlSharq Smart Services & Fleet Management',

        'id'           => '#',
        'car'          => 'Vehicle',
        'plate'        => 'Plate',
        'driver'       => 'Driver',
        'oil_type'     => 'Oil Type',
        'change_date'  => 'Change Date',
        'next_change'  => 'Next Change',
        'current_km'   => 'Current KM',
        'next_km'      => 'Next KM',
        'cost'         => 'Cost',
        'notes'        => 'Notes',

        'total_records' => 'Total Records',
        'total_cost'    => 'Total Cost',

        'print'        => 'Print',

        'all'          => 'All',
        'from'         => 'From',
        'to'           => 'To',

        'no_data'      => 'No records found matching the selected filters',

        'sar'          => 'SAR',

    ]

];

$t = $text[$lang];

/* =========================================================
   بناء WHERE
========================================================= */

$where = " WHERE 1=1 ";

$params = [];
$types  = "";

/* =========================================================
   فلتر التاريخ من
========================================================= */

if ($from !== '') {

    $where .= " AND t.change_date >= ? ";

    $params[] = $from;
    $types .= "s";
}

/* =========================================================
   فلتر التاريخ إلى
========================================================= */

if ($to !== '') {

    $where .= " AND t.change_date <= ? ";

    $params[] = $to;
    $types .= "s";
}

/* =========================================================
   فلتر المركبة
========================================================= */

if ($car_id > 0) {

    $where .= " AND t.car_id = ? ";

    $params[] = $car_id;
    $types .= "i";
}

/* =========================================================
   فلتر السائق
========================================================= */

if ($driver_id > 0) {

    $where .= " AND t.driver_id = ? ";

    $params[] = $driver_id;
    $types .= "i";
}

/* =========================================================
   البحث
========================================================= */

if ($search !== '') {

    $where .= "
        AND (
            f.plate LIKE ?
            OR d.name LIKE ?
            OR t.driver LIKE ?
            OR t.oil_type LIKE ?
            OR t.notes LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "sssss";
}

/* =========================================================
   الاستعلام
========================================================= */

$sql = "

    SELECT

        t.*,

        f.plate AS vehicle_plate,

        d.name AS driver_name,

        COALESCE(
            NULLIF(d.name, ''),
            NULLIF(t.driver, ''),
            '-'
        ) AS display_driver

    FROM oil_changes t

    LEFT JOIN fleet f
        ON t.car_id = f.id

    LEFT JOIN drivers d
        ON t.driver_id = d.id

    $where

    ORDER BY
        t.change_date DESC,
        t.id DESC

";

/* =========================================================
   تجهيز الاستعلام
========================================================= */

$stmt = $con->prepare($sql);

if (!$stmt) {

    die(
        "SQL Error: " .
        htmlspecialchars($con->error)
    );
}

/* =========================================================
   ربط الفلاتر
========================================================= */

if (!empty($params)) {

    if (strlen($types) !== count($params)) {

        die(
            "Filter Error: عدد أنواع البيانات لا يطابق عدد المتغيرات."
        );
    }

    $stmt->bind_param(
        $types,
        ...$params
    );
}

/* =========================================================
   تنفيذ
========================================================= */

if (!$stmt->execute()) {

    die(
        "Execute Error: " .
        htmlspecialchars($stmt->error)
    );
}

$result = $stmt->get_result();

/* =========================================================
   جلب البيانات
========================================================= */

$rows = [];

$totalCost = 0;

while ($row = $result->fetch_assoc()) {

    $rows[] = $row;

    $totalCost += (float)($row['cost'] ?? 0);
}

$totalRecords = count($rows);

/* =========================================================
   HTML
========================================================= */

?>
<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= htmlspecialchars($t['title']) ?>
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    padding: 25px;

    background: #ffffff;

    color: #222;

    font-family:
        Arial,
        Tahoma,
        sans-serif;

}

.report {

    max-width: 1500px;

    margin: auto;

}

/* =========================================================
   Header
========================================================= */

.header {

    text-align: center;

    margin-bottom: 25px;

    border-bottom: 2px solid #198754;

    padding-bottom: 15px;

}

.header h1 {

    margin: 0 0 8px;

    font-size: 26px;

    color: #198754;

}

.header h2 {

    margin: 0;

    font-size: 18px;

    color: #333;

}

.header p {

    margin: 8px 0 0;

    color: #777;

    font-size: 13px;

}

/* =========================================================
   Filters Summary
========================================================= */

.filters {

    display: flex;

    flex-wrap: wrap;

    gap: 10px;

    margin-bottom: 20px;

    padding: 12px;

    background: #f5f5f5;

    border: 1px solid #ddd;

    border-radius: 8px;

    font-size: 13px;

}

.filter-item {

    padding: 6px 10px;

    background: #fff;

    border: 1px solid #ddd;

    border-radius: 6px;

}

/* =========================================================
   Summary
========================================================= */

.summary {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 15px;

    padding: 12px;

    background: #f8f9fa;

    border: 1px solid #ddd;

    border-radius: 8px;

    font-size: 14px;

}

.total-cost {

    color: #198754;

    font-size: 18px;

    font-weight: bold;

}

/* =========================================================
   Table
========================================================= */

.table-wrapper {

    width: 100%;

    overflow-x: auto;

}

table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1100px;

}

th {

    background: #343a40;

    color: #fff;

    padding: 11px 8px;

    border: 1px solid #222;

    font-size: 13px;

    white-space: nowrap;

}

td {

    padding: 9px 8px;

    border: 1px solid #ddd;

    font-size: 12px;

    text-align: center;

    vertical-align: middle;

}

tbody tr:nth-child(even) {

    background: #f8f9fa;

}

.plate {

    display: inline-block;

    padding: 5px 9px;

    background: #eef1f4;

    border-radius: 5px;

    font-weight: bold;

}

.cost {

    color: #198754;

    font-weight: bold;

}

.total-row {

    background: #e9ecef !important;

    font-weight: bold;

}

.total-row td {

    font-size: 13px;

}

/* =========================================================
   No Data
========================================================= */

.no-data {

    text-align: center;

    padding: 40px;

    color: #777;

    font-size: 16px;

}

/* =========================================================
   Print Button
========================================================= */

.print-button {

    position: fixed;

    top: 20px;

    left: 20px;

    padding: 10px 18px;

    border: none;

    border-radius: 7px;

    background: #198754;

    color: white;

    cursor: pointer;

    font-size: 14px;

}

.print-button:hover {

    background: #157347;

}

/* =========================================================
   Print
========================================================= */

@media print {

    body {

        padding: 0;

    }

    .print-button {

        display: none !important;

    }

    .filters {

        background: #fff;

    }

    .summary {

        background: #fff;

    }

    table {

        min-width: 0;

    }

    th {

        background: #343a40 !important;

        color: #fff !important;

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

    .total-row {

        background: #e9ecef !important;

        -webkit-print-color-adjust: exact;

        print-color-adjust: exact;

    }

    @page {

        size: A4 landscape;

        margin: 10mm;

    }

}

</style>

</head>

<body>

<button
    class="print-button"
    onclick="window.print()"
>
    🖨 <?= htmlspecialchars($t['print']) ?>
</button>

<div class="report">

    <!-- =====================================================
         Header
    ====================================================== -->

    <div class="header">

        <h1>
            <?= htmlspecialchars($t['company']) ?>
        </h1>

        <h2>
            <?= htmlspecialchars($t['title']) ?>
        </h2>

        <p>
            <?= date('Y-m-d H:i') ?>
        </p>

    </div>

    <!-- =====================================================
         Applied Filters
    ====================================================== -->

    <div class="filters">

        <?php if ($search !== ''): ?>

            <div class="filter-item">

                <strong>
                    بحث:
                </strong>

                <?= htmlspecialchars($search) ?>

            </div>

        <?php endif; ?>


        <?php if ($car_id > 0): ?>

            <div class="filter-item">

                <strong>
                    <?= $t['car'] ?>:
                </strong>

                <?php

                $carName = '-';

                $carStmt = $con->prepare(
                    "SELECT plate FROM fleet WHERE id = ? LIMIT 1"
                );

                if ($carStmt) {

                    $carStmt->bind_param(
                        "i",
                        $car_id
                    );

                    $carStmt->execute();

                    $carResult = $carStmt->get_result();

                    if ($carRow = $carResult->fetch_assoc()) {

                        $carName = $carRow['plate'];

                    }

                    $carStmt->close();
                }

                ?>

                <?= htmlspecialchars($carName) ?>

            </div>

        <?php endif; ?>


        <?php if ($driver_id > 0): ?>

            <div class="filter-item">

                <strong>
                    <?= $t['driver'] ?>:
                </strong>

                <?php

                $driverName = '-';

                $driverStmt = $con->prepare(
                    "SELECT name FROM drivers WHERE id = ? LIMIT 1"
                );

                if ($driverStmt) {

                    $driverStmt->bind_param(
                        "i",
                        $driver_id
                    );

                    $driverStmt->execute();

                    $driverResult = $driverStmt->get_result();

                    if ($driverRow = $driverResult->fetch_assoc()) {

                        $driverName = $driverRow['name'];

                    }

                    $driverStmt->close();
                }

                ?>

                <?= htmlspecialchars($driverName) ?>

            </div>

        <?php endif; ?>


        <?php if ($from !== ''): ?>

            <div class="filter-item">

                <strong>
                    <?= $t['from'] ?>:
                </strong>

                <?= htmlspecialchars($from) ?>

            </div>

        <?php endif; ?>


        <?php if ($to !== ''): ?>

            <div class="filter-item">

                <strong>
                    <?= $t['to'] ?>:
                </strong>

                <?= htmlspecialchars($to) ?>

            </div>

        <?php endif; ?>


        <?php if (
            $search === '' &&
            $car_id <= 0 &&
            $driver_id <= 0 &&
            $from === '' &&
            $to === ''
        ): ?>

            <div class="filter-item">

                <?= $t['all'] ?>

            </div>

        <?php endif; ?>

    </div>

    <!-- =====================================================
         Summary
    ====================================================== -->

    <div class="summary">

        <div>

            <?= $t['total_records'] ?>:

            <strong>
                <?= number_format($totalRecords) ?>
            </strong>

        </div>

        <div>

            <?= $t['total_cost'] ?>:

            <span class="total-cost">

                <?= number_format($totalCost, 2) ?>

                <?= $t['sar'] ?>

            </span>

        </div>

    </div>

    <!-- =====================================================
         Table
    ====================================================== -->

    <div class="table-wrapper">

        <table>

            <thead>

                <tr>

                    <th>
                        <?= $t['id'] ?>
                    </th>

                    <th>
                        <?= $t['car'] ?>
                    </th>

                    <th>
                        <?= $t['plate'] ?>
                    </th>

                    <th>
                        <?= $t['driver'] ?>
                    </th>

                    <th>
                        <?= $t['oil_type'] ?>
                    </th>

                    <th>
                        <?= $t['change_date'] ?>
                    </th>

                    <th>
                        <?= $t['next_change'] ?>
                    </th>

                    <th>
                        <?= $t['current_km'] ?>
                    </th>

                    <th>
                        <?= $t['next_km'] ?>
                    </th>

                    <th>
                        <?= $t['cost'] ?>
                    </th>

                    <th>
                        <?= $t['notes'] ?>
                    </th>

                </tr>

            </thead>

            <tbody>

            <?php if (empty($rows)): ?>

                <tr>

                    <td
                        colspan="11"
                        class="no-data"
                    >

                        <?= $t['no_data'] ?>

                    </td>

                </tr>

            <?php else: ?>

                <?php foreach ($rows as $row): ?>

                    <tr>

                        <td>
                            #<?= (int)$row['id'] ?>
                        </td>

                        <td>

                            <span class="plate">

                                <?= htmlspecialchars(
                                    $row['car_id'] ?? '-'
                                ) ?>

                            </span>

                        </td>

                        <td>

                            <span class="plate">

                                <?= htmlspecialchars(
                                    $row['vehicle_plate'] ?? '-'
                                ) ?>

                            </span>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $row['display_driver'] ?? '-'
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $row['oil_type'] ?? '-'
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $row['change_date'] ?? '-'
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $row['next_change'] ?? '-'
                            ) ?>

                        </td>

                        <td>

                            <?= number_format(
                                (int)($row['current_km'] ?? 0)
                            ) ?>

                            KM

                        </td>

                        <td>

                            <?= number_format(
                                (int)($row['next_km'] ?? 0)
                            ) ?>

                            KM

                        </td>

                        <td class="cost">

                            <?= number_format(
                                (float)($row['cost'] ?? 0),
                                2
                            ) ?>

                            <?= $t['sar'] ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $row['notes'] ?? '-'
                            ) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>


                <!-- إجمالي -->

                <tr class="total-row">

                    <td colspan="9">

                        <?= $t['total_cost'] ?>

                    </td>

                    <td class="cost">

                        <?= number_format(
                            $totalCost,
                            2
                        ) ?>

                        <?= $t['sar'] ?>

                    </td>

                    <td></td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<script>

/*
|--------------------------------------------------------------------------
| فتح نافذة الطباعة تلقائياً
|--------------------------------------------------------------------------
*/

window.addEventListener('load', function () {

    setTimeout(function () {

        window.print();

    }, 500);

});

</script>

</body>

</html>

<?php

$stmt->close();

?>