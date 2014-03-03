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
 * @group ProjectTests
 */
class ProjectTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testGET() {
        $response      = $this->getResponse($this->client->get('projects'));
        $json_projects = $response->json();

        $this->assertEquals(
            array(
                TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
                TestDataBuilder::PROJECT_PUBLIC_ID,
                TestDataBuilder::PROJECT_PUBLIC_MEMBER_ID,
                TestDataBuilder::PROJECT_PBI_ID,
            ),
            $this->getIds($json_projects)
        );

        $this->assertEquals(
            $json_projects[0],
            array(
                'id'        => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
                'uri'       => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
                'label'     => 'Private member',
                'resources' => array(
                    array(
                        'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/trackers',
                        'type' => 'trackers',
                    ),
                    array(
                        'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog',
                        'type' => 'backlog',
                    ),
                    array(
                        'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/milestones',
                        'type' => 'milestones',
                    ),
                    array(
                        'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/plannings',
                        'type' => 'plannings',
                    ),
                    array(
                        'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/user_groups',
                        'type' => 'user_groups',
                    ),
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    private function getIds(array $json_with_id) {
        $ids = array();
        foreach ($json_with_id as $json) {
            $ids[] = $json['id'];
        }
        return $ids;
    }

    public function testGETbyIdForAdmin() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $this->assertEquals(
            $response->json(),
            array(
                'id'        => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
                'uri'       => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
                'label'     => 'Private member',
                'resources' => array(
                    array(
                        'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/trackers',
                        'type' => 'trackers',
                    ),
                    array(
                        'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog',
                        'type' => 'backlog',
                    ),
                    array(
                        'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/milestones',
                        'type' => 'milestones',
                    ),
                    array(
                        'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/plannings',
                        'type' => 'plannings',
                    ),
                    array(
                        'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/user_groups',
                        'type' => 'user_groups',
                    ),
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSprojects() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSbyIdForAdmin() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSbyIdForProjectMember() {
        $response = $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->client->options('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETbyIdForForbiddenUser() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->client->get('projects/'.TestDataBuilder::ADMIN_PROJECT_ID));
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 403);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testGETBadRequest() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/abc'));
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 400);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testGETUnknownProject() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/1234567890'));
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 404);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testGETmilestones() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/milestones'));

        $milestones = $response->json();
        $this->assertCount(1, $milestones);

        $release_milestone = $milestones[0];
        $this->assertArrayHasKey('id', $release_milestone);
        $this->assertEquals($release_milestone['label'], "Release 1.0");
        $this->assertEquals($release_milestone['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));
        $this->assertArrayHasKey('id', $release_milestone['artifact']);
        $this->assertArrayHasKey('uri', $release_milestone['artifact']);
        $this->assertRegExp('%^artifacts/[0-9]+$%', $release_milestone['artifact']['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSmilestones() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/milestones'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONStrackers() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/trackers'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETtrackers() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/trackers'));

        $trackers = $response->json();

        $this->assertCount(5, $trackers);

        $epics_tracker = $trackers[0];
        $this->assertArrayHasKey('id', $epics_tracker);
        $this->assertEquals($epics_tracker['label'], "Epics");
        $this->assertEquals($epics_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $releases_tracker = $trackers[1];
        $this->assertArrayHasKey('id', $releases_tracker);
        $this->assertEquals($releases_tracker['label'], "Releases");
        $this->assertEquals($releases_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $sprints_tracker = $trackers[2];
        $this->assertArrayHasKey('id', $sprints_tracker);
        $this->assertEquals($sprints_tracker['label'], "Sprints");
        $this->assertEquals($sprints_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $tasks_tracker = $trackers[3];
        $this->assertArrayHasKey('id', $tasks_tracker);
        $this->assertEquals($tasks_tracker['label'], "Tasks");
        $this->assertEquals($tasks_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $userstories_tracker = $trackers[4];
        $this->assertArrayHasKey('id', $userstories_tracker);
        $this->assertEquals($userstories_tracker['label'], "User Stories");
        $this->assertEquals($userstories_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSbacklog() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog'));

        $this->assertEquals(array('OPTIONS', 'GET', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETbacklog() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog'));

        $backlog_items = $response->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Epic pic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_5_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_5_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Epic c'est tout");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_6_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_6_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Epic epoc");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_7_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_7_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));
    }

    public function testPUTbacklog() {
        $response_put = $this->getResponse($this->client->put('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog', null, '['.TestDataBuilder::EPIC_7_ARTIFACT_ID.','.TestDataBuilder::EPIC_5_ARTIFACT_ID.','.TestDataBuilder::EPIC_6_ARTIFACT_ID.']'));

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog'));
        $backlog_items = $response_get->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Epic epoc");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_7_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_7_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Epic pic");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_5_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_5_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Epic c'est tout");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_6_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_6_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));
    }

    public function testPUTbacklogOnlyTwoItems() {
        $response_put = $this->getResponse($this->client->put('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog', null, '['.TestDataBuilder::EPIC_6_ARTIFACT_ID.','.TestDataBuilder::EPIC_7_ARTIFACT_ID.']'));

        $this->assertEquals($response_put->getStatusCode(), 200);

        $response_get = $this->getResponse($this->client->get('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog'));
        $backlog_items = $response_get->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Epic pic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_5_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_5_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Epic c'est tout");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_6_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_6_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Epic epoc");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_7_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_7_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID)));
    }

   public function testGETUserGroupsContainingStaticUGroups() {
        $response = $this->getResponse($this->client->get('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/user_groups'));

        $expected_result = array(

            0 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_PROJECT_MEMBERS_ID.'/users'
            ),
            1 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID.'/users'
            ),
            2 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID.'/users'
            ),
            3 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_TECH_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_TECH_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_TECH_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_TECH_ID.'/users'
            ),
            4 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_ADMIN_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_ADMIN_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_ADMIN_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_ADMIN_ID.'/users'
            ),
            5 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID.'/users'
            ),
            6 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID,
                'label' => TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID.'/users'
            ),
            7 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_2_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_2_ID,
                'label' => TestDataBuilder::STATIC_UGROUP_2_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_2_ID.'/users'
            )
        );

        $this->assertEquals($expected_result, $response->json());
   }
}