<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'pre.php';
require_once 'Tracker/SOAPServer.class.php';

$soap_server = new Tracker_SOAPServer(
    UserManager::instance(),
    TrackerFactory::instance(),
    PermissionsManager::instance(),
    new Tracker_ReportDao(),
    Tracker_FormElementFactory::instance()
);

$criteria = array(
    array(
        'name' => 'remaining_effort',
        'value' => '>=5'
    )
);

$group_id = $offset = $max_rows = 0;

$res = $soap_server->getArtifacts('f3bc736bcf98a5e78947cc605e5d22f0', $group_id, 276, $criteria, $offset, $max_rows);
var_dump($res);

?>