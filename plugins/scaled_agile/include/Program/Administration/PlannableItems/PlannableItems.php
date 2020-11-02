<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ScaledAgile\Program\Administration\PlannableItems;

use Tuleap\ScaledAgile\ProjectData;
use Tuleap\ScaledAgile\TrackerData;

/**
 * @psalm-immutable
 */
class PlannableItems
{
    /**
     * @var ProjectData
     */
    private $project_data;

    /**
     * @var TrackerData[]
     */
    private $trackers;

    public function __construct(ProjectData $project_data, array $trackers)
    {
        $this->project_data = $project_data;
        $this->trackers     = $trackers;
    }

    public function getProjectData(): ProjectData
    {
        return $this->project_data;
    }

    /**
     * @return TrackerData[]
     */
    public function getTrackersData(): array
    {
        return $this->trackers;
    }
}
