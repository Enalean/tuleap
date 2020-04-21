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

namespace Tracker\Creation\JiraImporter;

use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapperBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

final class ClientWrapperBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ClientWrapperBuilder
     */
    private $wrapper_builder;

    protected function setUp(): void
    {
        $this->wrapper_builder = new ClientWrapperBuilder();
    }

    public function testItThrowsAnExceptionWhenCredentialKeyIsNotProvided(): void
    {
        $body    = new \stdClass();
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getJsonDecodedBody')->andReturn($body);

        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionMessage("credentials key is mandatory");

        $this->wrapper_builder->buildFromRequest($request);
    }

    public function testItThrowsAnExceptionWhenCredentialValuesAreMissing(): void
    {
        $body              = new \stdClass();
        $body->credentials = "";
        $request           = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getJsonDecodedBody')->andReturn($body);

        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionMessage("server, email or token empty");

        $this->wrapper_builder->buildFromRequest($request);
    }

    public function testItThrowsAnExceptionWhenUrlIsInvalid(): void
    {
        $body                          = new \stdClass();
        $body->credentials             = new \stdClass();
        $body->credentials->server_url = "invalid-example.com";
        $body->credentials->user_email = "user-email@example.com";
        $body->credentials->token      = "azerty1234";

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getJsonDecodedBody')->andReturn($body);

        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionMessage("server url is invalid");

        $this->wrapper_builder->buildFromRequest($request);
    }

    public function testItBuildsAClientWrapper(): void
    {
        $body                          = new \stdClass();
        $body->credentials             = new \stdClass();
        $body->credentials->server_url = "https://example.com";
        $body->credentials->user_email = "user-email@example.com";
        $body->credentials->token      = "azerty1234";

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getJsonDecodedBody')->andReturn($body);

        $this->wrapper_builder->buildFromRequest($request);
    }
}
