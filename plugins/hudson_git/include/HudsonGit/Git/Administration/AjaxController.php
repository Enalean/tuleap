<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\HudsonGit\Git\Administration;

use HTTPRequest;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class AjaxController implements DispatchableWithRequest
{
    /**
     * @var ClientInterface
     */
    private $http_client;

    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ClientInterface $http_client,
        RequestFactoryInterface $request_factory,
        LoggerInterface $logger
    ) {
        $this->http_client     = $http_client;
        $this->request_factory = $request_factory;
        $this->logger          = $logger;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $jenkins_url_to_test = $request->get('jenkins_url_to_test');
        if ($jenkins_url_to_test === false) {
            $tuleap_response = [
                'type'    => "error",
                'message' => dgettext("tuleap-hudson_git", "No Jenkins server URL provided.")
            ];
            $layout->sendJSON($tuleap_response);
            return;
        }

        $this->logger->debug("Jenkins server URL to test: " . $jenkins_url_to_test);

        $request = $this->request_factory->createRequest(
            'GET',
            $jenkins_url_to_test
        );

        try {
            $response = $this->http_client->sendRequest($request);
            $this->logger->debug("Jenkins server is reachable.");
            $this->logger->debug("Response status code: " . $response->getStatusCode());
            $this->logger->debug("Response body: " . $response->getBody());
            $tuleap_response = [
                'type' => "success",
                'message' => dgettext("tuleap-hudson_git", "Jenkins server is reachable.")
            ];
        } catch (ClientExceptionInterface $exception) {
            $this->logger->debug("Jenkins server is not reachable.");
            $this->logger->debug("Error message: " . $exception->getMessage());
            $tuleap_response = [
                'type' => "error",
                'message' => dgettext("tuleap-hudson_git", "Jenkins server is not reachable.")
            ];
        }

        $layout->sendJSON($tuleap_response);
        return;
    }
}
