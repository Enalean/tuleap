<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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

    public function setUp() : void
    {
        parent::setUp();
        $this->tracker_id = $this->getTrackerId();
    }

    public function testTQLQueries()
    {
        $tests = array(
            ''                                                             => array('bug1', 'bug2', 'bug3'),
            'summary = "bug1"'                                             => array('bug1'),
            'summary = "bug"'                                              => array('bug1', 'bug2', 'bug3'),
            'summary = "bug" and details = "original2"'                    => array('bug2'),
            'remaining_effort between(1, 42)'                              => array('bug1'),
            'remaining_effort > 3.14'                                      => array('bug2'),
            'story_points <= 21'                                           => array('bug1', 'bug2'),
            'story_points = ""'                                            => array('bug3'),
            'story_points != ""'                                           => array('bug1', 'bug2'),
            'due_date = "2017-01-10"'                                      => array('bug2'),
            'timesheeting < "2017-01-18 14:36"'                            => array('bug1'),
            'last_update_date between("2017-01-01", now() + 1w)'           => array('bug1', 'bug2', 'bug3'),
            'submitted_by = MYSELF()'                                      => array('bug1', 'bug2', 'bug3'),
            'submitted_by != MYSELF()'                                     => array(),
            'submitted_by IN (MYSELF())'                                   => array('bug1', 'bug2', 'bug3'),
            'submitted_by NOT IN (MYSELF())'                               => array(),
            'status IN ("todo", "doing") OR ugroups = "Membres du projet"' => array('bug1', 'bug2'),
            'status = ""'                                                  => array('bug2', 'bug3'),
            'ugroups = "Contractors"'                                      => array('bug1'),
            '@comments != ""'                                              => array('bug1'),
            '@comments = "comment"'                                        => array('bug1'),
            '@comments = ""'                                               => array('bug2', 'bug3'),
            'attachment = "file"'                                          => array('bug3'),
            'attachment = "awesome"'                                       => array('bug3'),
            'attachment != "document"'                                     => array('bug1', 'bug2', 'bug3'),
        );
        foreach ($tests as $query => $expectation) {
            $message = "Query $query should returns " . implode(', ', $expectation);

            $response = $this->performExpertQuery($query);
            $this->assertEquals($response->getStatusCode(), 200, $message);

            $artifacts = $response->json();
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
        $body = $response->json();
        $this->assertStringContainsString(
            "Error during parsing expert query",
            $body['error']['message']
        );
    }

    public function testUnknownValueForListFields()
    {
        $response = $this->performExpertQuery('status = "pouet"');
        $this->assertEquals(400, $response->getStatusCode());
        $body = $response->json();
        $this->assertStringContainsString(
            "The value 'pouet' doesn't exist for the list field 'status'",
            $body['error']['message']
        );
    }

    public function testInvalidDateTime()
    {
        $response = $this->performExpertQuery('due_date = "2017-01-10 12:12"');
        $this->assertEquals(400, $response->getStatusCode());
        $body = $response->json();
        $this->assertStringContainsString(
            "The date field 'due_date' cannot be compared to the string value '2017-01-10 12:12'",
            $body['error']['message']
        );
    }

    public function testUnknownField()
    {
        $response = $this->performExpertQuery('test = "bug1"');
        $this->assertEquals(400, $response->getStatusCode());
        $body = $response->json();
        $this->assertStringContainsString(
            "We cannot search on 'test', we don't know what it refers to",
            $body['error']['message']
        );
    }

    private function getTrackerId()
    {
        $project_id = $this->getProjectId(self::PROJECT_NAME);

        $response = $this->getResponse($this->client->get("projects/$project_id/trackers"))->json();

        return $response[0]['id'];
    }

    private function performExpertQuery($query)
    {
        $query = urlencode($query);
        $url   = "trackers/$this->tracker_id/artifacts?expert_query=$query";

        return $this->getResponse(
            $this->client->get($url)
        );
    }
}
