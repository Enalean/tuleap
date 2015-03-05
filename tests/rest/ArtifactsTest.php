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
 * @group ArtifactsTest
 */
class ArtifactsTest extends RestBase {

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testOptionsArtifactId() {
        $response = $this->getResponse($this->client->options('artifacts/9'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOptionsArtifacts() {
        $response = $this->getResponse($this->client->options('artifacts'));
        $this->assertEquals(array('OPTIONS', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPostArtifact() {
        $summary_field_label = 'Summary';
        $summary_field_value = "This is a new epic";
        $post_resource = json_encode(array(
            'tracker' => array(
                'id'  => TestDataBuilder::EPICS_TRACKER_ID,
                'uri' => 'whatever'
            ),
            'values' => array(
               $this->getSubmitTextValue(TestDataBuilder::EPICS_TRACKER_ID, $summary_field_label, $summary_field_value),
               $this->getSubmitListValue(TestDataBuilder::EPICS_TRACKER_ID, 'Status', 205)
            ),
        ));

        $response = $this->getResponse($this->client->post('artifacts', null, $post_resource));
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getHeader('Last-Modified'));
        $artifact_reference = $response->json();
        $this->assertGreaterThan(0, $artifact_reference['id']);
        
        $fetched_value = $this->getFieldValueForFieldLabel($artifact_reference['id'], $summary_field_label);
        $this->assertEquals($summary_field_value, $fetched_value);
        return $artifact_reference['id'];
    }


    /**
     * @depends testPostArtifact
     */
    public function testPutArtifactId() {
        $test_post_return = func_get_args();
        $artifact_id = $test_post_return[0];
        $field_label =  'Summary';
        $field_id = $this->getFieldIdForFieldLabel($artifact_id, $field_label);
        $put_resource = json_encode(array(
            'values' => array(
                array(
                    'field_id' => $field_id,
                    'value'    => "Amazing test stuff",
                ),
            ),
        ));

        $response    = $this->getResponse($this->client->put('artifacts/'.$artifact_id, null, $put_resource));
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getHeader('Last-Modified'));

        $this->assertEquals("Amazing test stuff", $this->getFieldValueForFieldLabel($artifact_id, $field_label));
        return $artifact_id;
    }

    /**
     * @depends testPostArtifact
     */
    public function testPutArtifactIdWithValidConcurrentEdition() {
        $test_post_return = func_get_args();
        $artifact_id = $test_post_return[0];

        $field_label =  'Summary';
        $field_id = $this->getFieldIdForFieldLabel($artifact_id, $field_label);
        $put_resource = json_encode(array(
            'values' => array(
                array(
                    'field_id' => $field_id,
                    'value'    => "This should return 200",
                ),
            ),
        ));

        $request = $this->client->put('artifacts/'.$artifact_id, null, $put_resource);

        $artifact      = $this->getResponse($this->client->get('artifacts/'.$artifact_id));
        $last_modified = $artifact->getHeader('Last-Modified')->normalize()->__toString();
        $request->setHeader('If-Unmodified-Since', $last_modified);

        $response = $this->getResponse($request);
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getHeader('Last-Modified'));

        $this->assertEquals("This should return 200", $this->getFieldValueForFieldLabel($artifact_id, $field_label));
        return $artifact_id;
    }

    /**
     * @depends testPostArtifact
     */
    public function testPutArtifactIdWithInvalidConcurrentEditionDate() {
        $test_post_return = func_get_args();
        $artifact_id = $test_post_return[0];

        $field_label =  'Summary';
        $field_id = $this->getFieldIdForFieldLabel($artifact_id, $field_label);
        $put_resource = json_encode(array(
            'values' => array(
                array(
                    'field_id' => $field_id,
                    'value'    => "This should return 412",
                ),
            ),
        ));

        $last_modified = "2001-03-05T15:14:55+01:00";
        $request       = $this->client->put('artifacts/'.$artifact_id, null, $put_resource);
        $request->setHeader('If-Unmodified-Since', $last_modified);

        try {
            $this->getResponse($request);
        } catch (Exception $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 412);
        }
    }

    /**
     * @depends testPutArtifactId
     */
    public function testPutArtifactComment() {
        $test_post_return = func_get_args();
        $artifact_id = $test_post_return[0];

        $put_resource = json_encode(array(
            'values' => array(),
            'comment' => array(
                'format' => 'text',
                'body'   => 'Please see my comment',
            ),
        ));

        $response    = $this->getResponse($this->client->put('artifacts/'.$artifact_id, null, $put_resource));
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getHeader('Last-Modified'));

        $response   = $this->getResponse($this->client->get("artifacts/$artifact_id/changesets"));
        $changesets = $response->json();
        $this->assertEquals(4, count($changesets));
        $this->assertEquals('Please see my comment', $changesets[3]['last_comment']['body']);
    }

    private function getFieldIdForFieldLabel($artifact_id, $field_label) {
        $value = $this->getFieldByFieldLabel($artifact_id, $field_label);
        return $value['field_id'];
    }

    private function getFieldValueForFieldLabel($artifact_id, $field_label) {
        $value = $this->getFieldByFieldLabel($artifact_id, $field_label);
        return $value['value'];
    }

    private function getFieldByFieldLabel($artifact_id, $field_label) {
        $artifact = $this->getArtifact($artifact_id);
        foreach ($artifact['values'] as $value) {
            if ($value['label'] == $field_label) {
                return $value;
            }
        }
    }

    private function getArtifact($artifact_id) {
        $response = $this->getResponse($this->client->get('artifacts/'.$artifact_id));
        $this->assertNotNull($response->getHeader('Last-Modified'));
        return $response->json();
    }

    private function getSubmitTextValue($tracker_id, $field_label, $field_value) {
        $field_def = $this->getFieldDefByFieldLabel($tracker_id, $field_label);
        return array(
            'field_id' => $field_def['field_id'],
            'value'    => $field_value,
        );
    }

    private function getSubmitListValue($tracker_id, $field_label, $field_value) {
        $field_def = $this->getFieldDefByFieldLabel($tracker_id, $field_label);
        return array(
            'field_id'       => $field_def['field_id'],
            'bind_value_ids' => array(
                $field_value
            ),
        );
    }

    private function getFieldDefByFieldLabel($tracker_id, $field_label) {
        $tracker = $this->getTracker($tracker_id);
        foreach ($tracker['fields'] as $field) {
            if ($field['label'] == $field_label) {
                return $field;
            }
        }
    }

    private function getTracker($tracker_id) {
        $response = $this->getResponse($this->client->get('trackers/'.$tracker_id));
        return $response->json();
    }
}