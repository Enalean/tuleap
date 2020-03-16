<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;

/**
 * A class to be able to work with the jenkins server
 */
class Jenkins_Client
{

    public const BUILD_WITH_PARAMETERS_REGEXP = '%(?P<job_url>.*)/buildWithParameters(/|\?).*%';

    /**
     * @var String
     */
    private $token = null;
    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var JenkinsCSRFCrumbRetriever
     */
    private $csrf_crumb_retriever;

    public function __construct(
        ClientInterface $http_client,
        RequestFactoryInterface $request_factory,
        StreamFactoryInterface $stream_factory,
        JenkinsCSRFCrumbRetriever $csrf_crumb_retriever
    ) {
        $this->http_client          = $http_client;
        $this->request_factory      = $request_factory;
        $this->stream_factory       = $stream_factory;
        $this->csrf_crumb_retriever = $csrf_crumb_retriever;
    }

    /**
     * Allow to define token to be used for authentication
     *
     * @param String $token Jenkins authentication token
     *
     * @return Jenkins_Client
     */
    public function setToken($token)
    {
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

        $request = $this->request_factory->createRequest('POST', $this->getBuildUrl($job_url));

        $crumb_header_split = explode(':', $csrf_crumb_header);
        if (count($crumb_header_split) === 2) {
            [$crumb_header_name, $crumb_header_value] = $crumb_header_split;
            $request = $request->withHeader($crumb_header_name, $crumb_header_value);
        }

        if (count($build_parameters) > 0) {
            $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($this->stream_factory->createStream($this->generateBuildParameters($build_parameters)));
        }

        try {
            $response = $this->http_client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new Jenkins_ClientUnableToLaunchBuildException('Job: ' . $job_url . '; Message: ' . $e->getMessage());
        }

        $response_status_code = $response->getStatusCode();
        if ($response_status_code !== 200 && $response_status_code !== 201) {
            throw new Jenkins_ClientUnableToLaunchBuildException('Job: ' . $job_url . '; HTTP Status code ' . $response_status_code .
                ' ' . $response->getReasonPhrase());
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
            $params = $separator . $params;
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

    private function getTokenUrlParameter()
    {
        if ($this->token) {
            return array('token' => $this->token);
        }
        return array();
    }

    /**
     * @param array $build_parameters
     * @return string
     */
    private function generateBuildParameters(array $build_parameters)
    {
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
