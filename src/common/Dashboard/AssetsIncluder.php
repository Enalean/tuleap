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

use Tuleap\Dashboard\Widget\DashboardWidgetPresenter;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\IncludeAssets;

class AssetsIncluder
{
    /**
     * @var IncludeAssets
     */
    private $include_assets;

    public function __construct(IncludeAssets $include_assets)
    {
        $this->include_assets = $include_assets;
    }

    /**
     * @param DashboardPresenter[] $dashboards_presenter
     */
    public function includeAssets(array $dashboards_presenter)
    {
        $GLOBALS['Response']->includeFooterJavascriptFile($this->include_assets->getFileURL('dashboard.js'));
        $this->includeAssetsNeededByWidgets($dashboards_presenter);
    }

    /**
     * @param DashboardPresenter[] $dashboards_presenter
     */
    private function includeAssetsNeededByWidgets(array $dashboards_presenter)
    {
        $current_dashboard = $this->getCurrentDashboard($dashboards_presenter);
        if (! $current_dashboard) {
            return;
        }

        $is_unique_dependency_included = [];
        $deduplicated_css_assets = new CssAssetCollection([]);
        foreach ($current_dashboard->widget_lines as $line) {
            foreach ($line->widget_columns as $column) {
                /** @var DashboardWidgetPresenter $widget */
                foreach ($column->widgets as $widget) {
                    $deduplicated_css_assets = $deduplicated_css_assets->merge($widget->stylesheet_dependencies);

                    foreach ($widget->javascript_dependencies as $javascript) {
                        if (isset($javascript['unique-name'])) {
                            if (isset($is_unique_dependency_included[$javascript['unique-name']])) {
                                continue;
                            }
                            $is_unique_dependency_included[$javascript['unique-name']] = true;
                        }
                        if (isset($javascript['snippet'])) {
                            /** @var \Tuleap\Layout\BaseLayout */
                            $GLOBALS['Response']->includeFooterJavascriptSnippet($javascript['snippet']);
                        } else {
                            /** @var \Tuleap\Layout\BaseLayout */
                            $GLOBALS['Response']->includeFooterJavascriptFile($javascript['file']);
                        }
                    }
                }
            }
        }

        /** @var \Tuleap\Layout\BaseLayout */
        $GLOBALS['Response']->addCssAssetCollection($deduplicated_css_assets);
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
