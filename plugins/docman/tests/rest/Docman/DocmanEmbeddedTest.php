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

declare(strict_types = 1);

namespace Tuleap\Docman\Test\rest\Docman;

require_once __DIR__ . '/../../../vendor/autoload.php';

use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;

final class DocmanEmbeddedTest extends DocmanTestExecutionHelper
{
    /**
     * @depends testGetRootId
     */
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

    /**
     * @depends testGetRootIdWithUserRESTReadOnlyAdmin
     */
    public function testGetDocumentItemsWithUserRESTReadOnlyAdmin(int $root_id): array
    {
        $root_folder = $this->loadRootFolderContent(
            $root_id,
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $items         = $this->loadFolderContent($root_id, 'Embedded', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $folder        = $this->findItemByTitle($root_folder, 'Embedded');
        $items_id      = $folder['id'];
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Embedded', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Embedded', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $version_items = $this->loadFolderContent($items_id, 'POST Embedded version', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $put_items     = $this->loadFolderContent($items_id, 'PUT HM Embedded', REST_TestDataBuilder::TEST_BOT_USER_NAME);

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

    /**
     * @depends testGetRootId
     */
    public function testMoveEmbeddedDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'               => 'My new embedded',
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $embedded_id = $this->createEmbeddedFileAndReturnItsId($root_id, $query);

        $response_folder_creation_with_rest_read_only_user = $this->getResponseByName(
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
            $this->client->post(
                'docman_folders/' . urlencode((string) $root_id) . '/folders',
                null,
                json_encode(['title' => 'Embedded cut folder'])
            )
        );
        $this->assertEquals(403, $response_folder_creation_with_rest_read_only_user->getStatusCode());

        $response_folder_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . urlencode((string) $root_id) . '/folders',
                null,
                json_encode(['title' => 'Embedded cut folder'])
            )
        );

        $this->assertEquals(201, $response_folder_creation->getStatusCode());
        $folder_id = $response_folder_creation->json()['id'];

        $move_response_with_rest_read_only_user = $this->getResponseByName(
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
            $this->client->patch(
                'docman_embedded_files/' . urlencode((string) $embedded_id),
                null,
                json_encode(['move' => ['destination_folder_id' => $folder_id]])
            )
        );
        $this->assertEquals(403, $move_response_with_rest_read_only_user->getStatusCode());

        $move_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch(
                'docman_embedded_files/' . urlencode((string) $embedded_id),
                null,
                json_encode(['move' => ['destination_folder_id' => $folder_id]])
            )
        );
        $this->assertEquals(200, $move_response->getStatusCode());

        $moved_item_response = $this->getResponse(
            $this->client->get('docman_items/' . urlencode((string) $embedded_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals($folder_id, $moved_item_response->json()['parent_id']);

        $moved_item_response_with_rest_read_only_user = $this->getResponse(
            $this->client->get('docman_items/' . urlencode((string) $embedded_id)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals($folder_id, $moved_item_response_with_rest_read_only_user->json()['parent_id']);

        $delete_response_with_rest_read_only_user = $this->getResponse(
            $this->client->delete('docman_folders/' . urlencode((string) $folder_id)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $delete_response_with_rest_read_only_user->getStatusCode());

        $this->getResponse(
            $this->client->delete('docman_folders/' . urlencode((string) $folder_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testUpdatePermissionsEmbeddedDocument(int $root_id) : void
    {
        $embedded_doc_id = $this->createEmbeddedFileAndReturnItsId(
            $root_id,
            json_encode(['title' => 'Embedded update permissions', 'embedded_properties' => ['content' => 'content']])
        );

        $project_members_identifier = $this->project_id . '_3';
        $put_body = json_encode(
            ['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]]
        );

        $permission_update_response_with_rest_read_only_user = $this->getResponse(
            $this->client->put(
                'docman_embedded_files/' . urlencode((string) $embedded_doc_id) . '/permissions',
                null,
                $put_body
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $permission_update_response_with_rest_read_only_user->getStatusCode());

        $permission_update_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                'docman_embedded_files/' . urlencode((string) $embedded_doc_id) . '/permissions',
                null,
                $put_body
            )
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());

        $embedded_doc_representation_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . urlencode((string) $embedded_doc_id))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());
        $permissions_for_groups_representation = $embedded_doc_representation_response->json()['permissions_for_groups'];
        $this->assertEmpty($permissions_for_groups_representation['can_read']);
        $this->assertEmpty($permissions_for_groups_representation['can_write']);
        $this->assertCount(1, $permissions_for_groups_representation['can_manage']);
        $this->assertEquals($project_members_identifier, $permissions_for_groups_representation['can_manage'][0]['id']);

        $delete_response_with_rest_read_only_user = $this->getResponse(
            $this->client->delete('docman_embedded_files/' . urlencode((string) $embedded_doc_id)),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $delete_response_with_rest_read_only_user->getStatusCode());

        $this->getResponse(
            $this->client->delete('docman_embedded_files/' . urlencode((string) $embedded_doc_id)),
            DocmanDataBuilder::ADMIN_USER_NAME
        );
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteThrowsAnErrorWhenUserHasNotPermissionToDeleteTheEmbedded(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE E RO');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_embedded_files/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteThrowAPermissionErrorWhenTheEmbeddedIsLockedByAnotherUser(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE E L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_embedded_files/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteIsProceedWhenFileIsLockedAndUserIsAdmin(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE E L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_embedded_files/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItDoesNotDeletesAnEmbeddedFileWithRESTReadOnlyUserNotInvolvedInProject(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE E');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponse(
            $this->client->delete('docman_embedded_files/' . $file_to_delete_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItDeletesAnEmbeddedFile(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE E');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_embedded_files/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostLocksAnEmbeddedFile(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK E');
        $locked_document_id = $locked_document['id'];

        $post_response_with_rest_read_only_user = $this->getResponse(
            $this->client->post('docman_embedded_files/' . $locked_document_id . "/lock"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $post_response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_embedded_files/' . $locked_document_id . "/lock")
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $locked_document_id)
        );

        $document = $response->json();
        $this->assertEquals($document['lock_info']["locked_by"]["username"], DocmanDataBuilder::ADMIN_USER_NAME);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteLockAnEmbeddedFile(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK E');
        $locked_document_id = $locked_document['id'];

        $delete_response_with_rest_read_only_user = $this->getResponse(
            $this->client->delete('docman_embedded_files/' . $locked_document_id . "/lock"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $delete_response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_embedded_files/' . $locked_document_id . "/lock")
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $locked_document_id)
        );

        $document = $response->json();
        $this->assertEquals($document['lock_info'], null);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionWithRESTReadOnlyUserDoesNotCreateAnEmbeddedFile(array $items): void
    {
        $title             = 'POST E V';
        $file_to_update    = $this->findItemByTitle($items, $title);
        $file_to_update_id = $file_to_update['id'];

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                "embedded_properties" => [
                    "content" => "my new content"
                ],
                "should_lock_file"    => false
            ]
        );
        $new_version_response = $this->getResponse(
            $this->client->post('docman_embedded_files/' . $file_to_update_id . "/version", null, $new_version_resource),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $new_version_response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionItCreatesAnEmbeddedFile(array $items): void
    {
        $title             = 'POST E V';
        $file_to_update    = $this->findItemByTitle($items, $title);
        $file_to_update_id = $file_to_update['id'];

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                "embedded_properties" => [
                    "content" => "my new content"
                ],
                "should_lock_file"    => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_embedded_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $this->checkItemHasADisabledApprovalTable($items, $title);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
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
                "embedded_properties"   => [
                    "content" => "my new content"
                ],
                "should_lock_file"      => false,
                'approval_table_action' => 'copy'
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_embedded_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $this->checkItemHasAnApprovalTable($items, $title, 'Approved');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
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
                "embedded_properties"   => [
                    "content" => "my new content"
                ],
                "should_lock_file"      => false,
                'approval_table_action' => 'reset'
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_embedded_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $this->checkItemHasAnApprovalTable($items, $title, 'Not yet');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
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
                "embedded_properties"   => [
                    "content" => "my new content"
                ],
                "should_lock_file"      => false,
                'approval_table_action' => 'empty'
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_embedded_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $new_version_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $this->checkItemHasADisabledApprovalTable($items, $title);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionItThrowsExceptionWhenUserSetApprovalTableOnItemWithoutApprovalTable(
        array $items
    ): void {
        $file_to_update    = $this->findItemByTitle($items, 'POST E V No AT');
        $file_to_update_id = $file_to_update['id'];

        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                "embedded_properties"   => [
                    "content" => "my new content"
                ],
                "should_lock_file"      => false,
                'approval_table_action' => 'reset'
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_embedded_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(400, $new_version_response->getStatusCode());
        $this->assertStringContainsString(
            "does not have an approval table",
            $new_version_response->json()["error"]['i18n_error_message']
        );
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionCanUnlockAFile(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST E V L');
        $file_to_update_id = $file_to_update['id'];

        $this->assertNotNull($file_to_update['lock_info']);

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                "embedded_properties" => [
                    "content" => "my new content"
                ],
                "should_lock_file"    => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_embedded_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $response = $this->getResponse(
            $this->client->get('docman_items/' . $file_to_update_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('embedded', $response->json()['type']);
        $this->assertNull($response->json()['lock_info']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionAdminAlwaysCanUnlockAFile(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST E V UL Admin');
        $file_to_update_id = $file_to_update['id'];

        $this->assertNotNull($file_to_update['lock_info']);

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                "embedded_properties" => [
                    "content" => "my new content"
                ],
                "should_lock_file"    => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_embedded_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('embedded', $response->json()['type']);
        $this->assertNull($response->json()['lock_info']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionRegularUserCanNotUnlockFileLockedByOtherUser(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST E V L Admin');
        $file_to_update_id = $file_to_update['id'];

        $this->assertNotNull($file_to_update['lock_info']);

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                "embedded_properties" => [
                    "content" => "my new content"
                ],
                "should_lock_file"    => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_embedded_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(403, $new_version_response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
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
            'status'            => 'none'
        ];

        $updated_metadata_file_response_with_rest_read_only_user = $this->getResponse(
            $this->client->put('docman_embedded_files/' . $item_to_update_id . '/metadata', null, $put_resource),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $updated_metadata_file_response_with_rest_read_only_user->getStatusCode());

        $updated_metadata_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->put('docman_embedded_files/' . $item_to_update_id . '/metadata', null, $put_resource)
        );

        $this->assertEquals(200, $updated_metadata_file_response->getStatusCode());

        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $item_to_update_id)
        );

        $this->assertEquals($new_version_response->getStatusCode(), 200);

        $new_version = $new_version_response->json();

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

    /**
     * @depends testGetRootId
     */
    public function testOptionsMetadata(int $id): void
    {
        $response = $this->getResponse(
            $this->client->options('docman_embedded_files/' . $id . '/metadata'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptions(int $id): void
    {
        $response = $this->getResponse($this->client->options('docman_embedded_files/' . $id), REST_TestDataBuilder::ADMIN_USER_NAME);
        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

        /**
     * @depends testGetRootId
     */
    public function testOptionsLock($id): void
    {
        $response = $this->getResponse($this->client->options('docman_embedded_files/' . $id . '/lock'), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptionsVersion(int $id): void
    {
        $response = $this->getResponse(
            $this->client->options('docman_embedded_files/' . $id . '/version'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testGetRootIdWithUserRESTReadOnlyAdmin
     */
    public function testAllAvailableOptionsWithRESTReadOnlyUser(int $id): void
    {
        $response = $this->getResponse(
            $this->client->options('docman_embedded_files/' . $id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->options('docman_embedded_files/' . $id . '/metadata'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->options('docman_embedded_files/' . $id . '/lock'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->options('docman_embedded_files/' . $id . '/version'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @return mixed
     */
    private function createEmbeddedFileAndReturnItsId(int $root_id, string $query)
    {
        $post_response_with_rest_read_only_user = $this->getResponse(
            $this->client->post('docman_folders/' . $root_id . '/embedded_files', null, $query),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $post_response_with_rest_read_only_user->getStatusCode());

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/embedded_files', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $get_response_with_rest_read_only_user = $this->getResponse(
            $this->client->get($response1->json()['uri']),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(200, $get_response_with_rest_read_only_user->getStatusCode());

        $embedded_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );

        $this->assertEquals(200, $embedded_item_response->getStatusCode());
        $this->assertEquals('embedded', $embedded_item_response->json()['type']);

        return $response1->json()['id'];
    }
}
