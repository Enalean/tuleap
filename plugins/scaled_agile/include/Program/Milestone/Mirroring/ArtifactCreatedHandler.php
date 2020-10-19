<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Milestone\Mirroring;

use Tuleap\ScaledAgile\Program\Milestone\Mirroring\Asynchronous\CreateMirrorsRunner;
use Tuleap\ScaledAgile\Program\Milestone\Mirroring\Asynchronous\PendingArtifactCreationDao;
use Tuleap\ScaledAgile\Program\ProgramDao;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;

class ArtifactCreatedHandler
{
    /**
     * @var ProgramDao
     */
    private $program_dao;
    /**
     * @var \PlanningFactory
     */
    private $planning_factory;
    /**
     * @var CreateMirrorsRunner
     */
    private $mirrors_runner;
    /**
     * @var PendingArtifactCreationDao
     */
    private $pending_artifact_creation_dao;

    public function __construct(
        ProgramDao $program_dao,
        \PlanningFactory $planning_factory,
        CreateMirrorsRunner $mirrors_runner,
        PendingArtifactCreationDao $pending_artifact_creation_dao
    ) {
        $this->program_dao                   = $program_dao;
        $this->planning_factory              = $planning_factory;
        $this->mirrors_runner                = $mirrors_runner;
        $this->pending_artifact_creation_dao = $pending_artifact_creation_dao;
    }

    public function handle(ArtifactCreated $event): void
    {
        $artifact     = $event->getArtifact();
        $tracker      = $artifact->getTracker();
        $project      = $tracker->getProject();
        $current_user = $event->getUser();

        if (! $this->program_dao->isProjectAnProgramProject((int) $project->getID())) {
            return;
        }

        try {
            $root_planning = $this->planning_factory->getVirtualTopPlanning($current_user, (int) $project->getID());
        } catch (\Planning_NoPlanningsException $e) {
            // Do nothing if there is no planning
            return;
        }

        $program_top_milestones_tracker_id = $root_planning->getPlanningTrackerId();
        if ($tracker->getId() !== $program_top_milestones_tracker_id) {
            return;
        }

        $this->pending_artifact_creation_dao->addArtifactToPendingCreation(
            (int) $event->getArtifact()->getId(),
            (int) $event->getUser()->getId(),
            (int) $event->getChangeset()->getId()
        );

        $this->mirrors_runner->executeMirrorsCreation($artifact, $current_user, $event->getChangeset());
    }
}
