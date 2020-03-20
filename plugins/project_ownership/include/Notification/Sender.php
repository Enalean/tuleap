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
 */

namespace Tuleap\ProjectOwnership\Notification;

use Codendi_HTMLPurifier;
use Codendi_Mail;
use ForgeConfig;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\ProjectOwnership\Exceptions\FailedToNotifyProjectMemberException;

class Sender
{

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(ProjectManager $project_manager)
    {
        $this->project_manager = $project_manager;
    }

    /**
     * @throws FailedToNotifyProjectMemberException
     */
    public function sendNotification($project_id, $status)
    {
        $project = $this->project_manager->getProject($project_id);

        $notifications_enabled = ForgeConfig::get('project_owner_notify_project_members');
        if ($notifications_enabled === false) {
            return;
        }

        foreach ($project->getMembers() as $project_member) {
            $this->sendMailPerProjectMember($project, $project_member, $status);
        }
    }

    /**
     * @throws FailedToNotifyProjectMemberException
     */
    private function sendMailPerProjectMember(Project $project, PFUser $user, $status)
    {
        $user_language = $user->getLanguage();
        $purifier      = Codendi_HTMLPurifier::instance();

        $title = $user_language->getText(
            'plugin_project_ownership',
            'email_status_change_title'
        );

        switch ($status) {
            case 'H':
                $body = $user_language->getText(
                    'plugin_project_ownership',
                    'email_status_change_body_status_H',
                    $project->getPublicName()
                );
                break;
            case 'P':
                $body = $user_language->getText(
                    'plugin_project_ownership',
                    'email_status_change_body_status_P',
                    $project->getPublicName()
                );
                break;
            case 'D':
                $body = $user_language->getText(
                    'plugin_project_ownership',
                    'email_status_change_body_status_D',
                    $project->getPublicName()
                );
                break;
            case 'A':
            default:
                $body = $user_language->getText(
                    'plugin_project_ownership',
                    'email_status_change_body_status_A',
                    $project->getPublicName()
                );
                break;
        }

        $body_text = $purifier->purify($body, CODENDI_PURIFIER_STRIP_HTML);

        $mail = new Codendi_Mail();
        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->setTo($user->getEmail());
        $mail->setSubject($purifier->purify($title, CODENDI_PURIFIER_STRIP_HTML));
        $mail->setBodyHtml($body_text);
        $mail->setBodyText($body_text);

        $is_sent = $mail->send();
        if (! $is_sent) {
            throw new FailedToNotifyProjectMemberException($user);
        }
    }
}
