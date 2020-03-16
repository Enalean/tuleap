<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
/**
 * Sends mails to a group of users in a project.
 */
class MassmailSender
{

    /**
     *
     * Send mails to a group of people and check the max number of emailed people limit.
     *
     * @param PFUser[] $receivers
     */
    public function sendMassmail(Project $project, PFUser $user, string $subject, string $html_body, array $receivers)
    {
        $hp             = Codendi_HTMLPurifier::instance();
        $project_name   = $project->getPublicName();

        $sys_max_number_of_emailed_people = ForgeConfig::get('sys_max_number_of_emailed_people');
        if (count($receivers) > $sys_max_number_of_emailed_people && !$user->isSuperUser()) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('my_index', 'massmail_not_sent_max_users', $sys_max_number_of_emailed_people));
            return;
        }

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo($user->getEmail());
        $mail->addAdditionalHeader('Reply-To', $user->getEmail());
        $mail->setBccUser($receivers);
        $mail->setSubject("[" . $GLOBALS['sys_name'] . "] [" . $project_name . "] " . $subject);
        $mail->setBodyText($hp->purify($html_body, CODENDI_PURIFIER_STRIP_HTML));

        $mail->setBodyHtml($html_body);

        $is_sent = $mail->send();
        return $is_sent;
    }
}
