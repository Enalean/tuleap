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
// format : project_id  tracker_id  value

if ($argc < 4) {
    die('Usage: ".$argv[0]." project_id  tracker_id  value \n');
}

$serverURL = 'http://sonde.cro.enalean.com';
$soapLogin = new SoapClient($serverURL.'/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

// Establish connection to the server
$requesterSessionHash = $soapLogin->login('testman','testpwd')->session_hash;

//save values
$project_id  = $argv[1];
$tracker_id  = $argv[2];
$value       = array(
    array(
        'field_name' => 'title',
        'field_label' => 'title',
        'field_value' => $argv[3]
    ),
    
    array(
        'field_name' => 'start',
        'field_label' => 'start',
        'field_value' => date("Y-m-d")
    ),
    
    array(
        'field_name' => 'end',
        'field_label' => 'end',
        'field_value' => date("Y-m-d")
    )
);

// Connecting to the soap's tracker client
$soapTracker = new SoapClient($serverURL.'/plugins/tracker/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

//executing method updateArtefact

$response = $soapTracker->addArtifact($requesterSessionHash, $project_id, $tracker_id, $value);

var_dump($response);

?>
