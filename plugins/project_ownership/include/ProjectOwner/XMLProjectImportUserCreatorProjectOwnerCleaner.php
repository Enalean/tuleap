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

namespace Tuleap\ProjectOwnership\ProjectOwner;

use Tuleap\Project\Admin\ProjectUGroup\ProjectImportCleanupUserCreatorFromAdministrators;

final class XMLProjectImportUserCreatorProjectOwnerCleaner
{
    /**
     * @var ProjectOwnerUpdater
     */
    private $project_owner_updater;

    public function __construct(ProjectOwnerUpdater $project_owner_updater)
    {
        $this->project_owner_updater = $project_owner_updater;
    }

    public function updateProjectOwnership(
        ProjectImportCleanupUserCreatorFromAdministrators $cleanup_user_creator_from_administrators
    ): void {
        $ugroup_admin = $cleanup_user_creator_from_administrators->getUGroupAdministrator();
        $project      = $ugroup_admin->getProject();
        if ($project === null) {
            return;
        }
        $creator = $cleanup_user_creator_from_administrators->getCreator();

        foreach ($ugroup_admin->getMembers() as $administrator) {
            if ($administrator->getId() !== $creator->getId()) {
                $this->project_owner_updater->updateProjectOwner($project, $administrator);
                return;
            }
        }
    }
}
