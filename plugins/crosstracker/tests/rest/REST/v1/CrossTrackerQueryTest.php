<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\REST\v1;

use REST_TestDataBuilder;
use RestBase;
use function Psl\Json\decode;
use function Psl\Json\encode;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossTrackerQueryTest extends RestBase
{
    private const UUID_PATTERN = '/^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/';
    private const WIDGET_ID    = 3;

    private string $query_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->getEpicArtifactIds();

        $response      = $this->getResponse($this->request_factory->createRequest('GET', 'crosstracker_widget/' . self::WIDGET_ID));
        $json_response = decode($response->getBody()->getContents());
        self::assertIsArray($json_response);
        self::assertArrayHasKey('queries', $json_response);
        $queries = $json_response['queries'];
        self::assertCount(1, $queries);
        $this->query_id = $queries[0]['id'];
    }

    public function testPut(): void
    {
        $params   = [
            'tql_query'   => "SELECT @id FROM @project = 'self' WHERE @id = {$this->epic_artifact_ids[1]}",
            'title'       => 'My awesome title',
            'description' => 'Hello World!',
            'widget_id'   => self::WIDGET_ID,
        ];
        $response = $this->getResponse($this->request_factory->createRequest('PUT', 'crosstracker_query/' . urlencode($this->query_id))
            ->withBody($this->stream_factory->createStream(encode($params))));

        self::assertSame(200, $response->getStatusCode());
        $json_response = decode($response->getBody()->getContents());
        self::assertSame("SELECT @id FROM @project = 'self' WHERE @id = {$this->epic_artifact_ids[1]}", $json_response['tql_query']);
        self::assertSame('My awesome title', $json_response['title']);
        self::assertSame('Hello World!', $json_response['description']);
        self::assertMatchesRegularExpression(self::UUID_PATTERN, $json_response['id']);
    }

    public function testPutForReadOnlyUser(): void
    {
        $params   = [
            'tql_query'   => "SELECT @id FROM @project = 'self' WHERE @id = {$this->epic_artifact_ids[1]}",
            'title'       => 'My awesome title',
            'description' => 'Hello World!',
            'widget_id'   => self::WIDGET_ID,
        ];
        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'crosstracker_query/' . urlencode($this->query_id))
                ->withBody($this->stream_factory->createStream(encode($params))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * @depends testPut
     */
    public function testGetContentIdForReadOnlyUser(): void
    {
        $query_id = urlencode($this->query_id);
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "crosstracker_query/$query_id/content?limit=50&offset=0"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(200, $response->getStatusCode());
        $json_response = decode($response->getBody()->getContents());
        self::assertCount(2, $json_response['selected']);
        self::assertSame('@artifact', $json_response['selected'][0]['name']);
        self::assertSame('@id', $json_response['selected'][1]['name']);

        self::assertCount(1, $json_response['artifacts']);
        self::assertEquals($this->epic_artifact_ids[1], $json_response['artifacts'][0]['@id']['value']);
    }

    /**
     * @depends testPut
     */
    public function testGetContentId(): void
    {
        $query_id = urlencode($this->query_id);
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "crosstracker_query/$query_id/content?limit=50&offset=0"),
        );

        self::assertSame(200, $response->getStatusCode());
        $json_response = decode($response->getBody()->getContents());
        self::assertCount(2, $json_response['selected']);
        self::assertSame('@artifact', $json_response['selected'][0]['name']);
        self::assertSame('@id', $json_response['selected'][1]['name']);

        self::assertCount(1, $json_response['artifacts']);
        self::assertEquals($this->epic_artifact_ids[1], $json_response['artifacts'][0]['@id']['value']);
    }

    public function testGetQueryWithoutArtifacts(): void
    {
        $params       = [
            'tql_query'   => "SELECT @id FROM @project = 'self' WHERE @id < 1",
            'title'       => 'My awesome title',
            'description' => 'Hello World!',
            'widget_id'   => self::WIDGET_ID,
        ];
        $put_response = $this->getResponse($this->request_factory->createRequest('PUT', 'crosstracker_query/' . urlencode($this->query_id))
            ->withBody($this->stream_factory->createStream(encode($params))));
        self::assertSame(200, $put_response->getStatusCode());

        $query_id = urlencode($this->query_id);
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "crosstracker_query/$query_id/content?limit=50&offset=0"),
        );

        self::assertSame(200, $response->getStatusCode());
        $cross_tracker_artifacts = decode($response->getBody()->getContents());

        self::assertEmpty($cross_tracker_artifacts['artifacts']);
    }

    public function testGetContent(): void
    {
        $query    = [
            'widget_id' => self::WIDGET_ID,
            'tql_query' => "SELECT @id FROM @project = 'self' WHERE @id = {$this->epic_artifact_ids[1]}",
        ];
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'crosstracker_query/content?query=' . urlencode(encode($query))));

        self::assertSame(200, $response->getStatusCode());
        $json_response = decode($response->getBody()->getContents());
        self::assertCount(2, $json_response['selected']);
        self::assertSame('@artifact', $json_response['selected'][0]['name']);
        self::assertSame('@id', $json_response['selected'][1]['name']);

        self::assertCount(1, $json_response['artifacts']);
        self::assertEquals($this->epic_artifact_ids[1], $json_response['artifacts'][0]['@id']['value']);
    }
}
