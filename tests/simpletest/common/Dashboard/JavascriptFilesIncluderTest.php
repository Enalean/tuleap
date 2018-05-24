<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Dashboard;

use Tuleap\Dashboard\Project\ProjectDashboardPresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetColumnPresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetLinePresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetPresenter;
use TuleapTestCase;

require_once 'www/themes/BurningParrot/BurningParrotTheme.php';
require_once 'IncludeAssetsForTestingPurpose.php';

class JavascriptFilesIncluderTest extends TuleapTestCase
{
    /** @var JavascriptFilesIncluder */
    private $includer;

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['Response'] = mock('Tuleap\Theme\BurningParrot\BurningParrotTheme');

        $this->includer = new JavascriptFilesIncluder(
            new IncludeAssetsForTestingPurpose('', '')
        );
    }

    public function itAlwaysIncludesDashboardJs()
    {
        expect($GLOBALS['Response'])->includeFooterJavascriptFile()->count(1);
        expect($GLOBALS['Response'])->includeFooterJavascriptFile('dashboard.js')->at(0);

        $this->includer->includeJavascriptFiles(array());
    }

    public function itDoesNotIncludeDependenciesIfThereIsNoDashboard()
    {
        $this->expectDependenciesScriptsWillNOTBeIncluded();

        $this->includer->includeJavascriptFiles(array());
    }

    public function itDoesNotIncludeDependenciesIfCurrentDashboardDoesNotHaveAnyWidgets()
    {
        $widgets         = array();
        $empty_dashboard = new ProjectDashboardPresenter(
            mock('Tuleap\Dashboard\Project\ProjectDashboard'),
            true,
            $widgets
        );

        $this->expectDependenciesScriptsWillNOTBeIncluded();

        $this->includer->includeJavascriptFiles(array($empty_dashboard));
    }

    public function itDoesNotIncludeDependenciesIfItIsNotNeeded()
    {
        $dashboard = $this->getDashboardWithWidgets(array('widget_without_dependencies'));

        $this->expectDependenciesScriptsWillNOTBeIncluded();

        $this->includer->includeJavascriptFiles(array($dashboard));
    }

    public function itIncludesDependenciesWidgetsWithoutDuplicationWhenRequested()
    {
        $dashboard = $this->getDashboardWithWidgets(array('first_widget', 'second_widget'));

        expect($GLOBALS['Response'])->includeFooterJavascriptFile()->count(5);
        expect($GLOBALS['Response'])->includeFooterJavascriptSnippet()->count(1);
        //first widget
        expect($GLOBALS['Response'])->includeFooterJavascriptFile('dashboard.js')->at(0);
        expect($GLOBALS['Response'])->includeFooterJavascriptFile('dependency_one')->at(1);
        expect($GLOBALS['Response'])->includeFooterJavascriptSnippet('dependency_two')->at(0);
        expect($GLOBALS['Response'])->includeFooterJavascriptFile('dependency_three')->at(2);
        // second widget
        expect($GLOBALS['Response'])->includeFooterJavascriptFile('dependency_one')->at(3);
        expect($GLOBALS['Response'])->includeFooterJavascriptFile('dependency_four')->at(4);

        $this->includer->includeJavascriptFiles(array($dashboard));
    }

    private function expectDependenciesScriptsWillNOTBeIncluded()
    {
        expect($GLOBALS['Response'])->includeFooterJavascriptFile()->count(1);
        expect($GLOBALS['Response'])->includeFooterJavascriptFile('dashboard.js')->at(0);

        expect($GLOBALS['Response'])->includeFooterJavascriptSnippet()->never();
    }

    /**
     * @param string $widget_names
     * @return ProjectDashboardPresenter
     */
    private function getDashboardWithWidgets($widget_names)
    {
        $dependencies = array(
            'first_widget' => array(
                array('file'    => 'dependency_one'),
                array('snippet' => 'dependency_two'),
                array('file'    => 'dependency_three', 'unique-name' => 'angular')
            ),
            'second_widget' => array(
                array('file' => 'dependency_one'),
                array('file' => 'dependency_three', 'unique-name' => 'angular'),
                array('file' => 'dependency_four')
            ),
            'widget_without_dependencies' => array()
        );

        $widget_presenters = array();
        foreach ($widget_names as $widget_name) {
            $widget_presenters[] = new DashboardWidgetPresenter(
                mock('Tuleap\Dashboard\Dashboard'),
                stub('Tuleap\Dashboard\Widget\DashboardWidget')->getName()->returns($widget_name),
                stub('Widget')->getJavascriptDependencies()->returns($dependencies[$widget_name]),
                true
            );
        }
        $widgets = array(
            new DashboardWidgetLinePresenter(
                1,
                'one-column',
                array(
                    new DashboardWidgetColumnPresenter(
                        1,
                        $widget_presenters
                    )
                )
            )
        );

        $dashboard = new ProjectDashboardPresenter(
            mock('Tuleap\Dashboard\Project\ProjectDashboard'),
            true,
            $widgets
        );

        return $dashboard;
    }
}
