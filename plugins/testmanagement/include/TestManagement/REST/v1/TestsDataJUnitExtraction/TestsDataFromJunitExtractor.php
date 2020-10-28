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
use TemplateRenderer;

class TestsDataFromJunitExtractor
{
    private const STATUS_FAILURE = "failed";
    private const STATUS_SUCCESS = "passed";

    /**
     * @var \TemplateRenderer
     */
    private $renderer;

    public function __construct(TemplateRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @return array<string, ExtractedTestResultFromJunit>
     * @throws AutomatedTestsNotXmlException
     */
    public function getTestsResultsFromJunit(
        AutomatedTestsResultPATCHRepresentation $automated_tests_results_representation
    ): array {
        $build_url        = $automated_tests_results_representation->build_url;
        $all_test_results = [];

        foreach ($automated_tests_results_representation->junit_contents as $junit_content) {
            $this->isXml($junit_content);

            $automated_test_xml = new SimpleXMLElement($junit_content);
            $this->extractTestExecutionData($automated_test_xml, $all_test_results, $build_url);
        }

        return $all_test_results;
    }

    /**
     * @param array<string, ExtractedTestResultFromJunit> $all_test_results
     */
    private function extractTestExecutionData(
        SimpleXMLElement $junit_xml,
        array &$all_test_results,
        string $build_url
    ): void {
        foreach ($junit_xml->testsuite as $test_suite) {
            $this->registerTestSuiteResults($test_suite, $all_test_results, $build_url);
        }
    }

    /**
     * @param array<string, ExtractedTestResultFromJunit> $all_test_results
     */
    private function registerTestSuiteResults(
        SimpleXMLElement $test_suite,
        array &$all_test_results,
        string $build_url
    ): void {
        $test_suite_name = (string) $test_suite['name'];
        $status = (int) $test_suite['failures'] > 0 ? self::STATUS_FAILURE : self::STATUS_SUCCESS;
        $time = (int) ($test_suite['time'] ?? 0);

        if (! isset($all_test_results[$test_suite_name])) {
            $all_test_results[$test_suite_name] = new ExtractedTestResultFromJunit();
        }

        $extracted_result = $all_test_results[$test_suite_name];
        $extracted_result->addTime($time);
        $this->changeResultStatusIfNeeded($extracted_result, $status);
        $extracted_result->addFeedbackOnResult(
            $this->renderer->renderToString(
                'test-suite-execution',
                new TestSuiteExecutionPresenter($test_suite_name, $build_url)
            )
        );

        if ($status === self::STATUS_FAILURE) {
            $this->collectFailuresForTestSuite($test_suite, $extracted_result);
        }

        foreach ($test_suite->testsuite as $nested_test_suite) {
            $this->registerTestSuiteResults($nested_test_suite, $all_test_results, $build_url);
        }

        if ($test_suite->testcase) {
            $this->extractTestCaseFromTestSuite($test_suite, $all_test_results, $build_url);
        }
    }

    /**
     * @param array<string, ExtractedTestResultFromJunit> $all_test_results
     */
    private function extractTestCaseFromTestSuite(
        SimpleXMLElement $test_suite,
        array &$all_test_results,
        string $build_url
    ): void {
        foreach ($test_suite->testcase as $testcase) {
            $testcase_name = (string) $testcase['name'];
            $status = $testcase->failure ? self::STATUS_FAILURE : self::STATUS_SUCCESS;
            $time = (int) ($testcase['time'] ?? 0);

            if (! isset($all_test_results[$testcase_name])) {
                $all_test_results[$testcase_name] = new ExtractedTestResultFromJunit();
            }

            $extracted_result = $all_test_results[$testcase_name];
            $extracted_result->addTime($time);
            $this->changeResultStatusIfNeeded($extracted_result, $status);
            $extracted_result->addFeedbackOnResult(
                $this->renderer->renderToString(
                    'test-case-execution',
                    new TestCaseExecutionPresenter($testcase_name, $build_url)
                )
            );

            if ($status === self::STATUS_FAILURE) {
                $this->addFailuresForTest($testcase, $extracted_result);
            }
        }
    }

    private function changeResultStatusIfNeeded(
        ExtractedTestResultFromJunit $result,
        string $status
    ): void {
        if ($result->getStatus() === self::STATUS_FAILURE) {
            return;
        }

        if ($status === self::STATUS_SUCCESS) {
            $result->setStatus(self::STATUS_SUCCESS);
        }

        if ($status === self::STATUS_FAILURE) {
            $result->setStatus(self::STATUS_FAILURE);
        }
    }

    private function collectFailuresForTestSuite(
        SimpleXMLElement $test_suite,
        ExtractedTestResultFromJunit $result
    ): void {
        foreach ($test_suite->testcase as $testcase) {
            $this->addFailuresForTest($testcase, $result);
        }

        foreach ($test_suite->testsuite as $nested_test_suite) {
            $this->collectFailuresForTestSuite($nested_test_suite, $result);
        }
    }

    private function addFailuresForTest(SimpleXMLElement $testcase, ExtractedTestResultFromJunit $extracted_test): void
    {
        foreach ($testcase->failure as $failure) {
            $extracted_test->addFeedbackOnResult(
                $this->renderer->renderToString(
                    'failure-feedback',
                    $failure
                )
            );
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
}
