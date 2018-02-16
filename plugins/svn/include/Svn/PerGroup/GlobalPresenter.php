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

namespace Tuleap\Svn\PerGroup;

use Project;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupPanePresenter;

class GlobalPresenter
{
    /**
     * @var PermissionPerGroupPanePresenter
     */
    public $service_presenter;
    /**
     * @var PermissionPerGroupPanePresenter
     */
    public $repository_presenter;
    public $url;

    public function __construct(
        PermissionPerGroupPanePresenter $service_presenter,
        PermissionPerGroupPanePresenter $repository_presenter,
        Project $project
    ) {
        $this->service_presenter    = $service_presenter;
        $this->repository_presenter = $repository_presenter;
        $this->url                  = $this->getGlobalAdminLink($project);
    }

    private function getGlobalAdminLink(Project $project)
    {
        return SVN_BASE_URL . "?" . http_build_query(
            [
                "group_id" => $project->getID(),
                "action"   => "admin-groups"
            ]
        );
    }
}
