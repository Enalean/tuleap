<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

use Guzzle\Http\Message\Response;

/**
 * @group TrackersTests
 */
final class TrackersTest extends TrackerBase
{
    public function testOptionsTrackers(): void
    {
        $response = $this->getResponse($this->client->options('trackers'));

        $this->assertEquals(array('OPTIONS'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsTrackersWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('trackers'),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsTrackersId(): void
    {
        $response = $this->getResponse($this->client->options($this->getReleaseTrackerUri()));

        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsTrackersIdWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options($this->getReleaseTrackerUri()),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsTrackersIdReports(): void
    {
        $response = $this->getResponse($this->client->options($this->getReleaseTrackerReportsUri()));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsTrackersIdReportsForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->options($this->getReleaseTrackerReportsUri()),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOptionsReportsId()
    {
        $response = $this->getResponse($this->client->options($this->report_uri));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsReportsIdForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->options($this->report_uri),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOptionsReportsArtifactsId(): void
    {
        $response = $this->getResponse($this->client->options($this->getReportsArtifactsUri()));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsReportsArtifactsIdForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->options($this->getReportsArtifactsUri()),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOptionsGetParentArtifacts(): void
    {
        $response = $this->getResponse($this->client->options('trackers/' . $this->user_stories_tracker_id . '/parent_artifacts'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsGetParentArtifactsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('trackers/' . $this->user_stories_tracker_id . '/parent_artifacts'),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetTrackersId(): void
    {
        $tracker_uri = $this->getReleaseTrackerUri();
        $response = $this->getResponse($this->client->get($tracker_uri));

        $this->assertGETTrackersId($response, $tracker_uri);
    }

    public function testGETTrackersIdWithReadOnlyAdmin(): void
    {
        $tracker_uri = $this->getReleaseTrackerUri();
        $response = $this->getResponse(
            $this->client->get($tracker_uri),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETTrackersId($response, $tracker_uri);
    }

    private function assertGETTrackersId(Response $response, string $tracker_uri): void
    {
        $tracker = $response->json();

        $this->assertEquals(basename($tracker_uri), $tracker['id']);
        $this->assertEquals($tracker_uri, $tracker['uri']);
        $this->assertEquals('Releases', $tracker['label']);
        $this->assertEquals('rel', $tracker['item_name']);
        $this->assertEquals($this->project_private_member_id, $tracker['project']['id']);
        $this->assertEquals('projects/' . $this->project_private_member_id, $tracker['project']['uri']);
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
        $this->assertArrayHasKey('permissions_for_groups', $tracker);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetTrackersIdReturnsPermissionsForGroupsToTrackerAdmin()
    {
        $tracker = $this->getResponseByName(
            \TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get($this->getReleaseTrackerUri())
        )->json();

        $all_user_groups = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/user_groups'))->json();
        $developers_ugroup = [];
        $static_ugroup_2   = [];
        $project_members   = [];
        foreach ($all_user_groups as $user_group) {
            if ($user_group['short_name'] === 'developers') {
                $developers_ugroup = $user_group;
            }
            if ($user_group['short_name'] === 'static_ugroup_2') {
                $static_ugroup_2 = $user_group;
            }
            if ($user_group['short_name'] === 'project_members') {
                $project_members = $user_group;
            }
        }

        $this->assertEquals(
            [
                "can_access" => [
                    [
                        "id"         => "1",
                        "uri"        => "user_groups/1",
                        "label"      => "Anonymous",
                        "users_uri"  => "user_groups/1/users",
                        "short_name" => "all_users",
                        "key"        => "ugroup_anonymous_users_name_key",
                    ]
                ],
                "can_access_submitted_by_user"  => [],
                "can_access_assigned_to_group"  => [],
                "can_access_submitted_by_group" => [],
                "can_admin"                     => [
                    $developers_ugroup
                ],
            ],
            $tracker['permissions_for_groups']
        );

        $status_field = $this->getStatusField($tracker);
        $this->assertEquals(
            [
                'can_read'   => [
                    [
                        "id"         => "1",
                        "uri"        => "user_groups/1",
                        "label"      => "Anonymous",
                        "users_uri"  => "user_groups/1/users",
                        "short_name" => "all_users",
                        "key"        => "ugroup_anonymous_users_name_key",
                    ]
                ],
                'can_submit' => [
                    [
                        "id"         => "2",
                        "uri"        => "user_groups/2",
                        "label"      => "Registered users",
                        "users_uri"  => "user_groups/2/users",
                        "short_name" => "registered_users",
                        "key"        => "ugroup_registered_users_name_key",
                    ]
                ],
                'can_update' => [
                    $project_members,
                    $static_ugroup_2,
                ],
            ],
            $status_field['permissions_for_groups']
        );
    }

    public function testGetTrackersIdReturnsNoPermissionsForGroupsToRegularUsers()
    {
        $tracker = $this->getResponseByName(
            \TestDataBuilder::TEST_USER_2_NAME,
            $this->client->get($this->getReleaseTrackerUri())
        )->json();

        $status_field = $this->getStatusField($tracker);

        $this->assertEquals(null, $tracker['permissions_for_groups']);
        $this->assertEquals(null, $status_field['permissions_for_groups']);
    }

    private function getStatusField(array $tracker): array
    {
        foreach ($tracker['fields'] as $field) {
            if ($field['label'] === 'Status') {
                return $field;
            }
        }
        return [];
    }

    public function testGetTrackersIdReports(): void
    {
        $report_uri = $this->getReleaseTrackerReportsUri();
        $response   = $this->getResponse($this->client->get($report_uri));
        $this->assertGETTrackersIdReports($response);
    }

    public function testGetTrackersIdReportsForReadOnlyUser(): void
    {
        $report_uri = $this->getReleaseTrackerReportsUri();
        $response   = $this->getResponse($this->client->get($report_uri), \REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $this->assertGETTrackersIdReports($response);
    }

    private function assertGETTrackersIdReports(Response $response): void
    {
        $reports        = $response->json();
        $default_report = $reports[0];

        $this->assertEquals($this->report_id, $default_report['id']);
        $this->assertEquals('tracker_reports/' . $this->report_id, $default_report['uri']);
        $this->assertEquals('Default', $default_report['label']);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetReportsId(): void
    {
        $response = $this->getResponse($this->client->get($this->report_uri));

        $this->assertGETReportsId($response);
    }

    public function testGetReportsIdFoReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->get($this->report_uri),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETReportsId($response);
    }

    private function assertGETReportsId(Response $response): void
    {
        $report = $response->json();

        $this->assertEquals($this->report_id, $report['id']);
        $this->assertEquals('tracker_reports/' . $this->report_id, $report['uri']);
        $this->assertEquals('Default', $report['label']);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetReportsArtifactsId()
    {
        $response = $this->getResponse($this->client->get($this->getReportsArtifactsUri()));
        $this->assertGETReportsByArtifactsId($response);
    }

    public function testGetReportsByArtifactsIdForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->get($this->getReportsArtifactsUri()),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertGETReportsByArtifactsId($response);
    }

    private function assertGETReportsByArtifactsId(Response $response): void
    {
        $artifacts = $response->json();

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifacts(): void
    {
        $request = $this->client->get($this->getReleaseTrackerUri() . '/artifacts');
        $response = $this->getResponse($request);

        $this->assertGETTrackerArtifacts($response);
    }

    public function testGetTrackerArtifactsWithReadOnlyAdmin(): void
    {
        $request = $this->client->get($this->getReleaseTrackerUri() . '/artifacts');
        $response = $this->getResponse($request, \REST_TestDataBuilder::TEST_BOT_USER_NAME);

        $this->assertGETTrackerArtifacts($response);
    }

    private function assertGETTrackerArtifacts(Response $response): void
    {
        $artifacts = $response->json();

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals(200, $response->getStatusCode());
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

        $request   = $this->client->get($this->getReleaseTrackerUri() . '/artifacts?values=all&limit=10&query=' . urlencode($query));
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
                "operator" => "contains",
                "value" => "lease"
                )
            )
        );
        $request   = $this->client->get($this->getReleaseTrackerUri() . '/artifacts?values=all&query=' . urlencode($query));
        $response  = $this->getResponse($request);
        $artifacts = $response->json();

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifactsExpertQuery()
    {
        $query     = "i_want_to='Believe'";
        $request   = $this->client->get('trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query=' . $query);
        $response  = $this->getResponse($request);
        $artifacts = $response->json();

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->story_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->story_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifactsExpertQueryWithNonexistentFieldReturnsError()
    {
        $query     = "nonexistent='Believe'";
        $request   = $this->client->get('trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query=' . $query);
        $response  = $this->getResponse($request);
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGetTrackerArtifactsExpertQueryWithNotSupportedFieldReturnsError()
    {
        $query     = "openlist='On going'";
        $request   = $this->client->get('trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query=' . $query);
        $response  = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGetTrackerArtifactsExpertQueryWithASyntaxErrorInQueryReturnsError()
    {
        $query     = "i_want_to='On going";
        $request   = $this->client->get('trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query=' . $query);
        $response  = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGetDeletedTrackerReturnsError(): void
    {
        $response = $this->getResponse($this->client->get("trackers/$this->deleted_tracker_id"));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('deleted', $response->json()['error']['i18n_error_message']);
    }

    public function testGetParentArtifacts(): void
    {
        $response = $this->getResponse($this->client->get('trackers/' . $this->user_stories_tracker_id . '/parent_artifacts'));

        $this->assertGETParentArtifacts($response);
    }

    public function testGetParentArtifactsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get('trackers/' . $this->user_stories_tracker_id . '/parent_artifacts'),
            \REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETParentArtifacts($response);
    }

    private function assertGETParentArtifacts(Response $response): void
    {
        $parent_artifacts = $response->json();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(5, $parent_artifacts);
        $this->assertEquals($parent_artifacts[0]['title'], "Epic epoc");
        $this->assertEquals($parent_artifacts[1]['title'], "Epic c'est tout");
        $this->assertEquals($parent_artifacts[2]['title'], "Epic pic");
        $this->assertEquals($parent_artifacts[3]['title'], "Fourth epic");
        $this->assertEquals($parent_artifacts[4]['title'], "First epic");
    }

    private function getReleaseTrackerUri()
    {
        $response_plannings = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/plannings'))->json();
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
