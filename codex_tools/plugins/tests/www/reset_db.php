<?php
require_once('pre.php');
echo '<pre>';
$cmd = 'mysql -h '. $GLOBALS['sys_dbhost'] .' -u '. $GLOBALS['sys_dbuser'] .' -p'. $GLOBALS['sys_dbpasswd'] .' '. $GLOBALS['sys_dbname'] .' < db.sql';
echo $cmd ."\n";

passthru($cmd);
echo '</pre>';
?>
