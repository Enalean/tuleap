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
use Tuleap\TestManagement\REST\ExecutionChangesExtractor;
use Tuleap\Tracker\Artifact\Artifact;

class ExecutionFromAutomatedTestsUpdater
{
    /**
     * @var ExecutionStatusUpdater
     */
    private $execution_status_updater;

    /**
     * @var ExecutionChangesExtractor
     */
    private $execution_change_extractor;

    /**
     * @var TestsDataFromJunitExtractor
     */
    private $tests_data_extractor;

    /**
     * @var ListOfExecutionsWithAutomatedTestDataRetriever
     */
    private $list_of_executions_with_automated_test_data_retriever;

    public function __construct(
        ExecutionStatusUpdater $execution_status_updater,
        ExecutionChangesExtractor $execution_change_extractor,
        TestsDataFromJunitExtractor $tests_data_extractor,
        ListOfExecutionsWithAutomatedTestDataRetriever $list_of_executions_with_automated_test_data_retriever
    ) {
        $this->execution_status_updater                              = $execution_status_updater;
        $this->execution_change_extractor                            = $execution_change_extractor;
        $this->tests_data_extractor                                  = $tests_data_extractor;
        $this->list_of_executions_with_automated_test_data_retriever = $list_of_executions_with_automated_test_data_retriever;
    }

    /**
     * @throws AutomatedTestsNotXmlException
     */
    public function updateExecutionFromAutomatedTests(
        AutomatedTestsResultPATCHRepresentation $automated_tests_results,
        Artifact $campaign_artifact,
        PFUser $user
    ): void {
        if (! empty($automated_tests_results->junit_contents)) {
            $all_test_cases = $this->tests_data_extractor->getTestsResultsFromJunit(
                $automated_tests_results
            );

            if (empty($all_test_cases)) {
                return;
            }

            $executions_with_automated_test_data = $this->list_of_executions_with_automated_test_data_retriever
                ->getExecutionsWithAutomatedTestData($campaign_artifact, $user);

            $this->updateExecution($all_test_cases, $executions_with_automated_test_data, $user);
        }
    }

    /**
     *
     * @param ExtractedTestResultFromJunit[]                  $all_test_cases
     * @param array<string, ExecutionWithAutomatedTestData> $executions_with_automated_test_data
     * @throws \Luracast\Restler\RestException
     */
    private function updateExecution(
        array $all_test_cases,
        array $executions_with_automated_test_data,
        PFUser $user
    ): void {
        foreach ($executions_with_automated_test_data as $execution_with_automated_test_data) {
            $automated_test = $execution_with_automated_test_data->getAutomatedTest();
            if ($automated_test === '' || ! isset($all_test_cases[$automated_test])) {
                continue;
            }

            $test_case = $all_test_cases[$automated_test];

            $execution_artifact = $execution_with_automated_test_data->getExecution();

            $this->execution_status_updater->update(
                $execution_artifact,
                $this->execution_change_extractor->getChanges(
                    $test_case->getStatus(),
                    [],
                    $test_case->getTime(),
                    $test_case->getResult(),
                    $execution_artifact,
                    $user
                ),
                $user
            );
        }
    }
}
