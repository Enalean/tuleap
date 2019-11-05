<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types = 1);

namespace Tuleap\Project\Registration;

use HTTPRequest;
use Tuleap\Request\ForbiddenException;

class ProjectRegistrationUserPermissionChecker
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;

    public function __construct(\ProjectManager $project_manager)
    {
        $this->project_manager = $project_manager;
    }

    /**
     * @throws ForbiddenException
     */
    public function checkUserCreateAProject(HTTPRequest $request): void
    {
        $user = $request->getCurrentUser();

        if (! \ForgeConfig::get('sys_use_project_registration') && ! $user->isSuperUser()) {
            throw new ForbiddenException();
        }

        if ($user->isAnonymous()) {
            throw new ForbiddenException();
        }

        if (! $this->project_manager->userCanCreateProject($user)) {
            throw new ForbiddenException();
        }
    }
}
