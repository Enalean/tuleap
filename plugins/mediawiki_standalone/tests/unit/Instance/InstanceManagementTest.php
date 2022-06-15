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

use Http\Message\RequestMatcher\RequestMatcher;
use Http\Mock\Client;
use Psr\Http\Client\ClientInterface;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Queue\WorkerEvent;
use Tuleap\ServerHostname;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

/**
 * @covers \Tuleap\MediawikiStandalone\Instance\CreateInstance
 * @covers \Tuleap\MediawikiStandalone\Instance\ResumeInstance
 * @covers \Tuleap\MediawikiStandalone\Instance\SuspendInstance
 * @covers \Tuleap\MediawikiStandalone\Instance\DeleteInstance
 */
final class InstanceManagementTest extends TestCase
{
    use ForgeConfigSandbox;

    private const DELETED_PROJECT_ID = 130;

    private TestLogger $logger;
    private Client $mediawiki_client;
    private InstanceManagement $instance_management;

    protected function setUp(): void
    {
        \ForgeConfig::set(ServerHostname::DEFAULT_DOMAIN, 'tuleap.example.com');
        $this->logger           = new TestLogger();
        $this->mediawiki_client = new Client();
        $this->mediawiki_client->setDefaultResponse(HTTPFactoryBuilder::responseFactory()->createResponse(400, 'Should be overridden in tests'));
        $project_120               = ProjectTestBuilder::aProject()->withId(120)->withUnixName('gpig')->build();
        $project_130               = ProjectTestBuilder::aProject()->withId(self::DELETED_PROJECT_ID)->withUnixName('foo')->withStatusDeleted()->build();
        $this->instance_management = new InstanceManagement(
            $this->logger,
            new class ($this->mediawiki_client) implements MediawikiClientFactory {
                public function __construct(private ClientInterface $client)
                {
                }

                public function getHTTPClient(): ClientInterface
                {
                    return $this->client;
                }
            },
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            ProjectByIDFactoryStub::buildWith($project_120, $project_130),
        );

        parent::setUp();
    }

    public function testCreationInvalidProjectWillNotIssueRequests(): void
    {
        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => CreateInstance::TOPIC, 'payload' => ['project_id' => 100]]));

        self::assertFalse($this->mediawiki_client->getLastRequest());
    }

    public function testCreationIsSuccessful(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(404);
            }
        );

        $create_has_been_called = false;
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'PUT'),
            function () use (&$create_has_been_called) {
                $create_has_been_called = true;
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );


        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => CreateInstance::TOPIC, 'payload' => ['project_id' => 120]]));

        self::assertTrue($create_has_been_called);
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testCreationIsError(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(404);
            }
        );

        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'PUT'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(422);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => CreateInstance::TOPIC, 'payload' => ['project_id' => 120]]));

        self::assertTrue($this->logger->hasErrorThatContains(CreateInstance::class . ' error'));
    }

    public function testCreationRequestWhenInstanceIsSuspendedMeansResume(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'GET'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200)->withBody(HTTPFactoryBuilder::streamFactory()->createStream('{"name":"gpig","directory":"/gpig","database":"mediawiki_102","scriptPath":"/mediawiki/gpig","created":"20220607172633","status":"suspended","data":{"projectId":102}}'));
            }
        );

        $resume_has_been_called = false;
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/resume/gpig$', null, 'POST'),
            function () use (&$resume_has_been_called) {
                $resume_has_been_called = true;
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => CreateInstance::TOPIC, 'payload' => ['project_id' => 120]]));

        self::assertTrue($resume_has_been_called);
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testSuspendIsSuccessful(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/suspend/gpig$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => SuspendInstance::TOPIC, 'payload' => ['project_id' => 120]]));

        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testSuspendIsError(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/suspend/gpig$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(422);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => SuspendInstance::TOPIC, 'payload' => ['project_id' => 120]]));

        self::assertTrue($this->logger->hasErrorThatContains(SuspendInstance::class . ' error'));
    }

    public function testResumeIsSuccessful(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/resume/gpig$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => ResumeInstance::TOPIC, 'payload' => ['project_id' => 120]]));

        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testResumeIsError(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/resume/gpig$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(422);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => ResumeInstance::TOPIC, 'payload' => ['project_id' => 120]]));

        self::assertTrue($this->logger->hasErrorThatContains(ResumeInstance::class . ' error'));
    }

    public function testLogsOutUserOnAllInstancesIsSuccessful(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest\.php/tuleap/maintenance/\*/terminate-sessions$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => LogUsersOutInstance::TOPIC, 'payload' => []]));

        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testLogsOutUserOnSpecificIsSuccessful(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest\.php/tuleap/maintenance/gpig/terminate-sessions$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => LogUsersOutInstance::TOPIC, 'payload' => ['project_id' => 120]]));

        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testLogsOutUserIsError(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest\.php/tuleap/maintenance/gpig/terminate-sessions$', null, 'POST'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(422);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => LogUsersOutInstance::TOPIC, 'payload' => ['project_id' => 120]]));

        self::assertTrue($this->logger->hasErrorThatContains(LogUsersOutInstance::class . ' error'));
    }

    public function testDeleteIsSuccessful(): void
    {
        $delete_has_been_called = false;

        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/foo$', null, 'DELETE'),
            function () use (&$delete_has_been_called) {
                $delete_has_been_called = true;
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => DeleteInstance::TOPIC, 'payload' => ['project_id' => self::DELETED_PROJECT_ID]]));

        self::assertTrue($delete_has_been_called);
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testDeleteIsError(): void
    {
        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'DELETE'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(404);
            }
        );

        $this->instance_management->process(new WorkerEvent(new NullLogger(), ['event_name' => DeleteInstance::TOPIC, 'payload' => ['project_id' => self::DELETED_PROJECT_ID]]));

        self::assertTrue($this->logger->hasErrorThatContains(DeleteInstance::class . ' error'));
    }
}
