<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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
use UGroupDao;

class NotificationListBuilder
{
    /**
     * @var CollectionOfUgroupToBeNotifiedPresenterBuilder
     */
    private $ugroup_to_be_notified_builder;
    /**
     * @var UGroupDao
     */
    private $ugroup_dao;
    /**
     * @var CollectionOfUserToBeNotifiedPresenterBuilder
     */
    private $user_to_be_notified_builder;

    public function __construct(
        UGroupDao $ugroup_dao,
        CollectionOfUserToBeNotifiedPresenterBuilder $user_to_be_notified_builder,
        CollectionOfUgroupToBeNotifiedPresenterBuilder $ugroup_to_be_notified_builder
    ) {
        $this->ugroup_dao                    = $ugroup_dao;
        $this->user_to_be_notified_builder   = $user_to_be_notified_builder;
        $this->ugroup_to_be_notified_builder = $ugroup_to_be_notified_builder;
    }

    /**
     * @param MailNotification[] $notifications
     * @param NotificationsEmailsBuilder $emails_builder
     * @return array
     */
    public function getNotificationsPresenter(array $notifications, NotificationsEmailsBuilder $emails_builder)
    {
        $notifications_presenters = array();
        foreach ($notifications as $notification) {
            $emails_to_be_notified = $emails_builder->transformNotificationEmailsStringAsArray(
                $notification->getNotifiedMails()
            );
            $user_presenters   = $this->user_to_be_notified_builder->getCollectionOfUserToBeNotifiedPresenter($notification);
            $ugroup_presenters = $this->ugroup_to_be_notified_builder->getCollectionOfUgroupToBeNotifiedPresenter($notification);
            $notifications_presenters[] = new NotificationPresenter(
                $notification,
                $emails_to_be_notified,
                $user_presenters,
                $ugroup_presenters,
                $notification->getNotifiedMails()
            );
        }
        return $notifications_presenters;
    }
}
