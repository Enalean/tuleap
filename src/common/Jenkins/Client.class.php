<?php
/**
 * Copyright (c) Enalean, 2012-2017. All Rights Reserved.
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

use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;

require_once 'common/Http/Client.class.php';
require_once 'ClientUnableToLaunchBuildException.class.php';

/**
 * A class to be able to work with the jenkins server
 */
class Jenkins_Client {

    const BUILD_WITH_PARAMETERS_REGEXP = '%(?P<job_url>.*)/buildWithParameters(/|\?).*%';

    /**
     * @var Http_Client
     */
    private $http_curl_client;

    /**
     * @var String
     */
    private $token = null;
    /**
     * @var JenkinsCSRFCrumbRetriever
     */
    private $csrf_crumb_retriever;

    /**
     * @param Http_Client $http_curl_client Any instance of Http_Client
     */
    public function __construct(Http_Client $http_curl_client, JenkinsCSRFCrumbRetriever $csrf_crumb_retriever)
    {
        $this->http_curl_client     = $http_curl_client;
        $this->csrf_crumb_retriever = $csrf_crumb_retriever;
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
     * @throws Jenkins_ClientUnableToLaunchBuildException
     */
    public function launchJobBuild($job_url, array $build_parameters = array())
    {
        $server_url        = $this->getServerUrl($job_url);
        $csrf_crumb_header = $this->csrf_crumb_retriever->getCSRFCrumbHeader($server_url);

        $options = array(
            CURLOPT_URL             => $this->getBuildUrl($job_url),
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_POST            => true,
            CURLOPT_HTTPHEADER      => array($csrf_crumb_header)
        );
        
        if (count($build_parameters) > 0) {
            $options[CURLOPT_POSTFIELDS] = $this->generateBuildParameters($build_parameters);
        }

        $this->http_curl_client->addOptions($options);

        try {
            $this->http_curl_client->doRequest();
        } catch (Http_ClientException $e) {
            throw new Jenkins_ClientUnableToLaunchBuildException('Job: ' . $job_url . '; Message: ' . $e->getMessage());
        }
    }

    private function getBuildUrl($job_url)
    {
        if (mb_substr($job_url, -1) === '/') {
            $job_url = mb_substr($job_url, 0, -1);
        }
        $params = http_build_query($this->getTokenUrlParameter());
        if (preg_match(self::BUILD_WITH_PARAMETERS_REGEXP, $job_url)) {
            $separator = '&';
        } else {
            $job_url   = $job_url . '/build';
            $separator = '?';
        }
        if ($params) {
            $params = $separator.$params;
        }
        return $job_url . $params;
    }

    /**
     * @return string
     * @throws Jenkins_ClientUnableToLaunchBuildException
     */
    private function getServerUrl($job_url)
    {
        $server_url = mb_strstr($job_url, '/job', true);
        if ($server_url === false) {
            throw new Jenkins_ClientUnableToLaunchBuildException("Job URL $job_url does not seem to be a valid job URL");
        }
        return $server_url;
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

        return 'json=' . json_encode($parameters);
    }
}
