<?php
require(getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc');
require($GLOBALS['db_config_file']);
echo '<pre>';
$cmd = 'mysql -h '. $GLOBALS['sys_dbhost'] .' -u '. $GLOBALS['sys_dbuser'] .' -p'. $GLOBALS['sys_dbpasswd'] .' '. $GLOBALS['sys_dbname'] .' < db.sql';
echo $cmd ."\n";

passthru($cmd);
echo '</pre>';
?>
