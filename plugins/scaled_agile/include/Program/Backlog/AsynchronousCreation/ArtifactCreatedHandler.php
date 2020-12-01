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

use Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ScaledAgile\Program\BuildPlanning;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ScaledAgile\Program\ProgramStore;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;

class ArtifactCreatedHandler
{
    /**
     * @var ProgramStore
     */
    private $program_store;
    /**
     * @var RunProgramIncrementCreation
     */
    private $run_program_increment_creation;
    /**
     * @var PendingArtifactCreationStore
     */
    private $pending_artifact_creation_store;
    /**
     * @var BuildPlanning
     */
    private $planning_adapter;

    public function __construct(
        ProgramStore $program_store,
        RunProgramIncrementCreation $run_program_increment_creation,
        PendingArtifactCreationStore $pending_artifact_creation_store,
        BuildPlanning $planning_adapter
    ) {
        $this->program_store                   = $program_store;
        $this->run_program_increment_creation  = $run_program_increment_creation;
        $this->pending_artifact_creation_store = $pending_artifact_creation_store;
        $this->planning_adapter                 = $planning_adapter;
    }

    public function handle(ArtifactCreated $event): void
    {
        $source_artifact = $event->getArtifact();
        $source_tracker  = $source_artifact->getTracker();
        $source_project  = $source_tracker->getProject();
        $current_user    = $event->getUser();

        if (! $this->program_store->isProjectAProgramProject((int) $source_project->getID())) {
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

        $this->pending_artifact_creation_store->addArtifactToPendingCreation(
            (int) $event->getArtifact()->getId(),
            (int) $event->getUser()->getId(),
            (int) $event->getChangeset()->getId()
        );

        $replication_data = ReplicationDataAdapter::build($source_artifact, $current_user, $event->getChangeset());
        $this->run_program_increment_creation->executeProgramIncrementsCreation($replication_data);
    }
}
