<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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


class AgileDashboard_Milestone_PaginatedMilestones
{

    /** @var Planning_Milestone[] */
    private $milestones;

    /** @var int */
    private $total_size;


    public function __construct(array $milestones, $total_size)
    {
        $this->milestones = $milestones;
        $this->total_size = $total_size;
    }

    public function getMilestones()
    {
        return $this->milestones;
    }

    public function getTotalSize()
    {
        return $this->total_size;
    }
}
