<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub;
use function PHPUnit\Framework\assertEquals;

final class ClientWrapperBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private ClientWrapperBuilder $wrapper_builder;

    protected function setUp(): void
    {
        $this->wrapper_builder = new ClientWrapperBuilder(fn () => new class extends JiraCloudClientStub {
        });
    }

    public function testItThrowsAnExceptionWhenCredentialKeyIsNotProvided(): void
    {
        $body    = new \stdClass();
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getJsonDecodedBody')->andReturn($body);

        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionMessage('credentials key is mandatory');

        $this->wrapper_builder->buildFromRequest($request, new NullLogger());
    }

    public function testItThrowsAnExceptionWhenCredentialValuesAreMissing(): void
    {
        $body              = new \stdClass();
        $body->credentials = '';
        $request           = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getJsonDecodedBody')->andReturn($body);

        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionMessage('server, email or token empty');

        $this->wrapper_builder->buildFromRequest($request, new NullLogger());
    }

    public function testItThrowsAnExceptionWhenUrlIsInvalid(): void
    {
        $body                          = new \stdClass();
        $body->credentials             = new \stdClass();
        $body->credentials->server_url = 'invalid-example.com';
        $body->credentials->user_email = 'user-email@example.com';
        $body->credentials->token      = 'azerty1234';

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getJsonDecodedBody')->andReturn($body);

        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionMessage('server url is invalid');

        $this->wrapper_builder->buildFromRequest($request, new NullLogger());
    }

    public function testItBuildsAClientWrapper(): void
    {
        $body                          = new \stdClass();
        $body->credentials             = new \stdClass();
        $body->credentials->server_url = 'https://example.com';
        $body->credentials->user_email = 'user-email@example.com';
        $body->credentials->token      = 'azerty1234';

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getJsonDecodedBody')->andReturn($body);

        $wrapper_builder = new ClientWrapperBuilder(
            fn (JiraCredentials $jira_credentials, LoggerInterface $logger) => new class ($jira_credentials) extends JiraCloudClientStub {
                public function __construct(public JiraCredentials $jira_credentials)
                {
                }
            }
        );
        $client          = $wrapper_builder->buildFromRequest($request, new NullLogger());

        assertEquals('https://example.com', $client->jira_credentials->getJiraUrl());
        assertEquals('user-email@example.com', $client->jira_credentials->getJiraUsername());
        assertEquals('azerty1234', $client->jira_credentials->getJiraToken());
    }
}
