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

use Guzzle\Http\Client;
use REST_TestDataBuilder;
use RestBase;

class FileTest extends RestBase
{
    public const PROJECT_NAME = 'frs-test';

    public function testOPTIONSFile(): void
    {
        $response = $this->getResponse($this->client->options('frs_files/1'));
        $this->assertEquals(
            ['OPTIONS', 'GET', 'POST', 'DELETE'],
            $response->getHeader('Allow')->normalize()->toArray()
        );
    }

    public function testOPTIONSFileWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('frs_files/1'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(
            ['OPTIONS', 'GET', 'POST', 'DELETE'],
            $response->getHeader('Allow')->normalize()->toArray()
        );
    }

    public function testGETFile(): void
    {
        $file = $this->getResponse($this->client->get('frs_files/1'))->json();

        $this->assertGETFile($file);
    }

    public function testGETFileWithUserRESTReadOnlyAdmin(): void
    {
        $file = $this->getResponse(
            $this->client->get('frs_files/1'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        )->json();

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

        $file_data_response = $this->getResponse($this->setup_client->get($file['download_url']));
        $this->assertEquals(200, $file_data_response->getStatusCode());
        $this->assertStringEqualsFile(
            __DIR__ . '/../_fixtures/frs/data/authors.txt',
            (string) $file_data_response->getBody()
        );
    }

    public function testDELETEFileWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->delete('frs_files/2'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testDELETEFile(): void
    {
        $response = $this->getResponse($this->client->delete('frs_files/2'));
        $this->assertEquals(202, $response->getStatusCode());
        $response = $this->getResponse($this->client->get('frs_files/2'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPOSTFileWithUserRESTReadOnlyAdminNotProjectMember(): void
    {
        $file_size = 123;

        $query = [
            'release_id' => 1,
            'name'       => 'file_creation_' . bin2hex(random_bytes(8)),
            'file_size'  => $file_size
        ];

        $response = $this->getResponse(
            $this->client->post('frs_files', null, json_encode($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testPOSTFile(): void
    {
        $file_size = 123;

        $query = [
            'release_id' => 1,
            'name'       => 'file_creation_' . bin2hex(random_bytes(8)),
            'file_size'  => $file_size
        ];

        $response0 = $this->getResponse($this->client->get('frs_release/1/files'));
        $nb_files  = count($response0->json()['files']);

        $response1 = $this->getResponse($this->client->post('frs_files', null, json_encode($query)));
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertNotEmpty($response1->json()['upload_href']);

        $response2 = $this->getResponse($this->client->post('frs_files', null, json_encode($query)));
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertSame($response1->json()['upload_href'], $response2->json()['upload_href']);

        $query['file_size'] = 456;
        $response3          = $this->getResponse($this->client->post('frs_files', null, json_encode($query)));
        $this->assertEquals(409, $response3->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setCurlMulti($this->client->getCurlMulti());
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponse(
            $tus_client->patch(
                $response1->json()['upload_href'],
                [
                    'Tus-Resumable' => '1.0.0',
                    'Content-Type'  => 'application/offset+octet-stream',
                    'Upload-Offset' => '0'
                ],
                str_repeat('A', $file_size)
            )
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset')->toArray());

        $response_files = $this->getResponse($this->client->get('frs_release/1/files'));
        $this->assertCount($nb_files + 1, $response_files->json()['files']);
    }

    public function testFileCreationWithASameNameIsNotRejectedWhenTheUploadHasBeenCanceled(): void
    {
        $query = [
            'release_id' => 1,
            'name'       => 'file_not_conflict_after_cancel_' . bin2hex(random_bytes(8)),
            'file_size'  => 123
        ];

        $response_creation_file = $this->getResponse($this->client->post('frs_files', null, json_encode($query)));
        $this->assertEquals(201, $response_creation_file->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setCurlMulti($this->client->getCurlMulti());
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponse(
            $tus_client->delete(
                $response_creation_file->json()['upload_href'],
                ['Tus-Resumable' => '1.0.0',]
            )
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());

        $response_creation_file = $this->getResponse($this->client->post('frs_files', null, json_encode($query)));
        $this->assertEquals(201, $response_creation_file->getStatusCode());
    }

    public function testEmptyFileCreation(): void
    {
        $name  = 'empty_file_' . bin2hex(random_bytes(8));
        $query = [
            'release_id' => 1,
            'name'       => $name,
            'file_size'  => 0
        ];

        $response_creation_file = $this->getResponse($this->client->post('frs_files', null, json_encode($query)));
        $this->assertEquals(201, $response_creation_file->getStatusCode());
        $this->assertEmpty($response_creation_file->json()['upload_href']);

        $response_files = $this->getResponse($this->client->get('frs_release/1/files'));
        $is_empty_file_found = false;
        foreach ($response_files->json()['files'] as $file) {
            if ($file['name'] === $name) {
                $is_empty_file_found = true;
                break;
            }
        }
        $this->assertTrue($is_empty_file_found, 'Empty file should be created');
    }
}
