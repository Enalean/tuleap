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
    private $targets = [];
    private $junit_file;
    private $quiet = false;

    public function __construct(array $targets, array $options)
    {
        if (isset($options['log-junit'])) {
            $this->junit_file = $options['log-junit'];
        }
        if (isset($options['quiet'])) {
            $this->quiet = true;
            $this->junit_file = null;
        }
        foreach ($targets as $target) {
            if (file_exists($target)) {
                $this->targets[] = $target;
            }
        }
    }

    public function main()
    {
        $test_suite = new TestSuite();
        if (count($this->targets) === 0) {
            foreach ($this->getCompatibleTestFiles() as $test_file) {
                $test_suite->addFile($test_file);
            }
        } else {
            foreach ($this->targets as $dir) {
                if (is_dir($dir)) {
                    foreach ($this->collectFiles($dir) as $file) {
                        $test_suite->addFile($file->getPathname());
                    }
                } elseif (is_file($dir)) {
                    $test_suite->addFile($dir);
                }
            }
        }
        $reporter = new \Tuleap\Test\TuleapColorTextReporter();
        if ($this->junit_file !== null) {
            $reporter = new \Tuleap\Test\TuleapJunitXMLReporter();
        }
        if ($this->quiet === true) {
            $reporter = new SimpleReporter();
        }
        $result = $test_suite->run($reporter);
        if ($this->junit_file !== null) {
            file_put_contents($this->junit_file, $reporter->doc->saveXML());
        }
        if ($result && $reporter->getPassCount() > 0) {
            exit(0);
        }
        exit(1);
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

    private function collectFiles($path)
    {
        $rii = new FilterTestCase(
            new RecursiveIteratorIterator(
                new FilterTestDirectory(
                    new RecursiveDirectoryIterator(realpath($path))
                ),
                RecursiveIteratorIterator::SELF_FIRST
            )
        );
        foreach ($rii as $file) {
            yield $file;
        }
    }
}
