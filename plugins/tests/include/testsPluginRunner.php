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
    protected $testSuite;
    protected $titles         = array('normal'=>'All Tests', 'revert'=>'All Tests (revert order)', 'random'=>'All Tests (random order)');
    protected $testFiles      = array();
    protected $testFilesToRun = array();
    protected $categoryPath   = array();
    
    public function __construct( testsPluginRequest $request) {
        $this->request = $request;
        $title = $this->getTitleByOrder($request->getOrder());
        $this->testSuite = new TestSuite($title);
    }
    
    public function appendTestsInPath($path, $category) {
        $this->categoryPath[$category] = realpath($path).DIRECTORY_SEPARATOR;
        $filterIterator = testsPluginFilterIterator::apply($path);
        foreach($filterIterator as $file) {
            $filename = str_replace($this->categoryPath[$category], '', $file->getPathname());       
            $this->testFiles[$category][] = $filename;
            $exPath = $this->explodePath($filename);
            if ($this->mustBeRun($filename)) {
                $this->testFilesToRun = array_unique(array_merge_recursive($exPath, $this->testFilesToRun));
            }
        }
        
        $this->testFiles[$category] = array_unique($this->testFiles[$category]);
        
    }
    
    protected function explodePath($pathName) {
        $expPath = array_reverse(explode(DIRECTORY_SEPARATOR, $pathName));
        $return  = array_shift($expPath);
        foreach ($expPath as $expKey) {
            $return = array($expKey=>$return);
        }
        return $return;
    }
    
    public static function implodePath() {
        $path =  func_get_args();
        return self::implodeArrayPath($path);
    }
    
    public static function implodeArrayPath($path) {
        return implode(DIRECTORY_SEPARATOR, $path);
    }
    
    protected function implodePathRecursive($pathName) {
        $return = array();
        foreach($pathName as $key=>$path) {
            if (is_array($path)) {
                 $path = $this->implodePathRecursive($path);
                 foreach ($path as $currentPath) {
                     $return[] = self::implodePath($key, $currentPath);
                 }
            } elseif(is_string($key)) {
                $return[] = self::implodePath($key, $path);
            } else {
                $return[] = $path;
            }
        }
        return array_unique($return);
    }
    
    protected function mustBeRun($filename) {        
        $testsToRun = $this->implodePathRecursive($this->request->getTestsToRun());
        return in_array($filename, $testsToRun);
    }
    
    public function getAllTestFilesOfCategory($category) {
        if (isset($this->testFiles[$category])) {
            return $this->testFiles[$category];
        } else {
            return array();
        }
    }
    public function getTestFilesToRunOfCategory($category) {
        return $this->implodePathRecursive($this->testFilesToRun);
    }
    
    public function getTitleByOrder($order) {
        return $this->titles[$order];
    }
    
    /*
    
    public function run($reporter) {
        $this->testSuite->run($reporter);
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
    
    function add_test_to_group($test, $categ, $params) {
        global $random;
        if (is_array($test)) {
            if ($categ != '_tests') {
                $g = new TestSuite($categ .' Results');
                foreach($test as $c => $t) {
                    add_test_to_group($t, $c, array('group' => &$g, 'path' => $params['path']."/$categ/"));
                }
                $params['group']->addTestCase($g);
            } else {
                foreach($test as $t) {
                    $random[] = $params['path'] . '/' . $t;
                    $params['group']->addTestFile($params['path'] . '/' . $t);
                }
            }
        } else if ($test) {
            $random[] = $params['path'] . $categ;
            $params['group']->addTestFile($params['path'] . $categ);
        }
    }
    
    $g = get_group_tests($_REQUEST['tests_to_run']);
    if (isset($_REQUEST['order']) && $_REQUEST['order'] != 'normal') {
                                    if ($_REQUEST['order'] == 'random') {
                                        shuffle($random);
    $g = new TestSuite("All Tests (random order)");
    } else if ($_REQUEST['order'] == 'invert') {
                                        rsort($random);
    $g = new TestSuite("All Tests (invert order)");
    }
    foreach($random as $file) {
    $g->addTestFile($file);
    }
    }
    
    /*
    public function append($appendTests) {
        foreach($appendTests as $plugin => $tests) {
            $testSuite = new TestSuite($plugin .' Tests');
            foreach($tests as $c => $t) {
                add_test_to_group($t, $c,
                array(
                'group' => &$o, 
                'path' => $GLOBALS['config']['plugins_root'] . ($plugin == 'Codendi' ? 'tests' : $plugin) . $GLOBALS['config']['tests_root']
                ));
            }
            $g->add($o);
        }
    return $g;
        $o =new TestSuite($plugin .' Tests');
        foreach($tests as $c => $t) {
            add_test_to_group($t, $c,
            array(
                        'group' => &$o, 
                        'path' => $GLOBALS['config']['plugins_root'] . ($plugin == 'Codendi' ? 'tests' : $plugin) . $GLOBALS['config']['tests_root']
            ));
        }
        $g->add($o);
        }
        return $g;
    }
    
    public function addTestToGroup($test, $categ, $params) {
        global $random;
        if (is_array($test)) {
            if ($categ != '_tests') {
                $g = new TestSuite($categ .' Results');
                foreach($test as $c => $t) {
                    $this->addTestToGroup($t, $c, array('group' => &$g, 'path' => $params['path']."/$categ/"));
                }
                $params['group']->addTestCase($g);
            } else {
                foreach($test as $t) {
                    $random[] = $params['path'] . '/' . $t;
                    $params['group']->addTestFile($params['path'] . '/' . $t);
                }
            }
        } elseif ($test) {
            $random[] = $params['path'] . $categ;
            $params['group']->addTestFile($params['path'] . $categ);
        }
    }
    
    public function collect($testSuite, $path) {
        $pathIterator = new FilterTestCase(new RecursiveIteratorIterator(new RecursiveCachingIterator(new RecursiveDirectoryIterator($path)),
        RecursiveIteratorIterator::SELF_FIRST));
        foreach ($pathIterator as $file) {
            $testSuite->addTestFile($file->getPathname());
        }
    }*/
}
?>