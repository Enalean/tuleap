<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
use Tuleap\Language\LocaleSwitcher;
use Tuleap\ProjectOwnership\Exceptions\FailedToNotifyProjectMemberException;

class Sender
{

    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var LocaleSwitcher
     */
    private $locale_switcher;

    public function __construct(ProjectManager $project_manager, LocaleSwitcher $locale_switcher)
    {
        $this->project_manager = $project_manager;
        $this->locale_switcher = $locale_switcher;
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
            $this->locale_switcher->setLocaleForSpecificExecutionContext(
                $project_member->getLocale(),
                function () use (
                    $status,
                    $project_member,
                    $project
                ): void {
                    $this->sendMailPerProjectMember($project, $project_member, $status);
                }
            );
        }
    }

    /**
     * @throws FailedToNotifyProjectMemberException
     */
    private function sendMailPerProjectMember(Project $project, PFUser $user, $status): void
    {
        $purifier      = Codendi_HTMLPurifier::instance();

        $title = dgettext('tuleap-project_ownership', 'Project status updated');

        switch ($status) {
            case 'H':
                $body = sprintf(dgettext('tuleap-project_ownership', 'The project %1$s is now suspended.'), $project->getPublicName());
                break;
            case 'P':
                $body = sprintf(dgettext('tuleap-project_ownership', 'The project %1$s is now pending.'), $project->getPublicName());
                break;
            case 'D':
                $body = sprintf(dgettext('tuleap-project_ownership', 'The project %1$s is now deleted.'), $project->getPublicName());
                break;
            case 'A':
            default:
                $body = sprintf(dgettext('tuleap-project_ownership', 'The project %1$s is now active.'), $project->getPublicName());
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
