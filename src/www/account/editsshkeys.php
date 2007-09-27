<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('account.php');
session_require(array('isloggedin'=>1));

$Language->loadLanguageMsg('account/account');

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{

	if (!isset($GLOBALS["Submit"]) || !$GLOBALS["Submit"]) {
		return 0;
	}

	$GLOBALS['form_authorized_keys'] = trim($GLOBALS['form_authorized_keys']);
	$GLOBALS['form_authorized_keys'] = ereg_replace("(\r\n)|(\n)","###",$GLOBALS['form_authorized_keys']);
	
	// if we got this far, it must be good
	db_query("UPDATE user SET authorized_keys='$GLOBALS[form_authorized_keys]' WHERE user_id=" . user_getid());
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	session_redirect("/account/");
} else { // not valid registration, or first time to page
	$HTML->header(array('title'=>$Language->getText('account_editsshkeys', 'title')));

?>

<h2><?php echo $Language->getText('account_editsshkeys', 'title').' '.help_button('OtherServices.html#ShellAccount'); ?></h2>
<?php
        echo $Language->getText('account_editsshkeys', 'message');
	$date = getdate(time());
	$hoursleft = ($sys_crondelay - 1) - ($date['hours'] % $sys_crondelay);
	$minutesleft = 60 - $date['minutes'];
        echo "\n".$Language->getText('account_editsshkeys', 'important', array($hoursleft, $minutesleft));

?>

<?php if (isset($register_error) && $register_error) print "<p>$register_error"; ?>
<form action="editsshkeys.php" method="post">
<p><?php echo $Language->getText('account_editsshkeys', 'keys'); ?>
<br><TEXTAREA rows=10 cols=60 name="form_authorized_keys">
<?php
	$res_keys = db_query("SELECT authorized_keys FROM user WHERE user_id=".user_getid());
	$row_keys = db_fetch_array($res_keys);
	$authorized_keys = ereg_replace("###","\n",$row_keys['authorized_keys']);
	print $authorized_keys;
?>
</TEXTAREA>
<p><input type="submit" name="Submit" value="<?php echo $Language->getText('global', 'btn_submit'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
