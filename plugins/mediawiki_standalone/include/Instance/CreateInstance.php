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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiCentralDatabaseParameterGenerator;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\ServerHostname;

final class CreateInstance
{
    public const string TOPIC = 'tuleap.mediawiki-standalone.instance-creation';

    private function __construct(
        private readonly \Project $project,
        private readonly bool $use_central_database,
        private readonly string $short_language_code,
    ) {
    }

     /**
     * @psalm-return Option<self>
     */
    public static function fromEvent(WorkerEvent $event, ProjectByIDFactory $project_factory, MediaWikiCentralDatabaseParameterGenerator $central_database_parameter_generator): Option
    {
        if ($event->getEventName() !== self::TOPIC) {
            return Option::nothing(self::class);
        }
        $payload = $event->getPayload();
        if (! isset($payload['project_id']) || ! is_int($payload['project_id'])) {
            throw new \Exception(sprintf('Payload doesnt have project_id or project_id is not integer: %s', var_export($payload, true)));
        }

        return Option::fromValue(
            new self(
                $project_factory->getValidProjectById($payload['project_id']),
                $central_database_parameter_generator->getCentralDatabase() !== null,
                (string) ($payload['language_code'] ?? \BaseLanguage::DEFAULT_LANG_SHORT)
            )
        );
    }

    /**
     * @psalm-return Ok<\Project>|Err<InitializationIssue>
     */
    public function process(ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, LoggerInterface $logger): Ok|Err
    {
        $logger->info(sprintf('Processing %s: ', self::TOPIC));
        $instance_name = $this->project->getUnixNameLowerCase();
        $request       = $request_factory->createRequest(
            'GET',
            ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/' . urlencode($instance_name)
        );
        return self::processRequest($client, $request, $logger)
            ->andThen(
                /** @return Ok<null>|Err<Fault> */
                function (ResponseInterface $response) use ($client, $request_factory, $stream_factory, $logger, $instance_name): Ok|Err {
                    $logger->debug($response->getBody()->getContents());
                    $response->getBody()->rewind();
                    return match ($response->getStatusCode()) {
                        404 => $this->createInstance($client, $request_factory, $stream_factory, $logger),
                        200 => $this->resumeInstance($client, $request_factory, $stream_factory, $logger, $response),
                        default => Result::err(
                            Fault::fromMessage(
                                sprintf(
                                    'Could not determine current status of the %s instance, received %d %s',
                                    $instance_name,
                                    $response->getStatusCode(),
                                    $response->getReasonPhrase()
                                )
                            )
                        )
                    };
                }
            )->match(
                /** @psalm-return Ok<\Project> */
                fn (): Ok => Result::ok($this->project),
                /** @psalm-return Err<InitializationIssue> */
                fn (Fault $fault): Err => Result::err(new InitializationIssue($fault, $this->project)),
            );
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function createInstance(ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, LoggerInterface $logger): Ok|Err
    {
        $payload = ['project_id' => (int) $this->project->getID(), 'lang' => $this->short_language_code];
        if ($this->use_central_database) {
            $payload['dbprefix'] = 'mw_' . $this->project->getID() . '_';
        }
        return self::jsonEncoder($payload)
            ->andThen(
                /** @return Ok<null>|Err<Fault> */
                function (string $json_payload) use ($request_factory, $stream_factory, $logger, $client): Ok|Err {
                    $request = $request_factory->createRequest('PUT', ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/' . urlencode($this->project->getUnixNameLowerCase()))
                        ->withBody(
                            $stream_factory->createStream($json_payload)
                        );

                    return self::processSuccessOnlyRequest($client, $request, $logger)->andThen(
                        /** @return Ok<null> */
                        static function () use ($logger): Ok {
                            $logger->info(sprintf('Mediawiki %s success', self::class));
                            return Result::ok(null);
                        }
                    );
                }
            );
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function resumeInstance(ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, LoggerInterface $logger, ResponseInterface $response): Ok|Err
    {
        return self::jsonDecoder($response->getBody()->getContents())
            ->andThen(
                /** @return Ok<null>|Err<Fault> */
                function (array $payload): Ok|Err {
                    if (! isset($payload['status']) || $payload['status'] !== 'suspended') {
                        return Result::err(Fault::fromMessage('Cannot resume instance. Invalid payload: ' . print_r($payload, true)));
                    }
                    return Result::ok(null);
                }
            )
            ->andThen(
                /** @return Ok<null>|Err<Fault> */
                function () use ($request_factory, $logger, $client): Ok|Err {
                    $request = $request_factory->createRequest(
                        'POST',
                        ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/resume/' . urlencode(
                            $this->project->getUnixNameLowerCase()
                        )
                    );
                    return self::processSuccessOnlyRequest($client, $request, $logger)->andThen(
                    /** @return Ok<null> */
                        static function () use ($logger): Ok {
                            $logger->info(sprintf('Mediawiki %s success resumed', self::class));
                            return Result::ok(null);
                        }
                    );
                }
            )
            ->andThen(
                /** @return Ok<null>|Err<Fault> */
                function () use ($client, $request_factory, $stream_factory, $logger): Ok|Err {
                    return $this->updateInstance($client, $request_factory, $stream_factory, $logger);
                }
            );
    }

    /**
     * @return Ok<string>|Err<Fault>
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
     * @return Ok<array>|Err<Fault>
     */
    private static function jsonDecoder(string $json): Ok|Err
    {
        try {
            return Result::ok(\json_decode($json, true, 512, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            return Result::err(Fault::fromThrowableWithMessage($e, sprintf('Cannot decode JSON string. %s: %s', $e->getMessage(), $e->getTraceAsString())));
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

    private function updateInstance(ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, LoggerInterface $logger): Ok|Err
    {
        $request = $request_factory->createRequest(
            'POST',
            ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/maintenance/' . urlencode($this->project->getUnixNameLowerCase()) . '/update'
        )->withBody(
            $stream_factory->createStream('{}')
        );
        return self::processSuccessOnlyRequest($client, $request, $logger);
    }
}
