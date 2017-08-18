<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/TestsPluginFilterIterator.class.php';

class PluginFilterIteratorTest extends TuleapTestCase
{
    private $fixFiles = array(
        'fixtures/test2/test 2/test2.1Test.php',
        'fixtures/test2/test 2/test2.2Test.php',
        'fixtures/test2/test2Test.php',
        'fixtures/test1/test1Test.php',
    );

    private $fixDirs = array(
        'fixtures',
        'fixtures/test1',
        'fixtures/test2',
        'fixtures/test2/test 2',
    );

    private $fixtureDir;

    public function setUp() {
        parent::setUp();
        $this->fixtureDir = $this->getTmpDir() . '/fixtures/';
        $this->makeFixtures($this->fixDirs, $this->fixFiles);
    }

    public function itCanFindAllTestsFilesInTheGivenPath() {
        $allTestsIterator = TestsPluginFilterIterator::apply($this->fixtureDir);
        $allTests = self::cleanIteratorToArray($allTestsIterator);
        $expected = array(
            'test1/test1Test.php',
            'test2/test2Test.php',
            'test2/test 2/test2.1Test.php',
            'test2/test 2/test2.2Test.php'
        );
        sort($expected);
        $this->assertEqual($expected, $allTests);
    }

    public function itCanFindAllTestsFilesInTheGivenPathWithinRegexpPattern() {
        $allTestsIterator = TestsPluginFilterIterator::apply($this->fixtureDir, '@1Test.php@');
        $allTests = self::cleanIteratorToArray($allTestsIterator);
        $expected = array(
            'test1/test1Test.php',
            'test2/test 2/test2.1Test.php',
        );
        sort($expected);
        $this->assertEqual($expected, $allTests);
        ///
        $allTestsIterator->setPattern('@2Test.php@');
        $allTests = self::cleanIteratorToArray($allTestsIterator);
        $expected = array(
            'test2/test2Test.php',
            'test2/test 2/test2.2Test.php',
        );
        sort($expected);
        $this->assertEqual($expected, $allTests);
    }

    private function makeFixtures($fixDirs, $fixFiles) {
        $baseDir = $this->getTmpDir();
        foreach($fixDirs as $dirname) {
            $dirname = "$baseDir/$dirname";
            if (!file_exists($dirname)) {
                mkdir($dirname);
            }
        }
        foreach($fixFiles as $filename) {
            touch("$baseDir/$filename");
        }
    }

    private function cleanIteratorToArray($iterator) {
        $array = array();
        foreach($iterator as $testFile) {
            $array[] = str_replace($this->fixtureDir, '', $testFile->getPathName());
        }
        sort($array);
        return $array;
    }
}
