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

if ($argc != 5) {
    die("Usage: ".$argv[0]." shortName publicName privacy templateId\n");
}

$serverURL = isset($_SERVER['TULEAP_SERVER']) ? $_SERVER['TULEAP_SERVER'] : 'http://sonde.cro.enalean.com';
$login     = isset($_SERVER['TULEAP_USER']) ? $_SERVER['TULEAP_USER'] : 'sandrae';
$password  = isset($_SERVER['TULEAP_PASSWORD']) ? $_SERVER['TULEAP_PASSWORD'] : 'sandrae';

$soapLogin = new SoapClient($serverURL.'/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

$requesterSessionHash = $soapLogin->login($login, $password)->session_hash;
try {
    $adminSessionHash = $soapLogin->login('admin', 'siteadmin')->session_hash;
} catch (Exception $ex) {
    $adminSessionHash = '';
}

$short_name   = $argv[1];
$public_name  = $argv[2];
$privacy      = $argv[3];
$template_id  = $argv[4];

$soapProject = new SoapClient($serverURL.'/soap/project/?wsdl',
                              array('cache_wsdl' => WSDL_CACHE_NONE));

$response = $soapProject->addProject($requesterSessionHash, $adminSessionHash, $short_name, $public_name, $privacy, $template_id);
var_dump($response);

$soapLogin->logout($requesterSessionHash);

?>
