<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\REST\v1;

use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Notifications\EmailsToBeNotifiedRetriever;
use Tuleap\SVNCore\Repository;

class NotificationUpdateChecker
{
    /**
     * @var MailNotificationManager
     */
    private $mail_notification_manager;

    /**
     * @var EmailsToBeNotifiedRetriever
     */
    private $emails_to_be_notified_retriever;

    public function __construct(
        MailNotificationManager $mail_notification_manager,
        EmailsToBeNotifiedRetriever $emails_to_be_notified_retriever,
    ) {
        $this->mail_notification_manager       = $mail_notification_manager;
        $this->emails_to_be_notified_retriever = $emails_to_be_notified_retriever;
    }

    /**
     * @param MailNotification[] $new_notifications
     *
     * @return bool
     */
    public function hasNotificationChanged(Repository $repository, array $new_notifications)
    {
        $all_old_notification = $this->mail_notification_manager->getByRepository($repository);

        if (count($all_old_notification) !== count($new_notifications)) {
            return true;
        }

        foreach ($new_notifications as $new_notification) {
            if (
                ! $this->mail_notification_manager->isAnExistingPath(
                    $repository,
                    0,
                    $new_notification->getPath()
                )
            ) {
                return true;
            }

            $old_notifications = $this->emails_to_be_notified_retriever->getNotificationsForPath(
                $repository,
                $new_notification->getPath()
            );

            foreach ($old_notifications as $old_notification) {
                if ($new_notification->getPath() === $old_notification->getPath()) {
                    if (
                        $this->sortNotification($new_notification->getNotifiedMails())
                        !== $this->sortNotification($old_notification->getNotifiedMails())
                    ) {
                        return true;
                    }

                    if (
                        $this->sortNotification($new_notification->getNotifiedUsers())
                        != $this->sortNotification($old_notification->getNotifiedUsers())
                    ) {
                        return true;
                    }

                    if (
                        $this->sortNotification($new_notification->getNotifiedUgroups())
                        != $this->sortNotification($old_notification->getNotifiedUgroups())
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function sortNotification(array $notification)
    {
        sort($notification);

        return $notification;
    }
}
