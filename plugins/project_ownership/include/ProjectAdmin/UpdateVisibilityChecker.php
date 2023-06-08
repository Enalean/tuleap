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

namespace Tuleap\ProjectOwnership\ProjectAdmin;

use Project;
use Tuleap\Project\Admin\Visibility\UpdateVisibilityStatus;
use Tuleap\ProjectOwnership\ProjectOwner\ProjectOwnerRetriever;

final class UpdateVisibilityChecker
{
    public function __construct(private readonly ProjectOwnerRetriever $project_owner_retriever)
    {
    }

    public function canUpdateVisibilityRegardingRestrictedUsers(Project $project): UpdateVisibilityStatus
    {
        $project_owner = $this->project_owner_retriever->getProjectOwner($project);
        if ($project_owner && $project_owner->isRestricted()) {
            return UpdateVisibilityStatus::buildStatusSwitchIsNotAllowed(
                dgettext('tuleap-project_ownership', 'Cannot switch the project visibility because it will remove the project owner which is restricted.')
            );
        }

        return UpdateVisibilityStatus::buildStatusSwitchIsAllowed();
    }
}
