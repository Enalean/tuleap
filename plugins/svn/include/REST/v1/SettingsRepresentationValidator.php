<?php
/**
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
    private function getNonUniquePath(SettingsPOSTRepresentation | SettingsPUTRepresentation | null $settings = null): array
    {
        $already_seen_path = [];
        $non_unique_path   = [];

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

    private function getNonUniqueEmail(SettingsPOSTRepresentation | SettingsPUTRepresentation $settings): array
    {
        $non_unique_mail = [];

        if (! isset($settings->email_notifications)) {
            return $non_unique_mail;
        }

        foreach ($settings->email_notifications as $notification) {
            $duplicated_values = array_diff_key($notification->emails, array_unique($notification->emails));
            if (count($duplicated_values) > 0) {
                $non_unique_mail[$notification->path] = $duplicated_values;
            }
        }

        return $non_unique_mail;
    }

    private function isAccessFileKeySent(SettingsPOSTRepresentation | SettingsPUTRepresentation | null $settings = null): bool
    {
        return isset($settings->access_file);
    }

    public function validateForPUTRepresentation(?SettingsPUTRepresentation $settings = null): void
    {
        if (isset($settings)) {
            if (! $this->isAccessFileKeySent($settings)) {
                throw new SettingsInvalidException('`settings[access_file]` is required');
            }

            $this->validatePathAreUnique($settings);
            $this->validateAtLeastOneNotificationSent($settings);
            $this->validateMailAreUnique($settings);
        }
    }

    public function validateForPOSTRepresentation(?SettingsPOSTRepresentation $settings = null): void
    {
        if (isset($settings)) {
            $this->validatePathAreUnique($settings);
            $this->validateAtLeastOneNotificationSent($settings);
            $this->validateMailAreUnique($settings);
        }
    }

    private function validatePathAreUnique(SettingsPOSTRepresentation | SettingsPUTRepresentation $settings): void
    {
        $non_unique_path = $this->getNonUniquePath($settings);
        if (count($non_unique_path) > 0) {
            throw new SettingsInvalidException('One path or more are not unique: ' . implode(', ', $non_unique_path));
        }
    }

    private function validateAtLeastOneNotificationSent(SettingsPOSTRepresentation | SettingsPUTRepresentation | null $settings = null): void
    {
        $empty_notification = [];
        if ($settings && $settings->email_notifications) {
            foreach ($settings->email_notifications as $notification) {
                if (count($notification->emails) === 0 && count($notification->users) === 0 && count($notification->user_groups) === 0) {
                    $empty_notification[] = $notification->path;
                }
            }
        }

        if (count($empty_notification) > 0) {
            throw new SettingsInvalidException(
                "Notification should concern at least one email or one user or one user group for path: " .
                implode(',', $empty_notification)
            );
        }
    }

    private function validateMailAreUnique(SettingsPOSTRepresentation | SettingsPUTRepresentation $settings): void
    {
        $non_unique_mail = $this->getNonUniqueEmail($settings);

        $exceptions = [];
        foreach ($non_unique_mail as $path => $mail) {
            $exceptions[] = $path . ' : ' . implode(', ', $mail);
        }

        if (count($exceptions) > 0) {
            throw new SettingsInvalidException(
                'One email or more are not unique for path: ' . implode(PHP_EOL, $exceptions)
            );
        }
    }
}
