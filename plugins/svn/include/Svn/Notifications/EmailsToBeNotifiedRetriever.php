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

use Tuleap\Svn\Admin\MailNotification;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Repository\Repository;

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

    public function __construct(
        MailNotificationManager $notification_manager,
        UsersToNotifyDao $user_dao
    ) {
        $this->notification_manager = $notification_manager;
        $this->user_dao             = $user_dao;
    }

    public function getEmailsToBeNotifiedForPath(Repository $repository, $path)
    {
        $notified_emails = array();

        $notifications = $this->notification_manager->getByPath($repository, $path);
        foreach ($notifications as $notification) {
            $mail_list       = explode(",", $notification->getNotifiedMails());
            $mail_list       = array_map('trim', $mail_list);
            $notified_emails = array_merge($mail_list, $notified_emails);
            $this->addUsers($notification, $notified_emails);
        }

        return array_values(array_filter($notified_emails));
    }

    private function addUsers(MailNotification $notification, array &$emails)
    {
        foreach ($this->user_dao->searchUsersByNotificationId($notification->getId()) as $row) {
            $emails[] = $row['email'];
        }
    }
}
