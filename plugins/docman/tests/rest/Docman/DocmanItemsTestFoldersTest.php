<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

class DocmanItemsTestFoldersTest extends DocmanBase
{
    public function testGetRootId()
    {
        $project_response = $this->getResponse($this->client->get('projects/' . $this->project_id));

        $this->assertSame(200, $project_response->getStatusCode());

        $json_projects = $project_response->json();
        return $json_projects['additional_informations']['docman']['root_item']['id'];
    }

    /**
     * @depends             testGetRootId
     */
    public function testPostFileIsRejectedIfDocumentAlreadyExists($root_id)
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'Custom title',
                'description' => 'A description'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileDocument(int $root_id): void
    {
        $file_size = 123;
        $query     = json_encode(
            [
                'title'           => 'File1',
                'file_properties' => ['file_name' => 'file1', 'file_size' => $file_size]
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertNotEmpty($response1->json()['file_properties']['upload_href']);

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertSame(
            $response1->json()['file_properties']['upload_href'],
            $response2->json()['file_properties']['upload_href']
        );

        $general_use_http_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $general_use_http_client->setSslVerification(false, false, false);
        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $general_use_http_client->patch(
                $response1->json()['file_properties']['upload_href'],
                [
                    'Tus-Resumable' => '1.0.0',
                    'Content-Type'  => 'application/offset+octet-stream',
                    'Upload-Offset' => '0'
                ],
                $file_content
            )
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset')->toArray());

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);

        $file_content_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $general_use_http_client->get($file_item_response->json()['file_properties']['download_href'])
        );
        $this->assertEquals(200, $file_content_response->getStatusCode());
        $this->assertEquals($file_content, $file_content_response->getBody());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyFileDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'File2',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 0]
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertEmpty($response1->json()['file_properties']['upload_href']);

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileDocumentIsRejectedIfFileIsTooBig(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'           => 'File1',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 999999999999]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testDocumentCreationIsRejectedIfAFileIsBeingUploadedForTheSameNameByADifferentUser(int $root_id): void
    {
        $document_name = 'document_conflict_' . bin2hex(random_bytes(8));

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . $root_id . '/files',
                null,
                json_encode(
                    [
                        'title'           => $document_name,
                        'file_properties' => ['file_name' => 'file', 'file_size' => 123]
                    ]
                )
            )
        );
        $this->assertEquals(201, $response->getStatusCode());

        $response2 = $this->getResponse(
            $this->client->post(
                'docman_folders/' . $root_id . '/empties',
                null,
                json_encode(
                    [
                        'title'     => $document_name,
                        'parent_id' => $root_id,
                    ]
                )
            )
        );
        $this->assertEquals(409, $response2->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testDocumentCreationWithASameNameIsNotRejectedWhenTheUploadHasBeenCanceled(int $root_id): void
    {
        $document_name = 'document_not_conflict_after_cancel_' . bin2hex(random_bytes(8));

        $response_creation_file = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . $root_id . '/files',
                null,
                json_encode(
                    [
                        'title'           => $document_name,
                        'file_properties' => ['file_name' => 'file', 'file_size' => 123]
                    ]
                )
            )
        );
        $this->assertEquals(201, $response_creation_file->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_cancel = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $tus_client->delete(
                $response_creation_file->json()['file_properties']['upload_href'],
                ['Tus-Resumable' => '1.0.0']
            )
        );
        $this->assertEquals(204, $tus_response_cancel->getStatusCode());

        $response_creation_empty = $this->getResponse(
            $this->client->post(
                'docman_folders/' . $root_id . '/files',
                null,
                json_encode(
                    [
                        'title'           => $document_name,
                        'file_properties' => ['file_name' => 'file', 'file_size' => 123]
                    ]
                )
            )
        );
        $this->assertEquals(201, $response_creation_empty->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFolderItem(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'My Folder',
                'description' => 'A Folder description',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/folders", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNull($response->json()['file_properties']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFolderFailIfFolderNameAlreadyExists(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'My Folder',
                'description' => 'A Folder description',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/folders", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyDocument($root_id)
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'Custom title',
                'description' => 'A description',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/empties', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostDocumentIsRejectedIfDocumentAlreadyExists($root_id)
    {
        $stored_items = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        )->json();
        $folder_1     = $this->findItemByTitle($stored_items, 'folder 1');

        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'empty',
                'description' => 'A description',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_1['id'] . '/empties', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostReturns403WhenPermissionDenied(int $root_id): void
    {
        $stored_items = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        )->json();
        $folder_3     = $this->findItemByTitle($stored_items, 'Folder A File');

        $query = json_encode(
            [
                'title'       => 'A title',
                'description' => 'A description',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_3['id'] . '/empties', null, $query)
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiDocument(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query = json_encode(
            [
                'title'           => 'How to become a Tuleap',
                'description'     => 'A description',
                'wiki_properties' => $wiki_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id ."/wikis", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }


    /**
     * @depends testGetRootId
     */
    public function testPostEmbeddedDocument(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $embedded_properties = ['content' => 'step1 : Avoid to sort items in the docman'];
        $query = json_encode(
            [
                'title'           => 'How to become a Tuleap (embedded version)',
                'description'     => 'A description',
                'embedded_properties' => $embedded_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/'.$root_id.'/embedded_files', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }


    /**
     * @depends testGetRootId
     */
    public function testPostLinkDocument(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'           => 'To the future',
                'description'     => 'A description',
                'link_properties' => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id ."/links", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFolderWithStatusWhenStatusIsNotAllowedForProject(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $query = json_encode(
            [
                'title'       => 'To the future',
                'description' => 'A description',
                'status'      => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/folders", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }


    /**
     * @depends testGetRootId
     */
    public function testPostEmptyWithStatusWhenStatusIsNotAllowedForProject(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $query = json_encode(
            [
                'title'       => 'EMPTY FAIL',
                'description' => 'A description',
                'status'      => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/empties", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyWithObsolescenceDateWhenObsolescenceDateIsNotAllowedForProject(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $query = json_encode(
            [
                'title'             => 'EMPTY FAIL 2',
                'description'       => 'A description',
                'obsolescence_date' => '2019-02-25'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/empties", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmbeddedWithObsolescenceDateWhenObsolescenceDateIsNotAllowedForProject(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $embedded_properties = ['content' => 'step2 : Stop using approval table'];
        $query               = json_encode(
            [
                'title'               => 'How to become a Tuleap 2  (embedded version)',
                'description'         => 'A description',
                'embedded_properties' => $embedded_properties,
                'obsolescence_date'   => '2019-02-25'
            ]
        );
        $response            = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/embedded_files", $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmbeddedWithStatusWhenStatusIsNotAllowedForProject(int $root_id): void
    {
        $headers             = ['Content-Type' => 'application/json'];
        $embedded_properties = ['content' => 'step3 : bruh'];
        $query               = json_encode(
            [
                'title'               => 'How to become a Tuleap 3 (embedded version)',
                'description'         => 'A description',
                'embedded_properties' => $embedded_properties,
                'status'              => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/embedded_files", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostLinkWithStatusWhenStatusIsNotAllowedForProject(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'           => 'To the future 2',
                'description'     => 'A description',
                'link_properties' => $link_properties,
                'status'          => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/links", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostLinkWithObsolescenceDateWhenObsolescenceDateIsNotAllowedForProject(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'             => 'To the future 3, the return',
                'description'       => 'A description',
                'link_properties'   => $link_properties,
                'obsolescence_date' => '3000-08-08'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/links", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiWithWithStatusWhenStatusIsNotAllowedForProject(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'           => 'How to become a Tuleap wiki version',
                'description'     => 'A description',
                'wiki_properties' => $wiki_properties,
                'status'          => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/wikis", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiWithObsolescenceDateWhenObsolescenceDateIsNotAllowedForProject(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'             => 'How to become a Tuleap wiki version 2',
                'description'       => 'A description',
                'wiki_properties'   => $wiki_properties,
                'obsolescence_date' => '3000-08-08'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/wikis", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileWithStatusWhenStatusIsNotAllowedForProject(int $root_id): void
    {
        $file_size = 123;
        $headers   = ['Content-Type' => 'application/json'];
        $query     = json_encode(
            [
                'title'           => 'File5',
                'file_properties' => ['file_name' => 'file1', 'file_size' => $file_size],
                'status'          => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/files", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileWithObsolescenceDateWhenObsolescenceDateIsNotAllowedForProject(int $root_id): void
    {
        $file_size = 123;
        $headers   = ['Content-Type' => 'application/json'];
        $query     = json_encode(
            [
                'title'             => 'My File',
                'file_properties'   => ['file_name' => 'file1', 'file_size' => $file_size],
                'obsolescence_date' => '3019-05-20'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/files", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testGetTrashFolderContent(int $root_id): array
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder = $response->json();

        $trash_folder    = $this->findItemByTitle($folder, "Trash");
        $trash_folder_id = $trash_folder['id'];

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $trash_folder_id . '/docman_items')
        );

        $items_to_delete = $response->json();

        $this->assertGreaterThan(0, count($items_to_delete));

        return $items_to_delete;
    }

    /**
     * @depends testGetRootId
     */
    public function testItThrowsAnErrorWhenWeTryToDeleteTheRootFolder(int $root_id) : void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_folders/' . $root_id)
        );

        $this->assertEquals(400, $response->getStatusCode());

        $this->checkFolderHasNotBeenDeleted($root_id);
    }

    /**
     * @depends testGetTrashFolderContent
     */
    public function testItThrowsAnErrorWhenUserHasNotPermissionToDeleteTheFolder(array $items): void
    {
        $folder_to_delete    = $this->findItemByTitle($items, 'old folder L');
        $folder_to_delete_id = $folder_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_folders/' . $folder_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkFolderHasNotBeenDeleted($folder_to_delete_id);
    }

    /**
     * @depends testGetTrashFolderContent
     */
    public function testItShouldThrowAnErrorWhenTheFolderContainsItemsUserIsNotAllowedToDelete(array $items): void
    {
        $folder_to_delete    = $this->findItemByTitle($items, 'folder with content you cannot delete');
        $folder_to_delete_id = $folder_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_folders/' . $folder_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkFolderHasNotBeenDeleted($folder_to_delete_id);
    }

    /**
     * @depends testGetTrashFolderContent
     */
    public function testItShouldDeleteWhenFolderIsLockedAndUserIsAdmin(array $items): void
    {
        $folder_to_delete    = $this->findItemByTitle($items, 'old folder L');
        $folder_to_delete_id = $folder_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_folders/' . $folder_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkFolderHasBeenDeleted($folder_to_delete_id);
    }

    /**
     * @depends testGetTrashFolderContent
     */
    public function testItDeletesAFolder(array $items): void
    {
        $folder_to_delete    = $this->findItemByTitle($items, 'another old folder');
        $folder_to_delete_id = $folder_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_folders/' . $folder_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkFolderHasBeenDeleted($folder_to_delete_id);
    }

    /**
     * Find first item in given array of items which has given title.
     * @return array|null Found item. null otherwise.
     */
    private function findItemByTitle(array $items, $title)
    {
        $index = array_search($title, array_column($items, 'title'));
        if ($index === false) {
            return null;
        }
        return $items[$index];
    }

    private function checkFolderHasNotBeenDeleted(int $folder_to_delete_id) : void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    private function checkFolderHasBeenDeleted(int $folder_to_delete_id) : void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_to_delete_id)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }
}
