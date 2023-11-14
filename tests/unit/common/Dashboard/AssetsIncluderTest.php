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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Dashboard\Project\ProjectDashboardPresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetColumnPresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetLinePresenter;
use Tuleap\Dashboard\Widget\DashboardWidgetPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;

class AssetsIncluderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var AssetsIncluder */
    private $includer;

    /**
     * @var BaseLayout&MockObject
     */
    private $layout;

    /** @var CssAssetCollection */
    private $css_asset_collection;

    /**
     * @var \HTTPRequest&MockObject
     */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->layout = $this->createMock(BaseLayout::class);

        $include_assets = $this->createMock(IncludeAssets::class);
        $include_assets->method('getFileUrl')->willReturnArgument(0);
        $css_include_assets = $this->createMock(IncludeAssets::class);
        $css_include_assets->method('getPath');
        $this->css_asset_collection = new CssAssetCollection(
            [new CssAssetWithoutVariantDeclinaisons($css_include_assets, 'dashboards-style')]
        );

        $this->request = $this->createMock(\HTTPRequest::class);
        $this->request->method('getFromServer');

        $this->includer = new AssetsIncluder(
            $this->layout,
            $include_assets,
            $this->css_asset_collection
        );
    }

    public function testItAlwaysIncludesDashboardJsAndCss(): void
    {
        $this->layout->expects(self::once())->method('includeFooterJavascriptFile')->with('dashboards/dashboard.js');
        $this->layout->expects(self::once())->method('addCssAssetCollection')->with($this->css_asset_collection);

        $this->includer->includeAssets([]);
    }

    public function testItDoesNotIncludeDependenciesIfThereIsNoDashboard(): void
    {
        $this->expectDependenciesScriptsWillNOTBeIncluded();
        $this->layout->expects(self::once())->method('addCssAssetCollection')->with($this->css_asset_collection);

        $this->includer->includeAssets([]);
    }

    public function testItDoesNotIncludeDependenciesIfCurrentDashboardDoesNotHaveAnyWidgets(): void
    {
        $empty_dashboard               = $this->createMock(ProjectDashboardPresenter::class);
        $empty_dashboard->is_active    = true;
        $empty_dashboard->widget_lines = [];

        $this->expectDependenciesScriptsWillNOTBeIncluded();
        $this->layout->expects(self::once())->method('addCssAssetCollection');

        $this->includer->includeAssets([$empty_dashboard]);
    }

    public function testItDoesNotIncludeDependenciesIfItIsNotNeeded(): void
    {
        $dashboard = $this->getDashboardWithWidgets(['widget_without_dependencies']);

        $this->expectDependenciesScriptsWillNOTBeIncluded();
        $this->layout->expects(self::once())->method('addCssAssetCollection');

        $this->includer->includeAssets([$dashboard]);
    }

    public function testItIncludesDependenciesWidgetsWithoutDuplicationWhenRequested(): void
    {
        $dashboard = $this->getDashboardWithWidgets(['first_widget', 'second_widget']);

        $this->layout->method('includeFooterJavascriptFile')->withConsecutive(
        // first widget
            ['dashboards/dashboard.js'],
            ['dependency_one'],
            ['dependency_three'],
            // second widget
            ['dependency_one'],
            ['dependency_four'],
        );
        $this->layout->expects(self::once())->method('includeFooterJavascriptSnippet')->with('dependency_two');

        $this->layout->expects(self::once())->method('addCssAssetCollection');

        $this->includer->includeAssets([$dashboard]);
    }

    private function expectDependenciesScriptsWillNOTBeIncluded(): void
    {
        $this->layout->expects(self::once())->method('includeFooterJavascriptFile')->with('dashboards/dashboard.js');
        $this->layout->expects(self::never())->method('includeFooterJavascriptSnippet');
    }

    /**
     * @param string[] $widget_names
     */
    private function getDashboardWithWidgets(array $widget_names): ProjectDashboardPresenter
    {
        $javascript_dependencies = [
            'first_widget' => [
                ['file' => 'dependency_one'],
                ['snippet' => 'dependency_two'],
                ['file' => 'dependency_three', 'unique-name' => 'angular'],
            ],
            'second_widget' => [
                ['file' => 'dependency_one'],
                ['file' => 'dependency_three', 'unique-name' => 'angular'],
                ['file' => 'dependency_four'],
            ],
            'widget_without_dependencies' => [],
        ];

        $first_collection = $this->createMock(CssAssetCollection::class);
        $first_collection->method('getDeduplicatedAssets')->willReturn([]);
        $second_collection = $this->createMock(CssAssetCollection::class);
        $second_collection->method('getDeduplicatedAssets')->willReturn([]);
        $empty_collection = $this->createMock(CssAssetCollection::class);
        $empty_collection->method('getDeduplicatedAssets')->willReturn([]);

        $stylesheet_dependencies = [
            'first_widget' => $first_collection,
            'second_widget' => $second_collection,
            'widget_without_dependencies' => $empty_collection,
        ];

        $widget_presenters = [];
        foreach ($widget_names as $widget_name) {
            $widget_presenter                          = $this->createMock(DashboardWidgetPresenter::class);
            $widget_presenter->javascript_dependencies = $javascript_dependencies[$widget_name];
            $widget_presenter->stylesheet_dependencies = $stylesheet_dependencies[$widget_name];
            $widget_presenters[]                       = $widget_presenter;
        }

        $widget_column               = $this->createMock(DashboardWidgetColumnPresenter::class);
        $widget_column->widgets      = $widget_presenters;
        $widget_line                 = $this->createMock(DashboardWidgetLinePresenter::class);
        $widget_line->widget_columns = [$widget_column];

        $dashboard               = $this->createMock(ProjectDashboardPresenter::class);
        $dashboard->widget_lines = [$widget_line];
        $dashboard->is_active    = true;

        return $dashboard;
    }
}
