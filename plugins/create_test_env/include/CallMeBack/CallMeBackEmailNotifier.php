<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CallMeBack;

use PFUser;
use Codendi_Mail;
use ForgeConfig;
use Valid_Email;
use Tuleap\CallMeBack\Exception\NotifyException;

class CallMeBackEmailNotifier
{
    /**
     * @var CallMeBackEmailDao
     */
    private $email_dao;

    public function __construct(CallMeBackEmailDao $email_dao)
    {
        $this->email_dao = $email_dao;
    }

    public function notify(PFUser $current_user, $phone, $date)
    {
        $to              = $this->email_dao->get();
        $email_validator = new Valid_Email();

        if (! $email_validator->validate($to)) {
            throw new NotifyException('Destination email is empty');
        }

        $subject = sprintf(
            dgettext('tuleap-create_test_env', '%s wants to be called back'),
            $current_user->getRealName()
        );

        $body = sprintf(
            dgettext('tuleap-create_test_env', '%s (%s - %s) wants to be called back on %s.'),
            $current_user->getRealName(),
            $current_user->getEmail(),
            $phone,
            $date
        );

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo($to);
        $mail->addAdditionalHeader('Reply-To', $current_user->getRealName().' <'.$current_user->getEmail().'>');
        $mail->setSubject($subject);
        $mail->setBodyHtml($body);

        if (! $mail->send()) {
            throw new NotifyException('Unable to send the email to ' . $to);
        }
    }
}
