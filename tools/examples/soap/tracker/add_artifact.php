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
    die('Usage: ".$argv[0]." project_id  tracker_id  value file\n');
}

$serverURL = isset($_SERVER['TULEAP_SERVER']) ? $_SERVER['TULEAP_SERVER'] : 'http://sonde.cro.enalean.com';
$soapLogin = new SoapClient($serverURL.'/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

$login    = isset($_SERVER['TULEAP_USER']) ? $_SERVER['TULEAP_USER'] : 'testman';
$password = isset($_SERVER['TULEAP_PASSWORD']) ? $_SERVER['TULEAP_PASSWORD'] : 'testpwd';

// Establish connection to the server
$requesterSessionHash = $soapLogin->login($login, $password)->session_hash;

//save values
$project_id  = $argv[1];
$tracker_id  = $argv[2];
$summary     = $argv[3];
$file        = $argv[4];

$filesize = filesize($file);
$filename = basename($file);
$filetype = system('file -b --mime-type '.escapeshellarg($file));


// Connecting to the soap's tracker client
$soapTracker = new SoapClient($serverURL.'/plugins/tracker/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

//executing method updateArtefact

// creating temporary file
$uuid = $soapTracker->createTemporaryAttachment($requesterSessionHash);

$value = array(
    array(
        'field_name' => 'summary',
        'field_label' => '',
        'field_value' => array('value' => $summary)
    ),
    array(
        'field_name' => 'attachment',
        'field_label' => '',
        'field_value' => array(
            'file_info' => array(
                array(
                    'id'           => $uuid,
                    'submitted_by' => 0,
                    'description'  => 'description',
                    'filename'     => $filename,
                    'filesize'     => $filesize,
                    'filetype'     => $filetype,
                )
            )
        )
    )
);



$total_written = 0;
$offset        = 0;
$chunk_size    = 20000;
$is_last_chunk = false;
while ($chunk = file_get_contents($file, false, null, $offset, $chunk_size)) {
    $chunk_length  = strlen($chunk);
    $is_last_chunk = $chunk_length < $chunk_size;
    $chunk_written = $soapTracker->appendTemporaryAttachmentChunk($requesterSessionHash, $uuid, base64_encode($chunk));
    if ($chunk_written !== $chunk_length) {
        var_dump("Warning: chunk not completely written on server");
    }
    $total_written += $chunk_written;
    $offset += $chunk_size;
}

if ($total_written == strlen(file_get_contents($file))) {
    var_dump("File successfully uploaded");
}


$response = $soapTracker->addArtifact($requesterSessionHash, $project_id, $tracker_id, $value);
var_dump($response);

?>
