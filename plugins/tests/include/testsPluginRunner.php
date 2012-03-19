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
require_once('testsPluginFilterIterator.php');

class testsPluginRunner {
    
    protected $request;
    protected $titles         = array('normal'=>'All Tests', 'revert'=>'All Tests (revert order)', 'random'=>'All Tests (random order)');
    protected $testSuite;
    protected $testFiles      = array();
    protected $testFilesToRun = array();
    protected $categoryPath   = array();
    
    public function __construct( testsPluginRequest $request) {
        $this->request = $request;
        $title = $this->getTitleByOrder($request->getOrder());
        $this->testSuite = new TestSuite();
    }
    
    public function appendTestsInPath($path, $category) {
        $this->categoryPath[$category] = realpath($path).DIRECTORY_SEPARATOR;
        $RecursiveIt    =  new RecursiveCachingIterator(new RecursiveDirectoryIterator($path));
        $RecursiveIt    = new RecursiveIteratorIterator($RecursiveIt, RecursiveIteratorIterator::SELF_FIRST);
        $filterIterator = new testsPluginFilterIterator($RecursiveIt);
        foreach($filterIterator as $file) {
            $pathName = $file->getPathname();
            $this->appendFileInCategory($category, $pathName);
        }
        $this->testFiles[$category] = array_unique($this->testFiles[$category]);
    }
    
    protected function appendFileInCategory($category, $filename) {        
        $this->testFiles[$category][] = str_replace($this->categoryPath[$category], '', $filename);
        $exPath = $this->explodePath($filename);
        if ($this->mustBeRun($category, $exPath)) {
            var_dump($exPath);
            $this->testFilesToRun = array_merge_recursive($exPath, $this->testFilesToRun);
        }
    }
    
    protected function explodePath($pathName) {
        return explode(DIRECTORY_SEPARATOR, $pathName);
    }
    
    protected function mustBeRun($explodedPath, $category) {
        $requestTests = $this->request->getTestsToRun();
        while (is_array($explodedPath)) {
            $current = array_shift($explodePath);
            if (! isset($requestTests[$current])) {
                return false;
            }
            $requestTests = $requestTests[$current];
        }
        return true;
    }
    
    public function getAllTestFilesOfCategory($category) {
        if (isset($this->testFiles[$category])) {
            return $this->testFiles[$category];
        } else {
            return array();
        }
    }
    public function getTestFilesToRunOfCategory($category) {
        return $this->testFilesToRun;
    }
    
    public function getTitleByOrder($order) {
        return $this->titles[$order];
    }
    
    public function run($reporter) {
        $this->testSuite->run($reporter);
    }
    
    public function appendPathRecursive($testSuite, $path) {
        $RecursiveIt =  new RecursiveCachingIterator(new RecursiveDirectoryIterator($path));
        $RecursiveIt = new RecursiveIteratorIterator($RecursiveIt, RecursiveIteratorIterator::SELF_FIRST);
        
        $this->testsPathIterators[$path] = new testsPluginFilterIterator($RecursiveIt);
    }
    
    public function iteratorToTestPath($basePath, $iterator) {
        $arrayPath = array();
        foreach($iterator as $file) {
            $pathName = $file->getPathname();
            
        }
    }
    
    public function getGroupTests($groupTests) {
        foreach($groupTests as $plugin => $tests) {
            $testSuite = new TestSuite($plugin .' Tests');
            foreach($tests as $category => $test) {
                $parameters = array('group'=> $testSuite, 'path' => $this->getPath($plugin));
                $this->addGroupTests($test, $category, $parameters);
            }
            $this->testSuite->add($testSuite);
        }
        return $this->testSuite;
    }
    
    public function getPath($plugin) {
        return $GLOBALS['config']['plugins_root'] . ($plugin == 'Codendi' ? 'tests' : $plugin) . $GLOBALS['config']['tests_root'];
    }
    public function addGroupTests($groupTests, $category, $params) {
        global $random;
        if (is_array($groupTests)) {
            if ($category != '_tests') {
                $testSuite = new TestSuite($category .' Results');
                foreach($groupTests as $subCategory => $test) {
                    $parameters = array('group'=> $testSuite, 'path' => $params['path']."/$subCategory/");
                    $this->addGroupTests($test, $subCategory, $parameters);
                }
                $params['group']->addTestCase($testSuite);
            } else {
                foreach($groupTests as $test) {
                    $random[] = $params['path'] . '/' . $test;
                    $params['group']->addTestFile($params['path'] . '/' . $test);
                }
            }
        } else if ($test) {
            $random[] = $params['path'] . $category;
            $params['group']->addTestFile($params['path'] . $category);
        }
    } 
}
?>