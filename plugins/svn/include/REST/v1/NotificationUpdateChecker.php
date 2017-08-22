<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\Svn\Admin\MailNotification;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Repository\Repository;

class NotificationUpdateChecker
{
    /**
     * @var MailNotificationManager
     */
    private $mail_notification_manager;

    public function __construct(MailNotificationManager $mail_notification_manager)
    {
        $this->mail_notification_manager       = $mail_notification_manager;
    }

    /**
     * @param Repository         $repository
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
            if (! $this->mail_notification_manager->isAnExistingPath(
                $repository,
                0,
                $new_notification->getPath()
            )) {
                return true;
            }

            $old_notifications = $this->mail_notification_manager->getByPathStrictlyEqual(
                $repository,
                $new_notification->getPath()
            );

            foreach ($old_notifications as $old_notification) {
                if ($new_notification->getPath() === $old_notification->getPath()) {
                    if ($this->sortNotification($new_notification->getNotifiedMails())
                        !== $this->sortNotification($old_notification->getNotifiedMails())
                    ) {
                        return true;
                    }
                }
            }

            if (count($new_notification->getNotifiedUsers()) > 0) {
                return true;
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
