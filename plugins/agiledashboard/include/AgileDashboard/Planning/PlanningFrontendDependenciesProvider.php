<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;

final readonly class PlanningFrontendDependenciesProvider
{
    public function __construct(
        private IncludeAssets $include_core_assets,
        private IncludeAssets $agiledashboard_include_assets,
        private BaseLayout $layout,
    ) {
    }

    public function loadPlanningJavascriptAssets(): void
    {
        $this->layout->addJavascriptAsset(new JavascriptAsset($this->include_core_assets, 'ckeditor.js'));
        $this->layout->addJavascriptAsset(new JavascriptAsset($this->agiledashboard_include_assets, 'planning-v2.js'));
    }

    public function loadStyleAssets(): void
    {
        $this->layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons($this->agiledashboard_include_assets, 'planning-style')
        );
    }
}
