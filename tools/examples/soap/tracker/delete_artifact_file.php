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

if ($argc != 5) {
    die('Usage: ".$argv[0]." project_id  tracker_id artifact_id attachment_id\n');
}

$serverURL = isset($_SERVER['TULEAP_SERVER']) ? $_SERVER['TULEAP_SERVER'] : 'http://sonde.cro.enalean.com';
$soapLogin = new SoapClient($serverURL.'/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

$login    = isset($_SERVER['TULEAP_USER']) ? $_SERVER['TULEAP_USER'] : 'testman';
$password = isset($_SERVER['TULEAP_PASSWORD']) ? $_SERVER['TULEAP_PASSWORD'] : 'testpwd';

// Establish connection to the server
$requesterSessionHash = $soapLogin->login($login, $password)->session_hash;

//save values
$project_id    = $argv[1];
$tracker_id    = $argv[2];
$artifact_id   = $argv[3];
$attachment_id = $argv[4];

// Connecting to the soap's tracker client
$soapTracker = new SoapClient($serverURL.'/plugins/tracker/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

$value = array(
    array(
        'field_name' => 'attachment',
        'field_label' => '',
        'field_value' => array(
            'file_info' => array(
                array(
                    'id'           => $attachment_id,
                    'submitted_by' => 0,
                    'description'  => 'description',
                    'filename'     => '',
                    'filesize'     => '',
                    'filetype'     => '',
                    'action'       => 'delete'
                )
            )
        )
    )
);

$response = $soapTracker->updateArtifact($requesterSessionHash, $project_id, $tracker_id, $artifact_id, $value, 'One comment', 0);
var_dump("successfully deleted");
var_dump($response);

?>
