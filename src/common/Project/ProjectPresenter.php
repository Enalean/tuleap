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

namespace Tuleap\Project;

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

    /** @var bool */
    public $is_current_user_admin;

    /** @var bool */
    public $is_private;
    /**
     * @var bool
     */
    public $is_public;
    /**
     * @var bool
     */
    public $is_public_incl_restricted = false;
    /**
     * @var bool
     */
    public $is_private_incl_restricted = false;

    public function __construct(
        string $project_name,
        string $project_uri,
        string $project_config_uri,
        bool $is_current_user_admin,
        Project $project
    ) {
        $this->project_name          = $project_name;
        $this->project_uri           = $project_uri;
        $this->project_config_uri    = $project_config_uri;
        $this->is_private            = ! $project->isPublic();
        $this->is_public             = $project->isPublic();
        $this->is_current_user_admin = $is_current_user_admin;

        $are_restricted_users_allowed = ForgeConfig::areRestrictedUsersAllowed();
        if ($are_restricted_users_allowed) {
            $this->is_public                  = $project->getAccess() === Project::ACCESS_PUBLIC;
            $this->is_public_incl_restricted  = $project->getAccess() === Project::ACCESS_PUBLIC_UNRESTRICTED;
            $this->is_private                 = $project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED;
            $this->is_private_incl_restricted = $project->getAccess() === Project::ACCESS_PRIVATE;
        }
    }
}
