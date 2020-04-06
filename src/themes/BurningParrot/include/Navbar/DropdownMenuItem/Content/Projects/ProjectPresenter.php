<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Projects;

use ForgeConfig;
use Project;

class ProjectPresenter
{
    /** @var string */
    public $project_name;

    /** @var string */
    public $project_uri;

    /** @var string */
    public $project_config_uri;

    /** @var string */
    public $user_administers;

    /** @var string */
    public $user_belongs;

    /** @var bool */
    public $is_private;
    /**
     * @var bool
     */
    public $is_public;
    /**
     * @var bool
     */
    public $is_public_incl_restricted;
    /**
     * @var bool
     */
    public $is_private_incl_restricted;

    public function __construct(
        $project_name,
        $project_uri,
        $project_config_uri,
        $user_administers,
        $user_belongs,
        Project $project
    ) {
        $this->project_name       = $project_name;
        $this->project_uri        = $project_uri;
        $this->project_config_uri = $project_config_uri;
        $this->is_private         = ! $project->isPublic();
        $this->user_administers   = $user_administers;
        $this->user_belongs       = $user_belongs;

        $are_restricted_users_allowed = ForgeConfig::areRestrictedUsersAllowed();
        if ($are_restricted_users_allowed) {
            $this->is_public                  = $project->getAccess() === Project::ACCESS_PUBLIC;
            $this->is_public_incl_restricted  = $project->getAccess() === Project::ACCESS_PUBLIC_UNRESTRICTED;
            $this->is_private                 = $project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED;
            $this->is_private_incl_restricted = $project->getAccess() === Project::ACCESS_PRIVATE;
        }
    }
}
