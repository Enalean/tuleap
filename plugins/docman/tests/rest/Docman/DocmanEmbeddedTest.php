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
use Tuleap\Docman\Test\rest\Helper\DocmanDataBuildCommon;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;

class DocmanEmbeddedTest extends DocmanTestExecutionHelper
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
        $patch_items   = $this->loadFolderContent($items_id, 'PATCH Embedded');
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Embedded');
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Embedded');


        return array_merge(
            $root_folder,
            $folder,
            $items,
            $patch_items,
            $deleted_items,
            $lock_items
        );
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchAEmbeddedWithApprovalTableCopyAction(array $items): void
    {
        $item_name = 'PATCH E AT C';
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Approved');
        $embedded = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'title'                 => 'embedded AT C',
                'should_lock_file'      => false,
                'embedded_properties'   => ['content' => 'my new content'],
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Approved');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchEmbeddedWithApprovalTableResetAction(array $items): void
    {
        $item_name = 'PATCH E AT R';
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Approved');
        $embedded = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'title'                 => 'embedded AT R',
                'should_lock_file'      => false,
                'embedded_properties'   => ['content' => 'my new content'],
                'approval_table_action' => 'reset'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Not yet');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchEmbeddedWithApprovalTableEmptyAction(array $items): void
    {
        $item_name = 'PATCH E AT E';
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Approved');
        $embedded = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'title'                 => 'embedded AT E',
                'should_lock_file'      => false,
                'embedded_properties'   => ['content' => 'my new content'],
                'approval_table_action' => 'empty'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->checkItemHasADisabledApprovalTable($items, $item_name);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsAnApprovalTableForTheItemAndNoApprovalAction(array $items): void
    {
        $item_name = 'PATCH E AT';
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Approved');
        $embedded = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'title'               => 'embedded AT C',
                'should_lock_file'    => false,
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("has an approval table", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsNOTApprovalTableWhileThereIsApprovalAction(array $items): void
    {
        $embedded = $this->findItemByTitle($items, 'PATCH E NO AT');

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'title'                 => 'embedded NO AT',
                'embedded_properties'   => ['content' => 'my new content'],
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("does not have an approval table", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPATCHThrowsAnExceptionWhenPatchIsCalledOnANonEmbeddedItem(array $items): void
    {
        $item = $this->findItemByTitle($items, 'PATCH Embedded');

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'should_lock_file'    => false,
                'title'               => 'empty',
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_embedded_files/' . $item["id"],
                null,
                $put_resource
            )
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchAdminShouldAlwaysBeAbleToUnlockADocument(array $items): void
    {
        $locked_embedded = $this->findItemByTitle($items, 'PATCH E RL');

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'should_lock_file'    => false,
                'title'               => 'embedded L',
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_embedded_files/' . $locked_embedded["id"],
                null,
                $put_resource
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchRegularUserCanNotUnlockADocumentLockedByAnOtherUser(array $items): void
    {
        $file = $this->findItemByTitle($items, 'PATCH E AL');

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'should_lock_file'    => false,
                'title'               => 'embedded L',
                'embedded_properties' => ['content' => 'my new content']
            ]
        );


        $response = $this->getResponseByName(
            DocmanDataBuildCommon::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch(
                'docman_embedded_files/' . $file['id'],
                null,
                $put_resource
            )
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchEmbeddedDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'               => 'My new embedded',
                'parent_id'           => $root_id,
                'type'                => 'embedded',
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $embedded_id = $this->createEmbeddedFileAndReturnItsId($root_id, $query);

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'should_lock_file'    => false,
                'title'               => 'New title',
                'description'         => 'new description',
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $embedded_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $item     = $response->json();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('embedded', $item['type']);
        $this->assertEquals(null, $item['lock_info']);
        $this->assertFalse($item['has_approval_table']);
        $this->assertFalse($item['is_approval_table_enabled']);
        $this->assertNull($item['approval_table']);
    }


    /**
     * @depends testGetRootId
     */
    public function testPatchAndLockAnEmbeddedDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'               => 'My second embedded',
                'parent_id'           => $root_id,
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $embedded_id = $this->createEmbeddedFileAndReturnItsId($root_id, $query);

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'title'               => 'My second embedded',
                'should_lock_file'    => true,
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded_id, null, $put_resource)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $embedded_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('embedded', $response->json()['type']);
        $this->assertEquals(110, $response->json()['lock_info']["locked_by"]["id"]);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchEmbeddedFileWithStatusThrows400WhenStatusIsNotEnabledForProject(int $root_id): void
    {
        $query = json_encode(
            [
                'title'               => 'Embedded file 403 v2',
                'parent_id'           => $root_id,
                'embedded_properties' => ['content' => 'I am content']
            ]
        );

        $embedded_id = $this->createEmbeddedFileAndReturnItsId($root_id, $query);

        $embedded_properties = [
            'content' => 'https://example.com'
        ];
        $put_resource        = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'title'               => 'Embedded file 403 v2',
                'status'              => 'approved',
                'should_lock_file'    => false,
                'embedded_properties' => $embedded_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded_id, null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("Status", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchEmbeddedFileWithStatusThrows400WhenObsolescenceDateIsNotEnabledForProject(
        int $root_id
    ): void {
        $query = json_encode(
            [
                'title'               => 'Embedded file 403',
                'parent_id'           => $root_id,
                'embedded_properties' => ['content' => 'I am content']
            ]
        );

        $item_id = $this->createEmbeddedFileAndReturnItsId($root_id, $query);

        $embedded_properties = [
            'content' => 'https://example.com',
        ];
        $patch_resource      = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'title'               => 'My new embedded with fail obsolescence date',
                'obsolescence_date'   => '2038-12-31',
                'should_lock_file'    => false,
                'embedded_properties' => $embedded_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $item_id, null, $patch_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('obsolescence', $response->json()["error"]['i18n_error_message']);
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
        $this->assertEquals($document['lock_info'] ["locked_by"]["username"], DocmanDataBuilder::ADMIN_USER_NAME);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteLockAnEmbeddedFile(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK E');
        $locked_document_id = $locked_document['id'];

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
    public function testOptionsLock(int $id): void
    {
        $response = $this->getResponse($this->client->options('docman_embedded_files/' . $id . '/lock'), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @param int    $root_id
     * @param string $query
     *
     * @return mixed
     */
    private function createEmbeddedFileAndReturnItsId(int $root_id, string $query)
    {
        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/embedded_files', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $embedded_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $embedded_item_response->getStatusCode());
        $this->assertEquals('embedded', $embedded_item_response->json()['type']);

        return $response1->json()['id'];
    }
}
