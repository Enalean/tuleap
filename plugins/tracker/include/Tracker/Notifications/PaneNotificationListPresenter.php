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
    public $edit;
    public $save;
    public $cancel;
    public $add_notification;
    public $remove_notif_desc;
    public $remove_notif_title;
    public $remove_notif_confirm;
    public $project_id;
    public $additional_information_for_autocompleter;

    public function __construct($project_id, $tracker_id, array $notifications)
    {
        $this->project_id                               = $project_id;
        $this->additional_information_for_autocompleter = json_encode(['tracker_id' => $tracker_id]);
        $this->notifications                            = $notifications;
        $this->empty_notification                       = dgettext('tuleap-tracker', 'No notification set');
        $this->has_notifications                        = (bool) (count($notifications) > 0);

        $this->admin_note      = dgettext(
            'tuleap-tracker',
            'As a tracker administrator, you can provide email addresses to which new Artifact submissions (and possibly updates) will be systematically sent.'
        );
        $this->notified_people = dgettext('tuleap-tracker', 'Notified people');
        $this->send_all        = dgettext('tuleap-tracker', 'On all updates?');
        $this->check_perms     = dgettext('tuleap-tracker', 'Check permissions?');
        $this->edit            = dgettext('tuleap-tracker', 'Edit');
        $this->save            = dgettext('tuleap-tracker', 'Save');
        $this->cancel          = dgettext('tuleap-tracker', 'Cancel');
        $this->delete          = dgettext('tuleap-tracker', 'Delete');

        $this->remove_notif_title           = dgettext('tuleap-tracker', 'Wait a minute...');
        $this->remove_notif_desc            = dgettext('tuleap-tracker', 'You are about to remove the notification. Please confirm your action.');
        $this->remove_notif_confirm         = dgettext('tuleap-tracker', 'Confirm deletion');
        $this->add_notification             = dgettext('tuleap-tracker', 'Add notification');
        $this->new_notification_placeholder = dgettext('tuleap-tracker', 'User, group, email');
    }
}
