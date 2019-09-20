<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\User\Password\Change\PasswordChanger;
use Tuleap\User\SessionManager;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/account.php';
$request = HTTPRequest::instance();
$csrf    = new CSRFSynchronizerToken('/account/change_pw.php');

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid($user_id, CSRFSynchronizerToken $csrf, $old_password_required)
{
    $request = HTTPRequest::instance();

    if (!$request->isPost() || !$request->exist('Update')) {
        return 0;
    }
    $csrf->check();

    // check against old pw
    $user_manager = UserManager::instance();
    $user         = $user_manager->getUserById($user_id);
    if ($user === null) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'user_not_found'));
        return 0;
    }

    $password_handler  = PasswordHandlerFactory::getPasswordHandler();
    $password_verifier = new \Tuleap\User\PasswordVerifier($password_handler);
    if ($old_password_required && ! $password_verifier->verifyPassword($user, $request->get('form_oldpw'))) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'incorrect_old_password'));
        return 0;
    }
    if (! $old_password_required && ! $user->isLoggedIn()) {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('account_change_pw', 'error_no_rights_to_change_password')
        );
           return 0;
    }

    try {
        $status_manager = new User_UserStatusManager();
        $status_manager->checkStatus($user);
    } catch (User_StatusInvalidException $exception) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'account_inactive'));
        return 0;
    }

    if (!$request->exist('form_pw')) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'password_needed'));
        return 0;
    }
    if ($request->get('form_pw') !== $request->get('form_pw2')) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'password_not_match'));
        return 0;
    }
    if ($password_verifier->verifyPassword($user, $request->get('form_pw'))) {
        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('account_change_pw', 'identical_password'));
        return 0;
    }

    $password_sanity_checker = \Tuleap\Password\PasswordSanityChecker::build();
    if (! $password_sanity_checker->check($request->get('form_pw'))) {
        foreach ($password_sanity_checker->getErrors() as $error) {
            $GLOBALS['Response']->addFeedback('error', $error);
        }
        return 0;
    }

    // if we got this far, it must be good
    $password_changer = new PasswordChanger(
        $user_manager,
        new SessionManager($user_manager, new SessionDao(), new RandomNumberGenerator()),
        new \Tuleap\User\Password\Reset\Revoker(new \Tuleap\User\Password\Reset\DataAccessObject())
    );
    try {
        $password_changer->changePassword($user, $request->get('form_pw'));
    } catch (Exception $ex) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_change_pw', 'internal_error_update'));
        return 0;
    }
    return 1;
}

$event_manager = EventManager::instance();
$event_manager->processEvent('before_change_pw', array());
$old_password_required = true;
$event_manager->processEvent(
    Event::IS_OLD_PASSWORD_REQUIRED_FOR_PASSWORD_CHANGE,
    array('old_password_required' => &$old_password_required)
);

// ###### first check for valid login, if so, congratulate
$user_id = is_numeric($request->get('user_id')) ? (int)$request->get('user_id') : UserManager::instance()->getCurrentUser()->getId();
if (register_valid($user_id, $csrf, $old_password_required)) {
    $HTML->header(array('title'=>$Language->getText('account_change_pw', 'title_success')));
    ?>
<p><b><?php echo $Language->getText('account_change_pw', 'title_success'); ?></b>
<p><?php echo $Language->getText('account_change_pw', 'message', array($GLOBALS['sys_name'])); ?>

<p><a href="/">[ <?php echo $Language->getText('global', 'back_home');?> ]</a>
    <?php
} else { // not valid registration, or first time to page
    $HTML->includeJavascriptFile('/scripts/check_pw.js');
    $HTML->header(array('title'=>$Language->getText('account_options', 'change_password')));
    $user     = UserManager::instance()->getUserById($user_id);
    $username = $user !== null ? $user->getUserName() : '';
    $purifier = Codendi_HTMLPurifier::instance();
    ?>
<h2><?php echo $Language->getText('account_change_pw', 'title'); ?></h2>
<form action="change_pw.php" method="post">
<input type="hidden" value="<?php echo $purifier->purify($username) ?>" autocomplete="username">
<p><?php
echo $csrf->fetchHTMLInput();
if ($old_password_required) {
    echo $Language->getText('account_change_pw', 'old_password'); ?>:
    <br>
    <input type="password" value="" name="form_oldpw" autocomplete="current-password">
    <?php
}
user_display_choose_password('', is_numeric($request->get('user_id')) ? $request->get('user_id') : 0); ?>
<p><input type="submit" class="btn btn-primary" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

    <?php
}
$HTML->footer(array());

?>
