<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc;

require_once __DIR__ . '/../../../../docman/vendor/autoload.php';

use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;

final class ArtidocTest extends DocmanTestExecutionHelper
{
    private string $now = '';

    public function setUp(): void
    {
        parent::setUp();
        $this->now = (string) microtime();
    }

    /**
     * @depends testGetRootId
     */
    public function testArtidocCreation(int $root_id): void
    {
        $title = 'Artidoc F1 ' . $this->now;
        $query = json_encode(
            [
                'title' => $title,
                'type'  => 'artidoc',
            ]
        );

        $post_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/others')->withBody($this->stream_factory->createStream($query))
        );
        self::assertSame(201, $post_response->getStatusCode());
        $post_response_json = json_decode($post_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($post_response_json['file_properties']);

        $item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $post_response_json['uri'])
        );
        self::assertSame(200, $item_response->getStatusCode());
        $item_response_json = json_decode($item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('artidoc', $item_response_json['type']);
        self::assertSame($title, $item_response_json['title']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostOtherTypeDocumentDenidedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id): void
    {
        $query = json_encode(
            [
                'title' => 'Artidoc F2 ' . $this->now,
                'type'  => 'artidoc',
            ]
        );

        $response1 = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/others')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(403, $response1->getStatusCode());
    }

    public function testOptionsDocument(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc/123'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOptionsSections(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'artidoc/123/sections'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['OPTIONS', 'GET', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
    }
}
