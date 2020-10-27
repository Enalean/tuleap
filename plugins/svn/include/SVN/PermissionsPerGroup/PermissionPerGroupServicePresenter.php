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

namespace Tuleap\SVN\PermissionsPerGroup;

use Project;
use ProjectUGroup;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPanePresenter;
use Tuleap\SVN\Admin\GlobalAdministratorsController;

class PermissionPerGroupServicePresenter extends PermissionPerGroupPanePresenter
{
    /**
     * @var int
     */
    public $project_id;

    /**
     * @var string
     */
    public $url;

    public function __construct(
        array $permissions,
        Project $project,
        ?ProjectUGroup $selected_ugroup = null
    ) {
        parent::__construct($permissions, $selected_ugroup);

        $this->project_id = $project->getID();
        $this->url        = $this->getGlobalAdminLink($project);
    }

    private function getGlobalAdminLink(Project $project)
    {
        return GlobalAdministratorsController::getURL($project);
    }
}
