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

// format : project_id  tracker_id  artifact_id value [comment]

if ($argc < 1) {
    die('Usage: ".$argv[0]." artifact_id attachement_id'.PHP_EOL);
}

$serverURL = isset($_SERVER['TULEAP_SERVER']) ? $_SERVER['TULEAP_SERVER'] : 'http://sonde.cro.enalean.com';
$login     = isset($_SERVER['TULEAP_USER']) ? $_SERVER['TULEAP_USER'] : 'testman';
$password  = isset($_SERVER['TULEAP_PASSWORD']) ? $_SERVER['TULEAP_PASSWORD'] : 'testpwd';

$soapLogin = new SoapClient($serverURL.'/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

// Establish connection to the server
$requesterSessionHash = $soapLogin->login($login, $password)->session_hash;

//save values
$artifact_id   = $argv[1];
$attachment_id = $argv[2];
$offset = 0;
$size = 2000;
$filename = 'unknown_file';

// Connecting to the soap's tracker client
$soapTracker = new SoapClient($serverURL.'/plugins/tracker/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

// Brute force search of attachement name based on ID
$artifact = $soapTracker->getArtifact($requesterSessionHash, '', '', $artifact_id);
foreach ($artifact->value as $field_value) {
    if (isset($field_value->field_value->file_info)) {
        foreach ($field_value->field_value->file_info as $file_info) {
            if ((int)$file_info->id == $attachment_id) {
                $filename = (string)$file_info->filename;
            }
        }
    }
}

while($response = $soapTracker->getArtifactAttachmentChunk($requesterSessionHash, $artifact_id, $attachment_id, $offset, $size)) {
    file_put_contents($filename, base64_decode($response), FILE_APPEND);
    $offset += $size;
}

?>
