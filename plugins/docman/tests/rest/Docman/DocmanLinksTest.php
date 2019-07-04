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
        $link = $this->findItemByTitle($items, 'PATCH L NO AT');

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'title'                 => 'PATCH L NO AT',
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
        $item = $this->findItemByTitle($items, 'PATCH Link');

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => 'empty',
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
        $item = $this->findItemByTitle($items, 'PATCH L RL');

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'title'            => 'PATCH L RL',
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
        $file = $this->findItemByTitle($items, 'PATCH L AL');

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => 'PATCH L AL',
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

        $embedded_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $embedded_item_response->getStatusCode());
        $this->assertEquals('link', $embedded_item_response->json()['type']);

        return $response1->json()['id'];
    }
}
