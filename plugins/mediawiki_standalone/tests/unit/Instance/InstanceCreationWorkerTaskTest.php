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
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\ServerHostname;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class InstanceCreationWorkerTaskTest extends TestCase
{
    use ForgeConfigSandbox;

    private Client $mediawiki_client;
    private InstanceCreationWorkerTask $task;

    protected function setUp(): void
    {
        \ForgeConfig::set(ServerHostname::DEFAULT_DOMAIN, 'tuleap.example.com');
        $this->mediawiki_client = new Client();
        $this->task             = new InstanceCreationWorkerTask(
            new NullLogger(),
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

    public function testInvalidProjectWillNotIssueRequests(): void
    {
        $this->task->process(new InstanceCreationWorkerEvent(100));

        self::assertFalse($this->mediawiki_client->getLastRequest());
    }

    public function testItSendsPUTRequestToCreateMediawikiInstance(): void
    {
        $this->expectNotToPerformAssertions();

        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'PUT'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(200);
            }
        );
        $this->task->process(new InstanceCreationWorkerEvent(120));
    }

    public function testPUTRequestIsFailure(): void
    {
        $this->expectNotToPerformAssertions();

        $this->mediawiki_client->on(
            new RequestMatcher('^/mediawiki/w/rest.php/tuleap/instance/gpig$', null, 'PUT'),
            function () {
                return HTTPFactoryBuilder::responseFactory()->createResponse(422);
            }
        );
        $this->task->process(new InstanceCreationWorkerEvent(120));
    }
}
