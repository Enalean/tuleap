<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\AllowedProjectsConfig;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\AllowedProjectsDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureConfigController;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureCreator;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigController;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\Config\ConfigRouter;

require_once('pre.php');

$plugin_manager = PluginManager::instance();
$plugin = $plugin_manager->getPluginByName('tracker');
if ($plugin && $plugin_manager->isPluginAvailable($plugin)) {
    $project_manager = ProjectManager::instance();
    $request         = HTTPRequest::instance();
    $current_user    = UserManager::instance()->getCurrentUser();
    $nature_dao      = new NatureDao();

    $router = new ConfigRouter(
        new CSRFSynchronizerToken($_SERVER['SCRIPT_URL']),
        new MailGatewayConfigController(
            new MailGatewayConfig(
                new MailGatewayConfigDao()
            ),
            new Config_LocalIncFinder(),
            EventManager::instance()
        ),
        new NatureConfigController(
            $project_manager,
            new AllowedProjectsConfig(
                $project_manager,
                new AllowedProjectsDao(),
                new Tracker_Hierarchy_Dao()
            ),
            new NatureCreator(
                $nature_dao
            ),
            new NatureFactory(
                $nature_dao
            )
        )
    );
    $router->process($request, $GLOBALS['HTML'], $current_user);
} else {
    header('Location: '.get_server_url());
}
