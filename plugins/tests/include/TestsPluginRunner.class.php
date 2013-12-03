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

require_once dirname(__FILE__) . '/TestsPluginFilterIterator.class.php';
require_once dirname(__FILE__) . '/TestsPluginRunnerPresenter.class.php';
require_once dirname(__FILE__) . '/TestsPluginSuitePresenter.class.php';
require_once dirname(__FILE__) . '/TestsPluginRequest.class.php';
require_once 'common/templating/TemplateRendererFactory.class.php';

require_once dirname(__FILE__) . '/TestsPluginOrderedSuite.php';

require_once dirname(__FILE__) . '/../include/TestsPluginReporter.class.php';

class TestsPluginRunner {
    protected $request;
    protected $mainSuite;
    protected $navigator;
    protected $titles = array(
        'normal' => 'All Tests',
        'invert' => 'All Tests (revert order)',
        'random' => 'All Tests (random order)'
    );

    protected $renderDone = false;

    public $rootCategory = 'tests_to_run';

    public function __construct(TestsPluginRequest $request) {
        $this->request   = $request;
        $title           = $this->getTitleByOrder($request->getOrder());
        $this->mainSuite = $this->buildSuite($title);
        $this->navigator = $this->getPresenter($this->rootCategory, 'Main', '_all_tests');
        $this->navigator->setTitle($title);
        
        $this->addCoreSuite();
        $this->addAllPluginsSuite();
    }

    public function addCoreSuite() {
        $corePresenter = $this->getPresenter($this->rootCategory.'[core]', 'Core', '_all_core');
        $coreSuite     = $this->buildSuite($corePresenter->title());
        $corePath      =  realpath(dirname(__FILE__) . '/../../../tests/simpletest');
        
        $this->addSuite($coreSuite, $corePresenter, $this->rootCategory.'[core]', $corePath);
        
        $this->navigator->addChild($corePresenter);
        $this->mainSuite->add($coreSuite);
    }

    private function addAllPluginsSuite() {
        $allPluginsPresenter = $this->getPresenter($this->rootCategory.'[plugins]', 'Plugins', '_all_plugins');
        $allPluginsSuite     = $this->buildSuite($allPluginsPresenter->title());
        $allPluginsPath      = realpath(dirname(__FILE__) . '/../..');

        foreach ($this->getTestsIterator($allPluginsPath) as $file) {
            if ($this->isSuite($file, '/tests')) {
                $this->addPluginSuite($file, $allPluginsPresenter, $allPluginsSuite);
            }
        }
        
        $this->mainSuite->add($allPluginsSuite);
        $this->navigator->addChild($allPluginsPresenter);
    }

    private function addPluginSuite($file, $allPluginsPresenter, $allPluginsSuite) {
        $pluginName      = basename($file->getPathname());
        $testsPath       = $file->getPathname() . '/tests';
        $prefix          = $allPluginsPresenter->prefix();
        $pluginPresenter = $this->getPresenter($prefix, $pluginName, $testsPath);
        $pluginSuite     = $this->buildSuite($pluginPresenter->title());

        $this->addSuite($pluginSuite, $pluginPresenter, $prefix, $testsPath);
        if ($pluginPresenter->hasChildren()) {
            $allPluginsPresenter->addChild($pluginPresenter);
            $allPluginsSuite->add($pluginSuite);
        }
    }

    public function addSuite($parentSuite, $presenter, $name, $path) {

        foreach ($this->getTestsIterator($path) as $file) {
            $childPath = $file->getPathname();
            $baseName  = basename($childPath);
            $dirName   = $name . '[' . $baseName . ']';
            
            if ($this->isSuite($file)) {
                $child = $this->getPresenter($dirName, $baseName, $childPath);
                $child->setTitle($baseName);
                $childSuite = $this->buildSuite($child->title());
                $this->addSuite($childSuite, $child, $dirName, $childPath);
                $parentSuite->add($childSuite);
                unset($childSuite);
                if ($child->hasChildren()) {
                    $presenter->addChild($child);
                }
            } elseif ($this->isTest($file)) {
                $child = $this->getPresenter($name, $baseName, $childPath);
                if ($this->isSelected($childPath)) {
                    $parentSuite->addFile($childPath);
                }
                $presenter->addChild($child);
            }

        }
    }

    public function buildSuite($title) {
        return new TestsPluginOrderedSuite($title);
    }

    public function isSuite($test, $append = '') {
        $pathName = $test->getPathname();
        $baseName = baseName($pathName);
        return  is_dir($pathName . $append) && !in_array($baseName[0], array('_', '.'));
    }

    public function isTest($test) {
        $baseName = basename($test->getPathname());
        return !in_array($baseName[0], array('_', '.')) &&
               (substr($baseName, -8) === 'Test.php' || (version_compare(phpversion(), '5.3', '>=') &&  substr($baseName, -13) === 'TestPHP53.php'));
    }

    public function getPresenter($prefix, $name, $value) {
        return new TestsPluginSuitePresenter($prefix, $name, $value, $this->isSelected($value));
    }

    public function isSelected($path) {
        return $this->request->isSelected($path);

    }

    public function getTestsIterator($testsSrc) {
        return new DirectoryIterator($testsSrc);
    }

    public function runAndDisplay() {
        register_shutdown_function(array($this, 'onRunError'));
        $this->render($this->getNavigator(), $this->getResults());
    }

    public function getResults() {
        ob_start();
        $format   = strtolower($this->request->getDisplay());
        $reporter = TestsPluginReporterFactory::reporter($format, $this->request->getCoverCode());
        $this->mainSuite->runByOrder($reporter, $this->request->getOrder());
        return ob_get_clean();
    }

    public function getNavigator() {
        return $this->navigator;
    }

    public function getTitleByOrder($order) {
        return $this->titles[$order];
    }

    public function onRunError() {
        if ($this->renderDone === false) {
            $navigator = $this->getNavigator();
            $results   = ob_get_clean();
            $this->render($navigator, $results);
        }
    }

    protected function render($navigator, $results) {
        $presenter = new TestsPluginRunnerPresenter($this->request, $navigator, $results);
        $template  = 'testsPluginRunner' . strtoupper($this->request->getDisplay());
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__) . '/../templates');
        $renderer->renderToPage($template, $presenter);
        $this->renderDone = true;
    }
}
?>
