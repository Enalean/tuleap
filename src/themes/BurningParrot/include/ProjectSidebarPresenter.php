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

use PFUser;
use Project;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Project\Banner\BannerDisplay;
use Tuleap\Project\ProjectPrivacyPresenter;

class ProjectSidebarPresenter
{
    public $sidebar;
    public $project_link;
    public $project_name;
    public $is_sidebar_collapsable;
    public $project_id;
    public $version;
    public $copyright;
    /**
     * @var bool
     */
    public $has_copyright;
    /**
     * @var ProjectPrivacyPresenter
     * @psalm-readonly
     */
    public $privacy;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_project_banner;
    /**
     * @var array
     * @psalm-readonly
     */
    public $project_flags;
    /**
     * @var int
     * @psalm-readonly
     */
    public $nb_project_flags;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $has_project_flags;

    public function __construct(
        PFUser $current_user,
        Project $project,
        \Generator $sidebar,
        ProjectPrivacyPresenter $privacy,
        VersionPresenter $version,
        ?BannerDisplay $banner,
        array $project_flags
    ) {
        $this->sidebar                = $sidebar;
        $this->is_sidebar_collapsable = $current_user->isLoggedIn();
        $this->project_link           = '/projects/' . $project->getUnixName() . '/';
        $this->project_name           = $project->getPublicName();
        $this->project_id             = $project->getID();

        $this->version       = $version;
        $this->has_copyright = $GLOBALS['Language']->hasText('global', 'copyright');
        $this->copyright     = '';

        if ($this->has_copyright) {
            $this->copyright = $GLOBALS['Language']->getOverridableText('global', 'copyright');
        }

        $this->has_project_banner = $banner !== null;
        $this->privacy            = $privacy;
        $this->project_flags      = $project_flags;
        $this->nb_project_flags   = \count($project_flags);
        $this->has_project_flags  = $this->nb_project_flags > 0;
    }
}
