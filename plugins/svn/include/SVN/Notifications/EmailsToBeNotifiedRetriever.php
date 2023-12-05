<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Notifications;

use ProjectUGroup;
use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVNCore\Repository;

class EmailsToBeNotifiedRetriever
{
    /**
     * @var MailNotificationManager
     */
    private $notification_manager;

    public function __construct(
        MailNotificationManager $notification_manager,
    ) {
        $this->notification_manager = $notification_manager;
    }

    public function getEmailsToBeNotifiedForPath(Repository $repository, $path)
    {
        $notified_emails = [];

        $notifications = $this->notification_manager->getByPath($repository, $path);
        foreach ($notifications as $notification) {
            $this->addEmails($notification, $notified_emails);
            $this->addUsers($notification, $notified_emails);
            $this->addUgroups($notification, $notified_emails);
        }

        return array_unique(array_values(array_filter($notified_emails)));
    }

    private function addEmails(MailNotification $notification, &$emails)
    {
        $mail_list = array_map('trim', $notification->getNotifiedMails());
        $emails    = array_merge($mail_list, $emails);
    }

    private function addUsers(MailNotification $notification, array &$emails)
    {
        foreach ($notification->getNotifiedUsers() as $user) {
            $emails[] = $user->getEmail();
        }
    }

    private function addUgroups(MailNotification $notification, array &$emails)
    {
        foreach ($notification->getNotifiedUgroups() as $ugroup) {
            $this->addUgroupMembers($ugroup, $emails);
        }
    }

    private function addUgroupMembers(ProjectUGroup $ugroup, array &$emails)
    {
        foreach ($ugroup->getMembers() as $user) {
            if ($user->isAlive()) {
                $emails[] = $user->getEmail();
            }
        }
    }

    public function getNotificationsForPath(Repository $repository, $path)
    {
        return $this->notification_manager->getByPathStrictlyEqual($repository, $path);
    }
}
