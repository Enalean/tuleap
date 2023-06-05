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
            'WITHOUT PARENT'                                               => ['bug2', 'bug3'],
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

    public function testInvalidQuery()
    {
        $response = $this->performExpertQuery('summary="bug1');
        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString(
            "Error during parsing expert query",
            $body['error']['message']
        );
    }

    public function testUnknownValueForListFields()
    {
        $response = $this->performExpertQuery('status = "pouet"');
        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString(
            "The value 'pouet' doesn't exist for the list field 'status'",
            $body['error']['message']
        );
    }

    public function testInvalidDateTime()
    {
        $response = $this->performExpertQuery('due_date = "2017-01-10 12:12"');
        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString(
            "The date field 'due_date' cannot be compared to the string value '2017-01-10 12:12'",
            $body['error']['message']
        );
    }

    public function testUnknownField()
    {
        $response = $this->performExpertQuery('test = "bug1"');
        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString(
            "We cannot search on 'test', we don't know what it refers to",
            $body['error']['message']
        );
    }

    private function getTrackerId()
    {
        $project_id = $this->getProjectId(self::PROJECT_NAME);

        $response = json_decode($this->getResponse($this->request_factory->createRequest('GET', "projects/$project_id/trackers"))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $response[0]['id'];
    }

    private function performExpertQuery($query)
    {
        $query = urlencode($query);
        $url   = "trackers/$this->tracker_id/artifacts?expert_query=$query";

        return $this->getResponse(
            $this->request_factory->createRequest('GET', $url)
        );
    }
}
