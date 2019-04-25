<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Administration;

use Project;
use TrackerFactory;

class TrackerChecker
{
    /**
     * @var array
     */
    private $project_trackers = [];

    /**
     * @var array
     */
    private $project_tracker_ids = [];

    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct(TrackerFactory $tracker_factory)
    {
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @throws TrackerNotInProjectException
     */
    public function checkTrackerIsInProject(Project $project, int $submitted_id) : void
    {
        $this->initTrackerIds($project);

        if (! in_array($submitted_id, $this->project_tracker_ids[$project->getID()])) {
            throw new TrackerNotInProjectException();
        }
    }

    private function initTrackerIds(Project $project) : void
    {
        $project_id = $project->getID();

        if (! array_key_exists($project_id, $this->project_trackers)) {
            $this->project_trackers[$project_id] = $this->tracker_factory->getTrackersByGroupId($project_id);
        }

        if (! array_key_exists($project_id, $this->project_tracker_ids)) {
            $this->project_tracker_ids[$project_id] = array_map(
                function ($tracker) {
                    return $tracker->getId();
                },
                $this->project_trackers[$project_id]
            );
        }
    }
}
