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
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\WorkerEvent;
use Tuleap\ServerHostname;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

/**
 * @covers \Tuleap\MediawikiStandalone\Instance\CreateInstance
 * @covers \Tuleap\MediawikiStandalone\Instance\ResumeInstance
 * @covers \Tuleap\MediawikiStandalone\Instance\SuspendInstance
 */
final class InstanceManagementTest extends TestCase
{
    use ForgeConfigSandbox;

    private TestLogger $logger;
    private Client $mediawiki_client;
    private InstanceManagement $instance_management;

    protected function setUp(): void
    {
        \ForgeConfig::set(ServerHostname::DEFAULT_DOMAIN, 'tuleap.example.com');
        $this->logger           = new TestLogger();
        $this->mediawiki_client = new Client();
        $this->mediawiki_client->setDefaultResponse(HTTPFactoryBuilder::responseFactory()->createResponse(400, 'Should be overridden in tests'));
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
            new class implements ProjectByIDFactory {
                public function getValidProjectById(int $project_id): \Project
                {
                    if ($project_id !== 120) {
                        throw new \Project_NotFoundException();
                    }
                    return ProjectTestBuilder::aProject()->withId(120)->withUnixName('gpig')->build();
                }
            },
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
}
