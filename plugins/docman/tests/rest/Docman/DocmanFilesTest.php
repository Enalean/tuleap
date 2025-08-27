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

use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RESTTestDataBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class DocmanFilesTest extends DocmanTestExecutionHelper
{
    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testGetDocumentItemsForAdminUser(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items_file    = $this->loadFolderContent($root_id, 'File');
        $folder_files  = $this->findItemByTitle($root_folder, 'File');
        $items_file_id = $folder_files['id'];
        $deleted_files = $this->loadFolderContent($items_file_id, 'DELETE File');
        $lock_files    = $this->loadFolderContent($items_file_id, 'LOCK File');
        $post_files    = $this->loadFolderContent($items_file_id, 'POST File Version');
        $put_files     = $this->loadFolderContent($items_file_id, 'PUT HM File');

        return array_merge(
            $root_folder,
            $folder_files,
            $items_file,
            $deleted_files,
            $lock_files,
            $post_files,
            $put_files
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootIdWithUserRESTReadOnlyAdmin')]
    public function testGetDocumentItemsWithUserRESTReadOnlyAdmin(int $root_id): array
    {
        $root_folder = $this->loadRootFolderContent(
            $root_id,
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $items_file    = $this->loadFolderContent($root_id, 'File', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $folder_files  = $this->findItemByTitle($root_folder, 'File');
        $items_file_id = $folder_files['id'];
        $deleted_files = $this->loadFolderContent($items_file_id, 'DELETE File', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $lock_files    = $this->loadFolderContent($items_file_id, 'LOCK File', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $post_files    = $this->loadFolderContent($items_file_id, 'POST File Version', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $put_files     = $this->loadFolderContent($items_file_id, 'PUT HM File', RESTTestDataBuilder::TEST_BOT_USER_NAME);

        return array_merge(
            $root_folder,
            $folder_files,
            $items_file,
            $deleted_files,
            $lock_files,
            $post_files,
            $put_files
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testMoveFileDocument(int $root_id): void
    {
        $file_creation_body_content = json_encode([
            'title' => 'File document to cut',
            'file_properties' => ['file_name' => 'file0', 'file_size' => 0],
        ]);

        $response_file_creation_with_rest_read_only_user = $this->getResponseByName(
            RESTTestDataBuilder::TEST_BOT_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/files')->withBody($this->stream_factory->createStream($file_creation_body_content))
        );
        $this->assertEquals(403, $response_file_creation_with_rest_read_only_user->getStatusCode());

        $response_file_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/files')->withBody($this->stream_factory->createStream($file_creation_body_content))
        );
        $this->assertEquals(201, $response_file_creation->getStatusCode());
        $file_doc_id = json_decode($response_file_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $response_folder_creation_with_rest_read_only_user = $this->getResponseByName(
            RESTTestDataBuilder::TEST_BOT_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'File cut folder'])))
        );
        $this->assertEquals(403, $response_folder_creation_with_rest_read_only_user->getStatusCode());

        $response_folder_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'File cut folder'])))
        );
        $this->assertEquals(201, $response_folder_creation->getStatusCode());
        $folder_id = json_decode($response_folder_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $move_response_with_rest_read_only_user = $this->getResponseByName(
            RESTTestDataBuilder::TEST_BOT_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'docman_files/' . urlencode((string) $file_doc_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_id]])))
        );
        $this->assertEquals(403, $move_response_with_rest_read_only_user->getStatusCode());

        $move_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'docman_files/' . urlencode((string) $file_doc_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_id]])))
        );
        $this->assertEquals(200, $move_response->getStatusCode());

        $moved_item_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $file_doc_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals($folder_id, json_decode($moved_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['parent_id']);

        $moved_item_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $file_doc_id)),
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
    public function testUpdatePermissionsFileDocument(int $root_id): void
    {
        $file_doc_id = $this->createANewFileAndGetItsId(
            $root_id,
            'File update permissions'
        );

        $project_members_identifier = $this->project_id . '_3';
        $put_body                   = json_encode(
            ['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]]
        );

        $permission_update_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_files/' . urlencode((string) $file_doc_id) . '/permissions')->withBody($this->stream_factory->createStream($put_body)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $permission_update_response_with_rest_read_only_user->getStatusCode());

        $permission_update_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_files/' . urlencode((string) $file_doc_id) . '/permissions')->withBody($this->stream_factory->createStream($put_body))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());

        $file_doc_representation_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $file_doc_id))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());
        $permissions_for_groups_representation = json_decode($file_doc_representation_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['permissions_for_groups'];
        $this->assertEmpty($permissions_for_groups_representation['can_read']);
        $this->assertEmpty($permissions_for_groups_representation['can_write']);
        $this->assertCount(1, $permissions_for_groups_representation['can_manage']);
        $this->assertEquals($project_members_identifier, $permissions_for_groups_representation['can_manage'][0]['id']);

        $delete_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_files/' . urlencode((string) $file_doc_id)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $delete_response_with_rest_read_only_user->getStatusCode());

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_files/' . urlencode((string) $file_doc_id)),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteThrowsAnErrorWhenUserHasNotPermissionToDeleteTheFile(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE F RO');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteThrowAPermissionErrorWhenTheFileIsLockedByAnotherUser(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE F L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteIsProceedWhenFileIsLockedAndUserIsAdmin(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE F L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($file_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testItDoesNotDeletesAFileWithRESTReadOnlyUserNotInvolvedInProject(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE F');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $file_to_delete_id),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testItDeletesAFile(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE F');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($file_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testLockThrowsAndExceptionWhenUserCanNotReadTheFile(array $items): void
    {
        $locked_document   = $this->findItemByTitle($items, 'LOCK F RO');
        $file_to_delete_id = $locked_document['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_delete_id . '/lock')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testItLocksAFile(array $items): void
    {
        $file_to_lock    = $this->findItemByTitle($items, 'LOCK F');
        $file_to_lock_id = $file_to_lock['id'];

        $post_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_lock_id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $post_response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_lock_id . '/lock')
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_lock_id)
        );

        $file = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($file['lock_info']['locked_by']['username'], BaseTestDataBuilder::ADMIN_USER_NAME);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostLockThrowsAndExceptionIfAnOtherUserHasLockedTheFile(array $items): void
    {
        $locked_document   = $this->findItemByTitle($items, 'LOCK F AL');
        $file_to_delete_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_delete_id . '/lock')
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testAdminShouldAlwaysBeAbleToUnLockAFile(array $items): void
    {
        $locked_document   = $this->findItemByTitle($items, 'LOCK F AL');
        $file_to_delete_id = $locked_document['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $file_to_delete_id . '/lock')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testRESTReadOnyAdminShouldNotBeAbleToUnLockAFileIfNotInvolvedInProject(array $items): void
    {
        $locked_document   = $this->findItemByTitle($items, 'LOCK F AL');
        $file_to_delete_id = $locked_document['id'];

        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $file_to_delete_id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testItThrowsExceptionForDeleteLockWhenUserCanNotReadTheFile(array $items): void
    {
        $locked_document   = $this->findItemByTitle($items, 'LOCK F RO');
        $file_to_delete_id = $locked_document['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::TEST_USER_2_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_files/' . $file_to_delete_id . '/lock')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionWithRESTReadOnlyUserDoesNotCreateAFile(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $file_size            = 15;
        $new_version_resource = json_encode(
            [
                'version_title'   => 'My new versionnn',
                'description'     => 'whatever',
                'file_properties' => [
                    'file_name' => 'string',
                    'file_size' => $file_size,
                ],
                'should_lock_file'      => false,
            ]
        );

        $new_version_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $new_version_response_with_rest_read_only_user->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionItCreatesAFile(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version              = json_decode($current_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();
        $file_size                    = 15;
        $new_version_resource         = json_encode(
            [
                'version_title'   => 'My new versionnn',
                'description'     => 'whatever',
                'file_properties' => [
                    'file_name' => 'string',
                    'file_size' => $file_size,
                ],
                'should_lock_file'      => false,
            ]
        );
        $new_version_response         = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $new_version_response_json = json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotNull($new_version_response_json['upload_href']);

        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PATCH', $new_version_response_json['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($file_content))
        );

        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $new_version_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version_file            = json_decode($new_version_file_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_after_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version_file['last_update_date']
        );
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->checkItemHasADisabledApprovalTable($items, 'POST F V');
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionCopyThePreviousApprovalTableStatus(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V AT C');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version = json_decode($current_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $current_version_approval_table = $current_version['approval_table']['approval_state'];
        $this->assertEquals($current_version_approval_table, 'Approved');

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();
        $file_size                    = 15;
        $new_version_resource         = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'file_properties'       => [
                    'file_name' => 'string',
                    'file_size' => $file_size,
                ],
                'should_lock_file'      => false,
                'approval_table_action' => 'copy',
            ]
        );
        $new_version_response         = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $new_version_response_json = json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotNull($new_version_response_json['upload_href']);

        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PATCH', $new_version_response_json['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($file_content))
        );

        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $new_version_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version       = json_decode($new_version_file_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_after_update = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version['last_update_date']
        );

        $this->checkItemHasAnApprovalTable($items, 'POST F V AT C', 'Approved');

        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionResetTheApprovalTableStatus(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V AT R');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version = json_decode($current_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $current_version_approval_table = $current_version['approval_table']['approval_state'];
        $this->assertEquals($current_version_approval_table, 'Approved');

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();
        $file_size                    = 15;
        $new_version_resource         = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'file_properties'       => [
                    'file_name' => 'string',
                    'file_size' => $file_size,
                ],
                'should_lock_file'      => false,
                'approval_table_action' => 'reset',
            ]
        );
        $new_version_response         = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $new_version_response_json = json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotNull($new_version_response_json['upload_href']);

        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PATCH', $new_version_response_json['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($file_content))
        );

        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $new_version_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version       = json_decode($new_version_file_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_after_update = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version['last_update_date']
        );

        $this->checkItemHasAnApprovalTable($items, 'POST F V AT R', 'Not yet');

        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionDisableApprovalTable(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V AT E');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version = json_decode($current_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $current_version_approval_table = $current_version['approval_table']['approval_state'];
        $this->assertEquals($current_version_approval_table, 'Approved');

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();
        $file_size                    = 15;
        $new_version_resource         = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'file_properties'       => [
                    'file_name' => 'string',
                    'file_size' => $file_size,
                ],
                'should_lock_file'      => false,
                'approval_table_action' => 'empty',
            ]
        );
        $new_version_response         = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $new_version_response_json = json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotNull($new_version_response_json['upload_href']);

        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PATCH', $new_version_response_json['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($file_content))
        );

        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $new_version_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version       = json_decode($new_version_file_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_after_update = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version['last_update_date']
        );

        $this->assertTrue($new_version['has_approval_table']);

        $this->checkItemHasADisabledApprovalTable($items, 'POST F V AT E');
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThan($date_before_update_timestamp, $date_after_update_timestamp);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionItThrowsExceptionWhenUserSetApprovalTableOnItemWithoutApprovalTable(
        array $items,
    ): void {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V No AT');
        $file_to_update_id = $file_to_update['id'];

        $file_size            = 15;
        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'file_properties'       => [
                    'file_name' => 'string',
                    'file_size' => $file_size,
                ],
                'should_lock_file'      => false,
                'approval_table_action' => 'reset',
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
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
        $file_to_update    = $this->findItemByTitle($items, 'POST F V L');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version              = json_decode($current_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $this->assertNotNull($current_version['lock_info']);

        $file_size            = 15;
        $new_version_resource = json_encode(
            [
                'version_title'    => 'My new versionnn',
                'description'      => 'whatever',
                'file_properties'  => [
                    'file_name' => 'string',
                    'file_size' => $file_size,
                ],
                'should_lock_file' => false,
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $new_version_response_json = json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotNull($new_version_response_json['upload_href']);

        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', $new_version_response_json['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($file_content))
        );

        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $new_version_file_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version_file            = json_decode($new_version_file_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_after_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version_file['last_update_date']
        );
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);
        $this->assertNull($new_version_file['lock_info']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionAdminAlwaysCanUnlockAFile(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V UL Admin');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version              = json_decode($current_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $this->assertNotNull($current_version['lock_info']);

        $file_size            = 15;
        $new_version_resource = json_encode(
            [
                'version_title'    => 'My new versionnn',
                'description'      => 'whatever',
                'file_properties'  => [
                    'file_name' => 'string',
                    'file_size' => $file_size,
                ],
                'should_lock_file' => false,
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $new_version_response_json = json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotNull($new_version_response_json['upload_href']);

        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PATCH', $new_version_response_json['upload_href'])
                ->withHeader('Tus-Resumable', '1.0.0')
                ->withHeader('Content-Type', 'application/offset+octet-stream')
                ->withHeader('Upload-Offset', '0')
                ->withBody($this->stream_factory->createStream($file_content))
        );

        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset'));

        $new_version_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version_file            = json_decode($new_version_file_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_after_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version_file['last_update_date']
        );
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);
        $this->assertNull($new_version_file['lock_info']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionRegularUserCanNotUnlockFileLockedByOtherUser(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V L Admin');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version              = json_decode($current_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();
        $this->assertNotNull($current_version['lock_info']);

        $file_size            = 15;
        $new_version_resource = json_encode(
            [
                'version_title'    => 'My new versionnn',
                'description'      => 'whatever',
                'file_properties'  => [
                    'file_name' => 'string',
                    'file_size' => $file_size,
                ],
                'should_lock_file' => false,
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_files/' . $file_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(403, $new_version_response->getStatusCode());
        $this->assertNotNull($current_version['lock_info']);

        $new_version_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version_file            = json_decode($new_version_file_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $date_after_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version_file['last_update_date']
        );
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertEquals($date_before_update_timestamp, $date_after_update_timestamp);
        $this->assertEquals($new_version_file['lock_info'], $current_version['lock_info']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPutBasicHardcodedMetadata(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'PUT F');
        $file_to_update_id = $file_to_update['id'];

        $this->assertEquals('PUT F', $file_to_update['title']);
        $this->assertEquals('', $file_to_update['description']);
        $this->assertEquals($this->docman_user_id, $file_to_update['owner']['id']);

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $file_to_update['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $put_resource = [
            'id'                => $file_to_update_id,
            'title'             => 'PUT F New Title',
            'description'       => '',
            'owner_id'          => $this->test_user_1_id,
            'status'            => 'none',
        ];

        $updated_metadata_file_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_files/' . $file_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $updated_metadata_file_response_with_rest_read_only_user->getStatusCode());

        $updated_metadata_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_files/' . $file_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource)))
        );

        $this->assertEquals(200, $updated_metadata_file_response->getStatusCode());

        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($new_version_response->getStatusCode(), 200);

        $new_version = json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $date_after_update          = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version['last_update_date']
        );
        $last_update_date_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $last_update_date_timestamp);

        $this->assertEquals('PUT F New Title', $new_version['title']);
        $this->assertEquals('', $new_version['description']);
        $this->assertEquals($this->test_user_1_id, $new_version['owner']['id']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPutFileMetadataThrowsExceptionWhenStatusIsGiven(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'PUT F Status');
        $file_to_update_id = $file_to_update['id'];

        $this->assertEquals('PUT F Status', $file_to_update['title']);
        $this->assertEquals('', $file_to_update['description']);
        $this->assertEquals($this->docman_user_id, $file_to_update['owner']['id']);

        $put_resource = [
            'id'                => $file_to_update_id,
            'title'             => 'FAIL',
            'description'       => 'Danger ! Danger !',
            'owner_id'          => 101,
            'status'            => 'approved',
            'obsolescence_date' => '0',
        ];

        $updated_metadata_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_files/' . $file_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource)))
        );

        $this->assertEquals(400, $updated_metadata_file_response->getStatusCode());
        $this->assertStringContainsString(
            'not activated',
            json_decode($updated_metadata_file_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPutFileMetadataThrowsExceptionWhenObsolescenceDateIsGiven(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'PUT F OD');
        $file_to_update_id = $file_to_update['id'];

        $this->assertEquals('PUT F OD', $file_to_update['title']);
        $this->assertEquals('', $file_to_update['description']);
        $this->assertEquals($this->docman_user_id, $file_to_update['owner']['id']);

        $put_resource = [
            'id'                => $file_to_update_id,
            'title'             => 'FAIL',
            'description'       => 'Danger ! Danger !',
            'owner_id'          => 101,
            'status'            => 'none',
            'obsolescence_date' => '2038-02-02',
        ];

        $updated_metadata_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_files/' . $file_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource)))
        );

        $this->assertEquals(400, $updated_metadata_file_response->getStatusCode());
        $this->assertStringContainsString(
            'does not support',
            json_decode($updated_metadata_file_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsId($id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_files/' . $id),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsLock($id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'docman_files/' . $id . '/lock'), BaseTestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsVersion(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_files/' . $id . '/version'),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsMetadata(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_files/' . $id . '/metadata'),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootIdWithUserRESTReadOnlyAdmin')]
    public function testAllAvailableOptionsWithRESTReadOnlyUser(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_files/' . $id),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_files/' . $id . '/metadata'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_files/' . $id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_files/' . $id . '/version'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    private function createANewFileAndGetItsId(int $parent_folder_id, string $title): int
    {
        $query = json_encode(
            [
                'title'           => $title,
                'parent_id'       => $parent_folder_id,
                'type'            => 'file',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 0],
            ]
        );

        $created_file = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $parent_folder_id . '/files')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $created_file->getStatusCode());
        $created_file_json = json_decode($created_file->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNull($created_file_json['file_properties']);

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $created_file_json['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', json_decode($file_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['type']);

        return $created_file_json['id'];
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testGetDocumentVersions(array $items): void
    {
        $file_with_versions = $this->findItemByTitle($items, 'POST F V');

        $file_id = $file_with_versions['id'];

        $project_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_files/' . $file_id . '/versions'),
            BaseTestDataBuilder::ADMIN_USER_NAME,
        );

        $json_history = json_decode($project_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('', $json_history[1]['name']);
        self::assertEquals('My new versionnn', $json_history[0]['name']);
    }
}
