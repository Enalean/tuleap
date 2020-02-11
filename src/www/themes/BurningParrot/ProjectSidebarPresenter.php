<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot;

use ForgeConfig;
use PFUser;
use Project;
use Codendi_HTMLPurifier;
use Tuleap\BuildVersion\VersionPresenter;

class ProjectSidebarPresenter
{
    public $sidebar;
    public $project_link;
    public $project_is_public;
    public $project_name;
    public $project_privacy;
    public $is_sidebar_collapsable;
    public $project_id;
    public $version;
    public $copyright;
    /**
     * @var bool
     */
    public $has_copyright;
    /**
     * @var bool
     */
    public $are_restricted_users_allowed;
    /**
     * @var bool
     */
    public $project_is_public_incl_restricted;
    /**
     * @var bool
     */
    public $project_is_private;
    /**
     * @var bool
     */
    public $project_is_private_incl_restricted;

    public function __construct(PFUser $current_user, Project $project, \Generator $sidebar, $project_privacy, VersionPresenter $version)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $this->project_privacy        = $purifier->purify($project_privacy, CODENDI_PURIFIER_STRIP_HTML);
        $this->sidebar                = $sidebar;
        $this->is_sidebar_collapsable = $current_user->isLoggedIn();
        $this->project_link           = '/projects/' . $project->getUnixName() . '/';
        $this->project_is_public      = $project->isPublic();
        $this->project_name           = $project->getPublicName();
        $this->project_id             = $project->getID();

        $this->version       = $version;
        $this->has_copyright = $GLOBALS['Language']->hasText('global', 'copyright');
        $this->copyright     = '';

        if ($this->has_copyright) {
            $this->copyright = $GLOBALS['Language']->getOverridableText('global', 'copyright');
        }

        $this->are_restricted_users_allowed = ForgeConfig::areRestrictedUsersAllowed();
        if ($this->are_restricted_users_allowed) {
            $this->project_is_public                  = $project->getAccess() === Project::ACCESS_PUBLIC;
            $this->project_is_public_incl_restricted  = $project->getAccess() === Project::ACCESS_PUBLIC_UNRESTRICTED;
            $this->project_is_private                 = $project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED;
            $this->project_is_private_incl_restricted = $project->getAccess() === Project::ACCESS_PRIVATE;
        }
    }
}
