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

namespace Tuleap\AgileDashboard\BreadCrumbDropdown;

use Planning_Milestone;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbItem;

class MilestoneCrumbBuilder
{
    /** @var string */
    private $plugin_path;

    public function __construct($plugin_path)
    {
        $this->plugin_path = $plugin_path;
    }

    /**
     * @param Planning_Milestone $milestone
     * @return BreadCrumbItem
     */
    public function build(Planning_Milestone $milestone)
    {
        $url = $this->plugin_path . '/?' . http_build_query(
            [
                'planning_id' => $milestone->getPlanningId(),
                'pane'        => 'planning-v2',
                'action'      => 'show',
                'group_id'    => $milestone->getGroupId(),
                'aid'         => $milestone->getArtifactId()
            ]
        );

        return new BreadCrumbItem(
            $milestone->getArtifactTitle(),
            $url
        );
    }
}
