<?php
/**
 * Copyright Enalean (c) 2017 - 2018. All rights reserved.
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
     * @return array
     */
    public function getNotificationsPresenter(array $notifications)
    {
        $notifications_presenters = [];
        foreach ($notifications as $notification) {
            $emails_to_be_notified = $notification->getNotifiedMails();
            $user_presenters   = $this->user_to_be_notified_builder->getCollectionOfUserToBeNotifiedPresenter($notification);
            $ugroup_presenters = $this->ugroup_to_be_notified_builder->getCollectionOfUgroupToBeNotifiedPresenter($notification);
            $notifications_presenters[] = new NotificationPresenter(
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
     * @param array $ugroups_to_be_notified
     * @return array
     */
    private function transformUgroupsData(array $ugroups_to_be_notified)
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

    /**
     * @param array $users_to_be_notified
     * @return array
     */
    private function transformUsersData(array $users_to_be_notified)
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

    /**
     * @param array $emails_to_be_notified
     * @return array
     */
    private function transformEmailsData(array $emails_to_be_notified)
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
