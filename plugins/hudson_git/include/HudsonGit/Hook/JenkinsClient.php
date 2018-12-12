<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Hook;

use Tuleap\HudsonGit\PollingResponseFactory;
use Http_Client;
use Http_ClientException;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;

class JenkinsClient
{

    private static $NOTIFY_URL = '/git/notifyCommit';

    /**
     * @var PollingResponseFactory
     */
    private $response_factory;

    /**
     * @var Http_Client
     */
    private $http_curl_client;
    /**
     * @var JenkinsCSRFCrumbRetriever
     */
    private $csrf_crumb_retriever;

    /**
     * @param Http_Client $http_curl_client Any instance of Http_Client
     */
    public function __construct(
        Http_Client $http_curl_client,
        PollingResponseFactory $response_factory,
        JenkinsCSRFCrumbRetriever $csrf_crumb_retriever
    ) {
        $this->http_curl_client     = $http_curl_client;
        $this->response_factory     = $response_factory;
        $this->csrf_crumb_retriever = $csrf_crumb_retriever;
    }

    public function pushGitNotifications($server_url, $repository_url)
    {
        $csrf_crumb_header = $this->csrf_crumb_retriever->getCSRFCrumbHeader($server_url);

        if (mb_substr($server_url, -1) === '/') {
            $server_url = mb_substr($server_url, 0, -1);
        }
        $push_url = $server_url . self::$NOTIFY_URL . '?url=' . urlencode($repository_url);

        $options  = array(
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_POST            => true,
            CURLOPT_HEADER          => true,
            CURLOPT_URL             => $push_url,
            CURLOPT_HTTPHEADER      => array($csrf_crumb_header)
        );

        $this->http_curl_client->addOptions($options);

        try {
            $this->http_curl_client->doRequest();

            $response     = $this->http_curl_client->getLastResponse();
            $header_size  = $this->http_curl_client->getInfo(CURLINFO_HEADER_SIZE);

            return $this->response_factory->buildResponseFormCurl($response, $header_size);
        } catch (Http_ClientException $exception) {
            throw new UnableToLaunchBuildException('pushGitNotifications: ' . $push_url . '; Message: ' . $exception->getMessage());
        }
    }
}
