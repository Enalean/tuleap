<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All rights reserved
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

namespace Tuleap\REST\ReadOnlyAdministrator;

use Psr\Http\Message\ResponseInterface;
use REST_TestDataBuilder;
use Tuleap\REST\ProjectBase;

/**
 * @group ProjectTests
 */
class ProjectTest extends ProjectBase
{
    protected function getResponse($request, $user_name = REST_TestDataBuilder::TEST_BOT_USER_NAME): ResponseInterface
    {
        return parent::getResponse($request, $user_name);
    }

    public function testPOST(): void
    {
        $post_resource = json_encode([
            'label' => 'Test Request 9747 read only',
            'shortname'  => 'test9747-ro',
            'description' => 'Test of Request 9747 for REST API Project Creation',
            'is_public' => true,
            'template_id' => $this->project_public_template_id,
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest(
                'POST',
                'projects'
            )->withBody(
                $this->stream_factory->createStream($post_resource)
            )
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testGET(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects'));

        $this->assertEquals(200, $response->getStatusCode());

        $json_projects = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue(
            $this->valuesArePresent(
                [
                    $this->project_private_member_id,
                    $this->project_public_id,
                    $this->project_public_member_id,
                    $this->project_pbi_id,
                ],
                $this->getIds($json_projects)
            )
        );

        $this->assertArrayHasKey('resources', $json_projects[0]);
        $this->assertContains(
            [
                'type' => 'trackers',
                'uri' => 'projects/' . $this->project_private_member_id . '/trackers',
            ],
            $json_projects[0]['resources']
        );

        $this->assertContains(
            [
                'type' => 'backlog',
                'uri' => 'projects/' . $this->project_private_member_id . '/backlog',
            ],
            $json_projects[0]['resources']
        );

        $this->assertContains(
            [
                'type' => 'milestones',
                'uri' => 'projects/' . $this->project_private_member_id . '/milestones',
            ],
            $json_projects[0]['resources']
        );

        $this->assertContains(
            [
                'type' => 'plannings',
                'uri' => 'projects/' . $this->project_private_member_id . '/plannings',
            ],
            $json_projects[0]['resources']
        );

        $this->assertContains(
            [
                'type' => 'user_groups',
                'uri' => 'projects/' . $this->project_private_member_id . '/user_groups',
            ],
            $json_projects[0]['resources']
        );

        $this->assertContains(
            [
                'type' => 'labels',
                'uri' => 'projects/' . $this->project_private_member_id . '/labels',
            ],
            $json_projects[0]['resources']
        );

        $this->assertContains(
            [
                'type' => 'project_services',
                'uri' => 'projects/' . $this->project_private_member_id . '/project_services',
            ],
            $json_projects[0]['resources']
        );

        $this->assertContains(
            [
                'type' => 'docman_service',
                'uri'  => 'projects/' . $this->project_private_member_id . '/docman_service',
            ],
            $json_projects[0]['resources']
        );
        $this->assertContains(
            [
                'type' => 'docman_metadata',
                'uri'  => 'projects/' . $this->project_private_member_id . '/docman_metadata',
            ],
            $json_projects[0]['resources']
        );

        $this->assertArrayHasKey('id', $json_projects[0]);
        $this->assertEquals($this->project_private_member_id, $json_projects[0]['id']);

        $this->assertArrayHasKey('uri', $json_projects[0]);
        $this->assertEquals('projects/' . $this->project_private_member_id, $json_projects[0]['uri']);

        $this->assertArrayHasKey('label', $json_projects[0]);
        $this->assertEquals('Private member', $json_projects[0]['label']);

        $this->assertArrayHasKey('access', $json_projects[0]);
        $this->assertEquals('private', $json_projects[0]['access']);

        $this->assertArrayHasKey('is_member_of', $json_projects[0]);
        $this->assertFalse($json_projects[0]['is_member_of']);

        $this->assertArrayHasKey('additional_informations', $json_projects[0]);
        $this->assertEquals(
            $this->releases_tracker_id,
            $json_projects[0]['additional_informations']['agiledashboard']['root_planning']['milestone_tracker']['id']
        );
    }

    private function valuesArePresent(array $values, array $array): bool
    {
        foreach ($values as $value) {
            if (! in_array($value, $array)) {
                return false;
            }
        }

        return true;
    }

    private function getIds(array $json_with_id): array
    {
        $ids = [];
        foreach ($json_with_id as $json) {
            $ids[] = $json['id'];
        }
        return $ids;
    }

    public function testThatAdminGetEvenPrivateProjectThatSheIsNotMemberOf(): void
    {
        $response       = $this->getResponse($this->request_factory->createRequest('GET', 'projects/'));
        $admin_projects = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($admin_projects as $project) {
            if ($project['id'] !== $this->project_private_id) {
                continue;
            }

            $this->assertFalse($project['is_member_of']);

            $project_members_uri = "user_groups/$this->project_private_id" . "_3/users";
            $project_members     = json_decode(
                $this->getResponse(
                    $this->request_factory->createRequest('GET', $project_members_uri)
                )->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            foreach ($project_members as $member) {
                $this->assertNotEquals('admin', $member['username']);
            }
            return;
        }

        $this->fail('REST read only admin should be able to get private projects even if she is not member of');
    }

    public function testGETMilestones(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/milestones'));

        $this->assertEquals(200, $response->getStatusCode());

        $milestones = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $milestones);
    }

    public function testGETTrackers(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/trackers'));

        $this->assertEquals(200, $response->getStatusCode());

        $trackers = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(6, $trackers);
    }

    public function testGETBacklog(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/backlog'));

        $this->assertEquals(200, $response->getStatusCode());

        $backlog_items = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(3, $backlog_items);
    }

    public function testPUTBacklog(): void
    {
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                'projects/' . $this->project_private_member_id . '/backlog',
            )->withBody(
                $this->stream_factory->createStream('[' . $this->epic_artifact_ids[7] . ',' . $this->epic_artifact_ids[5] . ',' . $this->epic_artifact_ids[6] . ']')
            )
        );

        $this->assertEquals(403, $response_put->getStatusCode());
    }

    public function testGETLabels(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/labels'));

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(['labels' => []], json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGETUserGroups(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/user_groups'));

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertTrue(count(json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)) > 0);
    }

    public function testGETWiki(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'projects/' . $this->project_private_member_id . '/phpwiki'));

        $expected_result = [
            'pages' => [
                0 => [
                    'id'  => REST_TestDataBuilder::PHPWIKI_PAGE_ID,
                    'uri' => 'phpwiki/6097',
                    'name' => 'WithContent',
                ],
                1 => [
                    'id'  => REST_TestDataBuilder::PHPWIKI_SPACE_PAGE_ID,
                    'uri' => 'phpwiki/6100',
                    'name' => 'With Space',
                ],
            ],
        ];

        $this->assertEquals($expected_result, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testPATCH(): void
    {
        $patch_resource = json_encode([
            'status' => 'suspended',
        ]);

        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'projects/' . $this->project_deleted_id)
                ->withBody(
                    $this->stream_factory->createStream($patch_resource)
                )
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAllOPTIONSProjects(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id));

        $this->assertEquals(200, $response->getStatusCode());
        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/milestones'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/trackers'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/backlog'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET', 'PUT', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/labels'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/user_groups'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'projects/' . $this->project_private_member_id . '/phpwiki'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }
}
