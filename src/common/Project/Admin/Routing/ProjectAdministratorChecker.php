<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Routing;

use PFUser;
use Project;
use Tuleap\Request\ForbiddenException;

class ProjectAdministratorChecker
{
    /**
     * @throws ForbiddenException
     */
    public function checkUserIsProjectAdministrator(PFUser $user, Project $project): void
    {
        if ($project->getStatus() === Project::STATUS_CREATING_FROM_ARCHIVE || ! $user->isAdmin($project->getID())) {
            throw new ForbiddenException(
                gettext("You don't have permission to access administration of this project.")
            );
        }
    }
}
