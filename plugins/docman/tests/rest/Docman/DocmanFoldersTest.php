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

namespace Tuleap\Docman\Test\rest\Docman;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Guzzle\Http\Client;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;

class DocmanFoldersTest extends DocmanTestExecutionHelper
{
    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForAdminUser(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items_file    = $this->loadFolderContent($root_id, 'Folder');
        $folder_files  = $this->findItemByTitle($root_folder, 'Folder');
        $items_file_id = $folder_files['id'];
        $get           = $this->loadFolderContent($items_file_id, 'GET FO');
        $delete        = $this->loadFolderContent($items_file_id, 'DELETE Folder');

        return array_merge(
            $root_folder,
            $folder_files,
            $items_file,
            $get,
            $delete
        );
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostFileIsRejectedIfDocumentAlreadyExists(array $items): void
    {
        $folder    = $this->findItemByTitle($items, 'GET FO');
        $folder_id = $folder['id'];

        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'           => 'GET F',
                'description'     => 'A description',
                'file_properties' => ['file_name' => 'NEW F', 'file_size' => 0]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_id . '/files', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("exists", $response->json()["error"]['message']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileDocument(int $root_id) : int
    {
        $file_size = 123;
        $query     = json_encode(
            [
                'title'           => 'NEW F',
                'file_properties' => ['file_name' => 'NEW F', 'file_size' => $file_size]
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

        return $response1->json()['id'];
    }

    /**
     * @depends testGetRootId
     * @depends testPostFileDocument
     */
    public function testPostCopyFileDocument(int $root_id, int $file_document_id) : void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . urlencode((string) $root_id) . '/files',
                ['Content-Type' => 'application/json'],
                json_encode(['copy' => ['item_id' => $file_document_id]])
            )
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_files/' . urlencode((string) $response->json()['id']))
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyFileDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'NEW EMPTY F',
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
                'title'           => 'NEW BIG F',
                'file_properties' => ['file_name' => 'NEW BIG F', 'file_size' => 999999999999]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("size", $response->json()["error"]['message']);
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
    public function testPostFolderItem(int $root_id): int
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'NEW FO',
                'description' => 'A Folder description',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/folders", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNull($response->json()['file_properties']);

        return $response->json()['id'];
    }

    /**
     * @depends testGetRootId
     * @depends testPostFolderItem
     */
    public function testPostCopyFolderItem(int $root_id, int $folder_id) : void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . urlencode((string) $root_id) . '/folders',
                ['Content-Type' => 'application/json'],
                json_encode(['copy' => ['item_id' => $folder_id]])
            )
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_folders/' . urlencode((string) $response->json()['id']))
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFolderFailIfFolderNameAlreadyExists(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'Folder',
                'description' => 'A Folder description',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/folders", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("exists", $response->json()["error"]['message']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyDocument(int $root_id): int
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'NEW E',
                'description' => 'A description',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/empties', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        return $response->json()['id'];
    }

    /**
     * @depends testGetRootId
     * @depends testPostEmptyDocument
     */
    public function testPostCopyEmptyDocument(int $root_id, int $empty_document_id) : void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . urlencode((string) $root_id) . '/empties',
                ['Content-Type' => 'application/json'],
                json_encode(['copy' => ['item_id' => $empty_document_id]])
            )
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_empty_documents/' . urlencode((string) $response->json()['id']))
        );
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostReturns403WhenPermissionDenied(array $items): void
    {
        $read_only_folder = $this->findItemByTitle($items, 'GET FO RO');

        $query = json_encode(
            [
                'title'       => 'A title',
                'description' => 'A description',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $read_only_folder['id'] . '/empties', null, $query)
        );
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiDocument(int $root_id) : int
    {
        $headers = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query = json_encode(
            [
                'title'           => 'NEW W',
                'description'     => 'A description',
                'wiki_properties' => $wiki_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id ."/wikis", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        return $response->json()['id'];
    }

    /**
     * @depends testGetRootId
     * @depends testPostWikiDocument
     */
    public function testPostCopyWikiDocument(int $root_id, int $wiki_document_id) : void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . urlencode((string) $root_id) . '/wikis',
                ['Content-Type' => 'application/json'],
                json_encode(['copy' => ['item_id' => $wiki_document_id]])
            )
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_wikis/' . urlencode((string) $response->json()['id']))
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmbeddedDocument(int $root_id): int
    {
        $headers = ['Content-Type' => 'application/json'];
        $embedded_properties = ['content' => 'step1 : Avoid to sort items in the docman'];
        $query = json_encode(
            [
                'title'               => 'NEW EMEBEDDED',
                'description'         => 'A description',
                'embedded_properties' => $embedded_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/'.$root_id.'/embedded_files', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        return $response->json()['id'];
    }

    /**
     * @depends testGetRootId
     * @depends testPostEmbeddedDocument
     */
    public function testPostCopyEmbeddedDocument(int $root_id, int $embedded_document_id) : void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . urlencode((string) $root_id) . '/embedded_files',
                ['Content-Type' => 'application/json'],
                json_encode(['copy' => ['item_id' => $embedded_document_id]])
            )
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_embedded_files/' . urlencode((string) $response->json()['id']))
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPostLinkDocument(int $root_id): int
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'           => 'NEW L',
                'description'     => 'A description',
                'link_properties' => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id ."/links", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        return $response->json()['id'];
    }

    /**
     * @depends testGetRootId
     * @depends testPostLinkDocument
     */
    public function testPostCopyLinkDocument(int $root_id, int $link_document_id) : void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . urlencode((string) $root_id) . '/links',
                ['Content-Type' => 'application/json'],
                json_encode(['copy' => ['item_id' => $link_document_id]])
            )
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_links/' . urlencode((string) $response->json()['id']))
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFolderWithStatusWhenStatusIsNotAllowedForProject(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $query = json_encode(
            [
                'title'       => 'NEW FOLDER',
                'description' => 'A description',
                'status'      => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/folders", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("Status", $response->json()["error"]['i18n_error_message']);
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
        $this->assertStringContainsString("Status", $response->json()["error"]['i18n_error_message']);
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
        $this->assertStringContainsString("obsolescence date", $response->json()["error"]['i18n_error_message']);
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
        $this->assertStringContainsString("root folder", $response->json()["error"]['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($root_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteThrowsAnErrorWhenUserHasNotPermissionToDeleteTheFolder(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE FO RO');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_folders/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItDeletesAFolder(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE FO');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_folders/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($file_to_delete_id);
    }
}
