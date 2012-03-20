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

require_once dirname(__FILE__).'/../include/TestsPluginRequest.class.php';
require_once dirname(__FILE__).'/../include/TestsPluginRunner.class.php';
require_once('PluginFilterIteratorTest.php');

class PluginRunnerTest extends TuleapTestCase {
    protected $runner;
    protected $request;
    protected $reqArguments = array(
    	'tests_to_run'=> array(
    		'test1' => array('_do_all'=>'0', 'test1Test.php'=>'1'),
    		'test2' => array('_do_all'=>'0', 'test 2'=>
    		    array('_do_all'=>'1', 'test2.1Test.php'=>'1', 'test2.2Test.php'=>'1'),
    			'test2Test.php'=>'0'
    		 )
        ),
        'order'=>'normal',
        'cover_code'=>false,
        'show_pass' =>false
    );
    
    public function setUp() {
        parent::setUp();
        PluginFilterIteratorTest::makeFixtures(PluginFilterIteratorTest::$fixDirs, PluginFilterIteratorTest::$fixFiles);
        $this->request = new TestsPluginRequest();
        $this->request->parse($this->reqArguments);
        $this->request->setDisplay('testsPluginRunnerHTML');
        $this->runner  = new TestsPluginRunner($this->request);
    }
    
    public function tearDown() {
        parent::tearDown();
        PluginFilterIteratorTest::delFixtures(PluginFilterIteratorTest::$fixDirs, PluginFilterIteratorTest::$fixFiles);
    }
    
    public function itCanFindAllTestsFilesInTheGivenPathThatMustBeRun() {
        
        $baseDir  = PluginFilterIteratorTest::implodePath(dirname(__FILE__), 'fixtures');
        
        $expected = array(
            PluginFilterIteratorTest::implodePath('test1', 'test1Test.php'),
            PluginFilterIteratorTest::implodePath('test2', 'test 2', 'test2.1Test.php'),
            PluginFilterIteratorTest::implodePath('test2', 'test 2', 'test2.2Test.php')
        );
        sort($expected);
        $this->runner->appendTestsInPath($baseDir, 'MyTest 2');
        $allTests = $this->runner->getTestFilesToRunOfCategory('MyTest 2');
        sort($allTests);
        $this->assertEqual($expected, $allTests);
    }
}
?>