<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics 2014. All rights reserved
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

use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Layout\IncludeAssets;

require_once __DIR__ . '/../../../src/www/include/pre.php';

$assets_path    = ForgeConfig::get('tuleap_dir') . '/src/www/assets';
$include_assets = new IncludeAssets($assets_path, '/assets');

$GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('ckeditor.js'));
$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/tuleap/tuleap-ckeditor-toolbar.js');

$pluginManager    = PluginManager::instance();
$statisticsPlugin = $pluginManager->getPluginByName('statistics');
if (! $statisticsPlugin || ! $pluginManager->isPluginAvailable($statisticsPlugin)) {
    $GLOBALS['Response']->redirect('/');
}

if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

$project_quota_html = new ProjectQuotaHtml(new InstanceBaseURLBuilder(), Codendi_HTMLPurifier::instance());
$project_quota_html->displayProjectsOverQuota();
