<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Svn\Notifications;

use ProjectUGroup;
use Tuleap\Svn\Admin\MailNotification;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Repository\Repository;
use UGroupManager;

class EmailsToBeNotifiedRetriever
{
    /**
     * @var MailNotificationManager
     */
    private $notification_manager;
    /**
     * @var UsersToNotifyDao
     */
    private $user_dao;
    /**
     * @var UgroupsToNotifyDao
     */
    private $ugroup_dao;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        MailNotificationManager $notification_manager,
        UsersToNotifyDao $user_dao,
        UgroupsToNotifyDao $ugroup_dao,
        UGroupManager $ugroup_manager
    ) {
        $this->notification_manager = $notification_manager;
        $this->user_dao             = $user_dao;
        $this->ugroup_dao           = $ugroup_dao;
        $this->ugroup_manager       = $ugroup_manager;
    }

    public function getEmailsToBeNotifiedForPath(Repository $repository, $path)
    {
        $notified_emails = array();

        $notifications = $this->notification_manager->getByPath($repository, $path);
        foreach ($notifications as $notification) {
            $this->addEmails($notification, $notified_emails);
            $this->addUsers($notification, $notified_emails);
            $this->addUgroups($notification, $notified_emails);
        }

        return array_unique(array_values(array_filter($notified_emails)));
    }

    private function addEmails($notification, &$emails)
    {
        $mail_list = explode(",", $notification->getNotifiedMails());
        $mail_list = array_map('trim', $mail_list);
        $emails    = array_merge($mail_list, $emails);
    }

    private function addUsers(MailNotification $notification, array &$emails)
    {
        foreach ($this->user_dao->searchUsersByNotificationId($notification->getId()) as $row) {
            $emails[] = $row['email'];
        }
    }

    private function addUgroups(MailNotification $notification, array &$emails)
    {
        $project = $notification->getRepository()->getProject();
        foreach ($this->ugroup_dao->searchUgroupsByNotificationId($notification->getId()) as $row) {
            $ugroup = $this->ugroup_manager->getUGroup($project, $row['ugroup_id']);
            if ($ugroup) {
                $this->addUgroupMembers($ugroup, $emails);
            }
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
}
