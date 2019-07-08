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

class DocmanWikiTest extends DocmanTestExecutionHelper
{
    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForAdminUser(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items         = $this->loadFolderContent($root_id, 'Wiki');
        $folder        = $this->findItemByTitle($root_folder, 'Wiki');
        $items_id      = $folder['id'];
        $patch_items   = $this->loadFolderContent($items_id, 'PATCH Wiki');
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Wiki');
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Wiki');
        $post_items    = $this->loadFolderContent($items_id, 'POST Wiki');

        return array_merge(
            $root_folder,
            $folder,
            $items,
            $patch_items,
            $deleted_items,
            $lock_items,
            $post_items
        );
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsNOTApprovalTableWhileThereIsApprovalAction(array $items): void
    {
        $item_title = 'POST AT W';
        $wiki       = $this->findItemByTitle($items, $item_title);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'wiki_properties'       => ['page_name' => 'my new page name'],
                'title'                 => $item_title,
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_wikis/' . $wiki['id'], null, $put_resource)
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchAdminShouldAlwaysBeAbleToUnlockADocument(array $items): void
    {
        $item_name = 'PATCH W RL';
        $item      = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'title'            => $item_name,
                'wiki_properties'  => ['page_name' => 'my new page name']
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_wikis/' . $item["id"],
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
        $item_name = 'PATCH W AL';
        $item      = $this->findItemByTitle($items, $item_name);

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => $item_name,
                'should_lock_file' => false,
                'wiki_properties'  => ['page_name' => 'my new page name']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuildCommon::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch(
                'docman_wikis/' . $item['id'],
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
    public function testPatchWikiDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My new wiki',
                'parent_id'       => $root_id,
                'type'            => 'wiki',
                'wiki_properties' => ['page_name' => 'my new page name']
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/wikis', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $wiki_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $wiki_item_response->getStatusCode());
        $this->assertEquals('wiki', $wiki_item_response->json()['type']);
        $this->assertEquals('My new wiki', $wiki_item_response->json()['title']);
        $this->assertEquals('', $wiki_item_response->json()['description']);

        $wiki_id = $response1->json()['id'];

        $put_resource = json_encode(
            [
                'title'            => 'New wiki title :o',
                'description'      => 'Add a description',
                'should_lock_file' => false,
                'wiki_properties'  => ['page_name' => 'my updated page name']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_wikis/' . $wiki_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $wiki_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('wiki', $response->json()['type']);
        $this->assertEquals('New wiki title :o', $response->json()['title']);
        $this->assertEquals('Add a description', $response->json()['description']);
        $this->assertEquals(null, $response->json()['lock_info']);
        $this->assertEquals('my updated page name', $response->json()['wiki_properties']['page_name']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchWikiWithStatusThrows400WhenStatusIsNotEnabledForProject(int $root_id): void
    {
        $title = 'wiki with status';
        $query = json_encode(
            [
                'title'           => $title,
                'parent_id'       => $root_id,
                'wiki_properties' => ['page_name' => 'my updated page name']
            ]
        );

        $wiki_id = $this->createWikiAndReturnItsId($root_id, $query);

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => $title,
                'description'      => 'I have a description now',
                'should_lock_file' => false,
                'wiki_properties'  => ['page_name' => 'my updated page name'],
                'status'           => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_wikis/' . $wiki_id, null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("Status", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchWikiWithStatusThrows400WhenObsolescenceDateIsNotEnabledForProject(
        int $root_id
    ): void {
        $title = 'wiki with validity date';
        $query = json_encode(
            [
                'title'           => $title,
                'parent_id'       => $root_id,
                'wiki_properties' => ['page_name' => 'my updated page name']
            ]
        );

        $item_id = $this->createWikiAndReturnItsId($root_id, $query);

        $patch_resource = json_encode(
            [
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'title'             => $title,
                'should_lock_file'  => true,
                'wiki_properties'   => ['page_name' => 'my updated page name'],
                'obsolescence_date' => '2038-12-31',
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_wikis/' . $item_id, null, $patch_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('obsolescence', $response->json()["error"]['i18n_error_message']);
    }


    /**
     * @depends testGetRootId
     */
    public function testPatchDocumentAddLock(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My second wiki',
                'parent_id'       => $root_id,
                'type'            => 'wiki',
                'wiki_properties' => ['page_name' => 'my new page name']
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/wikis', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $wiki_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );

        $this->assertEquals(200, $wiki_item_response->getStatusCode());
        $this->assertEquals('wiki', $wiki_item_response->json()['type']);

        $wiki_id = $response1->json()['id'];

        $put_resource = json_encode(
            [
                'title'            => 'My second wiki',
                'should_lock_file' => true,
                'wiki_properties'  => ['page_name' => 'my new page name']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_wikis/' . $wiki_id, null, $put_resource)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $wiki_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('wiki', $response->json()['type']);
        $this->assertEquals(110, $response->json()['lock_info']["locked_by"]["id"]);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteThrowsAnErrorWhenUserHasNotPermissionToDeleteTheWiki(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE W RO');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_wikis/' . $item_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteThrowAPermissionErrorWhenTheWikiIsLockedByAnotherUser(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE W L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_wikis/' . $item_to_delete_id)
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
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE W L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_wikis/' . $item_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($item_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItDeletesAWiki(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE W');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_wikis/' . $item_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($item_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostLocksAWiki(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK W');
        $locked_document_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_wikis/' . $locked_document_id . "/lock")
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
    public function testDeleteLockAWiki(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK W');
        $locked_document_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_wikis/' . $locked_document_id . "/lock")
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
    public function testPostThrowsExceptionWhenThereIsNOTApprovalTableWhileThereIsApprovalAction(array $items): void
    {
        $item_title = 'POST AT W';
        $wiki       = $this->findItemByTitle($items, $item_title);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'wiki_properties'       => ['page_name' => 'my new page name'],
                'title'                 => $item_title,
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_wikis/' . $wiki['id'].'/version', null, $put_resource)
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My new wiki',
                'parent_id'       => $root_id,
                'type'            => 'wiki',
                'wiki_properties' => ['page_name' => 'my new page name']
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/wikis', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $wiki_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $wiki_item_response->getStatusCode());
        $this->assertEquals('wiki', $wiki_item_response->json()['type']);
        $this->assertEquals('My new wiki', $wiki_item_response->json()['title']);
        $this->assertEquals('', $wiki_item_response->json()['description']);

        $wiki_id = $response1->json()['id'];

        $put_resource = json_encode(
            [
                'should_lock_file' => false,
                'wiki_properties'  => ['page_name' => 'my updated page name']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_wikis/' . $wiki_id.'/version', null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $wiki_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('wiki', $response->json()['type']);
        $this->assertEquals(null, $response->json()['lock_info']);
        $this->assertEquals('my updated page name', $response->json()['wiki_properties']['page_name']);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptions(int $id): void
    {
        $response = $this->getResponse($this->client->options('docman_wikis/' . $id), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptionsLock(int $id): void
    {
        $response = $this->getResponse($this->client->options('docman_wikis/' . $id . '/lock'), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @param int    $root_id
     * @param string $query
     *
     * @return mixed
     */
    private function createWikiAndReturnItsId(int $root_id, string $query)
    {
        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/wikis', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $item_response->getStatusCode());
        $this->assertEquals('wiki', $item_response->json()['type']);

        return $response1->json()['id'];
    }
}
