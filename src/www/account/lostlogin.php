<?php
/**
 * Copyright (c) Enalean, 2017-2019. All Rights Reserved.
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

use Tuleap\User\Password\Reset\ExpiredTokenException;

require_once __DIR__ . '/../include/pre.php';

$request = HTTPRequest::instance();

$confirm_hash = new \Tuleap\Cryptography\ConcealedString(
    $request->get('confirm_hash') === false ? '' : $request->get('confirm_hash')
);

$reset_token_dao          = new Tuleap\User\Password\Reset\DataAccessObject();
$hasher                   = new \Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher();
$user_manager             = UserManager::instance();
$reset_token_verifier     = new \Tuleap\User\Password\Reset\Verifier($reset_token_dao, $hasher, $user_manager);
$reset_token_unserializer = new \Tuleap\User\Password\Reset\ResetTokenSerializer();

try {
    $token = $reset_token_unserializer->getSplitToken($confirm_hash);
    $user  = $reset_token_verifier->getUser($token);
} catch (ExpiredTokenException $ex) {
    $GLOBALS['Response']->addFeedback(
        Feedback::ERROR,
        $GLOBALS['Language']->getText('account_lostlogin', 'expired_token')
    );
    $GLOBALS['Response']->redirect('/account/lostpw.php');
} catch (Exception $ex) {
    exit_error(
        $GLOBALS['Language']->getText('include_exit', 'error'),
        $GLOBALS['Language']->getText('account_lostlogin', 'invalid_hash')
    );
}

if (
    $request->isPost()
    && $request->exist('Update')
    && $request->existAndNonEmpty('form_pw')
    && !strcmp($request->get('form_pw'), $request->get('form_pw2'))
) {
    $user->setPassword($request->get('form_pw'));

    $reset_token_revoker = new \Tuleap\User\Password\Reset\Revoker($reset_token_dao);
    $reset_token_revoker->revokeTokens($user);

    $user_manager->updateDb($user);

    session_redirect("/");
}

$purifier = Codendi_HTMLPurifier::instance();

$HTML->header(array('title' => $Language->getText('account_lostlogin', 'title')));
?>
<p><b><?php echo $Language->getText('account_lostlogin', 'title'); ?></b>
<P><?php echo $Language->getText('account_lostlogin', 'message', array($purifier->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML))); ?>.

<form action="lostlogin.php" method="post">
<input type="hidden" value="<?php echo $purifier->purify($user->getUserName()) ?>" autocomplete="username">
<p><?php echo $Language->getText('account_lostlogin', 'newpasswd'); ?>:
<br><input type="password" name="form_pw" autocomplete="new-password">
<p><?php echo $Language->getText('account_lostlogin', 'newpasswd2'); ?>:
<br><input type="password" name="form_pw2" autocomplete="new-password">
<input type="hidden" name="confirm_hash" value="<?php echo $purifier->purify($confirm_hash); ?>">
<p><input type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
$HTML->footer(array());

?>
