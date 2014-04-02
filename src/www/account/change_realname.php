<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    

$em      = EventManager::instance();
$um      = UserManager::instance();
$request = HTTPRequest::instance();

$em->processEvent('before_change_realname', array());

$csrf = new CSRFSynchronizerToken('/account/change_realname.php');

$user = $um->getCurrentUser();

if ($request->isPost() && $request->existAndNonEmpty('form_realname')) {
    $csrf->check();

    $user->setRealName($request->get('form_realname'));
    $um->updateDb($user);

    $GLOBALS['Response']->redirect("/account/");
    exit;
}

$HTML->header(array('title'=>$Language->getText('account_change_realname', 'title')));

$hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo $Language->getText('account_change_realname', 'title'); ?></h2>
<form action="change_realname.php" method="post">
<?php
echo $csrf->fetchHTMLInput();
echo $Language->getText('account_change_realname', 'new_name'); ?>:
<br><input type="text" name="form_realname" class="textfield_medium" value="<?php echo $hp->purify($user->getRealname(), CODENDI_PURIFIER_CONVERT_HTML) ?>" />
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php

$HTML->footer(array());
