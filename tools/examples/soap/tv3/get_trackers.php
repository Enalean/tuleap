<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

if ($argc < 2) {
    die("Usage: ".$argv[0]." project_id \n");
}

$serverURL = isset($_SERVER['TULEAP_SERVER']) ? $_SERVER['TULEAP_SERVER'] : 'http://tuleap-aio-dev.dev.nala';
$login     = isset($_SERVER['TULEAP_USER']) ? $_SERVER['TULEAP_USER'] : 'testman';
$password  = isset($_SERVER['TULEAP_PASSWORD']) ? $_SERVER['TULEAP_PASSWORD'] : 'welcome0';

$soapLogin = new SoapClient(
    $serverURL.'/soap/?wsdl',
    array('cache_wsdl' => WSDL_CACHE_NONE)
);

// Establish connection to the server
$requesterSessionHash = $soapLogin->login($login, $password)->session_hash;

//save values
$project_id = $argv[1];

// Connecting to the soap's tracker client
$soapTrackerv3 = new SoapClient(
    $serverURL.'/soap/?wsdl',
    array('cache_wsdl' => WSDL_CACHE_NONE)
);

$response = $soapTrackerv3->getTrackerList(
    $requesterSessionHash,
    $project_id
);

var_dump($response);