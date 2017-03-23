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

namespace Tuleap\Docman\Notifications;

use Codendi_HTMLPurifier;
use Docman_Item;
use Tuleap\Notifications\UgroupToBeNotifiedPresenter;
use Tuleap\Notifications\UserToBeNotifiedPresenter;
use UserManager;

class NotificationListPresenter
{
    public $has_listeners;
    public $notifications;
    public $notified_people;
    public $validate_button;
    public $monitored_doc;
    public $purified_help;
    public $empty_state;
    public $ugroups_to_be_notified;
    public $users_to_be_notified;
    public $project_id;
    public $placeholder;
    public $enable_sub_hierarchy;

    public function __construct(array $users, array $ugroups, Docman_Item $item)
    {
        $this->has_listeners = count($users) + count($ugroups) > 0;
        $this->project_id    = $item->getGroupId();

        $this->users_to_be_notified   = $this->buildNotificationsFromUsers($users, $item);
        $this->ugroups_to_be_notified = $this->buildNotificationsFromUGroups($ugroups, $item);

        $this->placeholder     = dgettext('tuleap-docman', 'User, group');
        $this->notified_people = dgettext('tuleap-docman', 'Notified people');
        $this->validate_button = dgettext('tuleap-docman', 'Validate');
        $this->monitored_doc   = $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_monitored_doc');
        $this->empty_state     = $GLOBALS['Language']->getText('plugin_docman', 'empty_state');

        $this->enable_sub_hierarchy = $GLOBALS['Language']->getText('plugin_docman', 'notifications_add_user_cascade');
        $this->purified_help        = Codendi_HTMLPurifier::instance()->purify(
            $GLOBALS['Language']->getText('plugin_docman', 'details_notifications_help'),
            CODENDI_PURIFIER_LIGHT
        );
    }

    private function buildNotificationsFromUsers(array $users, Docman_Item $item)
    {
        $user_manager    = UserManager::instance();
        $users_to_notify = array();

        foreach ($users as $user_id => $monitored_item) {
            $user = $user_manager->getUserById($user_id);

            $users_to_notify[] = array(
                'can_be_deleted' => $monitored_item == $item,
                'item_title'     => $item->getTitle(),
                'user'           => new UserToBeNotifiedPresenter(
                    $user->getId(),
                    $user->getName(),
                    $user->getRealName(),
                    $user->hasAvatar(),
                    $user->getAvatarUrl()
                )
            );
        }

        return $users_to_notify;
    }

    private function buildNotificationsFromUGroups(array $ugroups, Docman_Item $item)
    {
        $groups_to_notify = array();

        foreach ($ugroups as $ugroup_monitored_item) {
            $monitored_item     = $ugroup_monitored_item->getMonitoredItem();
            $monitoring_ugroup  = $ugroup_monitored_item->getUgroupPresenter();
            $groups_to_notify[] = array(
                'can_be_deleted' => ($monitored_item == $item),
                'item_title'     => $monitored_item->getTitle(),
                'ugroup'         => $monitoring_ugroup
            );
        }

        return $groups_to_notify;
    }
}
