<?php
/**
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
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommand;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandFactory;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandFailure;
use Tuleap\MediawikiStandalone\Instance\InitializationIssue;
use Tuleap\MediawikiStandalone\Instance\InitializationLanguageCodeProvider;
use Tuleap\MediawikiStandalone\Permissions\LegacyPermissionsMigrator;
use Tuleap\MediawikiStandalone\Service\MediawikiFlavorUsage;
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
    final public const TOPIC                = 'tuleap.mediawiki-standalone.instance-migration';
    public const MEDIAWIKI_123_SERVICE_NAME = 'plugin_mediawiki';

    private function __construct(
        private readonly MediaWikiManagementCommandFactory $command_factory,
        private readonly \Project $project,
        private readonly ?string $central_database_name,
        private readonly InitializationLanguageCodeProvider $default_language_code_provider,
        private readonly MediawikiFlavorUsage $mediawiki_flavor_usage,
        private readonly SwitchMediawikiService $switch_mediawiki_service,
        private readonly LegacyMediawikiDBPrimer $legacy_mediawiki_db_primer,
        private readonly LegacyMediawikiLanguageRetriever $legacy_mediawiki_language_retriever,
        private readonly LegacyPermissionsMigrator $legacy_permissions_migrator,
        private readonly LegacyMediawikiCreateMissingUsers $legacy_create_missing_users,
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
        MediawikiFlavorUsage $mediawiki_flavor_usage,
        SwitchMediawikiService $switch_mediawiki_service,
        LegacyMediawikiDBPrimer $legacy_mediawiki_db_primer,
        LegacyMediawikiLanguageRetriever $legacy_mediawiki_language_retriever,
        InitializationLanguageCodeProvider $default_language_code_provider,
        LegacyPermissionsMigrator $legacy_permissions_migrator,
        LegacyMediawikiCreateMissingUsers $legacy_create_missing_users,
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
                $central_database_parameter_generator->getCentralDatabase(),
                $default_language_code_provider,
                $mediawiki_flavor_usage,
                $switch_mediawiki_service,
                $legacy_mediawiki_db_primer,
                $legacy_mediawiki_language_retriever,
                $legacy_permissions_migrator,
                $legacy_create_missing_users,
            )
        );
    }

    /**
     * @psalm-return Ok<\Project>|Err<InitializationIssue>
     */
    public function process(ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, LoggerInterface $logger): Ok|Err
    {
        $logger->info(sprintf("Processing %s: ", self::TOPIC));
        if (! $this->mediawiki_flavor_usage->wasLegacyMediawikiUsed($this->project)) {
            return Result::err(
                new InitializationIssue(
                    Fault::fromMessage("Project does not have a MediaWiki 1.23 to migrate"),
                    $this->project,
                )
            );
        }

        $logger->info("Create missing users in Legacy MediaWiki base prior migration");
        $this->legacy_create_missing_users->create($logger, $this->project, $this->getDBPrefix());

        $logger->info("Switching to MediaWiki Standalone service");
        $this->switch_mediawiki_service->switchToStandalone($this->project);

        $instance_name = $this->project->getUnixNameLowerCase();

        return $this->moveDataDirectory()->andThen(
            /** @psalm-return Ok<null>|Err<Fault> */
            function () use ($logger): Ok|Err {
                return $this->legacy_mediawiki_db_primer->prepareDBForMigration(
                    $logger,
                    $this->project,
                    $this->getDBName(),
                    $this->getDBPrefix()
                );
            }
        )->andThen(
            /** @psalm-return Ok<ResponseInterface>|Err<Fault> */
            function () use ($request_factory, $instance_name, $client, $logger): Ok|Err {
                $request = $request_factory->createRequest(
                    'GET',
                    ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/' . urlencode($instance_name)
                );
                return self::processRequest($client, $request, $logger);
            }
        )->andThen(
            /** @psalm-return Ok<\Project>|Err<Fault> */
            function (ResponseInterface $response) use ($client, $request_factory, $stream_factory, $logger, $instance_name): Ok|Err {
                return match ($response->getStatusCode()) {
                    404 => $this->registerInstance($client, $request_factory, $stream_factory, $logger),
                    200 => $this->performUpgrade($logger),
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
                return Result::err(new InitializationIssue($fault, $this->project));
            }
        );
    }

    /**
     * @psalm-return Ok<null>|Err<Fault>
     */
    private function moveDataDirectory(): Ok|Err
    {
        $mw_data_dir_project_name = '/var/lib/tuleap/mediawiki/projects/' . $this->project->getUnixName();
        if (! is_dir($mw_data_dir_project_name)) {
            return Result::ok(null);
        }

        $mw_data_dir_project_id = '/var/lib/tuleap/mediawiki/projects/' . (int) $this->project->getID();
        $is_success             = rename($mw_data_dir_project_name, $mw_data_dir_project_id);
        if (! $is_success) {
            Result::err(Fault::fromMessage(sprintf('Not able to rename "%s" to "%s"', $mw_data_dir_project_name, $mw_data_dir_project_id)));
        }

        return Result::ok(null);
    }

    /**
     * @return Ok<\Project>|Err<Fault>
     */
    private function registerInstance(ClientInterface $client, RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, LoggerInterface $logger): Ok|Err
    {
        $short_language_code = $this->default_language_code_provider->getLanguageCode();
        $language            = $this->legacy_mediawiki_language_retriever->getLanguageFor((int) $this->project->getID());
        if ($language) {
            $short_language_code = \Psl\Str\before($language, '_') ?? $short_language_code;
        }

        $payload = [
            'project_id' => (int) $this->project->getID(),
            'project_name' => $this->project->getUnixNameLowerCase(),
            'lang' => $short_language_code,
            'dbprefix' => $this->getDBPrefix(),
        ];

        return self::jsonEncoder($payload)
            ->andThen(
                /** @return Ok<\Project>|Err<Fault> */
                function (string $json_payload) use ($request_factory, $stream_factory, $logger, $client): Ok|Err {
                    $request = $request_factory->createRequest('POST', ServerHostname::HTTPSUrl() . '/mediawiki/w/rest.php/tuleap/instance/register/' . urlencode($this->project->getUnixNameLowerCase()))
                        ->withBody(
                            $stream_factory->createStream($json_payload)
                        );

                    return self::processSuccessOnlyRequest($client, $request, $logger)->andThen(
                        /** @return Ok<\Project>|Err<Fault> */
                        fn () => $this->performUpgrade($logger)
                    );
                }
            );
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function applyManagementCommand(MediaWikiManagementCommand $command): Ok|Err
    {
        return $command->wait()->match(
        /** @return Ok<null>|Err<Fault> */
            fn (): Ok => Result::ok(null),
            fn (MediaWikiManagementCommandFailure $failure): Err => Result::err(Fault::fromMessage((string) $failure))
        );
    }

    /**
     * @return Ok<\Project>|Err<Fault>
     */
    private function performUpgrade(LoggerInterface $logger): Ok|Err
    {
        $project_name = $this->project->getUnixNameLowerCase();

        return $this->applyManagementCommand($this->command_factory->buildUpdateToMediaWiki135ProjectInstanceCommand($project_name))
            ->andThen(
                /** @psalm-return Ok<null>|Err<Fault> */
                function (): Ok|Err {
                    $this->legacy_permissions_migrator->migrateFromLegacyPermissions($this->project);

                    return Result::ok(null);
                }
            )->andThen(
                /** @psalm-return Ok<null>|Err<Fault> */
                fn (): Ok|Err => $this->applyManagementCommand($this->command_factory->buildUpdateProjectInstanceCommand($project_name))
            )->andThen(
                /** @psalm-return Ok<\Project> */
                function () use ($logger): Ok {
                    $logger->info(sprintf('Mediawiki %s success', self::class));
                    return Result::ok($this->project);
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

    private function getDBPrefix(): string
    {
        if ($this->central_database_name !== null) {
            return 'mw_' . $this->project->getID() . '_';
        }

        return 'mw';
    }

    private function getDBName(): string
    {
        return $this->central_database_name ?? 'plugin_mediawiki_' . $this->project->getID();
    }
}
