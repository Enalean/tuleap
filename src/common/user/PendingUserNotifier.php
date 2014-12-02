<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class User_PendingUserNotifier {

    public function notifyAdministrator(PFuser $user) {
        $user_name     = $user->getUserName();
        $href_approval = get_server_url().'/admin/approve_pending_users.php?page=pending';

        $from    = Config::get('sys_noreply');
        $to      = Config::get('sys_email_admin');
        $subject = $GLOBALS['Language']->getText('account_register', 'mail_approval_subject', $user_name);
        $body    = stripcslashes(
            $GLOBALS['Language']->getText(
                'account_register',
                'mail_approval_body',
                array(Config::get('sys_name'), $user_name, $href_approval)
            )
        );

        $mail = new Mail();
        $mail->setSubject($subject);
        $mail->setFrom($from);
        $mail->setTo($to, true);
        $mail->setBody($body);
        if (! $mail->send()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'mail_failed', $to)
            );
        }
    }
}
