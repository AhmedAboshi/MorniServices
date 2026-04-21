<?php
//بدء الجلسه
session_start();
//حزف لكل السكشن تم حفظها بالمتصفح
session_unset();
//حزف وتدمير الجلسة
session_destroy();
//اعادة توجية المستخدم
header('location:admin.php');
?>