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

namespace Tuleap\Tracker\Notifications;

class NotificationCustomisationSettingsPresenter
{
    public $notification_customisation_title;
    public $assign_to_me_enabled;
    public $assign_to_me_description;
    public $submit_changes;
    public $custom_email_enabled;
    public $custom_email_content;

    public function __construct($assign_to_me_enabled, $custom_email)
    {
        $this->notification_customisation_title = $GLOBALS['Language']->getText('plugin_tracker_include_type', 'notification_customisation_title');
        $this->assign_to_me_enabled             = $assign_to_me_enabled;
        $this->assign_to_me_description         = $GLOBALS['Language']->getText('plugin_tracker_include_type', 'assigned_to_me_description');
        $this->submit_changes                   = $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'submit');
        $this->custom_sender_description        = $GLOBALS['Language']->getText('plugin_tracker_include_type', 'custom_sender_description');
        $this->custom_email_enabled             = $custom_email['enabled'];
        $this->custom_email_content             = $custom_email['format'];
    }
}
