<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project;

use ForgeConfig;
use Project;

final class DefaultProjectVisibilityRetriever
{
    public const CONFIG_SETTING_NAME = 'default_project_visibility';

    public function getDefaultProjectVisibility(): string
    {
        $are_restricted_users_allowed          = ForgeConfig::areRestrictedUsersAllowed();
        $default_project_visibility_setting    = ForgeConfig::get(self::CONFIG_SETTING_NAME);

        if ($are_restricted_users_allowed && $default_project_visibility_setting === Project::ACCESS_PUBLIC_UNRESTRICTED) {
            return $default_project_visibility_setting;
        }

        if ($are_restricted_users_allowed && $default_project_visibility_setting === Project::ACCESS_PRIVATE_WO_RESTRICTED) {
            return $default_project_visibility_setting;
        }

        if (
            $default_project_visibility_setting === Project::ACCESS_PRIVATE ||
            $default_project_visibility_setting === Project::ACCESS_PUBLIC
        ) {
            return $default_project_visibility_setting;
        }

        if ($default_project_visibility_setting === Project::ACCESS_PRIVATE_WO_RESTRICTED) {
            return Project::ACCESS_PRIVATE;
        }

        if ($default_project_visibility_setting === Project::ACCESS_PUBLIC_UNRESTRICTED) {
            return Project::ACCESS_PUBLIC;
        }

        $is_project_public_setting = ForgeConfig::get('sys_is_project_public');

        if ($is_project_public_setting !== false && (int) $is_project_public_setting === 0) {
            return Project::ACCESS_PRIVATE;
        }

        return Project::ACCESS_PUBLIC;
    }
}
