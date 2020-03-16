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

use Guzzle\Http\Exception\BadResponseException;
use REST_TestDataBuilder;

/**
 * @group ProjectTests
 */
class ProjectTest extends ProjectBase
{
    private function getBasicAuthResponse($request)
    {
        return $this->getResponseByBasicAuth(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            REST_TestDataBuilder::TEST_USER_1_PASS,
            $request
        );
    }

    public function testPOSTForRegularUser()
    {
        $post_resource = json_encode([
            'label' => 'Test Request 9747 regular user',
            'shortname'  => 'test9747-regular-user',
            'description' => 'Test of Request 9747 for REST API Project Creation',
            'is_public' => true,
            'template_id' => 100
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->client->post(
                'projects',
                null,
                $post_resource
            )
        );
        $this->assertEquals($response->getStatusCode(), 201);
    }

    public function testPOSTForAdmin()
    {
        $post_resource = json_encode([
            'label' => 'Test Request 9747',
            'shortname'  => 'test9747',
            'description' => 'Test of Request 9747 for REST API Project Creation',
            'is_public' => true,
            'template_id' => 100
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->post(
                'projects',
                null,
                $post_resource
            )
        );

        $project = $response->json();
        $this->assertArrayHasKey("id", $project);
        $this->assertEquals($response->getStatusCode(), 201);
    }

    public function testPOSTForRestProjectManager()
    {
        $post_resource = json_encode([
            'label' => 'Test Request 9748',
            'shortname'  => 'test9748',
            'description' => 'Test of Request 9748 for REST API Project Creation',
            'is_public' => true,
            'template_id' => 100
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME,
            $this->client->post(
                'projects',
                null,
                $post_resource
            )
        );

        $project = $response->json();
        $this->assertArrayHasKey("id", $project);
        $this->assertEquals($response->getStatusCode(), 201);
    }

    public function testProjectCreationWithAnIncorrectProjectIDFails() : void
    {
        $post_resource = json_encode([
            'label'       => 'Invalid project creation template ID',
            'shortname'   => 'invalidtemplateid',
            'description' => 'Invalid project creation template ID',
            'is_public'   => true,
            'template_id' => 0
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->post(
                'projects',
                null,
                $post_resource
            )
        );
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testProjectCreationWithXMLTemplate() : void
    {
        $post_resource = json_encode([
            'label'       => 'Created from scrum XML template',
            'shortname'   => 'from-scrum-template',
            'description' => 'I create projects',
            'is_public'   => false,
            'xml_template_name' => 'scrum',
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_5_NAME,
            $this->client->post(
                'projects',
                null,
                $post_resource
            )
        );
        $this->assertEquals($response->getStatusCode(), 201);
        $project = $response->json();
        $this->assertEquals('Created from scrum XML template', $project['label']);
    }

    public function testGET()
    {
        $response      = $this->getResponse($this->client->get('projects'));
        $json_projects = $response->json();

        $this->assertTrue(
            $this->valuesArePresent(
                array(
                    $this->project_private_member_id,
                    $this->project_public_id,
                    $this->project_public_member_id,
                    $this->project_pbi_id
                ),
                $this->getIds($json_projects)
            )
        );

        $this->assertArrayHasKey('resources', $json_projects[0]);
        $this->assertContains(
            array(
                'type' => 'trackers',
                'uri' => 'projects/' . $this->project_private_member_id . '/trackers',
            ),
            $json_projects[0]['resources']
        );

        $this->assertContains(
            array(
                'type' => 'backlog',
                'uri' => 'projects/' . $this->project_private_member_id . '/backlog',
            ),
            $json_projects[0]['resources']
        );

        $this->assertContains(
            array(
                'type' => 'milestones',
                'uri' => 'projects/' . $this->project_private_member_id . '/milestones',
            ),
            $json_projects[0]['resources']
        );

        $this->assertContains(
            array(
                'type' => 'plannings',
                'uri' => 'projects/' . $this->project_private_member_id . '/plannings',
            ),
            $json_projects[0]['resources']
        );

        $this->assertContains(
            array(
                'type' => 'user_groups',
                'uri' => 'projects/' . $this->project_private_member_id . '/user_groups',
            ),
            $json_projects[0]['resources']
        );

        $this->assertContains(
            array(
                'type' => 'labels',
                'uri' => 'projects/' . $this->project_private_member_id . '/labels',
            ),
            $json_projects[0]['resources']
        );

        $this->assertContains(
            array(
                'type' => 'project_services',
                'uri' => 'projects/' . $this->project_private_member_id . '/project_services',
            ),
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
        $this->assertTrue($json_projects[0]['is_member_of']);

        $this->assertArrayHasKey('additional_informations', $json_projects[0]);
        $this->assertEquals(
            $this->releases_tracker_id,
            $json_projects[0]['additional_informations']['agiledashboard']['root_planning']['milestone_tracker']['id']
        );

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETByShortname()
    {
        $response      = $this->getResponse($this->client->get('projects?query=' . urlencode('{"shortname":"pbi-6348"}')));
        $json_projects = $response->json();

        $this->assertArrayHasKey('id', $json_projects[0]);
        $this->assertEquals($this->project_pbi_id, $json_projects[0]['id']);
        $this->assertEquals(1, count($json_projects));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETByMembership()
    {
        $response      = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->client->get('projects?query=' . urlencode('{"is_member_of":true}'))
        );
        $json_projects = $response->json();

        $this->assertEquals(1, count($json_projects));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETByAdministratorship(): void
    {
        $response      = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('projects?query=' . urlencode('{"is_admin_of":true}'))
        );
        $json_projects = $response->json();

        $this->assertGreaterThan(5, count($json_projects));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETByNonMembershipShouldFail()
    {
        $response = $this->getResponse($this->client->get('projects?query=' . urlencode('{"is_member_of":false}')));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    private function valuesArePresent(array $values, array $array)
    {
        foreach ($values as $value) {
            if (! in_array($value, $array)) {
                return false;
            }
        }

        return true;
    }

    private function getIds(array $json_with_id)
    {
        $ids = array();
        foreach ($json_with_id as $json) {
            $ids[] = $json['id'];
        }
        return $ids;
    }

    public function testProjectRepresentationContainsShortname()
    {
        $response     = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get("projects/$this->project_pbi_id"));
        $json_project = $response->json();

        $this->assertArrayHasKey('shortname', $json_project);

        $this->assertEquals($json_project['shortname'], REST_TestDataBuilder::PROJECT_PBI_SHORTNAME);
    }

    public function testThatAdminGetEvenPrivateProjectThatSheIsNotMemberOf()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/'));
        $admin_projects = $response->json();

        foreach ($admin_projects as $project) {
            if ($project['id'] !== $this->project_private_id) {
                continue;
            }

            $this->assertFalse($project['is_member_of']);

            $project_members_uri = "user_groups/$this->project_private_id" . "_3/users";
            $project_members = $this
                ->getResponseByName(
                    REST_TestDataBuilder::ADMIN_USER_NAME,
                    $this->client->get($project_members_uri)
                )->json();
            foreach ($project_members as $member) {
                $this->assertNotEquals('admin', $member['username']);
            }
            return;
        }

        $this->fail('Admin should be able to get private projects even if she is not member of');
    }

    public function testGETbyIdForAdmin()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/' . $this->project_private_member_id));

        $json_project = $response->json();

        $this->assertArrayHasKey('resources', $json_project);
        $this->assertContains(
            array(
                'type' => 'trackers',
                'uri' => 'projects/' . $this->project_private_member_id . '/trackers',
            ),
            $json_project['resources']
        );

        $this->assertContains(
            array(
                'type' => 'backlog',
                'uri' => 'projects/' . $this->project_private_member_id . '/backlog',
            ),
            $json_project['resources']
        );

        $this->assertContains(
            array(
                'type' => 'milestones',
                'uri' => 'projects/' . $this->project_private_member_id . '/milestones',
            ),
            $json_project['resources']
        );

        $this->assertContains(
            array(
                'type' => 'plannings',
                'uri' => 'projects/' . $this->project_private_member_id . '/plannings',
            ),
            $json_project['resources']
        );

        $this->assertContains(
            array(
                'type' => 'user_groups',
                'uri' => 'projects/' . $this->project_private_member_id . '/user_groups',
            ),
            $json_project['resources']
        );

        $this->assertContains(
            array(
                'type' => 'labels',
                'uri' => 'projects/' . $this->project_private_member_id . '/labels',
            ),
            $json_project['resources']
        );

        $this->assertArrayHasKey('id', $json_project);
        $this->assertEquals($this->project_private_member_id, $json_project['id']);

        $this->assertArrayHasKey('uri', $json_project);
        $this->assertEquals('projects/' . $this->project_private_member_id, $json_project['uri']);

        $this->assertArrayHasKey('label', $json_project);
        $this->assertEquals('Private member', $json_project['label']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETbyIdForDelegatedRestProjectManager()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME, $this->client->get('projects/' . $this->project_deleted_id));

        $json_project = $response->json();

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($json_project['status'], 'deleted');
    }

    public function testOPTIONSprojects()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects'));

        $this->assertEquals(['OPTIONS', 'GET', 'POST', 'PATCH'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSbyIdForAdmin()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/' . $this->project_private_member_id));

        $this->assertEquals(['OPTIONS', 'GET', 'POST', 'PATCH'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSbyIdForDelegatedRestProjectManager()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME, $this->client->options('projects/' . $this->project_deleted_id));

        $this->assertEquals(['OPTIONS', 'GET', 'POST', 'PATCH'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSbyIdForProjectMember()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->options('projects/' . $this->project_private_member_id));

        $this->assertEquals(['OPTIONS', 'GET', 'POST', 'PATCH'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETbyIdForForbiddenUser()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get('projects/' . REST_TestDataBuilder::ADMIN_PROJECT_ID));
        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testGETBadRequest()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/abc'));
        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testGETUnknownProject()
    {
        $response  = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/1234567890'));
        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testGETmilestones()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('projects/' . $this->project_private_member_id . '/milestones')
        );

        $milestones = $response->json();
        $this->assertCount(1, $milestones);

        $release_milestone = $milestones[0];
        $this->assertArrayHasKey('id', $release_milestone);
        $this->assertEquals($release_milestone['label'], "Release 1.0");
        $this->assertEquals($release_milestone['project'], array(
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member'
        ));
        $this->assertArrayHasKey('id', $release_milestone['artifact']);
        $this->assertArrayHasKey('uri', $release_milestone['artifact']);
        $this->assertRegExp('%^artifacts/[0-9]+$%', $release_milestone['artifact']['uri']);

        $this->assertArrayHasKey('open', $release_milestone['status_count']);
        $this->assertArrayHasKey('closed', $release_milestone['status_count']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETmilestonesDoesNotContainStatusCountInSlimRepresentation()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('projects/' . $this->project_private_member_id . '/milestones?fields=slim')
        );

        $milestones = $response->json();
        $this->assertCount(1, $milestones);

        $release_milestone = $milestones[0];
        $this->assertEquals($release_milestone['label'], "Release 1.0");
        $this->assertEquals($release_milestone['status_count'], null);

        $this->assertEquals($response->getStatusCode(), 200);
    }


    public function testOPTIONSmilestones()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/' . $this->project_private_member_id . '/milestones'));

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONStrackers()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/' . $this->project_private_member_id . '/trackers'));

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETtrackers()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/' . $this->project_private_member_id . '/trackers'));

        $trackers = $response->json();

        $this->assertCount(6, $trackers);

        $epics_tracker = $trackers[0];
        $this->assertArrayHasKey('id', $epics_tracker);
        $this->assertEquals($epics_tracker['label'], "Epics");
        $this->assertEquals($epics_tracker['project'], array(
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member'
        ));

        $kanban_tracker = $trackers[1];
        $this->assertArrayHasKey('id', $kanban_tracker);
        $this->assertEquals($kanban_tracker['label'], "Kanban Tasks");
        $this->assertEquals($kanban_tracker['project'], array(
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member'
        ));

        $releases_tracker = $trackers[2];
        $this->assertArrayHasKey('id', $releases_tracker);
        $this->assertEquals($releases_tracker['label'], "Releases");
        $this->assertEquals($releases_tracker['project'], array(
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member'
        ));

        $sprints_tracker = $trackers[3];
        $this->assertArrayHasKey('id', $sprints_tracker);
        $this->assertEquals($sprints_tracker['label'], "Sprints");
        $this->assertEquals($sprints_tracker['project'], array(
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member'
        ));

        $tasks_tracker = $trackers[4];
        $this->assertArrayHasKey('id', $tasks_tracker);
        $this->assertEquals($tasks_tracker['label'], "Tasks");
        $this->assertEquals($tasks_tracker['project'], array(
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member'
        ));

        $userstories_tracker = $trackers[5];
        $this->assertArrayHasKey('id', $userstories_tracker);
        $this->assertEquals($userstories_tracker['label'], "User Stories");
        $this->assertEquals($userstories_tracker['project'], array(
            'id'    => $this->project_private_member_id,
            'uri'   => 'projects/' . $this->project_private_member_id,
            'label' => 'Private member'
        ));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSbacklog()
    {
        $response = $this->getResponseByName(REST_TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/' . $this->project_private_member_id . '/backlog'));

        $this->assertEquals(array('OPTIONS', 'GET', 'PUT', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETbacklog()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('projects/' . $this->project_private_member_id . '/backlog')
        );

        $backlog_items = $response->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Epic pic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact']['id'], $this->epic_artifact_ids[5]);
        $this->assertEquals($first_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[5]);
        $this->assertEquals($first_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Epic c'est tout");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact']['id'], $this->epic_artifact_ids[6]);
        $this->assertEquals($second_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[6]);
        $this->assertEquals($second_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Epic epoc");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact']['id'], $this->epic_artifact_ids[7]);
        $this->assertEquals($third_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[7]);
        $this->assertEquals($third_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);
    }

    public function testPUTbacklogWithoutPermission()
    {
        $response_put = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_2_NAME, $this->client->put('projects/' . $this->project_private_member_id . '/backlog', null, '[' . $this->epic_artifact_ids[7] . ',' . $this->epic_artifact_ids[5] . ',' . $this->epic_artifact_ids[6] . ']'));

        $this->assertEquals($response_put->getStatusCode(), 403);
    }

    public function testPUTbacklog()
    {
        $response_put = $this->getResponse(
            $this->client->put(
                'projects/' . $this->project_private_member_id . '/backlog',
                null,
                '[' . $this->epic_artifact_ids[7] . ',' . $this->epic_artifact_ids[5] . ',' . $this->epic_artifact_ids[6] . ']'
            )
        );

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get  = $this->getResponse(
            $this->client->get('projects/' . $this->project_private_member_id . '/backlog')
        );
        $backlog_items = $response_get->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Epic epoc");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact']['id'], $this->epic_artifact_ids[7]);
        $this->assertEquals($first_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[7]);
        $this->assertEquals($first_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Epic pic");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact']['id'], $this->epic_artifact_ids[5]);
        $this->assertEquals($second_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[5]);
        $this->assertEquals($second_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Epic c'est tout");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact']['id'], $this->epic_artifact_ids[6]);
        $this->assertEquals($third_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[6]);
        $this->assertEquals($third_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);
    }

    public function testPUTbacklogOnlyTwoItems()
    {
        $response_put = $this->getResponse(
            $this->client->put(
                'projects/' . $this->project_private_member_id . '/backlog',
                null,
                '[' . $this->epic_artifact_ids[6] . ',' . $this->epic_artifact_ids[7] . ']'
            )
        );

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get  = $this->getResponse(
            $this->client->get('projects/' . $this->project_private_member_id . '/backlog')
        );
        $backlog_items = $response_get->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Epic pic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact']['id'], $this->epic_artifact_ids[5]);
        $this->assertEquals($first_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[5]);
        $this->assertEquals($first_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Epic c'est tout");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact']['id'], $this->epic_artifact_ids[6]);
        $this->assertEquals($second_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[6]);
        $this->assertEquals($second_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Epic epoc");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact']['id'], $this->epic_artifact_ids[7]);
        $this->assertEquals($third_backlog_item['artifact']['uri'], 'artifacts/' . $this->epic_artifact_ids[7]);
        $this->assertEquals($third_backlog_item['artifact']['tracker']['id'], $this->epic_tracker_id);
    }

    public function testOPTIONSLabels()
    {
        $response = $this->getResponse($this->client->options('projects/' . $this->project_private_member_id . '/labels'));

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETLabels()
    {
        $response = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/labels'));

        $this->assertEquals(array('labels' => array()), $response->json());
    }

    public function testOPTIONSUserGroups()
    {
        $response = $this->getResponse($this->client->options('projects/' . $this->project_private_member_id . '/user_groups'));

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETUserGroupsContainingStaticUGroups()
    {
        $response = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/user_groups'));
        $expected_result = array(

            array(
                'id' => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID,
                'uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID,
                'label' => 'Project members',
                'users_uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID . '/users',
                'key' => REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_KEY,
                'short_name' => 'project_members'
            ),
            array(
                'id' => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID,
                'uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID,
                'label' => 'Project administrators',
                'users_uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID . '/users',
                'key' => 'ugroup_' . REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_LABEL . '_name_key',
                'short_name' => 'project_admins'
            ),
            array(
                'id'         => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID,
                'uri'        => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID,
                'label'      => REST_TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_LABEL,
                'users_uri'  => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID . '/users',
                'key'        => 'ugroup_file_manager_admin_name_key',
                'short_name' => 'file_manager_admins'
            ),
            array(
                'id' => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID,
                'uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID,
                'label' => 'Wiki administrators',
                'users_uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID . '/users',
                'key' => 'ugroup_wiki_admin_name_key',
                'short_name' => 'wiki_admins'
            ),
            array(
                'id' => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FORUM_ADMIN_ID,
                'uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FORUM_ADMIN_ID,
                'label' => 'Forum moderators',
                'users_uri' => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_FORUM_ADMIN_ID . '/users',
                'key' => 'ugroup_forum_admin_name_key',
                'short_name' => 'forum_admins'
            ),
            array(
                'id'         => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_ADMIN_ID,
                'uri'        => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_ADMIN_ID,
                'label'      => 'News administrators',
                'users_uri'  => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_ADMIN_ID . '/users',
                'key'        => 'ugroup_news_admin_name_key',
                'short_name' => 'news_admins'

            ),
            array(
                'id'         => $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_WRITER_ID,
                'uri'        => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_WRITER_ID,
                'label'      => 'News writers',
                'users_uri'  => 'user_groups/' . $this->project_private_member_id . '_' . REST_TestDataBuilder::DYNAMIC_UGROUP_NEWS_WRITER_ID . '/users',
                'key'        => 'ugroup_news_writer_name_key',
                'short_name' => 'news_editors'
            ),
            array(
                'id' => (string) REST_TestDataBuilder::STATIC_UGROUP_1_ID,
                'uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_1_ID,
                'label' => REST_TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'users_uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_1_ID . '/users',
                'key' => REST_TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'short_name' => 'static_ugroup_1'
            ),
            array(
                'id' => (string) REST_TestDataBuilder::STATIC_UGROUP_2_ID,
                'uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_2_ID,
                'label' => REST_TestDataBuilder::STATIC_UGROUP_2_LABEL,
                'users_uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_UGROUP_2_ID . '/users',
                'key' => REST_TestDataBuilder::STATIC_UGROUP_2_LABEL,
                'short_name' => 'static_ugroup_2'
            ),
            array(
                'id' => (string) REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID,
                'uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID,
                'label' => REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL,
                'users_uri' => 'user_groups/' . REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_ID . '/users',
                'key' => REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL,
                'short_name' => REST_TestDataBuilder::STATIC_PRIVATE_MEMBER_UGROUP_DEVS_LABEL
            )
        );
        $this->assertEquals($expected_result, $response->json());
    }

    public function testGETUserGroupsWithSystemUserGroupsReturnsAnonymousAndRegisteredWhenAnonymousUsersCanAccessThePlatform()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('projects/' . $this->project_public_member_id . '/user_groups?query=' . urlencode('{"with_system_user_groups":true}'))
        );

        $json_response = $response->json();

        $user_group_ids = [];
        foreach ($json_response as $user_group) {
            $user_group_ids[] = $user_group['id'];
        }
        $this->assertContains('1', $user_group_ids); // ProjectUgroup::ANONYMOUS
        $this->assertContains('2', $user_group_ids); // ProjectUgroup::REGISTERED
    }

    public function testPATCHbacklogWithoutPermission()
    {
        $response_patch = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->client->put(
                'projects/' . $this->project_private_member_id . '/backlog',
                null,
                '[' . $this->epic_artifact_ids[7] . ',' . $this->epic_artifact_ids[5] . ',' . $this->epic_artifact_ids[6] . ']'
            )
        );

        $this->assertEquals($response_patch->getStatusCode(), 403);
    }

    public function testPATCHbacklog()
    {
        $uri = 'projects/' . $this->project_private_member_id . '/backlog';
        $backlog_items = $this->getResponse($this->client->get($uri))->json();

        $first_item  = $backlog_items[0];
        $second_item = $backlog_items[1];
        $third_item  = $backlog_items[2];
        $this->assertEquals($first_item['label'], "Epic pic");
        $this->assertEquals($second_item['label'], "Epic c'est tout");
        $this->assertEquals($third_item['label'], "Epic epoc");

        $request_body = json_encode(array(
            'order' => array(
                'ids'         => array($second_item['id']),
                'direction'   => 'before',
                'compared_to' => $first_item['id']
            )
        ));

        $response_patch_with_rest_read_only = $this->getResponse(
            $this->client->patch(
                'projects/' . $this->project_private_member_id . '/backlog',
                null,
                $request_body
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_patch_with_rest_read_only->getStatusCode());

        $response_patch = $this->getResponse($this->client->patch(
            'projects/' . $this->project_private_member_id . '/backlog',
            null,
            $request_body
        ));

        $this->assertEquals(200, $response_patch->getStatusCode());

        // assert that the two items are in a different order
        $modified_backlog_items = $this->getResponse($this->client->get($uri))->json();

        $first_modified  = $modified_backlog_items[0];
        $second_modified = $modified_backlog_items[1];
        $third_modified  = $modified_backlog_items[2];
        $this->assertEquals($first_modified['label'], "Epic c'est tout");
        $this->assertEquals($second_modified['label'], "Epic pic");
        $this->assertEquals($third_modified['label'], "Epic epoc");

        // re-invert order of the two tasks
        $reinvert_patch = $this->getResponse($this->client->patch($uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($first_modified['id']),
                'direction'   => 'after',
                'compared_to' => $second_modified['id']
            )
        ))));
        $this->assertEquals(200, $reinvert_patch->getStatusCode());

        // assert that the two tasks are in the order
        $reverted_backlog_items = $this->getResponse($this->client->get($uri))->json();

        $first_item  = $reverted_backlog_items[0];
        $second_item = $reverted_backlog_items[1];
        $third_item  = $backlog_items[2];
        $this->assertEquals($first_item['label'], "Epic pic");
        $this->assertEquals($second_item['label'], "Epic c'est tout");
        $this->assertEquals($third_item['label'], "Epic epoc");
    }

    public function testPATCHbacklogMoveBackAndForthInTopBacklog()
    {
        $uri = 'projects/' . $this->project_private_member_id . '/backlog';
        $backlog_items = $this->getResponse($this->client->get($uri))->json();

        $first_item  = $backlog_items[0];
        $second_item = $backlog_items[1];
        $third_item  = $backlog_items[2];

        $releases = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/milestones'))->json();
        $first_release = $releases[0];

        $release_content = $this->getResponse($this->client->get('milestones/' . $first_release['id'] . '/content'))->json();
        $first_epic  = $release_content[0];
        $second_epic = $release_content[1];

        // remove from release, back to top backlog
        $response = $this->getResponse($this->client->patch('projects/' . $this->project_private_member_id . '/backlog', null, json_encode(array(
            'order' => array(
                'ids'         => array($first_epic['id']),
                'direction'   => 'after',
                'compared_to' => $first_item['id']
            ),
            'add' => array(
                array(
                    'id'          => $first_epic['id'],
                    'remove_from' => $first_release['id'],
                )
            )
        ))));
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            array(
                $first_item['id'],
                $first_epic['id'],
                $second_item['id'],
                $third_item['id'],
            ),
            $this->getIdsOrderedByPriority($uri)
        );

        // Move back to release
        $response = $this->getResponse($this->client->patch('milestones/' . $first_release['id'] . '/content', null, json_encode(array(
            'order' => array(
                'ids'         => array($first_epic['id']),
                'direction'   => 'before',
                'compared_to' => $second_epic['id']
            ),
            'add' => array(
                array(
                    'id'          => $first_epic['id'],
                )
            )
        ))));
        $this->assertEquals(200, $response->getStatusCode());

        // Assert Everything is equal to the beginning of the test
        $this->assertEquals(
            array(
                $first_item['id'],
                $second_item['id'],
                $third_item['id'],
            ),
            $this->getIdsOrderedByPriority($uri)
        );

        $this->assertEquals(
            $this->getIds($release_content),
            $this->getIdsOrderedByPriority('milestones/' . $first_release['id'] . '/content')
        );
    }

    private function getIdsOrderedByPriority($uri)
    {
        return $this->getIds($this->getResponse($this->client->get($uri))->json());
    }

    public function testOPTIONSWiki()
    {
        $response = $this->getResponse($this->client->options('projects/' . $this->project_private_member_id . '/phpwiki'));

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETWiki()
    {
        $response = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/phpwiki'));

        $expected_result = array(
            'pages' => array(
                0 => array(
                    'id'  => REST_TestDataBuilder::PHPWIKI_PAGE_ID,
                    'uri' => 'phpwiki/6097',
                    'name' => 'WithContent'
                ),
                1 => array(
                    'id'  => REST_TestDataBuilder::PHPWIKI_SPACE_PAGE_ID,
                    'uri' => 'phpwiki/6100',
                    'name' => 'With Space'
                )
            )
        );

        $this->assertEquals($expected_result, $response->json());
    }

    public function testGETWikiWithGoodPagename()
    {
        $response = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/phpwiki?pagename=WithContent'));

        $expected_result = array(
            'pages' => array(
                0 => array(
                    'id'  => REST_TestDataBuilder::PHPWIKI_PAGE_ID,
                    'uri' => 'phpwiki/6097',
                    'name' => 'WithContent'
                )
            )
        );

        $this->assertEquals($expected_result, $response->json());
    }

    public function testGETWikiWithGoodPagenameAndASpace()
    {
        $response = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/phpwiki?pagename=With+Space'));

        $expected_result = array(
            'pages' => array(
                0 => array(
                    'id'  => REST_TestDataBuilder::PHPWIKI_SPACE_PAGE_ID,
                    'uri' => 'phpwiki/6100',
                    'name' => 'With Space'
                )
            )
        );

        $this->assertEquals($expected_result, $response->json());
    }

    public function testGETWikiWithGoodRelativePagename()
    {
        $response = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/phpwiki?pagename=With'));

        $expected_result = array(
            'pages' => array(
                0 => array(
                    'id'  => REST_TestDataBuilder::PHPWIKI_PAGE_ID,
                    'uri' => 'phpwiki/6097',
                    'name' => 'WithContent'
                ),
                1 => array(
                    'id'  => REST_TestDataBuilder::PHPWIKI_SPACE_PAGE_ID,
                    'uri' => 'phpwiki/6100',
                    'name' => 'With Space'
                )
            )
        );

        $this->assertEqualsCanonicalizing($expected_result, $response->json());
    }

    public function testGETWikiWithNotExistingPagename()
    {
        $response = $this->getResponse($this->client->get('projects/' . $this->project_private_member_id . '/phpwiki?pagename="no"'));

        $expected_result = array(
            'pages' => array()
        );

        $this->assertEquals($expected_result, $response->json());
    }

    public function testGETWithBasicAuth()
    {
        $response      = $this->getBasicAuthResponse($this->client->get('projects'));
        $json_projects = $response->json();

        $this->assertTrue(
            $this->valuesArePresent(
                array(
                    $this->project_private_member_id,
                    $this->project_public_id,
                    $this->project_public_member_id,
                    $this->project_pbi_id
                ),
                $this->getIds($json_projects)
            )
        );
    }

    public function testPATCHWithRegularUser()
    {
        $patch_resource = json_encode([
            'status' => 'suspended'
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_2_NAME,
            $this->client->patch('projects/' . $this->project_deleted_id, null, $patch_resource)
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPATCHWithAdmin()
    {
        $patch_resource = json_encode([
            'status' => 'suspended'
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('projects/' . $this->project_deleted_id, null, $patch_resource)
        );

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPATCHWithRestProjectManager()
    {
        $patch_resource = json_encode([
            'status' => 'active'
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_DELEGATED_REST_PROJECT_MANAGER_NAME,
            $this->client->patch('projects/' . $this->project_deleted_id, null, $patch_resource)
        );

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function getSuspendedProjectTrackersWithRegularUser()
    {
        $has_exception_been_caught = false;

        try {
            $this->getResponseByName(
                REST_TestDataBuilder::TEST_USER_1_NAME,
                $this->client->get('projects/' . $this->project_suspended_id . '/trackers')
            );
        } catch (BadResponseException $exception) {
            $this->assertEquals($exception->getResponse()->getStatusCode(), 403);
            $this->assertEquals($exception->getMessage(), 'This project is suspended');

            $has_exception_been_caught = true;
        }

        $this->assertTrue($has_exception_been_caught);
    }

    public function getSuspendedProjectTrackersWithSiteAdmin()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('projects/' . $this->project_suspended_id . '/trackers')
        );

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testPUTBanner(): void
    {
        $payload = json_encode([
            'message' => 'a banner message'
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                'projects/' . $this->project_public_member_id . '/banner',
                null,
                $payload
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPUTEmptyMessageBannerShouldReturn400(): void
    {
        $payload = json_encode([
            'message' => ''
        ]);

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                'projects/' . $this->project_public_member_id . '/banner',
                null,
                $payload
            )
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGETBanner
     */
    public function testDELETEBanner(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->delete(
                'projects/' . $this->project_public_member_id . '/banner'
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testPUTBanner
     */
    public function testGETBanner(): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get(
                'projects/' . $this->project_public_member_id . '/banner'
            )
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response_json = $response->json();
        $this->assertEquals('a banner message', $response_json['message']);
    }
}
