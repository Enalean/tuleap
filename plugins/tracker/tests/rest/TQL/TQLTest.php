<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\Tests\REST\TQL;

use Psr\Http\Message\ResponseInterface;
use RestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group TrackerTests
 */
class TQLTest extends RestBase
{
    public const PROJECT_NAME = 'tql';

    private $tracker_id;

    public function setUp(): void
    {
        parent::setUp();
        $this->tracker_id = $this->getTrackerId();
    }

    public function testTQLQueries()
    {
        $tests = [
            ''                                                             => ['bug1', 'bug2', 'bug3'],
            'summary = "bug1"'                                             => ['bug1'],
            'summary = "bug"'                                              => ['bug1', 'bug2', 'bug3'],
            'summary = "bug" and details = "original2"'                    => ['bug2'],
            'remaining_effort between(1, 42)'                              => ['bug1'],
            'remaining_effort > 3.14'                                      => ['bug2'],
            'story_points <= 21'                                           => ['bug1', 'bug2'],
            'story_points = ""'                                            => ['bug3'],
            'story_points != ""'                                           => ['bug1', 'bug2'],
            'due_date = "2017-01-10"'                                      => ['bug2'],
            'timesheeting < "2017-01-18 14:36"'                            => ['bug1'],
            'last_update_date between("2017-01-01", now() + 1w)'           => ['bug1', 'bug2', 'bug3'],
            'submitted_by = MYSELF()'                                      => ['bug1', 'bug2', 'bug3'],
            'submitted_by != MYSELF()'                                     => [],
            'submitted_by IN (MYSELF())'                                   => ['bug1', 'bug2', 'bug3'],
            'submitted_by NOT IN (MYSELF())'                               => [],
            'status IN ("todo", "doing") OR ugroups = "Membres du projet"' => ['bug1', 'bug2'],
            'status = ""'                                                  => ['bug2', 'bug3'],
            'status != ""'                                                 => ['bug1'],
            'status != "todo"'                                             => ['bug2', 'bug3'],
            'status = "" OR (status = "" OR status = "")'                  => ['bug2', 'bug3'],
            'status = "" AND (status = "" OR status = "")'                 => ['bug2', 'bug3'],
            'ugroups = "Contractors"'                                      => ['bug1'],
            '@comments != ""'                                              => ['bug1', 'bug2'],
            '@comments = "comment"'                                        => ['bug1'],
            '@comments = ""'                                               => ['bug3'],
            '@comments = "private followup"'                               => [],
            '@comments = "Everybody can see it"'                           => ['bug2'],
            'attachment = "file"'                                          => ['bug3'],
            'attachment = "awesome"'                                       => ['bug3'],
            'attachment != "document"'                                     => ['bug1', 'bug2', 'bug3'],
            'WITH PARENT'                                                  => ['bug1'],
            'IS LINKED FROM WITH TYPE "_is_child"'                         => ['bug1'],
            'WITH PARENT TRACKER = "tql"'                                  => ['bug1'],
            'IS LINKED FROM TRACKER = "tql" WITH TYPE "_is_child"'         => ['bug1'],
            'WITHOUT PARENT'                                               => ['bug2', 'bug3'],
            'IS NOT LINKED FROM WITH TYPE "_is_child"'                     => ['bug2', 'bug3'],
            'WITHOUT PARENT TRACKER = "epic"'                              => ['bug1', 'bug2', 'bug3'],
            'IS NOT LINKED FROM TRACKER = "epic" WITH TYPE "_is_child"'    => ['bug1', 'bug2', 'bug3'],
            'WITH CHILD'                                                   => ['bug2'],
            'WITH CHILDREN'                                                => ['bug2'],
            'IS LINKED TO WITH TYPE "_is_child"'                           => ['bug2'],
            'WITH CHILDREN TRACKER = "tql"'                                => ['bug2'],
            'IS LINKED TO TRACKER = "tql" WITH TYPE "_is_child"'           => ['bug2'],
            'WITHOUT CHILD'                                                => ['bug1', 'bug3'],
            'WITHOUT CHILDREN'                                             => ['bug1', 'bug3'],
            'IS NOT LINKED TO WITH TYPE "_is_child"'                       => ['bug1', 'bug3'],
            'WITHOUT CHILDREN TRACKER = "epic"'                            => ['bug1', 'bug2', 'bug3'],
            'IS NOT LINKED TO TRACKER = "epic" WITH TYPE "_is_child"'      => ['bug1', 'bug2', 'bug3'],
            'IS LINKED FROM'                                               => ['bug1'],
            'IS LINKED FROM TRACKER = "tql"'                               => ['bug1'],
            'IS LINKED TO'                                                 => ['bug2'],
            'IS LINKED TO TRACKER = "tql"'                                 => ['bug2'],
            'IS NOT LINKED FROM'                                           => ['bug2', 'bug3'],
            'IS NOT LINKED FROM TRACKER = "tql"'                           => ['bug2', 'bug3'],
            'IS NOT LINKED TO'                                             => ['bug1', 'bug3'],
            'IS NOT LINKED TO TRACKER = "tql"'                             => ['bug1', 'bug3'],
            'IS COVERED'                                                   => [],
            'IS COVERED BY artifact = 123'                                 => [],
            'IS COVERING'                                                  => [],
            'IS COVERING artifact = 123'                                   => [],

        ];
        foreach ($tests as $query => $expectation) {
            $message = "Query $query should returns " . implode(', ', $expectation);

            $response = $this->performExpertQuery($query);
            $this->assertEquals($response->getStatusCode(), 200, $message);

            $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $this->assertCount(count($expectation), $artifacts, $message);
            foreach ($expectation as $index => $title) {
                $this->assertEquals($title, $artifacts[$index]['title'], $message);
            }
        }
    }

    public function testLinkConditions(): void
    {
        $response = $this->performExpertQuery('summary = "bug2"');
        $this->assertEquals($response->getStatusCode(), 200);

        $artifact = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)[0];

        $response = $this->performExpertQuery('WITH PARENT ARTIFACT = ' . $artifact['id']);
        $this->assertEquals($response->getStatusCode(), 200);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $artifacts);
        self::assertEquals('bug1', $artifacts[0]['title']);
        $bug1_id = $artifacts[0]['id'];

        $response = $this->performExpertQuery('WITHOUT PARENT ARTIFACT = ' . $artifact['id']);
        $this->assertEquals($response->getStatusCode(), 200);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $artifacts);
        self::assertEquals('bug2', $artifacts[0]['title']);
        self::assertEquals('bug3', $artifacts[1]['title']);

        $response = $this->performExpertQuery('IS LINKED FROM ARTIFACT = ' . $artifact['id'] . ' WITH TYPE "_is_child"');
        $this->assertEquals($response->getStatusCode(), 200);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $artifacts);
        self::assertEquals('bug1', $artifacts[0]['title']);
        $bug1_id = $artifacts[0]['id'];

        $response = $this->performExpertQuery('IS NOT LINKED FROM ARTIFACT = ' . $artifact['id'] . ' WITH TYPE "_is_child"');
        $this->assertEquals($response->getStatusCode(), 200);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $artifacts);
        self::assertEquals('bug2', $artifacts[0]['title']);
        self::assertEquals('bug3', $artifacts[1]['title']);

        $response = $this->performExpertQuery('WITH CHILDREN ARTIFACT = ' . $bug1_id);
        $this->assertEquals($response->getStatusCode(), 200);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $artifacts);
        self::assertEquals('bug2', $artifacts[0]['title']);

        $response = $this->performExpertQuery('WITHOUT CHILDREN ARTIFACT = ' . $bug1_id);
        $this->assertEquals($response->getStatusCode(), 200);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $artifacts);
        self::assertEquals('bug1', $artifacts[0]['title']);
        self::assertEquals('bug3', $artifacts[1]['title']);

        $response = $this->performExpertQuery('IS LINKED TO ARTIFACT = ' . $bug1_id . ' WITH TYPE "_is_child"');
        $this->assertEquals($response->getStatusCode(), 200);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $artifacts);
        self::assertEquals('bug2', $artifacts[0]['title']);

        $response = $this->performExpertQuery('IS NOT LINKED TO ARTIFACT = ' . $bug1_id . ' WITH TYPE "_is_child"');
        $this->assertEquals($response->getStatusCode(), 200);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $artifacts);
        self::assertEquals('bug1', $artifacts[0]['title']);
        self::assertEquals('bug3', $artifacts[1]['title']);
    }

    public function testInvalidQuery(): void
    {
        $response = $this->performExpertQuery('summary="bug1');
        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString(
            "Error during parsing expert query",
            $body['error']['message']
        );
    }

    public function testUnknownValueForListFields(): void
    {
        $response = $this->performExpertQuery('status = "pouet"');
        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString(
            "The value 'pouet' doesn't exist for the list field 'status'",
            $body['error']['message']
        );
    }

    public function testInvalidDateTime(): void
    {
        $response = $this->performExpertQuery('due_date = "2017-01-10 12:12"');
        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString(
            "The date field 'due_date' cannot be compared to the string value '2017-01-10 12:12'",
            $body['error']['message']
        );
    }

    public function testUnknownField(): void
    {
        $response = $this->performExpertQuery('test = "bug1"');
        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString(
            "We cannot search on 'test', we don't know what it refers to",
            $body['error']['message']
        );
    }

    private function getTrackerId(): int
    {
        $project_id = $this->getProjectId(self::PROJECT_NAME);

        $response = json_decode($this->getResponse($this->request_factory->createRequest('GET', "projects/$project_id/trackers"))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return (int) $response[0]['id'];
    }

    private function performExpertQuery($query): ResponseInterface
    {
        $query = urlencode($query);
        $url   = "trackers/$this->tracker_id/artifacts?expert_query=$query";

        return $this->getResponse(
            $this->request_factory->createRequest('GET', $url)
        );
    }
}
