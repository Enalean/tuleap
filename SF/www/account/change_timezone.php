<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/account.php');
require($DOCUMENT_ROOT.'/include/timezones.php');

$LANG->loadLanguageMsg('account/account');

if (!user_isloggedin()) {
	exit_not_logged_in();
}

if ($submit) {	
	if (!$timezone) {
		$feedback .= ' '.$LANG->getText('account_change_timezone', 'no_update').' ';
	} else if ($timezone == 'None') {
		$feedback .= ' '.$LANG->getText('account_change_timezone', 'choose_tz').' ';
	  
	} else {
		// if we got this far, it must be good
		db_query("UPDATE user SET timezone='$timezone' WHERE user_id=" . user_getid());
		session_redirect("/account/");
	}
}

$HTML->header(array('title'=>$LANG->getText('account_change_timezone', 'title')));

?>
<H3><?php echo $LANG->getText('account_change_timezone', 'title'); ?></h3>
<P>
<?php echo $LANG->getText('account_change_timezone', 'title', array($GLOBALS['sys_name']); ?>
<P>
<FORM ACTION="<?php echo $PHP_SELF; ?>" METHOD="POST">
<?php

echo '<H4><span class="feedback">'.$feedback.'</span></H4>';

echo html_get_timezone_popup ('timezone',user_get_timezone());

?>
<input type="submit" name="submit" value="<?php echo $LANG->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());

?>
