<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 * @codingStandardsIgnoreFile
 */

class RunTestSuite
{
    public function mainWithCompatibilityListJunit(array $argv)
    {
        $test_suite = new TestSuite();
        foreach ($this->getCompatibleTestFiles() as $test_file) {
            $test_suite->addFile($test_file);
        }
        $reporter = new JUnitXMLReporter();
        $test_suite->run($reporter);
        file_put_contents($argv[1], $reporter->doc->saveXML());
    }

    public function mainWithCompatibilityList()
    {
        $test_suite = new TestSuite();
        foreach ($this->getCompatibleTestFiles() as $test_file) {
            $test_suite->addFile($test_file);
        }
        $this->run($test_suite, new ColorTextReporter());
    }

    private function getCompatibleTestFiles()
    {
        foreach (file(__DIR__.'/../../'.FindCompatibleTests::COMPATIBLE_TESTS_FILE) as $file) {
            yield trim($file);
        }
        $directory_iterator = new \DirectoryIterator(__DIR__.'/../../../plugins');
        foreach ($directory_iterator as $directory) {
            if ($directory->isDot()) {
                continue;
            }
            $compatibility_file = $directory->getPathname().'/tests/'.FindCompatibleTests::COMPATIBLE_TESTS_FILE;
            if (is_file($compatibility_file)) {
                foreach (file($compatibility_file) as $file) {
                    yield trim($file);
                }
            }
        }
    }

    public function mainWithOneFile(array $argv)
    {
        $test_suite = new TestSuite();
        $test_suite->addFile($argv[1]);
        $this->run($test_suite, new ColorTextReporter());
    }

    public function mainWithoutOutput(array $argv)
    {
        $test_suite = new TestSuite();
        $test_suite->addFile($argv[1]);
        $this->run($test_suite, new SimpleReporter());
    }

    private function run(TestSuite $test_suite, SimpleReporter $reporter)
    {
        $result = $test_suite->run($reporter);
        if ($result && $reporter->getPassCount() > 0) {
            exit(0);
        }
        exit(1);
    }
}
