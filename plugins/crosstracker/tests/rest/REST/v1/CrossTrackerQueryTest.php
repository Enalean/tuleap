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

use Psl\Json;
use Tuleap\CrossTracker\TestBase;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RESTTestDataBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossTrackerQueryTest extends TestBase
{
    private const string UUID_PATTERN = '/^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/';

    private const int REVERSE_CROSS_LINK_WIDGET_ID = 1;
    private const int FORWARD_CROSS_LINK_WIDGET_ID = 2;

    private string $query_id;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->getEpicArtifactIds();
        $this->getReleaseArtifactIds();

        $response      = $this->getResponse($this->request_factory->createRequest('GET', 'crosstracker_widget/' . self::WIDGET_ID));
        $json_response = Json\decode($response->getBody()->getContents());
        self::assertIsArray($json_response);
        self::assertArrayHasKey('queries', $json_response);
        /** @var list<array{id: string}> $queries */
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
            ->withBody($this->stream_factory->createStream(Json\encode($params))));

        self::assertSame(200, $response->getStatusCode());
        $json_response = Json\decode($response->getBody()->getContents());
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
                ->withBody($this->stream_factory->createStream(Json\encode($params))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(404, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPut')]
    public function testGetContentIdForReadOnlyUser(): void
    {
        $query_id = urlencode($this->query_id);
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "crosstracker_query/$query_id/content?limit=50&offset=0"),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(200, $response->getStatusCode());
        $json_response = Json\decode($response->getBody()->getContents());
        self::assertCount(2, $json_response['selected']);
        self::assertSame('@artifact', $json_response['selected'][0]['name']);
        self::assertSame('@id', $json_response['selected'][1]['name']);

        self::assertCount(1, $json_response['artifacts']);
        self::assertEquals($this->epic_artifact_ids[1], $json_response['artifacts'][0]['@id']['value']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPut')]
    public function testGetContentId(): void
    {
        $query_id = urlencode($this->query_id);
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "crosstracker_query/$query_id/content?limit=50&offset=0"),
        );

        self::assertSame(200, $response->getStatusCode());
        $json_response = Json\decode($response->getBody()->getContents());
        self::assertCount(2, $json_response['selected']);
        self::assertSame('@artifact', $json_response['selected'][0]['name']);
        self::assertSame('@id', $json_response['selected'][1]['name']);

        self::assertCount(1, $json_response['artifacts']);
        self::assertEquals($this->epic_artifact_ids[1], $json_response['artifacts'][0]['@id']['value']);
    }

    /**
     * Data structure used for the test, ids in parentheses and label text are the one exposed in xml _fixture/reverse-link project
     *
     *                            ┌────────────────────────────────┐
     *                            │                                │
     *                            │    Reverse (603)               │
     *                            │                                │
     *                            └─────────────┬──────────────────┘
     *                           ┌────────────────────────────────┐
     *                           │                                │
     *                        _is_child                       _is_child
     *                           │                                │
     *                           │                                │
     *         ┌─────────────────▼─────────────────────┐    ┌────────────────────────────────┐
     *         │                                       │    │                                │
     *         │   current artifact   (500)            │    │     Other artifact (604)       │
     *         │                                       │    │                                │
     *         └─────────────────┴─────────────────────┘    └────────────────────────────────┘
     *                           │
     *                       _is_child
     *                           │
     *                    ┌─────────────────────────────────────┬───────────────────────────────────┐
     *                    │                                     │                                   │
     *                    │                                     │                                   │
     *                    │                                     │                                   │
     *                    │                                     │                                   │
     *                    │                                     │                                   │
     *                    ▼                                     │                                   │
     *           ┌───────────────────────┐           ┌──────────▼──────────┐       ┌────────────────▼───────┐
     *           │                       │           │                     │       │                        │
     *           │  forward 1  (499)     │           │ forward 2  (501)    │       │ forward 3  (502)       │
     *           │                       │           │                     │       │                        │
     *           └───────────────────────┘           └─────────────────────┘       └────────────────────────┘
     *
     * TLDR;
     *  - artifact 500 has three forward links, 499, 501, 502 and one reverse link 603
     *  - reverse artifact 603 has two forward links, 500 and 604
     *  - when we call for reverse links of current artifact 500, we should only find one link with _is_child type
     */
    public function testGetReverseLinksFromCrossTrackerQuery(): void
    {
        $query = http_build_query(
            ['order' => 'asc']
        );

        $artifacts = Json\decode(
            $this->getResponseByName(
                BaseTestDataBuilder::ADMIN_USER_NAME,
                $this->request_factory->createRequest('GET', "trackers/$this->reverse_cross_tracker_tracker_id/artifacts?$query")
            )->getBody()->getContents(),
        );

        $artifact_title   = 'current artifact';
        $current_artifact = $this->findItemByTitle($artifacts, $artifact_title);
        self::assertNotNull($current_artifact);
        $current_artifact_id = $current_artifact['id'];

        $tql_query = "SELECT @pretty_title, @link_type FROM @project = 'self' WHERE @id = $current_artifact_id ORDER BY @last_update_date DESC";
        $response  = $this->getResponse(
            $this->request_factory->createRequest('GET', 'crosstracker_widget/' . self::REVERSE_CROSS_LINK_WIDGET_ID . '/reverse_links?target_artifact_id=' . $current_artifact_id . '&tql_query=' . (urlencode($tql_query)) .  '&limit=50&offset=0'),
        );

        self::assertSame(200, $response->getStatusCode());
        $json_response = Json\decode($response->getBody()->getContents());

        self::assertGreaterThan(1, count($json_response['artifacts'][0]['@artifact']));

        self::assertSame('_is_child', $json_response['artifacts'][0]['@link_type']['value']);
    }

    /**
     * Data structure used for the test, ids in parentheses and label text are the one exposed in xml _fixture/forward-link project
     *
     *                                   ┌─────────────────────────┐
     *                                   │                         │
     *                     _is_child     │  artifact A (265)       │    _is_child
     *                        ┌──────────┴───────────┬─────────────┴────────────────────────┐
     *                        │                      │                                      │
     *                        │                      │ _is_child                         │
     *                        │                      │                                      │
     *              ┌─────────▼──────────┐     ┌─────▼───────────────┐       ┌──────────────▼───────────┐
     *              │                    │     │                     │       │                          │
     *              │ artifact B (266)   │     │ artifact C (267)    │       │   artifact D (268)       │
     *              └─────────▲──────────┘     └──────┬────────▲─────┘       └──────────────┬───────────┘
     *                        │                       │        │                            │
     *                        └───────────────────────┘        └────────────────────────────┘
     *                            _is_child                       _is_child
     */
    public function testGetForwardLinksFromCrossTrackerQuery(): void
    {
        $query = http_build_query(
            ['order' => 'asc']
        );

        $artifacts = json_decode(
            $this->getResponseByName(
                BaseTestDataBuilder::ADMIN_USER_NAME,
                $this->request_factory->createRequest('GET', "trackers/$this->forward_cross_tracker_tracker_id/artifacts?$query")
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $artifact_title   = 'artifact A';
        $current_artifact = $this->findItemByTitle($artifacts, $artifact_title);
        self::assertNotNull($current_artifact);
        $current_artifact_id = $current_artifact['id'];

        $tql_query = "SELECT @pretty_title, @link_type FROM @project = 'self' WHERE @id = $current_artifact_id ORDER BY @last_update_date DESC";
        $response  = $this->getResponse(
            $this->request_factory->createRequest('GET', 'crosstracker_widget/' . self::REVERSE_CROSS_LINK_WIDGET_ID . '/forward_links?source_artifact_id=' . $current_artifact_id . '&tql_query=' . (urlencode($tql_query)) .  '&limit=50&offset=0'),
        );

        self::assertSame(200, $response->getStatusCode());
        $json_response = Json\decode($response->getBody()->getContents());

        self::assertGreaterThan(1, count($json_response['artifacts'][0]['@artifact']));

        self::assertSame('_is_child', $json_response['artifacts'][0]['@link_type']['value']);
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
            ->withBody($this->stream_factory->createStream(Json\encode($params))));
        self::assertSame(200, $put_response->getStatusCode());

        $query_id = urlencode($this->query_id);
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "crosstracker_query/$query_id/content?limit=50&offset=0"),
        );

        self::assertSame(200, $response->getStatusCode());
        $cross_tracker_artifacts = Json\decode($response->getBody()->getContents());

        self::assertEmpty($cross_tracker_artifacts['artifacts']);
    }

    public function testGetContent(): void
    {
        $query    = [
            'widget_id' => self::WIDGET_ID,
            'tql_query' => "SELECT @id FROM @project = 'self' WHERE @id = {$this->epic_artifact_ids[1]}",
        ];
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'crosstracker_query/content?query=' . urlencode(Json\encode($query))));

        self::assertSame(200, $response->getStatusCode());
        $json_response = Json\decode($response->getBody()->getContents());
        self::assertCount(2, $json_response['selected']);
        self::assertSame('@artifact', $json_response['selected'][0]['name']);
        self::assertSame('@id', $json_response['selected'][1]['name']);

        self::assertCount(1, $json_response['artifacts']);
        self::assertEquals($this->epic_artifact_ids[1], $json_response['artifacts'][0]['@id']['value']);
    }

    public function testGetContentWithoutBeingTiedToAWidget(): void
    {
        $query    = [
            'tql_query' => "SELECT @id FROM @project = MY_PROJECTS() WHERE @id = {$this->epic_artifact_ids[1]}",
        ];
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'crosstracker_query/content?query=' . urlencode(Json\encode($query))));

        self::assertSame(200, $response->getStatusCode());
        $json_response = Json\decode($response->getBody()->getContents());

        self::assertNotEmpty($json_response['artifacts']);
        self::assertNotEmpty($json_response['selected']);
    }

    public function findItemByTitle(array $items, string $title): ?array
    {
        $index = array_search($title, array_column($items, 'title'), true);
        if ($index === false) {
            return null;
        }
        return $items[$index];
    }
}
