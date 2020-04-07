<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\admin\ProjectCreation\ProjectVisibility;

use ForgeConfig;
use Project;
use Tuleap\Project\DefaultProjectVisibilityRetriever;

class ProjectVisibilityConfigManager
{
    public const PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY    = 'project_admin_can_choose_visibility';
    public const SEND_MAIL_ON_PROJECT_VISIBILITY_CHANGE = 'send_mail_on_project_visibility_change';

    /**
     * @var \ConfigDao
     */
    private $config_dao;

    public function __construct(\ConfigDao $config_dao)
    {
        $this->config_dao = $config_dao;
    }

    public function updateVisibilityOption($forge_config_option, $new_value)
    {
        $old_value = (bool) ForgeConfig::get($forge_config_option);

        if ($old_value === $new_value) {
            return;
        }

        $this->config_dao->save($forge_config_option, $new_value);
    }

    public function updateDefaultProjectVisibility(string $new_value): bool
    {
        $acceptable_values = [Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE];
        if (ForgeConfig::areRestrictedUsersAllowed()) {
            $acceptable_values[] = Project::ACCESS_PUBLIC_UNRESTRICTED;
            $acceptable_values[] = Project::ACCESS_PRIVATE_WO_RESTRICTED;
        }

        if (! in_array($new_value, $acceptable_values, true)) {
            return false;
        }

        $this->updateVisibilityOption(DefaultProjectVisibilityRetriever::CONFIG_SETTING_NAME, $new_value);
        return true;
    }
}
