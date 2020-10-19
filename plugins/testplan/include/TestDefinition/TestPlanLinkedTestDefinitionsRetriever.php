<?php
/**
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

namespace Tuleap\TestPlan\TestDefinition;

use PFUser;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\Nature\NatureCoveredByPresenter;
use Tuleap\Tracker\Artifact\Artifact;

class TestPlanLinkedTestDefinitionsRetriever
{
    /**
     * @var Config
     */
    private $testmanagement_config;
    /**
     * @var ArtifactDao
     */
    private $artifact_dao;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var TestPlanTestDefinitionWithTestStatusRetriever
     */
    private $test_definition_with_test_status_retriever;

    public function __construct(
        Config $testmanagement_config,
        ArtifactDao $artifact_dao,
        Tracker_ArtifactFactory $artifact_factory,
        TestPlanTestDefinitionWithTestStatusRetriever $test_definition_with_test_status_retriever
    ) {
        $this->testmanagement_config                      = $testmanagement_config;
        $this->artifact_dao                               = $artifact_dao;
        $this->artifact_factory                           = $artifact_factory;
        $this->test_definition_with_test_status_retriever = $test_definition_with_test_status_retriever;
    }

    public function getDefinitionsLinkedToAnArtifact(Artifact $artifact, Artifact $milestone, PFUser $user, int $limit, int $offset): TestPlanLinkedTestDefinitions
    {
        $test_definition_tracker_id = $this->testmanagement_config->getTestDefinitionTrackerId($artifact->getTracker()->getProject());
        if ($test_definition_tracker_id === false) {
            return TestPlanLinkedTestDefinitions::empty();
        }

        $rows = $this->artifact_dao->searchPaginatedLinkedArtifactsByLinkNatureAndTrackerId(
            [$artifact->getId()],
            [NatureCoveredByPresenter::NATURE_COVERED_BY, Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD],
            $test_definition_tracker_id,
            $limit,
            $offset,
        );
        $total_number_of_linked_artifacts = $this->artifact_dao->foundRows();

        $test_definitions = [];
        foreach ($rows as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $test_definitions[] = $artifact;
            }
        }

        return TestPlanLinkedTestDefinitions::subset(
            $this->test_definition_with_test_status_retriever->retrieveTestDefinitionWithTestStatus($milestone, $user, $test_definitions),
            $total_number_of_linked_artifacts
        );
    }
}
