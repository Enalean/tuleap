<?php


function site_admin_header($params, $breadcrumbs = array()) {
    GLOBAL $Language;
    global $feedback;
    site_admin_header_without_default_title($params, $breadcrumbs);
	echo html_feedback_top($feedback);
	$version = trim(file_get_contents($GLOBALS['codendi_dir'].'/VERSION'));
	echo '<H2>'.$Language->getText('admin_utils', 'title', array($GLOBALS['sys_name'])).' ('.$version.')'.'</H2>';
}

function site_admin_header_without_default_title($params, $breadcrumbs) {
    GLOBAL $HTML, $Language;
    $breadcrumbs = array_merge(
        array(
          array(
            'url'   => '/admin/',
            'title' => $Language->getText('menu', 'admin'),
          ),
        ),
        $breadcrumbs
    );
    $HTML->addBreadcrumbs($breadcrumbs);
	$HTML->header($params);
}

function site_admin_footer($vals=0) {
	GLOBAL $HTML;
	echo html_feedback_bottom($GLOBALS['feedback']);
	$HTML->footer(array());
}


?>
