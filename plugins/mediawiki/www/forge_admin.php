<?php
/**
 * Copyright (c) Enalean, 2014, 2015. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Admin\AdminPageRenderer;

require_once 'pre.php';
require_once dirname(__FILE__) .'/../include/MediawikiAdminController.class.php';
require_once dirname(__FILE__) .'/../include/MediawikiSiteAdminController.class.php';

/**
 * HACK
 */
require_once MEDIAWIKI_BASE_DIR.'/../../fusionforge_compat/include/fusionforge_compatPlugin.class.php';
$ff_plugin = new fusionforge_compatPlugin();
$ff_plugin->loaded();

$vWhiteList = new Valid_WhiteList('action', array('save_permissions', 'save_language', 'index', 'site_index', 'site_update_allowed_project_list'));
$vWhiteList->required();

$action = $request->getValidated('action', $vWhiteList, 'index');
switch ($action) {
    case 'index':
    case 'save_language':
    case 'save_permissions':
        $service = $request->getProject()->getService('plugin_mediawiki');
        $controller = new MediawikiAdminController();
        $controller->$action($service, $request);
        break;
    case 'site_index':
    case 'site_update_allowed_project_list':
        $controller = new MediawikiSiteAdminController(new AdminPageRenderer());
        $controller->$action($request);
        break;
}
