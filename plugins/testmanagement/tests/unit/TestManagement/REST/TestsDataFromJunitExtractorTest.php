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

namespace Tuleap\TestManagement\REST;

use Mockery;
use PHPUnit\Framework\TestCase;
use TemplateRenderer;
use Tuleap\TestManagement\REST\v1\AutomatedTestsNotXmlException;
use Tuleap\TestManagement\REST\v1\AutomatedTestsResultPATCHRepresentation;
use Tuleap\TestManagement\REST\v1\ExtractedTestResultFromJunit;
use Tuleap\TestManagement\REST\v1\TestsDataFromJunitExtractor;

class TestsDataFromJunitExtractorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var TestsDataFromJunitExtractor
     */
    private $tests_data_from_junit_extractor;

    /**
     * @var TemplateRenderer
     */
    private $template_renderer;

    protected function setUp(): void
    {
        $this->template_renderer = Mockery::mock(TemplateRenderer::class);
        $this->tests_data_from_junit_extractor = new TestsDataFromJunitExtractor($this->template_renderer);
    }

    public function testGetTestsCaseFromJunit(): void
    {
        $extracted_test_1 = new ExtractedTestResultFromJunit();
        $extracted_test_1->addTime(5);
        $extracted_test_1->setStatus("passed");
        $extracted_test_1->addFeedbackOnResult("test case executed");

        $extracted_test_2 = new ExtractedTestResultFromJunit();
        $extracted_test_2->addTime(0);
        $extracted_test_2->setStatus("failed");
        $extracted_test_2->addFeedbackOnResult("test case executedfailure feedback");

        $extracted_test_3 = new ExtractedTestResultFromJunit();
        $extracted_test_3->addTime(5);
        $extracted_test_3->setStatus("passed");
        $extracted_test_3->addFeedbackOnResult("test case executed");

        $extracted_test_suite = new ExtractedTestResultFromJunit();
        $extracted_test_suite->addTime(6);
        $extracted_test_suite->setStatus("failed");
        $extracted_test_suite->addFeedbackOnResult("test suite executedfailure feedback");

        $extracted_test_suite_2 = new ExtractedTestResultFromJunit();
        $extracted_test_suite_2->addTime(6);
        $extracted_test_suite_2->setStatus("passed");
        $extracted_test_suite_2->addFeedbackOnResult("test suite executed");

        $extracted_test_suite_3 = new ExtractedTestResultFromJunit();
        $extracted_test_suite_3->addTime(6);
        $extracted_test_suite_3->setStatus("passed");
        $extracted_test_suite_3->addFeedbackOnResult("test suite executed");

        $automated_tests_results_representation                          = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results_representation->build_url               = 'http://exemple/of/url';
        $automated_tests_results_representation->junit_contents = [
            '<testsuites>
                <testsuite name="testSuite" failures="1" time="6">
                    <testcase name="firsttest" time="5.649"></testcase>
                    <testcase name="failtest">
                        <failure>this is a failure</failure>
                    </testcase>
                </testsuite>
                <testsuite name="testSuite2" failures="0" time="6">
                    <testsuite name="testSuite3" failures="0" time="6">
                        <testcase name="thirdtest" time="5.649"></testcase>
                    </testsuite>
                </testsuite>
             </testsuites>'
        ];

        $this->template_renderer->shouldReceive('renderToString')->with('test-case-execution', Mockery::andAnyOtherArgs())->andReturn('test case executed')->times(3);
        $this->template_renderer->shouldReceive('renderToString')->with('test-suite-execution', Mockery::andAnyOtherArgs())->andReturn('test suite executed')->times(3);
        $this->template_renderer->shouldReceive('renderToString')->with('failure-feedback', Mockery::andAnyOtherArgs())->andReturn('failure feedback')->times(2);

        $result = $this->tests_data_from_junit_extractor->getTestsResultsFromJunit($automated_tests_results_representation);

        $this->assertEquals(
            [
                'firsttest' => $extracted_test_1,
                'failtest'  => $extracted_test_2,
                'thirdtest' => $extracted_test_3,
                'testSuite' => $extracted_test_suite,
                'testSuite2' => $extracted_test_suite_2,
                'testSuite3' => $extracted_test_suite_3
            ],
            $result
        );
    }

    public function testGetTestsCaseFromJunitWithMulitpleFailureForATest(): void
    {
        $extracted_test_1 = new ExtractedTestResultFromJunit();
        $extracted_test_1->addTime(5);
        $extracted_test_1->setStatus("passed");
        $extracted_test_1->addFeedbackOnResult("test case executed");

        $extracted_test_2 = new ExtractedTestResultFromJunit();
        $extracted_test_2->addTime(15);
        $extracted_test_2->setStatus("failed");
        $extracted_test_2->addFeedbackOnResult("test case executedfailure feedbackfailure feedbacktest case executed");

        $extracted_test_suite = new ExtractedTestResultFromJunit();
        $extracted_test_suite->addTime(25);
        $extracted_test_suite->setStatus("failed");
        $extracted_test_suite->addFeedbackOnResult("test suite executedfailure feedbackfailure feedback");

        $automated_tests_results_representation                          = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results_representation->build_url               = 'http://exemple/of/url';
        $automated_tests_results_representation->junit_contents = [
            '<testsuites>
                <testsuite name="testSuite" failures="2" time="25">
                    <testcase name="firsttest" time="5.649"></testcase>
                    <testcase name="failtest" time="5">
                        <failure>this is a failure</failure>
                        <failure>this is another failure</failure>
                    </testcase>
                    <testcase name="failtest" time="10"></testcase>
                </testsuite>
             </testsuites>'
        ];

        $this->template_renderer->shouldReceive('renderToString')->with('test-case-execution', Mockery::andAnyOtherArgs())->andReturn('test case executed')->times(3);
        $this->template_renderer->shouldReceive('renderToString')->with('test-suite-execution', Mockery::andAnyOtherArgs())->andReturn('test suite executed')->once();
        $this->template_renderer->shouldReceive('renderToString')->with('failure-feedback', Mockery::andAnyOtherArgs())->andReturn('failure feedback')->times(4);

        $result = $this->tests_data_from_junit_extractor->getTestsResultsFromJunit($automated_tests_results_representation);

        $this->assertEquals(['firsttest' => $extracted_test_1, 'failtest' => $extracted_test_2, 'testSuite' => $extracted_test_suite], $result);
    }

    public function testGetTestsCaseFromJunitTrowExceptionIfNoXmlInAutomatedTestResult(): void
    {
        $automated_tests_results_representation                          = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results_representation->build_url               = 'http://exemple/of/url';
        $automated_tests_results_representation->junit_contents = [
            'Oui'
        ];

        $this->expectException(AutomatedTestsNotXmlException::class);

        $this->tests_data_from_junit_extractor->getTestsResultsFromJunit($automated_tests_results_representation);
    }

    public function testItCollectsTestSuitesAsWell(): void
    {
        $extracted_suite_1 = new ExtractedTestResultFromJunit();
        $extracted_suite_1->addTime(2);
        $extracted_suite_1->setStatus("passed");
        $extracted_suite_1->addFeedbackOnResult("test suite executed");

        $extracted_suite_2 = new ExtractedTestResultFromJunit();
        $extracted_suite_2->addTime(4);
        $extracted_suite_2->setStatus("failed");
        $extracted_suite_2->addFeedbackOnResult("test suite executedfailure feedback");

        $automated_tests_results_representation = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results_representation->build_url = 'http://exemple/of/url';
        $automated_tests_results_representation->junit_contents = [
            '<testsuites>
                <testsuite name="firstTestSuite" failures="0" time="2">
                    <testcase name="firsttest" time="5.649"></testcase>
                </testsuite>
                <testsuite name="secondTestSuite" failures="1" time="4">
                    <testcase name="failtest">
                        <failure>this is a failure</failure>
                    </testcase>
                </testsuite>
             </testsuites>'
        ];

        $this->template_renderer->shouldReceive('renderToString')->with('test-case-execution', Mockery::andAnyOtherArgs())->andReturn('test case executed')->times(2);
        $this->template_renderer->shouldReceive('renderToString')->with('test-suite-execution', Mockery::andAnyOtherArgs())->andReturn('test suite executed')->times(2);
        $this->template_renderer->shouldReceive('renderToString')->with('failure-feedback', Mockery::andAnyOtherArgs())->andReturn('failure feedback')->times(2);

        $result = $this->tests_data_from_junit_extractor->getTestsResultsFromJunit($automated_tests_results_representation);

        $this->assertEquals($extracted_suite_1, $result['firstTestSuite']);
        $this->assertEquals($extracted_suite_2, $result['secondTestSuite']);
    }
}
