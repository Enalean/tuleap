<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('squal_pre.php');

// This script is not localizable because it includes squal_pre.php
// and it doesn't need to be anyway

/*
	Determine group
*/

if ($group_id) {
	$log_group=$group_id;
} else {
	$log_group=0;
}

$res_logger = db_query ("INSERT INTO activity_log (day,hour,group_id,browser,ver,platform,time,page,type) ".
	"VALUES (".date('Ymd', mktime()).",'".date('H', mktime())."','$log_group','". browser_get_agent() ."','". browser_get_version() ."','". browser_get_platform() ."','". time() ."','$PHP_SELF','1');");
if (!$res_logger) {
	echo $Language->getText('sflogo','err_logger')."\n";
	echo db_error();
	exit;
}

// output image
header("Content-Type: image/png");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

if (!$group_id) {
	echo $Language->getText('sflogo','err_nogroup');
	exit;
}

// LJ updated with the CodeX logo image
if ($type == 1) {
  readfile (util_get_image_theme("codex_logo.png", $sys_themedefault, true));
}  else { // default
  readfile (util_get_image_theme("codex_logo.png", $sys_themedefault, true));
} 

?>
