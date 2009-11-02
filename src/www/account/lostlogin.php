<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');

$request = HTTPRequest::instance();

$confirm_hash = $request->get('confirm_hash');

$um   = UserManager::instance();
$user = $um->getUserByConfirmHash($confirm_hash);

if ($user == null) {
    exit_error($Language->getText('include_exit', 'error'),
               $Language->getText('account_lostlogin', 'invalid_hash'));
}

if ($request->isPost()
    && $request->exist('Update')
    && $request->existAndNonEmpty('form_pw')
    && !strcmp($request->get('form_pw'), $request->get('form_pw2'))) {

    $user->setPassword($request->get('form_pw'));
    $um->updateDb($user);

    session_redirect("/");
}

$purifier =& Codendi_HTMLPurifier::instance();

$HTML->header(array('title'=>$Language->getText('account_lostlogin', 'title')));
?>
<p><b><?php echo $Language->getText('account_lostlogin', 'title'); ?></b>
<P><?php echo $Language->getText('account_lostlogin', 'message', array($purifier->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML))); ?>.

<form action="lostlogin.php" method="post">
<p><?php echo $Language->getText('account_lostlogin', 'newpasswd'); ?>:
<br><input type="password" name="form_pw">
<p><?php echo $Language->getText('account_lostlogin', 'newpasswd2'); ?>:
<br><input type="password" name="form_pw2">
<input type="hidden" name="confirm_hash" value="<?php echo $purifier->purify($confirm_hash); ?>">
<p><input type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
$HTML->footer(array());

?>
