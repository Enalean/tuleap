<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once __DIR__.'/../lib/autoload.php';

/**
 * @group TrackerV3Test
 */
class TrackerV3Test extends SOAPBase {

    public function setUp() {
        parent::setUp();

        $_SERVER['SERVER_NAME'] = $this->server_name;
        $_SERVER['SERVER_PORT'] = $this->server_port;
        $_SERVER['SCRIPT_NAME'] = $this->base_wsdl;
    }

    public function tearDown() {
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['SCRIPT_NAME']);

        parent::tearDown();
    }

    public function testGetTrackersV3() {
        $session_hash = $this->getSessionHash();

        $response = $this->soap_base->getTrackerList(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID
        );

        $this->assertCount(4, $response);
        $this->assertEquals('bug', $response[0]->item_name);
        $this->assertEquals('story', $response[1]->item_name);
        $this->assertEquals('SR', $response[2]->item_name);
        $this->assertEquals('task', $response[3]->item_name);

        return $response[3]->group_artifact_id;
    }

    /**
     * @depends testGetTrackersV3
     */
    public function testCreateArtifact($tracker_v3_id) {
        $session_hash = $this->getSessionHash();

        //save values
        $project_id   = SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID;
        $status_id    = 0;
        $close_date   = "";
        $summary      = "SOAP Summary";
        $details      = "";
        $severity     = 0;
        $extra_fields = array();

        $response = $this->soap_base->addArtifact(
            $session_hash,
            $project_id,
            $tracker_v3_id,
            $status_id,
            $close_date,
            $summary,
            $details,
            $severity,
            $extra_fields
        );

        $expected_artifact_id = 2;
        $this->assertEquals($expected_artifact_id, $response);

        return $tracker_v3_id;
    }

    /**
     * @depends testCreateArtifact
     */
    public function testGetArtifacts($tracker_v3_id) {
        $session_hash = $this->getSessionHash();

        $response = $this->soap_base->getArtifacts(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $tracker_v3_id
        );

        $this->assertEquals(1, $response->total_artifacts_number);
        $this->assertEquals("SOAP Summary", $response->artifacts[0]->summary);
        $this->assertNotNull($response->artifacts[0]->extra_fields);

        return $tracker_v3_id;
    }

    /**
     * @depends testGetArtifacts
     */
    public function testGetArtifactFromReport($tracker_v3_id) {
        $session_hash = $this->getSessionHash();

        $response = $this->soap_base->getArtifactsFromReport(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $tracker_v3_id,
            SOAP_TestDataBuilder::TV3_TASK_REPORT_ID
        );

        $this->assertEquals(1, $response->total_artifacts_number);
        $this->assertEquals(2, $response->artifacts[0]->artifact_id);
        $this->assertNotNull($response->artifacts[0]->fields);
    }
}
