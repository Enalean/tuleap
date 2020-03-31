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

use Tuleap\Dashboard\Widget\DashboardWidgetPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;

class AssetsIncluder
{
    /**
     * @var BaseLayout
     */
    private $layout;
    /**
     * @var IncludeAssets
     */
    private $include_assets;
    /**
     * @var CssAssetCollection
     */
    private $css_asset_collection;

    public function __construct(
        BaseLayout $layout,
        IncludeAssets $include_assets,
        CssAssetCollection $css_asset_collection
    ) {
        $this->layout               = $layout;
        $this->include_assets       = $include_assets;
        $this->css_asset_collection = $css_asset_collection;
    }

    /**
     * @param DashboardPresenter[] $dashboards_presenter
     */
    public function includeAssets(array $dashboards_presenter)
    {
        $this->layout->includeFooterJavascriptFile($this->include_assets->getFileURL('dashboard.js'));
        $css_assets = $this->includeAssetsNeededByWidgets($dashboards_presenter);
        $this->layout->addCssAssetCollection($css_assets);
    }

    /**
     * @param DashboardPresenter[] $dashboards_presenter
     */
    private function includeAssetsNeededByWidgets(array $dashboards_presenter): CssAssetCollection
    {
        $deduplicated_css_assets = $this->css_asset_collection;
        $current_dashboard = $this->getCurrentDashboard($dashboards_presenter);
        if (! $current_dashboard) {
            return $deduplicated_css_assets;
        }

        $is_unique_dependency_included = [];
        foreach ($current_dashboard->widget_lines as $line) {
            foreach ($line->widget_columns as $column) {
                foreach ($column->widgets as $widget) {
                    \assert($widget instanceof DashboardWidgetPresenter);
                    $deduplicated_css_assets = $deduplicated_css_assets->merge($widget->stylesheet_dependencies);

                    foreach ($widget->javascript_dependencies as $javascript) {
                        if (isset($javascript['unique-name'])) {
                            if (isset($is_unique_dependency_included[$javascript['unique-name']])) {
                                continue;
                            }
                            $is_unique_dependency_included[$javascript['unique-name']] = true;
                        }
                        if (isset($javascript['snippet'])) {
                            $this->layout->includeFooterJavascriptSnippet($javascript['snippet']);
                        } else {
                            $this->layout->includeFooterJavascriptFile($javascript['file']);
                        }
                    }
                }
            }
        }

        return $deduplicated_css_assets;
    }

    /**
     * @param DashboardPresenter[] $dashboards_presenter
     * @return DashboardPresenter|null
     */
    private function getCurrentDashboard(array $dashboards_presenter)
    {
        foreach ($dashboards_presenter as $dashboard) {
            if ($dashboard->is_active) {
                return $dashboard;
            }
        }

        return null;
    }
}
