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
 * When you move an artifact from the release plan back to the product backlog and Submit the changes an error is generated
 *
 * @group Regressions
 */
class Regressions_MilestonesContentTest extends RestBase {

    /** @var Test_Rest_TrackerFactory */
    private $tracker_test_helper;

    private $one_epic;
    private $another_epic;
    private $product;
    private $release;

    public function testItCanMoveBackFromReleaseBacklogToProductBacklog() {
        $this->getResponse($this->client->put('milestones/'.$this->release['id'].'/content', null, json_encode(array($this->one_epic['id']))));

        $this->assertEquals($this->getMilestoneContentIds($this->release['id']), array($this->one_epic['id']));
        $this->assertEquals($this->getMilestoneContentIds($this->product['id']), array($this->one_epic['id'], $this->another_epic['id']));
        $this->assertEquals($this->getMilestoneBacklogIds($this->product['id']), array($this->another_epic['id']));
    }

    private function getMilestoneBacklogIds($id) {
        return $this->getIds("milestones/$id/backlog");
    }

    private function getMilestoneContentIds($id) {
        return $this->getIds("milestones/$id/content");
    }

    private function getIds($route) {
        $ids = array();
        foreach ($this->getResponse($this->client->get($route))->json() as $item) {
            $ids[] = $item['id'];
        }
        return $ids;
    }

    public function setUp() {
        parent::setUp();
        $this->tracker_test_helper = new Test\Rest\Tracker\TrackerFactory(
            $this->client,
            $this->rest_request,
            $this->project_pbi_id,
            REST_TestDataBuilder::TEST_USER_1_NAME
        );
        $this->createBacklog();
        $this->createProductAndRelease();
        $this->assignEpicsToProductAndRelease();
    }

    private function createBacklog() {
        $this->one_epic     = $this->createEpic('One Epic');
        $this->another_epic = $this->createEpic('Another Epic');
    }

    private function createProductAndRelease() {
        $this->product = $this->createProduct("Widget");
        $this->release = $this->createRelease("1.0");
        $this->getResponse($this->client->put('milestones/'.$this->product['id'].'/milestones', null, json_encode(array($this->release['id']))));
    }

    private function assignEpicsToProductAndRelease() {
        $this->getResponse($this->client->put('milestones/'.$this->product['id'].'/content', null, json_encode(array($this->one_epic['id'], $this->another_epic['id']))));
        $this->getResponse($this->client->put('milestones/'.$this->release['id'].'/content', null, json_encode(array($this->one_epic['id'], $this->another_epic['id']))));
    }

    private function createEpic($summary) {
        $tracker = $this->tracker_test_helper->getTrackerRest('epic');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Title', $summary),
                $tracker->getSubmitListValue('Status', 'Not Started'),
            )
        );
    }

    private function createProduct($name) {
        $tracker = $this->tracker_test_helper->getTrackerRest('product');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Name', $name)
            )
        );
    }

    private function createRelease($release) {
        $tracker = $this->tracker_test_helper->getTrackerRest('releases');
        return $tracker->createArtifact(
            array(
                $tracker->getSubmitTextValue('Version Number', $release)
            )
        );
    }

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }
}