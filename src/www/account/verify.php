<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('account.php');
require_once('common/event/EventManager.class.php');


// ###### function login_valid()
// ###### checks for valid login from form post

function verify_login_valid()	{
    global $Language;

    $request =& HTTPRequest::instance();

	if (!$request->existAndNonEmpty('form_loginname')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('include_session','missing_pwd'));
        return 0;
    }

	// first check just confirmation hash
	$res = db_query('SELECT confirm_hash,status FROM user WHERE '
		.'user_name=\''.db_es($request->get('form_loginname')).'\'');

	if (db_numrows($res) < 1) {
		$GLOBALS['Response']->addFeedback('error', $Language->getText('account_verify', 'err_user'));
		return 0;
	}

	$usr = db_fetch_array($res);
    //if sys_user_approval=1 then check if the admin aldready validates the account

    if($GLOBALS['sys_user_approval'] == 0 || $usr['status'] == 'V' || $usr['status'] == 'W'){
    	if (strcmp($request->get('confirm_hash'),$usr['confirm_hash'])) {
    		$GLOBALS['Response']->addFeedback('error', $Language->getText('account_verify', 'err_hash'));
    		return 0;
    	}
    }else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_verify', 'err_status'));
        return 0;
    }

	// then check valid login
    return UserManager::instance()->login($request->get('form_loginname'), $request->get('form_pw'), true);
}

$request =& HTTPRequest::instance();

// ###### first check for valid login, if so, redirect

if ($request->isPost() && $request->exist('Login')){
    $success=verify_login_valid();
    if ($success) {
        // Get user status: if already set to 'R' (restricted) don't change it!
        $um = UserManager::instance();
        $user = $um->getUserByUserName($request->get('form_loginname'));
        if ($user->getStatus() == 'R' || $user->getStatus() == 'W') {
            $user->setStatus('R');
        } else {
            $user->setStatus('A');
        }

        // LJ in Codendi we now activate the Unix account upfront to limit
        // LJ source code access control(CVS, File Release) to registered
        // LJ users only
        // LJ	$res = db_query("UPDATE user SET status='A' WHERE user_name='$GLOBALS[form_loginname]'");

        // LJ Since the URL in the e-mail notification can be used
        // LJ several times we must make sure that we do not generate
        // LJ a unix user_id a second time
        if ($user->getUnixUid() == 0) {
            $um->assignNextUnixUid($user);
            if ($user->getStatus() == 'R') {
                // Set restricted shell for restricted users.
                $user->setShell($GLOBALS['codendi_bin_prefix'] .'/cvssh-restricted');
            }

        }
        $user->setUnixStatus('A');
        $um->updateDb($user);

        session_redirect("/account/first.php");
	}
}

$HTML->header(array('title'=>$Language->getText('account_verify', 'title')));

$purifier =& Codendi_HTMLPurifier::instance();
$confirm_hash = $purifier->purify($request->get('confirm_hash'));

?>
<p><h2><?php echo $Language->getText('account_verify', 'title'); ?></h2>
<P>
<?php
echo '<p>'.$Language->getText('account_verify', 'message');
?>
<form action="verify.php" method="post" autocomplete="off">
<p><?php echo $Language->getText('account_login', 'name'); ?>:
<br><input type="text" name="form_loginname">
<p><?php echo $Language->getText('account_login', 'password'); ?>:
<br><input type="password" name="form_pw">
<INPUT type="hidden" name="confirm_hash" value="<?php print $confirm_hash; ?>">
<p><input type="submit" class="btn primary" name="Login" value="<?php echo $Language->getText('account_login', 'login_btn'); ?>">
</form>

<?php
$HTML->footer(array());

?>
