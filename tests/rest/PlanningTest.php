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
use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;

/**
 * @group ProjectTests
 */
class PlanningTest extends RestBase {

    /**
     * @var Client
     */
    private $client;

    public function setUp() {
        parent::setUp();

        $this->client = new Client($this->base_url);
        $user    = $this->getUserByName(TestDataBuilder::ADMIN_USER_NAME);
        $this->token   = $this->generateToken($user);
    }

    public function testPlanningsContainsAReleasePlanning() {
        $request = $this->client->get('/api/v1/projects/101/plannings')
                ->setHeader('X-Auth-Token', $this->token->getTokenValue())
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('X-Auth-UserId', $this->token->getUserId());

        $response = $request->send();

        $plannings = $response->json();

        $this->assertCount(2, $plannings);

        $release_planning = $plannings[0];
        $this->assertArrayHasKey('id', $release_planning);
        $this->assertEquals($release_planning['label'], "Release Planning");
        $this->assertEquals($release_planning['project'], array('id' => '101', 'uri' => 'projects/101'));
        $this->assertArrayHasKey('id', $release_planning['milestone_tracker']);
        $this->assertArrayHasKey('uri', $release_planning['milestone_tracker']);
        $this->assertRegExp('%^trackers/[0-9]+$%', $release_planning['milestone_tracker']['uri']);
        $this->assertCount(1, $release_planning['backlog_trackers']);
        $this->assertEquals($release_planning['milestones_uri'], 'plannings/'.$release_planning['id'].'/milestones');

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testReleasePlanningHasNoMilestone() {
        $request = $this->client->get(self::API_BASE.'/'.$this->getMilestonesUri())
                ->setHeader('X-Auth-Token', $this->token->getTokenValue())
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('X-Auth-UserId', $this->token->getUserId());
        $response = $request->send();

        $this->assertCount(0, $response->json());

        $this->assertEquals($response->getStatusCode(), 200);
    }

    private function getMilestonesUri() {
        $request_planning = $this->client->get(self::API_BASE.'/projects/101/plannings')
                ->setHeader('X-Auth-Token', $this->token->getTokenValue())
                ->setHeader('Content-Type', 'application/json')
                ->setHeader('X-Auth-UserId', $this->token->getUserId());

        $response_plannings = $request_planning->send()->json();
        return $response_plannings[0]['milestones_uri'];
    }
}
