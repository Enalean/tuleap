<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

require_once __DIR__ . '/../include/pre.php';

$renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates/help');

$explorer_endpoint_event = new \Tuleap\REST\ExplorerEndpointAvailableEvent();
EventManager::instance()->dispatch($explorer_endpoint_event);

$presenter = array(
    'should_display_documentation_about_deprecated_soap_api' => ForgeConfig::get('should_display_documentation_about_deprecated_soap_api'),
    'explorer_url' => $explorer_endpoint_event->getEndpointURL(),
    'end_points'         => array(
        array(
            'title'       => 'Core',
            'wsdl'        => '/soap/?wsdl',
            'wsdl_viewer' => '/soap/wsdl.php',
            'changelog'   => '/soap/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__) . '/../soap/VERSION'),
            'description' => <<<EOT
Historically the sole end point, therefore it groups multiple different functions:
<ul>
    <li>Session management: login, logout, projects, ...</li>
    <li>File Release System access (FRS): addPackage, addRelease, addFile, ...</li>
    <li>Tracker v3 (for historical deployments): get/updateTracker, get/updateArtifact, ...</li>
    <li>Documentation: get/updateDocman, ...</li>
</ul>
EOT
        ),
        array(
            'title'       => 'Subversion',
            'wsdl'        => '/soap/svn/?wsdl',
            'wsdl_viewer' => '/soap/svn/wsdl-viewer.php',
            'changelog'   => '/soap/svn/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__) . '/../soap/svn/VERSION'),
            'description' => 'Get informations about Subversion usage in project.',
        ),
        array(
            'title'       => 'Project',
            'wsdl'        => '/soap/project/?wsdl',
            'wsdl_viewer' => '/soap/project/wsdl-viewer.php',
            'changelog'   => '/soap/project/ChangeLog',
            'version'     => file_get_contents(dirname(__FILE__) . '/../soap/project/VERSION'),
            'description' => 'Create and administrate projects.',
        ),
    )
);

$GLOBALS['HTML']->header(array('title' => 'API', 'main_classes' => array('tlp-framed')));
$renderer->renderToPage('api', $presenter);
$GLOBALS['HTML']->footer(array());
