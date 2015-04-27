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

    private function getBasicAuthResponse($request) {
        return $this->getResponseByBasicAuth(
            TestDataBuilder::TEST_USER_1_NAME,
            TestDataBuilder::TEST_USER_1_PASS,
            $request
        );
    }

    public function testGET() {
        $response      = $this->getResponse($this->client->get('projects'));
        $json_projects = $response->json();

        $this->assertTrue(
            $this->valuesArePresent(
                array(
                    TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
                    TestDataBuilder::PROJECT_PUBLIC_ID,
                    TestDataBuilder::PROJECT_PUBLIC_MEMBER_ID,
                    TestDataBuilder::PROJECT_PBI_ID
                ),
                $this->getIds($json_projects)
            )
        );

        $this->assertArrayHasKey('resources', $json_projects[0]);
        $this->assertContains(
            array(
                'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/trackers',
                'type' => 'trackers',
            ),
            $json_projects[0]['resources']
        );

        $this->assertContains(
            array(
                'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog',
                'type' => 'backlog',
            ),
            $json_projects[0]['resources']
        );

        $this->assertContains(
            array(
                'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/milestones',
                'type' => 'milestones',
            ),
            $json_projects[0]['resources']
        );

        $this->assertContains(
            array(
                'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/plannings',
                'type' => 'plannings',
            ),
            $json_projects[0]['resources']
        );

        $this->assertContains(
            array(
                'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/user_groups',
                'type' => 'user_groups',
            ),
            $json_projects[0]['resources']
        );

        $this->assertArrayHasKey('id', $json_projects[0]);
        $this->assertEquals(TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, $json_projects[0]['id']);

        $this->assertArrayHasKey('uri', $json_projects[0]);
        $this->assertEquals('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, $json_projects[0]['uri']);

        $this->assertArrayHasKey('label', $json_projects[0]);
        $this->assertEquals('Private member', $json_projects[0]['label']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    private function valuesArePresent(array $values, array $array) {
        foreach ($values as $value) {
            if (! in_array($value, $array)) {
                return false;
            }
        }

        return true;
    }

    private function getIds(array $json_with_id) {
        $ids = array();
        foreach ($json_with_id as $json) {
            $ids[] = $json['id'];
        }
        return $ids;
    }

    public function testProjectReprensationContainsShortname() {
        $response     = $this->getResponseByName(TestDataBuilder::TEST_USER_1_NAME, $this->client->get('projects/'.TestDataBuilder::PROJECT_PBI_ID));
        $json_project = $response->json();

        $this->assertArrayHasKey('shortname', $json_project);

        $this->assertEquals($json_project['shortname'], TestDataBuilder::PROJECT_PBI_SHORTNAME);
    }

    public function testGETbyIdForAdmin() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $json_project = $response->json();

        $this->assertArrayHasKey('resources', $json_project);
        $this->assertContains(
            array(
                'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/trackers',
                'type' => 'trackers',
            ),
            $json_project['resources']
        );

        $this->assertContains(
            array(
                'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog',
                'type' => 'backlog',
            ),
            $json_project['resources']
        );

        $this->assertContains(
            array(
                'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/milestones',
                'type' => 'milestones',
            ),
            $json_project['resources']
        );

        $this->assertContains(
            array(
                'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/plannings',
                'type' => 'plannings',
            ),
            $json_project['resources']
        );

        $this->assertContains(
            array(
                'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/user_groups',
                'type' => 'user_groups',
            ),
            $json_project['resources']
        );

        $this->assertArrayHasKey('id', $json_project);
        $this->assertEquals(TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, $json_project['id']);

        $this->assertArrayHasKey('uri', $json_project);
        $this->assertEquals('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, $json_project['uri']);

        $this->assertArrayHasKey('label', $json_project);
        $this->assertEquals('Private member', $json_project['label']);

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

        $this->assertCount(6, $trackers);

        $epics_tracker = $trackers[0];
        $this->assertArrayHasKey('id', $epics_tracker);
        $this->assertEquals($epics_tracker['label'], "Epics");
        $this->assertEquals($epics_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $kanban_tracker = $trackers[1];
        $this->assertArrayHasKey('id', $kanban_tracker);
        $this->assertEquals($kanban_tracker['label'], "Kanban Tasks");
        $this->assertEquals($kanban_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $releases_tracker = $trackers[2];
        $this->assertArrayHasKey('id', $releases_tracker);
        $this->assertEquals($releases_tracker['label'], "Releases");
        $this->assertEquals($releases_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $sprints_tracker = $trackers[3];
        $this->assertArrayHasKey('id', $sprints_tracker);
        $this->assertEquals($sprints_tracker['label'], "Sprints");
        $this->assertEquals($sprints_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $tasks_tracker = $trackers[4];
        $this->assertArrayHasKey('id', $tasks_tracker);
        $this->assertEquals($tasks_tracker['label'], "Tasks");
        $this->assertEquals($tasks_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $userstories_tracker = $trackers[5];
        $this->assertArrayHasKey('id', $userstories_tracker);
        $this->assertEquals($userstories_tracker['label'], "User Stories");
        $this->assertEquals($userstories_tracker['project'], array('id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID, 'uri' => 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID));

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSbacklog() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog'));

        $this->assertEquals(array('OPTIONS', 'GET', 'PUT', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETbacklog() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog'));

        $backlog_items = $response->json();

        $this->assertCount(3, $backlog_items);

        $first_backlog_item = $backlog_items[0];
        $this->assertArrayHasKey('id', $first_backlog_item);
        $this->assertEquals($first_backlog_item['label'], "Epic pic");
        $this->assertEquals($first_backlog_item['status'], "Open");
        $this->assertEquals($first_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_5_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_5_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Epic c'est tout");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_6_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_6_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Epic epoc");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_7_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_7_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPUTbacklogWithoutPermission() {
        $response_put = $this->getResponseByName(TestDataBuilder::TEST_USER_2_NAME, $this->client->put('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog', null, '['.TestDataBuilder::EPIC_7_ARTIFACT_ID.','.TestDataBuilder::EPIC_5_ARTIFACT_ID.','.TestDataBuilder::EPIC_6_ARTIFACT_ID.']'));

        $this->assertEquals($response_put->getStatusCode(), 403);
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
        $this->assertEquals($first_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_7_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_7_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Epic pic");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_5_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_5_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Epic c'est tout");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_6_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_6_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));
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
        $this->assertEquals($first_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_5_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_5_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $second_backlog_item = $backlog_items[1];
        $this->assertArrayHasKey('id', $second_backlog_item);
        $this->assertEquals($second_backlog_item['label'], "Epic c'est tout");
        $this->assertEquals($second_backlog_item['status'], "Open");
        $this->assertEquals($second_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_6_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_6_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));

        $third_backlog_item = $backlog_items[2];
        $this->assertArrayHasKey('id', $third_backlog_item);
        $this->assertEquals($third_backlog_item['label'], "Epic epoc");
        $this->assertEquals($third_backlog_item['status'], "Open");
        $this->assertEquals($third_backlog_item['artifact'], array('id' => TestDataBuilder::EPIC_7_ARTIFACT_ID, 'uri' => 'artifacts/'.TestDataBuilder::EPIC_7_ARTIFACT_ID, 'tracker' => array('id' => TestDataBuilder::EPICS_TRACKER_ID, 'uri' => 'trackers/'.TestDataBuilder::EPICS_TRACKER_ID, 'label' => 'Epics')));
    }

    public function testOPTIONSUserGroups() {
        $response = $this->getResponse($this->client->options('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/user_groups'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
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
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_AUTHENTICATED_USERS_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_AUTHENTICATED_USERS_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_AUTHENTICATED_USERS_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_AUTHENTICATED_USERS_ID.'/users'
            ),
            3 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_FILE_MANAGER_ID.'/users'
            ),
            4 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_TECH_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_TECH_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_TECH_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_TECH_ID.'/users'
            ),
            5 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_ADMIN_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_ADMIN_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_ADMIN_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_DOCUMENT_ADMIN_ID.'/users'
            ),
            6 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID,
                'label' => TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::DYNAMIC_UGROUP_WIKI_ADMIN_ID.'/users'
            ),
            7 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID,
                'label' => TestDataBuilder::STATIC_UGROUP_1_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_1_ID.'/users'
            ),
            8 => array(
                'id' => TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_2_ID,
                'uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_2_ID,
                'label' => TestDataBuilder::STATIC_UGROUP_2_LABEL,
                'users_uri' => 'user_groups/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'_'.TestDataBuilder::STATIC_UGROUP_2_ID.'/users'
            )
        );

        $this->assertEquals($expected_result, $response->json());
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPATCHbacklogWithoutPermission() {
        $response_patch = $this->getResponseByName(
            TestDataBuilder::TEST_USER_2_NAME,
            $this->client->put('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog',
            null,
            '['.TestDataBuilder::EPIC_7_ARTIFACT_ID.','.TestDataBuilder::EPIC_5_ARTIFACT_ID.','.TestDataBuilder::EPIC_6_ARTIFACT_ID.']')
        );

        $this->assertEquals($response_patch->getStatusCode(), 403);
    }

    public function testPATCHbacklog() {
        $uri = 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog';
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
        $response_patch = $this->getResponse($this->client->patch(
            'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog',
            null,
            $request_body
        ));

        $this->assertEquals($response_patch->getStatusCode(), 200);


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
        $this->assertEquals($reinvert_patch->getStatusCode(), 200);

        // assert that the two tasks are in the order
        $reverted_backlog_items = $this->getResponse($this->client->get($uri))->json();

        $first_item  = $reverted_backlog_items[0];
        $second_item = $reverted_backlog_items[1];
        $third_item  = $backlog_items[2];
        $this->assertEquals($first_item['label'], "Epic pic");
        $this->assertEquals($second_item['label'], "Epic c'est tout");
        $this->assertEquals($third_item['label'], "Epic epoc");
    }

    public function testPATCHbacklogMoveBackAndForthInTopBacklog() {
        $uri = 'projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog';
        $backlog_items = $this->getResponse($this->client->get($uri))->json();

        $first_item  = $backlog_items[0];
        $second_item = $backlog_items[1];
        $third_item  = $backlog_items[2];

        $releases = $this->getResponse($this->client->get('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/milestones'))->json();
        $first_release = $releases[0];

        $release_content = $this->getResponse($this->client->get('milestones/'.$first_release['id'].'/content'))->json();
        $first_epic  = $release_content[0];
        $second_epic = $release_content[1];

        // remove from release, back to top backlog
        $response = $this->getResponse($this->client->patch('projects/'.TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID.'/backlog', null, json_encode(array(
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
        $this->assertEquals($response->getStatusCode(), 200);

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
        $response = $this->getResponse($this->client->patch('milestones/'.$first_release['id'].'/content', null, json_encode(array(
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
        $this->assertEquals($response->getStatusCode(), 200);

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
            $this->getIdsOrderedByPriority('milestones/'.$first_release['id'].'/content')
        );
    }

    private function getIdsOrderedByPriority($uri) {
        return $this->getIds($this->getResponse($this->client->get($uri))->json());
    }

    public function testGETWithBasicAuth() {
        $response      = $this->getBasicAuthResponse($this->client->get('projects'));
        $json_projects = $response->json();

        $this->assertTrue(
            $this->valuesArePresent(
                array(
                    TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
                    TestDataBuilder::PROJECT_PUBLIC_ID,
                    TestDataBuilder::PROJECT_PUBLIC_MEMBER_ID,
                    TestDataBuilder::PROJECT_PBI_ID
                ),
                $this->getIds($json_projects)
            )
        );
    }
}
