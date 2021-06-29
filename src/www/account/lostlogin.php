<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\User\Password\Change\PasswordChanger;
use Tuleap\User\Password\Reset\ExpiredTokenException;
use Tuleap\User\SessionManager;

require_once __DIR__ . '/../include/pre.php';

$request = HTTPRequest::instance();

$confirm_hash = new \Tuleap\Cryptography\ConcealedString(
    $request->get('confirm_hash') === false ? '' : $request->get('confirm_hash')
);

$reset_token_dao          = new Tuleap\User\Password\Reset\LostPasswordDAO();
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
        _('The confirmation key is expired, please renew if needed your request for the retrieval of your lost password')
    );
    $GLOBALS['Response']->redirect('/account/lostpw.php');
} catch (Exception $ex) {
    exit_error(
        $GLOBALS['Language']->getText('include_exit', 'error'),
        _('Invalid confirmation hash.')
    );
}

if ($user->getUserPw() === null) {
    exit_error(
        $GLOBALS['Language']->getText('include_exit', 'error'),
        _('Invalid confirmation hash.')
    );
}

if (
    $request->isPost()
    && $request->exist('Update')
    && $request->existAndNonEmpty('form_pw')
    && ! strcmp($request->get('form_pw'), $request->get('form_pw2'))
) {
    $password_changer = new PasswordChanger(
        $user_manager,
        new SessionManager($user_manager, new SessionDao(), new RandomNumberGenerator()),
        new \Tuleap\User\Password\Reset\Revoker(new \Tuleap\User\Password\Reset\LostPasswordDAO()),
        EventManager::instance(),
        new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
    );
    $password_changer->changePassword($user, new ConcealedString((string) $request->get('form_pw')));

    session_redirect("/");
}

$purifier = Codendi_HTMLPurifier::instance();

$HTML->header(['title' => _('Lost Password')]);
?>
<p><b><?php echo _('Lost Password'); ?></b>
<P><?php echo sprintf(_('Welcome, %1$s. You may now change your lost password.'), $purifier->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML)); ?>.

<form action="lostlogin.php" method="post">
<input type="hidden" value="<?php echo $purifier->purify($user->getUserName()) ?>" autocomplete="username">
<p><?php echo _('New Password'); ?>:
<br><input type="password" name="form_pw" autocomplete="new-password">
<p><?php echo _('New Password (repeat)'); ?>:
<br><input type="password" name="form_pw2" autocomplete="new-password">
<input type="hidden" name="confirm_hash" value="<?php echo $purifier->purify($confirm_hash); ?>">
<p><input type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
$HTML->footer([]);

?>
