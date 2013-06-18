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

/*if ($argc < 2) {
    die("Usage: ".$argv[0]." projectId member_1 [member_2] [...]\n");
}*/

$serverUrl = 'http://ibex.cro.enalean.com';

// Establish connexion to the server
$soapLogin = new SoapClient($serverUrl.'/soap/?wsdl', 
                            array('cache_wsdl' => WSDL_CACHE_NONE));

$requesterSessionHash     = $soapLogin->login('admin', 'siteadmin')->session_hash;

$soapProject = new SoapClient($serverUrl.'/soap/project/?wsdl', 
                              array('cache_wsdl' => WSDL_CACHE_NONE));

$prjId = 103;
$pwd = 'hjmltfk';
var_dump($soapProject->setProjectGenericUser($requesterSessionHash, $prjId, $pwd));


$soapLogin->logout($requesterSessionHash);

?>