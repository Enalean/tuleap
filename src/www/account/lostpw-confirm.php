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

require_once __DIR__ . '/../include/pre.php';

$event_manager = EventManager::instance();
$event_manager->processEvent('before_lostpw-confirm', []);

$number_generator = new RandomNumberGenerator();
$confirm_hash     = $number_generator->getNumber();

$request      = HTTPRequest::instance();
$user_manager = UserManager::instance();

$user = $user_manager->getUserByUserName($request->get('form_loginname'));
if ($user === null || $user->getUserPw() === null) {
    exit_error('Invalid User', 'That user does not exist.');
}

$reset_token_dao     = new Tuleap\User\Password\Reset\LostPasswordDAO();
$reset_token_creator = new \Tuleap\User\Password\Reset\Creator(
    $reset_token_dao,
    new Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher()
);
$reset_token         = $reset_token_creator->create($user);

$mail_is_sent = false;

if ($reset_token !== null) {
    $reset_token_formatter = new \Tuleap\User\Password\Reset\ResetTokenSerializer();
    $identifier            = $reset_token_formatter->getIdentifier($reset_token);

    $message = stripcslashes(sprintf(_('Someone (presumably you) on the %1$s site requested a
password change through email verification. If this was
not you,ignore this message and nothing will happen.

If you requested this verification, visit the following URL
to change your password:

%2$s

 -- The %1$s Team'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME), \Tuleap\ServerHostname::HTTPSUrl() . '/account/lostlogin.php?confirm_hash=' . urlencode($identifier)));

    $mail = new Codendi_Mail();
    $mail->setTo($user->getEmail(), true);
    $mail->setSubject(sprintf(_('%1$s Password Verification'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)));
    $mail->setBodyText($message);
    $mail->setFrom(ForgeConfig::get('sys_noreply'));
    $mail_is_sent = $mail->send();
    if (! $mail_is_sent) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', [ForgeConfig::get('sys_email_admin')]), CODENDI_PURIFIER_FULL);
    }
}

site_header(['title' => _('Lost Password Confirmation')]);
if ($reset_token === null || $mail_is_sent) {
    echo '<p>' . _('<B>Confirmation mailed</B><P>An email has been sent to the address you have on file. Follow the instructions in the email to change your account password.') . '</p>';
}
echo '<p><a href="/">[' . $Language->getText('global', 'back_home') . ']</a></p>';
site_footer([]);
