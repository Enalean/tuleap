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
 * @group MilestonesTest
 */
class MilestonesMilestonesTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testPUTRemoveSubMilestones() {
        $this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null, '['.TestDataBuilder::SPRINT_ARTIFACT_ID.']');
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null, '[]'));
        $this->assertEquals($response_put->getStatusCode(), 200);
        $response_get = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null));
        $submilestones = $response_get->json();

        $this->assertCount(0, $submilestones);
    }

    public function testPUTOnlyOneSubMilestone() {
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null, '['.TestDataBuilder::SPRINT_ARTIFACT_ID.']'));
        $this->assertEquals($response_put->getStatusCode(), 200);
        $response_get = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null));
        $submilestones = $response_get->json();

        $this->assertCount(1, $submilestones);
        $this->assertEquals(TestDataBuilder::SPRINT_ARTIFACT_ID, $submilestones[0]['id']);
    }

    public function testPUTOnlyOneSubMilestoneAlreadyAdded() {
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null, '['.TestDataBuilder::SPRINT_ARTIFACT_ID.']'));
        $this->assertEquals($response_put->getStatusCode(), 200);
        $this->assertEquals($response_put->json(), array());
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testPUTOnlyOneSubMilestoneTwice() {
        $response_put = $this->getResponse($this->client->put('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null, '['.TestDataBuilder::SPRINT_ARTIFACT_ID.','.TestDataBuilder::SPRINT_ARTIFACT_ID.']'));
        $this->assertEquals($response_put->getStatusCode(), 400);
        $response_get = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/milestones', null));
        $submilestones = $response_get->json();

        $this->assertCount(0, $submilestones);
    }
}
