<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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
 * @group TrackerV3Test
 */
class TrackerV3Test extends SOAPBase {

    private $server_url;
    private $login;
    private $password;

    public function setUp() {
        parent::setUp();

        $this->server_url = 'http://localhost/soap/?wsdl';
        $this->login      = SOAP_TestDataBuilder::TEST_USER_1_NAME;
        $this->password   = SOAP_TestDataBuilder::TEST_USER_1_PASS;

        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['SCRIPT_NAME'] = '/soap/codendi.wsdl.php';

        // Connecting to the soap's tracker client
        $this->soapTrackerv3 = new SoapClient(
            $this->server_url,
            array('cache_wsdl' => WSDL_CACHE_NONE)
        );

    }

    public function tearDown() {
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['SCRIPT_NAME']);

        parent::tearDown();
    }

    /**
     * @return string
     */
    private function getSessionHash() {
        $soapLogin = new SoapClient(
            $this->server_url,
            array('cache_wsdl' => WSDL_CACHE_NONE)
        );

        // Establish connection to the server
        return $soapLogin->login($this->login, $this->password)->session_hash;
    }

    public function testGetTrackersV3() {
        $session_hash = $this->getSessionHash();

        $response = $this->soapTrackerv3->getTrackerList(
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

        $response = $this->soapTrackerv3->addArtifact(
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

        $this->assertEquals(1, $response);

        return $tracker_v3_id;
    }

    /**
     * @depends testCreateArtifact
     */
    public function testGetArtifacts($tracker_v3_id) {
        $session_hash = $this->getSessionHash();

        $response = $this->soapTrackerv3->getArtifacts(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PRIVATE_MEMBER_ID,
            $tracker_v3_id
        );

        $this->assertEquals(1, $response->total_artifacts_number);
        $this->assertEquals("SOAP Summary", $response->artifacts[0]->summary);
    }

}