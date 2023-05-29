<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

use Project;
use Psr\Log\LoggerInterface;

class ProjectOwnerRemover
{
    public function __construct(
        private readonly ProjectOwnerDAO $dao,
        private readonly ProjectOwnerRetriever $project_owner_retriever,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function forceRemovalOfRestrictedProjectOwner(\Project $project, \PFUser $removed_user): void
    {
        if ($project->getAccess() !== Project::ACCESS_PRIVATE_WO_RESTRICTED) {
            return;
        }

        if (! $removed_user->isRestricted()) {
            return;
        }

        $project_owner = $this->project_owner_retriever->getProjectOwner($project);
        if ($project_owner === null) {
            return;
        }

        if ($project_owner->getId() === $removed_user->getId()) {
            $this->dao->delete(
                (int) $project->getID(),
                (int) $removed_user->getId(),
            );

            $this->logger->info(
                "Project owner #" . $removed_user->getId() . " in project # " . $project->getID() . " removed by system event at project visibility switch."
            );
        }
    }
}
