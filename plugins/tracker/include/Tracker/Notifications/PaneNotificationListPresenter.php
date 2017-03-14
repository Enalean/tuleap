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

namespace Tuleap\Tracker\Notifications;

class PaneNotificationListPresenter
{
    public $notifications;
    public $empty_notification;
    public $has_notifications;
    public $admin_note;
    public $notified_people;
    public $send_all;
    public $check_perms;
    public $new_notification_placeholder;

    /**
     * @var CollectionOfUserToBeNotifiedPresenterBuilder
     */
    private $user_to_be_notified_builder;
    /**
     * @var CollectionOfUgroupToBeNotifiedPresenterBuilder
     */
    private $ugroup_to_be_notified_builder;

    public function __construct(
        $notifications,
        CollectionOfUserToBeNotifiedPresenterBuilder $user_to_be_notified_builder,
        CollectionOfUgroupToBeNotifiedPresenterBuilder $ugroup_to_be_notified_builder
    ) {
        $this->user_to_be_notified_builder   = $user_to_be_notified_builder;
        $this->ugroup_to_be_notified_builder = $ugroup_to_be_notified_builder;

        $this->notifications      = $this->getNotificationsPresenter($notifications);
        $this->empty_notification = dgettext('tuleap-tracker', 'No notification set');
        $this->has_notifications  = (bool)(count($notifications) > 0);

        $this->admin_note      = dgettext(
            'tuleap-tracker',
            'As a tracker administrator you can provide email addresses (comma separated) to which new Artifact submissions (and possibly updates) will be systematically sent.'
        );
        $this->notified_people = dgettext('tuleap-tracker', 'Notified people');
        $this->send_all        = dgettext('tuleap-tracker', 'Send on all updates?');
        $this->check_perms     = dgettext('tuleap-tracker', 'Check permissions?');

        $this->new_notification_placeholder = dgettext('tuleap-tracker', 'Enter here a comma separated email addresses list to be notified');
    }

    private function getNotificationsPresenter($notifications)
    {
        $notifications_presenters = array();
        foreach ($notifications as $notification) {
            $user_presenters   = $this->user_to_be_notified_builder->getCollectionOfUserToBeNotifiedPresenter($notification);
            $ugroup_presenters = $this->ugroup_to_be_notified_builder->getCollectionOfUgroupToBeNotifiedPresenter($notification);
            $notifications_presenters[] = new PaneNotificationPresenter(
                $notification,
                $user_presenters,
                $ugroup_presenters
            );
        }
        return $notifications_presenters;
    }
}
