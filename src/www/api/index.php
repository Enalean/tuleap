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

require_once 'pre.php';
require_once '/usr/share/restler/vendor/restler.php';
require_once 'common/REST/BasicAuthentication.class.php';

use Luracast\Restler\Restler;
use Luracast\Restler\Resources;
use Luracast\Restler\Defaults;

// Do not put .json at the end of the resource
Resources::$useFormatAsExtension = false;

// Use /api/v1/projects uri
Defaults::$useUrlBasedVersioning = true;

$r = new Restler();
// comment the line above and uncomment the line below for production mode
// $r = new Restler(true);

$r->setAPIVersion(file_get_contents(__DIR__ .'/VERSION'));
$r->addAPIClass('\\Tuleap\\Project\\REST\\ProjectResource', 'projects');
EventManager::instance()->processEvent(Event::REST_RESOURCES, array('restler' => $r));
$r->addAPIClass('Resources');

$r->addAuthenticationClass('\\Tuleap\\REST\\BasicAuthentication');
$r->handle();
