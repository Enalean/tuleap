<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Tests\REST\FileFieldArtifactChangeset;

use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class FileFieldArtifactChangesetTest extends TrackerBase
{
    public function testGetLastChangesetWhenBeforeLastChangesetUpdatesFileField(): void
    {
        $file_id = $this->uploadFileOnServer();
        $this->attachUploadedFileToArtifact($file_id);

        $title_field_id = $this->getAUsedFieldId($this->tracker_file_and_title_fields_tracker_id, 'title');
        $payload        = [
            'tracker' => ['id' => $this->tracker_file_and_title_fields_tracker_id],
            'values'  => [
                [
                    'field_id' => $title_field_id,
                    'value'    => 'New title',
                ],
            ],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artifacts/' . urlencode((string) $this->tracker_file_and_title_fields_artifact_id))->withBody($this->stream_factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR)))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $changeset_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $this->tracker_file_and_title_fields_artifact_id) . '/changesets?limit=10&order=desc')
        );
        $this->assertEquals(200, $changeset_response->getStatusCode());
        $changeset = json_decode($changeset_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($changeset[0]['values'] as $field) {
            if ($field['field_id'] === $title_field_id) {
                $this->assertEquals('New title', $field['value']);
                return;
            }
        }
        $this->fail("No field with id $title_field_id");
    }

    private function uploadFileOnServer(): int
    {
        $file_field_id = $this->getAUsedFieldId($this->tracker_file_and_title_fields_tracker_id, 'attachments_1');
        $file_size     = 123;
        $data          = str_repeat('A', $file_size);

        $query = [
            'name'       => 'file_creation_' . bin2hex(random_bytes(8)),
            'file_size'  => $file_size,
            'file_type'  => 'text/plain',
        ];

        $response1 = $this->getResponse($this->request_factory->createRequest('POST', "tracker_fields/$file_field_id/files")->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(200, $response1->getStatusCode());
        $response1_json = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($response1_json['upload_href']);
        $this->assertNotEmpty($response1_json['download_href']);

        $tus_response_upload = $this->getResponse(
            $this->request_factory->createRequest('PATCH', $response1_json['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($data))
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        return $response1_json['id'];
    }

    private function attachUploadedFileToArtifact(int $file_id): void
    {
        $file_field_id = $this->getAUsedFieldId($this->tracker_file_and_title_fields_tracker_id, 'attachments_1');

        $payload = [
            'tracker' => ['id' => $this->tracker_file_and_title_fields_tracker_id],
            'values'  => [
                [
                    'field_id' => $file_field_id,
                    'value'    => [$file_id],
                ],
            ],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artifacts/' . urlencode((string) $this->tracker_file_and_title_fields_artifact_id))->withBody($this->stream_factory->createStream(json_encode($payload)))
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
