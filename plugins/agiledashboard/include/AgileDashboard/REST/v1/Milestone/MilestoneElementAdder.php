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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use Luracast\Restler\RestException;
use PFUser;
use Project;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactAlreadyPlannedException;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\AgileDashboard\Milestone\Backlog\NoRootPlanningException;
use Tuleap\AgileDashboard\Milestone\Backlog\ProvidedAddedIdIsNotInPartOfTopBacklogException;
use Tuleap\AgileDashboard\Milestone\Backlog\TopBacklogElementsToAddChecker;
use Tuleap\AgileDashboard\REST\v1\ResourcesPatcher;
use Tuleap\DB\DBTransactionExecutor;

class MilestoneElementAdder
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var UnplannedArtifactsAdder
     */
    private $unplanned_artifacts_adder;

    /**
     * @var ResourcesPatcher
     */
    private $resources_patcher;

    /**
     * @var DBTransactionExecutor
     */
    private $db_transaction_executor;

    /**
     * @var TopBacklogElementsToAddChecker
     */
    private $top_backlog_elements_to_add_checker;

    public function __construct(
        ExplicitBacklogDao $explicit_backlog_dao,
        UnplannedArtifactsAdder $unplanned_artifacts_adder,
        ResourcesPatcher $resources_patcher,
        TopBacklogElementsToAddChecker $top_backlog_elements_to_add_checker,
        DBTransactionExecutor $db_transaction_executor
    ) {
        $this->explicit_backlog_dao                = $explicit_backlog_dao;
        $this->unplanned_artifacts_adder           = $unplanned_artifacts_adder;
        $this->resources_patcher                   = $resources_patcher;
        $this->db_transaction_executor             = $db_transaction_executor;
        $this->top_backlog_elements_to_add_checker = $top_backlog_elements_to_add_checker;
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
        $added_artifact_ids = [];
        foreach ($add as $added_artifact) {
            $added_artifact_ids[] = (int) $added_artifact->id;
        }

        $this->top_backlog_elements_to_add_checker->checkAddedIdsBelongToTheProjectTopBacklogTrackers(
            $project,
            $user,
            $added_artifact_ids
        );
    }

    /**
     * @throws \Throwable
     */
    private function addElementInExplicitBacklog(PFUser $user, array $add, int $project_id): void
    {
        $this->db_transaction_executor->execute(
            function () use ($user, $add, $project_id) {
                $this->resources_patcher->removeArtifactFromSource($user, $add);
                foreach ($add as $added_artifact) {
                    try {
                        $this->unplanned_artifacts_adder->addArtifactToTopBacklogFromIds(
                            (int) $added_artifact->id,
                            $project_id
                        );
                    } catch (ArtifactAlreadyPlannedException $exception) {
                        //Do nothing
                    }
                }
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
