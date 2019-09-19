<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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


class AgileDashboard_Milestone_PaginatedMilestonesRepresentations
{

    /** @var array */
    public $milestones_representations;

    /** @var int */
    public $total_size;


    public function __construct(array $milestones_representations, $total_size)
    {
        $this->milestones_representations = $milestones_representations;
        $this->total_size                 = $total_size;
    }

    public function getMilestonesRepresentations()
    {
        return $this->milestones_representations;
    }

    public function getTotalSize()
    {
        return $this->total_size;
    }
}
