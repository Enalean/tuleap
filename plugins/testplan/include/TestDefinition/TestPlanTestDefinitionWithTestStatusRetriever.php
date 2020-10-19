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
use Tuleap\Tracker\Artifact\Artifact;
use UserManager;

class TestPlanTestDefinitionWithTestStatusRetriever
{
    /**
     * @var TestPlanTestDefinitionsTestStatusDAO
     */
    private $dao;
    /**
     * @var TestStatusPerTestDefinitionsInformationForUserRetriever
     */
    private $test_status_per_test_definitions_information_for_user_retriever;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        TestPlanTestDefinitionsTestStatusDAO $dao,
        TestStatusPerTestDefinitionsInformationForUserRetriever $test_status_per_test_definitions_information_for_user_retriever,
        UserManager $user_manager
    ) {
        $this->dao                                                             = $dao;
        $this->test_status_per_test_definitions_information_for_user_retriever = $test_status_per_test_definitions_information_for_user_retriever;
        $this->user_manager                                                    = $user_manager;
    }


    /**
     * @param Artifact[] $test_definitions
     *
     * @return TestPlanTestDefinitionWithTestStatus[]
     */
    public function retrieveTestDefinitionWithTestStatus(Artifact $milestone, PFUser $user, array $test_definitions): array
    {
        $information = $this->test_status_per_test_definitions_information_for_user_retriever->getInformationNeededToRetrieveTestStatusPerTestDefinition(
            $user,
            $milestone,
            $test_definitions,
        );

        if ($information === null) {
            $test_definitions_with_unknown_test_status = [];
            foreach ($test_definitions as $test_definition) {
                $test_definitions_with_unknown_test_status[] = TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition($test_definition);
            }
            return $test_definitions_with_unknown_test_status;
        }

        $rows = $this->dao->searchTestStatusPerTestDefinitionInAMilestone($information);

        $test_definitions_with_test_status         = [];
        $test_definitions_with_unknown_test_status = [];
        foreach ($test_definitions as $test_definition) {
            $test_definition_id = (int) $test_definition->getId();
            if (isset($rows[$test_definition_id])) {
                $test_definitions_with_test_status[] = TestPlanTestDefinitionWithTestStatus::knownTestStatusForTheDefinition(
                    $test_definition,
                    $rows[$test_definition_id]['test_status'],
                    $rows[$test_definition_id]['test_exec_id'],
                    $rows[$test_definition_id]['test_exec_submitted_on'],
                    $this->getSubmittedByUser($rows[$test_definition_id]['test_exec_submitted_by']),
                    $rows[$test_definition_id]['test_campaign_id'],
                );
            } else {
                $test_definitions_with_unknown_test_status[] = TestPlanTestDefinitionWithTestStatus::unknownTestStatusForTheDefinition(
                    $test_definition,
                );
            }
        }
        return array_merge($test_definitions_with_test_status, $test_definitions_with_unknown_test_status);
    }

    private function getSubmittedByUser(int $submitted_by_user_id): PFUser
    {
        $submitted_by = $this->user_manager->getUserById($submitted_by_user_id);
        if ($submitted_by !== null) {
            return $submitted_by;
        }

        return $this->user_manager->getUserAnonymous();
    }
}
