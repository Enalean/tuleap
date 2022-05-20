<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Instance;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\ServerHostname;

final class InstanceSuspensionWorkerTask
{
    public function __construct(private LoggerInterface $logger, private MediawikiClientFactory $client_factory, private RequestFactoryInterface $http_factory, private ProjectByIDFactory $project_factory)
    {
    }

    public function process(InstanceSuspensionWorkerEvent $event): void
    {
        $this->logger->info(sprintf("Processing %s: ", $event->getTopic()));

        try {
            $project = $this->project_factory->getValidProjectById($event->project_id);
            $request = $this->http_factory->createRequest('POST', ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/suspend/' . urlencode($project->getUnixNameLowerCase()));
            $this->logger->debug(sprintf('%s %s', $request->getMethod(), (string) $request->getUri()));
            $response = $this->client_factory->getHTTPClient()->sendRequest($request);
            $this->logger->debug((string) $response->getBody());
            if ($response->getStatusCode() === 200) {
                $this->logger->info('Mediawiki instance successfully suspended');
                return;
            }
            $this->logger->error(sprintf('Unable to create new mediawiki instance: %s (code: %d)', $response->getReasonPhrase(), $response->getStatusCode()));
        } catch (\Project_NotFoundException) {
            $this->logger->error(sprintf('Project %d does not exist or is no longer active. Skip init', $event->project_id));
        } catch (ClientExceptionInterface | ConfigurationErrorException $e) {
            $this->logger->error(sprintf('Cannot connect to mediawiki REST API: %s (%s)', $e->getMessage(), $e::class), ['exception' => $e]);
        }
    }
}
