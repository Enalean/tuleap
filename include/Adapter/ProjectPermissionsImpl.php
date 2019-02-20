<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Baseline\Adapter;

use Luracast\Restler\RestException;
use Project;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\Baseline\ProjectPermissions;
use Tuleap\REST\ProjectStatusVerificator;

class ProjectPermissionsImpl implements ProjectPermissions
{
    /** @var ProjectStatusVerificator */
    private $project_status_verificator;

    public function __construct(ProjectStatusVerificator $project_status_verificator)
    {
        $this->project_status_verificator = $project_status_verificator;
    }

    /**
     * @throws NotAuthorizedException
     */
    function checkRead(Project $project)
    {
        try {
            $this->project_status_verificator->checkProjectStatusAllowsAllUsersToAccessIt($project);
        } catch (RestException $e) {
            throw new NotAuthorizedException(
                dgettext(
                    'tuleap-baseline',
                    'You cannot read this project'
                )
            );
        }
    }
}
