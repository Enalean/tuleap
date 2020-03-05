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

use Tuleap\AgileDashboard\JavascriptDependenciesProvider;
use Tuleap\Layout\IncludeAssets;

final class PlanningJavascriptDependenciesProvider implements JavascriptDependenciesProvider
{
    /**
     * @var IncludeAssets
     */
    private $agiledashboard_include_assets;

    public function __construct(IncludeAssets $agiledashboard_include_assets)
    {
        $this->agiledashboard_include_assets = $agiledashboard_include_assets;
    }

    public function getDependencies(): array
    {
        $core_include_assets = new IncludeAssets(__DIR__ . '/../../../../../src/www/assets', '/assets');
        return [
            ['file' => $core_include_assets->getFileURL('ckeditor.js')],
            ['file' => $this->agiledashboard_include_assets->getFileURL('planning-v2.js')],
        ];
    }
}
