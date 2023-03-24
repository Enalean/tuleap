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

use Http\Message\RequestMatcher\CallbackRequestMatcher;
use Http\Message\RequestMatcher\RequestMatcher;
use Http\Mock\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandAlwaysFail;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandDoNothing;
use Tuleap\MediawikiStandalone\Instance\MediaWikiCentralDatabaseParameterGeneratorStub;
use Tuleap\MediawikiStandalone\Instance\OngoingInitializationsStateStub;
use Tuleap\MediawikiStandalone\Instance\ProvideInitializationLanguageCodeStub;
use Tuleap\MediawikiStandalone\Service\MediawikiFlavorUsage;
use Tuleap\MediawikiStandalone\Service\MediawikiFlavorUsageStub;
use Tuleap\MediawikiStandalone\Stub\MediaWikiManagementCommandFactoryStub;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Queue\WorkerEvent;
use Tuleap\ServerHostname;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

final class MigrateInstanceTest extends TestCase
{
    use ForgeConfigSandbox;

    private Client $mediawiki_client;
    private ProjectByIDFactoryStub $project_factory;
    private \Project $project;

    protected function setUp(): void
    {
        \ForgeConfig::set(ServerHostname::DEFAULT_DOMAIN, 'tuleap.example.com');
        $this->mediawiki_client = new Client();
        $this->mediawiki_client->setDefaultResponse(HTTPFactoryBuilder::responseFactory()->createResponse(400, 'Should be overridden in tests'));

        $this->project         = ProjectTestBuilder::aProject()->withId(120)->withUnixName('gpig')->build();
        $this->project_factory = ProjectByIDFactoryStub::buildWith(
            $this->project,
        );
    }

    public function testSuccess(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(404);
            }
        );

        $this->mediawiki_client->on(
            new CallbackRequestMatcher(
                function (RequestInterface $request): bool {
                    return $request->getMethod() === 'POST' &&
                        $request->getUri()->getPath() === '/mediawiki/w/rest.php/tuleap/instance/register/gpig' &&
                        $request->getBody()->getContents() === '{"project_id":120,"project_name":"gpig","lang":"fr","dbprefix":"mw"}';
                }
            ),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $this->mediawiki_client->on(
            new CallbackRequestMatcher(
                function (RequestInterface $request): bool {
                    return $request->getMethod() === 'POST' &&
                        $request->getUri()->getPath() === '/mediawiki/w/rest.php/tuleap/maintenance/gpig/update' &&
                        $request->getBody()->getContents() === '{}';
                }
            ),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $initializations_state = OngoingInitializationsStateStub::buildSelf();
        $switcher              = SwitchMediawikiServiceStub::buildSelf();
        $db_primer             = new LegacyMediawikiDBPrimerStub();

        $migrate_instance_option = MigrateInstance::fromEvent(
            new WorkerEvent(new NullLogger(), ['event_name' => MigrateInstance::TOPIC, 'payload' => ['project_id' => 120]]),
            $this->project_factory,
            new MediaWikiCentralDatabaseParameterGeneratorStub(),
            MediaWikiManagementCommandFactoryStub::buildForUpdateInstancesCommandsOnly([new MediaWikiManagementCommandDoNothing()]),
            self::buildFlavorUsageWithLegacyMediaWiki(),
            $initializations_state,
            $switcher,
            $db_primer,
            LegacyMediawikiLanguageRetrieverStub::withLanguage('fr_FR'),
            new ProvideInitializationLanguageCodeStub(),
        );

        self::assertTrue($migrate_instance_option->isValue());
        $migrate_instance_option->apply(
            function (MigrateInstance $migrate_instance) use ($initializations_state, $switcher, $db_primer): void {
                $result = $migrate_instance->process(
                    $this->mediawiki_client,
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    new NullLogger(),
                );
                self::assertTrue(Result::isOk($result));
                self::assertTrue($initializations_state->isFinished());
                self::assertFalse($initializations_state->isError());
                self::assertTrue($switcher->isSwitchedToStandalone());
                self::assertEquals(Option::fromValue('plugin_mediawiki_120'), $db_primer->db_name_used);
                self::assertEquals(Option::fromValue('mw'), $db_primer->db_prefix_used);
            }
        );
    }

    public function testUseDefaultLanguageIfLegacyMediaWikiDoesNotHaveOne(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(404);
            }
        );

        $this->mediawiki_client->on(
            new CallbackRequestMatcher(
                function (RequestInterface $request): bool {
                    return $request->getMethod() === 'POST' &&
                        $request->getUri()->getPath() === '/mediawiki/w/rest.php/tuleap/instance/register/gpig' &&
                        $request->getBody()->getContents() === '{"project_id":120,"project_name":"gpig","lang":"en","dbprefix":"mw"}';
                }
            ),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $this->mediawiki_client->on(
            new CallbackRequestMatcher(
                function (RequestInterface $request): bool {
                    return $request->getMethod() === 'POST' &&
                        $request->getUri()->getPath() === '/mediawiki/w/rest.php/tuleap/maintenance/gpig/update' &&
                        $request->getBody()->getContents() === '{}';
                }
            ),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $initializations_state = OngoingInitializationsStateStub::buildSelf();
        $switcher              = SwitchMediawikiServiceStub::buildSelf();

        $migrate_instance_option = MigrateInstance::fromEvent(
            new WorkerEvent(new NullLogger(), ['event_name' => MigrateInstance::TOPIC, 'payload' => ['project_id' => 120]]),
            $this->project_factory,
            new MediaWikiCentralDatabaseParameterGeneratorStub(),
            MediaWikiManagementCommandFactoryStub::buildForUpdateInstancesCommandsOnly([new MediaWikiManagementCommandDoNothing()]),
            self::buildFlavorUsageWithLegacyMediaWiki(),
            $initializations_state,
            $switcher,
            new LegacyMediawikiDBPrimerStub(),
            LegacyMediawikiLanguageRetrieverStub::withoutLanguage(),
            new ProvideInitializationLanguageCodeStub(),
        );

        self::assertTrue($migrate_instance_option->isValue());
        $migrate_instance_option->apply(
            function (MigrateInstance $migrate_instance) use ($initializations_state, $switcher): void {
                $result = $migrate_instance->process(
                    $this->mediawiki_client,
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    new NullLogger(),
                );
                self::assertTrue(Result::isOk($result));
                self::assertTrue($initializations_state->isFinished());
                self::assertFalse($initializations_state->isError());
                self::assertTrue($switcher->isSwitchedToStandalone());
            }
        );
    }

    public function testUseDefaultLanguageIfLegacyMediaWikiLanguageDoesNotHaveTheExpectedFormat(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(404);
            }
        );

        $this->mediawiki_client->on(
            new CallbackRequestMatcher(
                function (RequestInterface $request): bool {
                    return $request->getMethod() === 'POST' &&
                        $request->getUri()->getPath() === '/mediawiki/w/rest.php/tuleap/instance/register/gpig' &&
                        $request->getBody()->getContents() === '{"project_id":120,"project_name":"gpig","lang":"en","dbprefix":"mw"}';
                }
            ),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $this->mediawiki_client->on(
            new CallbackRequestMatcher(
                function (RequestInterface $request): bool {
                    return $request->getMethod() === 'POST' &&
                        $request->getUri()->getPath() === '/mediawiki/w/rest.php/tuleap/maintenance/gpig/update' &&
                        $request->getBody()->getContents() === '{}';
                }
            ),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $initializations_state = OngoingInitializationsStateStub::buildSelf();
        $switcher              = SwitchMediawikiServiceStub::buildSelf();

        $migrate_instance_option = MigrateInstance::fromEvent(
            new WorkerEvent(new NullLogger(), ['event_name' => MigrateInstance::TOPIC, 'payload' => ['project_id' => 120]]),
            $this->project_factory,
            new MediaWikiCentralDatabaseParameterGeneratorStub(),
            MediaWikiManagementCommandFactoryStub::buildForUpdateInstancesCommandsOnly([new MediaWikiManagementCommandDoNothing()]),
            self::buildFlavorUsageWithLegacyMediaWiki(),
            $initializations_state,
            $switcher,
            new LegacyMediawikiDBPrimerStub(),
            LegacyMediawikiLanguageRetrieverStub::withLanguage('invalid'),
            new ProvideInitializationLanguageCodeStub(),
        );

        self::assertTrue($migrate_instance_option->isValue());
        $migrate_instance_option->apply(
            function (MigrateInstance $migrate_instance) use ($initializations_state, $switcher): void {
                $result = $migrate_instance->process(
                    $this->mediawiki_client,
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    new NullLogger(),
                );
                self::assertTrue(Result::isOk($result));
                self::assertTrue($initializations_state->isFinished());
                self::assertFalse($initializations_state->isError());
                self::assertTrue($switcher->isSwitchedToStandalone());
            }
        );
    }

    public function testInstanceAlreadyExistsRunsMaintenanceSoUnsuccessfulMigrationHaveAChanceToComplete(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $this->mediawiki_client->on(
            new CallbackRequestMatcher(
                function (RequestInterface $request): bool {
                    return $request->getMethod() === 'POST' &&
                        $request->getUri()->getPath() === '/mediawiki/w/rest.php/tuleap/maintenance/gpig/update' &&
                        $request->getBody()->getContents() === '{}';
                }
            ),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $initializations_state = OngoingInitializationsStateStub::buildSelf();
        $switcher              = SwitchMediawikiServiceStub::buildSelf();

        $migrate_instance_option = MigrateInstance::fromEvent(
            new WorkerEvent(new NullLogger(), ['event_name' => MigrateInstance::TOPIC, 'payload' => ['project_id' => 120]]),
            $this->project_factory,
            new MediaWikiCentralDatabaseParameterGeneratorStub(),
            MediaWikiManagementCommandFactoryStub::buildForUpdateInstancesCommandsOnly([new MediaWikiManagementCommandDoNothing()]),
            self::buildFlavorUsageWithLegacyMediaWiki(),
            $initializations_state,
            $switcher,
            new LegacyMediawikiDBPrimerStub(),
            LegacyMediawikiLanguageRetrieverStub::withLanguage('en_US'),
            new ProvideInitializationLanguageCodeStub(),
        );
        self::assertTrue($migrate_instance_option->isValue());
        $migrate_instance_option->apply(
            function (MigrateInstance $migrate_instance) use ($initializations_state, $switcher): void {
                $result = $migrate_instance->process(
                    $this->mediawiki_client,
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    new NullLogger(),
                );
                self::assertTrue(Result::isOk($result));
                self::assertTrue($initializations_state->isFinished());
                self::assertFalse($initializations_state->isError());
                self::assertTrue($switcher->isSwitchedToStandalone());
            }
        );
    }

    public function testInstanceIsInErrorAbortProcess(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(500)->withBody(HTTPFactoryBuilder::streamFactory()->createStream('foo bar error'));
            }
        );

        $initializations_state = OngoingInitializationsStateStub::buildSelf();
        $switcher              = SwitchMediawikiServiceStub::buildSelf();

        $migrate_instance_option = MigrateInstance::fromEvent(
            new WorkerEvent(new NullLogger(), ['event_name' => MigrateInstance::TOPIC, 'payload' => ['project_id' => 120]]),
            $this->project_factory,
            new MediaWikiCentralDatabaseParameterGeneratorStub(),
            MediaWikiManagementCommandFactoryStub::buildForUpdateInstancesCommandsOnly([new MediaWikiManagementCommandDoNothing()]),
            self::buildFlavorUsageWithLegacyMediaWiki(),
            $initializations_state,
            $switcher,
            new LegacyMediawikiDBPrimerStub(),
            LegacyMediawikiLanguageRetrieverStub::withLanguage('en_US'),
            new ProvideInitializationLanguageCodeStub(),
        );
        self::assertTrue($migrate_instance_option->isValue());
        $migrate_instance_option->apply(
            function (MigrateInstance $migrate_instance) use ($initializations_state, $switcher): void {
                $result = $migrate_instance->process(
                    $this->mediawiki_client,
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    new NullLogger(),
                );
                self::assertTrue(Result::isErr($result));
                self::assertStringContainsString('foo bar error', (string) $result->error);
                self::assertTrue($initializations_state->isStarted());
                self::assertTrue($initializations_state->isError());
                self::assertTrue($switcher->isSwitchedToStandalone());
            }
        );
    }

    public function testFailureToRegisterInstanceAbortProcess(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(404);
            }
        );

        $this->mediawiki_client->on(
            new CallbackRequestMatcher(
                function (RequestInterface $request): bool {
                    return $request->getMethod() === 'POST' &&
                        $request->getUri()->getPath() === '/mediawiki/w/rest.php/tuleap/instance/register/gpig';
                }
            ),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(400);
            }
        );

        $initializations_state = OngoingInitializationsStateStub::buildSelf();

        $migrate_instance_option = MigrateInstance::fromEvent(
            new WorkerEvent(new NullLogger(), ['event_name' => MigrateInstance::TOPIC, 'payload' => ['project_id' => 120]]),
            $this->project_factory,
            new MediaWikiCentralDatabaseParameterGeneratorStub(),
            MediaWikiManagementCommandFactoryStub::buildForUpdateInstancesCommandsOnly([new MediaWikiManagementCommandDoNothing()]),
            self::buildFlavorUsageWithLegacyMediaWiki(),
            $initializations_state,
            SwitchMediawikiServiceStub::buildSelf(),
            new LegacyMediawikiDBPrimerStub(),
            LegacyMediawikiLanguageRetrieverStub::withLanguage('en_US'),
            new ProvideInitializationLanguageCodeStub(),
        );
        self::assertTrue($migrate_instance_option->isValue());
        $migrate_instance_option->apply(
            function (MigrateInstance $migrate_instance) use ($initializations_state): void {
                $result = $migrate_instance->process(
                    $this->mediawiki_client,
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    new NullLogger(),
                );

                self::assertTrue(Result::isErr($result));
                self::assertTrue($initializations_state->isError());
                self::assertStringContainsStringIgnoringCase('bad request', (string) $result->error);
            }
        );
    }

    public function testFailureOfMaintenanceCommandAbortTheProcess(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(404);
            }
        );

        $this->mediawiki_client->on(
            new CallbackRequestMatcher(
                function (RequestInterface $request): bool {
                    return $request->getMethod() === 'POST' &&
                        $request->getUri()->getPath() === '/mediawiki/w/rest.php/tuleap/instance/register/gpig' &&
                        $request->getBody()->getContents() === '{"project_id":120,"project_name":"gpig","lang":"en","dbprefix":"mw"}';
                }
            ),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $initializations_state = OngoingInitializationsStateStub::buildSelf();

        $migrate_instance_option = MigrateInstance::fromEvent(
            new WorkerEvent(new NullLogger(), ['event_name' => MigrateInstance::TOPIC, 'payload' => ['project_id' => 120]]),
            $this->project_factory,
            new MediaWikiCentralDatabaseParameterGeneratorStub(),
            MediaWikiManagementCommandFactoryStub::buildForUpdateInstancesCommandsOnly([new MediaWikiManagementCommandAlwaysFail()]),
            self::buildFlavorUsageWithLegacyMediaWiki(),
            $initializations_state,
            SwitchMediawikiServiceStub::buildSelf(),
            new LegacyMediawikiDBPrimerStub(),
            LegacyMediawikiLanguageRetrieverStub::withLanguage('en_US'),
            new ProvideInitializationLanguageCodeStub(),
        );

        self::assertTrue($migrate_instance_option->isValue());
        $migrate_instance_option->apply(
            function (MigrateInstance $migrate_instance) use ($initializations_state): void {
                $result = $migrate_instance->process(
                    $this->mediawiki_client,
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    new NullLogger(),
                );
                self::assertTrue(Result::isErr($result));
                self::assertTrue($initializations_state->isError());
                self::assertStringContainsString('Exit code', (string) $result->error);
            }
        );
    }

    public function testNoUsageOfLegacyMWAbortTheProcess(): void
    {
        $initializations_state = OngoingInitializationsStateStub::buildSelf();

        $flavor_usage                  = new MediawikiFlavorUsageStub();
        $flavor_usage->was_legacy_used = false;

        $migrate_instance_option = MigrateInstance::fromEvent(
            new WorkerEvent(new NullLogger(), ['event_name' => MigrateInstance::TOPIC, 'payload' => ['project_id' => 120]]),
            $this->project_factory,
            new MediaWikiCentralDatabaseParameterGeneratorStub(),
            MediaWikiManagementCommandFactoryStub::buildForUpdateInstancesCommandsOnly([new MediaWikiManagementCommandAlwaysFail()]),
            $flavor_usage,
            $initializations_state,
            SwitchMediawikiServiceStub::buildSelf(),
            new LegacyMediawikiDBPrimerStub(),
            LegacyMediawikiLanguageRetrieverStub::withLanguage('en_US'),
            new ProvideInitializationLanguageCodeStub(),
        );

        self::assertTrue($migrate_instance_option->isValue());
        $migrate_instance_option->apply(
            function (MigrateInstance $migrate_instance) use ($initializations_state): void {
                $result = $migrate_instance->process(
                    $this->mediawiki_client,
                    HTTPFactoryBuilder::requestFactory(),
                    HTTPFactoryBuilder::streamFactory(),
                    new NullLogger(),
                );
                self::assertTrue(Result::isErr($result));
                self::assertFalse($initializations_state->isStarted());
                self::assertStringContainsString('Project does not have a MediaWiki 1.23 to migrate', (string) $result->error);
            }
        );
    }

    public function testDoesNotInstantiateTaskWhenEventIsNotAMigration(): void
    {
        $migrate_instance_option = MigrateInstance::fromEvent(
            new WorkerEvent(new NullLogger(), ['event_name' => 'something_else_that_is_not_a_migration', 'payload' => []]),
            $this->project_factory,
            new MediaWikiCentralDatabaseParameterGeneratorStub(),
            MediaWikiManagementCommandFactoryStub::buildForUpdateInstancesCommandsOnly([new MediaWikiManagementCommandDoNothing()]),
            self::buildFlavorUsageWithLegacyMediaWiki(),
            OngoingInitializationsStateStub::buildSelf(),
            SwitchMediawikiServiceStub::buildSelf(),
            new LegacyMediawikiDBPrimerStub(),
            LegacyMediawikiLanguageRetrieverStub::withoutLanguage(),
            new ProvideInitializationLanguageCodeStub(),
        );

        self::assertTrue($migrate_instance_option->isNothing());
    }

    private static function buildFlavorUsageWithLegacyMediaWiki(): MediawikiFlavorUsage
    {
        $flavor_usage                  = new MediawikiFlavorUsageStub();
        $flavor_usage->was_legacy_used = true;
        return $flavor_usage;
    }
}
