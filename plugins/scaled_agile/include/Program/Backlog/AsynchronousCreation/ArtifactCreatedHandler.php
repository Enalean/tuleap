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

namespace Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation;

use Tuleap\ScaledAgile\Adapter\Program\ReplicationDataAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;

class ArtifactCreatedHandler
{
    /**
     * @var ProgramDao
     */
    private $program_dao;
    /**
     * @var CreateProgramIncrementsRunner
     */
    private $create_program_increments_runner;
    /**
     * @var PendingArtifactCreationDao
     */
    private $pending_artifact_creation_dao;
    /**
     * @var PlanningAdapter
     */
    private $planning_adapter;

    public function __construct(
        ProgramDao $program_dao,
        CreateProgramIncrementsRunner $create_program_increments_runner,
        PendingArtifactCreationDao $pending_artifact_creation_dao,
        PlanningAdapter $planning_adapter
    ) {
        $this->program_dao                      = $program_dao;
        $this->create_program_increments_runner = $create_program_increments_runner;
        $this->pending_artifact_creation_dao    = $pending_artifact_creation_dao;
        $this->planning_adapter                 = $planning_adapter;
    }

    public function handle(ArtifactCreated $event): void
    {
        $source_artifact = $event->getArtifact();
        $source_tracker  = $source_artifact->getTracker();
        $source_project  = $source_tracker->getProject();
        $current_user    = $event->getUser();

        if (! $this->program_dao->isProjectAProgramProject((int) $source_project->getID())) {
            return;
        }

        try {
            $root_planning = $this->planning_adapter->buildRootPlanning($current_user, (int) $source_project->getID());
        } catch (TopPlanningNotFoundInProjectException $e) {
            // Do nothing if there is no planning
            return;
        }

        $program_top_milestones_tracker_id = $root_planning->getPlanningTrackerData()->getTrackerId();
        if ($source_tracker->getId() !== $program_top_milestones_tracker_id) {
            return;
        }

        $this->pending_artifact_creation_dao->addArtifactToPendingCreation(
            (int) $event->getArtifact()->getId(),
            (int) $event->getUser()->getId(),
            (int) $event->getChangeset()->getId()
        );

        $replication_data = ReplicationDataAdapter::build($source_artifact, $current_user, $event->getChangeset());
        $this->create_program_increments_runner->executeProgramIncrementsCreation($replication_data);
    }
}
