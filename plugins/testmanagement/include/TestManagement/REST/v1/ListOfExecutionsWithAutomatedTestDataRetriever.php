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

namespace Tuleap\TestManagement\REST\v1;

use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Artifact\Artifact;

class ListOfExecutionsWithAutomatedTestDataRetriever
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ArtifactDao
     */
    private $artifact_dao;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var DefinitionForExecutionRetriever
     */
    private $definition_retriever;

    /**
     * @var ExecutionWithAutomatedTestDataProvider
     */
    private $execution_with_automated_data_provider;

    public function __construct(
        Config $config,
        ArtifactDao $artifact_dao,
        DefinitionForExecutionRetriever $definition_retriever,
        ExecutionWithAutomatedTestDataProvider $execution_with_automated_data_provider,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->config                                 = $config;
        $this->artifact_dao                           = $artifact_dao;
        $this->artifact_factory                       = $artifact_factory;
        $this->definition_retriever                   = $definition_retriever;
        $this->execution_with_automated_data_provider = $execution_with_automated_data_provider;
    }

    /**
     * @return ExecutionWithAutomatedTestData[]
     */
    public function getExecutionsWithAutomatedTestData(Artifact $campaign_artifact, PFUser $user): array
    {
        $execution_tracker_id = $this->config->getTestExecutionTrackerId($campaign_artifact->getTracker()->getProject());

        if (! $execution_tracker_id) {
            return [];
        }

        return $this->getExecutionWithAutomatedTest($campaign_artifact, $user, $execution_tracker_id);
    }

    /**
     * @return ExecutionWithAutomatedTestData[]
     */
    private function getExecutionWithAutomatedTest(
        Artifact $campaign_artifact,
        PFUser $user,
        int $execution_tracker_id
    ): array {
        $list_of_executions_with_automated_test = [];

        $rows = $this->artifact_dao->searchExecutionArtifactsForCampaign(
            (int) $campaign_artifact->getId(),
            $execution_tracker_id
        );

        foreach ($rows as $row) {
            $execution = $this->artifact_factory->getInstanceFromRow($row);
            try {
                $definition = $this->definition_retriever->getDefinitionRepresentationForExecution(
                    $user,
                    $execution
                );
            } catch (DefinitionNotFoundException $exception) {
                continue;
            }

            $executions_with_automated_test = $this->execution_with_automated_data_provider->getExecutionWithAutomatedTestData(
                $execution,
                $definition,
                $user
            );

            if ($executions_with_automated_test) {
                $list_of_executions_with_automated_test[] = $executions_with_automated_test;
            }
        }

        return $list_of_executions_with_automated_test;
    }
}
