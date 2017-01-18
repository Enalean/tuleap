<?php
/**
 * Copyright (c) Enalean, 2013 - 2015. All Rights Reserved.
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

define('IS_SCRIPT', true);

require_once 'pre.php';
require_once 'www/project/admin/permissions.php';
require_once '/usr/share/restler/vendor/restler.php';

use Tuleap\REST\GateKeeper;
use Luracast\Restler\Restler;
use Luracast\Restler\Explorer;
use Luracast\Restler\Defaults;
use Luracast\Restler\Format\JsonFormat;

try {
    $gate_keeper = new GateKeeper();
    $gate_keeper->assertAccess(UserManager::instance()->getCurrentUser(), HTTPRequest::instance());
} catch (Exception $exception) {
    header("HTTP/1.0 403 Forbidden");
    $GLOBALS['Response']->sendJSON(array(
        'error' => $exception->getMessage()
    ));
    die();
}

Tuleap\Instrument\Collect::increment('service.api.rest.accessed');

preg_match('/^\/api\/v(\d+)\//', $_SERVER['REQUEST_URI'], $matches);
$version = floor(file_get_contents(__DIR__ .'/VERSION'));
if ($matches && isset($matches[1]) && $matches[1] == 2) {
    $version = 2;
}

// Do not put .json at the end of the resource
Explorer::$useFormatAsExtension = false;

//Do not hide the API
Explorer::$hideProtected = false;
// Use /api/v1/projects uri
Defaults::$useUrlBasedVersioning = true;

// Do not unescape unicode or it will break the api (see request #9162)
JsonFormat::$unEscapedUnicode = false;

if (ForgeConfig::get('DEBUG_MODE')) {
    $restler = new Restler(false, true);
    $restler->setSupportedFormats('JsonFormat', 'XmlFormat', 'HtmlFormat');
} else {
    $restler_cache = new RestlerCache();
    Defaults::$cacheDirectory = $restler_cache->getAndInitiateCacheDirectory($version);
    $restler = new Restler(true, false);
    $restler->setSupportedFormats('JsonFormat', 'XmlFormat');
}

// Do not let Restler find itself the domain, when behind a reverse proxy, it's
// a mess.
$restler->setBaseUrl($sys_default_domain);
$restler->setAPIVersion($version);

$core_resources_injector = new Tuleap\REST\ResourcesInjector();
$core_resources_injector->populate($restler);

switch ($version) {
    case 2:
        $event = Event::REST_RESOURCES_V2;
        break;
    default:
        $event = Event::REST_RESOURCES;
        break;
}

EventManager::instance()->processEvent($event, array('restler' => $restler));
$restler->addAPIClass('Explorer');

$restler->addAuthenticationClass('\\Tuleap\\REST\\TokenAuthentication');
$restler->addAuthenticationClass('\\Tuleap\\REST\\BasicAuthentication');
$restler->handle();
