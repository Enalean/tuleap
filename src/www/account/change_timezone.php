<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: change_timezone.php 4978 2007-02-21 13:22:16 +0000 (Wed, 21 Feb 2007) nterray $

require_once('pre.php');
require_once('account.php');
require_once('timezones.php');

require_once('common/event/EventManager.class.php');
$em =& EventManager::instance();
$em->processEvent('before_change_timezone', array());

$Language->loadLanguageMsg('account/account');

if (!user_isloggedin()) {
	exit_not_logged_in();
}

if (isset($submit) && $submit) {	
	if (!$timezone) {
		$GLOBALS['Response']->addFeedback('error', $Language->getText('account_change_timezone', 'no_update'));
	} else if ($timezone == 'None') {
		$GLOBALS['Response']->addFeedback('error', $Language->getText('account_change_timezone', 'choose_tz'));
	  
	} else {
		// if we got this far, it must be good
		db_query("UPDATE user SET timezone='$timezone' WHERE user_id=" . user_getid());
		session_redirect("/account/");
	}
}

$HTML->header(array('title'=>$Language->getText('account_change_timezone', 'title')));

?>
<H3><?php echo $Language->getText('account_change_timezone', 'title2'); ?></h3>
<P>
<?php echo $Language->getText('account_change_timezone', 'message', array($GLOBALS['sys_name'])); ?>
<P>
<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<?php

echo html_get_timezone_popup ('timezone',user_get_timezone());

?>
<input type="submit" name="submit" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());

?>
