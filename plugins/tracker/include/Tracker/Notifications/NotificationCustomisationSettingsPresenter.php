<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

    public function __construct(
        $assign_to_me_enabled,
        $custom_email,
        public readonly bool $should_send_event_in_notification,
        public readonly bool $is_semantic_timeframe_defined,
        public readonly string $semantic_timeframe_admin_url,
        public readonly bool $is_semantic_title_defined,
        public readonly string $semantic_title_admin_url,
    ) {
        $this->notification_customisation_title = dgettext('tuleap-tracker', 'Email subject customisation');
        $this->assign_to_me_enabled             = $assign_to_me_enabled;
        $this->assign_to_me_description         = dgettext('tuleap-tracker', 'Include [Assigned to me] flag in subject for people who are assigned to the artifact');
        $this->submit_changes                   = dgettext('tuleap-tracker', 'Submit Changes');
        $this->custom_sender_description        = dgettext('tuleap-tracker', 'Enable custom sender fields for email notifications');
        $this->custom_email_enabled             = $custom_email['enabled'];
        $this->custom_email_content             = $custom_email['format'];
    }
}
