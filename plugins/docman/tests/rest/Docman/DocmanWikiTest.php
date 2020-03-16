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
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Wiki');
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Wiki');
        $post_items    = $this->loadFolderContent($items_id, 'POST Wiki');
        $put_items     = $this->loadFolderContent($items_id, 'PUT HM Wiki');

        return array_merge(
            $root_folder,
            $folder,
            $items,
            $deleted_items,
            $lock_items,
            $post_items,
            $put_items
        );
    }

    /**
     * @depends testGetRootIdWithUserRESTReadOnlyAdmin
     */
    public function testGetDocumentItemsForUserRESTReadOnlyAdmin(int $root_id): array
    {
        $root_folder = $this->loadRootFolderContent($root_id, REST_TestDataBuilder::TEST_BOT_USER_NAME);

        $items         = $this->loadFolderContent($root_id, 'Wiki', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $folder        = $this->findItemByTitle($root_folder, 'Wiki');
        $items_id      = $folder['id'];
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Wiki', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Wiki', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $post_items    = $this->loadFolderContent($items_id, 'POST Wiki', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $put_items     = $this->loadFolderContent($items_id, 'PUT HM Wiki', REST_TestDataBuilder::TEST_BOT_USER_NAME);

        return array_merge(
            $root_folder,
            $folder,
            $items,
            $deleted_items,
            $lock_items,
            $post_items,
            $put_items
        );
    }

    /**
     * @depends testGetRootId
     */
    public function testMoveWikiDocument(int $root_id): void
    {
        $response_wiki_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . urlencode((string) $root_id) . '/wikis',
                null,
                json_encode([
                    'title' => 'Link document to cut',
                    'wiki_properties' => ['page_name' => 'AAAAA']
                ])
            )
        );
        $this->assertEquals(201, $response_wiki_creation->getStatusCode());
        $wiki_doc_id = $response_wiki_creation->json()['id'];

        $response_folder_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_folders/' . urlencode((string) $root_id) . '/folders',
                null,
                json_encode(['title' => 'Wiki cut folder'])
            )
        );
        $this->assertEquals(201, $response_folder_creation->getStatusCode());
        $folder_id = $response_folder_creation->json()['id'];

        $move_response_with_rest_read_only_user = $this->getResponse(
            $this->client->patch(
                'docman_wikis/' . urlencode((string) $wiki_doc_id),
                null,
                json_encode(['move' => ['destination_folder_id' => $folder_id]])
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $move_response_with_rest_read_only_user->getStatusCode());

        $move_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch(
                'docman_wikis/' . urlencode((string) $wiki_doc_id),
                null,
                json_encode(['move' => ['destination_folder_id' => $folder_id]])
            )
        );
        $this->assertEquals(200, $move_response->getStatusCode());

        $moved_item_response = $this->getResponse(
            $this->client->get('docman_items/' . urlencode((string) $wiki_doc_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals($folder_id, $moved_item_response->json()['parent_id']);

        $this->getResponse(
            $this->client->delete('docman_folders/' . urlencode((string) $folder_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
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
    public function testDeletionOfAWikiForbiddenForRESTReadOnlyUserNotInvolvedInProject(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE W');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponse(
            $this->client->delete('docman_wikis/' . $item_to_delete_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
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

        $response_with_rest_read_only_user = $this->getResponse(
            $this->client->post('docman_wikis/' . $locked_document_id . "/lock"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

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
        $this->assertEquals($document['lock_info']["locked_by"]["username"], DocmanDataBuilder::ADMIN_USER_NAME);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteLockAWiki(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK W');
        $locked_document_id = $locked_document['id'];

        $response_with_rest_read_only_user = $this->getResponse(
            $this->client->delete('docman_wikis/' . $locked_document_id . "/lock"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

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
            $this->client->post('docman_wikis/' . $wiki['id'] . '/version', null, $put_resource)
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

        $response1_with_rest_read_only_user = $this->getResponse(
            $this->client->post('docman_folders/' . $root_id . '/wikis', null, $query),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response1_with_rest_read_only_user->getStatusCode());

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

        $response_with_rest_read_only_user = $this->getResponse(
            $this->client->post('docman_wikis/' . $wiki_id . '/version', null, $put_resource),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_wikis/' . $wiki_id . '/version', null, $put_resource)
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
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPutBasicHardcodedMetadata(array $items): void
    {
        $item_name         = 'PUT W';
        $item_to_update    = $this->findItemByTitle($items, $item_name);
        $item_to_update_id = $item_to_update['id'];

        $this->assertEquals($item_name, $item_to_update['title']);
        $this->assertEquals('', $item_to_update['description']);
        $this->assertEquals($this->docman_user_id, $item_to_update['owner']['id']);

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $item_to_update['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $put_resource = [
            'id'                => $item_to_update_id,
            'title'             => 'PUT W New title',
            'description'       => 'Danger ! Danger !',
            'owner_id'          => $this->test_user_1_id,
            'status'            => 'none'
        ];

        $updated_metadata_file_response_with_reast_read_only_user = $this->getResponse(
            $this->client->put('docman_wikis/' . $item_to_update_id . '/metadata', null, $put_resource),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $updated_metadata_file_response_with_reast_read_only_user->getStatusCode());

        $updated_metadata_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->put('docman_wikis/' . $item_to_update_id . '/metadata', null, $put_resource)
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

        $this->assertEquals('PUT W New title', $new_version['title']);
        $this->assertEquals('Danger ! Danger !', $new_version['description']);
        $this->assertEquals($this->test_user_1_id, $new_version['owner']['id']);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptionsMetadata(int $id): void
    {
        $response = $this->getResponse(
            $this->client->options('docman_wikis/' . $id . '/metadata'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
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
     * @depends testGetRootId
     */
    public function testAllOptionsForRESTReadOnlyUser(int $id): void
    {
        $response = $this->getResponse(
            $this->client->options('docman_wikis/' . $id . '/metadata'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->options('docman_wikis/' . $id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->options('docman_wikis/' . $id . '/lock'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testUpdatePermissionsWikiDocument(int $root_id) : void
    {
        $wiki_doc_id = $this->createWikiAndReturnItsId(
            $root_id,
            json_encode(['title' => 'Wiki update permissions', 'wiki_properties' => ['page_name' => 'example']])
        );

        $project_members_identifier = $this->project_id . '_3';

        $response_with_rest_read_only_user = $this->getResponse(
            $this->client->put(
                'docman_wikis/' . urlencode((string) $wiki_doc_id) . '/permissions',
                null,
                json_encode(['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]])
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $permission_update_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                'docman_wikis/' . urlencode((string) $wiki_doc_id) . '/permissions',
                null,
                json_encode(['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]])
            )
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());

        $wiki_doc_representation_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . urlencode((string) $wiki_doc_id))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());
        $permissions_for_groups_representation = $wiki_doc_representation_response->json()['permissions_for_groups'];
        $this->assertEmpty($permissions_for_groups_representation['can_read']);
        $this->assertEmpty($permissions_for_groups_representation['can_write']);
        $this->assertCount(1, $permissions_for_groups_representation['can_manage']);
        $this->assertEquals($project_members_identifier, $permissions_for_groups_representation['can_manage'][0]['id']);

        $this->getResponse(
            $this->client->delete('docman_wikis/' . urlencode((string) $wiki_doc_id)),
            DocmanDataBuilder::ADMIN_USER_NAME
        );
    }

    /**
     *
     * @return mixed
     */
    private function createWikiAndReturnItsId(int $root_id, string $query)
    {
        $response_with_rest_read_only_user = $this->getResponse(
            $this->client->post('docman_folders/' . $root_id . '/wikis', null, $query),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

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
