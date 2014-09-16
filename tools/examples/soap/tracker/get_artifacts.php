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

$serverURL = isset($_SERVER['TULEAP_SERVER']) ? $_SERVER['TULEAP_SERVER'] : 'http://foudre';
$login     = isset($_SERVER['TULEAP_USER']) ? $_SERVER['TULEAP_USER'] : 'michelle';
$password  = isset($_SERVER['TULEAP_PASSWORD']) ? $_SERVER['TULEAP_PASSWORD'] : 'welcome0';

$project_id = 0; //not needed
$tracker_id = 52;
$offset     = 0;
$limit      = 100;
$criteria = array(
//    array(
//        'field_name' => 'description',
//        'value' => array('value' => '/^(Choose|Integrate)/')
//    ),
//    array(
//        'field_name' => 'submitted_on',
//        'value' => array(
//            'date' => array('op' => '>', 'to_date' => mktime(0,0,0,10,1,2012))
//        )
//    ),
    array(
        'field_name' => 'open_date',
        'value' => array(
            'dateAdvanced' => array(
                'from_date' =>  mktime(0,0,0,9,4,2014),
                'to_date'   =>  mktime(1,0,0,9,5,2014)
            )
        )
    ),
//    array(
//        'field_name' => 'status',
//        'value' => array('value' => '4731')
//    )
);

$soap_options = array(
    'cache_wsdl' => WSDL_CACHE_NONE,
    'exceptions' => 1,
    'trace'      => 1,
);
$host_login   = $serverURL .'/soap/?wsdl';
$host_tracker = $serverURL .'/plugins/tracker/soap/?wsdl';

// Establish connection to the server
$client_login = new SoapClient($host_login, $soap_options);
$session_hash = $client_login->login($login, $password)->session_hash;
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
    
    echo "total_artifacts_number: ".$response->total_artifacts_number."\n";
    foreach ($response->artifacts as $artifact) {
        $message = "#".$artifact->artifact_id;
        foreach ($artifact->value as $value) {
            if ((string)$value->field_name == "description") {
                $message .= " ".(string)$value->field_value;
            }
            if ((string)$value->field_name == "remaining_effort" ) {
                $message .= " (".(string)$value->field_label.": ".(string)$value->field_value->value.")";
            }
        }
        echo $message."\n";
    }
    
} catch(Exception $e) {
    echo $e->getMessage();
    echo "\n";
    echo $client_tracker->__getLastResponse();
    echo "\n";
}

?>
