<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Jenkins;

class JenkinsCSRFCrumbRetriever
{
    const CRUMB_ISSUER_PATH = '/crumbIssuer/api/xml';

    /**
     * @var \Http_Client
     */
    private $http_client;

    public function __construct(\Http_Client $http_client)
    {
        $this->http_client = $http_client;
    }

    /**
     * @return string
     */
    public function getCSRFCrumbHeader($jenkins_server_url)
    {
        if (mb_substr($jenkins_server_url, -1) === '/') {
            $jenkins_server_url = mb_substr($jenkins_server_url, 0, -1);
        }

        $url_parameters = array('xpath' => 'concat(//crumbRequestField,":",//crumb)');

        $csrf_crumb_retriever_url = $jenkins_server_url . self::CRUMB_ISSUER_PATH . '?' . http_build_query($url_parameters);

        $curl_options = array(
            CURLOPT_URL            => $csrf_crumb_retriever_url,
            CURLOPT_RETURNTRANSFER => true
        );
        $this->http_client->addOptions($curl_options);

        try {
            $this->http_client->doRequest();
        } catch (\Http_ClientException $ex) {
            return '';
        }

        return $this->http_client->getLastResponse();
    }
}
