<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All rights reserved
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

require_once __DIR__ . '/../TrackerBase.php';

use Tuleap\Tracker\Tests\REST\TrackerBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
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

        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', "tracker_fields/$field_id"));
        $this->assertEquals(['OPTIONS', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testPATCHAddsNewValuesInSelectboxBindToStaticValues()
    {
        $field_id = $this->getStaticSelectboxFieldId();
        $body     = json_encode([
            'new_values' => ['new_value_01', 'new_value_02'],
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', "tracker_fields/$field_id")->withBody($this->stream_factory->createStream($body)));

        $this->assertEquals($response->getStatusCode(), 200);

        $tracker_field_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(count($tracker_field_json['values']), 7);
        $this->assertEquals($tracker_field_json['values'][5]['label'], 'new_value_01');
        $this->assertEquals($tracker_field_json['values'][6]['label'], 'new_value_02');
    }

    public function testPATCHAddsNewValuesInRadiobuttonBindToStaticValues()
    {
        $field_id = $this->getStaticRadiobuttonFieldId();
        $body     = json_encode([
            'new_values' => ['new_value_01', 'new_value_02'],
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', "tracker_fields/$field_id")->withBody($this->stream_factory->createStream($body)));

        $this->assertEquals($response->getStatusCode(), 200);

        $tracker_field_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(count($tracker_field_json['values']), 7);
        $this->assertEquals($tracker_field_json['values'][5]['label'], 'new_value_01');
        $this->assertEquals($tracker_field_json['values'][6]['label'], 'new_value_02');
    }

    public function testPATCHThrowsAnExceptionIfFieldIsNotASimpleList()
    {
        $field_id = $this->getStaticMultiSelectboxFieldId();
        $body     = json_encode([
            'new_values' => ['new_value_01', 'new_value_02'],
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', "tracker_fields/$field_id")->withBody($this->stream_factory->createStream($body)));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    public function testPATCHThrowsAnExceptionIfFieldIsNotBoundToStaticValues()
    {
        $field_id = $this->getUserSelectboxFieldId();
        $body     = json_encode([
            'new_values' => ['new_value_01', 'new_value_02'],
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', "tracker_fields/$field_id")->withBody($this->stream_factory->createStream($body)));

        $this->assertEquals($response->getStatusCode(), 400);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function getFileFieldId(): int
    {
        $field    = $this->getAUsedField($this->tracker_fields_tracker_id, self::FIELD_FILE);
        $field_id = $field['field_id'];

        $this->assertEquals("tracker_fields/$field_id/files", $field['file_creation_uri']);
        $this->assertEquals(67108864, $field['max_size_upload']);

        return $field_id;
    }

    #[\PHPUnit\Framework\Attributes\Depends('getFileFieldId')]
    public function testPOSTFile(int $field_id): int
    {
        $file_size = 123;
        $data      = str_repeat('A', $file_size);

        $query = [
            'name'       => 'file_creation_' . bin2hex(random_bytes(8)),
            'file_size'  => $file_size,
            'file_type'  => 'text/plain',
        ];

        $response1 = $this->getResponse($this->request_factory->createRequest('POST', "tracker_fields/$field_id/files")->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(200, $response1->getStatusCode());
        $response1_json = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($response1_json['upload_href']);
        $this->assertNotEmpty($response1_json['download_href']);

        $response2 = $this->getResponse($this->request_factory->createRequest('POST', "tracker_fields/$field_id/files")->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(200, $response2->getStatusCode());
        $response2_json = json_decode($response2->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($response1_json['upload_href'], $response2_json['upload_href']);
        self::assertSame($response1_json['download_href'], $response2_json['download_href']);

        $query['file_size'] = 456;
        $response3          = $this->getResponse($this->request_factory->createRequest('POST', "tracker_fields/$field_id/files")->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(200, $response3->getStatusCode());

        $tus_response_upload = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $response1_json['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($data))
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $data_response = $this->getResponse($this->request_factory->createRequest('GET', $response1_json['download_href']));
        $this->assertEquals(200, $data_response->getStatusCode());
        $this->assertEquals(
            $data,
            $data_response->getBody()->getContents()
        );

        return $response1_json['id'];
    }

    #[\PHPUnit\Framework\Attributes\Depends('getFileFieldId')]
    #[\PHPUnit\Framework\Attributes\Depends('testPOSTFile')]
    public function testUploadedFileCanBeAttachedToTheArtifact($field_id, $file_id): void
    {
        $payload = [
            'tracker' => ['id' => $this->tracker_fields_tracker_id],
            'values'  => [
                [
                    'field_id' => $field_id,
                    'value'    => [$file_id],
                ],
            ],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream(json_encode($payload)))
        );
        $this->assertEquals(201, $response->getStatusCode());
        $artifact_id = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . $artifact_id)
        );
        $this->assertEquals(200, $response->getStatusCode());
        foreach (json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['values'] as $field) {
            if ($field['field_id'] === $field_id) {
                $this->assertCount(1, $field['file_descriptions']);
                $this->assertEquals($file_id, $field['file_descriptions'][0]['id']);
                return;
            }
        }
        $this->fail('File not attached to the artifact');
    }

    #[\PHPUnit\Framework\Attributes\Depends('getFileFieldId')]
    public function testFileCreationWithASameNameIsNotRejectedWhenTheUploadHasBeenCanceled($field_id): void
    {
        $query = [
            'name'       => 'file_not_conflict_after_cancel_' . bin2hex(random_bytes(8)),
            'file_size'  => 123,
            'file_type'  => 'text/plain',
        ];

        $response_creation_file = $this->getResponse($this->request_factory->createRequest('POST', "tracker_fields/$field_id/files")->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(200, $response_creation_file->getStatusCode());

        $tus_response_upload = $this->getResponse(
            $this->request_factory->createRequest(
                'DELETE',
                json_decode($response_creation_file->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['upload_href']
            )->withHeader('Tus-Resumable', '1.0.0')
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());

        $response_creation_file = $this->getResponse($this->request_factory->createRequest('POST', "tracker_fields/$field_id/files")->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(200, $response_creation_file->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('getFileFieldId')]
    public function testEmptyFileCreation($field_id): void
    {
        $name  = 'empty_file_' . bin2hex(random_bytes(8));
        $query = [
            'name'       => $name,
            'file_size'  => 0,
            'file_type'  => 'text/plain',
        ];

        $response_creation_file = $this->getResponse($this->request_factory->createRequest('POST', "tracker_fields/$field_id/files")->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(200, $response_creation_file->getStatusCode());
        $this->assertEmpty(json_decode($response_creation_file->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['upload_href']);
    }

    private function getStaticMultiSelectboxFieldId()
    {
        $tracker_json = $this->tracker_representations[$this->tracker_fields_tracker_id];

        return $this->getFieldId($tracker_json['fields'], self::FIELD_STATIC_MULTI_SELECTBOX_SHOTNAME);
    }

    private function getStaticSelectboxFieldId()
    {
        $tracker_json = $this->tracker_representations[$this->tracker_fields_tracker_id];

        return $this->getFieldId($tracker_json['fields'], self::FIELD_STATIC_SELECTBOX_SHOTNAME);
    }

    private function getUserSelectboxFieldId()
    {
        $tracker_json = $this->tracker_representations[$this->tracker_fields_tracker_id];

        return $this->getFieldId($tracker_json['fields'], self::FIELD_USER_SELECTBOX_SHOTNAME);
    }

    private function getStaticRadiobuttonFieldId()
    {
        $tracker_json = $this->tracker_representations[$this->tracker_fields_tracker_id];

        return $this->getFieldId($tracker_json['fields'], self::FIELD_STATIC_RADIOBUTTON_SHOTNAME);
    }

    private function getFieldId(array $tracker_fields_json, $field_shortname)
    {
        foreach ($tracker_fields_json as $tracker_field_json) {
            if ($tracker_field_json['name'] === $field_shortname) {
                return $tracker_field_json['field_id'];
            }
        }

        return null;
    }
}
