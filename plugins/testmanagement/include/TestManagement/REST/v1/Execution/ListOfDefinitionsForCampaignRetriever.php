<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1\Execution;

use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\Tracker\Artifact\Artifact;

class ListOfDefinitionsForCampaignRetriever
{
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

    public function __construct(
        ArtifactDao $artifact_dao,
        Tracker_ArtifactFactory $artifact_factory,
        DefinitionForExecutionRetriever $definition_retriever
    ) {
        $this->artifact_dao         = $artifact_dao;
        $this->artifact_factory     = $artifact_factory;
        $this->definition_retriever = $definition_retriever;
    }

    /**
     * @return Artifact[]
     */
    public function getDefinitionListForCampaign(PFUser $user, Artifact $campaign_artifact, int $execution_tracker_id): array
    {
        $rows = $this->artifact_dao->searchExecutionArtifactsForCampaign(
            (int) $campaign_artifact->getId(),
            $execution_tracker_id
        );

        $definitions = [];
        foreach ($rows as $row) {
            $execution = $this->artifact_factory->getInstanceFromRow($row);
            try {
                $definition = $this->definition_retriever->getDefinitionRepresentationForExecution(
                    $user,
                    $execution
                );

                $definitions[$definition->getId()] = $definition;
            } catch (DefinitionNotFoundException $exception) {
                continue;
            }
        }

        return $definitions;
    }
}
