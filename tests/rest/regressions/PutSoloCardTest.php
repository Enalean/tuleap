<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All rights reserved
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

require_once dirname(__FILE__).'/../../lib/autoload.php';

/**
 * PUT /cards/:id cannot update solo card
 *
 * In the context of a sprint Cardwall, if we change through direct edition, the
 * value of the field "Story Point" of a backlog "User Story" ,we receive the
 * error "column_id is required", after submitting this sprint.
 *
 * @see https://tuleap.net/plugins/tracker/?aid=6430
 * @group Regressions
 */
class Regressions_PutSoloCardTest extends RestBase {

    /** @var Test_Rest_TrackerFactory */
    private $tracker_test_helper;
    private $project_id;
    private $story;
    private $task;
    private $sprint;
    private $planning_id;

    public function testItEditSoloCardLabel() {
        $put = json_encode(array(
            "label"     => "Whatever",
            "column_id" => null,
            "values"    => array()
        ));
        try {
        $response = $this->getResponse($this->client->put('cards/'.$this->planning_id.'_'.$this->story['id'], null, $put));
        $this->assertEquals($response->getStatusCode(), 200);
        } catch (Guzzle\Http\Exception\BadResponseException $exception) {
            echo $exception->getRequest();
            echo $exception->getResponse()->getBody(true);
            die(PHP_EOL);
        }
    }

    public function setUp() {
        parent::setUp();
        $this->tracker_test_helper = new Test\Rest\Tracker\TrackerFactory(
            $this->client,
            $this->rest_request,
            $this->project_private_member_id,
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        try {
            $this->story       = $this->createStory('Story 1');
            $this->task        = $this->createTask('Task 1');
            $this->sprint      = $this->createSprint("Sprint 1");
            $this->planning_id = $this->getSprintPlanningId();
        } catch (Guzzle\Http\Exception\BadResponseException $exception) {
            echo $exception->getRequest();
            echo $exception->getResponse()->getBody(true);
            die(PHP_EOL);
        }
    }

    private function createStory($summary) {
        $tracker = $this->tracker_test_helper->getTrackerRest('story');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('I want to', $summary),
                $tracker->getSubmitListValue('Status', 'To be done'),
            )
        );
    }

    private function createTask($summary) {
        $tracker = $this->tracker_test_helper->getTrackerRest('task');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Summary', $summary),
                $tracker->getSubmitListValue('Status', 'To be done'),
            )
        );
    }


    private function createSprint($name) {
        $tracker = $this->tracker_test_helper->getTrackerRest('sprint');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Name', $name),
                $tracker->getSubmitListValue('Status', 'Current'),
            )
        );
    }

    private function getSprintPlanningId() {
        $project_plannings = $this->getResponse($this->client->get("projects/$this->project_private_member_id/plannings"))->json();
        foreach ($project_plannings as $planning) {
            if ($planning['label'] == 'Sprint Planning') {
                return $planning['id'];
            }
        }
    }

    private function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }
}