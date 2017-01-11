<?php
/**
 * Copyright (c) Enalean, 2013-2015. All rights reserved
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
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
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
                'id'  => REST_TestDataBuilder::EPICS_TRACKER_ID,
                'uri' => 'whatever'
            ),
            'values' => array(
               $this->getSubmitTextValue(REST_TestDataBuilder::EPICS_TRACKER_ID, $summary_field_label, $summary_field_value),
               $this->getSubmitListValue(REST_TestDataBuilder::EPICS_TRACKER_ID, 'Status', 103)
            ),
        ));

        $response = $this->getResponse($this->client->post('artifacts', null, $post_resource));
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertRegExp('/.+ GMT$/', $response->getHeader('Last-Modified')->normalize()->__toString(), 'Last-Modified must be RFC1123 complient');
        $this->assertNotNull($response->getHeader('Etag'));
        $this->assertNotNull($response->getHeader('Location'));

        $artifact_reference = $response->json();
        $this->assertGreaterThan(0, $artifact_reference['id']);

        $fetched_value = $this->getFieldValueForFieldLabel($artifact_reference['id'], $summary_field_label);
        $this->assertEquals($summary_field_value, $fetched_value);
        return $artifact_reference['id'];
    }

    public function testComputedFieldsCalculation()
    {
        $this->testComputedFieldValueForArtifactId(
            REST_TestDataBuilder::LEVEL_ONE_ARTIFACT_A_ID,
            10,
            25,
            20,
            33
        );
        $this->testComputedFieldValueForArtifactId(
            REST_TestDataBuilder::LEVEL_TWO_ARTIFACT_B_ID,
            null,
            25,
            15,
            33
        );
        $this->testComputedFieldValueForArtifactId(
            REST_TestDataBuilder::LEVEL_TWO_ARTIFACT_C_ID,
            null,
            null,
            5,
            null
        );
        $this->testComputedFieldValueForArtifactId(
            REST_TestDataBuilder::LEVEL_THREE_ARTIFACT_D_ID,
            null,
            null,
            5,
            11
        );
        $this->testComputedFieldValueForArtifactId(
            REST_TestDataBuilder::LEVEL_THREE_ARTIFACT_E_ID,
            10,
            10,
            5,
            22
        );
        $this->testComputedFieldValueForArtifactId(
            REST_TestDataBuilder::LEVEL_THREE_ARTIFACT_F_ID,
            null,
            null,
            5,
            null
        );
        $this->testComputedFieldValueForArtifactId(
            REST_TestDataBuilder::LEVEL_FOUR_ARTIFACT_G_ID,
            null,
            15,
            5,
            null
        );
        $this->testComputedFieldValueForArtifactId(
            REST_TestDataBuilder::LEVEL_FOUR_ARTIFACT_H_ID,
            null,
            10,
            5,
            null
        );
    }

    public function testComputedFieldValueForArtifactId(
        $artifact_id = null,
        $capacity_slow_compute_value = null,
        $capacity_fast_compute_value = null,
        $remaining_effort_value = null,
        $total_effort_value = null
    ) {
        if ($artifact_id !== null) {
            $response = $this->getResponse($this->client->get("artifacts/$artifact_id"));
            $artifact = $response->json();

            $this->assertNotNull($response->getHeader('Last-Modified'));
            $this->assertNotNull($response->getHeader('Etag'));
            $this->assertNull($response->getHeader('Location'), "There is no redirect with a simple GET");

            $fields = $artifact['values'];

            foreach ($fields as $field) {
                $value = null;
                if (isset($field['manual_value'])) {
                    $value = $field['manual_value'];
                } else if (isset($field[$field['type'].'_value'])) {
                    $value = $field[$field['type'].'_value'];
                } else if (isset($field['value'])) {
                    $value = $field['value'];
                }

                if ($field['label'] === 'remaining_effort') {
                    $this->assertEquals($remaining_effort_value, $value);
                }
                if ($field['label'] === 'capacity') {
                    $this->assertEquals($capacity_fast_compute_value, $value);
                }
                if ($field['label'] === 'effort_estimate') {
                    $this->assertEquals($total_effort_value, $value);
                }
            }
        }
    }

    public function testGETBurndownForParentArtifact()
    {
        $response     = $this->getResponse(
            $this->client->get("artifacts/" . REST_TestDataBuilder::BURNDOWN_FATHER_ARTIFACT_ID)
        );

        $burndown     = $response->json();

        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));
        $this->assertNull($response->getHeader('Location'), "There is no redirect with a simple GET");
        $this->assertEquals(200, $response->getStatusCode());

        $expected_burndown_chart = array(
            55,
            43,
            48,
            37,
            37,
            37
        );

        $this->assertEquals($burndown['values'][6]['value']['points'], $expected_burndown_chart);
    }

    public function testGETBurndownForAChildrenArtifact()
    {
        $response     = $this->getResponse(
            $this->client->get("artifacts/" . REST_TestDataBuilder::BURNDOWN_CHILD_ARTIFACT_ID)
        );

        $burndown     = $response->json();

        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));
        $this->assertNull($response->getHeader('Location'), "There is no redirect with a simple GET");
        $this->assertEquals(200, $response->getStatusCode());

        $expected_burndown_chart = array(
            32,
            20,
            25,
            20,
            20,
            20,
        );

        $this->assertEquals($burndown['values'][6]['value']['points'], $expected_burndown_chart);
    }

    public function testGETBurndownForAnotherChildrenArtifact()
    {
        $response     = $this->getResponse(
            $this->client->get("artifacts/" . REST_TestDataBuilder::BURNDOWN_CHILD_2_ARTIFACT_ID)
        );

        $burndown     = $response->json();

        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));
        $this->assertNull($response->getHeader('Location'), "There is no redirect with a simple GET");
        $this->assertEquals(200, $response->getStatusCode());

        $expected_burndown_chart = array(
            25,
            20,
            40,
            20,
            20,
            20,
        );

        $this->assertEquals($burndown['values'][6]['value']['points'], $expected_burndown_chart);
    }

    /**
     * @depends testPostArtifact
     */
    public function testGetArtifact() {
        $test_put_return = func_get_args();
        $artifact_id = $test_put_return[0];

        $response   = $this->getResponse($this->client->get("artifacts/$artifact_id"));
        $artifact = $response->json();

        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));
        $this->assertNull($response->getHeader('Location'), "There is no redirect with a simple GET");

        $fields = $artifact['values'];

        $this->assertTrue(is_int($artifact['id']));
        $this->assertTrue(is_int($artifact['submitted_by']));

        $this->assertTrue(is_string($artifact['uri']));
        $this->assertTrue(is_string($artifact['xref']));
        $this->assertTrue(is_string($artifact['submitted_on']));
        $this->assertTrue(is_string($artifact['html_url']));
        $this->assertTrue(is_string($artifact['changesets_uri']));
        $this->assertTrue(is_string($artifact['last_modified_date']));
        $this->assertTrue(is_string($artifact['status']));
        $this->assertTrue(is_string($artifact['title']));

        $this->assertTrue(is_array($artifact['assignees']));

        $this->assertTrue(is_array($artifact['tracker']));
        $this->assertTrue(is_array($artifact['project']));
        $this->assertTrue(is_array($artifact['submitted_by_user']));

        foreach($fields as $field) {
            switch($field['type']) {
                case 'string':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_string($field['value']));
                    break;
                case 'cross':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_array($field['value']));
                    break;
                case 'art_link':
                    $this->assertTrue(is_array($field['links']));
                    $this->assertTrue(is_array($field['reverse_links']));
                    break;
                case 'text':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_string($field['value']));
                    $this->assertTrue(is_string($field['format']));
                    $this->assertTrue($field['format'] == 'text'|| $field['format'] == 'html' );
                    break;
                case 'sb':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_array($field['values']));
                    $this->assertTrue(is_array($field['bind_value_ids']));
                    break;
                case 'computed':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_int($field['value']) || is_null($field['value']));
                    break;
                case 'aid':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_int($field['value']));
                    break;
                case 'luby':
                case 'subby':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_array($field['value']));
                    $this->assertTrue(array_key_exists('display_name', $field['value']));
                    $this->assertTrue(array_key_exists('avatar_url', $field['value']));
                    break;
                case 'lud':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(DateTime::createFromFormat('Y-m-d\TH:i:sT' , $field['value']) !== false);
                    break;
                case 'subon':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(DateTime::createFromFormat('Y-m-d\TH:i:sT' , $field['value']) !== false);
                    break;
                default:
                    throw new Exception('You need to update this test for the field: '.print_r($field, true));
            }
        }
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
                    'value'    => "wunderbar",
                ),
            ),
        ));

        $response    = $this->getResponse($this->client->put('artifacts/'.$artifact_id, null, $put_resource));
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));

        $this->assertEquals("wunderbar", $this->getFieldValueForFieldLabel($artifact_id, $field_label));
        return $artifact_id;
    }

    /**
     * @depends testPutArtifactId
     */
    public function testPutIsIdempotent() {
        $test_post_return = func_get_args();
        $artifact_id = $test_post_return[0];

        $artifact      = $this->getResponse($this->client->get('artifacts/'.$artifact_id));
        $last_modified = $artifact->getHeader('Last-Modified')->normalize()->__toString();
        $etag          = $artifact->getHeader('Etag')->normalize()->__toString();

        $field_label =  'Summary';
        $field_id = $this->getFieldIdForFieldLabel($artifact_id, $field_label);
        $put_resource = json_encode(array(
            'values' => array(
                array(
                    'field_id' => $field_id,
                    'value'    => "wunderbar",
                ),
            ),
        ));

        $response    = $this->getResponse($this->client->put('artifacts/'.$artifact_id, null, $put_resource));
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));

        $this->assertEquals($response->getHeader('Last-Modified')->normalize()->__toString(), $last_modified);
        $this->assertEquals($response->getHeader('Etag')->normalize()->__toString(), $etag);
        $this->assertEquals("wunderbar", $this->getFieldValueForFieldLabel($artifact_id, $field_label));
        return $artifact_id;
    }

    /**
     * @depends testPutIsIdempotent
     */
    public function testPutArtifactIdWithValidIfUnmodifiedSinceHeader() {
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
        $this->assertNotNull($response->getHeader('Etag'));

        $this->assertEquals("This should return 200", $this->getFieldValueForFieldLabel($artifact_id, $field_label));
        return $artifact_id;
    }

    /**
     * @depends testPutArtifactIdWithValidIfUnmodifiedSinceHeader
     */
    public function testPutArtifactIdWithValidIfMatchHeader() {
        $test_post_return = func_get_args();
        $artifact_id = $test_post_return[0];

        $field_label =  'Summary';
        $field_id = $this->getFieldIdForFieldLabel($artifact_id, $field_label);
        $put_resource = json_encode(array(
            'values' => array(
                array(
                    'field_id' => $field_id,
                    'value'    => "varm choklade",
                ),
            ),
        ));

        $request = $this->client->put('artifacts/'.$artifact_id, null, $put_resource);

        $artifact      = $this->getResponse($this->client->get('artifacts/'.$artifact_id));
        $Etag = $artifact->getHeader('Etag')->normalize()->__toString();
        $request->setHeader('If-Match', $Etag);

        $response = $this->getResponse($request);
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));

        $this->assertEquals("varm choklade", $this->getFieldValueForFieldLabel($artifact_id, $field_label));
        return $artifact_id;
    }

    /**
     * @depends testPutArtifactIdWithValidIfMatchHeader
     */
    public function testPutArtifactIdWithInvalidIfUnmodifiedSinceHeader() {
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
     * @depends testPutArtifactIdWithValidIfMatchHeader
     */
    public function testPutArtifactIdWithInvalidIfMatchHeader() {
        $test_post_return = func_get_args();
        $artifact_id = $test_post_return[0];

        $field_label =  'Summary';
        $field_id = $this->getFieldIdForFieldLabel($artifact_id, $field_label);
        $put_resource = json_encode(array(
            'values' => array(
                array(
                    'field_id' => $field_id,
                    'value'    => "This should return 4122415",
                ),
            ),
        ));

        $Etag    = "one empty bottle";
        $request = $this->client->put('artifacts/'.$artifact_id, null, $put_resource);
        $request->setHeader('If-Match', $Etag);

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
        $this->assertNotNull($response->getHeader('Etag'));

        $response   = $this->getResponse($this->client->get("artifacts/$artifact_id/changesets"));
        $changesets = $response->json();
        $this->assertEquals(5, count($changesets));
        $this->assertEquals('Please see my comment', $changesets[4]['last_comment']['body']);

        return $artifact_id;
    }

    public function testAnonymousGETArtifact() {
        try {
            $this->client->get('artifacts/'.REST_TestDataBuilder::STORY_1_ARTIFACT_ID)->send();
        } catch (Exception $e) {
            $this->assertEquals($e->getResponse()->getStatusCode(), 403);
        }
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
        $this->assertNotNull($response->getHeader('Etag'));

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
