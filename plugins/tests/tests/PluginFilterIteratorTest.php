<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class PluginFilterIteratorTest extends TuleapTestCase {


    public static $fixFiles = array(
        array('fixtures', 'test2', 'test 2', 'test2.1Test.php'),
        array('fixtures', 'test2', 'test 2', 'test2.2Test.php'),
        array('fixtures', 'test2', 'test2Test.php'),
        array('fixtures', 'test1', 'test1Test.php'),
    );
    
    public static $fixDirs = array(
        array('fixtures'),
        array('fixtures', 'test1'),
        array('fixtures', 'test2'),
        array('fixtures', 'test2', 'test 2'),
    );
    
    public static $fixtureDir;

    
    public function setUp() {
        self::$fixtureDir = self::implodePath(dirname(__FILE__), 'fixtures').DIRECTORY_SEPARATOR;
        self::makeFixtures(self::$fixDirs, self::$fixFiles);
    }
    
    public function tearDown() {
        self::delFixtures(self::$fixDirs, self::$fixFiles);
    }

    public function itCanFindAllTestsFilesInTheGivenPath() {
        $allTestsIterator = TestsPluginFilterIterator::apply(self::$fixtureDir);
        $allTests = self::cleanIteratorToArray($allTestsIterator);
        $expected = array(
            self::implodePath('test1', 'test1Test.php'),
            self::implodePath('test2', 'test2Test.php'),
            self::implodePath('test2', 'test 2', 'test2.1Test.php'),
            self::implodePath('test2', 'test 2', 'test2.2Test.php')
        );
        sort($expected);
        $this->assertEqual($expected, $allTests);
    }
    
    public function itCanFindAllTestsFilesInTheGivenPathWithinRegexpPattern() {
        $allTestsIterator = TestsPluginFilterIterator::apply(self::$fixtureDir, '@1Test.php@');
        $allTests = self::cleanIteratorToArray($allTestsIterator);
        $expected = array(
            self::implodePath('test1', 'test1Test.php'),
            self::implodePath('test2', 'test 2', 'test2.1Test.php'),
        );
        sort($expected);
        $this->assertEqual($expected, $allTests);
        ///
        $allTestsIterator->setPattern('@2Test.php@');
        $allTests = self::cleanIteratorToArray($allTestsIterator);
        $expected = array(
            self::implodePath('test2', 'test2Test.php'),
            self::implodePath('test2', 'test 2', 'test2.2Test.php'),
        );
        sort($expected);
        $this->assertEqual($expected, $allTests);
    }
    
    /**
     * Helpers
     */

    public static function implodePath() {
        $path =  func_get_args();
        return self::implodeArrayPath($path);
    }

    public static function implodeArrayPath($path) {
        return implode(DIRECTORY_SEPARATOR, $path);
    }

    public static function makeFixtures($fixDirs, $fixFiles) {
        $baseDir = dirname(__FILE__).DIRECTORY_SEPARATOR;
        foreach($fixDirs as $dirname) {
            $dirname = $baseDir.self::implodeArrayPath($dirname);
            if (!file_exists($dirname)) {
                mkdir($dirname);
            }
        }
        foreach($fixFiles as $filename) {
            touch($baseDir.self::implodeArrayPath($filename));
        }
    }

    public static function delFixtures($fixDirs, $fixFiles) {
        $baseDir = dirname(__FILE__).DIRECTORY_SEPARATOR;
        foreach ($fixFiles as $filename) {
            $filename = $baseDir.self::implodeArrayPath($filename);
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
        $fixDirs = array_reverse($fixDirs);
        foreach($fixDirs as $dirname) {
            $dirname = $baseDir.self::implodeArrayPath($dirname);
            if (file_exists($dirname)) {
                rmdir($dirname);
            }
        }
    }

    public static function cleanIteratorToArray($iterator) {
        $array = array();
        foreach($iterator as $testFile) {
            $array[] = str_replace(self::$fixtureDir, '', $testFile->getPathName());
        }
        sort($array);
        return $array;
    }
}
?>