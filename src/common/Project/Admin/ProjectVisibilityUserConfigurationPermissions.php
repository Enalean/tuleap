<?php
/**
 * Copyright Enalean (c) 2017 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin;

use ForgeConfig;
use PFUser;
use Project;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;

class ProjectVisibilityUserConfigurationPermissions
{
    public function canUserConfigureProjectVisibility(PFUser $user, Project $project)
    {
        return $user->isSuperUser()
            || (ForgeConfig::get(ProjectVisibilityConfigManager::PROJECT_ADMIN_CAN_CHOOSE_VISIBILITY)
                && $user->isAdmin($project->getID())
            );
    }

    public function canUserConfigureTruncatedMail(PFUser $user)
    {
        return $user->isSuperUser();
    }

    public function canUserConfigureSomething(PFUser $user, Project $project)
    {
        return $this->canUserConfigureTruncatedMail($user) ||
            $this->canUserConfigureProjectVisibility($user, $project);
    }
}
