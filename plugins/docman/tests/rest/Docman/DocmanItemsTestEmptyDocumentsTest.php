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

class DocmanItemsTestEmptyDocumentsTest extends DocmanBase
{
    public function testGetRootId()
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

        $folder_1 = $this->findItemByTitle($folder, 'folder 1');

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_1['id'] . '/docman_items')
        );
        $items   = $response->json();

        $this->assertGreaterThan(0, count($items));

        return $items;
    }

    /**
     * @depends testGetRootId
     */
    public function testGetTrashFolderContent(int $root_id): array
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder = $response->json();

        $trash_folder    = $this->findItemByTitle($folder, "Trash");
        $trash_folder_id = $trash_folder['id'];

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $trash_folder_id . '/docman_items')
        );

        $items_to_delete = $response->json();

        $this->assertGreaterThan(0, count($items_to_delete));

        return $items_to_delete;
    }

    /**
     * Find first item in given array of items which has given title.
     * @return array|null Found item. null otherwise.
     */
    private function findItemByTitle(array $items, $title)
    {
        $index = array_search($title, array_column($items, 'title'));
        if ($index === false) {
            return null;
        }
        return $items[$index];
    }

    /**
     * @depends testGetTrashFolderContent
     */
    public function testItThrowsAnErrorWhenUserHasNotPermissionToDeleteTheEmptyDoc(array $items): void
    {
        $empty_doc_to_delete    = $this->findItemByTitle($items, 'old empty doc L');
        $empty_doc_to_delete_id = $empty_doc_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_empty_documents/' . $empty_doc_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkEmptyDocHasNotBeenDeleted($empty_doc_to_delete_id);
    }

    /**
     * @depends testGetTrashFolderContent
     */
    public function testItShouldDeleteWhenEmptyDocIsLockedAndUserIsAdmin(array $items): void
    {
        $empty_doc_to_delete    = $this->findItemByTitle($items, 'old empty doc L');
        $empty_doc_to_delete_id = $empty_doc_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_empty_documents/' . $empty_doc_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkEmptyDocHasBeenDeleted($empty_doc_to_delete_id);
    }

    /**
     * @depends testGetTrashFolderContent
     */
    public function testItDeletesAnEmptyDoc(array $items): void
    {
        $empty_doc_to_delete    = $this->findItemByTitle($items, 'another old empty doc');
        $empty_doc_to_delete_id = $empty_doc_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_empty_documents/' . $empty_doc_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkEmptyDocHasBeenDeleted($empty_doc_to_delete_id);
    }

    private function checkEmptyDocHasNotBeenDeleted(int $empty_doc_to_delete_id) : void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $empty_doc_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    private function checkEmptyDocHasBeenDeleted(int $empty_doc_to_delete_id) : void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $empty_doc_to_delete_id)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostLocksAnEmpty(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'Empty POST L');
        $locked_document_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_empty_documents/' . $locked_document_id . "/lock")
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
    public function testDeleteLockAnEmpty(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'Empty POST L');
        $locked_document_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_empty_documents/' . $locked_document_id . "/lock")
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
        $response = $this->getResponse($this->client->options('docman_empty_documents/' . $id), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptionsLock(int $id): void
    {
        $response = $this->getResponse($this->client->options('docman_empty_documents/' . $id . '/lock'), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }
}
