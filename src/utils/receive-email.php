<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'Mail/mimeDecode.php';

// Get email from stdin
$email = file_get_contents('php://stdin');

// Cut email in 2 pieces: headers and body
$endOfHeaders = strpos($email, "\n\n");
$headers = substr($email, 0, $endOfHeaders+1);
$body = substr($email, $endOfHeaders+2); 

// Process headers
$decoder = new Mail_mimeDecode($email);
$structure = $decoder->decode(array('include_bodies' => false,
                                    'decode_headers' => false));
//var_dump($structure);

// Prepare headers to send mail
$sendHeaders = "From: Tuleap <noreply@tuleap.net>\r\n";

if (isset($structure->headers['mime-version'])) {
    $sendHeaders .= 'MIME-Version: '.$structure->headers['mime-version']."\r\n";
}
if (isset($structure->headers['content-type'])) {
    $sendHeaders .= 'Content-Type: '.$structure->headers['content-type']."\r\n";
}
if (isset($structure->headers['content-transfer-encoding'])) {
    $sendHeaders .= 'Content-Transfer-Encoding: '.$structure->headers['content-transfer-encoding']."\r\n";
}

//mail("manuel.vacelet@enalean.com", $structure->headers['subject'], $body, $sendHeaders);
//mail("manuel.vacelet@st.com", $structure->headers['subject'], $body, $sendHeaders);



// V3
// mostly fonctionnal but doesn't work with headers on 2 lines
/*
$sendHeaders = "From: Tuleap <noreply@tuleap.net>\r\n";
$endOfHeaders = strpos($email, "\n\n");
$headers = substr($email, 0, $endOfHeaders+1);
if (preg_match('/^(MIME-Version:.*\n)/mi', $headers, $matches)) {
    $sendHeaders .= $matches[0];
}
if (preg_match('/^(Content-Type:.*\n)/mi',  $headers, $matches)) {
    $sendHeaders .= $matches[0];
}
if (preg_match('/^(Content-Transfer-Encoding:.*\n)/mi',  $headers, $matches)) {
    $sendHeaders .= $matches[0];
}
if (preg_match('/^Subject:(.*)\n/mi', $headers, $matches)) {
    $subject = trim($matches[1]);
}
echo $headers;
$body = substr($email, $endOfHeaders+2); 
//echo $body;
var_dump($sendHeaders);
//mail("manuel.vacelet@enalean.com", $subject, $body, $sendHeaders);
*/



//Functionnal
// All headers
/*
$endOfHeaders = strpos($email, "\n\n");
$headers = substr($email, 0, $endOfHeaders+1);
$headers = preg_replace("/^From.*\n/mi", "", $headers);
$headers = preg_replace("/^Date:.*\n/mi", "", $headers);
$headers = preg_replace("/^To:.*\n/mi", "", $headers);
$headers = preg_replace("/^message-id:.*\n/mi", "", $headers);
$headers = preg_replace("/^return-path:.*\n/mi", "", $headers);
$headers = preg_replace("/^x-.*:.*\n/mi", "", $headers);
echo $headers;
$body = substr($email, $endOfHeaders+2); 
//echo $body;

mail("manuel.vacelet@enalean.com", "Bla", $body, $headers);
*/


/*
echo "Posix effective uid: ".posix_geteuid()."\n";
echo "Posix real uid     : ".posix_getuid()."\n";

echo "Posix effective gid: ".posix_getegid()."\n";
echo "Posix real gid     : ".posix_getgid()."\n";
*/
?>