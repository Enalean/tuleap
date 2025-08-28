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

use Psr\Http\Message\ResponseInterface;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('TrackersTests')]
final class TrackersTest extends TrackerBase
{
    public function testOptionsTrackers(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'trackers'));

        self::assertEqualsCanonicalizing(['OPTIONS'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsTrackersWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'trackers'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertEqualsCanonicalizing(['OPTIONS'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsTrackersId(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', $this->getReleaseTrackerUri()));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsTrackersIdWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', $this->getReleaseTrackerUri()),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsTrackersIdReports(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', $this->getReleaseTrackerReportsUri()));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsTrackersIdReportsForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', $this->getReleaseTrackerReportsUri()),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 200);
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOptionsReportsId()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', $this->report_uri));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOptionsReportsIdForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', $this->report_uri),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 200);
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOptionsReportsArtifactsId(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', $this->getReportsArtifactsUri()));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsReportsArtifactsIdForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', $this->getReportsArtifactsUri()),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 200);
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOptionsGetParentArtifacts(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'OPTIONS',
                'trackers/' . $this->user_stories_tracker_id . '/parent_artifacts'
            )
        );

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testOptionsGetParentArtifactsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'OPTIONS',
                'trackers/' . $this->user_stories_tracker_id . '/parent_artifacts'
            ),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetTrackersId(): void
    {
        $tracker_uri = $this->getReleaseTrackerUri();
        $response    = $this->getResponse($this->request_factory->createRequest('GET', $tracker_uri));

        $this->assertGETTrackersId($response, $tracker_uri);
    }

    public function testGETTrackersIdWithReadOnlyAdmin(): void
    {
        $tracker_uri = $this->getReleaseTrackerUri();
        $response    = $this->getResponse(
            $this->request_factory->createRequest('GET', $tracker_uri),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETTrackersId($response, $tracker_uri);
    }

    private function assertGETTrackersId(ResponseInterface $response, string $tracker_uri): void
    {
        $tracker = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

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
        $tracker = json_decode(
            $this->getResponseByName(
                BaseTestDataBuilder::TEST_USER_1_NAME,
                $this->request_factory->createRequest('GET', $this->getReleaseTrackerUri())
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $all_user_groups   = json_decode(
            $this->getResponse(
                $this->request_factory->createRequest(
                    'GET',
                    'projects/' . $this->project_private_member_id . '/user_groups'
                )
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $developers_ugroup = [];
        $static_ugroup_2   = [];
        $project_members   = [];
        foreach ($all_user_groups as $user_group) {
            unset($user_group['additional_information']);
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
                'can_access' => [
                    [
                        'id'         => '1',
                        'uri'        => 'user_groups/1',
                        'label'      => 'Anonymous',
                        'users_uri'  => 'user_groups/1/users',
                        'short_name' => 'all_users',
                        'key'        => 'ugroup_anonymous_users_name_key',
                    ],
                ],
                'can_access_submitted_by_user'  => [],
                'can_access_assigned_to_group'  => [],
                'can_access_submitted_by_group' => [],
                'can_admin'                     => [
                    $developers_ugroup,
                ],
            ],
            $tracker['permissions_for_groups']
        );

        $status_field = $this->getStatusField($tracker);
        $this->assertEquals(
            [
                'can_read'   => [
                    [
                        'id'         => '1',
                        'uri'        => 'user_groups/1',
                        'label'      => 'Anonymous',
                        'users_uri'  => 'user_groups/1/users',
                        'short_name' => 'all_users',
                        'key'        => 'ugroup_anonymous_users_name_key',
                    ],
                ],
                'can_submit' => [
                    [
                        'id'         => '2',
                        'uri'        => 'user_groups/2',
                        'label'      => 'Registered users',
                        'users_uri'  => 'user_groups/2/users',
                        'short_name' => 'registered_users',
                        'key'        => 'ugroup_registered_users_name_key',
                    ],
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
        $tracker = json_decode(
            $this->getResponseByName(
                BaseTestDataBuilder::TEST_USER_2_NAME,
                $this->request_factory->createRequest('GET', $this->getReleaseTrackerUri())
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

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
        $response   = $this->getResponse($this->request_factory->createRequest('GET', $report_uri));
        $this->assertGETTrackersIdReports($response);
    }

    public function testGetTrackersIdReportsForReadOnlyUser(): void
    {
        $report_uri = $this->getReleaseTrackerReportsUri();
        $response   = $this->getResponse($this->request_factory->createRequest('GET', $report_uri), RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $this->assertGETTrackersIdReports($response);
    }

    private function assertGETTrackersIdReports(ResponseInterface $response): void
    {
        $reports        = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $default_report = $reports[0];

        self::assertEquals(200, $response->getStatusCode());

        self::assertEquals($this->report_id, $default_report['id']);
        self::assertEquals('tracker_reports/' . $this->report_id, $default_report['uri']);
        self::assertEquals('Default', $default_report['label']);
        self::assertEquals(true, $default_report['is_public']);
    }

    public function testGetReportsId(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', $this->report_uri));

        $this->assertGETReportsId($response);
    }

    public function testGetReportsIdFoReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', $this->report_uri),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETReportsId($response);
    }

    private function assertGETReportsId(ResponseInterface $response): void
    {
        $report = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(200, $response->getStatusCode());

        self::assertEquals($this->report_id, $report['id']);
        self::assertEquals('tracker_reports/' . $this->report_id, $report['uri']);
        self::assertEquals('Default', $report['label']);
        self::assertEquals(true, $report['is_public']);
    }

    public function testGetReportsArtifactsId()
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', $this->getReportsArtifactsUri()));
        $this->assertGETReportsByArtifactsId($response);
    }

    public function testGetReportsByArtifactsIdForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', $this->getReportsArtifactsUri()),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertGETReportsByArtifactsId($response);
    }

    private function assertGETReportsByArtifactsId(ResponseInterface $response): void
    {
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifacts(): void
    {
        $request  = $this->request_factory->createRequest('GET', $this->getReleaseTrackerUri() . '/artifacts');
        $response = $this->getResponse($request);

        $this->assertGETTrackerArtifacts($response);
    }

    public function testGetTrackerArtifactsWithReadOnlyAdmin(): void
    {
        $request  = $this->request_factory->createRequest('GET', $this->getReleaseTrackerUri() . '/artifacts');
        $response = $this->getResponse($request, RESTTestDataBuilder::TEST_BOT_USER_NAME);

        $this->assertGETTrackerArtifacts($response);
    }

    private function assertGETTrackerArtifacts(ResponseInterface $response): void
    {
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetTrackerArtifactsBasicQuery()
    {
        $query     = json_encode(
            [
                'Name' => 'lease',
            ]
        );
        $request   = $this->request_factory->createRequest('GET', $this->getReleaseTrackerUri() . '/artifacts?query=' . urlencode($query));
        $response  = $this->getResponse($request);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifactsBasicQueryWithNonExisingField()
    {
        $query    = json_encode(
            [
                'Nonexisting' => 'lease',
            ]
        );
        $request  = $this->request_factory->createRequest('GET', $this->getReleaseTrackerUri() . '/artifacts?query=' . urlencode($query));
        $response = $this->getResponse($request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testGetTrackerArtifactsBasicCounterQuery()
    {
        $query = json_encode(
            [
                'Name' => 'wwwxxxyyyzzz',
            ]
        );

        $request   = $this->request_factory->createRequest('GET', $this->getReleaseTrackerUri() . '/artifacts?values=all&limit=10&query=' . urlencode($query));
        $response  = $this->getResponse($request);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(0, $artifacts);
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifactsAdvancedQuery()
    {
        $query     = json_encode(
            [
                'Name' => [
                    'operator' => 'contains',
                    'value' => 'lease',
                ],
            ]
        );
        $request   = $this->request_factory->createRequest('GET', $this->getReleaseTrackerUri() . '/artifacts?values=all&query=' . urlencode($query));
        $response  = $this->getResponse($request);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->release_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->release_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifactsExpertQuery()
    {
        $query     = "i_want_to='Believe'";
        $request   = $this->request_factory->createRequest('GET', 'trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query=' . $query);
        $response  = $this->getResponse($request);
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $first_artifact_info = $artifacts[0];
        $this->assertEquals($this->story_artifact_ids[1], $first_artifact_info['id']);
        $this->assertEquals('artifacts/' . $this->story_artifact_ids[1], $first_artifact_info['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetTrackerArtifactsExpertQueryWithNonexistentFieldReturnsError()
    {
        $query    = "nonexistent='Believe'";
        $request  = $this->request_factory->createRequest('GET', 'trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query=' . $query);
        $response = $this->getResponse($request);
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGetTrackerArtifactsExpertQueryWithNotSupportedFieldReturnsError()
    {
        $query    = "openlist='On going'";
        $request  = $this->request_factory->createRequest('GET', 'trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query=' . $query);
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGetTrackerArtifactsExpertQueryWithASyntaxErrorInQueryReturnsError()
    {
        $query    = "i_want_to='On going";
        $request  = $this->request_factory->createRequest('GET', 'trackers/' . $this->user_stories_tracker_id . '/artifacts?values=all&expert_query=' . $query);
        $response = $this->getResponse($request);

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGetDeletedTrackerReturnsError(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', "trackers/$this->deleted_tracker_id"));

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('deleted', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);
    }

    public function testGetParentArtifacts(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                'trackers/' . $this->user_stories_tracker_id . '/parent_artifacts'
            )
        );

        $this->assertGETParentArtifacts($response);
    }

    public function testGetParentArtifactsWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                'trackers/' . $this->user_stories_tracker_id . '/parent_artifacts'
            ),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETParentArtifacts($response);
    }

    public function testGetUsedArtifactLinks(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'trackers/' . $this->user_stories_tracker_id . '/used_artifact_links'),
        );

        self::assertEquals(200, $response->getStatusCode());
        $representations = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $shortnames = [];
        foreach ($representations as $representation) {
            $shortnames[] = $representation['shortname'];
        }

        self::assertContains('_is_child', $shortnames);
    }

    private function assertGETParentArtifacts(ResponseInterface $response): void
    {
        $parent_artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(5, $parent_artifacts);
        self::assertSame('Epic epoc', $parent_artifacts[0]['title']);
        self::assertSame("Epic c'est tout", $parent_artifacts[1]['title']);
        self::assertSame('Epic pic', $parent_artifacts[2]['title']);
        self::assertSame('Fourth epic', $parent_artifacts[3]['title']);
        self::assertSame('First epic', $parent_artifacts[4]['title']);
    }

    private function getReleaseTrackerUri()
    {
        $response_plannings = json_decode(
            $this->getResponse(
                $this->request_factory->createRequest(
                    'GET',
                    'projects/' . $this->project_private_member_id . '/plannings'
                )
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        return $response_plannings[0]['milestone_tracker']['uri'];
    }

    private function getReleaseTrackerReportsUri()
    {
        $response_tracker = json_decode(
            $this->getResponse(
                $this->request_factory->createRequest('GET', $this->getReleaseTrackerUri())
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($response_tracker['resources'] as $resource) {
            if ($resource['type'] == 'reports') {
                return $resource['uri'];
            }
        }
    }

    private function getReportsArtifactsUri()
    {
        $response_report = json_decode(
            $this->getResponse(
                $this->request_factory->createRequest('GET', $this->report_uri)
            )->getBody()->getContents(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($response_report['resources'] as $resource) {
            if ($resource['type'] == 'artifacts') {
                return $resource['uri'];
            }
        }
    }
}
