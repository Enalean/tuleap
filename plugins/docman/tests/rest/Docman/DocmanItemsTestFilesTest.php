<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\rest\v1;

use Guzzle\Http\Client;
use REST_TestDataBuilder;
use Tuleap\Docman\rest\DocmanBase;
use Tuleap\Docman\rest\DocmanDataBuilder;

require_once __DIR__ . '/../bootstrap.php';

class DocmanItemsTestFilesTest extends DocmanBase
{
    public function testGetRootId(): int
    {
        $project_response = $this->getResponse($this->client->get('projects/' . $this->project_id));

        $this->assertSame(200, $project_response->getStatusCode());

        $json_projects = $project_response->json();
        return $json_projects['additional_informations']['docman']['root_item']['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForRegularUser($root_id): array
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();

        $folder_id = $folder[0]['id'];
        $this->assertEquals($folder[0]['title'], "folder 1");

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $folder_id . '/docman_items')
        );
        $items    = $response->json();

        $this->assertEquals(count($items), 8);

        return $items;
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchOnDocumentWithApprovalTableThrowException(int $root_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();

        $put_resource = json_encode(
            [
                'version_title'   => 'My version title',
                'changelog'       => 'I have changed',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 10]
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_files/' . $folder[0]["id"],
                null,
                $put_resource
            )
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchOnDocumentLockedByAnOtherUserThrowException(int $root_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();

        $put_resource = json_encode(
            [
                'version_title'   => 'My version title',
                'changelog'       => 'I have changed',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 10]
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_files/' . $folder[1]["id"],
                null,
                $put_resource
            )
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForRegularUser
     */
    public function testPatchFileDocumentIsRejectedIfFileIsTooBig(array $items): void
    {
        $file         = $this->findItemByTitle($items, 'file A');
        $put_resource = json_encode(
            [
                'version_title'   => 'My version title',
                'changelog'       => 'I have changed',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 999999999999]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForRegularUser
     */
    public function testPatchOnEmptyItemThrowAnException(array $items): void
    {
        $empty = $this->findItemByTitle($items, 'item A');

        $put_resource = json_encode(
            [
                'version_title'   => 'My version title',
                'changelog'       => 'I have changed',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 0]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $empty['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForRegularUser
     */
    public function testPACTHIsRejectedIfAFileIsBeingUploadedForTheSameNameByADifferentUser(array $items): void
    {
        $file         = $this->findItemByTitle($items, 'file A');
        $put_resource = json_encode(
            [
                'version_title'   => 'My version title',
                'changelog'       => 'I have changed',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 10]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("/uploads/docman/version/1", $response->json()['upload_href']);


        $put_resource = json_encode(
            [
                'version_title'   => 'My version title',
                'changelog'       => 'I have changed',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 10]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(409, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForRegularUser
     */
    public function testPatchFileDocumentReturnsFileRepresentation(array $items) : void
    {
        $file         = $this->findItemByTitle($items, 'file B');
        $put_resource = json_encode(
            [
                'version_title'   => 'My version title',
                'changelog'       => 'I have changed',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 10]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("/uploads/docman/version/2", $response->json()['upload_href']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchFileDocument(int $root_id) : void
    {
        $query     = json_encode([
                                     'title'           => 'My new file',
                                     'parent_id'       => $root_id,
                                     'type'            => 'file',
                                     'file_properties' => ['file_name' => 'file1', 'file_size' => 0]
                                 ]);

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertEmpty($response1->json()['file_properties']['upload_href']);

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);

        $file_id = $response1->json()['id'];

        $file_size = 123;
        $put_resource = json_encode(
            [
                'version_title'   => 'My version title',
                'changelog'       => 'I have changed',
                'file_properties' => ['file_name' => 'My new file', 'file_size' => $file_size]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->json()['upload_href']);

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(
            $response->json()['upload_href'],
            $response2->json()['upload_href']
        );

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $tus_client->patch(
                $response->json()['upload_href'],
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

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $file_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('file', $response->json()['type']);
    }

    /**
     * Find first item in given array of items which has given title.
     * @return array|null Found item. null otherwise.
     */
    private function findItemByTitle(array $items, string $title) : ?array
    {
        $index = array_search($title, array_column($items, 'title'));
        if ($index === false) {
            return null;
        }
        return $items[$index];
    }
}
