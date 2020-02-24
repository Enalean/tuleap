<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Dashboard\Project\ProjectDashboardPresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetColumnPresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetLinePresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;

class AssetsIncluderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AssetsIncluder */
    private $includer;

    /**
     * @var BaseLayout|Mockery\MockInterface
     */
    private $layout;

    /** @var CssAssetCollection */
    private $css_asset_collection;

    protected function setUp() : void
    {
        parent::setUp();

        $this->layout = Mockery::mock(BaseLayout::class);

        $include_assets = Mockery::mock(IncludeAssets::class);
        $include_assets->allows('getFileUrl')->andReturnUsing(function ($file_name) {
            return $file_name;
        });
        $css_include_assets = Mockery::mock(IncludeAssets::class);
        $css_include_assets->allows('getPath');
        $this->css_asset_collection = new CssAssetCollection([new CssAsset($css_include_assets, 'dashboards')]);

        $this->includer = new AssetsIncluder($this->layout, $include_assets, $this->css_asset_collection);
    }

    public function testItAlwaysIncludesDashboardJsAndCss() : void
    {
        $this->layout->shouldReceive('includeFooterJavascriptFile')->once()->with('dashboard.js');
        $this->layout->shouldReceive('addCssAssetCollection')->once()->with($this->css_asset_collection);

        $this->includer->includeAssets([]);
    }

    public function testItDoesNotIncludeDependenciesIfThereIsNoDashboard() : void
    {
        $this->expectDependenciesScriptsWillNOTBeIncluded();
        $this->layout->shouldReceive('addCssAssetCollection')->once()->with($this->css_asset_collection);

        $this->includer->includeAssets([]);
    }

    public function testItDoesNotIncludeDependenciesIfCurrentDashboardDoesNotHaveAnyWidgets() : void
    {
        $empty_dashboard               = Mockery::mock(ProjectDashboardPresenter::class);
        $empty_dashboard->is_active    = true;
        $empty_dashboard->widget_lines = [];

        $this->expectDependenciesScriptsWillNOTBeIncluded();
        $this->layout->shouldReceive('addCssAssetCollection')->once();

        $this->includer->includeAssets([$empty_dashboard]);
    }

    public function testItDoesNotIncludeDependenciesIfItIsNotNeeded() : void
    {
        $dashboard = $this->getDashboardWithWidgets(['widget_without_dependencies']);

        $this->expectDependenciesScriptsWillNOTBeIncluded();
        $this->layout->shouldReceive('addCssAssetCollection')->once();

        $this->includer->includeAssets([$dashboard]);
    }

    public function testItIncludesDependenciesWidgetsWithoutDuplicationWhenRequested() : void
    {
        $dashboard = $this->getDashboardWithWidgets(['first_widget', 'second_widget']);

        //first widget
        $this->layout->shouldReceive('includeFooterJavascriptFile')->with('dashboard.js')->ordered()->once();
        $this->layout->shouldReceive('includeFooterJavascriptFile')->with('dependency_one')->ordered()->once();
        $this->layout->shouldReceive('includeFooterJavascriptSnippet')->with('dependency_two')->once();
        $this->layout->shouldReceive('includeFooterJavascriptFile')->with('dependency_three')->ordered()->once();
        // second widget
        $this->layout->shouldReceive('includeFooterJavascriptFile')->with('dependency_one')->ordered()->once();
        $this->layout->shouldReceive('includeFooterJavascriptFile')->with('dependency_four')->ordered()->once();

        $this->layout->shouldReceive('addCssAssetCollection')->once();

        $this->includer->includeAssets([$dashboard]);
    }

    private function expectDependenciesScriptsWillNOTBeIncluded() : void
    {
        $this->layout->shouldReceive('includeFooterJavascriptFile')->once()->with('dashboard.js');
        $this->layout->shouldNotReceive('includeFooterJavascriptSnippet');
    }

    /**
     * @param string[] $widget_names
     */
    private function getDashboardWithWidgets(array $widget_names) : ProjectDashboardPresenter
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
