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

use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RESTTestDataBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanEmbeddedTest extends DocmanTestExecutionHelper
{
    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testGetDocumentItemsForAdminUser(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items         = $this->loadFolderContent($root_id, 'Embedded');
        $folder        = $this->findItemByTitle($root_folder, 'Embedded');
        $items_id      = $folder['id'];
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Embedded');
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Embedded');
        $version_items = $this->loadFolderContent($items_id, 'POST Embedded version');
        $put_items     = $this->loadFolderContent($items_id, 'PUT HM Embedded');

        return array_merge(
            $root_folder,
            $folder,
            $items,
            $deleted_items,
            $lock_items,
            $version_items,
            $put_items
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootIdWithUserRESTReadOnlyAdmin')]
    public function testGetDocumentItemsWithUserRESTReadOnlyAdmin(int $root_id): array
    {
        $root_folder = $this->loadRootFolderContent(
            $root_id,
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $items         = $this->loadFolderContent($root_id, 'Embedded', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $folder        = $this->findItemByTitle($root_folder, 'Embedded');
        $items_id      = $folder['id'];
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Embedded', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Embedded', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $version_items = $this->loadFolderContent($items_id, 'POST Embedded version', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $put_items     = $this->loadFolderContent($items_id, 'PUT HM Embedded', RESTTestDataBuilder::TEST_BOT_USER_NAME);

        return array_merge(
            $root_folder,
            $folder,
            $items,
            $deleted_items,
            $lock_items,
            $version_items,
            $put_items
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testMoveEmbeddedDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'               => 'My new embedded',
                'embedded_properties' => ['content' => 'my new content'],
            ]
        );

        $embedded_id = $this->createEmbeddedFileAndReturnItsId($root_id, $query);

        $response_folder_creation_with_rest_read_only_user = $this->getResponseByName(
            RESTTestDataBuilder::TEST_BOT_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Embedded cut folder'])))
        );
        $this->assertEquals(403, $response_folder_creation_with_rest_read_only_user->getStatusCode());

        $response_folder_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Embedded cut folder'])))
        );

        $this->assertEquals(201, $response_folder_creation->getStatusCode());
        $folder_id = json_decode($response_folder_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $move_response_with_rest_read_only_user = $this->getResponseByName(
            RESTTestDataBuilder::TEST_BOT_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'docman_embedded_files/' . urlencode((string) $embedded_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_id]])))
        );
        $this->assertEquals(403, $move_response_with_rest_read_only_user->getStatusCode());

        $move_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'docman_embedded_files/' . urlencode((string) $embedded_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_id]])))
        );
        $this->assertEquals(200, $move_response->getStatusCode());

        $moved_item_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $embedded_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals($folder_id, json_decode($moved_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['parent_id']);

        $moved_item_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $embedded_id)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals($folder_id, json_decode($moved_item_response_with_rest_read_only_user->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['parent_id']);

        $delete_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) $folder_id)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $delete_response_with_rest_read_only_user->getStatusCode());

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) $folder_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testUpdatePermissionsEmbeddedDocument(int $root_id): void
    {
        $embedded_doc_id = $this->createEmbeddedFileAndReturnItsId(
            $root_id,
            json_encode(['title' => 'Embedded update permissions', 'embedded_properties' => ['content' => 'content']])
        );

        $project_members_identifier = $this->project_id . '_3';
        $put_body                   = json_encode(
            ['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]]
        );

        $permission_update_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_embedded_files/' . urlencode((string) $embedded_doc_id) . '/permissions')->withBody($this->stream_factory->createStream($put_body)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $permission_update_response_with_rest_read_only_user->getStatusCode());

        $permission_update_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_embedded_files/' . urlencode((string) $embedded_doc_id) . '/permissions')->withBody($this->stream_factory->createStream($put_body))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());

        $embedded_doc_representation_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $embedded_doc_id))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());
        $permissions_for_groups_representation = json_decode($embedded_doc_representation_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['permissions_for_groups'];
        $this->assertEmpty($permissions_for_groups_representation['can_read']);
        $this->assertEmpty($permissions_for_groups_representation['can_write']);
        $this->assertCount(1, $permissions_for_groups_representation['can_manage']);
        $this->assertEquals($project_members_identifier, $permissions_for_groups_representation['can_manage'][0]['id']);

        $delete_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . urlencode((string) $embedded_doc_id)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $delete_response_with_rest_read_only_user->getStatusCode());

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . urlencode((string) $embedded_doc_id)),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteThrowsAnErrorWhenUserHasNotPermissionToDeleteTheEmbedded(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE E RO');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteThrowAPermissionErrorWhenTheEmbeddedIsLockedByAnotherUser(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE E L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteIsProceedWhenFileIsLockedAndUserIsAdmin(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE E L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($file_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testItDoesNotDeletesAnEmbeddedFileWithRESTReadOnlyUserNotInvolvedInProject(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE E');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . $file_to_delete_id),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testItDeletesAnEmbeddedFile(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE E');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($file_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostLocksAnEmbeddedFile(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK E');
        $locked_document_id = $locked_document['id'];

        $post_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $locked_document_id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $post_response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $locked_document_id . '/lock')
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $locked_document_id)
        );

        $document = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($document['lock_info']['locked_by']['username'], BaseTestDataBuilder::ADMIN_USER_NAME);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteLockAnEmbeddedFile(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK E');
        $locked_document_id = $locked_document['id'];

        $delete_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . $locked_document_id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $delete_response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_embedded_files/' . $locked_document_id . '/lock')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $locked_document_id)
        );

        $document = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($document['lock_info'], null);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionWithRESTReadOnlyUserDoesNotCreateAnEmbeddedFile(array $items): void
    {
        $title             = 'POST E V';
        $file_to_update    = $this->findItemByTitle($items, $title);
        $file_to_update_id = $file_to_update['id'];

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                'embedded_properties' => [
                    'content' => 'my new content',
                ],
                'should_lock_file'    => false,
            ]
        );
        $new_version_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $file_to_update_id . '/versions')->withBody($this->stream_factory->createStream($new_version_resource)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $new_version_response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionItCreatesAnEmbeddedFile(array $items): void
    {
        $title             = 'POST E V';
        $file_to_update    = $this->findItemByTitle($items, $title);
        $file_to_update_id = $file_to_update['id'];

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                'embedded_properties' => [
                    'content' => 'my new content',
                ],
                'should_lock_file'    => false,
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $file_to_update_id . '/versions')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $this->checkItemHasADisabledApprovalTable($items, $title);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionCopyThePreviousApprovalTableStatus(array $items): void
    {
        $title             = 'POST E V AT C';
        $file_to_update    = $this->findItemByTitle($items, $title);
        $file_to_update_id = $file_to_update['id'];

        $this->checkItemHasAnApprovalTable($items, $title, 'Approved');

        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'embedded_properties'   => [
                    'content' => 'my new content',
                ],
                'should_lock_file'      => false,
                'approval_table_action' => 'copy',
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $file_to_update_id . '/versions')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $this->checkItemHasAnApprovalTable($items, $title, 'Approved');
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionResetTheApprovalTableStatus(array $items): void
    {
        $title             = 'POST E V AT R';
        $file_to_update    = $this->findItemByTitle($items, $title);
        $file_to_update_id = $file_to_update['id'];

        $this->checkItemHasAnApprovalTable($items, $title, 'Approved');

        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'embedded_properties'   => [
                    'content' => 'my new content',
                ],
                'should_lock_file'      => false,
                'approval_table_action' => 'reset',
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $file_to_update_id . '/versions')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $this->checkItemHasAnApprovalTable($items, $title, 'Not yet');
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionDisableApprovalTable(array $items): void
    {
        $title             = 'POST E V AT E';
        $file_to_update    = $this->findItemByTitle(
            $items,
            $title
        );
        $file_to_update_id = $file_to_update['id'];

        $this->checkItemHasAnApprovalTable($items, $title, 'Approved');

        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'embedded_properties'   => [
                    'content' => 'my new content',
                ],
                'should_lock_file'      => false,
                'approval_table_action' => 'empty',
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $file_to_update_id . '/versions')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $new_version_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $this->checkItemHasADisabledApprovalTable($items, $title);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionItThrowsExceptionWhenUserSetApprovalTableOnItemWithoutApprovalTable(
        array $items,
    ): void {
        $file_to_update    = $this->findItemByTitle($items, 'POST E V No AT');
        $file_to_update_id = $file_to_update['id'];

        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'embedded_properties'   => [
                    'content' => 'my new content',
                ],
                'should_lock_file'      => false,
                'approval_table_action' => 'reset',
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $file_to_update_id . '/versions')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(400, $new_version_response->getStatusCode());
        $this->assertStringContainsString(
            'does not have an approval table',
            json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionCanUnlockAFile(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST E V L');
        $file_to_update_id = $file_to_update['id'];

        $this->assertNotNull($file_to_update['lock_info']);

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                'embedded_properties' => [
                    'content' => 'my new content',
                ],
                'should_lock_file'    => false,
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $file_to_update_id . '/versions')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('embedded', $response_json['type']);
        $this->assertNull($response_json['lock_info']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionAdminAlwaysCanUnlockAFile(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST E V UL Admin');
        $file_to_update_id = $file_to_update['id'];

        $this->assertNotNull($file_to_update['lock_info']);

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                'embedded_properties' => [
                    'content' => 'my new content',
                ],
                'should_lock_file'    => false,
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $file_to_update_id . '/versions')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(200, $response->getStatusCode());
        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('embedded', $response_json['type']);
        $this->assertNull($response_json['lock_info']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionRegularUserCanNotUnlockFileLockedByOtherUser(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST E V L Admin');
        $file_to_update_id = $file_to_update['id'];

        $this->assertNotNull($file_to_update['lock_info']);

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                'embedded_properties' => [
                    'content' => 'my new content',
                ],
                'should_lock_file'    => false,
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_embedded_files/' . $file_to_update_id . '/versions')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(403, $new_version_response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPutBasicHardcodedMetadata(array $items): void
    {
        $item_to_update    = $this->findItemByTitle($items, 'PUT E');
        $item_to_update_id = $item_to_update['id'];

        $this->assertEquals('PUT E', $item_to_update['title']);
        $this->assertEquals('', $item_to_update['description']);
        $this->assertEquals($this->docman_user_id, $item_to_update['owner']['id']);

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $item_to_update['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $new_title    = 'PUT E New Title';
        $put_resource = [
            'id'                => $item_to_update_id,
            'title'             => $new_title,
            'description'       => 'Danger ! Danger !',
            'owner_id'          => $this->test_user_1_id,
            'status'            => 'none',
        ];

        $updated_metadata_file_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_embedded_files/' . $item_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $updated_metadata_file_response_with_rest_read_only_user->getStatusCode());

        $updated_metadata_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_embedded_files/' . $item_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource)))
        );

        $this->assertEquals(200, $updated_metadata_file_response->getStatusCode());

        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
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

        $this->assertEquals($new_title, $new_version['title']);
        $this->assertEquals('Danger ! Danger !', $new_version['description']);
        $this->assertEquals($this->test_user_1_id, $new_version['owner']['id']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsMetadata(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_embedded_files/' . $id . '/metadata'),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptions(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_embedded_files/' . $id),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );
        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

        #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsLock($id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_embedded_files/' . $id . '/lock'),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsVersion(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_embedded_files/' . $id . '/versions'),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootIdWithUserRESTReadOnlyAdmin')]
    public function testAllAvailableOptionsWithRESTReadOnlyUser(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_embedded_files/' . $id),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_embedded_files/' . $id . '/metadata'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_embedded_files/' . $id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_embedded_files/' . $id . '/versions'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testGetVersionsOfEmbeddedFileAndDeleteOneOfThem(int $root_id): void
    {
        $embedded_doc_id = $this->createEmbeddedFileAndReturnItsId(
            $root_id,
            json_encode(
                ['title' => 'Embedded file with versions', 'embedded_properties' => ['content' => 'initial content']],
                JSON_THROW_ON_ERROR
            ),
        );

        $versions_uri = 'docman_embedded_files/' . $embedded_doc_id . '/versions';

        $new_version_resource = json_encode([
            'version_title'       => 'My new version',
            'description'         => 'whatever',
            'embedded_properties' => [
                'content' => 'my new content',
            ],
            'should_lock_file'    => false,
        ], JSON_THROW_ON_ERROR);

        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', $versions_uri)->withBody($this->stream_factory->createStream($new_version_resource))
        );

        self::assertEquals(200, $new_version_response->getStatusCode());

        $get_versions_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $versions_uri),
        );
        self::assertEquals(200, $get_versions_response->getStatusCode());

        $versions = json_decode($get_versions_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $versions);
        self::assertEquals(2, $versions[0]['number']);
        self::assertEquals('My new version', $versions[0]['name']);
        self::assertEquals(1, $versions[1]['number']);
        self::assertEquals('', $versions[1]['name']);

        $get_version_content_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_embedded_file_versions/' . $versions[1]['id'] . '/content'),
        );
        self::assertEquals(200, $get_version_content_response->getStatusCode());
        self::assertEquals(
            'initial content',
            json_decode($get_version_content_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['content']
        );

        $delete_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_embedded_file_versions/' . $versions[0]['id']),
        );

        self::assertEquals(204, $delete_version_response->getStatusCode());

        $get_versions_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $versions_uri),
        );
        self::assertEquals(200, $get_versions_response->getStatusCode());
        $versions = json_decode($get_versions_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $versions);
    }

    /**
     * @return mixed
     */
    private function createEmbeddedFileAndReturnItsId(int $root_id, string $query)
    {
        $post_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/embedded_files')->withBody($this->stream_factory->createStream($query)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $post_response_with_rest_read_only_user->getStatusCode());

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/embedded_files')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $response1_json = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $get_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('GET', $response1_json['uri']),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(200, $get_response_with_rest_read_only_user->getStatusCode());

        $embedded_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $response1_json['uri'])
        );

        $this->assertEquals(200, $embedded_item_response->getStatusCode());
        $this->assertEquals('embedded', json_decode($embedded_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['type']);

        return $response1_json['id'];
    }
}
