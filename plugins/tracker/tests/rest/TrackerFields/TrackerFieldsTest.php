<?php
/**
 * Copyright (c) Enalean, 2018. All rights reserved
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

namespace Tuleap\Tracker\Tests\REST\TrackerFields;

require_once __DIR__ .'/../TrackerBase.php';

use Guzzle\Http\Client;
use Tuleap\Tracker\Tests\REST\TrackerBase;

class TrackerFieldsTest extends TrackerBase
{
    private const FIELD_STATIC_SELECTBOX_SHOTNAME       = 'staticsb';
    private const FIELD_STATIC_RADIOBUTTON_SHOTNAME     = 'staticrb';
    private const FIELD_STATIC_MULTI_SELECTBOX_SHOTNAME = 'staticmsb';
    private const FIELD_USER_SELECTBOX_SHOTNAME         = 'userssb';
    private const FIELD_FILE                            = 'attachment';

    public function testOPTIONSId()
    {
        $field_id = $this->getStaticSelectboxFieldId();

        $response = $this->getResponse($this->client->options("tracker_fields/$field_id"));
        $this->assertEquals(array('OPTIONS', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testPATCHAddsNewValuesInSelectboxBindToStaticValues()
    {
        $field_id = $this->getStaticSelectboxFieldId();
        $body     = json_encode([
            "new_values" => ['new_value_01', 'new_value_02']
        ]);

        $response = $this->getResponse($this->client->patch("tracker_fields/$field_id", null, $body));

        $this->assertEquals($response->getStatusCode(), 200);

        $tracker_field_json = $response->json();

        $this->assertEquals(count($tracker_field_json['values']), 7);
        $this->assertEquals($tracker_field_json['values'][5]['label'], 'new_value_01');
        $this->assertEquals($tracker_field_json['values'][6]['label'], 'new_value_02');
    }

    public function testPATCHAddsNewValuesInRadiobuttonBindToStaticValues()
    {
        $field_id = $this->getStaticRadiobuttonFieldId();
        $body     = json_encode([
            "new_values" => ['new_value_01', 'new_value_02']
        ]);

        $response = $this->getResponse($this->client->patch("tracker_fields/$field_id", null, $body));

        $this->assertEquals($response->getStatusCode(), 200);

        $tracker_field_json = $response->json();

        $this->assertEquals(count($tracker_field_json['values']), 7);
        $this->assertEquals($tracker_field_json['values'][5]['label'], 'new_value_01');
        $this->assertEquals($tracker_field_json['values'][6]['label'], 'new_value_02');
    }

    public function testPATCHThrowsAnExceptionIfFieldIsNotASimpleList()
    {
        $field_id = $this->getStaticMultiSelectboxFieldId();
        $body     = json_encode([
            "new_values" => ['new_value_01', 'new_value_02']
        ]);

        $response = $this->getResponse($this->client->patch("tracker_fields/$field_id", null, $body));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPATCHThrowsAnExceptionIfFieldIsNotBoundToStaticValues()
    {
        $field_id = $this->getUserSelectboxFieldId();
        $body     = json_encode([
            "new_values" => ['new_value_01', 'new_value_02']
        ]);

        $response = $this->getResponse($this->client->patch("tracker_fields/$field_id", null, $body));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    /**
     * @test
     */
    public function getFileFieldId(): int
    {
        return $this->getAUsedField($this->tracker_fields_tracker_id, self::FIELD_FILE);
    }

    /**
     * @depends getFileFieldId
     */
    public function testPOSTFile(int $field_id): void
    {
        $file_size = 123;
        $data      = str_repeat('A', $file_size);

        $query = [
            'name'       => 'file_creation_' . bin2hex(random_bytes(8)),
            'file_size'  => $file_size,
            'file_type'  => 'text/plain'
        ];

        $response1 = $this->getResponse($this->client->post("tracker_fields/$field_id/files", null, json_encode($query)));
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertNotEmpty($response1->json()['upload_href']);
        $this->assertNotEmpty($response1->json()['download_href']);

        $response2 = $this->getResponse($this->client->post("tracker_fields/$field_id/files", null, json_encode($query)));
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertSame($response1->json()['upload_href'], $response2->json()['upload_href']);
        $this->assertSame($response1->json()['download_href'], $response2->json()['download_href']);

        $query['file_size'] = 456;
        $response3          = $this->getResponse($this->client->post("tracker_fields/$field_id/files", null, json_encode($query)));
        $this->assertEquals(409, $response3->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponse(
            $tus_client->patch(
                $response1->json()['upload_href'],
                [
                    'Tus-Resumable' => '1.0.0',
                    'Content-Type'  => 'application/offset+octet-stream',
                    'Upload-Offset' => '0'
                ],
                $data
            )
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset')->toArray());

        $data_response = $this->getResponse($this->setup_client->get($response1->json()['download_href']));
        $this->assertEquals(200, $data_response->getStatusCode());
        $this->assertEquals(
            $data,
            (string) $data_response->getBody()
        );
    }

    /**
     * @depends getFileFieldId
     */
    public function testFileCreationWithASameNameIsNotRejectedWhenTheUploadHasBeenCanceled($field_id): void
    {
        $query = [
            'name'       => 'file_not_conflict_after_cancel_' . bin2hex(random_bytes(8)),
            'file_size'  => 123,
            'file_type'  => 'text/plain'
        ];

        $response_creation_file = $this->getResponse($this->client->post("tracker_fields/$field_id/files", null, json_encode($query)));
        $this->assertEquals(201, $response_creation_file->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponse(
            $tus_client->delete(
                $response_creation_file->json()['upload_href'],
                ['Tus-Resumable' => '1.0.0',]
            )
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());

        $response_creation_file = $this->getResponse($this->client->post("tracker_fields/$field_id/files", null, json_encode($query)));
        $this->assertEquals(201, $response_creation_file->getStatusCode());
    }

    /**
     * @depends getFileFieldId
     */
    public function testEmptyFileCreation($field_id): void
    {
        $name  = 'empty_file_' . bin2hex(random_bytes(8));
        $query = [
            'name'       => $name,
            'file_size'  => 0,
            'file_type'  => 'text/plain'
        ];

        $response_creation_file = $this->getResponse($this->client->post("tracker_fields/$field_id/files", null, json_encode($query)));
        $this->assertEquals(201, $response_creation_file->getStatusCode());
        $this->assertEmpty($response_creation_file->json()['upload_href']);
    }

    private function getStaticMultiSelectboxFieldId()
    {
        $response     = $this->getResponse($this->client->get('trackers/' . $this->tracker_fields_tracker_id));
        $tracker_json = $response->json();

        return $this->getFieldId($tracker_json["fields"], self::FIELD_STATIC_MULTI_SELECTBOX_SHOTNAME);
    }

    private function getStaticSelectboxFieldId()
    {
        $response     = $this->getResponse($this->client->get('trackers/' . $this->tracker_fields_tracker_id));
        $tracker_json = $response->json();

        return $this->getFieldId($tracker_json["fields"], self::FIELD_STATIC_SELECTBOX_SHOTNAME);
    }

    private function getUserSelectboxFieldId()
    {
        $response     = $this->getResponse($this->client->get('trackers/' . $this->tracker_fields_tracker_id));
        $tracker_json = $response->json();

        return $this->getFieldId($tracker_json["fields"], self::FIELD_USER_SELECTBOX_SHOTNAME);
    }

    private function getStaticRadiobuttonFieldId()
    {
        $response     = $this->getResponse($this->client->get('trackers/' . $this->tracker_fields_tracker_id));
        $tracker_json = $response->json();

        return $this->getFieldId($tracker_json["fields"], self::FIELD_STATIC_RADIOBUTTON_SHOTNAME);
    }

    private function getFieldId(array $tracker_fields_json, $field_shortname)
    {
        foreach ($tracker_fields_json as $tracker_field_json) {
            if ($tracker_field_json["name"] === $field_shortname) {
                return $tracker_field_json["field_id"];
            }
        }

        return null;
    }
}
