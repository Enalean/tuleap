<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

use Tuleap\SVN\Admin\MailNotification;
use Tuleap\SVN\Admin\UserGroupsPresenterBuilder;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\ProvideUserFromRow;
use Tuleap\User\REST\MinimalUserRepresentation;
use User_ForgeUGroup;
use function Psl\Json\encode;

class NotificationListBuilder
{
    /**
     * @var CollectionOfUgroupToBeNotifiedPresenterBuilder
     */
    private $ugroup_to_be_notified_builder;
    /**
     * @var CollectionOfUserToBeNotifiedPresenterBuilder
     */
    private $user_to_be_notified_builder;

    public function __construct(
        private readonly UgroupsToNotifyDao $ugroups_to_notify_dao,
        private readonly UsersToNotifyDao $users_to_notify_dao,
        private readonly ProvideUserFromRow $user_manager,
        private readonly ProvideUserAvatarUrl $provide_user_avatar_url,
        private readonly UserGroupsPresenterBuilder $user_groups_presenter_builder,
        CollectionOfUserToBeNotifiedPresenterBuilder $user_to_be_notified_builder,
        CollectionOfUgroupToBeNotifiedPresenterBuilder $ugroup_to_be_notified_builder,
    ) {
        $this->user_to_be_notified_builder   = $user_to_be_notified_builder;
        $this->ugroup_to_be_notified_builder = $ugroup_to_be_notified_builder;
    }

    /**
     * @param User_ForgeUGroup[] $project_ugroups
     * @param MailNotification[] $notifications
     * @return NotificationPresenter[]
     */
    public function getNotificationsPresenter(array $project_ugroups, array $notifications): array
    {
        $notifications_presenters = [];
        foreach ($notifications as $notification) {
            $emails_to_be_notified = $notification->getNotifiedMails();
            $user_presenters       = $this->user_to_be_notified_builder->getCollectionOfUserToBeNotifiedPresenter($notification);
            $ugroup_presenters     = $this->ugroup_to_be_notified_builder->getCollectionOfUgroupToBeNotifiedPresenter($notification);

            $ugroups = $this->getUgroups($project_ugroups, $notification);

            $notifications_presenters[] = new NotificationPresenter(
                $notification,
                $emails_to_be_notified,
                $user_presenters,
                $ugroup_presenters,
                $this->getUsersToBeNotifiedJson($notification),
                $ugroups,
            );
        }
        return $notifications_presenters;
    }

    /**
     * @param User_ForgeUGroup[] $project_ugroups
     * @return list<array{id: int, name: string, selected: bool}>
     */
    private function getUgroups(array $project_ugroups, MailNotification $notification): array
    {
        $selected = [];
        foreach ($this->ugroups_to_notify_dao->searchUgroupsByNotificationId($notification->getId()) as $row) {
            $selected[$row['ugroup_id']] = true;
        }

        return $this->user_groups_presenter_builder->getUgroups($project_ugroups, $selected);
    }

    private function getUsersToBeNotifiedJson(MailNotification $notification): string
    {
        $users = [];
        foreach ($this->users_to_notify_dao->searchUsersByNotificationId($notification->getId()) as $row) {
            $user    = $this->user_manager->getUserInstanceFromRow($row);
            $users[] = MinimalUserRepresentation::build($user, $this->provide_user_avatar_url);
        }

        usort($users, function (MinimalUserRepresentation $a, MinimalUserRepresentation $b) {
            return strnatcasecmp($a->display_name, $b->display_name);
        });

        return encode($users);
    }
}
