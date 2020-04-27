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

use SimpleXMLElement;

class TestsDataFromJunitExtractor
{
    /**
     * @return array<string, ExtractedTestCaseFromJunit>
     * @throws AutomatedTestsNotXmlException
     */
    public function getTestsCaseFromJunit(
        AutomatedTestsResultPATCHRepresentation $automated_tests_results_representation
    ): array {
        $build_url     = $automated_tests_results_representation->build_url;
        $all_test_case = [];

        foreach ($automated_tests_results_representation->junit_contents as $junit_content) {
            $this->isXml($junit_content);

            $automated_test_xml = new SimpleXMLElement($junit_content);
            $this->extractTestExecutionData($automated_test_xml, $all_test_case, $build_url);
        }

        return $all_test_case;
    }

    /**
     * @param ExecutionWithAutomatedTestData[] $executions_with_automated_test
     * @param array<string, ExtractedTestCaseFromJunit> $all_test_cases
     */
    private function extractTestExecutionData(
        SimpleXMLElement $junit_xml,
        array &$all_test_cases,
        string $build_url
    ): void {
        foreach ($junit_xml->testsuite as $test_suite) {
            if ($test_suite->testcase) {
                $this->extractTestCaseFromTestSuite($test_suite, $all_test_cases, $build_url);
            }
        }
    }

    /**
     * @param array<string, ExtractedTestCaseFromJunit> $all_test_cases
     */
    private function extractTestCaseFromTestSuite(
        SimpleXMLElement $test_suite,
        array &$all_test_cases,
        string $build_url
    ): void {
        foreach ($test_suite->testcase as $testcase) {
            $testcase_name = (string) $testcase['name'];
            $status        = $testcase->failure ? 'failed' : 'passed';
            $time          = (int) ($testcase['time'] ?? 0);

            if (isset($all_test_cases[$testcase_name])) {
                $this->updateExtractedTest($all_test_cases[$testcase_name], $testcase, $status);
                continue;
            }

            $result = "<p>Executed '$testcase_name' test case. Checkout build results : <a href=$build_url>$build_url</a></p>";

            $all_test_cases[$testcase_name] = new ExtractedTestCaseFromJunit(
                $time,
                $status,
                $result
            );

            $this->addFailuresForTest($testcase, $all_test_cases[$testcase_name]);
        }
    }

    /**
     * @throws AutomatedTestsNotXmlException
     */
    private function isXml(string $automated_test_results): void
    {
        $previous_use_errors = libxml_use_internal_errors(true);
        simplexml_load_string($automated_test_results);
        if (libxml_get_errors()) {
            libxml_use_internal_errors($previous_use_errors);
            throw new AutomatedTestsNotXmlException('Automated tests results contents should be XML.');
        }
        libxml_use_internal_errors($previous_use_errors);
    }

    private function addFailuresForTest(SimpleXMLElement $testcase, ExtractedTestCaseFromJunit $extracted_test): void
    {
        foreach ($testcase->failure as $failure) {
            $extracted_test->addFailureOnResult("<p>Got a failure: " . $failure . "</p>");
        }
    }

    private function updateExtractedTest(
        ExtractedTestCaseFromJunit $extracted_test_case,
        SimpleXMLElement $testcase,
        string $status
    ): void {
        $extracted_test_case->addTime((int) ($testcase['time'] ?? 0));

        if ($extracted_test_case->getStatus() === 'failed') {
            $this->addFailuresForTest($testcase, $extracted_test_case);
            return;
        }

        $extracted_test_case->setStatus($status);
    }
}
