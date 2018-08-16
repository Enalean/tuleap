<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
use Codendi_HTMLPurifier;

class ProjectSidebarPresenter
{
    public $sidebar;
    public $project_link;
    public $project_is_public;
    public $project_name;
    public $project_privacy;
    public $is_sidebar_collapsable;
    public $project_id;
    public $powered_by;
    public $copyright;

    public function __construct(PFUser $current_user, Project $project, array $sidebar, $project_privacy)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $this->project_privacy        = $purifier->purify(
            $GLOBALS['Language']->getText('project_privacy', 'tooltip_' . $project_privacy),
            CODENDI_PURIFIER_STRIP_HTML
        );
        $this->sidebar                = $sidebar;
        $this->is_sidebar_collapsable = $current_user->isLoggedIn();
        $this->project_link           = '/projects/' . $project->getUnixName() . '/';
        $this->project_is_public      = $project->isPublic();
        $this->project_name           = $project->getUnconvertedPublicName();
        $this->project_id             = $project->getID();

        $this->powered_by = $GLOBALS['Language']->getText('global', 'powered_by') . ' ' . $this->getVersion();
        $this->copyright  = $GLOBALS['Language']->getText('global', 'copyright');
    }

    private function getVersion()
    {
        return trim(file_get_contents($GLOBALS['codendi_dir'] . '/VERSION'));
    }
}
