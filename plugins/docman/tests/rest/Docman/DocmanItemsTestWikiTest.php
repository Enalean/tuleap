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

class DocmanItemsTestWikiTest extends DocmanBase
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
    public function testGetDocumentItemsForAdminUser(int $root_id): array
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

        $folder_wiki = $this->findItemByTitle($folder, 'Folder C Wiki');
        $folder_wiki_id = $folder_wiki['id'];
        $response       = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_wiki_id . '/docman_items')
        );
        $items_wiki     = $response->json();

        $trash_folder    = $this->findItemByTitle($folder, "Trash");
        $trash_folder_id = $trash_folder['id'];

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $trash_folder_id . '/docman_items')
        );

        $items_to_delete = $response->json();

        $items = array_merge($items_folder_1, $items_wiki, $items_to_delete);

        $this->assertGreaterThan(0, count($items));

        return $items;
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenWikiHasAnApprovalTable(array $items): void
    {
        $wiki = $this->findItemByTitle($items, 'wiki AT');

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'wiki_properties'       => ['page_name' => 'my new page name'],
                'title'                 => 'No title',
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
    public function testPatchOnDocumentWithBadMatchingBetweenThePatchedItemTypeAndAcceptedRouteType(array $items): void
    {
        $empty_document = $this->findItemByTitle($items, 'empty');

        $put_resource = json_encode(
            [
                'title'            => 'No title 2',
                'should_lock_file' => false,
                'wiki_properties'  => ['page_name' => 'my new page name']
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_wikis/' . $empty_document["id"],
                null,
                $put_resource
            )
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testAdminShouldAlwaysBeAbleToUnlockAnItem(array $items): void
    {
        $locked_wiki = $this->findItemByTitle($items, 'wiki L');

        $put_resource = json_encode(
            [
                'title'            => 'No title 3',
                'should_lock_file' => false,
                'wiki_properties'  => ['page_name' => 'my new page name']
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_wikis/' . $locked_wiki["id"],
                null,
                $put_resource
            )
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchOnEmptyItemThrowAnException(array $items): void
    {
        $empty = $this->findItemByTitle($items, 'empty');

        $put_resource = json_encode(
            [
                'title'            => 'No title 4',
                'should_lock_file' => false,
                'wiki_properties'  => ['page_name' => 'my new page name']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_wikis/' . $empty['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
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
    public function testPatchWikiWithObsolescenceDateWhenObsolescenceDateIsNotEnabledForProject(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My new wiki fail',
                'parent_id'       => $root_id,
                'wiki_properties' => ['page_name' => 'my new page name'],
            ]
        );

        $post_wiki_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/wikis', null, $query)
        );

        $this->assertEquals(201, $post_wiki_response->getStatusCode());

        $wiki_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($post_wiki_response->json()['uri'])
        );
        $this->assertEquals(200, $wiki_item_response->getStatusCode());
        $this->assertEquals('wiki', $wiki_item_response->json()['type']);

        $wiki_id = $post_wiki_response->json()['id'];

        $put_resource = json_encode(
            [
                'title'             => 'My new wiki fail',
                'should_lock_file'  => false,
                'wiki_properties'   => ['page_name' => 'my updated page name'],
                'obsolescence_date' => '2040-02-03'
            ]
        );

        $patch_wiki_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_wikis/' . $wiki_id, null, $put_resource)
        );
        $this->assertEquals(400, $patch_wiki_response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchWikiWithStatusWhenStatusIsNotEnabledForProject(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My new wiki fail v2',
                'parent_id'       => $root_id,
                'wiki_properties' => ['page_name' => 'my new page name'],
            ]
        );

        $post_wiki_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/wikis', null, $query)
        );

        $this->assertEquals(201, $post_wiki_response->getStatusCode());

        $wiki_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($post_wiki_response->json()['uri'])
        );
        $this->assertEquals(200, $wiki_item_response->getStatusCode());
        $this->assertEquals('wiki', $wiki_item_response->json()['type']);

        $wiki_id = $post_wiki_response->json()['id'];

        $put_resource = json_encode(
            [
                'should_lock_file' => false,
                'title'            => 'My new wiki fail patch',
                'wiki_properties'  => ['page_name' => 'my updated page name'],
                'status'           => 'approved'
            ]
        );

        $patch_wiki_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_wikis/' . $wiki_id, null, $put_resource)
        );
        $this->assertEquals(400, $patch_wiki_response->getStatusCode());
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

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItThrowsAnErrorWhenUserHasNotPermissionToDeleteTheWiki(array $items): void
    {
        $wiki_to_delete    = $this->findItemByTitle($items, 'old wiki L');
        $wiki_to_delete_id = $wiki_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_wikis/' . $wiki_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkItemHasNotBeenDeleted($wiki_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItShouldThrowAnErrorWhenTheWikiIsLockedByAnotherUser(array $items): void
    {
        $wiki_to_delete    = $this->findItemByTitle($items, 'another old wiki');
        $wiki_to_delete_id = $wiki_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_wikis/' . $wiki_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkItemHasNotBeenDeleted($wiki_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItShouldDeleteWhenWikiIsLockedAndUserIsAdmin(array $items): void
    {
        $wiki_to_delete    = $this->findItemByTitle($items, 'old wiki L');
        $wiki_to_delete_id = $wiki_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_wikis/' . $wiki_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($wiki_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItDeletesAWiki(array $items): void
    {
        $wiki_to_delete    = $this->findItemByTitle($items, 'another old wiki');
        $wiki_to_delete_id = $wiki_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_wikis/' . $wiki_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($wiki_to_delete_id);
    }

    private function checkItemHasNotBeenDeleted(int $wiki_to_delete_id) : void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $wiki_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    private function checkItemHasBeenDeleted(int $wiki_to_delete_id) : void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $wiki_to_delete_id)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }
}
