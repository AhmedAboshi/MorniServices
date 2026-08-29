
<?php

session_start();

include('../include/core.php');
include('../include/connected.php');

mysqli_set_charset($con, 'utf8mb4');

/* =========================================================
   اللغة
========================================================= */

if (
    isset($_GET['lang']) &&
    in_array($_GET['lang'], ['ar', 'en'], true)
) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'ar';

if (!in_array($lang, ['ar', 'en'], true)) {
    $lang = 'ar';
}

/* =========================================================
   الوضع الليلي
========================================================= */

if (isset($_GET['dark'])) {

    $_SESSION['dark'] =
        $_GET['dark'] === '1'
            ? '1'
            : '0';
}

$dark = $_SESSION['dark'] ?? '0';

/* =========================================================
   حماية الصفحة
========================================================= */

/*
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin.php");
    exit();
}
*/

/* =========================================================
   CSRF
========================================================= */

if (empty($_SESSION['section_csrf'])) {

    $_SESSION['section_csrf'] =
        bin2hex(random_bytes(32));
}

$csrf =
    $_SESSION['section_csrf'];

/* =========================================================
   رسائل الصفحة
========================================================= */

$flashMessage = '';
$flashType = 'success';

/* =========================================================
   إضافة قسم
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_section'])
) {

    $postedCsrf =
        $_POST['csrf'] ?? '';

    if (
        !hash_equals(
            $csrf,
            $postedCsrf
        )
    ) {

        $flashMessage =
            $lang === 'ar'
                ? 'طلب غير صالح.'
                : 'Invalid request.';

        $flashType =
            'danger';

    } else {

        $sectionName =
            trim(
                $_POST['sectionname']
                ?? ''
            );

        if ($sectionName === '') {

            $flashMessage =
                __('Please fill in the field');

            $flashType =
                'warning';

        } elseif (
            mb_strlen(
                $sectionName,
                'UTF-8'
            ) > 50
        ) {

            $flashMessage =
                __('The section name must not exceed 50 characters');

            $flashType =
                'warning';

        } else {

            /* منع التكرار */

            $check =
                $con->prepare("
                    SELECT id
                    FROM section
                    WHERE sectionname = ?
                    LIMIT 1
                ");

            if (!$check) {

                $flashMessage =
                    'SQL Error: ' .
                    $con->error;

                $flashType =
                    'danger';

            } else {

                $check->bind_param(
                    's',
                    $sectionName
                );

                $check->execute();

                $exists =
                    $check
                        ->get_result()
                        ->fetch_assoc();

                $check->close();

                if ($exists) {

                    $flashMessage =
                        $lang === 'ar'
                            ? 'القسم موجود بالفعل.'
                            : 'Section already exists.';

                    $flashType =
                        'warning';

                } else {

                    $insert =
                        $con->prepare("
                            INSERT INTO section
                            (
                                sectionname
                            )
                            VALUES (?)
                        ");

                    if (!$insert) {

                        $flashMessage =
                            'SQL Error: ' .
                            $con->error;

                        $flashType =
                            'danger';

                    } else {

                        $insert->bind_param(
                            's',
                            $sectionName
                        );

                        if ($insert->execute()) {

                            $insert->close();

                            header(
                                "Location: sectionadmin.php?" .
                                http_build_query([
                                    'lang' =>
                                        $lang,
                                    'dark' =>
                                        $dark,
                                    'added' =>
                                        1
                                ])
                            );

                            exit();

                        } else {

                            $flashMessage =
                                $insert->error;

                            $flashType =
                                'danger';

                            $insert->close();
                        }
                    }
                }
            }
        }
    }
}

/* =========================================================
   رسالة نجاح الإضافة
========================================================= */

if (
    isset($_GET['added']) &&
    $_GET['added'] === '1'
) {

    $flashMessage =
        __('The active section has been added');

    $flashType =
        'success';
}

/* =========================================================
   حذف قسم
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_section'])
) {

    $postedCsrf =
        $_POST['csrf'] ?? '';

    $sectionId =
        (int)(
            $_POST['section_id'] ?? 0
        );

    if (
        !hash_equals(
            $csrf,
            $postedCsrf
        )
    ) {

        $flashMessage =
            $lang === 'ar'
                ? 'طلب غير صالح.'
                : 'Invalid request.';

        $flashType =
            'danger';

    } elseif (
        $sectionId <= 0
    ) {

        $flashMessage =
            $lang === 'ar'
                ? 'رقم القسم غير صحيح.'
                : 'Invalid section ID.';

        $flashType =
            'danger';

    } else {

        /*
         * أولاً نتأكد أن القسم موجود
         */

        $check =
            $con->prepare("
                SELECT id, sectionname
                FROM section
                WHERE id = ?
                LIMIT 1
            ");

        if (!$check) {

            $flashMessage =
                $con->error;

            $flashType =
                'danger';

        } else {

            $check->bind_param(
                'i',
                $sectionId
            );

            $check->execute();

            $section =
                $check
                    ->get_result()
                    ->fetch_assoc();

            $check->close();

            if (!$section) {

                $flashMessage =
                    $lang === 'ar'
                        ? 'القسم غير موجود.'
                        : 'Section not found.';

                $flashType =
                    'warning';

            } else {

                /*
                 * التحقق من وجود منتجات مرتبطة بالقسم
                 */

                $linked =
                    $con->prepare("
                        SELECT COUNT(*) AS total
                        FROM product
                        WHERE prosection = ?
                    ");

                if (!$linked) {

                    $flashMessage =
                        $con->error;

                    $flashType =
                        'danger';

                } else {

                    $linked->bind_param(
                        's',
                        $section['sectionname']
                    );

                    $linked->execute();

                    $linkedCount =
                        (int)(
                            $linked
                                ->get_result()
                                ->fetch_assoc()['total']
                            ?? 0
                        );

                    $linked->close();

                    /*
                     * لا نحذف القسم إذا كان مرتبطًا بمنتجات
                     */

                    if ($linkedCount > 0) {

                        $flashMessage =
                            $lang === 'ar'
                                ? 'لا يمكن حذف هذا القسم لأنه مرتبط بـ ' .
                                  $linkedCount .
                                  ' منتج.'
                                : 'This section cannot be deleted because it is linked to ' .
                                  $linkedCount .
                                  ' product(s).';

                        $flashType =
                            'warning';

                    } else {

                        $delete =
                            $con->prepare("
                                DELETE FROM section
                                WHERE id = ?
                                LIMIT 1
                            ");

                        if (!$delete) {

                            $flashMessage =
                                $con->error;

                            $flashType =
                                'danger';

                        } else {

                            $delete->bind_param(
                                'i',
                                $sectionId
                            );

                            if ($delete->execute()) {

                                $flashMessage =
                                    __('done Deleted successfully');

                                $flashType =
                                    'success';

                            } else {

                                $flashMessage =
                                    __('It was not deleted');

                                $flashType =
                                    'danger';
                            }

                            $delete->close();
                        }
                    }
                }
            }
        }
    }
}

/* =========================================================
   جلب الأقسام
========================================================= */

$sections = [];

$result =
    $con->query("
        SELECT
            id,
            sectionname
        FROM section
        ORDER BY id DESC
    ");

if ($result) {

    while (
        $row =
        $result->fetch_assoc()
    ) {

        $sections[] =
            $row;
    }
}

/* =========================================================
   العدد
========================================================= */

$totalSections =
    count($sections);

/* =========================================================
   الروابط
========================================================= */

$langArUrl =
    '?' .
    http_build_query([
        'lang' =>
            'ar',
        'dark' =>
            $dark
    ]);

$langEnUrl =
    '?' .
    http_build_query([
        'lang' =>
            'en',
        'dark' =>
            $dark
    ]);

$themeUrl =
    '?' .
    http_build_query([
        'lang' =>
            $lang,
        'dark' =>
            $dark === '1'
                ? '0'
                : '1'
    ]);

?>

<!DOCTYPE html>

<html
    lang="<?= htmlspecialchars($lang) ?>"
    dir="<?= $lang === 'ar'
        ? 'rtl'
        : 'ltr'
    ?>"
>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= __('Manage Website Sections') ?>
</title>

<link
    rel="stylesheet"
    href="assets/dark-mode.css"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>

<link
    href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<style>

*{
    box-sizing:border-box;
}

:root{

    --bg:
        <?= $dark === '1'
            ? '#0f172a'
            : '#f4f7fb'
        ?>;

    --card:
        <?= $dark === '1'
            ? '#1e293b'
            : '#ffffff'
        ?>;

    --soft:
        <?= $dark === '1'
            ? '#172033'
            : '#f8fafc'
        ?>;

    --text:
        <?= $dark === '1'
            ? '#f8fafc'
            : '#1f2937'
        ?>;

    --muted:
        <?= $dark === '1'
            ? '#94a3b8'
            : '#6b7280'
        ?>;

    --border:
        <?= $dark === '1'
            ? '#334155'
            : '#e5e7eb'
        ?>;
}

body{

    margin:0;

    font-family:
        'Cairo',
        Tahoma,
        Arial,
        sans-serif;

    background:
        var(--bg);

    color:
        var(--text);
}

.page{

    max-width:
        1100px;

    margin:
        30px auto;

    padding:
        0 15px;
}

/* =========================================================
   HEADER
========================================================= */

.page-header{

    background:
        linear-gradient(
            135deg,
            #0d6efd,
            #084298
        );

    color:#fff;

    border-radius:
        20px;

    padding:
        22px;

    margin-bottom:
        18px;

    box-shadow:
        0 8px 25px
        rgba(0,0,0,.12);
}

.header-top{

    display:flex;

    justify-content:
        space-between;

    align-items:
        center;

    gap:
        15px;

    flex-wrap:
        wrap;
}

.title-area{

    display:flex;

    align-items:
        center;

    gap:
        13px;
}

.title-icon{

    width:
        54px;

    height:
        54px;

    border-radius:
        14px;

    display:flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        rgba(255,255,255,.16);

    font-size:
        24px;
}

.page-header h1{

    margin:0;

    font-size:
        25px;

    font-weight:
        800;
}

.page-header p{

    margin:
        5px 0 0;

    font-size:
        12px;

    opacity:
        .88;
}

.header-actions{

    display:flex;

    gap:
        6px;

    flex-wrap:
        wrap;
}

.header-actions a{

    text-decoration:
        none;
}

/* =========================================================
   STATS
========================================================= */

.stat-card{

    background:
        var(--card);

    border:
        1px solid
        var(--border);

    border-radius:
        16px;

    padding:
        18px;

    margin-bottom:
        18px;

    box-shadow:
        0 5px 18px
        rgba(0,0,0,.05);

    display:flex;

    align-items:
        center;

    justify-content:
        space-between;
}

.stat-content{

    display:flex;

    align-items:
        center;

    gap:
        12px;
}

.stat-icon{

    width:
        46px;

    height:
        46px;

    border-radius:
        12px;

    background:
        #0d6efd;

    color:#fff;

    display:flex;

    align-items:
        center;

    justify-content:
        center;

    font-size:
        20px;
}

.stat-title{

    color:
        var(--muted);

    font-size:
        11px;

    font-weight:
        700;
}

.stat-number{

    font-size:
        25px;

    font-weight:
        800;
}

/* =========================================================
   CARD
========================================================= */

.card-box{

    background:
        var(--card);

    border:
        1px solid
        var(--border);

    border-radius:
        17px;

    padding:
        20px;

    margin-bottom:
        18px;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.05);
}

.section-title{

    font-size:
        17px;

    font-weight:
        800;

    margin-bottom:
        15px;

    display:flex;

    align-items:
        center;

    gap:
        8px;
}

/* =========================================================
   FORM
========================================================= */

.add-form{

    display:flex;

    gap:
        10px;

    align-items:
        flex-end;

    flex-wrap:
        wrap;
}

.form-group{

    flex:
        1;

    min-width:
        250px;
}

.form-label{

    display:block;

    margin-bottom:
        6px;

    font-size:
        12px;

    font-weight:
        700;
}

.form-control{

    min-height:
        44px;

    border-radius:
        10px;

    background:
        var(--soft);

    color:
        var(--text);

    border-color:
        var(--border);
}

.form-control:focus{

    background:
        var(--soft);

    color:
        var(--text);

    border-color:
        #0d6efd;

    box-shadow:
        0 0 0 3px
        rgba(13,110,253,.10);
}

.add-btn{

    min-height:
        44px;

    padding:
        0 20px;

    border:
        0;

    border-radius:
        10px;

    background:
        #198754;

    color:#fff;

    font-weight:
        700;

    cursor:
        pointer;
}

/* =========================================================
   TABLE
========================================================= */

.table-responsive{

    overflow-x:
        auto;
}

.table{

    margin-bottom:
        0;

    color:
        var(--text);
}

.table th{

    background:
        #0d6efd !important;

    color:#fff !important;

    border-color:
        #0d6efd;

    padding:
        12px;

    font-size:
        12px;

    white-space:
        nowrap;
}

.table td{

    padding:
        11px;

    vertical-align:
        middle;

    border-color:
        var(--border);

    font-size:
        12px;
}

.table tbody tr:hover{

    background:
        <?= $dark === '1'
            ? '#263449'
            : '#f8fbff'
        ?>;
}

/* =========================================================
   DELETE
========================================================= */

.delete-btn{

    border:
        0;

    background:
        #dc3545;

    color:#fff;

    padding:
        7px 12px;

    border-radius:
        8px;

    font-size:
        11px;

    font-weight:
        700;

    cursor:
        pointer;
}

.delete-btn:hover{

    background:
        #b02a37;
}

/* =========================================================
   EMPTY
========================================================= */

.empty{

    text-align:
        center;

    color:
        var(--muted);

    padding:
        45px 20px;
}

.empty i{

    display:block;

    font-size:
        40px;

    margin-bottom:
        8px;
}

/* =========================================================
   ALERT
========================================================= */

.alert{

    border-radius:
        11px;

    font-size:
        12px;

    margin-bottom:
        18px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:700px){

    .page{

        margin:
            15px auto;

        padding:
            0 9px;
    }

    .header-top{

        align-items:
            stretch;
    }

    .header-actions{

        width:
            100%;
    }

    .header-actions .btn{

        flex:
            1;
    }

    .add-form{

        flex-direction:
            column;

        align-items:
            stretch;
    }

    .form-group{

        min-width:
            100%;
    }

    .add-btn{

        width:
            100%;
    }

    .stat-card{

        padding:
            14px;
    }
}

</style>

</head>

<body>

<div class="page">

<!-- =====================================================
     HEADER
===================================================== -->

<div class="page-header">

<div class="header-top">

<div class="title-area">

<div class="title-icon">

<i class="bi bi-grid-3x3-gap-fill"></i>

</div>

<div>

<h1>

<?= __('Manage Website Sections') ?>

</h1>

<p>

<?= $lang === 'ar'
    ? 'إدارة أقسام المنتجات والخدمات'
    : 'Manage product and service sections'
?>

</p>

</div>

</div>

<div class="header-actions">

<a
    href="<?= htmlspecialchars(
        $langArUrl
    ) ?>"
    class="btn btn-light btn-sm"
>
🇸🇦 AR
</a>

<a
    href="<?= htmlspecialchars(
        $langEnUrl
    ) ?>"
    class="btn btn-light btn-sm"
>
🇬🇧 EN
</a>

<a
    href="<?= htmlspecialchars(
        $themeUrl
    ) ?>"
    class="btn <?= $dark === '1'
        ? 'btn-light'
        : 'btn-dark'
    ?> btn-sm"
>

<i class="bi <?= $dark === '1'
    ? 'bi-sun'
    : 'bi-moon-stars'
?>"></i>

</a>

</div>

</div>

</div>

<!-- =====================================================
     ALERT
===================================================== -->

<?php if (
    $flashMessage !== ''
): ?>

<div
    class="alert alert-<?= htmlspecialchars(
        $flashType
    ) ?>"
>

<?= htmlspecialchars(
    $flashMessage
) ?>

</div>

<?php endif; ?>

<!-- =====================================================
     STAT
===================================================== -->

<div class="stat-card">

<div class="stat-content">

<div class="stat-icon">

<i class="bi bi-grid"></i>

</div>

<div>

<div class="stat-title">

<?= $lang === 'ar'
    ? 'إجمالي الأقسام'
    : 'Total Sections'
?>

</div>

<div class="stat-number">

<?= number_format(
    $totalSections
) ?>

</div>

</div>

</div>

<span
    class="badge bg-primary"
>
    <?= $lang === 'ar'
        ? 'نشط'
        : 'Active'
    ?>
</span>

</div>

<!-- =====================================================
     ADD SECTION
===================================================== -->

<div class="card-box">

<div class="section-title">

<i class="bi bi-plus-circle text-success"></i>

<?= $lang === 'ar'
    ? 'إضافة قسم جديد'
    : 'Add New Section'
?>

</div>

<form
    method="POST"
    action="sectionadmin.php"
    class="add-form"
>

<input
    type="hidden"
    name="csrf"
    value="<?= htmlspecialchars(
        $csrf
    ) ?>"
>

<div class="form-group">

<label
    for="sectionname"
    class="form-label"
>

<?= __('New section') ?>

</label>

<input
    type="text"
    name="sectionname"
    id="sectionname"
    class="form-control"
    maxlength="50"
    required
    placeholder="<?= $lang === 'ar'
        ? 'اكتب اسم القسم'
        : 'Enter section name'
    ?>"
>

</div>

<button
    type="submit"
    name="add_section"
    class="add-btn"
>

<i class="bi bi-plus-lg"></i>

<?= __('Add a section') ?>

</button>

</form>

</div>

<!-- =====================================================
     SECTIONS TABLE
===================================================== -->

<div class="card-box">

<div class="section-title">

<i class="bi bi-table text-primary"></i>

<?= $lang === 'ar'
    ? 'الأقسام الحالية'
    : 'Current Sections'
?>

</div>

<?php if (
    empty($sections)
): ?>

<div class="empty">

<i class="bi bi-grid-3x3-gap"></i>

<?= $lang === 'ar'
    ? 'لا توجد أقسام حاليًا'
    : 'No sections found'
?>

</div>

<?php else: ?>

<div class="table-responsive">

<table
    class="table table-bordered table-hover text-center align-middle"
>

<thead>

<tr>

<th>
<?= __('Serial Number') ?>
</th>

<th>
<?= __('Section Name') ?>
</th>

<th>
<?= $lang === 'ar'
    ? 'الإجراء'
    : 'Action'
?>
</th>

</tr>

</thead>

<tbody>

<?php foreach (
    $sections
    as $section
): ?>

<tr>

<td>

<span
    class="badge bg-secondary"
>

<?= (int)$section['id'] ?>

</span>

</td>

<td>

<strong>

<?= htmlspecialchars(
    $section['sectionname']
) ?>

</strong>

</td>

<td>

<form
    method="POST"
    action="sectionadmin.php"
    style="display:inline;"
    onsubmit="return confirmDelete(
        '<?= htmlspecialchars(
            $section['sectionname'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>'
    );"
>

<input
    type="hidden"
    name="csrf"
    value="<?= htmlspecialchars(
        $csrf
    ) ?>"
>

<input
    type="hidden"
    name="section_id"
    value="<?= (int)$section['id'] ?>"
>

<button
    type="submit"
    name="delete_section"
    class="delete-btn"
>

<i class="bi bi-trash3"></i>

<?= __('Delete Section') ?>

</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php endif; ?>

</div>

</div>

<script>

/* =========================================================
   Confirm Delete
========================================================= */

function confirmDelete(name){

    return confirm(
        "<?= $lang === 'ar'
            ? 'هل أنت متأكد من حذف القسم: '
            : 'Are you sure you want to delete section: '
        ?>" +
        name +
        "?"
    );
}

</script>

<?php if (
    file_exists(
        __DIR__ .
        '/assets/dark-mode.js'
    )
): ?>

<script
    src="assets/dark-mode.js"
></script>

<?php endif; ?>

</body>

</html>

