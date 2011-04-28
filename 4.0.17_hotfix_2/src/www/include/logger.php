<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

/*
	Determine group
*/

if (isset($group_id) && $group_id) {
	$log_group=$group_id;
} else if (isset($form_grp) && $form_grp) {
	$log_group=$form_grp;
} else {
	$log_group=0;
}

$request =& HTTPRequest::instance();

$log_time=time();

$sql = 'INSERT INTO activity_log'.
'(day,hour,group_id,browser,ver,platform,time,page,type)'.
' VALUES ('.
date('Ymd', $log_time).','.
date('H', $log_time).','.
db_ei($log_group).','.
'"'.db_escape_string(browser_get_agent()).'",'.
floatval(browser_get_version()).','.
'"'.db_escape_string(browser_get_platform()).'",'.
$log_time.','.
'"'.db_escape_string($request->getFromServer('PHP_SELF')).'",'.
'0'.
')';

$res_logger = db_query ( $sql );

if (!$res_logger) {
    echo $GLOBALS['Language']->getText('include_logger','log_err');
	echo db_error();
	exit;
}

$em =& EventManager::instance();
$em->processEvent('logger_after_log_hook', array('isScript' => IS_SCRIPT,
                                                 'groupId'  => $log_group,
                                                 'time'     => $log_time));


unset($log_time);
unset($log_group);

?>
