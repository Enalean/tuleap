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
use Tuleap\Event\Dispatchable;

class PermissionPerGroupPaneCollector implements Dispatchable
{
    public const NAME = 'permissionPerGroupPaneCollector';

    /**
     * @var int|false
     */
    private $selected_ugroup_id;

    /**
     * @var string[]
     */
    private $panes = array();
    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project, $selected_ugroup_id)
    {
        $this->project            = $project;
        $this->selected_ugroup_id = $selected_ugroup_id;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return string[]
     */
    public function getPanes()
    {
        return $this->panes;
    }

    /**
     * @param string $additional_pane
     */
    public function addPane($additional_pane, $service_rank)
    {
        $this->panes[$service_rank] = $additional_pane;
    }

    /**
     * @return false|int
     */
    public function getSelectedUGroupId()
    {
        return $this->selected_ugroup_id;
    }
}
