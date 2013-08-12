<?php

/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

if ($argc < 3) {
    die("Usage: search_docman_item.php project_id item_id \n");
}

$serverURL = getenv('TULEAP_SERVER')   ? getenv('TULEAP_SERVER')   : 'http://valid2.cro.enalean.com';
$login     = getenv('TULEAP_USER')     ? getenv('TULEAP_USER')     : 'admin';
$password  = getenv('TULEAP_PASSWORD') ? getenv('TULEAP_PASSWORD') : 'siteadmin';

$soapLogin = new SoapClient($serverURL.'/soap/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

// Establish connection to the server
$requesterSessionHash = $soapLogin->login($login, $password)->session_hash;

//save values
$project_id  = $argv[1];
$item_id     = $argv[2];
$criterias   = array(
    array(
        'field_name'  => 'update_date',
        'field_value' => '2013-08-01',
        'operator'    => '>='
    )
);

//
$response = $soapLogin->searchDocmanItem($requesterSessionHash, $project_id, $item_id, $criterias);

var_dump($response);

?>
