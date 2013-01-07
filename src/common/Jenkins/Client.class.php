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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'common/Http/Client.class.php';
require_once 'ClientUnableToLaunchBuildException.class.php';

/**
 * A class to be able to work with the jenkins server
 */
class Jenkins_Client {

    /**
     *
     * @var Http_Client
     */
    private $http_curl_client;

    /**
     *
     * @param Http_Client $http_curl_client Any instance of Http_Client
     */
    public function __construct(Http_Client $http_curl_client) {
        $this->http_curl_client = $http_curl_client;
    }

    /**
     *
     * @param string $job_name
     * @param array $options curl options
     * @throws Tracker_Exception
     */
    public function launchJobBuild($job_url, array $build_parameters = array()) {
        $url = $job_url . '/build?json=' . $this->generateBuildParameters($build_parameters);
        $options = array(
            CURLOPT_HTTPGET         => true,
            CURLOPT_URL             => $url,
            CURLOPT_SSL_VERIFYPEER  => false,
        );
        $this->http_curl_client->addOptions($options);

        try {
            $this->http_curl_client->doRequest();
        } catch (Http_ClientException $e) {
            throw new Jenkins_ClientUnableToLaunchBuildException('Job: ' . $job_url . '; Message: ' . $e->getMessage());
        }
    }
    
    /**
     * 
     * @param array $build_parameters
     * @return string
     */
    private function generateBuildParameters(array $build_parameters) {
        $parameters = array();
        foreach ($build_parameters as $name => $value) {
            $parameters['parameter'][] = array(
                'name'  => $name,
                'value' => $value
            );
        }

        return urlencode(json_encode($parameters));
    }
}

?>
