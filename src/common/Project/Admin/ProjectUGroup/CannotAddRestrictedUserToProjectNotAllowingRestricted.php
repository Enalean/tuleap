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

namespace Tuleap\Project\Admin\ProjectUGroup;

use PFUser;
use Project;
use RuntimeException;

final class CannotAddRestrictedUserToProjectNotAllowingRestricted extends RuntimeException
{
    /**
     * @var Project
     */
    private $project;
    /**
     * @var PFUser
     */
    private $restricted_user;

    public function __construct(PFUser $restricted_user, Project $project)
    {
        parent::__construct(
            sprintf(
                'Cannot add the restricted user #%d to project #%d because it does not allow restricted user',
                $restricted_user->getId(),
                $project->getID()
            )
        );
        $this->project         = $project;
        $this->restricted_user = $restricted_user;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getRestrictedUser(): PFUser
    {
        return $this->restricted_user;
    }
}
