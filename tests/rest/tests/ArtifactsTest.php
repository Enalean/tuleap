<?php
/**
 * Copyright (c) Enalean, 2013-Present. All rights reserved
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

use Guzzle\Http\Message\Response;
use Test\Rest\Tracker\ArtifactsTestExecutionHelper;

/**
 * @group ArtifactsTest
 */
class ArtifactsTest extends ArtifactsTestExecutionHelper  // @codingStandardsIgnoreLine
{
    public function testOptionsArtifactId()
    {
        $response = $this->getResponse($this->client->options('artifacts/9'));

        $this->assertEquals(
            ['OPTIONS', 'GET', 'PUT', 'DELETE', 'PATCH'],
            $response->getHeader('Allow')->normalize()->toArray()
        );
    }

    public function testOptionsArtifacts()
    {
        $response = $this->getResponse($this->client->options('artifacts'));

        $this->assertEquals(
            ['OPTIONS', 'GET', 'POST'],
            $response->getHeader('Allow')->normalize()->toArray()
        );
    }

    public function testPostArtifact()
    {
        $summary_field_label = 'Summary';
        $summary_field_value = "This is a new epic";

        $post_body = $this->buildPOSTBodyContent($summary_field_label, $summary_field_value);

        $response = $this->getResponse($this->client->post('artifacts', null, $post_body));
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
        $this->checkComputedFieldValueForArtifactId(
            $this->level_one_artifact_ids[1],
            10,
            25,
            20,
            33
        );
        $this->checkComputedFieldValueForArtifactId(
            $this->level_two_artifact_ids[1],
            null,
            25,
            15,
            33
        );
        $this->checkComputedFieldValueForArtifactId(
            $this->level_two_artifact_ids[2],
            null,
            null,
            5,
            null
        );
        $this->checkComputedFieldValueForArtifactId(
            $this->level_three_artifact_ids[1],
            null,
            null,
            5,
            11
        );
        $this->checkComputedFieldValueForArtifactId(
            $this->level_three_artifact_ids[2],
            10,
            10,
            5,
            22
        );
        $this->checkComputedFieldValueForArtifactId(
            $this->level_three_artifact_ids[3],
            null,
            null,
            5,
            null
        );
        $this->checkComputedFieldValueForArtifactId(
            $this->level_four_artifact_ids[1],
            null,
            15,
            5,
            null
        );
        $this->checkComputedFieldValueForArtifactId(
            $this->level_four_artifact_ids[2],
            null,
            10,
            5,
            null
        );
    }

    private function checkComputedFieldValueForArtifactId(
        $artifact_id,
        $capacity_slow_compute_value,
        $capacity_fast_compute_value,
        $remaining_effort_value,
        $total_effort_value
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
                } elseif (isset($field[$field['type'] . '_value'])) {
                    $value = $field[$field['type'] . '_value'];
                } elseif (isset($field['value'])) {
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
        $response = $this->getResponse(
            $this->client->get("artifacts/" . $this->pokemon_artifact_ids[1])
        );

        $burndown = $response->json();

        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));
        $this->assertNull($response->getHeader('Location'), "There is no redirect with a simple GET");
        $this->assertEquals(200, $response->getStatusCode());

        $start_date = new DateTime();
        $start_date->setTimezone(new DateTimeZone('GMT+1'));
        $start_date->setDate(2016, 11, 17);
        $start_date->setTime(23, 59, 59);

        $expected_burndown_chart_with_date = [
            [
                "date"             => $start_date->format(DATE_ATOM),
                "remaining_effort" => 55
            ],
            [
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 43
            ],
            [
                "date"             => $start_date->modify('+3 day')->format(DATE_ATOM),
                "remaining_effort" => 48
            ],
            [
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 37
            ],
            [
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 37
            ],
            [
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 37
            ]
        ];

        $expected_burndown_chart = [
            55,
            43,
            48,
            37,
            37,
            37
        ];

        $this->assertEquals($burndown['values'][6]['value']['points'], $expected_burndown_chart);
        $this->assertEquals($burndown['values'][6]['value']['points_with_date'], $expected_burndown_chart_with_date);
    }

    public function testGETBurndownForAChildrenArtifact()
    {
        $response     = $this->getResponse(
            $this->client->get("artifacts/" . $this->niveau_1_artifact_ids[1])
        );

        $burndown     = $response->json();

        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));
        $this->assertNull($response->getHeader('Location'), "There is no redirect with a simple GET");
        $this->assertEquals(200, $response->getStatusCode());

        $start_date = new DateTime();
        $start_date->setTimezone(new DateTimeZone('GMT+1'));
        $start_date->setDate(2016, 11, 17);
        $start_date->setTime(23, 59, 59);

        $expected_burndown_chart = array(
            array(
                "date"             => $start_date->format(DATE_ATOM),
                "remaining_effort" => 32
            ),
            array(
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 20
            ),
            array(
                "date"             => $start_date->modify('+3 day')->format(DATE_ATOM),
                "remaining_effort" => 25
            ),
            array(
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 20
            ),
            array(
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 20
            ),
            array(
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 20
            )
        );

        $this->assertEquals($burndown['values'][6]['value']['points_with_date'], $expected_burndown_chart);
    }

    public function testGETBurndownForAnotherChildrenArtifact()
    {
        $response     = $this->getResponse(
            $this->client->get("artifacts/" . $this->niveau_2_artifact_ids[2])
        );

        $burndown     = $response->json();

        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));
        $this->assertNull($response->getHeader('Location'), "There is no redirect with a simple GET");
        $this->assertEquals(200, $response->getStatusCode());

        $start_date = new DateTime();
        $start_date->setTimezone(new DateTimeZone('GMT+1'));
        $start_date->setDate(2016, 11, 17);
        $start_date->setTime(23, 59, 59);

        $expected_burndown_chart = array(
            array(
                "date"             => $start_date->format(DATE_ATOM),
                "remaining_effort" => 25
            ),
            array(
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 20
            ),
            array(
                "date"             => $start_date->modify('+3 day')->format(DATE_ATOM),
                "remaining_effort" => 40
            ),
            array(
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 20
            ),
            array(
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 20
            ),
            array(
                "date"             => $start_date->modify('+1 day')->format(DATE_ATOM),
                "remaining_effort" => 20
            )
        );

        $this->assertEquals($burndown['values'][6]['value']['points_with_date'], $expected_burndown_chart);
    }

    /**
     * @depends testPostArtifact
     */
    public function testGetArtifact($artifact_id)
    {
        $response = $this->getResponse($this->client->get("artifacts/$artifact_id"));

        $this->assertArtifact($response);
    }

    /**
     * @depends testPostArtifact
     */
    public function testGetArtifacts()
    {
        $do_not_exist_artifact_id = 999999999999999999;
        $existing_artifact_ids    = array_merge($this->level_one_artifact_ids, $this->level_two_artifact_ids);
        $wanted_artifacts_ids     = $existing_artifact_ids;
        $wanted_artifacts_ids[]   = $do_not_exist_artifact_id;

        $query = json_encode(array('id' => $wanted_artifacts_ids));

        $response  = $this->getResponse($this->client->get('artifacts?query=' . urlencode($query)));
        $artifacts = $response->json();

        $this->assertCount(count($existing_artifact_ids), $artifacts['collection']);

        $response_with_read_only_user  = $this->getResponse(
            $this->client->get('artifacts?query=' . urlencode($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $artifacts = $response_with_read_only_user->json();

        $this->assertCount(count($existing_artifact_ids), $artifacts['collection']);
    }

    public function testGetTooManyArtifacts()
    {
        $too_many_artifacts_id = array_keys(array_fill(0, 200, 1));
        $query                 = json_encode(array('id' => $too_many_artifacts_id));

        $response = $this->getResponse($this->client->get('artifacts?query=' . urlencode($query)));
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testPostArtifact
     */
    public function testPutArtifactIdWithUserRESTReadOnlyAdminNonMember($artifact_id)
    {
        $field_label =  'Summary';
        $put_body    = $this->buildPUTBodyContent($artifact_id, $field_label);

        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->client->put('artifacts/' . $artifact_id, null, $put_body)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testPostArtifact
     */
    public function testPutArtifactId($artifact_id)
    {
        $field_label =  'Summary';
        $put_body    = $this->buildPUTBodyContent($artifact_id, $field_label);
        $response    = $this->getResponse($this->client->put('artifacts/' . $artifact_id, null, $put_body));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));

        $this->assertEquals("wunderbar", $this->getFieldValueForFieldLabel($artifact_id, $field_label));
        return $artifact_id;
    }

    private function buildPUTBodyContent($artifact_id, $field_label)
    {
        $field_id = $this->getFieldIdForFieldLabel($artifact_id, $field_label);

        return json_encode(array(
            'values' => array(
                array(
                    'field_id' => $field_id,
                    'value'    => "wunderbar",
                ),
            ),
        ));
    }

    /**
     * @depends testPutArtifactId
     */
    public function testPutIsIdempotent($artifact_id)
    {
        $artifact      = $this->getResponse($this->client->get('artifacts/' . $artifact_id));
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

        $response    = $this->getResponse($this->client->put('artifacts/' . $artifact_id, null, $put_resource));
        $this->assertEquals(200, $response->getStatusCode());
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
    public function testPutArtifactIdWithValidIfUnmodifiedSinceHeader($artifact_id)
    {
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

        $request = $this->client->put('artifacts/' . $artifact_id, null, $put_resource);

        $artifact      = $this->getResponse($this->client->get('artifacts/' . $artifact_id));
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
    public function testPutArtifactIdWithValidIfMatchHeader($artifact_id)
    {
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

        $request = $this->client->put('artifacts/' . $artifact_id, null, $put_resource);

        $artifact      = $this->getResponse($this->client->get('artifacts/' . $artifact_id));
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
    public function testPutArtifactIdWithInvalidIfUnmodifiedSinceHeader($artifact_id)
    {
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
        $request       = $this->client->put('artifacts/' . $artifact_id, null, $put_resource);
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
    public function testPutArtifactIdWithInvalidIfMatchHeader($artifact_id)
    {
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
        $request = $this->client->put('artifacts/' . $artifact_id, null, $put_resource);
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
    public function testPutArtifactComment($artifact_id)
    {
        $put_resource = json_encode(array(
            'values' => array(),
            'comment' => array(
                'format' => 'text',
                'body'   => 'Please see my comment',
            ),
        ));

        $response    = $this->getResponse($this->client->put('artifacts/' . $artifact_id, null, $put_resource));
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));

        $response   = $this->getResponse($this->client->get("artifacts/$artifact_id/changesets"));
        $changesets = $response->json();
        $this->assertEquals(5, count($changesets));
        $this->assertEquals('Please see my comment', $changesets[4]['last_comment']['body']);

        return $artifact_id;
    }

    public function testPutArtifactWithNatures()
    {
        $nature_is_child = '_is_child';
        $nature_empty    = '';
        $artifact_id     = $this->level_one_artifact_ids[1];
        $field_label     = 'artlink';
        $field_id        = $this->getFieldIdForFieldLabel($artifact_id, $field_label);
        $put_resource    = json_encode(
            array(
                'values' => array(
                    array(
                        'field_id' => $field_id,
                        "links"    => array(
                            array("id" => $this->level_three_artifact_ids[1], "type" => $nature_is_child),
                            array("id" => $this->level_three_artifact_ids[3], "type" => $nature_empty),
                        )
                    ),
                ),
            )
        );

        $response = $this->getResponse($this->client->put('artifacts/' . $artifact_id, null, $put_resource));
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertNotNull($response->getHeader('Last-Modified'));
        $this->assertNotNull($response->getHeader('Etag'));

        $response = $this->getResponse($this->client->get("artifacts/$artifact_id/links"));
        $this->assertLinks($response, $nature_is_child, $artifact_id, $nature_empty);

        return $artifact_id;
    }

    public function testAnonymousGETArtifact()
    {
        $response = $this->client->get('artifacts/' . $this->story_artifact_ids[1])->send();
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGetSuspendedProjectArtifactForSiteAdmin()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('artifacts/' . $this->suspended_tracker_artifacts_ids[1])
        );

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testGetSuspendedProjectArtifactForRegularUser()
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->get('artifacts/' . $this->suspended_tracker_artifacts_ids[1])
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPUTSuspendedProjectArtifactForSiteAdmin()
    {
        $target_artifact_id = $this->suspended_tracker_artifacts_ids[1];

        $this->expectExceptionCode(403);

        $put_resource = json_encode(
            [
                'values' => [
                    [
                        'field_id' => $this->getFieldByFieldLabel($target_artifact_id, 'name'),
                        'value'    => "Yolo"
                    ],
                ],
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                'artifacts/' . $target_artifact_id,
                null,
                $put_resource
            )
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testPUTSuspendedProjectArtifactForRegularUser()
    {
        $target_artifact_id = $this->suspended_tracker_artifacts_ids[1];

        $this->expectExceptionCode(403);

        $put_resource = json_encode(
            [
                'values' => [
                    [
                        'field_id' => $this->getFieldByFieldLabel($target_artifact_id, 'name'),
                        'value'    => "Yolo"
                    ],
                ],
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::TEST_USER_1_NAME,
            $this->client->put(
                'artifacts/' . $target_artifact_id,
                null,
                $put_resource
            )
        );
        $this->assertEquals(400, $response->getStatusCode());
    }
    /**
     * @param $response
     * @throws Exception
     */
    private function assertArtifact(Response $response): void
    {
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

        foreach ($fields as $field) {
            switch ($field['type']) {
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
                    $this->assertTrue($field['format'] == 'text' || $field['format'] == 'html');
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
                    $this->assertTrue(DateTime::createFromFormat('Y-m-d\TH:i:sT', $field['value']) !== false);
                    break;
                case 'subon':
                    $this->assertTrue(is_string($field['label']));
                    $this->assertTrue(DateTime::createFromFormat('Y-m-d\TH:i:sT', $field['value']) !== false);
                    break;
                default:
                    throw new Exception('You need to update this test for the field: ' . print_r($field, true));
            }
        }
    }
}
