<?php

/* =========================
   رأس التقارير الموحد
========================= */


$reportDate = date('Y-m-d');
$reportTime = date('H:i');

$reportUser = $_SESSION['username'] ?? 'Administrator';


$logo = "../uploads/logo/" . setting('company_logo');


$html .= '

<table width="100%" style="border-bottom:2px solid #0d6efd;">

<tr>

<td width="25%" align="center">
';

if(file_exists($logo)){

$html .= '

<img src="'.$logo.'" 
style="width:90px;height:auto;">

';

}

$html .= '

</td>


<td width="75%" align="center">


<h2 style="color:#0d6efd;">
'.setting('company_name').'
</h2>


<h4>
'.setting('system_name').'
</h4>


<p>

'.setting('company_address').'

<br>

هاتف:
'.setting('company_phone').'

<br>

'.setting('company_email').'

</p>


</td>


</tr>

</table>


<br>


<h2 style="
background:#0d6efd;
color:white;
padding:10px;
text-align:center;
">

'.$report_title.'

</h2>


<table width="100%" 
style="border-collapse:collapse;">


<tr>

<td>
<b>رقم التقرير:</b>
'.$report_number.'
</td>


<td>

<b>التاريخ:</b>
'.$reportDate.'

</td>


</tr>


<tr>

<td>

<b>أعد التقرير:</b>
'.$reportUser.'

</td>


<td>

<b>الوقت:</b>
'.$reportTime.'

</td>

</tr>


<tr>

<td>

<b>رقم اللوحة:</b>
'.$plate.'

</td>


<td>

<b>السائق:</b>
'.$driver.'

</td>


</tr>


</table>


<br>

<hr>

';

?>