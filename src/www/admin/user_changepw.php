<?php
// Copyright (c) Enalean, 2015-2016. All Rights Reserved.
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/account.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid(Codendi_Request $request, CSRFSynchronizerToken $csrf_token)
{
    global $Language;

    if (! $request->existAndNonEmpty('Update')) {
        return false;
    }
    if (! $request->existAndNonEmpty('user_id')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_user_changepw', 'error_userid'));
        return false;
    }
    if (! $request->existAndNonEmpty('form_pw')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_user_changepw', 'error_nopasswd'));
        return false;
    }
    if ($request->get('form_pw') != $request->get('form_pw2')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_user_changepw', 'error_passwd'));
        return false;
    }

    $password_sanity_checker = \Tuleap\Password\PasswordSanityChecker::build();
    if (! $password_sanity_checker->check($request->get('form_pw'))) {
        foreach ($password_sanity_checker->getErrors() as $error) {
            $GLOBALS['Response']->addFeedback('error', $error);
        }
        return false;
    }

    // if we got this far, it must be good
    $csrf_token->check();
    $user_manager = UserManager::instance();
    $user         = $user_manager->getUserById($request->get('user_id'));
    if ($user === null) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_user_changepw', 'error_userid'));
        return false;
    }
    $user->setPassword($request->get('form_pw'));
    if (!$user_manager->updateDb($user)) {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_user_changepw', 'error_update'));
        return false;
    }
    return true;
}

// ###### first check for valid login, if so, congratulate
$HTML->includeJavascriptFile('/scripts/check_pw.js');
$purifier   = Codendi_HTMLPurifier::instance();
$user_id    = $request->get('user_id');
$csrf_token = new CSRFSynchronizerToken('/admin/user_changepw.php?user_id=' . urlencode($user_id));
if (register_valid($request, $csrf_token)) {
    $HTML->header(array('title'=>$Language->getText('admin_user_changepw', 'title_changed'), 'main_classes' => array('tlp-framed')));
    ?>
<h3><?php echo $purifier->purify($Language->getText('admin_user_changepw', 'header_changed')); ?></h3>
<p><?php echo $purifier->purify($Language->getText('admin_user_changepw', 'msg_changed')); ?></p>

<p><a href="/admin"><?php echo $Language->getText('global', 'back'); ?></a>.
    <?php
} else { // not valid registration, or first time to page
    $HTML->header(array('title'=>$Language->getText('admin_user_changepw', 'title'), 'main_classes' => array('tlp-framed')));

    $em =& EventManager::instance();
    $em->processEvent('before_admin_change_pw', array());

    ?>
<h3><?php echo $purifier->purify($Language->getText('admin_user_changepw', 'header')); ?></h3>
<form action="user_changepw.php" method="post">
    <?php user_display_choose_password('', $user_id); ?>
<p><input type="submit" class="tlp-button-primary" name="Update" value="<?php echo $purifier->purify($Language->getText('global', 'btn_update')); ?>">
    <?php
    echo $csrf_token->fetchHTMLInput();
    ?>
</form>

    <?php
}
$HTML->footer(array());

?>
