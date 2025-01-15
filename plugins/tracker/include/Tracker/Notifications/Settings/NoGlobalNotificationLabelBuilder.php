<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\Settings;

final readonly class NoGlobalNotificationLabelBuilder
{
    public function __construct(private UserGlobalAccountNotificationSettings $settings_retriever)
    {
    }

    public function getInputLabel(): string
    {
        if ($this->settings_retriever->notification_on_my_own_action->enabled && $this->settings_retriever->notification_on_all_update->enabled) {
            return dgettext(
                'tuleap-tracker',
                'Notify me on all update of artifacts I touch (assignee, submitter, cc, commentator, change of value, ...).'
            );
        } elseif ($this->settings_retriever->notification_on_my_own_action->enabled && ! $this->settings_retriever->notification_on_all_update->enabled) {
            return dgettext(
                'tuleap-tracker',
                "Notify me on all updates I do on artifacts but not when other users further change artifact, unless I'm creator or assignee."
            );
        } elseif (! $this->settings_retriever->notification_on_my_own_action->enabled && $this->settings_retriever->notification_on_all_update->enabled) {
            return dgettext(
                'tuleap-tracker',
                'Notify me when other users change an artifact I touched (assignee, submitter, cc, commentator, change of value, ...).'
            );
        } else {
            return dgettext(
                'tuleap-tracker',
                "Notify me when other users change artifacts I created or I'm assigned to (and all fields that can trigger notifications)."
            );
        }
    }
}
