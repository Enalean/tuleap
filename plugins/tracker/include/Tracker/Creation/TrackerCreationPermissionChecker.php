<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

use PFUser;
use Project;
use trackerPlugin;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;

class TrackerCreationPermissionChecker
{
    /**
     * @var GlobalAdminPermissionsChecker
     */
    private $permissions_checker;

    public function __construct(GlobalAdminPermissionsChecker $permissions_checker)
    {
        $this->permissions_checker = $permissions_checker;
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function checkANewTrackerCanBeCreated(Project $project, PFUser $user): void
    {
        if (! $project->usesService(trackerPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext('tuleap-tracker', 'Tracker service is disabled.'));
        }

        if (! $this->permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user)) {
            throw new ForbiddenException();
        }
    }
}
