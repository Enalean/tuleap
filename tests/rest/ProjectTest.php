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

    public function testGETbyIdForAdmin() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/101'));

        $this->assertEquals($response->json(), array(
            'id'        => '101',
            'uri'       => 'projects/101',
            'label'     => TestDataBuilder::TEST_PROJECT_LONG_NAME,
            'resources' => array(
                'projects/101/plannings'
            ))
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSbyIdForAdmin() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/101'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETbyIdForForbiddenUser() {
        // Cannot use @expectedException as we want to check status code.
        $exception = false;
        try {
            $this->getResponseByName(TestDataBuilder::TEST_USER_NAME, $this->client->options('projects/100'));
        } catch (Guzzle\Http\Exception\BadResponseException $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 403);
            $exception = true;
        }

        $this->assertTrue($exception);
    }

    public function testGETmilestones() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/101/milestones'));

        $milestones = $response->json();
        $this->assertCount(1, $milestones);

        $release_milestone = $milestones[0];
        $this->assertArrayHasKey('id', $release_milestone);
        $this->assertEquals($release_milestone['label'], "Release 1.0");
        $this->assertEquals($release_milestone['project'], array('id' => '101', 'uri' => 'projects/101'));
        $this->assertArrayHasKey('id', $release_milestone['artifact']);
        $this->assertArrayHasKey('uri', $release_milestone['artifact']);
        $this->assertRegExp('%^artifacts/[0-9]+$%', $release_milestone['artifact']['uri']);

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testOPTIONSmilestones() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/101/milestones'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONStrackers() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->options('projects/101/trackers'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGETtrackers() {
        $response = $this->getResponseByName(TestDataBuilder::ADMIN_USER_NAME, $this->client->get('projects/101/trackers'));

        $trackers = $response->json();

        $this->assertCount(5, $trackers);

        $epics_tracker = $trackers[0];
        $this->assertArrayHasKey('id', $epics_tracker);
        $this->assertEquals($epics_tracker['label'], "Epics");
        $this->assertEquals($epics_tracker['project'], array('id' => '101', 'uri' => 'projects/101'));

        $releases_tracker = $trackers[1];
        $this->assertArrayHasKey('id', $releases_tracker);
        $this->assertEquals($releases_tracker['label'], "Releases");
        $this->assertEquals($releases_tracker['project'], array('id' => '101', 'uri' => 'projects/101'));

        $sprints_tracker = $trackers[2];
        $this->assertArrayHasKey('id', $sprints_tracker);
        $this->assertEquals($sprints_tracker['label'], "Sprints");
        $this->assertEquals($sprints_tracker['project'], array('id' => '101', 'uri' => 'projects/101'));

        $tasks_tracker = $trackers[3];
        $this->assertArrayHasKey('id', $tasks_tracker);
        $this->assertEquals($tasks_tracker['label'], "Tasks");
        $this->assertEquals($tasks_tracker['project'], array('id' => '101', 'uri' => 'projects/101'));

        $userstories_tracker = $trackers[4];
        $this->assertArrayHasKey('id', $userstories_tracker);
        $this->assertEquals($userstories_tracker['label'], "User Stories");
        $this->assertEquals($userstories_tracker['project'], array('id' => '101', 'uri' => 'projects/101'));

        $this->assertEquals($response->getStatusCode(), 200);
    }
}
?>