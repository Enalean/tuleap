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

class EmailUpdater
{
    /**
     * @throws EmailNotSentException
     */
    public function sendEmailChangeConfirm(string $server_url, \PFUser $current_user): void
    {
        $subject = sprintf(
            _('[%s] Email change confirmation'),
            \ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)
        );
        $message = sprintf(
            _("You have requested a change of email address on %1\$s.\nPlease visit the following URL to complete the email change:\n\n%2\$s%3\$s\n\n-- The %4\$s Team"),
            \ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME),
            $server_url,
            ConfirmNewEmailController::getUrlToSelf($current_user->getConfirmHash()),
            \ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)
        );

        $mail = new \Codendi_Mail();
        $mail->setTo($current_user->getEmailNew(), true);
        $mail->setSubject($subject);
        $mail->setBodyText($message);
        $mail->setFrom(\ForgeConfig::get('sys_noreply'));
        if (! $mail->send()) {
            throw new EmailNotSentException();
        }
    }
}
