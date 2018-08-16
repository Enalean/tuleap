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

namespace Tuleap\AgileDashboard;

use Tuleap\Layout\IncludeAssets;

class KanbanJavascriptDependenciesProvider implements JavascriptDependenciesProvider
{
    public function getDependencies()
    {
        $kanban_include_assets = new IncludeAssets(
            __DIR__. '/../../../../src/www/assets/agiledashboard/scripts',
            '/assets/agiledashboard/scripts/'
        );
        $ckeditor_path = '/scripts/ckeditor-4.3.2/';

        return array(
            array('file' => $kanban_include_assets->getFileURL('angular.js'), 'unique-name' => 'angular'),
            array('snippet' => 'window.CKEDITOR_BASEPATH = "' . $ckeditor_path . '";'),
            array('file' => $ckeditor_path . 'ckeditor.js'),
            array('file' => $kanban_include_assets->getFileURL('kanban.js')),
        );
    }
}
