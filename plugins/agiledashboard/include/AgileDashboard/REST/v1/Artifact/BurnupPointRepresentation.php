<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\v1\Artifact;

use Tuleap\AgileDashboard\FormElement\BurnupEffort;
use Tuleap\REST\JsonCast;

class BurnupPointRepresentation
{
    /**
     * @var string {@type date}
     */
    public $date;
    /**
     * @var float
     */
    public $team_effort;
    /**
     * @var float
     */
    public $total_effort;


    public function __construct(BurnupEffort $burnup_effort, $timestamp)
    {
        $this->date         = JsonCast::toDate($timestamp);
        $this->team_effort  = JsonCast::toFloat($burnup_effort->getTeamEffort());
        $this->total_effort = JsonCast::toFloat($burnup_effort->getTotalEffort());
    }
}
