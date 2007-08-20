<?php
require(getenv('CODEX_LOCAL_INC')?getenv('CODEX_LOCAL_INC'):'/etc/codex/conf/local.inc');
require($GLOBALS['db_config_file']);
echo '<pre>';
$cmd = 'mysql -h '. $GLOBALS['sys_dbhost'] .' -u '. $GLOBALS['sys_dbuser'] .' -p'. $GLOBALS['sys_dbpasswd'] .' '. $GLOBALS['sys_dbname'] .' < db.sql';
echo $cmd ."\n";

passthru($cmd);
echo '</pre>';
?>
