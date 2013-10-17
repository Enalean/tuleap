<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

if (! session_issecure() && ! Config::get('DEBUG_MODE')) {
    header("HTTP/1.0 403 Forbidden");
    $GLOBALS['Response']->sendJSON(array(
        'error' => 'The API is only accessible over HTTPS.'
    ));
    die();
}

require_once '/usr/share/restler/vendor/restler.php';

use Luracast\Restler\Restler;
use Luracast\Restler\Resources;
use Luracast\Restler\Defaults;

// Do not put .json at the end of the resource
Resources::$useFormatAsExtension = false;

// Use /api/v1/projects uri
Defaults::$useUrlBasedVersioning = true;

$restler = new Restler();
// comment the line above and uncomment the line below for production mode
// $restler = new Restler(true);

$restler->setAPIVersion(file_get_contents(__DIR__ .'/VERSION'));

$core_resources_injector = new Tuleap\REST\ResourcesInjector();
$core_resources_injector->populate($restler);

EventManager::instance()->processEvent(Event::REST_RESOURCES, array('restler' => $restler));
$restler->addAPIClass('Resources');

$restler->addAuthenticationClass('\\Tuleap\\REST\\TokenAuthentication');
$restler->handle();
