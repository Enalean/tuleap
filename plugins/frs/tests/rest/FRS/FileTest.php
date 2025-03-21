<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\FRS\Tests\REST;

use REST_TestDataBuilder;
use RestBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class FileTest extends RestBase
{
    public const PROJECT_NAME = 'frs-test';

    public function testOPTIONSFile(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'frs_files/1'));
        $this->assertEquals(
            ['OPTIONS', 'GET', 'POST', 'DELETE'],
            explode(', ', $response->getHeaderLine('Allow'))
        );
    }

    public function testOPTIONSFileWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'frs_files/1'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(
            ['OPTIONS', 'GET', 'POST', 'DELETE'],
            explode(', ', $response->getHeaderLine('Allow'))
        );
    }

    public function testGETFile(): void
    {
        $file = json_decode($this->getResponse($this->request_factory->createRequest('GET', 'frs_files/1'))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertGETFile($file);
    }

    public function testGETFileWithUserRESTReadOnlyAdmin(): void
    {
        $file = json_decode($this->getResponse(
            $this->request_factory->createRequest('GET', 'frs_files/1'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        )->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertGETFile($file);
    }

    private function assertGETFile(array $file): void
    {
        $this->assertEquals(1, $file['id']);
        $this->assertEquals('BooksAuthors.txt', $file['name']);
        $this->assertEquals('x86_64', $file['arch']);
        $this->assertEquals('text', $file['type']);
        $this->assertEquals(72, $file['file_size']);
        $this->assertEquals('2015-12-03T16:46:00+01:00', $file['date']);
        $this->assertEquals('7865eaef28db1b906eaf1e4fa353796d', $file['computed_md5']);
        $this->assertEquals('/file/download/1', $file['download_url']);
        $this->assertEquals('rest_api_tester_1', $file['owner']['username']);

        $file_data_response = $this->getResponse($this->request_factory->createRequest('GET', $file['download_url']));
        $this->assertEquals(200, $file_data_response->getStatusCode());
        $this->assertStringEqualsFile(
            __DIR__ . '/../_fixtures/frs/data/authors.txt',
            $file_data_response->getBody()->getContents()
        );
    }

    public function testDELETEFileWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'frs_files/2'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDELETEFile(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('DELETE', 'frs_files/2'));
        $this->assertEquals(202, $response->getStatusCode());
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'frs_files/2'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPOSTFileWithUserRESTReadOnlyAdminNotProjectMember(): void
    {
        $file_size = 123;

        $query = [
            'release_id' => 1,
            'name'       => 'file_creation_' . bin2hex(random_bytes(8)),
            'file_size'  => $file_size,
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'frs_files')->withBody($this->stream_factory->createStream(json_encode($query))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPOSTFile(): void
    {
        $file_size = 123;

        $query = [
            'release_id' => 1,
            'name'       => 'file_creation_' . bin2hex(random_bytes(8)),
            'file_size'  => $file_size,
        ];

        $response0 = $this->getResponse($this->request_factory->createRequest('GET', 'frs_release/1/files'));
        $nb_files  = count(json_decode($response0->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['files']);

        $response1      = $this->getResponse($this->request_factory->createRequest('POST', 'frs_files')->withBody($this->stream_factory->createStream(json_encode($query))));
        $response1_json = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertNotEmpty($response1_json['upload_href']);

        $response2 = $this->getResponse($this->request_factory->createRequest('POST', 'frs_files')->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(201, $response1->getStatusCode());
        self::assertSame($response1_json['upload_href'], json_decode($response2->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['upload_href']);

        $query['file_size'] = 456;
        $response3          = $this->getResponse($this->request_factory->createRequest('POST', 'frs_files')->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(409, $response3->getStatusCode());

        $tus_response_upload = $this->getResponse(
            $this->request_factory->createRequest(
                'PATCH',
                $response1_json['upload_href']
            )
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream(str_repeat('A', $file_size)))
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $response_files = $this->getResponse($this->request_factory->createRequest('GET', 'frs_release/1/files'));
        $this->assertCount($nb_files + 1, json_decode($response_files->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['files']);
    }

    public function testFileCreationWithASameNameIsNotRejectedWhenTheUploadHasBeenCanceled(): void
    {
        $query = [
            'release_id' => 1,
            'name'       => 'file_not_conflict_after_cancel_' . bin2hex(random_bytes(8)),
            'file_size'  => 123,
        ];

        $response_creation_file = $this->getResponse($this->request_factory->createRequest('POST', 'frs_files')->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(201, $response_creation_file->getStatusCode());

        $tus_response_upload = $this->getResponse(
            $this->request_factory->createRequest('DELETE', json_decode($response_creation_file->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());

        $response_creation_file = $this->getResponse($this->request_factory->createRequest('POST', 'frs_files')->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(201, $response_creation_file->getStatusCode());
    }

    public function testEmptyFileCreation(): void
    {
        $name  = 'empty_file_' . bin2hex(random_bytes(8));
        $query = [
            'release_id' => 1,
            'name'       => $name,
            'file_size'  => 0,
        ];

        $response_creation_file = $this->getResponse($this->request_factory->createRequest('POST', 'frs_files')->withBody($this->stream_factory->createStream(json_encode($query))));
        $this->assertEquals(201, $response_creation_file->getStatusCode());
        $this->assertEmpty(json_decode($response_creation_file->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['upload_href']);

        $response_files      = $this->getResponse($this->request_factory->createRequest('GET', 'frs_release/1/files'));
        $is_empty_file_found = false;
        foreach (json_decode($response_files->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['files'] as $file) {
            if ($file['name'] === $name) {
                $is_empty_file_found = true;
                break;
            }
        }
        $this->assertTrue($is_empty_file_found, 'Empty file should be created');
    }
}
