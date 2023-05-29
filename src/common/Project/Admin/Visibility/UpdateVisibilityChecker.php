<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Visibility;

use ForgeConfig;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;

final class UpdateVisibilityChecker
{
    public function __construct(
        private readonly EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function canUpdateVisibilityRegardingRestrictedUsers(Project $project, string $visibility): UpdateVisibilityStatus
    {
        if (
            ! ForgeConfig::areRestrictedUsersAllowed() ||
            $visibility !== Project::ACCESS_PRIVATE_WO_RESTRICTED
        ) {
            return UpdateVisibilityStatus::buildStatusSwitchIsAllowed();
        }

        if (! $this->atLeastOneAdministratorIsActive($project)) {
            return UpdateVisibilityStatus::buildStatusSwitchIsNotAllowed(
                _("Cannot switch the project visibility because it will remove every restricted users from the project, and after that no administrator will be left."),
            );
        }

        $event = $this->event_dispatcher->dispatch(
            new UpdateVisibilityIsAllowedEvent($project)
        );

        return $event->getUpdateVisibilityStatus();
    }

    private function atLeastOneAdministratorIsActive(Project $project): bool
    {
        foreach ($project->getAdmins() as $project_admin) {
            if ($project_admin->isActive()) {
                return true;
            }
        }

        return false;
    }
}
