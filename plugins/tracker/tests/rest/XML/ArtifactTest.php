<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All rights reserved
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

namespace Tracker;

use REST_TestDataBuilder;
use TrackerDataBuilder;
use RestBase;

require_once dirname(__FILE__).'/../bootstrap.php';

/**
 * @group TrackerTests
 */
class ArtifactTest extends RestBase {

    protected $project_id;
    protected $tracker_id;
    protected $slogan_field_id;
    protected $desc_field_id;
    protected $status_field_id;
    protected $status_value_id;

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function setUp() {
        parent::setUp();

        $this->project_id = $this->getProjectId();
        $tracker          = $this->getTracker();
        $this->tracker_id = $tracker['id'];

        foreach ($tracker['fields'] as $field) {
            if ($field['name'] === 'slogan') {
                $this->slogan_field_id = $field['field_id'];
            } elseif ($field['name'] === 'epic_desc') {
                $this->desc_field_id = $field['field_id'];
            } elseif ($field['name'] === 'status') {
                $this->status_field_id = $field['field_id'];
                $this->status_value_id = $field['values'][0]['id'];
            }
        }
    }

    private function getProjectId() {
        $response = $this->getResponse($this->client->get('projects/'))->json();
        foreach($response as $project_json) {
            if ($project_json['shortname'] === TrackerDataBuilder::XML_PROJECT_ID_SHORT_NAME) {
                return $project_json['id'];
            }
        }
    }

    private function getTracker() {
        $response = $this->getResponse($this->client->get('projects/'. $this->project_id . '/trackers'))->json();
        return $response[0];
    }

    public function testGetArtifact() {
        $response = $this->getResponse($this->xml_client->get('artifacts/'.$this->release_artifact_ids[1]));
        $this->assertEquals($response->getStatusCode(), 200);

        $artifact_xml = $response->xml();

        $this->assertEquals((int) $artifact_xml->id, $this->release_artifact_ids[1]);
        $this->assertEquals((int) $artifact_xml->project->id, $this->project_private_member_id);
    }

    public function testPOSTArtifact()
    {
        $xml = "<request><tracker><id>$this->releases_tracker_id</id></tracker><values><item><field_id>" .
            REST_TestDataBuilder::RELEASE_FIELD_NAME_ID . "</field_id><value>Test Release</value></item><item><field_id>" .
            REST_TestDataBuilder::RELEASE_FIELD_STATUS_ID . "</field_id><bind_value_ids><item>" .
            REST_TestDataBuilder::RELEASE_STATUS_CURRENT_ID . "</item></bind_value_ids></item></values></request>";

        $response = $this->getResponse($this->xml_client->post('artifacts', null, $xml));

        $this->assertEquals($response->getStatusCode(), 201);
        $artifact_xml = $response->xml();

        $artifact_id = (int) $artifact_xml->id;
        $this->assertGreaterThan(0, $artifact_id);

        return $artifact_id;
    }

    /**
     * @depends testPOSTArtifact
     */
    public function testPUTArtifact($artifact_id)
    {
        $new_value = 'Test Release Updated';
        $xml       = "<request><tracker><id>$this->releases_tracker_id</id></tracker><values><item><field_id>" .
            REST_TestDataBuilder::RELEASE_FIELD_NAME_ID . "</field_id><value>" . $new_value . "</value></item></values></request>";

        $response = $this->getResponse($this->xml_client->put('artifacts/' . $artifact_id, null, $xml));

        $this->assertEquals($response->getStatusCode(), 200);
        $artifact_xml = $this->getResponse($this->xml_client->get('artifacts/' . $artifact_id))->xml();

        $this->assertEquals($new_value, (string) $artifact_xml->values->item[0]->value);
    }

    public function testPOSTArtifactInXMLTracker() {
        $xml = "<request><tracker><id>".$this->tracker_id."</id></tracker><values><item><field_id>".$this->slogan_field_id."</field_id><value>slogan</value></item><item><field_id>".$this->desc_field_id."</field_id><value>desc</value></item><item><field_id>".$this->status_field_id."</field_id><bind_value_ids><item>".$this->status_value_id."</item></bind_value_ids></item></values></request>";

        $response = $this->getResponse($this->xml_client->post('artifacts', null, $xml));

        $this->assertEquals($response->getStatusCode(), 201);
        $artifact_xml = $response->xml();

        $artifact_id = (int) $artifact_xml->id;
        $this->assertGreaterThan(0, $artifact_id);

        return $artifact_id;
    }

    /**
     * @depends testPOSTArtifactInXMLTracker
     */
    public function testGetArtifactInXMLTracker($artifact_id) {
        $response = $this->getResponse($this->xml_client->get('artifacts/'.$artifact_id));
        $this->assertEquals($response->getStatusCode(), 200);

        $artifact_xml = $response->xml();

        $this->assertEquals((int) $artifact_xml->id, $artifact_id);
        $this->assertEquals((int) $artifact_xml->project->id, $this->project_id);

        $this->assertGreaterThan(0, count($artifact_xml->values->children()));
        $this->assertEquals(0, count($artifact_xml->values_by_field->children()));

        $this->assertEquals((string) $artifact_xml->values->item[0]->label, 'Slogan');
        $this->assertEquals((string) $artifact_xml->values->item[0]->value, 'slogan');
        $this->assertEquals((string) $artifact_xml->values->item[5]->label, 'Status');
        $this->assertEquals((string) $artifact_xml->values->item[5]->values->item->label, 'SM New');
        $this->assertEquals((string) $artifact_xml->values->item[5]->bind_value_ids, '810');
    }

    /**
     * @depends testPOSTArtifactInXMLTracker
     */
    public function testGetArtifactInXMLTrackerWithValuesByField($artifact_id) {
        $response = $this->getResponse($this->xml_client->get('artifacts/'.$artifact_id.'?values_format=by_field'));
        $this->assertEquals($response->getStatusCode(), 200);

        $artifact_xml = $response->xml();

        $this->assertEquals((int) $artifact_xml->id, $artifact_id);
        $this->assertEquals((int) $artifact_xml->project->id, $this->project_id);

        $this->assertEquals(0, count($artifact_xml->values->children()));
        $this->assertGreaterThan(0, count($artifact_xml->values_by_field->children()));

        $this->assertEquals((string) $artifact_xml->values_by_field->slogan->value, 'slogan');
   }

    /**
     * @depends testPOSTArtifactInXMLTracker
     */
    public function testGetArtifactInXMLTrackerInBothFormat($artifact_id) {
        $response = $this->getResponse($this->xml_client->get('artifacts/'.$artifact_id.'?values_format=all'));
        $this->assertEquals($response->getStatusCode(), 200);

        $artifact_xml = $response->xml();

        $this->assertEquals((int) $artifact_xml->id, $artifact_id);
        $this->assertEquals((int) $artifact_xml->project->id, $this->project_id);

        $this->assertGreaterThan(0, count($artifact_xml->values->children()));
        $this->assertGreaterThan(0, count($artifact_xml->values_by_field->children()));

        $this->assertEquals((string) $artifact_xml->values->item[0]->label, 'Slogan');
        $this->assertEquals((string) $artifact_xml->values->item[0]->value, 'slogan');
        $this->assertEquals((string) $artifact_xml->values->item[5]->label, 'Status');
        $this->assertEquals((string) $artifact_xml->values->item[5]->values->item->label, 'SM New');
        $this->assertEquals((string) $artifact_xml->values->item[5]->bind_value_ids, '810');

        $this->assertEquals((string) $artifact_xml->values_by_field->slogan->value, 'slogan');
    }

    /**
     * @depends testGetArtifactInXMLTrackerInBothFormat
     */
    public function testPOSTArtifactInXMLTrackerWithValuesByField() {
        $xml = "<request><tracker><id>".$this->tracker_id."</id></tracker><values_by_field><slogan><value>Sloganv2</value></slogan><epic_desc><value><content>Descv2</content><format>html</format></value></epic_desc></values_by_field></request>";

        $response = $this->getResponse($this->xml_client->post('artifacts', null, $xml));

        $this->assertEquals($response->getStatusCode(), 201);
        $artifact_xml = $response->xml();

        $artifact_id = (int) $artifact_xml->id;
        $this->assertGreaterThan(0, $artifact_id);

        return $artifact_id;
    }

    /**
     * @depends testPOSTArtifactInXMLTrackerWithValuesByField
     */
    public function testGetArtifactCreatedWithValueByFieldInXMLTracker($artifact_id) {
        $response = $this->getResponse($this->xml_client->get('artifacts/'.$artifact_id.'?values_format=by_field'));
        $this->assertEquals($response->getStatusCode(), 200);

        $artifact_xml = $response->xml();

        $this->assertEquals((int) $artifact_xml->id, $artifact_id);
        $this->assertEquals((int) $artifact_xml->project->id, $this->project_id);

        $this->assertEquals(0, count($artifact_xml->values->children()));
        $this->assertGreaterThan(0, count($artifact_xml->values_by_field->children()));

        $this->assertEquals((string) $artifact_xml->values_by_field->slogan->value, 'Sloganv2');
        $this->assertEquals((string) $artifact_xml->values_by_field->epic_desc->format, 'html');
        $this->assertEquals((string) $artifact_xml->values_by_field->epic_desc->value, 'Descv2');
   }
}
