<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Git\PermissionsPerGroup;

use Project;
use ProjectUGroup;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPanePresenter;

class GitPanePresenter
{
    /**
     * @var PermissionPerGroupPanePresenter
     */
    public $service_presenter;
    /**
     * @var string
     */
    public $url;
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var string|int
     */
    public $ugroup_id;
    /**
     * @var string
     */
    public $selected_ugroup_name;

    public function __construct(
        PermissionPerGroupPanePresenter $service_presenter,
        Project $project,
        ?ProjectUGroup $ugroup = null
    ) {
        $this->service_presenter    = $service_presenter;
        $this->url                  = $this->getGlobalAdminLink($project);
        $this->project_id           = $project->getID();
        $this->ugroup_id            = ($ugroup) ? $ugroup->getId() : "";
        $this->selected_ugroup_name = ($ugroup) ? $ugroup->getTranslatedName() : "";
    }

    private function getGlobalAdminLink(Project $project)
    {
        return GIT_BASE_URL . "/?" . http_build_query(
            [
                "group_id" => $project->getID(),
                "action"   => "admin-git-admins"
            ]
        );
    }
}
