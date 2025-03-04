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

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest\Docman;

require_once __DIR__ . '/../../../vendor/autoload.php';

use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
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
     * @depends testGetRootIdWithUserRESTReadOnlyAdmin
     */
    public function testGetDocumentItemsWithUserRESTReadOnlyAdmin(int $root_id): array
    {
        $root_folder = $this->loadRootFolderContent($root_id);

        $items_file    = $this->loadFolderContent($root_id, 'Folder', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $folder_files  = $this->findItemByTitle($root_folder, 'Folder');
        $items_file_id = $folder_files['id'];
        $get           = $this->loadFolderContent($items_file_id, 'GET FO', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $delete        = $this->loadFolderContent($items_file_id, 'DELETE Folder', REST_TestDataBuilder::TEST_BOT_USER_NAME);

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
                'file_properties' => ['file_name' => 'NEW F', 'file_size' => 0],
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $folder_id . '/files')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('exists', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['message']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileDocument(int $root_id): int
    {
        $file_size     = 123;
        $invalid_query = json_encode(
            [
                'title' => 'NEW F',
                'file_properties' => [
                    'file_name' => 'NEW F',
                    'file_size' => $file_size,
                ],
            ]
        );

        $post_response_with_rest_read_only_user = $this->getResponseByName(
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream($invalid_query))
        );
        $this->assertEquals(403, $post_response_with_rest_read_only_user->getStatusCode());

        $query     = json_encode(
            [
                'title' => 'NEW FILE',
                'file_properties' => [
                    'file_name' => 'NEW FILE',
                    'file_size' => $file_size,
                ],
            ]
        );
        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $response1_json = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($response1_json['file_properties']['upload_href']);

        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', $response1_json['file_properties']['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($file_content))
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $response1_json['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $file_item_response_json = json_decode($file_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('file', $file_item_response_json['type']);

        $file_content_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $file_item_response_json['file_properties']['download_href'])
        );
        $this->assertEquals(200, $file_content_response->getStatusCode());
        $this->assertEquals($file_content, $file_content_response->getBody());

        $this->assertStringContainsString('filename="tuleap-NEW FILE"', $file_content_response->getHeader('Content-Disposition')[0]);

        return $response1_json['id'];
    }

    /**
     * @depends testGetRootId
     * @depends testPostFileDocument
     */
    public function testPostCopyFileDocument(int $root_id, int $file_document_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/files')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $file_document_id]])))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_files/' . urlencode((string) json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id']))
        );
    }

    /**
     * @depends testGetRootId
     * @depends testPostFileDocument
     */
    public function testPostCopyFileDocumentWithUserRESTReadOnlyAdmin(int $root_id, int $file_document_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/files')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $file_document_id]]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyFileDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'NEW EMPTY F',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 0],
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $response1_json = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNull($response1_json['file_properties']);

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $response1_json['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', json_decode($file_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['type']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyFileDocumentDenidedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'NEW EMPTY F',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 0],
            ]
        );

        $response1 = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response1->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostOtherTypeDocumentIsRejectedForUnsupportedType(int $root_id): void
    {
        $query = json_encode(
            [
                'title' => 'NEW OTHER F1',
                'type'  => 'whatever',
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/others')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(400, $response1->getStatusCode());
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
                'file_properties' => ['file_name' => 'NEW BIG F', 'file_size' => 999999999999],
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('size', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['message']);
    }

    /**
     * @depends testGetRootId
     */
    public function testDocumentCreationIsRejectedIfAFileIsBeingUploadedForTheSameNameByADifferentUser(int $root_id): void
    {
        $document_name = 'document_conflict_' . bin2hex(random_bytes(8));

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream(json_encode(
                [
                    'title'           => $document_name,
                    'file_properties' => ['file_name' => 'file', 'file_size' => 123],
                ]
            )))
        );
        $this->assertEquals(201, $response->getStatusCode());

        $response2 = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/empties')->withBody($this->stream_factory->createStream(json_encode(
                [
                    'title'     => $document_name,
                    'parent_id' => $root_id,
                ]
            )))
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
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream(json_encode(
                [
                    'title'           => $document_name,
                    'file_properties' => ['file_name' => 'file', 'file_size' => 123],
                ]
            )))
        );
        $this->assertEquals(201, $response_creation_file->getStatusCode());

        $tus_response_cancel = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest(
                'DELETE',
                json_decode($response_creation_file->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['file_properties']['upload_href']
            )->withHeader('Tus-Resumable', '1.0.0')
        );
        $this->assertEquals(204, $tus_response_cancel->getStatusCode());

        $response_creation_empty = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream(json_encode(
                [
                    'title'           => $document_name,
                    'file_properties' => ['file_name' => 'file', 'file_size' => 123],
                ]
            )))
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
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/folders')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response->getStatusCode());
        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNull($response_json['file_properties']);

        return $response_json['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFolderItemDenidedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'NEW FO',
                'description' => 'A Folder description',
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/folders')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostMoveFolderItem(int $root_id): void
    {
        $response_folder_to_cut_with_rest_read_only_user = $this->getResponseByName(
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Folder to cut'])))
        );
        $this->assertEquals(403, $response_folder_to_cut_with_rest_read_only_user->getStatusCode());

        $response_folder_to_cut = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Folder to cut'])))
        );
        $this->assertEquals(201, $response_folder_to_cut->getStatusCode());
        $folder_to_cut_id = json_decode($response_folder_to_cut->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $response_folder_creation_with_rest_read_only_user = $this->getResponseByName(
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Folder cut folder'])))
        );
        $this->assertEquals(403, $response_folder_creation_with_rest_read_only_user->getStatusCode());

        $response_folder_destination_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Folder cut folder'])))
        );
        $this->assertEquals(201, $response_folder_destination_creation->getStatusCode());
        $folder_destination_id = json_decode($response_folder_destination_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $move_response_with_rest_read_only_user = $this->getResponseByName(
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'docman_folders/' . urlencode((string) $folder_to_cut_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_destination_id]])))
        );
        $this->assertEquals(403, $move_response_with_rest_read_only_user->getStatusCode());

        $move_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'docman_folders/' . urlencode((string) $folder_to_cut_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_destination_id]])))
        );
        $this->assertEquals(200, $move_response->getStatusCode());

        $moved_item_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $folder_to_cut_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals($folder_destination_id, json_decode($moved_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['parent_id']);

        $delete_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) $folder_destination_id)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $delete_response_with_rest_read_only_user->getStatusCode());

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) $folder_destination_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
    }

    /**
     * @depends testGetRootId
     * @depends testPostFolderItem
     */
    public function testPostCopyFolderItem(int $root_id, int $folder_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $folder_id]])))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id']))
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
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/folders')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('exists', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['message']);
    }

    /**
     * @depends testGetRootId
     * @depends testPostFolderItem
     */
    public function testPostCopyFolderItemWithUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id, int $folder_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $folder_id]]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
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
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/empties')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(201, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];
    }

    /**
     * @depends testGetRootId
     * @depends testPostEmptyDocument
     */
    public function testPostCopyEmptyDocument(int $root_id, int $empty_document_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/empties')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $empty_document_id]])))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . urlencode((string) json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id']))
        );
    }

    /**
     * @depends testGetRootId
     * @depends testPostEmptyDocument
     */
    public function testPostCopyOtherDocumentIsRejectedIfSourceItemIsNotOtherDocument(int $root_id, int $empty_document_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/others')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $empty_document_id]])))
        );

        $this->assertEquals(400, $response->getStatusCode());
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
            $this->request_factory->createRequest('POST', 'docman_folders/' . $read_only_folder['id'] . '/empties')->withBody($this->stream_factory->createStream($query))
        );
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $read_only_folder['id'] . '/empties')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiDocument(int $root_id): int
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'           => 'NEW W',
                'description'     => 'A description',
                'wiki_properties' => $wiki_properties,
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/wikis')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiDocumentDenidedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'           => 'NEW W',
                'description'     => 'A description',
                'wiki_properties' => $wiki_properties,
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/wikis')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     * @depends testPostWikiDocument
     */
    public function testPostCopyWikiDocument(int $root_id, int $wiki_document_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/wikis')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $wiki_document_id]])))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_wikis/' . urlencode((string) json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id']))
        );
    }

    /**
     * @depends testGetRootId
     * @depends testPostWikiDocument
     */
    public function testPostCopyWikiDocumentDenidedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id, int $wiki_document_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/wikis')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $wiki_document_id]]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmbeddedDocument(int $root_id): int
    {
        $headers             = ['Content-Type' => 'application/json'];
        $embedded_properties = ['content' => 'step1 : Avoid to sort items in the docman'];
        $query               = json_encode(
            [
                'title'               => 'NEW EMEBEDDED',
                'description'         => 'A description',
                'embedded_properties' => $embedded_properties,
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/embedded_files')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmbeddedDocumentDenidedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id): void
    {
        $headers             = ['Content-Type' => 'application/json'];
        $embedded_properties = ['content' => 'step1 : Avoid to sort items in the docman'];
        $query               = json_encode(
            [
                'title'               => 'NEW EMEBEDDED',
                'description'         => 'A description',
                'embedded_properties' => $embedded_properties,
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/embedded_files')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     * @depends testPostEmbeddedDocument
     */
    public function testPostCopyEmbeddedDocument(int $root_id, int $embedded_document_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/embedded_files')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $embedded_document_id]])))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . urlencode((string) json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id']))
        );
    }

    /**
     * @depends testGetRootId
     * @depends testPostEmbeddedDocument
     */
    public function testPostCopyEmbeddedDocumentDenidedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id, int $embedded_document_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/embedded_files')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $embedded_document_id]]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
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
                'link_properties' => $link_properties,
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/links')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testPostLinkDocumentDenidedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'           => 'NEW L',
                'description'     => 'A description',
                'link_properties' => $link_properties,
            ]
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/links')->withBody($this->stream_factory->createStream($query)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     * @depends testPostLinkDocument
     */
    public function testPostCopyLinkDocument(int $root_id, int $link_document_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/links')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $link_document_id]])))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_links/' . urlencode((string) json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id']))
        );
    }

    /**
     * @depends testGetRootId
     * @depends testPostLinkDocument
     */
    public function testPostCopyLinkDocumentDenidedForUserRESTReadOnlyAdminNotInvolvedInProject(int $root_id, int $link_document_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/links')->withBody($this->stream_factory->createStream(json_encode(['copy' => ['item_id' => $link_document_id]]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
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
                'status'      => 'approved',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/folders')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Status', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);
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
                'status'          => 'approved',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/files')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('Status', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);
    }

    /**
     * @depends testGetRootId
     */
    public function testUpdatePermissionsFolder(int $root_id): void
    {
        $response_folder_updater_permissions = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Folder update permissions'])))
        );
        $this->assertEquals(201, $response_folder_updater_permissions->getStatusCode());
        $folder_id = json_decode($response_folder_updater_permissions->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $project_members_identifier = $this->project_id . '_3';
        $permission_update_put_body = json_encode(
            ['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]]
        );

        $permission_update_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_folders/' . urlencode((string) $folder_id) . '/permissions')->withBody($this->stream_factory->createStream($permission_update_put_body)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $permission_update_response_with_rest_read_only_user->getStatusCode());

        $permission_update_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_folders/' . urlencode((string) $folder_id) . '/permissions')->withBody($this->stream_factory->createStream($permission_update_put_body))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());

        $folder_representation_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $folder_id))
        );
        $this->assertEquals(200, $folder_representation_response->getStatusCode());
        $permissions_for_groups_representation = json_decode($folder_representation_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['permissions_for_groups'];
        $this->assertEmpty($permissions_for_groups_representation['can_read']);
        $this->assertEmpty($permissions_for_groups_representation['can_write']);
        $this->assertCount(1, $permissions_for_groups_representation['can_manage']);
        $this->assertEquals($project_members_identifier, $permissions_for_groups_representation['can_manage'][0]['id']);

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) $folder_id)),
            \TestDataBuilder::ADMIN_USER_NAME
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testUpdatePermissionsFolderAndChildren(int $root_id): void
    {
        $response_folder_update_permissions = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Folder update permissions with child'])))
        );
        $this->assertEquals(201, $response_folder_update_permissions->getStatusCode());
        $folder_id = json_decode($response_folder_update_permissions->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $response_child_update_permissions = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $folder_id) . '/empties')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Child update permissions'])))
        );
        $this->assertEquals(201, $response_child_update_permissions->getStatusCode());
        $child_id = json_decode($response_child_update_permissions->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $project_members_identifier = $this->project_id . '_3';
        $permission_update_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_folders/' . urlencode((string) $folder_id) . '/permissions')->withBody($this->stream_factory->createStream(json_encode([
                'apply_permissions_on_children' => true,
                'can_read'                      => [],
                'can_write'                     => [],
                'can_manage'                    => [['id' => $project_members_identifier]],
            ])))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());

        $folder_representation_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $folder_id))
        );
        $this->assertEquals(200, $folder_representation_response->getStatusCode());
        $permissions_for_groups_representation = json_decode($folder_representation_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['permissions_for_groups'];
        $this->assertEmpty($permissions_for_groups_representation['can_read']);
        $this->assertEmpty($permissions_for_groups_representation['can_write']);
        $this->assertCount(1, $permissions_for_groups_representation['can_manage']);
        $this->assertEquals($project_members_identifier, $permissions_for_groups_representation['can_manage'][0]['id']);
        $child_representation_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $child_id))
        );
        $this->assertEquals(200, $child_representation_response->getStatusCode());
        $this->assertEquals($permissions_for_groups_representation, json_decode($child_representation_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['permissions_for_groups']);

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) $folder_id)),
            \TestDataBuilder::ADMIN_USER_NAME
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testItThrowsAnErrorWhenWeTryToDeleteTheRootFolder(int $root_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . $root_id)
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('root folder', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

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
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteThrowsAnErrorForUserRESTReadOnlyAdminNotInvolvedInProject(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE FO RO');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . $file_to_delete_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());

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
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($file_to_delete_id);
    }
}
