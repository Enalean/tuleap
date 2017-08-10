<?php
/**
 *  Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\SVN\REST\v1;

class SettingsRepresentationValidator
{
    /**
     * @param SettingsRepresentation $settings
     *
     * @return array
     */
    private function getNonUniquePath(SettingsRepresentation $settings = null)
    {
        $already_seen_path = array();
        $non_unique_path   = array();

        if (isset($settings->email_notifications)) {
            foreach ($settings->email_notifications as $notification) {
                if (isset($already_seen_path[$notification->path])) {
                    $non_unique_path[] = $notification->path;
                }

                $already_seen_path[$notification->path] = true;
            }
        }

        return $non_unique_path;
    }

    private function isAccessFileKeySent(SettingsRepresentation $settings = null)
    {
        return isset($settings->access_file);
    }

    public function validateForPUTRepresentation(SettingsPUTRepresentation $settings = null)
    {
        if (isset($settings)) {
            if (! $this->isAccessFileKeySent($settings)) {
                throw new SettingsInvalidException('`settings[access_file]` is required');
            }

            $this->validatePathAreUnique($settings);
        }
    }

    public function validateForPOSTRepresentation(SettingsPOSTRepresentation $settings = null)
    {
        if (isset($settings)) {
            $this->validatePathAreUnique($settings);
            $this->validateAtLeastOneNotificationSent($settings);
        }
    }

    private function validatePathAreUnique(SettingsRepresentation $settings)
    {
        $non_unique_path = $this->getNonUniquePath($settings);
        if (count($non_unique_path) > 0) {
            throw new SettingsInvalidException('One path or more are not unique: ' . implode(', ', $non_unique_path));
        }
    }

    private function validateAtLeastOneNotificationSent(SettingsPOSTRepresentation $settings = null)
    {
        $empty_notification = array();
        if ($settings) {
            foreach ($settings->email_notifications as $notification) {
                if (count($notification->emails) === 0 && count($notification->users) === 0) {
                    $empty_notification[] = $notification->path;
                }
            }
        }

        if (count($empty_notification) > 0) {
            throw new SettingsInvalidException(
                "Notification should concern at least one email or one user for path: " .
                implode(',', $empty_notification)
            );
        }
    }
}
