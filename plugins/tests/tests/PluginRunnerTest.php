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

require_once('../include/testsPluginRequest.php');
require_once('../include/testsPluginRunner.php');

class PluginRunnerTest extends TuleapTestCase {
    protected $runner;
    protected $request;
    protected $reqArguments = array(
    	'tests_to_run'=> array(
    		'test1' => array('_do_all'=>'0', 'test1Test.php'=>'1'),
    		'test2' => array('_do_all'=>'0', 'tests 2'=>
    		    array('_do_all'=>'1', 'test2.1Test.php'=>'1', 'test2.2Test.php'=>'1'),
    			'test2Test.php'=>'0'
    		 )
        ),
        'order'=>'normal',
        'cover_code'=>false,
        'show_pass' =>false
    );
    
    protected $fixFiles = array(
        array('fixtures', 'test2', 'test 2', 'test2.1Test.php'),
        array('fixtures', 'test2', 'test 2', 'test2.2Test.php'),
        array('fixtures', 'test2', 'test2Test.php'),
        array('fixtures', 'test1', 'test1Test.php'),
    );
    protected $fixDirs = array(
        array('fixtures'),
        array('fixtures', 'test1'),
        array('fixtures', 'test2'),
        array('fixtures', 'test2', 'test 2'),
    );
    
    public static function implodePath() {
        $path =  func_get_args();
        return self::implodeArrayPath($path);
    }
    
    public static function implodeArrayPath($path) {
        return implode(DIRECTORY_SEPARATOR, $path);
    }
    
    public function makeFixtures() {
        $baseDir = dirname(__FILE__).DIRECTORY_SEPARATOR;
        foreach($this->fixDirs as $dirname) {
            $dirname = $baseDir.self::implodeArrayPath($dirname);
            if (!file_exists($dirname)) {
                mkdir($dirname);
            }
        }
        foreach($this->fixFiles as $filename) {
            file_put_contents($baseDir.self::implodeArrayPath($filename), '');
        }
    }
    
    public function delFixtures() {
        $baseDir = dirname(__FILE__).DIRECTORY_SEPARATOR;
        foreach ($this->fixFiles as $filename) {
            $filename = $baseDir.self::implodeArrayPath($filename);
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
        $fixDirs = array_reverse($this->fixDirs);
        foreach($fixDirs as $dirname) {
            $dirname = $baseDir.self::implodeArrayPath($dirname);
            if (file_exists($dirname)) {
                rmdir($dirname);
            }
        }
    }
    
    public function setUp() {
        $this->makeFixtures();
        $this->request = new testsPluginRequest();
        $this->request->parse($this->reqArguments);
        $this->runner  = new testsPluginRunner($this->request);
    }
    
    public function tearDown() {
        $this->delFixtures();
    }
    
    public function itCanFindAllTestsFilesInTheGivenPath() {
        $baseDir  = self::implodePath(dirname(__FILE__), 'fixtures');
        $expected = array(
            self::implodePath('test1', 'test1Test.php'), 
            self::implodePath('test2', 'test2Test.php'), 
            self::implodePath('test2', 'test 2', 'test2.1Test.php'), 
            self::implodePath('test2', 'test 2', 'test2.2Test.php')
        );
        sort($expected);
        $this->runner->appendTestsInPath($baseDir, 'MyTest 1');
        $allTests = $this->runner->getAllTestFilesOfCategory('MyTest 1');
        sort($allTests);
        $this->assertEqual($expected, $allTests);
    }
    
    public function itCanFindAllTestsFilesInTheGivenPathThatMustBeRun() {
        
        $baseDir  = self::implodePath(dirname(__FILE__), 'fixtures');
        $expected = array(
        self::implodePath('test1', 'test1Test.php'),
        self::implodePath('test2', 'test 2', 'test2.1Test.php'),
        self::implodePath('test2', 'test 2', 'test2.2Test.php')
        );
        sort($expected);
                $this->runner->appendTestsInPath($baseDir, 'MyTest 2');
        $allTests = $this->runner->getTestFilesToRunOfCategory('MyTest 2');
        sort($allTests);
        $this->assertEqual($expected, $allTests);
    }
}
?>