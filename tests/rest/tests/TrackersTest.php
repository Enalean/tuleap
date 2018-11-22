<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All rights reserved
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
namespace Tuleap\REST;

use Tuleap\REST\TrackerBase;

/**
 * @group TrackersTests
 */
class TrackersTest extends TrackerBase
{
    public function testOptionsTrackers()
    {
        $response = $this->getResponse($this->client->options('trackers'));

        $this->assertEquals(array('OPTIONS'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsTrackersId()
    {
        $response = $this->getResponse($this->client->options($this->getReleaseTrackerUri()));

        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsTrackersIdReports()
    {
        $response = $this->getResponse($this->client->options($this->getReleaseTrackerReportsUri()));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsReportsId()
    {
        $response = $this->getResponse($this->client->options($this->report_uri));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsReportsArtifactsId()
    {
        $response = $this->getResponse($this->client->options($this->getReportsArtifactsUri()));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsGetParentArtifacts()
    {
        $response = $this->getResponse($this->client->options('trackers/' . $this->user_stories_tracker_id . '/parent_artifacts'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackersId()
    {
        $tracker_uri = $this->getReleaseTrackerUri();
        $response    = $this->getResponse($this->client->get($tracker_uri));

        $tracker = $response->json();

        $this->assertEquals(basename($tracker_uri), $tracker['id']);
        $this->assertEquals($tracker_uri, $tracker['uri']);
        $this->assertEquals('Releases', $tracker['label']);
        $this->assertEquals('rel', $tracker['item_name']);
        $this->assertEquals($this->project_private_member_id, $tracker['project']['id']);
        $this->assertEquals('projects/'.$this->project_private_member_id, $tracker['project']['uri']);
        $this->assertArrayHasKey('fields', $tracker);
        foreach ($tracker['fields'] as $field) {
            $this->assertArrayHasKey('required', $field);
            $this->assertArrayHasKey('default_value', $field);
            $this->assertArrayHasKey('collapsed', $field);
        }
        $this->assertArrayHasKey('semantics', $tracker);
        $this->assertArrayHasKey('workflow', $tracker);
        $this->assertArrayHasKey('parent', $tracker);
        $this->assertArrayHasKey('structure', $tracker);
        $this->assertArrayHasKey('color_name', $tracker);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackersIdReports()
    {
        $report_uri = $this->getReleaseTrackerReportsUri();
        $response   = $this->getResponse($this->client->get($report_uri));

        $reports        = $response->json();
        $default_report = $reports[0];

        $this->assertEquals($this->report_id, $default_report['id']);
        $this->assertEquals('tracker_reports/' . $this->report_id, $default_report['uri']);
        $this->assertEquals('Default', $default_report['label']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetReportsId()
    {
        $response = $this->getResponse($this->client->get($this->report_uri));

        $report = $response->json();

        $this->assertEquals($this->report_id, $report['id']);
        $this->assertEquals('tracker_reports/' . $this->report_id, $report['uri']);
        $this->assertEquals('Default', $report['label']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetReportsArtifactsId()
    {
        $response  = $this->getResponse($this->client->get($this->getReportsArtifactsUri()));
        $artifacts = $response->json();

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifacts()
    {
        $request   = $this->client->get($this->getReleaseTrackerUri() . '/artifacts');
        $response  = $this->getResponse($request);
        $artifacts = $response->json();

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifactsBasicQuery()
    {
        $query     = json_encode(
            array(
                "Name" => "lease"
            )
        );
        $request   = $this->client->get($this->getReleaseTrackerUri() . '/artifacts?query=' . urlencode($query));
        $response  = $this->getResponse($request);
        $artifacts = $response->json();

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifactsBasicCounterQuery()
    {
        $query = json_encode(
            array(
            "Name" => "wwwxxxyyyzzz"
            )
        );

        $request   = $this->client->get($this->getReleaseTrackerUri() . '/artifacts?values=all&limit=10&query='.urlencode($query));
        $response  = $this->getResponse($request);
        $artifacts = $response->json();

        $this->assertCount(0, $artifacts);
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifactsAdvancedQuery()
    {
        $query = json_encode(
            array(
            "Name" => array(
                "operator"=>"contains",
                "value"=>"lease"
                )
            )
        );
        $request   = $this->client->get($this->getReleaseTrackerUri() . '/artifacts?values=all&query='.urlencode($query));
        $response  = $this->getResponse($request);
        $artifacts = $response->json();

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/'.$this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifactsExpertQuery()
    {
        $query     = "i_want_to='Believe'";
        $request   = $this->client->get('trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query='.$query);
        $response  = $this->getResponse($request);
        $artifacts = $response->json();

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->story_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/'.$this->story_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGetTrackerArtifactsExpertQueryWithNonexistentFieldReturnsError()
    {
        $query     = "nonexistent='Believe'";
        $request   = $this->client->get('trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query='.$query);
        $response  = $this->getResponse($request);
        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGetTrackerArtifactsExpertQueryWithNotSupportedFieldReturnsError()
    {
        $query     = "openlist='On going'";
        $request   = $this->client->get('trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query='.$query);
        $response  = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGetTrackerArtifactsExpertQueryWithASyntaxErrorInQueryReturnsError()
    {
        $query     = "i_want_to='On going";
        $request   = $this->client->get('trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query='.$query);
        $response  = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testGetDeletedTrackerReturnsError()
    {
        $tracker_uri = $this->getDeletedTrackerId();
        $response    = $this->getResponse($this->client->get($tracker_uri));

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testGetParentArtifacts()
    {
        $response         = $this->getResponse($this->client->get('trackers/' . $this->user_stories_tracker_id . '/parent_artifacts'));
        $parent_artifacts = $response->json();

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertCount(5, $parent_artifacts);
        $this->assertEquals($parent_artifacts[0]['title'], "Epic epoc");
        $this->assertEquals($parent_artifacts[1]['title'], "Epic c'est tout");
        $this->assertEquals($parent_artifacts[2]['title'], "Epic pic");
        $this->assertEquals($parent_artifacts[3]['title'], "Fourth epic");
        $this->assertEquals($parent_artifacts[4]['title'], "First epic");
    }

    private function getDeletedTrackerId()
    {
        return "trackers/$this->deleted_tracker_id";
    }

    private function getReleaseTrackerUri()
    {
        $response_plannings = $this->getResponse($this->client->get('projects/'.$this->project_private_member_id.'/plannings'))->json();
        return $response_plannings[0]['milestone_tracker']['uri'];
    }

    private function getReleaseTrackerReportsUri()
    {
        $response_tracker = $this->getResponse($this->client->get($this->getReleaseTrackerUri()))->json();

        foreach ($response_tracker['resources'] as $resource) {
            if ($resource['type'] == 'reports') {
                return $resource['uri'];
            }
        }
    }

    private function getReportsArtifactsUri()
    {
        $response_report = $this->getResponse($this->client->get($this->report_uri))->json();


        foreach ($response_report['resources'] as $resource) {
            if ($resource['type'] == 'artifacts') {
                return $resource['uri'];
            }
        }
    }
}
