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

class DocmanLinksTest extends DocmanTestExecutionHelper
{
    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForAdminUser(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items         = $this->loadFolderContent($root_id, 'Link');
        $folder        = $this->findItemByTitle($root_folder, 'Link');
        $items_id      = $folder['id'];
        $patch_items   = $this->loadFolderContent($items_id, 'PATCH Link');
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Link');
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Link');
        $post_version  = $this->loadFolderContent($items_id, 'POST Link version');
        $put_metadata  = $this->loadFolderContent($items_id, 'PUT HM Link');

        return array_merge(
            $root_folder,
            $folder,
            $items,
            $patch_items,
            $deleted_items,
            $lock_items,
            $post_version,
            $put_metadata
        );
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchALinkWithApprovalTableCopyAction(array $items): void
    {
        $item_name = 'PATCH L AT C';
        $link      = $this->findItemByTitle($items, $item_name);
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Approved');

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'title'                 => $item_name,
                'should_lock_file'      => false,
                'link_properties'       => ['link_url' => 'https://example.com'],
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_links/' . $link['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Approved');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchLinkWithApprovalTableResetAction(array $items): void
    {
        $item_name = 'PATCH L AT R';
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Approved');
        $link = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'title'                 => $item_name,
                'should_lock_file'      => false,
                'link_properties'       => ['link_url' => 'https://example.com'],
                'approval_table_action' => 'reset'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_links/' . $link['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Not yet');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchLinkWithApprovalTableEmptyAction(array $items): void
    {
        $item_name = 'PATCH L AT E';
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Approved');
        $link = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'title'                 => $item_name,
                'should_lock_file'      => false,
                'link_properties'       => ['link_url' => 'https://example.com'],
                'approval_table_action' => 'empty'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_links/' . $link['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->checkItemHasADisabledApprovalTable($items, $item_name);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsAnApprovalTableForTheItemAndNoApprovalAction(array $items): void
    {
        $item_name = 'PATCH L AT';
        $this->checkItemHasAnApprovalTable($items, $item_name, 'Approved');
        $link = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => $item_name,
                'should_lock_file' => false,
                'link_properties'  => ['link_url' => 'https://example.com']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_links/' . $link['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("has an approval table", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsNOTApprovalTableWhileThereIsApprovalAction(array $items): void
    {
        $item_name = 'PATCH L NO AT';
        $link  = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'title'                 => $item_name,
                'link_properties'       => ['link_url' => 'https://example.com'],
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_links/' . $link['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("does not have an approval table", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPATCHThrowsAnExceptionWhenPatchIsCalledOnANonLinkItem(array $items): void
    {
        $item_name = 'PATCH Link';
        $item  = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => $item_name,
                'should_lock_file' => false,
                'link_properties'  => ['link_url' => 'https://example.com']
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_links/' . $item["id"],
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
        $item_name  = 'PATCH L RL';
        $item = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'title'            => $item_name,
                'link_properties'  => ['link_url' => 'https://example.com']
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_links/' . $item["id"],
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
        $item_name  = 'PATCH L AL';
        $file = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => $item_name,
                'should_lock_file' => false,
                'link_properties'  => ['link_url' => 'https://example.com']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuildCommon::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch(
                'docman_links/' . $file['id'],
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
    public function testPatchLinkDocument(int $root_id): void
    {
        $link_properties = ['link_url' => 'https://example.com'];
        $query           = json_encode(
            [
                'title'           => 'My new link 403',
                'parent_id'       => $root_id,
                'link_properties' => $link_properties
            ]
        );

        $link_id = $this->createLinkAndReturnItsId($root_id, $query);

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => 'My new link 403',
                'should_lock_file' => false,
                'link_properties'  => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $link_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $link_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $item     = $response->json();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $item['type']);
        $this->assertEquals(null, $item['lock_info']);
        $this->assertFalse($item['has_approval_table']);
        $this->assertFalse($item['is_approval_table_enabled']);
        $this->assertNull($item['approval_table']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchAndLockALinkDocument(int $root_id): void
    {
        $link_properties = ['link_url' => 'https://example.com'];
        $query           = json_encode(
            [
                'title'           => 'My new link with fail obsolescence date',
                'parent_id'       => $root_id,
                'link_properties' => $link_properties
            ]
        );

        $link_id = $this->createLinkAndReturnItsId($root_id, $query);

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => 'My new link with fail obsolescence date',
                'should_lock_file' => true,
                'link_properties'  => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $link_id, null, $put_resource)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $link_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $response->json()['type']);
        $this->assertEquals(110, $response->json()['lock_info']["locked_by"]["id"]);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchLinkWithStatusThrows400WhenStatusIsNotEnabledForProject(int $root_id): void
    {
        $link_properties = ['link_url' => 'https://example.com'];
        $query           = json_encode(
            [
                'title'           => 'My new link 3',
                'parent_id'       => $root_id,
                'link_properties' => $link_properties
            ]
        );

        $link_id = $this->createLinkAndReturnItsId($root_id, $query);

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => 'New title !!!',
                'description'      => 'I have a description now',
                'should_lock_file' => false,
                'link_properties'  => $link_properties,
                'status'           => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $link_id, null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("Status", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchLinkWithStatusThrows400WhenObsolescenceDateIsNotEnabledForProject(
        int $root_id
    ): void {
        $link_properties = ['link_url' => 'https://example.com'];
        $query           = json_encode(
            [
                'title'           => 'My second link',
                'parent_id'       => $root_id,
                'type'            => 'links',
                'link_properties' => $link_properties
            ]
        );

        $item_id = $this->createLinkAndReturnItsId($root_id, $query);

        $patch_resource = json_encode(
            [
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'title'             => 'My second link',
                'should_lock_file'  => true,
                'link_properties'   => $link_properties,
                'obsolescence_date' => '2038-12-31',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $item_id, null, $patch_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('obsolescence', $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteThrowsAnErrorWhenUserHasNotPermissionToDeleteTheLink(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE L RO');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_links/' . $item_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteThrowAPermissionErrorWhenTheLinkIsLockedByAnotherUser(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE L L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_links/' . $item_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteIsProceedWhenItemIsLockedAndUserIsAdmin(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE L L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_links/' . $item_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($item_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItDeletesALink(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_links/' . $item_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($item_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostLocksALink(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK L');
        $locked_document_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_links/' . $locked_document_id . "/lock")
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
    public function testDeleteLockALink(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK L');
        $locked_document_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_links/' . $locked_document_id . "/lock")
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
    public function testPostVersionItCreatesAnEmbeddedFile(array $items): void
    {
        $title             = 'POST L V';
        $item_to_update    = $this->findItemByTitle($items, $title);
        $item_to_update_id = $item_to_update['id'];

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                'link_properties'  => ['link_url' => 'https://example.com'],
                "should_lock_file"    => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_links/' . $item_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $this->checkItemHasADisabledApprovalTable($items, $title);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionCopyThePreviousApprovalTableStatus(array $items): void
    {
        $title             = 'POST L V AT C';
        $item_to_update    = $this->findItemByTitle($items, $title);
        $item_to_update_id = $item_to_update['id'];

        $this->checkItemHasAnApprovalTable($items, $title, 'Approved');

        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'link_properties'  => ['link_url' => 'https://example.com'],
                "should_lock_file"      => false,
                'approval_table_action' => 'copy'
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_links/' . $item_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $this->checkItemHasAnApprovalTable($items, $title, 'Approved');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionResetTheApprovalTableStatus(array $items): void
    {
        $title             = 'POST L V AT R';
        $item_to_update    = $this->findItemByTitle($items, $title);
        $item_to_update_id = $item_to_update['id'];

        $this->checkItemHasAnApprovalTable($items, $title, 'Approved');

        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'link_properties'  => ['link_url' => 'https://example.com'],
                "should_lock_file"      => false,
                'approval_table_action' => 'reset'
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_links/' . $item_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $this->checkItemHasAnApprovalTable($items, $title, 'Not yet');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionDisableApprovalTable(array $items): void
    {
        $title             = 'POST L V AT E';
        $item_to_update    = $this->findItemByTitle(
            $items,
            $title
        );
        $item_to_update_id = $item_to_update['id'];

        $this->checkItemHasAnApprovalTable($items, $title, 'Approved');

        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'link_properties'  => ['link_url' => 'https://example.com'],
                "should_lock_file"      => false,
                'approval_table_action' => 'empty'
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_links/' . $item_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $item_to_update_id)
        );
        $this->assertEquals($new_version_response->getStatusCode(), 200);

        $this->checkItemHasADisabledApprovalTable($items, $title);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionItThrowsExceptionWhenUserSetApprovalTableOnItemWithoutApprovalTable(
        array $items
    ): void {
        $item_to_update    = $this->findItemByTitle($items, 'POST L V No AT');
        $item_to_update_id = $item_to_update['id'];

        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'link_properties'  => ['link_url' => 'https://example.com'],
                "should_lock_file"      => false,
                'approval_table_action' => 'reset'
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_links/' . $item_to_update_id . "/version", null, $new_version_resource)
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
        $item_to_update    = $this->findItemByTitle($items, 'POST L V L');
        $item_to_update_id = $item_to_update['id'];

        $this->assertNotNull($item_to_update['lock_info']);

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                'link_properties'  => ['link_url' => 'https://example.com'],
                "should_lock_file"    => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_links/' . $item_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $response = $this->getResponse(
            $this->client->get('docman_items/' . $item_to_update_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $response->json()['type']);
        $this->assertNull($response->json()['lock_info']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionAdminAlwaysCanUnlockAFile(array $items): void
    {
        $item_to_update    = $this->findItemByTitle($items, 'POST L V UL Admin');
        $item_to_update_id = $item_to_update['id'];

        $this->assertNotNull($item_to_update['lock_info']);

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                'link_properties'  => ['link_url' => 'https://example.com'],
                "should_lock_file"    => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_links/' . $item_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $item_to_update_id)
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $response->json()['type']);
        $this->assertNull($response->json()['lock_info']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionRegularUserCanNotUnlockFileLockedByOtherUser(array $items): void
    {
        $item_to_update    = $this->findItemByTitle($items, 'POST L V L Admin');
        $item_to_update_id = $item_to_update['id'];

        $this->assertNotNull($item_to_update['lock_info']);

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                'link_properties'  => ['link_url' => 'https://example.com'],
                "should_lock_file"    => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_links/' . $item_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(403, $new_version_response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPutBasicHardcodedMetadata(array $items): void
    {
        $item_to_update    = $this->findItemByTitle($items, 'PUT L');
        $item_to_update_id = $item_to_update['id'];

        $this->assertEquals('PUT L', $item_to_update['title']);
        $this->assertEquals('', $item_to_update['description']);
        $this->assertEquals(110, $item_to_update['owner']['id']);

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $item_to_update['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $put_resource = [
            'id'                => $item_to_update_id,
            'title'             => 'PUT L New Title',
            'description'       => 'Danger ! Danger !',
            'owner_id'          => 101,
            'obsolescence_date' => '0',
            'status'            => 'none'
        ];

        $updated_metadata_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->put('docman_links/' . $item_to_update_id . '/metadata', null, $put_resource)
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

        $this->assertEquals('PUT L New Title', $new_version['title']);
        $this->assertEquals('Danger ! Danger !', $new_version['description']);
        $this->assertEquals(101, $new_version['owner']['id']);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptionsMetadata(int $id): void
    {
        $response = $this->getResponse(
            $this->client->options('docman_files/' . $id . '/metadata'),
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
        $response = $this->getResponse($this->client->options('docman_links/' . $id), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptionsLock(int $id): void
    {
        $response = $this->getResponse($this->client->options('docman_links/' . $id . '/lock'), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptionsVersion(int $id): void
    {
        $response = $this->getResponse(
            $this->client->options('docman_links/' . $id . '/version'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @param int    $root_id
     * @param string $query
     *
     * @return mixed
     */
    private function createLinkAndReturnItsId(int $root_id, string $query)
    {
        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/links', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $item_response->getStatusCode());
        $this->assertEquals('link', $item_response->json()['type']);

        return $response1->json()['id'];
    }
}
