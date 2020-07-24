<?php
/**
 * Copyright Enalean (c) 2017-2018. All rights reserved.
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

namespace Tuleap\Tracker\Notifications;

use ProjectUGroup;
use Tracker;
use UGroupDao;

class NotificationListBuilder
{
    /**
     * @var CollectionOfUserInvolvedInNotificationPresenterBuilder
     */
    private $user_involved_in_notification_presenter_builder;
    /**
     * @var CollectionOfUgroupToBeNotifiedPresenterBuilder
     */
    private $ugroup_to_be_notified_builder;
    /**
     * @var UGroupDao
     */
    private $ugroup_dao;

    public function __construct(
        UGroupDao $ugroup_dao,
        CollectionOfUserInvolvedInNotificationPresenterBuilder $user_involved_in_notification_presenter_builder,
        CollectionOfUgroupToBeNotifiedPresenterBuilder $ugroup_to_be_notified_builder
    ) {
        $this->ugroup_dao                                      = $ugroup_dao;
        $this->user_involved_in_notification_presenter_builder = $user_involved_in_notification_presenter_builder;
        $this->ugroup_to_be_notified_builder                   = $ugroup_to_be_notified_builder;
    }

    public function getNotificationsPresenter(array $notifications, GlobalNotificationsAddressesBuilder $addresses_builder)
    {
        $notifications_presenters = [];
        foreach ($notifications as $notification) {
            $emails_to_be_notified = $addresses_builder->transformNotificationAddressesStringAsArray(
                $notification->getAddresses()
            );
            $user_presenters   = $this->user_involved_in_notification_presenter_builder->getCollectionOfUserToBeNotifiedPresenter($notification);
            $ugroup_presenters = $this->ugroup_to_be_notified_builder->getCollectionOfUgroupToBeNotifiedPresenter($notification);
            $notifications_presenters[] = new PaneNotificationPresenter(
                $notification,
                $emails_to_be_notified,
                $user_presenters,
                $ugroup_presenters,
                json_encode($this->transformEmailsData($emails_to_be_notified)),
                json_encode($this->transformUsersData($user_presenters)),
                json_encode($this->transformUgroupsData($ugroup_presenters))
            );
        }
        return $notifications_presenters;
    }

    /**
     * @return UnsubscriberListPresenter
     */
    public function getUnsubscriberListPresenter(Tracker $tracker)
    {
        $unsubscribers = $this->user_involved_in_notification_presenter_builder->getCollectionOfNotificationUnsubscribersPresenter(
            $tracker
        );

        return new UnsubscriberListPresenter($tracker, ...$unsubscribers);
    }

    private function transformUgroupsData($ugroups_to_be_notified)
    {
        $ugroups_to_be_notified_parsed = [];
        foreach ($ugroups_to_be_notified as $ugroup_presenter) {
            $ugroup_row    = $this->ugroup_dao->searchByUGroupId($ugroup_presenter->ugroup_id)->getRow();
            $ugroup        = new ProjectUGroup($ugroup_row);
            $ugroup_parsed = [
                'type' => 'group',
                'id'   => '_ugroup:' . $ugroup->getNormalizedName(),
                'text' => $ugroup->getTranslatedName()
            ];
            $ugroups_to_be_notified_parsed[] = $ugroup_parsed;
        }
        return $ugroups_to_be_notified_parsed;
    }

    private function transformUsersData($users_to_be_notified)
    {
        $users_to_be_notified_parsed = [];
        foreach ($users_to_be_notified as $user_presenter) {
            $user_parsed                   = (array) $user_presenter;
            $user_parsed['type']           = 'user';
            $user_parsed['id']             = $user_presenter->label;
            $user_parsed['text']           = $user_presenter->label;
            $users_to_be_notified_parsed[] = $user_parsed;
        }
        return $users_to_be_notified_parsed;
    }

    private function transformEmailsData($emails_to_be_notified)
    {
        $emails_to_be_notified_parsed = [];
        foreach ($emails_to_be_notified as $email) {
            $email_parsed = [
                'type' => 'email',
                'id'   => $email,
                'text' => $email
            ];
            $emails_to_be_notified_parsed[] = $email_parsed;
        }
        return $emails_to_be_notified_parsed;
    }
}
