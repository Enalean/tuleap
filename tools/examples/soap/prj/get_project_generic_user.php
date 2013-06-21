<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
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

if ($argc < 2) {
    die("Usage: ".$argv[0]." projectId\n");
}

$serverUrl = isset($_SERVER['TULEAP_SERVER']) ? $_SERVER['TULEAP_SERVER'] : 'http://chaussette.cro.enalean.com';
$login     = isset($_SERVER['TULEAP_USER']) ? $_SERVER['TULEAP_USER'] : 'sandrae';
$password  = isset($_SERVER['TULEAP_PASSWORD']) ? $_SERVER['TULEAP_PASSWORD'] : 'sandrae';

// Establish connexion to the server
$soapLogin = new SoapClient($serverUrl.'/soap/?wsdl', 
                            array('cache_wsdl' => WSDL_CACHE_NONE));

$requesterSessionHash     = $soapLogin->login($login, $password)->session_hash;

$soapProject = new SoapClient($serverUrl.'/soap/project/?wsdl', 
                              array('cache_wsdl' => WSDL_CACHE_NONE));

var_dump($soapProject->getProjectGenericUser($requesterSessionHash, $argv[1]));


$soapLogin->logout($requesterSessionHash);

?>