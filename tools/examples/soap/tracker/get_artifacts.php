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

$host       = 'http://crampons.cro.enalean.com';
$user       = 'admin';
$pass       = 'secret';
$project_id = 0; //not needed
$tracker_id = 270;
$offset     = 0;
$limit      = 10;
$criteria = array(
    array(
        'field_name' => 'details',
        'value' => array('value' => '/^(Update|My Monitor)/')
    ),
    //array(
    //    'field_name' => 'close_date',
    //    'value' => array(
    //        'date' => array('op' => '>', 'to_date' => 1349827200)
    //    )
    //),
    //array(
    //    'field_name' => 'close_date',
    //    'value' => array(
    //        'dateAdvanced' => array(
    //            'from_date' => '1349827200',
    //            'to_date'   => 1350432000
    //        )
    //    )
    //),
    array(
        'field_name' => 'assigned_to',
        'value' => array('value' => '106')
    )
);

$soap_options = array(
    'cache_wsdl' => WSDL_CACHE_NONE,
    'exceptions' => 1,
    'trace'      => 1,
);
$host_login   = $host .'/soap/?wsdl';
$host_tracker = $host .'/plugins/tracker/soap/?wsdl';

// Establish connection to the server
$client_login = new SoapClient($host_login, $soap_options);
$session_hash = $client_login->login($user, $pass)->session_hash;
try {
    // Connecting to the soap's tracker api
    $client_tracker = new SoapClient($host_tracker, $soap_options);
    $response = $client_tracker->getArtifacts(
        $session_hash,
        $project_id,
        $tracker_id,
        $criteria,
        $offset,
        $limit
    );

    var_dump($response);
} catch(Exception $e) {
    echo $e->getMessage();
    echo "\n";
    echo $client_tracker->__getLastResponse();
    echo "\n";
}
echo "\n";
?>
