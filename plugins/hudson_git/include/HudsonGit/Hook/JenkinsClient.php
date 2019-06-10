<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Tuleap\HudsonGit\PollingResponse;
use Tuleap\Jenkins\JenkinsCSRFCrumbRetriever;

class JenkinsClient
{
    private const NOTIFY_URL = '/git/notifyCommit';

    /**
     * @var ClientInterface
     */
    private $http_client;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;
    /**
     * @var JenkinsCSRFCrumbRetriever
     */
    private $csrf_crumb_retriever;

    public function __construct(
        ClientInterface $http_client,
        RequestFactoryInterface $request_factory,
        JenkinsCSRFCrumbRetriever $csrf_crumb_retriever
    ) {
        $this->http_client          = $http_client;
        $this->request_factory      = $request_factory;
        $this->csrf_crumb_retriever = $csrf_crumb_retriever;
    }

    public function pushGitNotifications($server_url, $repository_url, string $commit_reference) : PollingResponse
    {
        $csrf_crumb_header = $this->csrf_crumb_retriever->getCSRFCrumbHeader($server_url);

        if (mb_substr($server_url, -1) === '/') {
            $server_url = mb_substr($server_url, 0, -1);
        }
        $push_url = $server_url . self::NOTIFY_URL . '?url=' . urlencode($repository_url) . '&sha1=' . urlencode($commit_reference);

        $request = $this->request_factory->createRequest('POST', $push_url);

        $crumb_header_split = explode(':', $csrf_crumb_header);
        if (count($crumb_header_split) === 2) {
            [$crumb_header_name, $crumb_header_value] = $crumb_header_split;
            $request = $request->withHeader($crumb_header_name, $crumb_header_value);
        }

        try {
            $response = $this->http_client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new UnableToLaunchBuildException('pushGitNotifications: ' . $push_url . '; Message: ' . $e->getMessage());
        }

        $status_code = $response->getStatusCode();
        if ($status_code !== 200) {
            throw new UnableToLaunchBuildException('pushGitNotifications: ' . $push_url . '; Response: ' .
                $status_code . ' ' . $response->getReasonPhrase());
        }

        return new PollingResponse(
            $response->getBody()->getContents(),
            $response->getHeader('Triggered')
        );
    }
}
