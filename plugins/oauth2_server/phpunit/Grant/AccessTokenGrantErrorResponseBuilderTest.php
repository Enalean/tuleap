<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\Grant;

use PHPUnit\Framework\TestCase;
use Tuleap\Http\HTTPFactoryBuilder;

final class AccessTokenGrantErrorResponseBuilderTest extends TestCase
{
    /**
     * @var AccessTokenGrantErrorResponseBuilder
     */
    private $response_builder;

    protected function setUp(): void
    {
        $this->response_builder = new AccessTokenGrantErrorResponseBuilder(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory()
        );
    }

    public function testBuildsInvalidClientResponse(): void
    {
        $response = $this->response_builder->buildInvalidClientResponse();

        $this->assertEquals(AccessTokenGrantController::CONTENT_TYPE_RESPONSE, $response->getHeaderLine('Content-Type'));
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('WWW-Authenticate'));
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_client"}', $response->getBody()->getContents());
    }

    public function testBuildsInvalidRequestResponse(): void
    {
        $response = $this->response_builder->buildInvalidRequestResponse();

        $this->assertEquals(AccessTokenGrantController::CONTENT_TYPE_RESPONSE, $response->getHeaderLine('Content-Type'));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_request"}', $response->getBody()->getContents());
    }

    public function testBuildsInvalidGrantResponse(): void
    {
        $response = $this->response_builder->buildInvalidGrantResponse();

        $this->assertEquals(AccessTokenGrantController::CONTENT_TYPE_RESPONSE, $response->getHeaderLine('Content-Type'));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_grant"}', $response->getBody()->getContents());
    }

    public function testBuildsInvalidScopeResponse(): void
    {
        $response = $this->response_builder->buildInvalidScopeResponse();

        $this->assertEquals(AccessTokenGrantController::CONTENT_TYPE_RESPONSE, $response->getHeaderLine('Content-Type'));
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"invalid_scope"}', $response->getBody()->getContents());
    }
}
