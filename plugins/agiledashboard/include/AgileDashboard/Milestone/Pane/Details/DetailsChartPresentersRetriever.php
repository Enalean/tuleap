<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Pane\Details;

use PFUser;
use Planning_Milestone;
use Tuleap\Event\Dispatchable;

class DetailsChartPresentersRetriever implements Dispatchable
{
    public const string NAME = 'detailsChartPresentersRetriever';
    /**
     * @var Planning_Milestone
     */
    private $milestone;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var array
     */
    private $escaped_charts = [];

    public function __construct(Planning_Milestone $milestone, PFUser $user)
    {
        $this->milestone = $milestone;
        $this->user      = $user;
    }

    public function getMilestone(): Planning_Milestone
    {
        return $this->milestone;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    public function addEscapedChart(string $escaped_chart): void
    {
        $this->escaped_charts[] = $escaped_chart;
    }

    /**
     * @return array
     */
    public function getEscapedCharts()
    {
        return $this->escaped_charts;
    }
}
