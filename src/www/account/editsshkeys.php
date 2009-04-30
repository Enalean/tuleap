<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('account.php');
session_require(array('isloggedin'=>1));


// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{
    $request =& HTTPRequest::instance();

	if (!$request->isPost()
        || !$request->exist('Submit')
        || !$request->existAndNonEmpty('form_authorized_keys')) {
		return 0;
	}
    $user = UserManager::instance()->getCurrentUser();

	$form_authorized_keys = trim($request->get('form_authorized_keys'));
	$form_authorized_keys = ereg_replace("(\r\n)|(\n)","###", $form_authorized_keys);

	// if we got this far, it must be good
	db_query("UPDATE user SET authorized_keys='".db_es($form_authorized_keys)."' WHERE user_id=" . $user->getId());
    
    $em = EventManager::instance();
    $em->processEvent(Event::EDIT_SSH_KEYS, array('user_id' => $user->getId()));
    
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
?>

<form action="editsshkeys.php" method="post">
<p><?php echo $Language->getText('account_editsshkeys', 'keys'); ?>
<br><textarea rows="10" cols="60" name="form_authorized_keys">
<?php
    $purifier =& Codendi_HTMLPurifier::instance();
	$res_keys = db_query("SELECT authorized_keys FROM user WHERE user_id=".user_getid());
	$row_keys = db_fetch_array($res_keys);
	$authorized_keys = ereg_replace("###","\n",$row_keys['authorized_keys']);
	echo $purifier->purify($authorized_keys);
?>
</textarea>
<p><input type="submit" name="Submit" value="<?php echo $Language->getText('global', 'btn_submit'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
