<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * SourceForge: Breaking Down the Barriers to Open Source Development
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

use Tuleap\User\Password\Reset\TokenNotCreatedException;

require_once __DIR__ . '/../include/pre.php';

$event_manager = EventManager::instance();
$event_manager->processEvent('before_lostpw-confirm', array());

$number_generator = new RandomNumberGenerator();
$confirm_hash     = $number_generator->getNumber();

$request      = HTTPRequest::instance();
$user_manager = UserManager::instance();

$user = $user_manager->getUserByUserName($request->get('form_loginname'));
if ($user === null) {
    exit_error('Invalid User', 'That user does not exist.');
}

$reset_token_dao         = new Tuleap\User\Password\Reset\DataAccessObject();
$reset_token_creator     = new \Tuleap\User\Password\Reset\Creator(
    $reset_token_dao,
    new Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher()
);
try {
    $reset_token = $reset_token_creator->create($user);
} catch (TokenNotCreatedException $ex) {
    $GLOBALS['Response']->addFeedback(
        Feedback::ERROR,
        $GLOBALS['Language']->getText('account_lostpw-confirm', 'token_generation_failed')
    );
    $GLOBALS['Response']->redirect('/account/lostpw.php');
}

$reset_token_formatter = new \Tuleap\User\Password\Reset\ResetTokenSerializer();
$identifier            = $reset_token_formatter->getIdentifier($reset_token);

$message = stripcslashes($Language->getText(
    'account_lostpw-confirm',
    'mail_body',
    array($GLOBALS['sys_name'],
    $request->getServerUrl() . '/account/lostlogin.php?confirm_hash=' . urlencode($identifier))
));

$mail = new Codendi_Mail();
$mail->setTo($user->getEmail(), true);
$mail->setSubject($Language->getText('account_lostpw-confirm', 'mail_subject', array($GLOBALS['sys_name'])));
$mail->setBodyText($message);
$mail->setFrom($GLOBALS['sys_noreply']);
$mail_is_sent = $mail->send();
if (!$mail_is_sent) {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])), CODENDI_PURIFIER_FULL);
}
site_header(array('title' => $Language->getText('account_lostpw-confirm', 'title')));
if ($mail_is_sent) {
    echo '<p>' . $Language->getText('account_lostpw-confirm', 'msg_confirm') . '</p>';
}
echo '<p><a href="/">[' . $Language->getText('global', 'back_home') . ']</a></p>';
site_footer(array());
