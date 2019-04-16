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

namespace Tuleap\Docman\rest\v1;

use REST_TestDataBuilder;
use Tuleap\Docman\rest\DocmanBase;
use Tuleap\Docman\rest\DocmanDataBuilder;

require_once __DIR__ . '/../bootstrap.php';

class DocmanItemsTestFilesEmbeddedTest extends DocmanBase
{
    public function testGetRootId(): int
    {
        $project_response = $this->getResponse($this->client->get('projects/' . $this->project_id));

        $this->assertSame(200, $project_response->getStatusCode());

        $json_projects = $project_response->json();
        return $json_projects['additional_informations']['docman']['root_item']['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForAdminUser($root_id): array
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();

        $folder_content = $this->findItemByTitle($folder, 'folder 1');
        $folder_1_id    = $folder_content['id'];
        $response       = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_1_id . '/docman_items')
        );
        $items_folder_1 = $response->json();

        $folder_embedded = $this->findItemByTitle($folder, 'Folder B Embedded');
        $folder_embedded_id = $folder_embedded['id'];
        $response           = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_embedded_id . '/docman_items')
        );
        $items_embedded     = $response->json();

        $items = array_merge($items_folder_1, $items_embedded);

        $this->assertEquals(count($items), 12);

        return $items;
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchAEmbeddedWithApprovalTableCopyAction(array $items): void
    {
        $embedded = $this->findItemByTitle($items, 'embedded AT C');

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $embedded['id'])
        );

        $item_before_patch                = $response->json();
        $item_approval_table_before_patch = $item_before_patch['approval_table'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($item_approval_table_before_patch);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
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
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchEmbeddedWithApprovalTableResetAction(array $items): void
    {
        $embedded = $this->findItemByTitle($items, 'embedded AT R');

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $embedded['id'])
        );

        $item_before_patch                = $response->json();
        $item_approval_table_before_patch = $item_before_patch['approval_table'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($item_approval_table_before_patch);
        $this->assertEquals($item_approval_table_before_patch ["approval_state"], 'Approved');

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
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
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchEmbeddedWithApprovalTableEmptyAction(array $items): void
    {
        $embedded = $this->findItemByTitle($items, 'embedded AT E');

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $embedded['id'])
        );

        $item_before_patch                = $response->json();
        $item_approval_table_before_patch = $item_before_patch['approval_table'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($item_approval_table_before_patch);
        $this->assertEquals($item_approval_table_before_patch['approval_state'], 'Approved');

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
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
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsAnApprovalTableForTheItemAndNoApprovalAction(array $items): void
    {
        $embedded = $this->findItemByTitle($items, 'embedded AT C');

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'should_lock_file'    => false,
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsNOTApprovalTableWhileThereIsApprovalAction(array $items): void
    {
        $embedded = $this->findItemByTitle($items, 'embedded NO AT');

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'embedded_properties'   => ['content' => 'my new content'],
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchOnDocumentWithBadMatchingBetweenThePatchedItemTypeAndAcceptedRouteType(array $items): void
    {
        $empty_document = $this->findItemByTitle($items, 'empty');

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'should_lock_file'    => false,
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_embedded_files/' . $empty_document["id"],
                null,
                $put_resource
            )
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenADocumentIsLockedByAnOtherUser(array $items): void
    {
        $locked_embedded = $this->findItemByTitle($items, 'embedded L');

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'should_lock_file'    => false,
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
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchOnEmptyItemThrowAnException(array $items): void
    {
        $empty = $this->findItemByTitle($items, 'empty');

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'should_lock_file'    => false,
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $empty['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
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

        $embedded_id = $response1->json()['id'];

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
                'should_lock_file'    => false,
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
        $this->assertEquals(null, $response->json()['lock_info']);
    }


    /**
     * @depends testGetRootId
     */
    public function testPatchDocumentAddLock(int $root_id): void
    {
        $query = json_encode(
            [
                'title'               => 'My second embedded',
                'parent_id'           => $root_id,
                'type'                => 'embedded',
                'embedded_properties' => ['content' => 'my new content']
            ]
        );

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

        $embedded_id = $response1->json()['id'];

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have changed',
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
    public function testApprovalTablesStatus(int $root_id): void
    {

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();


        $folder_embedded = $this->findItemByTitle($folder, 'Folder B Embedded');
        $folder_embedded_id = $folder_embedded['id'];
        $response           = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_embedded_id . '/docman_items')
        );
        $items     = $response->json();

        $reset_after_patch = $this->findItemByTitle($items, 'embedded AT R');
        $this->assertEquals($reset_after_patch['approval_table']["approval_state"], 'Not yet');

        $empty_after_patch = $this->findItemByTitle($items, 'embedded AT E');
        $this->assertNull($empty_after_patch['approval_table']["approval_state"]);

        $copy_after_patch = $this->findItemByTitle($items, 'embedded AT C');
        $this->assertEquals($copy_after_patch['approval_table']["approval_state"], "Approved");
    }


    /**
     * Find first item in given array of items which has given title.
     * @return array|null Found item. null otherwise.
     */
    private function findItemByTitle(array $items, string $title): ?array
    {
        $index = array_search($title, array_column($items, 'title'));
        if ($index === false) {
            return null;
        }
        return $items[$index];
    }
}
