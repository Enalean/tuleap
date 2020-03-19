<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../project/admin/permissions.php';

use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Request\RequestInstrumentation;
use Tuleap\REST\BasicAuthentication;
use Tuleap\REST\TuleapRESTAuthentication;
use Tuleap\REST\GateKeeper;
use Luracast\Restler\Restler;
use Luracast\Restler\Explorer\v2\Explorer;
use Luracast\Restler\Defaults;
use Luracast\Restler\Format\JsonFormat;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

$message_factory = \Tuleap\Http\HTTPFactoryBuilder::responseFactory();
$request_handler = new \Tuleap\Http\Server\AlwaysSuccessfulRequestHandler($message_factory);
$cors_middleware = new \Tuleap\REST\TuleapRESTCORSMiddleware();
$minimal_request = new \Tuleap\Http\Server\NullServerRequest();
$response        = $cors_middleware->process($minimal_request, $request_handler);
(new SapiEmitter())->emit($response);

$request_instrumentation = new RequestInstrumentation(Prometheus::instance());

$http_request = HTTPRequest::instance();
try {
    $gate_keeper = new GateKeeper();
    $gate_keeper->assertAccess(UserManager::instance()->getCurrentUser(), $http_request);
} catch (Exception $exception) {
    $request_instrumentation->incrementRest(403);
    header("HTTP/1.0 403 Forbidden");
    $GLOBALS['Response']->sendJSON(array(
        'error' => $exception->getMessage()
    ));
    die();
}

preg_match('/^\/api\/v(\d+)\//', $_SERVER['REQUEST_URI'], $matches);
$version = 1;
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

$restler->onComplete(static function () use ($restler, $request_instrumentation) {
    $request_instrumentation->incrementRest($restler->responseCode);

    if ($restler->exception === null || $restler->responseCode !== 500) {
        return;
    }

    $initial_exception = $restler->exception->getPrevious();
    if ($initial_exception === null) {
        return;
    }
    $logger = \Tuleap\REST\RESTLogger::getLogger();
    $logger->error('Unhandled exception', ['exception' => $initial_exception]);
});

// Do not let Restler find itself the domain, when behind a reverse proxy, it's
// a mess.
$restler->setBaseUrls($http_request->getServerUrl());
$restler->setAPIVersion($version);

$core_resources_injector = new Tuleap\REST\ResourcesInjector();
$core_resources_injector->populate($restler);

EventManager::instance()->processEvent(Event::REST_RESOURCES, array('restler' => $restler));
$restler->addAPIClass('Explorer');

$restler->addAuthenticationClass('\\' . TuleapRESTAuthentication::class);
$restler->addAuthenticationClass('\\' . BasicAuthentication::class);

$restler->handle();
