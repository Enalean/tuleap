<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance\Migration;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiCentralDatabaseParameterGenerator;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandFactory;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandFailure;
use Tuleap\MediawikiStandalone\Instance\OngoingInitializationsState;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\ServerHostname;

final class MigrateInstance
{
    final public const TOPIC = 'tuleap.mediawiki-standalone.instance-migration';

    private function __construct(
        private readonly MediaWikiManagementCommandFactory $command_factory,
        private readonly \Project $project,
        private readonly bool $use_central_database,
        private readonly string $short_language_code,
        private readonly OngoingInitializationsState $initializations_state,
        private readonly SwitchMediawikiService $switch_mediawiki_service,
    ) {
    }

    /**
     * @psalm-return Option<self>
     */
    public static function fromEvent(
        WorkerEvent $event,
        ProjectByIDFactory $project_factory,
        MediaWikiCentralDatabaseParameterGenerator $central_database_parameter_generator,
        MediaWikiManagementCommandFactory $command_factory,
        OngoingInitializationsState $initializations_state,
        SwitchMediawikiService $switch_mediawiki_service,
    ): Option {
        if ($event->getEventName() !== self::TOPIC) {
            return Option::nothing(self::class);
        }
        $payload = $event->getPayload();
        if (! isset($payload['project_id']) || ! is_int($payload['project_id'])) {
            throw new \Exception(sprintf('Payload doesnt have project_id or project_id is not integer: %s', var_export($payload, true)));
        }

        return Option::fromValue(
            new self(
                $command_factory,
                $project_factory->getValidProjectById($payload['project_id']),
                $central_database_parameter_generator->getCentralDatabase() !== null,
                (string) ($payload['language_code'] ?? \BaseLanguage::DEFAULT_LANG_SHORT),
                $initializations_state,
                $switch_mediawiki_service,
            )
        );
    }

    /**
     * @psalm-return Ok<null>|Err<Fault>
     */
    public function process(ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, LoggerInterface $logger): Ok|Err
    {
        $logger->info(sprintf("Processing %s: ", self::TOPIC));
        $service = $this->project->getService(\MediaWikiPlugin::SERVICE_SHORTNAME);
        if (! $service) {
            return Result::err(Fault::fromMessage("Project does not use MediaWiki service"));
        }

        $this->initializations_state->startInitialization((int) $this->project->getID());

        $logger->info("Switching to MediaWiki Standalone service");
        $this->switch_mediawiki_service->switchToStandalone($this->project);

        $instance_name = $this->project->getUnixNameLowerCase();
        $request       = $request_factory->createRequest(
            'GET',
            ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/' . urlencode($instance_name)
        );
        return self::processRequest($client, $request, $logger)
            ->andThen(
                /** @return Ok<null>|Err<Fault> */
                function (ResponseInterface $response) use ($client, $request_factory, $stream_factory, $logger, $instance_name) {
                    return match ($response->getStatusCode()) {
                        404 => $this->registerInstance($client, $request_factory, $stream_factory, $logger),
                        200 => $this->finishUpgrade($client, $request_factory, $stream_factory, $logger),
                        default => Result::err(
                            Fault::fromMessage(
                                sprintf(
                                    "Could not determine current status of the %s instance, received %d %s\n%s",
                                    $instance_name,
                                    $response->getStatusCode(),
                                    $response->getReasonPhrase(),
                                    $response->getBody()->getContents(),
                                )
                            )
                        )
                    };
                }
            )->orElse(
                function (Fault $fault): Err {
                    $this->initializations_state->markAsError((int) $this->project->getID());

                    return Result::err($fault);
                }
            );
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function registerInstance(ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, LoggerInterface $logger): Ok|Err
    {
        $payload             = [
            'project_id' => (int) $this->project->getID(),
            'project_name' => $this->project->getUnixNameLowerCase(),
            'lang' => $this->short_language_code,
        ];
        $payload['dbprefix'] = 'mw';
        if ($this->use_central_database) {
            $payload['dbprefix'] = 'mw_' . $this->project->getID() . '_';
        }
        return self::jsonEncoder($payload)
            ->andThen(
                /** @return Ok<null>|Err<Fault> */
                function (string $json_payload) use ($request_factory, $stream_factory, $logger, $client): Ok|Err {
                    $request = $request_factory->createRequest('POST', ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/register/' . urlencode($this->project->getUnixNameLowerCase()))
                        ->withBody(
                            $stream_factory->createStream($json_payload)
                        );

                    return self::processSuccessOnlyRequest($client, $request, $logger)->andThen(
                        /** @return Ok<null>|Err<Fault> */
                        fn () => $this->performUpgrade($client, $request_factory, $stream_factory, $logger)
                    );
                }
            );
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function performUpgrade(ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, LoggerInterface $logger): Ok|Err
    {
        $command = $this->command_factory->buildUpdateProjectInstanceCommand($this->project->getUnixNameLowerCase());
        return $command->wait()->match(
            /** @return Ok<null>|Err<Fault> */
            fn (): Ok|Err => $this->finishUpgrade($client, $request_factory, $stream_factory, $logger),
            fn (MediaWikiManagementCommandFailure $failure): Err => Result::err(Fault::fromMessage((string) $failure))
        );
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function finishUpgrade(ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, LoggerInterface $logger): Ok|Err
    {
        $request = $request_factory
            ->createRequest('POST', ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/maintenance/' . urlencode($this->project->getUnixNameLowerCase()) . '/update')
            ->withBody($stream_factory->createStream('{}'));
        return self::processSuccessOnlyRequest($client, $request, $logger)->andThen(
            function () use ($logger): Ok {
                $this->initializations_state->finishInitialization((int) $this->project->getID());
                $logger->info(sprintf('Mediawiki %s success', self::class));
                return Result::ok(null);
            }
        );
    }

    /**
     * @return Ok<non-empty-string>|Err<Fault>
     */
    private static function jsonEncoder(mixed $value): Ok|Err
    {
        try {
            return Result::ok(\json_encode($value, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            return Result::err(Fault::fromThrowableWithMessage($e, sprintf('Invalid json. %s: %s', $e->getMessage(), $e->getTraceAsString())));
        }
    }

    /**
     * @return Ok<ResponseInterface>|Err<Fault>
     */
    private static function processRequest(ClientInterface $client, RequestInterface $request, LoggerInterface $logger): Ok|Err
    {
        $logger->debug(sprintf('%s %s', $request->getMethod(), (string) $request->getUri()));
        try {
            $response = $client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            return Result::err(Fault::fromThrowableWithMessage($e, sprintf('Cannot connect to mediawiki REST API: %s (%s)', $e->getMessage(), $e::class)));
        }

        return Result::ok($response);
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private static function processSuccessOnlyRequest(ClientInterface $client, RequestInterface $request, LoggerInterface $logger): Ok|Err
    {
        return self::processRequest($client, $request, $logger)
            ->andThen(
            /** @return Ok<null>|Err<Fault> */
                static function (ResponseInterface $response): Ok|Err {
                    if ($response->getStatusCode() !== 200) {
                        return Result::err(Fault::fromMessage(sprintf('Mediawiki %s error (%d): %s', self::class, $response->getStatusCode(), $response->getReasonPhrase())));
                    }
                    return Result::ok(null);
                }
            );
    }
}
