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
     * @var Http_Client
     */
    private $http_curl_client;

    /**
     * @var String
     */
    private $token = null;

    /**
     * @param Http_Client $http_curl_client Any instance of Http_Client
     */
    public function __construct(Http_Client $http_curl_client) {
        $this->http_curl_client = $http_curl_client;
    }

    /**
     * Allow to define token to be used for authentication
     *
     * @param String $token Jenkins authentication token
     *
     * @return Jenkins_Client
     */
    public function setToken($token) {
        $this->token = $token;
        return $this;
    }

    /**
     * @param string $job_url
     * @param array $parameters
     *
     * @throws Tracker_Exception
     */
    public function launchJobBuild($job_url, array $build_parameters = array()) {
        $options = array(
            CURLOPT_URL             => $this->getBuildUrl($job_url),
            CURLOPT_SSL_VERIFYPEER  => false,
        );
        
        if (count($build_parameters) === 0) {
            $options[CURLOPT_HTTPGET] = true;
        } else {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $this->generateBuildParameters($build_parameters);
        }

        $this->http_curl_client->addOptions($options);

        try {
            $this->http_curl_client->doRequest();
        } catch (Http_ClientException $e) {
            throw new Jenkins_ClientUnableToLaunchBuildException('Job: ' . $job_url . '; Message: ' . $e->getMessage());
        }
    }

    private function getBuildUrl($job_url) {
        $params = http_build_query($this->getTokenUrlParameter());
        if ($params) {
            $params = '?'.$params;
        }
        return $job_url . '/build'.$params;
    }

    private function getTokenUrlParameter() {
        if ($this->token) {
            return array('token' => $this->token);
        }
        return array();
    }

    /**
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

        return 'json=' . urlencode(json_encode($parameters));
    }
}

?>
