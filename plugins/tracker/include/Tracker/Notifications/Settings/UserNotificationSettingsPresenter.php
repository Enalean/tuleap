<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications\Settings;

class UserNotificationSettingsPresenter
{
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var bool
     */
    public $is_in_no_notification_at_all_mode;
    /**
     * @var bool
     */
    public $is_in_no_global_notification_mode;
    /**
     * @var bool
     */
    public $is_in_notify_on_artifact_creation_mode;
    /**
     * @var bool
     */
    public $is_in_notify_on_every_change_mode;
    /**
     * @var bool
     */
    public $is_in_notify_on_status_change_mode;
    /**
     * @var bool
     */
    public $are_global_notifications_suspended;

    public function __construct(
        \CSRFSynchronizerToken $csrf_token,
        UserNotificationSettings $user_notification_settings,
        $are_global_notifications_suspended
    ) {
        $this->csrf_token                             = $csrf_token;
        $this->is_in_no_notification_at_all_mode      = $user_notification_settings->isInNoNotificationAtAllMode();
        $this->is_in_no_global_notification_mode      = $user_notification_settings->isInNoGlobalNotificationMode();
        $this->is_in_notify_on_artifact_creation_mode = $user_notification_settings->isInNotifyOnArtifactCreationMode();
        $this->is_in_notify_on_every_change_mode      = $user_notification_settings->isInNotifyOnEveryChangeMode();
        $this->is_in_notify_on_status_change_mode     = $user_notification_settings->isInNotifyOnStatusChange();
        $this->are_global_notifications_suspended     = $are_global_notifications_suspended;
    }
}
