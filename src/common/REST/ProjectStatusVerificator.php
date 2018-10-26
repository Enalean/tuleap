<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\REST;

use Luracast\Restler\RestException;
use Project;

class ProjectStatusVerificator
{
    public static function build()
    {
        return new self();
    }

    /**
     * @throws RestException
     */
    public function checkProjectStatusAllowsAllUsersToAccessIt(Project $project)
    {
        if ($project->isSuspended()) {
            $this->blockRestAccess();
        }
    }

    /**
     * @throws RestException
     */
    public function checkProjectStatusAllowsOnlySiteAdminToAccessIt(\PFUser $user, Project $project)
    {
        if ($project->isSuspended() && ! $user->isSuperUser()) {
            $this->blockRestAccess();
        }
    }

    /**
     * @throws RestException
     */
    private function blockRestAccess()
    {
        $status_suspended_label = Project::STATUS_SUSPENDED_LABEL;

        throw new RestException('403', "This project is $status_suspended_label");
    }
}
