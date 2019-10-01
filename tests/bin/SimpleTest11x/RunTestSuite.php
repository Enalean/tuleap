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
 */

class RunTestSuite // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
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
        foreach ($this->targets as $dir) {
            if (is_dir($dir)) {
                foreach ($this->collectFiles($dir) as $file) {
                    $test_suite->addFile($file->getPathname());
                }
            } elseif (is_file($dir)) {
                $test_suite->addFile($dir);
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
