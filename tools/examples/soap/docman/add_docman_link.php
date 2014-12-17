<?php

/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

if ($argc != 2) {
    die("Usage: ".$argv[0]." project_id \n");
}

$serverURL = getenv('TULEAP_SERVER')   ? getenv('TULEAP_SERVER')   : 'http://my..tuleap.example.com';
$login     = getenv('TULEAP_USER')     ? getenv('TULEAP_USER')     : 'admin';
$password  = getenv('TULEAP_PASSWORD') ? getenv('TULEAP_PASSWORD') : 'siteadmin';

$soapLogin = new SoapClient($serverURL.'/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

// Establish connection to the server
$requesterSessionHash = $soapLogin->login($login, $password)->session_hash;

//save values
$project_id  = $argv[1];

$root_id = $soapLogin->getRootFolder($requesterSessionHash, $project_id);

$response = $soapLogin->createDocmanLink($requesterSessionHash, $project_id, $root_id, 'Test link from SOAP',
        'description', 'begin', 'approved', '',
        'http://tuleap.org', '','', $login, '', '');

var_dump($response);
