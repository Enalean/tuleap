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

use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use Tuleap\TestManagement\REST\v1\AutomatedTestsResultPATCHRepresentation;
use Tuleap\TestManagement\REST\v1\ExecutionFromAutomatedTestsUpdater;
use Tuleap\TestManagement\REST\v1\ExecutionStatusUpdater;
use Tuleap\TestManagement\REST\v1\ExecutionWithAutomatedTestData;
use Tuleap\TestManagement\REST\v1\ExtractedTestResultFromJunit;
use Tuleap\TestManagement\REST\v1\ListOfExecutionsWithAutomatedTestDataRetriever;
use Tuleap\TestManagement\REST\v1\TestsDataFromJunitExtractor;
use Tuleap\Tracker\Artifact\Artifact;

class ExecutionFromAutomatedTestsUpdaterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExecutionStatusUpdater
     */
    private $execution_status_updater;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExecutionChangesExtractor
     */
    private $execution_change_extractor;
    /**
     * @var ExecutionFromAutomatedTestsUpdater
     */
    private $execution_from_automated_test_updater;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TestsDataFromJunitExtractor
     */
    private $tests_data_extractor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ListOfExecutionsWithAutomatedTestDataRetriever
     */
    private $list_of_executions_with_automated_test_data_retriever;

    protected function setUp(): void
    {
        $project = Mockery::mock(Project::class);
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn($project);

        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(102);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->user = Mockery::mock(\PFUser::class);

        $this->execution_status_updater                      = Mockery::mock(ExecutionStatusUpdater::class);
        $this->execution_change_extractor                    = Mockery::mock(ExecutionChangesExtractor::class);
        $this->tests_data_extractor                          = Mockery::mock(TestsDataFromJunitExtractor::class);
        $this->list_of_executions_with_automated_test_data_retriever = Mockery::mock(
            ListOfExecutionsWithAutomatedTestDataRetriever::class
        );

        $this->artifact_dao =

        $this->execution_from_automated_test_updater = new ExecutionFromAutomatedTestsUpdater(
            $this->execution_status_updater,
            $this->execution_change_extractor,
            $this->tests_data_extractor,
            $this->list_of_executions_with_automated_test_data_retriever
        );
    }

    public function testUpdateExecutionFromAutomatedSuccessTestCase(): void
    {
        $extracted_test = Mockery::mock(ExtractedTestResultFromJunit::class);
        $execution_1    = Mockery::mock(Artifact::class);

        $extracted_test->shouldReceive('getTime')->andReturn(5);
        $extracted_test->shouldReceive('getStatus')->andReturn("passed");
        $extracted_test
            ->shouldReceive('getResult')
            ->andReturn(
                "Executed 'firsttest' test case. <p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>"
            );

        $execution_with_automated_test = Mockery::mock(ExecutionWithAutomatedTestData::class);
        $execution_with_automated_test->shouldReceive('getAutomatedTest')->andReturn('firsttest');
        $execution_with_automated_test->shouldReceive('getExecution')->andReturn($execution_1);

        $this->list_of_executions_with_automated_test_data_retriever->shouldReceive(
            'getExecutionsWithAutomatedTestData'
        )->andReturn(
            [$execution_with_automated_test]
        );

        $automated_tests_results                          = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results->build_url               = 'http://exemple/of/url';
        $automated_tests_results->junit_contents = [
            '<testsuites>
                <testsuite>
                    <testcase name="firsttest" time="5.649"></testcase>
                </testsuite>
             </testsuites>'
        ];

        $this->tests_data_extractor
            ->shouldReceive('getTestsResultsFromJunit')
            ->with($automated_tests_results)
            ->andReturn(['firsttest' => $extracted_test]);

        $this->execution_change_extractor->shouldReceive('getChanges')->withArgs(
            [
                'passed',
                [],
                5,
                "Executed 'firsttest' test case. <p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>",
                $execution_1,
                $this->user
            ]
        )->once()->andReturn(['changed']);

        $this->execution_status_updater->shouldReceive('update')->withArgs(
            [
                $execution_1,
                ['changed'],
                $this->user
            ]
        );

        $this->execution_from_automated_test_updater->updateExecutionFromAutomatedTests(
            $automated_tests_results,
            $this->artifact,
            $this->user
        );
    }

    public function testUpdateExecutionFromAutomatedWithFailureTestCase(): void
    {
        $extracted_test_1 = Mockery::mock(ExtractedTestResultFromJunit::class);
        $extracted_test_2 = Mockery::mock(ExtractedTestResultFromJunit::class);
        $execution_1      = Mockery::mock(Artifact::class);
        $execution_2      = Mockery::mock(Artifact::class);

        $extracted_test_1->shouldReceive('getTime')->andReturn(5);
        $extracted_test_1->shouldReceive('getStatus')->andReturn("passed");
        $extracted_test_1
            ->shouldReceive('getResult')
            ->andReturn(
                "Executed 'firsttest' test case. <p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>"
            );

        $extracted_test_2->shouldReceive('getTime')->andReturn(9);
        $extracted_test_2->shouldReceive('getStatus')->andReturn("failed");
        $extracted_test_2
            ->shouldReceive('getResult')
            ->andReturn(
                "Executed 'failtest' test case. Got a failure:</br><p>this is a failure</p><p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>"
            );

        $execution_with_automated_test_1 = Mockery::mock(ExecutionWithAutomatedTestData::class);
        $execution_with_automated_test_1->shouldReceive('getAutomatedTest')->andReturn('firsttest');
        $execution_with_automated_test_1->shouldReceive('getExecution')->andReturn($execution_1);

        $execution_with_automated_test_2 = Mockery::mock(ExecutionWithAutomatedTestData::class);
        $execution_with_automated_test_2->shouldReceive('getAutomatedTest')->andReturn('failtest');
        $execution_with_automated_test_2->shouldReceive('getExecution')->andReturn($execution_2);

        $this->list_of_executions_with_automated_test_data_retriever->shouldReceive(
            'getExecutionsWithAutomatedTestData'
        )->andReturn(
            [$execution_with_automated_test_1, $execution_with_automated_test_2]
        );

        $automated_tests_results                          = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results->build_url               = 'http://exemple/of/url';
        $automated_tests_results->junit_contents = [
            '<testsuites>
                <testsuite>
                    <testcase name="firsttest" time="5.649"></testcase>
                    <testcase name="failtest" time="9">
                        <failure>this is a failure</failure>
                    </testcase>
                </testsuite>
             </testsuites>'
        ];

        $this->tests_data_extractor
            ->shouldReceive('getTestsResultsFromJunit')
            ->with($automated_tests_results)
            ->andReturn(['firsttest' => $extracted_test_1, 'failtest' => $extracted_test_2]);

        $this->execution_change_extractor->shouldReceive('getChanges')->withArgs(
            [
                'passed',
                [],
                5,
                "Executed 'firsttest' test case. <p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>",
                $execution_1,
                $this->user
            ]
        )->once()->andReturn(['changed']);

        $this->execution_status_updater->shouldReceive('update')->withArgs(
            [
                $execution_1,
                ['changed'],
                $this->user
            ]
        );

        $this->execution_change_extractor->shouldReceive('getChanges')->withArgs(
            [
                'failed',
                [],
                9,
                "Executed 'failtest' test case. Got a failure:</br><p>this is a failure</p><p>Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>",
                $execution_2,
                $this->user
            ]
        )->once()->andReturn(['changed with failure']);

        $this->execution_status_updater->shouldReceive('update')->withArgs(
            [
                $execution_2,
                ['changed with failure'],
                $this->user
            ]
        );

        $this->execution_from_automated_test_updater->updateExecutionFromAutomatedTests(
            $automated_tests_results,
            $this->artifact,
            $this->user
        );
    }

    public function testUpdateExecutionFromAutomatedShouldNotUpdateIfNoMatchingAutomatedTests(): void
    {
        $extracted_test_1 = Mockery::mock(ExtractedTestResultFromJunit::class);
        $extracted_test_2 = Mockery::mock(ExtractedTestResultFromJunit::class);

        $execution_with_automated_test_1 = Mockery::mock(ExecutionWithAutomatedTestData::class);
        $execution_with_automated_test_1->shouldReceive('getAutomatedTest')->andReturn('notfirsttest');
        $execution_with_automated_test_1->shouldReceive('getExecution')->never();

        $execution_with_automated_test_2 = Mockery::mock(ExecutionWithAutomatedTestData::class);
        $execution_with_automated_test_2->shouldReceive('getAutomatedTest')->andReturn('notfailtest');
        $execution_with_automated_test_2->shouldReceive('getExecution')->never();

        $this->list_of_executions_with_automated_test_data_retriever->shouldReceive(
            'getExecutionsWithAutomatedTestData'
        )->andReturn(
            [$execution_with_automated_test_1, $execution_with_automated_test_2]
        );

        $automated_tests_results                          = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results->build_url               = 'http://exemple/of/url';
        $automated_tests_results->junit_contents = [
            '<testsuites>
                <testsuite>
                    <testcase name="firsttest" time="5.649"></testcase>
                    <testcase name="failtest" time="9">
                        <failure>this is a failure</failure>
                    </testcase>
                </testsuite>
             </testsuites>'
        ];

        $this->tests_data_extractor
            ->shouldReceive('getTestsResultsFromJunit')
            ->with($automated_tests_results)
            ->andReturn(['firsttest' => $extracted_test_1, 'failtest' => $extracted_test_2]);

        $this->execution_change_extractor->shouldReceive('getChanges')->never();

        $this->execution_status_updater->shouldReceive('update')->never();

        $this->execution_from_automated_test_updater->updateExecutionFromAutomatedTests(
            $automated_tests_results,
            $this->artifact,
            $this->user
        );
    }
}
