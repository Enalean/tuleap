<?php
/**
 * Copyright (c) Enalean, 2020- Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\REST\v1\AutomatedTestsResultPATCHRepresentation;
use Tuleap\TestManagement\REST\v1\ExecutionFromAutomatedTestsUpdater;
use Tuleap\TestManagement\REST\v1\ExecutionStatusUpdater;
use Tuleap\TestManagement\REST\v1\ExecutionWithAutomatedTestData;
use Tuleap\TestManagement\REST\v1\ExtractedTestResultFromJunit;
use Tuleap\TestManagement\REST\v1\ListOfExecutionsWithAutomatedTestDataRetriever;
use Tuleap\TestManagement\REST\v1\TestsDataFromJunitExtractor;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ExecutionFromAutomatedTestsUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ExecutionStatusUpdater&MockObject $execution_status_updater;
    private ExecutionChangesExtractor&MockObject $execution_change_extractor;
    private ExecutionFromAutomatedTestsUpdater $execution_from_automated_test_updater;
    private Artifact $artifact;
    private \PFUser $user;
    private TestsDataFromJunitExtractor&MockObject $tests_data_extractor;
    private ListOfExecutionsWithAutomatedTestDataRetriever&MockObject $list_of_executions_with_automated_test_data_retriever;

    protected function setUp(): void
    {
        $this->artifact = ArtifactTestBuilder::anArtifact(102)->build();

        $this->user = UserTestBuilder::buildWithDefaults();

        $this->execution_status_updater                              = $this->createMock(ExecutionStatusUpdater::class);
        $this->execution_change_extractor                            = $this->createMock(ExecutionChangesExtractor::class);
        $this->tests_data_extractor                                  = $this->createMock(TestsDataFromJunitExtractor::class);
        $this->list_of_executions_with_automated_test_data_retriever = $this->createMock(
            ListOfExecutionsWithAutomatedTestDataRetriever::class
        );

        $this->execution_from_automated_test_updater = new ExecutionFromAutomatedTestsUpdater(
            $this->execution_status_updater,
            $this->execution_change_extractor,
            $this->tests_data_extractor,
            $this->list_of_executions_with_automated_test_data_retriever
        );
    }

    public function testUpdateExecutionFromAutomatedSuccessTestCase(): void
    {
        $extracted_test = $this->createMock(ExtractedTestResultFromJunit::class);
        $execution_1    = $this->createMock(Artifact::class);

        $extracted_test->method('getTime')->willReturn(5);
        $extracted_test->method('getStatus')->willReturn('passed');
        $extracted_test
            ->method('getResult')
            ->willReturn(
                "Executed 'firsttest' test case. <p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>"
            );

        $execution_with_automated_test = $this->createMock(ExecutionWithAutomatedTestData::class);
        $execution_with_automated_test->method('getAutomatedTest')->willReturn('firsttest');
        $execution_with_automated_test->method('getExecution')->willReturn($execution_1);

        $this->list_of_executions_with_automated_test_data_retriever->method(
            'getExecutionsWithAutomatedTestData'
        )->willReturn(
            [$execution_with_automated_test]
        );

        $automated_tests_results                 = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results->build_url      = 'http://exemple/of/url';
        $automated_tests_results->junit_contents = [
            '<testsuites>
                <testsuite>
                    <testcase name="firsttest" time="5.649"></testcase>
                </testsuite>
             </testsuites>',
        ];

        $this->tests_data_extractor
            ->method('getTestsResultsFromJunit')
            ->with($automated_tests_results)
            ->willReturn(['firsttest' => $extracted_test]);

        $this->execution_change_extractor->expects($this->once())->method('getChanges')->with(
            'passed',
            [],
            [],
            5,
            "Executed 'firsttest' test case. <p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>",
            $execution_1,
            $this->user,
        )->willReturn(['changed']);

        $this->execution_status_updater->method('update')->with(
            $execution_1,
            ['changed'],
            $this->user,
        );

        $this->execution_from_automated_test_updater->updateExecutionFromAutomatedTests(
            $automated_tests_results,
            $this->artifact,
            $this->user
        );
    }

    public function testUpdateExecutionFromAutomatedWithFailureTestCase(): void
    {
        $extracted_test_1 = $this->createMock(ExtractedTestResultFromJunit::class);
        $extracted_test_2 = $this->createMock(ExtractedTestResultFromJunit::class);
        $execution_1      = $this->createMock(Artifact::class);
        $execution_2      = $this->createMock(Artifact::class);

        $extracted_test_1->method('getTime')->willReturn(5);
        $extracted_test_1->method('getStatus')->willReturn('passed');
        $extracted_test_1
            ->method('getResult')
            ->willReturn(
                "Executed 'firsttest' test case. <p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>"
            );

        $extracted_test_2->method('getTime')->willReturn(9);
        $extracted_test_2->method('getStatus')->willReturn('failed');
        $extracted_test_2
            ->method('getResult')
            ->willReturn(
                "Executed 'failtest' test case. Got a failure:</br><p>this is a failure</p><p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>"
            );

        $execution_with_automated_test_1 = $this->createMock(ExecutionWithAutomatedTestData::class);
        $execution_with_automated_test_1->method('getAutomatedTest')->willReturn('firsttest');
        $execution_with_automated_test_1->method('getExecution')->willReturn($execution_1);

        $execution_with_automated_test_2 = $this->createMock(ExecutionWithAutomatedTestData::class);
        $execution_with_automated_test_2->method('getAutomatedTest')->willReturn('failtest');
        $execution_with_automated_test_2->method('getExecution')->willReturn($execution_2);

        $this->list_of_executions_with_automated_test_data_retriever->method(
            'getExecutionsWithAutomatedTestData'
        )->willReturn(
            [$execution_with_automated_test_1, $execution_with_automated_test_2]
        );

        $automated_tests_results                 = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results->build_url      = 'http://exemple/of/url';
        $automated_tests_results->junit_contents = [
            '<testsuites>
                <testsuite>
                    <testcase name="firsttest" time="5.649"></testcase>
                    <testcase name="failtest" time="9">
                        <failure>this is a failure</failure>
                    </testcase>
                </testsuite>
             </testsuites>',
        ];

        $this->tests_data_extractor
            ->method('getTestsResultsFromJunit')
            ->with($automated_tests_results)
            ->willReturn(['firsttest' => $extracted_test_1, 'failtest' => $extracted_test_2]);

        $this->execution_change_extractor
            ->expects($this->exactly(2))
            ->method('getChanges')
            ->willReturnCallback(
                fn (string $status,
                    array $uploaded_file_ids,
                    array $deleted_file_ids,
                    int $time,
                    string $results,
                    Artifact $artifact,
                    PFUser $user,
                ) => match (true) {
                    $status === 'passed'
                    && $uploaded_file_ids === []
                    && $deleted_file_ids === []
                    && $time === 5
                    && $results === "Executed 'firsttest' test case. <p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>"
                    && $artifact === $execution_1
                    && $user === $this->user => ['changed'],

                    $status === 'failed'
                    && $uploaded_file_ids === []
                    && $deleted_file_ids === []
                    && $time === 9
                    && $results === "Executed 'failtest' test case. Got a failure:</br><p>this is a failure</p><p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>"
                    && $artifact === $execution_2
                    && $user === $this->user => ['changed with failure'],
                }
            );

        $this->execution_status_updater
            ->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(fn (
                Artifact $execution_artifact,
                array $changes,
                PFUser $user,
            ) => match (true) {
                $execution_artifact === $execution_1 && $changes === ['changed'] && $user === $this->user,
                    $execution_artifact === $execution_2 && $changes === ['changed with failure'] && $user === $this->user
                => true
            });

        $this->execution_from_automated_test_updater->updateExecutionFromAutomatedTests(
            $automated_tests_results,
            $this->artifact,
            $this->user
        );
    }

    public function testUpdateExecutionFromAutomatedShouldNotUpdateIfNoMatchingAutomatedTests(): void
    {
        $extracted_test_1 = $this->createMock(ExtractedTestResultFromJunit::class);
        $extracted_test_2 = $this->createMock(ExtractedTestResultFromJunit::class);

        $execution_with_automated_test_1 = $this->createMock(ExecutionWithAutomatedTestData::class);
        $execution_with_automated_test_1->method('getAutomatedTest')->willReturn('notfirsttest');
        $execution_with_automated_test_1->expects($this->never())->method('getExecution');

        $execution_with_automated_test_2 = $this->createMock(ExecutionWithAutomatedTestData::class);
        $execution_with_automated_test_2->method('getAutomatedTest')->willReturn('notfailtest');
        $execution_with_automated_test_2->expects($this->never())->method('getExecution');

        $this->list_of_executions_with_automated_test_data_retriever->method(
            'getExecutionsWithAutomatedTestData'
        )->willReturn(
            [$execution_with_automated_test_1, $execution_with_automated_test_2]
        );

        $automated_tests_results                 = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results->build_url      = 'http://exemple/of/url';
        $automated_tests_results->junit_contents = [
            '<testsuites>
                <testsuite>
                    <testcase name="firsttest" time="5.649"></testcase>
                    <testcase name="failtest" time="9">
                        <failure>this is a failure</failure>
                    </testcase>
                </testsuite>
             </testsuites>',
        ];

        $this->tests_data_extractor
            ->method('getTestsResultsFromJunit')
            ->with($automated_tests_results)
            ->willReturn(['firsttest' => $extracted_test_1, 'failtest' => $extracted_test_2]);

        $this->execution_change_extractor->expects($this->never())->method('getChanges');

        $this->execution_status_updater->expects($this->never())->method('update');

        $this->execution_from_automated_test_updater->updateExecutionFromAutomatedTests(
            $automated_tests_results,
            $this->artifact,
            $this->user
        );
    }
}
