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
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\ServerHostname;

final class CreateInstance
{
    public const TOPIC = 'tuleap.mediawiki-standalone.instance-creation';

    private function __construct(private \Project $project)
    {
    }

    public static function fromEvent(WorkerEvent $event, ProjectByIDFactory $project_factory): ?self
    {
        if ($event->getEventName() !== self::TOPIC) {
            return null;
        }
        $payload = $event->getPayload();
        if (! isset($payload['project_id']) || ! is_int($payload['project_id'])) {
            throw new \Exception(sprintf('Payload doesnt have project_id or project_id is not integer: %s', var_export($payload, true)));
        }

        $project = $project_factory->getValidProjectById($payload['project_id']);
        return new self($project);
    }

    public function sendRequest(ClientInterface $client, RequestFactoryInterface $request_factory, LoggerInterface $logger): void
    {
        try {
            $logger->info(sprintf("Processing %s: ", self::TOPIC));
            $request = $request_factory->createRequest(
                'GET',
                ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/' . urlencode(
                    $this->project->getUnixNameLowerCase()
                )
            );
            $logger->debug(sprintf('%s %s', $request->getMethod(), (string) $request->getUri()));
            $response = $client->sendRequest($request);
            $logger->debug($response->getBody()->getContents());
            $response->getBody()->rewind();
            match ($response->getStatusCode()) {
                404 => $this->createInstance($client, $request_factory, $logger),
                200 => $this->resumeInstance($client, $request_factory, $logger, $response),
                default => $logger->warning(sprintf('Mediawiki %s warning (%d): %s', self::class, $response->getStatusCode(), $response->getReasonPhrase()))
            };
        } catch (ClientExceptionInterface $e) {
            $logger->error(sprintf('Cannot connect to mediawiki REST API: %s (%s)', $e->getMessage(), $e::class), ['exception' => $e]);
        } catch (\JsonException $e) {
            $logger->error(sprintf('Invalid json. %s: %s', $e->getMessage(), $e->getTraceAsString()));
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    private function createInstance(ClientInterface $client, RequestFactoryInterface $request_factory, LoggerInterface $logger): void
    {
        $request = $request_factory->createRequest('PUT', ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/' . urlencode($this->project->getUnixNameLowerCase()))
            ->withBody(
                HTTPFactoryBuilder::streamFactory()->createStream(
                    \json_encode(
                        [
                            'adminpass' => 'welcome400',
                            'project_id' => (int) $this->project->getID(),
                        ],
                        JSON_THROW_ON_ERROR
                    )
                )
            );
        $logger->debug(sprintf('%s %s', $request->getMethod(), (string) $request->getUri()));
        $response = $client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            $logger->error(sprintf('Mediawiki %s error (%d): %s', self::class, $response->getStatusCode(), $response->getReasonPhrase()));
            return;
        }
        $logger->info(sprintf('Mediawiki %s success', self::class));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws \JsonException
     */
    private function resumeInstance(ClientInterface $client, RequestFactoryInterface $request_factory, LoggerInterface $logger, ResponseInterface $response): void
    {
        $payload = \json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        if (! isset($payload['status']) || $payload['status'] !== 'suspended') {
            $logger->error('Cannot resume instance. Invalid payload: ' . print_r($payload, true));
        }

        $request = $request_factory->createRequest(
            'POST',
            ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/resume/' . urlencode(
                $this->project->getUnixNameLowerCase()
            )
        );
        $logger->debug(sprintf('%s %s', $request->getMethod(), (string) $request->getUri()));
        $response = $client->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            $logger->error(sprintf('Mediawiki %s error (%d): %s', self::class, $response->getStatusCode(), $response->getReasonPhrase()));
            return;
        }
        $logger->info(sprintf('Mediawiki %s success resumed', self::class));
    }
}
