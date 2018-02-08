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

namespace Tuleap\Project\Admin\Permission;

use Project;
use Tuleap\Event\Dispatchable;

class PermissionPerGroupPaneCollector implements Dispatchable
{
    const NAME = 'permissionPerGroupPaneCollector';

    /**
     * @var string[]
     */
    private $additional_panes = array();
    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
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
    public function getAdditionalPanes()
    {
        return $this->additional_panes;
    }

    /**
     * @param string $additional_pane
     */
    public function addAdditionalPane($additional_pane)
    {
        $this->additional_panes[] = $additional_pane;
    }
}
