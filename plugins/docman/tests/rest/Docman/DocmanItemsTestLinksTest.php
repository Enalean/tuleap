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

class DocmanItemsTestLinksTest extends DocmanBase
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

        $folder_content = $this->findItemByTitle($folder, 'Folder D Link');
        $folder_links_id = $folder_content['id'];
        $response        = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_links_id . '/docman_items')
        );
        $items_link      = $response->json();

        $items = array_merge($items_folder_1, $items_link);

        $this->assertEquals(count($items), 12);

        return $items;
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchALinksWithApprovalTableCopyAction(array $items): void
    {
        $links = $this->findItemByTitle($items, 'link AT C');

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $links['id'])
        );

        $item_before_patch                = $response->json();
        $item_approval_table_before_patch = $item_before_patch['approval_table'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($item_approval_table_before_patch);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'title'                 => 'link AT C',
                'should_lock_file'      => false,
                'link_properties'       => ['link_url' => 'https://example.com'],
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_links/' . $links['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchLinksWithApprovalTableResetAction(array $items): void
    {
        $links = $this->findItemByTitle($items, 'link AT R');

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $links['id'])
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
                'title'                 => 'link AT R',
                'should_lock_file'      => false,
                'link_properties'       => ['link_url' => 'https://example.com'],
                'approval_table_action' => 'reset'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_links/' . $links['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchLinksWithApprovalTableEmptyAction(array $items): void
    {
        $links = $this->findItemByTitle($items, 'link AT E');

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $links['id'])
        );

        $item_before_patch                = $response->json();
        $item_approval_table_before_patch = $item_before_patch['approval_table'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($item_approval_table_before_patch);

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'title'                 => 'link AT E',
                'should_lock_file'      => false,
                'link_properties'       => ['link_url' => 'https://example.com'],
                'approval_table_action' => 'empty'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_links/' . $links['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsAnApprovalTableForTheItemAndNoApprovalAction(array $items): void
    {
        $links = $this->findItemByTitle($items, 'link AT C');

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => 'link AT C',
                'should_lock_file' => false,
                'link_properties'  => ['link_url' => 'https://example.com']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_links/' . $links['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsNOTApprovalTableWhileThereIsApprovalAction(array $items): void
    {
        $links = $this->findItemByTitle($items, 'link NO AT');

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'title'                 => 'link NO AT',
                'link_properties'       => ['link_url' => 'https://example.com'],
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_links/' . $links['id'], null, $put_resource)
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
                'docman_links/' . $empty_document["id"],
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
        $locked_links = $this->findItemByTitle($items, 'link L');

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'title'            => 'link AT L',
                'link_properties'  => ['link_url' => 'https://example.com']
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_links/' . $locked_links["id"],
                null,
                $put_resource
            )
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchLinksDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My new link',
                'parent_id'       => $root_id,
                'link_properties' => ['link_url' => 'https://example.com']
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/links', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $links_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $links_item_response->getStatusCode());
        $this->assertEquals('link', $links_item_response->json()['type']);

        $links_id = $response1->json()['id'];

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => 'My new link',
                'should_lock_file' => false,
                'link_properties'  => ['link_url' => 'https://example.com']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $links_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $links_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $response->json()['type']);
        $this->assertEquals(null, $response->json()['lock_info']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchLinksWithStatusThrows403WhenStatusIsNotAllowedForProject(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My new link 403',
                'parent_id'       => $root_id,
                'link_properties' => ['link_url' => 'https://example.com']
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/links', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $links_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $links_item_response->getStatusCode());
        $this->assertEquals('link', $links_item_response->json()['type']);

        $links_id = $response1->json()['id'];

        $link_properties = [
            'link_url' => 'https://example.com'
        ];
        $put_resource    = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => 'My new link 403',
                'status'           => 'approved',
                'should_lock_file' => false,
                'link_properties'  => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $links_id, null, $put_resource)
        );
        $this->assertEquals(403, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $links_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $response->json()['type']);
        $this->assertEquals(null, $response->json()['lock_info']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchLinksWithStatusThrows403WhenObsolescenceDateIsNotAllowedForProject(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My new link with fail obsolescence date',
                'parent_id'       => $root_id,
                'link_properties' => ['link_url' => 'https://example.com']
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/links', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $links_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $links_item_response->getStatusCode());
        $this->assertEquals('link', $links_item_response->json()['type']);

        $links_id = $response1->json()['id'];

        $link_properties = [
            'link_url'          => 'https://example.com',
        ];
        $put_resource    = json_encode(
            [
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'title'             => 'My new link with fail obsolescence date',
                'obsolescence_date' => '2038-12-31',
                'should_lock_file'  => false,
                'link_properties'   => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $links_id, null, $put_resource)
        );
        $this->assertEquals(403, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $links_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $response->json()['type']);
        $this->assertEquals(null, $response->json()['lock_info']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchLinksDocumentTitleAndDescription(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My new link 3',
                'parent_id'       => $root_id,
                'link_properties' => ['link_url' => 'https://example.com']
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/links', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $links_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $links_item_response->getStatusCode());
        $this->assertEquals('link', $links_item_response->json()['type']);

        $links_id = $response1->json()['id'];

        $link_properties = [
            'link_url' => 'https://example.com'
        ];

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => 'New title !!!',
                'description'      => 'I have a description now',
                'should_lock_file' => false,
                'link_properties'  => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $links_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $links_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $response->json()['type']);
        $this->assertEquals('New title !!!', $response->json()['title']);
        $this->assertEquals('I have a description now', $response->json()['description']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchDocumentAddLock(int $root_id): void
    {
        $query = json_encode(
            [
                'title'            => 'My second link',
                'parent_id'        => $root_id,
                'type'             => 'links',
                'link_properties' => ['link_url' => 'https://example.com']
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/links', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $links_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );

        $this->assertEquals(200, $links_item_response->getStatusCode());
        $this->assertEquals('link', $links_item_response->json()['type']);

        $links_id = $response1->json()['id'];

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'            => 'My second link',
                'should_lock_file' => true,
                'link_properties'  => ['link_url' => 'https://example.com']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $links_id, null, $put_resource)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $links_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $response->json()['type']);
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


        $folder_embedded = $this->findItemByTitle($folder, 'Folder D Link');
        $folder_embedded_id = $folder_embedded['id'];
        $response           = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_embedded_id . '/docman_items')
        );
        $items     = $response->json();

        $reset_after_patch = $this->findItemByTitle($items, 'link AT R');
        $this->assertEquals($reset_after_patch['approval_table']["approval_state"], 'Not yet');

        $empty_after_patch = $this->findItemByTitle($items, 'link AT E');
        $this->assertNull($empty_after_patch['approval_table']["approval_state"]);

        $copy_after_patch = $this->findItemByTitle($items, 'link AT C');
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
            $this->fail("'$title' not found in test data");
        }
        return $items[$index];
    }

    /**
     * @depends testGetRootId
     */
    public function testGetItemsToTrash($root_id): array
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

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertGreaterThan(0, count($items_to_delete));


        return $items_to_delete;
    }

    /**
     * @depends testGetItemsToTrash
     */
    public function testItThrowsAnErrorWhenTheLinkIsLockedByAnotherUser(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'old link L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_links/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetItemsToTrash
     */
    public function testItDeletesWhenLinkIsLockedAndUserIsAdmin(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'old link L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_links/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_delete_id)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @depends testGetItemsToTrash
     */
    public function testItDeletesALink(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'another old link');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_links/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_delete_id)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }
}
