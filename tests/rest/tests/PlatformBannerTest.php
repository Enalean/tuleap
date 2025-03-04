<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\REST;

use REST_TestDataBuilder;

/**
 * @group ProjectTests
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PlatformBannerTest extends ProjectBase
{
    public function testOptions(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'banner'));
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PUT', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testPUTForRegularUser(): void
    {
        $post_resource = json_encode([
            'message' => 'a banner message',
            'importance'  => 'critical',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest('PUT', 'banner')
                ->withBody($this->stream_factory->createStream($post_resource))
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPUTForAdmin(): void
    {
        $post_resource = json_encode([
            'message' => 'a banner message',
            'importance'  => 'critical',
            'expiration_date' => '2100-06-30T10:44:34+02:00',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'banner')
                ->withBody($this->stream_factory->createStream($post_resource))
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPUTEmptyMessageBannerShouldReturn400(): void
    {
        $payload = json_encode([
            'message' => '',
            'importance' => 'critical',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'banner')
                ->withBody($this->stream_factory->createStream($payload))
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testPUTForAdmin
     */
    public function testGETBanner(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest('GET', 'banner'),
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('a banner message', $response_json['message']);
        $this->assertEquals(
            \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ATOM, '2100-06-30T09:44:34+01:00'),
            \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ATOM, $response_json['expiration_date'])
        );
    }

    /**
     * @depends testGETBanner
     */
    public function testDELETEBannerForRegularUser(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest('DELETE', 'banner'),
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGETBanner
     */
    public function testDELETEBannerForAdmin(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'banner')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
