<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\BrowserDetection\DetectedBrowser;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Request\RequestInstrumentation;
use Tuleap\Templating\TemplateCache;

require_once __DIR__ . '/../include/pre.php';

(new RequestInstrumentation(Prometheus::instance()))->incrementSoap(
    DetectedBrowser::detectFromTuleapHTTPRequest(HTTPRequest::instance())
);

define('CODENDI_WS_API_VERSION', file_get_contents(__DIR__ . '/VERSION'));

// Check if we the server is in secure mode or not.
$request = HTTPRequest::instance();
if ($request->isSecure()) {
    $protocol = "https";
} else {
    $protocol = "http";
}

$default_domain = ForgeConfig::get('sys_default_domain');

$uri = $protocol . '://' . $default_domain;

if ($request->exist('wsdl')) {
    header("Location: " . $uri . "/soap/codendi.wsdl.php?wsdl");
    exit();
}

$event_manager = EventManager::instance();

try {
    $server = new TuleapSOAPServer($uri . '/soap/codendi.wsdl.php?wsdl', ['trace' => 1]);

    require_once __DIR__ .  '/../include/utils_soap.php';
    require_once __DIR__ . '/common/session.php';
    require_once __DIR__ . '/common/group.php';
    require_once __DIR__ . '/common/users.php';
    require_once __DIR__ . '/tracker/tracker.php';
    require_once __DIR__ . '/frs/frs.php';

    // include the <Plugin> API (only if plugin is available)
    $event_manager->processEvent('soap', []);
} catch (Exception $e) {
    header('Content-Type: text/plain', true, 500);
    echo $e->getMessage();
    exit();
}


// if POST was used to send this request, we handle it
// else, we display a list of available methods
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $xml_security = new XML_Security();
    $xml_security->enableExternalLoadOfEntities();
    $server->handle();
    $xml_security->disableExternalLoadOfEntities();
} else {
    site_header(['title' => "SOAP API"]);
    $renderer = new MustacheRenderer(new TemplateCache(), 'templates');
    $renderer->renderToPage('soap_index', [
        'end_points'         => [
            [
                'title'       => 'Core',
                'wsdl'        => '/soap/?wsdl',
                'wsdl_viewer' => '/soap/wsdl.php',
                'changelog'   => '/soap/ChangeLog',
                'version'     => file_get_contents(__DIR__ . '/VERSION'),
                'description' => <<<EOT
Historically the sole end point, therefore it groups multiple different functions:
<ul>
    <li>Session management: login, logout, projects, ...</li>
    <li>File Release System access (FRS): addPackage, addRelease, addFile, ...</li>
    <li>Tracker v3 (for historical deployments): get/updateTracker, get/updateArtifact, ...</li>
    <li>Documentation: get/updateDocman, ...</li>
</ul>
EOT
            ],
            [
                'title'       => 'Subversion',
                'wsdl'        => '/soap/svn/?wsdl',
                'wsdl_viewer' => '/soap/svn/wsdl-viewer.php',
                'changelog'   => '/soap/svn/ChangeLog',
                'version'     => file_get_contents(__DIR__ . '/svn/VERSION'),
                'description' => 'Get informations about Subversion usage in project.',
            ],
            [
                'title'       => 'Project',
                'wsdl'        => '/soap/project/?wsdl',
                'wsdl_viewer' => '/soap/project/wsdl-viewer.php',
                'changelog'   => '/soap/project/ChangeLog',
                'version'     => file_get_contents(__DIR__ . '/project/VERSION'),
                'description' => 'Create and administrate projects.',
            ],
        ]
    ]);
    site_footer([]);
}
