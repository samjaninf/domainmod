<?php
// ssl-types.php
// 
// Domain Manager - A web-based application written in PHP & MySQL used to manage a collection of domain names.
// Copyright (C) 2010 Greg Chetcuti
// 
// Domain Manager is free software; you can redistribute it and/or modify it under the terms of the GNU General
// Public License as published by the Free Software Foundation; either version 2 of the License, or (at your
// option) any later version.
// 
// Domain Manager is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
// implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
// for more details.
// 
// You should have received a copy of the GNU General Public License along with Domain Manager. If not, please 
// see http://www.gnu.org/licenses/
?>
<?php
session_start();

include("_includes/config.inc.php");
include("_includes/database.inc.php");
include("_includes/software.inc.php");
include("_includes/auth/auth-check.inc.php");

$page_title = "SSL Certificate Types";
$software_section = "ssl-types";
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?=$software_title?> :: <?=$page_title?></title>
<?php include("_includes/head-tags.inc.php"); ?>
</head>
<body>
<?php include("_includes/header.inc.php"); ?>
Below is a list of all the types of SSL certificates that are stored in the <?=$software_title?>.<BR><BR>

<?php
$sql = "SELECT id, type, default_type
		FROM ssl_cert_types
		WHERE id IN (SELECT type_id FROM ssl_certs WHERE type_id != '0' AND active NOT IN ('0') GROUP BY type_id)
		ORDER BY default_type desc, type asc";
$result = mysql_query($sql,$connection) or die(mysql_error());
$number_of_types = mysql_num_rows($result);
?>
<strong>Number of Active Types:</strong> <?=$number_of_types?>
<?php if (mysql_num_rows($result) > 0) { ?>
<BR><BR>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr height="30">
	<td width="325">
   	<font class="subheadline">Type</font></td>
	<td>
    	<font class="subheadline"># of SSL Certs</font>
    </td>
</tr>
<?php while ($row = mysql_fetch_object($result)) { ?>
<tr height="20">
    <td>
		<a class="subtlelink" href="edit/ssl-type.php?ssltid=<?=$row->id?>"><?=$row->type?></a><?php if ($row->default_type == "1") echo "<a title=\"Default SSL Type\"><font color=\"#DD0000\"><strong>*</strong></font></a>"; ?>
	</td>
	<td>
    <?php
	$sql2 = "SELECT count(*) AS total_count
			 FROM ssl_certs
			 WHERE type_id = '$row->id'
			   AND active NOT IN ('0')";
	$result2 = mysql_query($sql2,$connection);
	while ($row2 = mysql_fetch_object($result2)) { $active_certs = $row2->total_count; }
	?>
    	<?php if ($active_certs == "0") { ?>
	        <?=number_format($active_certs)?>
        <?php } else { ?>
	        <a class="nobold" href="ssl-certs.php?ssltid=<?=$row->id?>"><?=number_format($active_certs)?></a>
        <?php } ?>
    </td>
</tr>
<?php } ?>
</table>
<?php } ?>
<BR><BR>
<?php
$sql = "SELECT id, type, default_type
		FROM ssl_cert_types
		WHERE id NOT IN (SELECT type_id FROM ssl_certs WHERE type_id != '0' AND active NOT IN ('0') GROUP BY type_id)
		ORDER BY default_type desc, type asc";
$result = mysql_query($sql,$connection) or die(mysql_error());
$number_of_types = mysql_num_rows($result);
?>
<strong>Number of Inactive Types:</strong> <?=$number_of_types?>
<?php if (mysql_num_rows($result) > 0) { ?>
<BR><BR>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr height="30">
	<td width="325">
   	<font class="subheadline">Type</font></td>
</tr>
<?php while ($row = mysql_fetch_object($result)) { ?>
<tr height="20">
    <td>
		<a class="subtlelink" href="edit/ssl-type.php?ssltid=<?=$row->id?>"><?=$row->type?></a><?php if ($row->default_type == "1") echo "<a title=\"Default SSL Type\"><font color=\"#DD0000\"><strong>*</strong></font></a>"; ?>
	</td>
</tr>
<?php } ?>
</table>
<?php } ?>
<BR><font color="#DD0000"><strong>*</strong></font> = Default SSL Type
<?php include("_includes/footer.inc.php"); ?>
</body>
</html>