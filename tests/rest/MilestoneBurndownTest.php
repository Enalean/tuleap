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
 * @group MilestoneBurndownTest
 */
class MilestoneBurndownTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_NAME),
            $request
        );
    }

    public function testOPTIONSBurndown() {
        $response = $this->getResponse($this->client->options('milestones/'.TestDataBuilder::SPRINT_ARTIFACT_ID.'/burndown'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @expectedException Guzzle\Http\Exception\ClientErrorResponseException
     */
    public function testOPTIONSBurndownGives404() {
        $this->getResponse($this->client->options('milestones/'.TestDataBuilder::RELEASE_ARTIFACT_ID.'/burndown'));
    }

    public function testGetBurndown() {
        $response = $this->getResponse($this->client->get('milestones/'.TestDataBuilder::SPRINT_ARTIFACT_ID.'/burndown'));
        $burndown = $response->json();
        $this->assertEquals(29, $burndown['duration']);
        $this->assertEquals(10, $burndown['capacity']);
        $this->assertCount(0, $burndown['points']);
    }
}
