<?php

require('squal_pre.php');

if (!$conn) {
	echo "mysql-bad-conn";
	exit;
}

$query = "SELECT COUNT(*) FROM alexandria.user";
$result = @mysql_query($query);
if (!$result || db_numrows($result) < 1) {
	echo 'mysql-bad';
} else {
	echo 'mysql-good';
}
?>
