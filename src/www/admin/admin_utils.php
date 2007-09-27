<?php

$Language->loadLanguageMsg('admin/admin');

function site_admin_header($params) {
    GLOBAL $HTML, $Language;
	global $feedback;
	$HTML->header($params);
	echo html_feedback_top($feedback);
	echo '<H2>'.$Language->getText('admin_utils', 'title', array($GLOBALS['sys_name'])).'</H2>';
}

function site_admin_footer($vals=0) {
	GLOBAL $HTML;
	echo html_feedback_bottom($GLOBALS['feedback']);
	$HTML->footer(array());
}


?>
