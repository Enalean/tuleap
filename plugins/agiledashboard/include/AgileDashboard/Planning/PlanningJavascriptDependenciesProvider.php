<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class PlanningJavascriptDependenciesProvider implements JavascriptDependenciesProvider
{
    public function getDependencies()
    {
        $planning_v2_include_assets = new IncludeAssets(
            AGILEDASHBOARD_BASE_DIR . '/../www/js/planning-v2/dist',
            AGILEDASHBOARD_BASE_URL . '/js/planning-v2/dist'
        );
        $ckeditor_path = '/scripts/ckeditor-4.3.2/';

        return array(
            array('snippet' => 'window.CKEDITOR_BASEPATH = "' . $ckeditor_path . '";'),
            array('file' => $ckeditor_path . 'ckeditor.js'),
            array('file' => $planning_v2_include_assets->getFileURL('planning-v2.js')),
        );
    }
}
