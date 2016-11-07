<?php


function site_admin_header($params) {
    GLOBAL $HTML, $Language;
	global $feedback;
	$HTML->header($params);
	echo html_feedback_top($feedback);
}

function site_admin_footer($vals=0) {
	GLOBAL $HTML;
	echo html_feedback_bottom($GLOBALS['feedback']);
	$HTML->footer(array());
}

function site_admin_warnings() {
    if (ForgeConfig::get('disable_forge_upgrade_warnings')) {
        return;
    }

    $forgeupgrade_config = new ForgeUpgradeConfig(new System_Command());
    $forgeupgrade_config->loadDefaults();
    if (! $forgeupgrade_config->isSystemUpToDate()) {
        return '<div class="tlp-alert-warning">'.$GLOBALS['Language']->getText('admin_main', 'forgeupgrade').'</div>';
    }
}
