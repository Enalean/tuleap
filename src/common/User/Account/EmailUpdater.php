<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);


namespace Tuleap\User\Account;

use UserManager;

class EmailUpdater
{
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(UserManager $user_manager)
    {
        $this->user_manager = $user_manager;
    }

    /**
     * @throws EmailNotSentException
     */
    public function setEmailChangeConfirm(string $server_url, \PFUser $current_user, string $new_mail): void
    {
        $confirmation_hash = (new \RandomNumberGenerator())->getNumber();
        $this->user_manager->setEmailChangeConfirm($current_user->getId(), $confirmation_hash, $new_mail);

        $subject = sprintf(
            _('[%s] Email change confirmation'),
            \ForgeConfig::get('sys_name')
        );
        $message = sprintf(
            _("You have requested a change of email address on %s.\nPlease visit the following URL to complete the email change:\n\n%s\n\n-- The %s Team"),
            \ForgeConfig::get('sys_name'),
            $server_url.$this->getChangeCompleteUrl($confirmation_hash),
            \ForgeConfig::get('sys_name')
        );

        $mail = new \Codendi_Mail();
        $mail->setTo($new_mail, true);
        $mail->setSubject($subject);
        $mail->setBodyText($message);
        $mail->setFrom(\ForgeConfig::get('sys_noreply'));
        if (! $mail->send()) {
            throw new EmailNotSentException();
        }
    }

    public function getChangeCompleteUrl($confirmation_hash)
    {
        return '/account/change_email-complete.php?confirm_hash='.$confirmation_hash;
    }
}
