<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
class DocmanEmptyTest extends DocmanTestExecutionHelper
{
    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testGetDocumentItemsForAdminUser(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items         = $this->loadFolderContent($root_id, 'Empty');
        $folder        = $this->findItemByTitle($root_folder, 'Empty');
        $items_id      = $folder['id'];
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Empty');
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Empty');
        $put_items     = $this->loadFolderContent($items_id, 'PUT HM Empty');

        return array_merge(
            $root_folder,
            $folder,
            $items,
            $deleted_items,
            $lock_items,
            $put_items
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteThrowsAnErrorWhenUserHasNotPermissionToDeleteTheEmpty(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE EM RO');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . $item_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteThrowAPermissionErrorWhenTheEmptyIsLockedByAnotherUser(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE EM L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . $item_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteIsProceedWhenItemIsLockedAndUserIsAdmin(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE EM L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . $item_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testItDeletesAnEmptyDoc(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE EM');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . $item_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostLocksAnEmpty(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK EM');
        $locked_document_id = $locked_document['id'];

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_empty_documents/' . $locked_document_id . '/lock')
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $locked_document_id)
        );

        $document = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($document['lock_info']['locked_by']['username'], \TestDataBuilder::ADMIN_USER_NAME);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteLockAnEmpty(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK EM');
        $locked_document_id = $locked_document['id'];

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . $locked_document_id . '/lock')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $locked_document_id)
        );

        $document = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($document['lock_info'], null);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPutBasicHardcodedMetadata(array $items): void
    {
        $item_name         = 'PUT EM';
        $item_to_update    = $this->findItemByTitle($items, $item_name);
        $item_to_update_id = $item_to_update['id'];

        $this->assertEquals($item_name, $item_to_update['title']);
        $this->assertEquals('', $item_to_update['description']);
        $this->assertEquals($this->docman_user_id, $item_to_update['owner']['id']);

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $item_to_update['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $put_resource = [
            'id'                => $item_to_update_id,
            'title'             => 'PUT EM New title',
            'description'       => 'Danger ! Danger !',
            'owner_id'          => $this->test_user_1_id,
            'status'            => 'none',
        ];

        $updated_metadata_file_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_empty_documents/' . $item_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource)))
        );

        $this->assertEquals(200, $updated_metadata_file_response->getStatusCode());

        $new_version_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $item_to_update_id)
        );

        $this->assertEquals($new_version_response->getStatusCode(), 200);

        $new_version = json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $date_after_update          = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version['last_update_date']
        );
        $last_update_date_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $last_update_date_timestamp);

        $this->assertEquals('PUT EM New title', $new_version['title']);
        $this->assertEquals('Danger ! Danger !', $new_version['description']);
        $this->assertEquals($this->test_user_1_id, $new_version['owner']['id']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsMetadata(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_wikis/' . $id . '/metadata'),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptions(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . $id), \TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsLock(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . $id . '/lock'), \TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testMoveEmptyDocument(int $root_id): void
    {
        $response_empty_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/empties')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Empty document to cut'])))
        );
        $this->assertEquals(201, $response_empty_creation->getStatusCode());
        $empty_doc_id = json_decode($response_empty_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $response_folder_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Empty cut folder'])))
        );
        $this->assertEquals(201, $response_folder_creation->getStatusCode());
        $folder_id = json_decode($response_folder_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $move_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'docman_empty_documents/' . urlencode((string) $empty_doc_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_id]])))
        );
        $this->assertEquals(200, $move_response->getStatusCode());

        $moved_item_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $empty_doc_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals($folder_id, json_decode($moved_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['parent_id']);

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) $folder_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testUpdatePermissionsEmptyDocument(int $root_id): void
    {
        $response_empty_creation = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/empties')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Empty document for updating permissions'])))
        );
        $this->assertEquals(201, $response_empty_creation->getStatusCode());
        $empty_doc_id = json_decode($response_empty_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $project_members_identifier = $this->project_id . '_3';
        $permission_update_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_empty_documents/' . urlencode((string) $empty_doc_id) . '/permissions')->withBody($this->stream_factory->createStream(json_encode(['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]])))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());

        $empty_doc_representation_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $empty_doc_id))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());
        $permissions_for_groups_representation = json_decode($empty_doc_representation_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['permissions_for_groups'];
        $this->assertEmpty($permissions_for_groups_representation['can_read']);
        $this->assertEmpty($permissions_for_groups_representation['can_write']);
        $this->assertCount(1, $permissions_for_groups_representation['can_manage']);
        $this->assertEquals($project_members_identifier, $permissions_for_groups_representation['can_manage'][0]['id']);

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_empty_document/' . urlencode((string) $empty_doc_id)),
            \TestDataBuilder::ADMIN_USER_NAME
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testPostVersionEmptyToEmbeddedFile(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $query = json_encode(
            [
                'title'       => 'Empty to embedded',
                'description' => 'A description',
            ]
        );

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/empties')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $empty_to_update_id = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $new_content = json_encode(
            [
                'content' => 'youhououh content',
            ]
        );

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_empty_documents/' . $empty_to_update_id . '/embedded_file')->withBody($this->stream_factory->createStream($new_content))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $updated_item_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $empty_to_update_id)),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(200, $updated_item_response->getStatusCode());

        $updated_item = json_decode($updated_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('embedded', $updated_item['type']);
        $this->assertEquals('youhououh content', $updated_item['embedded_file_properties']['content']);

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . $updated_item['id'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($updated_item['id']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsEmbeddedFileVersion(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . $id . '/embedded_file'),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testPostVersionEmptyToFile(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $query = json_encode(
            [
                'title'       => 'Empty',
                'description' => 'Nothing',
            ]
        );

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/empties')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $empty_to_update_id = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $file_size       = 10;
        $file_properties = json_encode(
            [
                'file_name' => 'Blanka',
                'file_size' => $file_size,
            ]
        );

        $version_response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_empty_documents/' . urlencode((string) $empty_to_update_id) . '/file')->withBody($this->stream_factory->createStream($file_properties))
        );

        $this->assertEquals(201, $version_response->getStatusCode());
        $version_response_json = json_decode($version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotNull($version_response_json['upload_href']);

        $file_content = str_repeat('A', $file_size);

        $tus_response_upload = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PATCH', $version_response_json['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($file_content))
        );

        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $updated_item_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $empty_to_update_id)),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(200, $updated_item_response->getStatusCode());

        $updated_item = json_decode($updated_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('file', $updated_item['type']);

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $updated_item['id'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($updated_item['id']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsFileVersion(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . $id . '/file'),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testPostVersionEmptyToLink(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $query = json_encode(
            [
                'title'       => 'Empty to link',
                'description' => 'A description',
            ]
        );

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/empties')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $empty_to_update_id = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $new_link_url = json_encode(
            [
                'link_url' => 'https://example.test',
            ]
        );

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_empty_documents/' . $empty_to_update_id . '/link')->withBody($this->stream_factory->createStream($new_link_url))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $updated_item_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $empty_to_update_id)),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(200, $updated_item_response->getStatusCode());

        $updated_item = json_decode($updated_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('link', $updated_item['type']);
        $this->assertEquals('https://example.test', $updated_item['link_properties']['link_url']);

        $response = $this->getResponseByName(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_links/' . urlencode((string) $updated_item['id']))
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($updated_item['id']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsLinkVersion(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . urlencode((string) $id) . '/link'),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testAllOptionsRouteForUserRESTReadOnlyAdmin(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . urlencode((string) $id)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . urlencode((string) $id) . '/lock'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . urlencode((string) $id) . '/embedded_file'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . urlencode((string) $id) . '/link'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . urlencode((string) $id) . '/file'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . urlencode((string) $id) . '/metadata'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_empty_documents/' . urlencode((string) $id) . '/permissions'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function patchMoveAnEmptyDocumentDeniedForUserRestReadOnlyAdmin(int $root_id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_empty_documents/' . $root_id)->withBody($this->stream_factory->createStream(['move' => ['destination_folder_id' => $root_id]])),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function deleteAnEmptyDocumentDeniedForUserRestReadOnlyAdmin(int $root_id): void
    {
        $empty_to_update_id = $this->createEmptyDocumentAndReturnId($root_id);
        $response           = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . $empty_to_update_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());
        $this->deleteCreatedEmptyDocument($empty_to_update_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function postLockToAnEmptyDocumentDeniedForUserRestReadOnlyAdmin(int $root_id): void
    {
        $empty_to_update_id = $this->createEmptyDocumentAndReturnId($root_id);
        $response           = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_empty_documents/' . $empty_to_update_id . '/lock'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());
        $this->deleteCreatedEmptyDocument($empty_to_update_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function deleteLockToAnEmptyDocumentDeniedForUserRestReadOnlyAdmin(int $root_id): void
    {
        $empty_to_update_id = $this->createEmptyDocumentAndReturnId($root_id);
        $response           = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . $empty_to_update_id . '/lock'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());
        $this->deleteCreatedEmptyDocument($empty_to_update_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function putMetadataToAnEmptyDocumentDeniedForUserRestReadOnlyAdmin(int $root_id): void
    {
        $empty_to_update_id = $this->createEmptyDocumentAndReturnId($root_id);

        $metadata = ['status' => 'none'];
        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_empty_documents/' . $empty_to_update_id . '/metadata')->withBody($this->stream_factory->createStream($metadata)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());
        $this->deleteCreatedEmptyDocument($empty_to_update_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function putPermissionsToAnEmptyDocumentDeniedForUserRestReadOnlyAdmin(int $root_id): void
    {
        $empty_to_update_id = $this->createEmptyDocumentAndReturnId($root_id);

        $project_members_identifier = $this->project_id . '_3';
        $response                   = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_empty_documents/' . $empty_to_update_id . '/permissions')->withBody($this->stream_factory->createStream(json_encode(['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]]))),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());
        $this->deleteCreatedEmptyDocument($empty_to_update_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function postVersionDeniedForUserRESTReadOnlyAdmin(int $root_id): void
    {
        $empty_to_update_id = $this->createEmptyDocumentAndReturnId($root_id);

        $content  = json_encode(
            [
                'content' => 'You just get jealous about my fame',
            ]
        );
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_empty_documents/' . $empty_to_update_id . '/embedded_file')->withBody($this->stream_factory->createStream($content)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());

        $file_properties = json_encode(
            [
                'file_name' => 'Spongebob.jpeg',
                'file_size' => 10,
            ]
        );
        $response        = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_empty_documents/' . $empty_to_update_id . '/file')->withBody($this->stream_factory->createStream($file_properties)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());

        $link_url = json_encode(
            [
                'link_url' => 'https://example.test',
            ]
        );
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_empty_documents/' . $empty_to_update_id . '/link')->withBody($this->stream_factory->createStream($link_url)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    private function createEmptyDocumentAndReturnId(int $root_id): int
    {
        $headers = ['Content-Type' => 'application/json'];

        $query = json_encode(
            [
                'title'       => 'Empty to nothing',
                'description' => 'A description',
            ]
        );

        $response = $this->getResponse(
            \TestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/empties')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response->getStatusCode());

        $empty_to_update_id = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        return $empty_to_update_id;
    }

    private function deleteCreatedEmptyDocument(int $empty_document_to_delete): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . $empty_document_to_delete),
            \TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
