<?php

require_once('squal_pre.php');

if (!$conn) {
	echo "mysql-bad-conn";
	exit;
}

$query = "SELECT COUNT(*) FROM sourceforge.themes";
$result = @mysql_query($query);
if (!$result || db_numrows($result) < 1) {
	echo 'mysql-bad';
} else {
	echo 'mysql-good';
}
?>
