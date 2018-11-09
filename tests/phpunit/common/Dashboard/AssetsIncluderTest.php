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

require_once __DIR__ . '/../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Dashboard\Project\ProjectDashboardPresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetColumnPresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetLinePresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetPresenter;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Theme\BurningParrot\BurningParrotTheme;

class AssetsIncluderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AssetsIncluder */
    private $includer;

    private $backup_globals;

    public function setUp()
    {
        parent::setUp();

        $this->backup_globals = array_merge([], $GLOBALS);
        $GLOBALS['Response']  = Mockery::mock(BurningParrotTheme::class);

        $include_assets = Mockery::mock(IncludeAssets::class);
        $include_assets->allows('getFileUrl')->andReturnUsing(function ($file_name) {
            return $file_name;
        });

        $this->includer = new AssetsIncluder($include_assets);
    }

    public function tearDown()
    {
        $GLOBALS = $this->backup_globals;

        parent::tearDown();
    }

    public function testItAlwaysIncludesDashboardJs()
    {
        $GLOBALS['Response']->shouldReceive('includeFooterJavascriptFile')->once()->with('dashboard.js');

        $this->includer->includeAssets([]);
    }

    public function testItDoesNotIncludeDependenciesIfThereIsNoDashboard()
    {
        $this->expectDependenciesScriptsWillNOTBeIncluded();
        $GLOBALS['Response']->shouldNotReceive('addCssAssets');

        $this->includer->includeAssets([]);
    }

    public function testItDoesNotIncludeDependenciesIfCurrentDashboardDoesNotHaveAnyWidgets()
    {
        $empty_dashboard               = Mockery::mock(ProjectDashboardPresenter::class);
        $empty_dashboard->is_active    = true;
        $empty_dashboard->widget_lines = [];

        $this->expectDependenciesScriptsWillNOTBeIncluded();
        $GLOBALS['Response']->shouldReceive('addCssAssets')->once();

        $this->includer->includeAssets([$empty_dashboard]);
    }

    public function testItDoesNotIncludeDependenciesIfItIsNotNeeded()
    {
        $dashboard = $this->getDashboardWithWidgets(['widget_without_dependencies']);

        $this->expectDependenciesScriptsWillNOTBeIncluded();
        $GLOBALS['Response']->shouldReceive('addCssAssets')->once();

        $this->includer->includeAssets([$dashboard]);
    }

    public function testItIncludesDependenciesWidgetsWithoutDuplicationWhenRequested()
    {
        $dashboard = $this->getDashboardWithWidgets(['first_widget', 'second_widget']);

        //first widget
        $GLOBALS['Response']->shouldReceive('includeFooterJavascriptFile')->with('dashboard.js')->ordered()->once();
        $GLOBALS['Response']->shouldReceive('includeFooterJavascriptFile')->with('dependency_one')->ordered()->once();
        $GLOBALS['Response']->shouldReceive('includeFooterJavascriptSnippet')->with('dependency_two')->once();
        $GLOBALS['Response']->shouldReceive('includeFooterJavascriptFile')->with('dependency_three')->ordered()->once();
        // second widget
        $GLOBALS['Response']->shouldReceive('includeFooterJavascriptFile')->with('dependency_one')->ordered()->once();
        $GLOBALS['Response']->shouldReceive('includeFooterJavascriptFile')->with('dependency_four')->ordered()->once();

        $GLOBALS['Response']->shouldReceive('addCssAssets')->once();

        $this->includer->includeAssets([$dashboard]);
    }

    private function expectDependenciesScriptsWillNOTBeIncluded()
    {
        $GLOBALS['Response']->shouldReceive('includeFooterJavascriptFile')->once()->with('dashboard.js');
        $GLOBALS['Response']->shouldNotReceive('includeFooterJavascriptSnippet');
    }

    /**
     * @param string[] $widget_names
     * @return ProjectDashboardPresenter
     */
    private function getDashboardWithWidgets(array $widget_names)
    {
        $javascript_dependencies = [
            'first_widget'                => [
                ['file' => 'dependency_one'],
                ['snippet' => 'dependency_two'],
                ['file' => 'dependency_three', 'unique-name' => 'angular']
            ],
            'second_widget'               => [
                ['file' => 'dependency_one'],
                ['file' => 'dependency_three', 'unique-name' => 'angular'],
                ['file' => 'dependency_four']
            ],
            'widget_without_dependencies' => []
        ];

        $first_collection  = Mockery::mock(CssAssetCollection::class)
            ->shouldReceive('getDeduplicatedAssets')
            ->andReturns([])
            ->getMock();
        $second_collection = Mockery::mock(CssAssetCollection::class)
            ->shouldReceive('getDeduplicatedAssets')
            ->andReturns([])
            ->getMock();
        $empty_collection  = Mockery::mock(CssAssetCollection::class)
            ->shouldReceive('getDeduplicatedAssets')
            ->andReturns([])
            ->getMock();

        $stylesheet_dependencies = [
            'first_widget'                => $first_collection,
            'second_widget'               => $second_collection,
            'widget_without_dependencies' => $empty_collection
        ];

        $widget_presenters = [];
        foreach ($widget_names as $widget_name) {
            $widget_presenter                          = Mockery::mock(DashboardWidgetPresenter::class);
            $widget_presenter->javascript_dependencies = $javascript_dependencies[$widget_name];
            $widget_presenter->stylesheet_dependencies = $stylesheet_dependencies[$widget_name];
            $widget_presenters[]                       = $widget_presenter;
        }

        $widget_column = Mockery::mock(DashboardWidgetColumnPresenter::class);
        $widget_column->widgets = $widget_presenters;
        $widget_line = Mockery::mock(DashboardWidgetLinePresenter::class);
        $widget_line->widget_columns = [$widget_column];

        $dashboard = Mockery::mock(ProjectDashboardPresenter::class);
        $dashboard->widget_lines = [$widget_line];
        $dashboard->is_active = true;

        return $dashboard;
    }
}
