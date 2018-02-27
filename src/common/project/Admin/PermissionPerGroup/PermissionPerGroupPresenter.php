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

namespace Tuleap\Project\Admin\PermissionsPerGroup;

use Project;
use Tuleap\Project\Admin\ProjectUGroup\UGroupPresenter;

class PermissionPerGroupPresenter
{
    public $group_id;

    /**
     * @var UGroupPresenter[]
     */
    public $groups;
    /**
     * @var string[]
     */
    public $additional_panes;
    /**
     * @var bool
     */
    public $has_additional_panes;

    public function __construct(Project $project, array $groups, array $additional_panes)
    {
        $this->group_id             = $project->getID();
        $this->groups               = $groups;
        $this->additional_panes     = $additional_panes;
        $this->has_additional_panes = count($additional_panes) > 0;
    }
}
