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
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;

class TrackerChecker
{
    /**
     * @var array
     */
    private $project_trackers = [];

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var FrozenFieldsDao
     */
    private $frozen_fields_dao;

    /**
     * @var HiddenFieldsetsDao
     */
    private $hidden_fieldsets_dao;

    public function __construct(
        TrackerFactory $tracker_factory,
        FrozenFieldsDao $frozen_fields_dao,
        HiddenFieldsetsDao $hidden_fieldsets_dao
    ) {
        $this->tracker_factory      = $tracker_factory;
        $this->frozen_fields_dao    = $frozen_fields_dao;
        $this->hidden_fieldsets_dao = $hidden_fieldsets_dao;
    }

    /**
     * @throws TrackerNotInProjectException
     * @throws TrackerDoesntExistException
     * @throws TrackerIsDeletedException
     */
    public function checkTrackerIsInProject(Project $project, int $submitted_id): void
    {
        $this->initTrackerIds($project);

        $tracker = $this->tracker_factory->getTrackerById($submitted_id);
        if (! $tracker) {
            throw new TrackerDoesntExistException();
        }
        if ($tracker->isDeleted()) {
            throw new TrackerIsDeletedException();
        }
        if (! array_key_exists($submitted_id, $this->project_trackers[$project->getID()])) {
            throw new TrackerNotInProjectException();
        }
    }

    /**
     * @throws TrackerNotInProjectException
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerHasAtLeastOneHiddenFieldsetsPostActionException
     */
    public function checkSubmittedTrackerCanBeUsed(Project $project, int $submitted_id): void
    {
        $this->checkTrackerIsInProject($project, $submitted_id);

        if ($this->frozen_fields_dao->isAFrozenFieldPostActionUsedInTracker($submitted_id)) {
            throw new TrackerHasAtLeastOneFrozenFieldsPostActionException();
        }

        if ($this->hidden_fieldsets_dao->isAHiddenFieldsetPostActionUsedInTracker($submitted_id)) {
            throw new TrackerHasAtLeastOneHiddenFieldsetsPostActionException();
        }
    }

    private function initTrackerIds(Project $project): void
    {
        $project_id = $project->getID();

        if (! array_key_exists($project_id, $this->project_trackers)) {
            $this->project_trackers[$project_id] = $this->tracker_factory->getTrackersByGroupId($project_id);
        }
    }
}
