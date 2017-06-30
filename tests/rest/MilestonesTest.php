<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All rights reserved
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

use Tuleap\REST\MilestoneBase;

/**
 * @group MilestonesTest
 */
class MilestonesTest extends MilestoneBase
{

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOPTIONS() {
        $response = $this->getResponse($this->client->options('milestones'));
        $this->assertEquals(array('OPTIONS'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSMilestonesId() {
        $response = $this->getResponse($this->client->options('milestones/'.$this->release_artifact_ids[1]));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETResourcesMilestones() {
        $response = $this->getResponse($this->client->get('milestones/'.$this->release_artifact_ids[1]));

        $this->assertEquals(200, $response->getStatusCode());

        $milestone = $response->json();
        $this->assertEquals(
            array(
                'uri'    => 'milestones/'.$this->release_artifact_ids[1].'/milestones',
                'accept' => array(
                    'trackers' => array(
                        array(
                            'id'  => $this->sprints_tracker_id,
                            'uri' => "trackers/$this->sprints_tracker_id",
                            'label' => 'Sprints'
                        )
                    )
                ),
            ),
            $milestone['resources']['milestones']
        );

        $this->arrayHasKey($milestone['sub_milestone_type']);
    }

    public function testGETResourcesBacklog() {
        $response = $this->getResponse($this->client->get('milestones/'.$this->release_artifact_ids[1]));

        $this->assertEquals(200, $response->getStatusCode());

        $milestone = $response->json();
        $this->assertEquals(
            array(
                'uri'    => 'milestones/'.$this->release_artifact_ids[1].'/backlog',
                'accept' => array(
                    'trackers' => array(
                        array(
                            'id'  => $this->user_stories_tracker_id,
                            'uri' => 'trackers/'.$this->user_stories_tracker_id,
                            'label' => 'User Stories'
                        )
                    )
                ),
            ),
            $milestone['resources']['backlog']
        );
    }

    public function testGETResourcesContent() {
        $response = $this->getResponse($this->client->get('milestones/'.$this->release_artifact_ids[1]));
        $this->assertEquals(200, $response->getStatusCode());

        $milestone = $response->json();
        $this->assertEquals(
            array(
                'uri'    => 'milestones/'.$this->release_artifact_ids[1].'/content',
                'accept' => array(
                    'trackers' => array(
                        array(
                            'id'  => $this->epic_tracker_id,
                            'uri' => 'trackers/'.$this->epic_tracker_id,
                            'label' => 'Epics'
                        )
                    )
                ),
            ),
            $milestone['resources']['content']
        );
    }

    public function testGETResourcesBurndownCardwallEmpty() {
        $response = $this->getResponse($this->client->get('milestones/'.$this->release_artifact_ids[1]));
        $this->assertEquals(200, $response->getStatusCode());

        $milestone = $response->json();
        $this->assertNull(
            $milestone['resources']['cardwall']
        );
        $this->assertNull(
            $milestone['resources']['burndown']
        );
    }

    public function testGETResourcesBurndown() {
        $response = $this->getResponse($this->client->get('milestones/'.$this->sprint_artifact_ids[1]));
        $this->assertEquals(200, $response->getStatusCode());

        $milestone = $response->json();
        $this->assertEquals(
            array(
                'uri'    => 'milestones/'.$this->sprint_artifact_ids[1].'/burndown',
            ),
            $milestone['resources']['burndown']
        );
    }

    public function testGETResourcesCardwall() {
        $response = $this->getResponse($this->client->get('milestones/'.$this->sprint_artifact_ids[1]));
        $this->assertEquals(200, $response->getStatusCode());

        $milestone = $response->json();
        $this->assertEquals(
            array(
                'uri'    => 'milestones/'.$this->sprint_artifact_ids[1].'/cardwall',
            ),
            $milestone['resources']['cardwall']
        );
    }
}
