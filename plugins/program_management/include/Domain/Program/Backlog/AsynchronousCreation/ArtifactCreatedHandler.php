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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\PlanCheckException;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
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
     * @var BuildPlanProgramIncrementConfiguration
     */
    private $build_plan_configuration;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ProgramStore $program_store,
        RunProgramIncrementCreation $run_program_increment_creation,
        PendingArtifactCreationStore $pending_artifact_creation_store,
        BuildPlanProgramIncrementConfiguration $build_plan_configuration,
        LoggerInterface $logger
    ) {
        $this->program_store                   = $program_store;
        $this->run_program_increment_creation  = $run_program_increment_creation;
        $this->pending_artifact_creation_store = $pending_artifact_creation_store;
        $this->build_plan_configuration        = $build_plan_configuration;
        $this->logger                          = $logger;
    }


    /**
     * @throws PlanTrackerException
     * @throws ProgramTrackerNotFoundException
     * @throws PlanCheckException
     */
    public function handle(ArtifactCreated $event): void
    {
        $source_artifact = $event->getArtifact();
        $source_tracker  = $source_artifact->getTracker();
        $source_project  = $source_tracker->getProject();
        $current_user    = $event->getUser();

        if (! $this->program_store->isProjectAProgramProject((int) $source_project->getID())) {
            $this->logger->debug($source_project->getID() . " is not a program");
            return;
        }

        $this->logger->debug(
            sprintf(
                "Store program create with #%d by user #%d",
                $source_artifact->getId(),
                (int) $event->getUser()->getId()
            )
        );

        $program_increment = $this->build_plan_configuration->buildTrackerProgramIncrementFromProjectId((int) $source_project->getID(), $current_user);
        if ($source_tracker->getId() !== $program_increment->getTrackerId()) {
            return;
        }

        $this->pending_artifact_creation_store->addArtifactToPendingCreation(
            $event->getArtifact()->getId(),
            (int) $event->getUser()->getId(),
            (int) $event->getChangeset()->getId()
        );

        $replication_data = ReplicationDataAdapter::build($source_artifact, $current_user, $event->getChangeset());
        $this->run_program_increment_creation->executeProgramIncrementsCreation($replication_data);
    }
}
