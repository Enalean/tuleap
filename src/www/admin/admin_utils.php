<?php


function site_admin_header($params) {
    GLOBAL $HTML, $Language;
	global $feedback;
	$HTML->header($params);
	echo html_feedback_top($feedback);
	$version = trim(file_get_contents($GLOBALS['codendi_dir'].'/VERSION'));
	echo '<H2>'.$Language->getText('admin_utils', 'title', array($GLOBALS['sys_name'])).' ('.$version.')'.'</H2>';
}

function site_admin_footer($vals=0) {
	GLOBAL $HTML;
	echo html_feedback_bottom($GLOBALS['feedback']);
	$HTML->footer(array());
}


?>
