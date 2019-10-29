<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use Luracast\Restler\RestException;
use PFUser;
use PlanningFactory;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\REST\v1\ResourcesPatcher;
use Tuleap\DB\DBTransactionExecutor;

class MilestoneElementAdder
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;
    /**
     * @var ResourcesPatcher
     */
    private $resources_patcher;

    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        ExplicitBacklogDao $explicit_backlog_dao,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        ResourcesPatcher $resources_patcher,
        PlanningFactory $planning_factory,
        Tracker_ArtifactFactory $artifact_factory,
        DBTransactionExecutor $db_transaction_executor
    ) {
        $this->explicit_backlog_dao              = $explicit_backlog_dao;
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
        $this->resources_patcher                 = $resources_patcher;
        $this->db_transaction_executor           = $db_transaction_executor;
        $this->planning_factory                  = $planning_factory;
        $this->artifact_factory                  = $artifact_factory;
    }


    /**
     * @throws RestException
     * @throws ProvidedAddedIdIsNotInPartOfTopBacklogException
     * @throws NoRootPlanningException
     * @throws \Throwable
     */
    public function addElementToBacklog(Project $project, array $add, \PFUser $user): void
    {
        $project_id = (int) $project->getID();

        $this->checkAddedIdsBelongToTheProjectTopBacklogTrackers($project, $user, $add);

        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            $this->addElementInExplicitBacklog($user, $add, $project_id);
        } else {
            $this->moveArtifactsForStandardBacklog($add, $user);
        }
    }

    /**
     * @throws NoRootPlanningException
     * @throws ProvidedAddedIdIsNotInPartOfTopBacklogException
     */
    private function checkAddedIdsBelongToTheProjectTopBacklogTrackers(Project $project, PFUser $user, array $add)
    {
        $root_planning = $this->planning_factory->getRootPlanning($user, (int) $project->getID());
        if (! $root_planning) {
            throw new NoRootPlanningException();
        }

        $ids_in_error  = [];
        foreach ($add as $added_artifact) {
            $added_artifact_id = (int) $added_artifact->id;
            $artifact          = $this->artifact_factory->getArtifactById($added_artifact_id);

            if ($artifact !== null &&
                ! in_array($artifact->getTrackerId(), $root_planning->getBacklogTrackersIds())
            ) {
                $ids_in_error[] = $added_artifact_id;
            }
        }

        if (count($ids_in_error) > 0) {
            throw new ProvidedAddedIdIsNotInPartOfTopBacklogException($ids_in_error);
        }
    }

    /**
     * @throws \Throwable
     */
    private function addElementInExplicitBacklog(PFUser $user, array $add, int $project_id): void
    {
        $this->db_transaction_executor->execute(
            function () use ($user, $add, $project_id) {
                foreach ($add as $added_artifact) {
                    $this->artifacts_in_explicit_backlog_dao->addArtifactToProjectBacklog(
                        $project_id,
                        (int) $added_artifact->id
                    );
                }
                $this->resources_patcher->removeArtifactFromSource($user, $add);
            }
        );
    }

    /**
     * @throws RestException
     */
    private function moveArtifactsForStandardBacklog(array $add, \PFUser $user): void
    {
        try {
            $this->resources_patcher->removeArtifactFromSource($user, $add);
        } catch (\Exception $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }
}
