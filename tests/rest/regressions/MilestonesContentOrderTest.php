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
 * PUT /milestones/123/content doesn't change the order of elements
 * I submit a change where only the order of the elements changes, there is no
 * addition or removal, and I receive 200 OK, but the order of the elements is
 * not modified.
 *
 * It looks like if there is an addition or removal, everything goes well.
 * The problem only arises when only the order of the elements changes.
 *
 * @see https://tuleap.net/plugins/tracker/?aid=6429
 * @group Regressions
 */
class Regressions_MilestonesContentOrderTest extends RestBase {

    /** @var Test_Rest_TrackerFactory */
    private $tracker_test_helper;

    private $epic1;
    private $epic2;
    private $epic3;
    private $epic4;
    private $product;

    public function testItSetsTheContentOrder() {
        $put = json_encode(array($this->epic1['id'], $this->epic2['id'], $this->epic3['id'], $this->epic4['id']));
        $this->getResponse($this->client->put('milestones/'.$this->product['id'].'/content', null, $put));
        $this->assertEquals($this->getMilestoneContentIds($this->product['id']), array($this->epic1['id'], $this->epic2['id'], $this->epic3['id'], $this->epic4['id']));

        $put = json_encode(array($this->epic3['id'], $this->epic1['id'], $this->epic2['id'], $this->epic4['id']));
        $this->getResponse($this->client->put('milestones/'.$this->product['id'].'/content', null, $put));
        $this->assertEquals($this->getMilestoneContentIds($this->product['id']), array($this->epic3['id'], $this->epic1['id'], $this->epic2['id'], $this->epic4['id']));
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

        $this->epic1 = $this->createEpic('Epic 1');
        $this->epic2 = $this->createEpic('Epic 2');
        $this->epic3 = $this->createEpic('Epic 3');
        $this->epic4 = $this->createEpic('Epic 4');
        $this->product = $this->createProduct("Widget");
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

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }
}