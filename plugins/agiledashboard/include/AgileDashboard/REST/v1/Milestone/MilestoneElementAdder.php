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
use Project;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\REST\v1\ResourcesPatcher;
use Tuleap\DB\DBTransactionExecutorWithConnection;

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
     * @var DBTransactionExecutorWithConnection
     */
    private $db_transaction_executor;

    public function __construct(
        ExplicitBacklogDao $explicit_backlog_dao,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        ResourcesPatcher $resources_patcher,
        DBTransactionExecutorWithConnection $db_transaction_executor
    ) {
        $this->explicit_backlog_dao              = $explicit_backlog_dao;
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
        $this->resources_patcher                 = $resources_patcher;
        $this->db_transaction_executor           = $db_transaction_executor;
    }


    /**
     * @throws RestException
     * @throws \Throwable
     */
    public function addElementToBacklog(Project $project, array $add, \PFUser $user): void
    {
        $project_id = (int) $project->getGroupId();
        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            $this->addElementInExplicitBacklog($add, $project_id);
        } else {
            $this->moveArtifactsForStandardBacklog($add, $user);
        }
    }

    /**
     * @throws \Throwable
     */
    private function addElementInExplicitBacklog(array $add, int $project_id): void
    {
        $this->db_transaction_executor->execute(
            function () use ($add, $project_id) {
                foreach ($add as $added_artifact) {
                    $this->artifacts_in_explicit_backlog_dao->addArtifactToProjectBacklog(
                        $project_id,
                        (int) $added_artifact['id']
                    );
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
