<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/account.php');  // LJ needed to create unix account
   
$Language->loadLanguageMsg('account/account');

// ###### function login_valid()
// ###### checks for valid login from form post

function verify_login_valid()	{
    global $HTTP_POST_VARS, $Language;

	if (!$GLOBALS['form_loginname']) return 0;

	// first check just confirmation hash
	$res = db_query('SELECT confirm_hash,status FROM user WHERE '
		.'user_name=\''.$GLOBALS['form_loginname'].'\'');

	if (db_numrows($res) < 1) {
		$GLOBALS['error_msg'] = $Language->getText('account_verify', 'err_user');
		return 0;
	}
	$usr = db_fetch_array($res);

	if (strcmp($GLOBALS['confirm_hash'],$usr['confirm_hash'])) {
		$GLOBALS['error_msg'] = $Language->getText('account_verify', 'err_hash');
		return 0;
	}

	// then check valid login	
	return (session_login_valid($GLOBALS['form_loginname'],$GLOBALS['form_pw'],1));
}

// ###### first check for valid login, if so, redirect

if ($Login){
    $success=verify_login_valid();
    if ($success) {
        // Get user status: if already set to 'R' (restricted) don't change it!
        $res_status=db_query("SELECT status FROM user WHERE user_name='$GLOBALS[form_loginname]'");
        if (db_result($res_status,0,'status') == 'R') {
            $newstatus='R';
        } else $newstatus='A';

        // LJ in CodeX we now activate the Unix account upfront to limit
        // LJ source code access control(CVS, File Release) to registered
        // LJ users only
        // LJ	$res = db_query("UPDATE user SET status='A' WHERE user_name='$GLOBALS[form_loginname]'");

	// LJ Since the URL in the e-mail notification can be used
	// LJ several times we must make sure that we do not generate
	// LJ a unix user_id a second time
	  $res_user = db_query("SELECT unix_uid FROM user WHERE user_name='$GLOBALS[form_loginname]'");
	  if (db_result($res_user,0,'unix_uid') == 0) {	
              $shell="";
              if ($newstatus=='R') {
                  // Set restricted shell for restricted users.
                  $shell=",shell='/usr/local/bin/cvssh-restricted'";
              }
	    $res = db_query("UPDATE user SET status='".$newstatus."',unix_status='A',unix_uid=". account_nextuid().$shell."  WHERE user_name='$GLOBALS[form_loginname]'");
	  } else {
	    $res = db_query("UPDATE user SET status='".$newstatus."',unix_status='A'  WHERE user_name='$GLOBALS[form_loginname]'");
	  }
		session_redirect("/account/first.php");
	}
}

$HTML->header(array('title'=>$Language->getText('account_verify', 'title')));

?>
<p><h2><?php echo $Language->getText('account_verify', 'title'); ?></h2>
<P>
<?php
echo '<p>'.$Language->getText('account_verify', 'message');

if ($GLOBALS['error_msg']) {
	print '<P><span class="feedback">'.$GLOBALS['error_msg'].'</span>';
}
if ($Login && !$success) {
	echo '<h2><span class="feedback">'. $feedback .'</span></H2>';
}
?>
<form action="verify.php" method="post">
<p><?php echo $Language->getText('account_login', 'name'); ?>:
<br><input type="text" name="form_loginname">
<p><?php echo $Language->getText('account_login', 'password'); ?>:
<br><input type="password" name="form_pw">
<INPUT type="hidden" name="confirm_hash" value="<?php print $confirm_hash; ?>">
<p><input type="submit" name="Login" value="<?php echo $Language->getText('account_login', 'login_btn'); ?>">
</form>

<?php
$HTML->footer(array());

?>
