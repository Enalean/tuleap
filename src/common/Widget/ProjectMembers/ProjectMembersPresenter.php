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

namespace Tuleap\Widget\ProjectMembers;

use Project;

class ProjectMembersPresenter
{
    public $view_members_link;
    /** @var int */
    public $project_member_count;
    /**
     * @var AdministratorPresenter[]
     */
    public $administrators;

    /**
     *
     * @param AdministratorPresenter[] $administrators
     */
    public function __construct(Project $project, array $administrators)
    {
        $this->view_members_link    = '/project/memberlist.php?group_id=' . urlencode($project->getID());
        $this->project_member_count = count($project->getMembers());
        $this->administrators       = $administrators;
    }
}
