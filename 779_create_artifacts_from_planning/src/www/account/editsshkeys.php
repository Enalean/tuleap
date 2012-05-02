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

$um   = UserManager::instance();
$user = $um->getCurrentUser();

$request = HTTPRequest::instance();

if ($request->isPost()
    && $request->exist('Submit')
    && $request->exist('form_authorized_keys')) {

    $form_authorized_keys = trim($request->get('form_authorized_keys'));
    $form_authorized_keys = ereg_replace("(\r\n)|(\n)","###", $form_authorized_keys);
    $user->setAuthorizedKeys($form_authorized_keys);

    $um->updateDb($user);

    $em = EventManager::instance();
    $em->processEvent(Event::EDIT_SSH_KEYS, array('user_id' => $user->getId()));

    $GLOBALS['Response']->redirect('/account');
}

$HTML->header(array('title'=>$Language->getText('account_editsshkeys', 'title')));

?>

<h2><?php echo $Language->getText('account_editsshkeys', 'title').' '.help_button('OtherServices.html#ShellAccount'); ?></h2>
<?php
        echo $Language->getText('account_editsshkeys', 'message');
?>

<form action="editsshkeys.php" method="post">
<p><?php echo $Language->getText('account_editsshkeys', 'keys'); ?>
<br>
<textarea rows="10" cols="60" name="form_authorized_keys">
<?php
$purifier = Codendi_HTMLPurifier::instance();
foreach ($user->getAuthorizedKeys(true) as $key) {
    echo $purifier->purify($key).PHP_EOL;
}
?>
</textarea>
<p><input type="submit" name="Submit" value="<?php echo $Language->getText('global', 'btn_submit'); ?>">
</form>

<?php

$HTML->footer(array());

?>
