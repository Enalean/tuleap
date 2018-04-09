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

namespace Tuleap\AgileDashboard\Kanban;

use AgileDashboard_Kanban;
use Project;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbItem;

class BreadCrumbBuilder
{
    /**
     * @var string
     */
    private $plugin_path;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var AgileDashboard_Kanban
     */
    private $kanban;

    /**
     * BreadCrumb constructor.
     *
     * @param string $plugin_path
     * @param Project $project
     * @param AgileDashboard_Kanban $kanban
     */
    public function __construct($plugin_path, Project $project, AgileDashboard_Kanban $kanban)
    {
        $this->plugin_path = $plugin_path;
        $this->project     = $project;
        $this->kanban      = $kanban;
    }

    /**
     * @return BreadCrumbItem
     */
    public function build()
    {
        $url = $this->plugin_path . '/?' .
            http_build_query(
                [
                    'group_id' => $this->project->getID(),
                    'action'   => 'showKanban',
                    'id'       => $this->kanban->getId()
                ]
            );

        return new BreadCrumbItem(
            $this->kanban->getName(),
            $url
        );
    }
}
