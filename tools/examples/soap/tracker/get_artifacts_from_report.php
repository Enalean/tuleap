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

$host     = getenv('TULEAP_SERVER')   ? getenv('TULEAP_SERVER')   : 'http://sonde.cro.enalean.com';
$login    = getenv('TULEAP_USER')     ? getenv('TULEAP_USER')     : 'testman';
$password = getenv('TULEAP_PASSWORD') ? getenv('TULEAP_PASSWORD') : 'testpwd';

$report_id  = $argv[1];
$offset     = 0;
$limit = 10;
$soap_options = array(
    'cache_wsdl' => WSDL_CACHE_NONE,
    'exceptions' => 1,
    'trace'      => 1,
);
$host_login   = $host .'/soap/?wsdl';
$host_tracker = $host .'/plugins/tracker/soap/?wsdl';

// Establish connection to the server
$client_login = new SoapClient($host_login, $soap_options);
$session_hash = $client_login->login($login, $password)->session_hash;
try {
    // Connecting to the soap's tracker api
    $client_tracker = new SoapClient($host_tracker, $soap_options);
    $response = $client_tracker->getArtifactsFromReport(
        $session_hash,
        $report_id,
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
            if ((string)$value->field_name == "remaining_effort" && (string)$value->field_value) {
                $message .= " (".(string)$value->field_label.": ".(string)$value->field_value.")";
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

$client_login->logout($session_hash);
?>
