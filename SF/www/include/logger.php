<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*
	Determine group
*/

if ($group_id) {
	$log_group=$group_id;
} else if ($form_grp) {
	$log_group=$form_grp;
} else {
	$log_group=0;
}

$sql =	"INSERT INTO activity_log "
	. "(day,hour,group_id,browser,ver,platform,time,page,type) "
	. "VALUES (" . date('Ymd', mktime()) . ",'" . date('H', mktime())
	. "','$log_group','" . browser_get_agent() . "','" . browser_get_version() 
	. "','" . browser_get_platform() . "','" . time() . "','$PHP_SELF','0');";

$res_logger = db_query ( $sql );

if (!$res_logger) {
	echo "An error occured in the logger.\n";
	echo db_error();
	exit;
}


?>
