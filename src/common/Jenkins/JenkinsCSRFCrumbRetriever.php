<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class JenkinsCSRFCrumbRetriever
{
    private const CRUMB_ISSUER_PATH = '/crumbIssuer/api/xml';

    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;

    public function __construct(ClientInterface $http_client, RequestFactoryInterface $request_factory)
    {
        $this->http_client     = $http_client;
        $this->request_factory = $request_factory;
    }

    public function getCSRFCrumbHeader($jenkins_server_url): string
    {
        if (mb_substr($jenkins_server_url, -1) === '/') {
            $jenkins_server_url = mb_substr($jenkins_server_url, 0, -1);
        }

        $url_parameters = array('xpath' => 'concat(//crumbRequestField,":",//crumb)');

        $csrf_crumb_retriever_url = $jenkins_server_url . self::CRUMB_ISSUER_PATH . '?' . http_build_query($url_parameters);

        $request = $this->request_factory->createRequest('GET', $csrf_crumb_retriever_url);

        try {
            $response = $this->http_client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return '';
        }

        if ($response->getStatusCode() !== 200) {
            return '';
        }

        return $response->getBody()->getContents();
    }
}
