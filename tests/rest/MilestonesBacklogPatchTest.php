<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * @group MilestonesTest
 */
class MilestonesBacklogPatchTest extends RestBase {

    /** @var Test_Rest_TrackerFactory */
    private $tracker_test_helper;

    /** @var Test\Rest\Tracker\Tracker */
    private $release;
    private $story_add;
    private $story_sub;
    private $story_mul;
    private $story_div;
    private $uri;

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function setUp() {
        parent::setUp();

        $project = $this->getProject(TestDataBuilder::PROJECT_BACKLOG_DND);

        $this->tracker_test_helper = new Test\Rest\Tracker\TrackerFactory(
            $this->client,
            $this->rest_request,
            $project['id'],
            TestDataBuilder::TEST_USER_1_NAME
        );

        $this->createReleaseAndBacklog();
    }

    private function getProject($shortname) {
        foreach ($this->getResponse($this->client->get('projects'))->json() as $project) {
            if ($project['shortname'] == $shortname) {
                return $project;
            }
        }
        throw new Exception("Project $shortname not found");
    }

    public function testPatchAfter() {
        $response = $this->getResponse($this->client->patch($this->uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($this->story_mul['id'], $this->story_div['id']),
                'direction'   => 'after',
                'compared_to' => $this->story_add['id']
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->story_add['id'],
                $this->story_mul['id'],
                $this->story_div['id'],
                $this->story_sub['id'],
            ),
            $this->getBacklogOrder()
        );
    }

    public function testPatchBefore() {
        $response = $this->getResponse($this->client->patch($this->uri, null, json_encode(array(
            'order' => array(
                'ids'         => array($this->story_mul['id'], $this->story_sub['id']),
                'direction'   => 'before',
                'compared_to' => $this->story_add['id']
            )
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            array(
                $this->story_mul['id'],
                $this->story_sub['id'],
                $this->story_add['id'],
                $this->story_div['id'],
            ),
            $this->getBacklogOrder()
        );
    }

    public function testPatchWithItemNotInBacklogRaiseErrors() {
        $exception_thrown = false;

        try {
            $this->getResponse($this->client->patch($this->uri, null, json_encode(array(
                'order' => array(
                    'ids'         => array($this->story_mul['id'], $this->story_sub['id']),
                    'direction'   => 'before',
                    'compared_to' => 1
                )
            ))));
        } catch (Guzzle\Http\Exception\ClientErrorResponseException $exception) {
            $exception_thrown = true;
            $this->assertEquals(409, $exception->getResponse()->getStatusCode());
        }
        $this->assertTrue($exception_thrown, "An exception should have been thrown");
    }

    private function getBacklogOrder() {
        $response = $this->getResponse($this->client->get($this->uri));
        $actual_order = array();
        foreach($response->json() as $backlog_element) {
            $actual_order[] = $backlog_element['id'];
        }
        return $actual_order;
    }

    private function createReleaseAndBacklog() {
        $this->release = $this->createRelease();
        $this->uri     = 'milestones/'.$this->release['id'].'/backlog';
    }

    private function createRelease() {
        $tracker = $this->tracker_test_helper->getTrackerRest('rel');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Name', 'Release 2014 12 02'),
                $tracker->getSubmitListValue('Status', 'Current'),
                $tracker->getSubmitArtifactLinkValue($this->createReleaseBacklog())
            )
        );
    }

    private function createReleaseBacklog() {
        $this->story_add = $this->createStory("add two integers");
        $this->story_sub = $this->createStory("sub two integers");
        $this->story_mul = $this->createStory("mul two integers");
        $this->story_div = $this->createStory("div two integers");

        return array($this->story_add['id'], $this->story_sub['id'], $this->story_mul['id'], $this->story_div['id']);
    }

    private function createStory($i_want_to) {
        $tracker = $this->tracker_test_helper->getTrackerRest('story');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('I want to', $i_want_to),
                $tracker->getSubmitListValue('Status', 'To be done')
            )
        );
    }
}
