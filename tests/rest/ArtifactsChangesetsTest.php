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

/**
 * @group ArtifactsChangesets
 */
class ArtifactsChangesetsTest extends RestBase {

    private $artifact_resource;
    private $data_already_created = false;

    /** @var Test_Rest_TrackerFactory */
    private $tracker_test_helper;

    public function setUp()
    {
        parent::setUp();

        $this->tracker_test_helper = new Test\Rest\Tracker\TrackerFactory(
            $this->client,
            $this->rest_request,
            $this->project_private_member_id,
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->createData();
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6371
     */
    public function testOptionsArtifactId() {
        $response = $this->getResponse($this->client->options($this->artifact_resource['uri'].'/changesets'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6371
     */
    public function testGetChangesetsHasPagination() {
        $response = $this->getResponse($this->client->get($this->artifact_resource['uri'].'/changesets?offset=2&limit=10'));
        $this->assertEquals($response->getStatusCode(), 200);

        $changesets = $response->json();
        $this->assertCount(1, $changesets);
        $this->assertEquals("Awesome changes", $changesets[0]['last_comment']['body']);

        $fields = $changesets[0]['values'];
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
                case 'text':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(is_string($field['value']));
                    $this->assertTrue(is_string($field['format']));
                    $this->assertTrue($field['format'] == 'text'|| $field['format'] == 'html' );
                    break;
                case 'msb':
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



        $pagination_offset = $response->getHeader('X-PAGINATION-OFFSET')->normalize()->toArray();
        $this->assertEquals($pagination_offset[0], 2);

        $pagination_size = $response->getHeader('X-PAGINATION-SIZE')->normalize()->toArray();
        $this->assertEquals($pagination_size[0], 3);
    }

    private function getResponse($request) {
        return $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $request
        );
    }

    private function createData()
    {
        if ($this->data_already_created) {
            return;
        }

        $tracker = $this->tracker_test_helper->getTrackerRest('task');
        $this->artifact_resource = $tracker->createArtifact(array(
            $tracker->getSubmitTextValue('Summary', 'A task to do'),
            $tracker->getSubmitListValue('Status', 'To be done')
        ));

        $tracker->addCommentToArtifact($this->artifact_resource, "I do some changes");
        $tracker->addCommentToArtifact($this->artifact_resource, "Awesome changes");

        $this->data_already_created = true;
    }
}
