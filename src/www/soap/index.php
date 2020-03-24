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

use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Request\RequestInstrumentation;
use Tuleap\Templating\TemplateCache;

require_once __DIR__ . '/../include/pre.php';

(new RequestInstrumentation(Prometheus::instance()))->incrementSoap();

define('CODENDI_WS_API_VERSION', file_get_contents(dirname(__FILE__) . '/VERSION'));

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
    $server = new TuleapSOAPServer($uri . '/soap/codendi.wsdl.php?wsdl', array('trace' => 1));

    require_once __DIR__ .  '/../include/utils_soap.php';
    require_once __DIR__ . '/common/session.php';
    require_once __DIR__ . '/common/group.php';
    require_once __DIR__ . '/common/users.php';
    require_once __DIR__ . '/tracker/tracker.php';
    require_once __DIR__ . '/frs/frs.php';

    // include the <Plugin> API (only if plugin is available)
    $event_manager->processEvent('soap', array());
} catch (Exception $e) {
    echo $e;
}


// if POST was used to send this request, we handle it
// else, we display a list of available methods
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $xml_security = new XML_Security();
    $xml_security->enableExternalLoadOfEntities();
    $server->handle();
    $xml_security->disableExternalLoadOfEntities();
} else {
    site_header(array('title' => "SOAP API"));
    $renderer = new MustacheRenderer(new TemplateCache(), 'templates');
    $renderer->renderToPage('soap_index', array());
    site_footer(array());
}
