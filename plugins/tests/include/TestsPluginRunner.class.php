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

require_once dirname(__FILE__).'/TestsPluginFilterIterator.class.php';
require_once dirname(__FILE__).'/TestsPluginRunnerPresenter.class.php';
require_once dirname(__FILE__).'/TestsPluginSuitePresenter.class.php';
require_once dirname(__FILE__).'/TestsPluginRequest.class.php';
if (!class_exists('Mustache')) {
    include_once dirname(__FILE__).'/mustache/MustacheRenderer.class.php';
}

require_once dirname(__FILE__).'/simpletest/test_case.php';

require_once dirname(__FILE__).'/../www/CodendiReporter.class.php';

class TestsPluginRunner {
    protected $request;
    protected $mainSuite;
    protected $navigator;
    protected $rootCategory = 'tests_to_run';
    protected $titles       = array('normal'=>'All Tests', 'revert'=>'All Tests (revert order)', 'random'=>'All Tests (random order)');
    protected $categories   = array();

        public function __construct(TestsPluginRequest $request) {
            $this->request   = $request;
            $title           = $this->getTitleByOrder($request->getOrder());
            $this->mainSuite = $this->buildSuite($title);
            $this->navigator = $this->getPresenter($this->rootCategory.'[_do_all]', '_all_tests');
            $title           = $this->titles[$this->request->getOrder()];
            $this->navigator->setTitle($title);
            
            $this->addSuite($this->mainSuite, $this->navigator, $this->rootCategory.'[core]', '/usr/share/codendi/tests/simpletest');
            $this->addAllPluginsSuite();
        }
        
        private function addAllPluginsSuite() {
            $allPluginsPresenterName = $this->rootCategory."[plugins]";
            $allPluginsPresenter     = $this->getPresenter($allPluginsPresenterName, '_all_plugins');
            $allPluginsSuite         = $this->buildSuite("Plugins");
            
            foreach ($this->getTestsIterator('/usr/share/codendi/plugins') as $file) {
                if ($this->isSuite($file, '/tests')) {
                    $this->addPluginSuite($file, $allPluginsPresenterName, $allPluginsPresenter, $allPluginsSuite);
                }
            }
            
            $this->mainSuite->add($allPluginsSuite);
            $this->navigator->addChild($allPluginsPresenter);
        }
        
        private function addPluginSuite($file, $allPluginsPresenterName, $allPluginsPresenter, $allPluginsSuite) {
            $pluginName          = basename($file->getPathname());
            $testsPath           = $file->getPathname().'/tests';
            $pluginPresenterName = $allPluginsPresenterName."[$pluginName]";
            
            $pluginPresenter     = $this->getPresenter($pluginPresenterName, $testsPath);
            $pluginSuite         = $this->buildSuite($pluginPresenter->title());
            
            $this->addSuite($pluginSuite, $pluginPresenter, $pluginPresenterName, $testsPath);
            
            $allPluginsPresenter->addChild($pluginPresenter);
            $allPluginsSuite->add($pluginSuite);
        }
        
        public function buildSuite($title) {
            return new TestSuite($title);
        }
        
        public function isSuite($test, $append  = '') {
            return is_dir($test->getPathname().$append) && !$test->isDot();
        }
        
        public function isTest($test) {
            return preg_match('/Test.php$/', $test->getPathname());
        }
        
        public function addSuite($suite, $presenter, $name, $path) {
            
            foreach ($this->getTestsIterator($path) as $file) {
                $childName = basename($file->getPathname());
                
                $dirName   = $name.'['.$childName.']';
                if ($this->isSuite($file)) {
                    $child = $this->getPresenter($dirName.'[_do_all]', $file->getPathname());
                    if ($this->isSelected($file->getPathname())) {
                        $suite->add($this->buildSuite($child->title()));
                    }
                    $this->addSuite($suite, $child, $dirName, $file->getPathname());
                    if ($child->hasChildren()) {
                        $presenter->addChild($child);
                    }
                } elseif ($this->isTest($file)) {
                    $dirName.='[]';
                    $child = $this->getPresenter($dirName, $file->getPathname());
                    if ($this->isSelected($file->getPathname())) {
                        $suite->addFile($file->getPathname());
                    }
                    $presenter->addChild($child);
                }               
                
            }
        }

        public function getPresenter($name, $value) {
            return new TestsPluginSuitePresenter($name, $value, $this->isSelected($value));
        }
        
        
        public function isSelected($path) {
            return $this->request->isSelected($path);

        }
        
        
        
        public function getTestsIterator($testsSrc) {
            return new DirectoryIterator($testsSrc);
        }
       
    
    
    public function runAndDisplay() {
        //$this->run();
        $navigator = $this->getNavigator();
        $results   = $this->getResults();
        $renderer = new MustacheRenderer(dirname(__FILE__).'/../templates');
        $presenter= new TestsPluginRunnerPresenter($this->request, $navigator, $results);
        $renderer->render($this->request->getDisplay(), $presenter);
    }
    
    public function getResults() {
        //var_dump($this->mainSuite);
        return $this->mainSuite;
    }
    
    public function getNavigator() {
        return $this->navigator;
    }
    
    public function getTitleByOrder($order) {
        return $this->titles[$order];
    }
    
}
?>