<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\InstanceBaseURLBuilder;
use Tuleap\Statistics\AdminHeaderPresenter;
use Tuleap\Statistics\ProjectQuotaPresenter;

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/ProjectQuotaHtml.class.php';


$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginAvailable($p)) {
    $GLOBALS['Response']->redirect('/');
}

// Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

$csrf = new CSRFSynchronizerToken('project_quota.php');

$request = HTTPRequest::instance();
$pqHtml  = new ProjectQuotaHtml(new InstanceBaseURLBuilder(), Codendi_HTMLPurifier::instance());
$pqHtml->HandleRequest($request);

$project_quota_manager = new ProjectQuotaManager();

$collection = $pqHtml->getListOfProjectQuotaPresenters($request);

$title = $GLOBALS['Language']->getText('plugin_statistics', 'quota_title');

$admin_page_renderer = new AdminPageRenderer();
$admin_page_renderer->renderANoFramedPresenter(
    $title,
    ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
    'project-quota',
    new ProjectQuotaPresenter(
        new AdminHeaderPresenter(
            $title,
            'project_quota'
        ),
        $request->get('project_filter'),
        $collection['quotas'],
        $collection['pagination'],
        $project_quota_manager->getDefaultQuota(),
        $project_quota_manager->getMaximumQuota(),
        $csrf
    )
);
