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

if ($argc < 7) {
    die("Usage: ".$argv[0]." project_id package_id release_id notes changes status_id");
}

$project_id = $argv[1];
$package_id = $argv[2];
$release_id = $argv[3];
$notes      = $argv[4];
$changes    = $argv[5];
$status_id  = $argv[6];

$server_url = isset($_SERVER['TULEAP_SERVER']) ? $_SERVER['TULEAP_SERVER'] : 'http://sonde.cro.enalean.com';
$login     = isset($_SERVER['TULEAP_USER']) ? $_SERVER['TULEAP_USER'] : 'testman';
$password  = isset($_SERVER['TULEAP_PASSWORD']) ? $_SERVER['TULEAP_PASSWORD'] : 'testpwd';


$soapLogin = new SoapClient($server_url.'/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

// Establish connection to the server
$requesterSessionHash = $soapLogin->login($login, $password)->session_hash;

$svn_client = new SoapClient($server_url.'/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

var_dump($svn_client->updateRelease(
    $requesterSessionHash,
    $project_id,
    $package_id,
    $release_id,
    $notes,
    $changes,
    $status_id
    )
);

$soapLogin->logout($requesterSessionHash);

?>