<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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

require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group TrackersTests
 */
class TrackersTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_NAME),
            $request
        );
    }

    public function testOptionsTrackers() {
        $response = $this->getResponse($this->client->options('trackers'));

        $this->assertEquals(array('OPTIONS'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsTrackersId() {
        $response = $this->getResponse($this->client->options($this->getReleaseTrackerUri()));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsTrackersIdReports() {
        $response = $this->getResponse($this->client->options($this->getReleaseTrackerReportsUri()));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsReportsId() {
        $response = $this->getResponse($this->client->options($this->getReportUri()));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsReportsArtifactsId() {
        $response = $this->getResponse($this->client->options($this->getReportsArtifactsUri()));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackersId() {
        $tracker_uri = $this->getReleaseTrackerUri();
        $response    = $this->getResponse($this->client->get($tracker_uri));

        $tracker = $response->json();

        $this->assertEquals(basename($tracker_uri), $tracker['id']);
        $this->assertEquals($tracker_uri, $tracker['uri']);
        $this->assertEquals('Releases', $tracker['label']);
        $this->assertEquals('rel', $tracker['item_name']);
        $this->assertEquals(101, $tracker['project']['id']);
        $this->assertEquals('projects/101', $tracker['project']['uri']);
        $this->assertArrayHasKey('fields', $tracker);
        $this->assertArrayHasKey('semantics', $tracker);
        $this->assertArrayHasKey('workflow', $tracker);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackersIdReports() {
        $report_uri = $this->getReleaseTrackerReportsUri();
        $response   = $this->getResponse($this->client->get($report_uri));

        $reports = $response->json();
        $default_report = $reports[0];

        $this->assertEquals(102, $default_report['id']);
        $this->assertEquals('tracker_reports/102', $default_report['uri']);
        $this->assertEquals('Default', $default_report['label']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetReportsId() {
        $response = $this->getResponse($this->client->get($this->getReportUri()));

        $report = $response->json();

        $this->assertEquals(102, $report['id']);
        $this->assertEquals('tracker_reports/102', $report['uri']);
        $this->assertEquals('Default', $report['label']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetReportsArtifactsId() {
        $response = $this->getResponse($this->client->get($this->getReportsArtifactsUri()));
        var_dump($response->getBody(1));
        $artifacts = $response->json();

        $first_artifact_info = $artifacts[0];
        $this->assertEquals(1, $first_artifact_info['id']);
        $this->assertEquals('artifacts/1', $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    private function getReleaseTrackerUri() {
        $response_plannings = $this->getResponse($this->client->get('projects/101/plannings'))->json();
        return $response_plannings[0]['milestone_tracker']['uri'];
    }

    private function getReleaseTrackerReportsUri() {
        $response_tracker = $this->getResponse($this->client->get($this->getReleaseTrackerUri()))->json();

        foreach ($response_tracker['resources'] as $resource) {
            if ($resource['type'] == 'reports') {
                return $resource['uri'];
            }
        }
    }

    private function getReportUri() {
        $reports_uri = $this->getReleaseTrackerReportsUri();
        $response_reports = $this->getResponse($this->client->get($reports_uri))->json();

        return $response_reports[0]['uri'];
    }

    private function getReportsArtifactsUri() {
        $report_uri = $this->getReportUri();
        $response_report = $this->getResponse($this->client->get($report_uri))->json();


        foreach ($response_report['resources'] as $resource) {
            if ($resource['type'] == 'artifacts') {
                return $resource['uri'];
            }
        }
    }

}