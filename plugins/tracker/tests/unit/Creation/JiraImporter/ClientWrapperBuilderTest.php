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
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Psr\Log\NullLogger;
use stdClass;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraClientStub;

#[DisableReturnValueGenerationForTestDoubles]
final class ClientWrapperBuilderTest extends TestCase
{
    private ClientWrapperBuilder $wrapper_builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->wrapper_builder = new ClientWrapperBuilder(static fn() => JiraClientStub::aJiraClient());
    }

    public function testItThrowsAnExceptionWhenCredentialKeyIsNotProvided(): void
    {
        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionMessage('credentials key is mandatory');

        $this->wrapper_builder->buildFromRequest(new HTTPRequest(), new NullLogger());
    }

    public function testItThrowsAnExceptionWhenCredentialValuesAreMissing(): void
    {
        $body              = new stdClass();
        $body->credentials = '';
        $request           = $this->createMock(HTTPRequest::class);
        $request->method('getJsonDecodedBody')->willReturn($body);

        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionMessage('server, email or token empty');

        $this->wrapper_builder->buildFromRequest($request, new NullLogger());
    }

    public function testItThrowsAnExceptionWhenUrlIsInvalid(): void
    {
        $body                          = new stdClass();
        $body->credentials             = new stdClass();
        $body->credentials->server_url = 'invalid-example.com';
        $body->credentials->user_email = 'user-email@example.com';
        $body->credentials->token      = 'azerty1234';

        $request = $this->createMock(HTTPRequest::class);
        $request->method('getJsonDecodedBody')->willReturn($body);

        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionMessage('server url is invalid');

        $this->wrapper_builder->buildFromRequest($request, new NullLogger());
    }

    public function testItBuildsAClientWrapper(): void
    {
        $body                          = new stdClass();
        $body->credentials             = new stdClass();
        $body->credentials->server_url = 'https://example.com';
        $body->credentials->user_email = 'user-email@example.com';
        $body->credentials->token      = 'azerty1234';

        $request = $this->createMock(HTTPRequest::class);
        $request->method('getJsonDecodedBody')->willReturn($body);

        $wrapper_builder = new ClientWrapperBuilder(static function (JiraCredentials $jira_credentials) {
            self::assertEquals('https://example.com', $jira_credentials->getJiraUrl());
            self::assertEquals('user-email@example.com', $jira_credentials->getJiraUsername());
            self::assertEquals('azerty1234', $jira_credentials->getJiraToken());
            return JiraClientStub::aJiraClient();
        });
        $wrapper_builder->buildFromRequest($request, new NullLogger());
    }
}
