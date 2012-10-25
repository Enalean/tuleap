<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
    die("Usage: ".$argv[0]." group_id tracker_id \n");
}

$serverUrl = 'http://sonde.cro.enalean.com';

// Establish connexion to the server
$soapLogin = new SoapClient($serverUrl.'/soap/?wsdl',
                            array('cache_wsdl' => WSDL_CACHE_NONE));


$requesterSessionHash = $soapLogin->login('admin', 'siteadmin')->session_hash;

$group_id   = $argv[1];
$tracker_id = $argv[2];
    
$client_tracker_v5 = new SoapClient($serverUrl.'/plugins/tracker/soap/?wsdl',
                                    array('cache_wsdl' => WSDL_CACHE_NONE));

echo '<h1>Get semantic of tracker ' . $tracker_id . '</h1>';
echo '<h3>function getTrackerSemantic</h3>';

try {
    $trackerlist = $client_tracker_v5->getTrackerSemantic($requesterSessionHash, $group_id, $tracker_id);
    var_dump($trackerlist);
    
} catch (SoapFault $fault) {
   var_dump($fault->getMessage());
}

$soapLogin->logout($requesterSessionHash);
?>
