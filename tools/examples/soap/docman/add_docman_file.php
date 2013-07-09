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

if ($argc != 3) {
    die('Usage: ".$argv[0]." project_id  tracker_id  summary file\n');
}

$serverURL = getenv('TULEAP_SERVER')   ? getenv('TULEAP_SERVER')   : 'http://valid2.cro.enalean.com';
$login     = getenv('TULEAP_USER')     ? getenv('TULEAP_USER')     : 'admin';
$password  = getenv('TULEAP_PASSWORD') ? getenv('TULEAP_PASSWORD') : 'siteadmin';

$soapLogin = new SoapClient($serverURL.'/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

// Establish connection to the server
$requesterSessionHash = $soapLogin->login($login, $password)->session_hash;

//save values
$project_id  = $argv[1];

$file        = $argv[2];

$filesize = filesize($file);
$filename = basename($file);




$offset        = 0;
$chunk_size    = 20000;
//while ($chunk = file_get_contents($file, false, null, $offset, $chunk_size)) {
//    $chunk_length  = strlen($chunk);
//    $is_last_chunk = $chunk_length < $chunk_size;
//    if ($chunk_written !== $chunk_length) {
//        var_dump("Warning: chunk not completely written on server");
//    }
//    $total_written += $chunk_written;
//    $offset += $chunk_size;
//}

$raw_content = file_get_contents($file, false, null, $offset, $chunk_size);
$content = base64_encode($raw_content);
//
$response = $soapLogin->createDocmanFile($requesterSessionHash, $project_id, 654, 'Test from SOAP',
        'description', 'begin', 'approved', '',
        '', '', $filesize, $filename, '',
        $content, '', '', 'admin', '', '', '', '');

var_dump($response);

?>
