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

use PHPUnit\Framework\TestCase;
use Tuleap\TestManagement\REST\v1\AutomatedTestsNotXmlException;
use Tuleap\TestManagement\REST\v1\AutomatedTestsResultPATCHRepresentation;
use Tuleap\TestManagement\REST\v1\ExtractedTestCaseFromJunit;
use Tuleap\TestManagement\REST\v1\TestsDataFromJunitExtractor;

class TestsDataFromJunitExtractorTest extends TestCase
{

    /**
     * @var TestsDataFromJunitExtractor
     */
    private $tests_data_from_junit_extractor;

    protected function setUp(): void
    {
        $this->tests_data_from_junit_extractor = new TestsDataFromJunitExtractor();
    }

    public function testGetTestsCaseFromJunit(): void
    {
        $extracted_test_1                = new ExtractedTestCaseFromJunit(
            5,
            "passed",
            "<p>Executed 'firsttest' test case. Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>",
        );

        $extracted_test_2 = new ExtractedTestCaseFromJunit(
            0,
            "failed",
            "<p>Executed 'failtest' test case. Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p><p>Got a failure: this is a failure</p>",
        );

        $automated_tests_results_representation                          = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results_representation->build_url               = 'http://exemple/of/url';
        $automated_tests_results_representation->junit_contents = [
            '<testsuites>
                <testsuite>
                    <testcase name="firsttest" time="5.649"></testcase>
                    <testcase name="failtest">
                        <failure>this is a failure</failure>
                    </testcase>
                </testsuite>
             </testsuites>'
        ];

        $result = $this->tests_data_from_junit_extractor->getTestsCaseFromJunit($automated_tests_results_representation);

        $this->assertEquals(['firsttest' => $extracted_test_1, 'failtest' => $extracted_test_2], $result);
    }

    public function testGetTestsCaseFromJunitWithMulitpleFailureForATest(): void
    {
        $extracted_test_1                = new ExtractedTestCaseFromJunit(
            5,
            "passed",
            "<p>Executed 'firsttest' test case. Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p>",
        );

        $extracted_test_2 = new ExtractedTestCaseFromJunit(
            15,
            "failed",
            "<p>Executed 'failtest' test case. Checkout build results : <a href=http://exemple/of/url>http://exemple/of/url</a></p><p>Got a failure: this is a failure</p><p>Got a failure: this is another failure</p>",
        );

        $automated_tests_results_representation                          = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results_representation->build_url               = 'http://exemple/of/url';
        $automated_tests_results_representation->junit_contents = [
            '<testsuites>
                <testsuite>
                    <testcase name="firsttest" time="5.649"></testcase>
                    <testcase name="failtest" time="5">
                        <failure>this is a failure</failure>
                        <failure>this is another failure</failure>
                    </testcase>
                    <testcase name="failtest" time="10"></testcase>
                </testsuite>
             </testsuites>'
        ];

        $result = $this->tests_data_from_junit_extractor->getTestsCaseFromJunit($automated_tests_results_representation);

        $this->assertEquals(['firsttest' => $extracted_test_1, 'failtest' => $extracted_test_2], $result);
    }

    public function testGetTestsCaseFromJunitTrowExceptionIfNoXmlInAutomatedTestResult(): void
    {
        $automated_tests_results_representation                          = new AutomatedTestsResultPATCHRepresentation();
        $automated_tests_results_representation->build_url               = 'http://exemple/of/url';
        $automated_tests_results_representation->junit_contents = [
            'Oui'
        ];

        $this->expectException(AutomatedTestsNotXmlException::class);

        $this->tests_data_from_junit_extractor->getTestsCaseFromJunit($automated_tests_results_representation);
    }
}
