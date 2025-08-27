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

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest\Docman;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RESTTestDataBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class DocmanWikiTest extends DocmanTestExecutionHelper
{
    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
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

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootIdWithUserRESTReadOnlyAdmin')]
    public function testGetDocumentItemsForUserRESTReadOnlyAdmin(int $root_id): array
    {
        $root_folder = $this->loadRootFolderContent($root_id, RESTTestDataBuilder::TEST_BOT_USER_NAME);

        $items         = $this->loadFolderContent($root_id, 'Wiki', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $folder        = $this->findItemByTitle($root_folder, 'Wiki');
        $items_id      = $folder['id'];
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Wiki', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Wiki', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $post_items    = $this->loadFolderContent($items_id, 'POST Wiki', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $put_items     = $this->loadFolderContent($items_id, 'PUT HM Wiki', RESTTestDataBuilder::TEST_BOT_USER_NAME);

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

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testMoveWikiDocument(int $root_id): void
    {
        $response_wiki_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/wikis')->withBody($this->stream_factory->createStream(json_encode([
                'title' => 'Link document to cut',
                'wiki_properties' => ['page_name' => 'AAAAA'],
            ])))
        );
        $this->assertEquals(201, $response_wiki_creation->getStatusCode());
        $wiki_doc_id = json_decode($response_wiki_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $response_folder_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Wiki cut folder'])))
        );
        $this->assertEquals(201, $response_folder_creation->getStatusCode());
        $folder_id = json_decode($response_folder_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $move_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'docman_wikis/' . urlencode((string) $wiki_doc_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_id]]))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $move_response_with_rest_read_only_user->getStatusCode());

        $move_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'docman_wikis/' . urlencode((string) $wiki_doc_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_id]])))
        );
        $this->assertEquals(200, $move_response->getStatusCode());

        $moved_item_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $wiki_doc_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals($folder_id, json_decode($moved_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['parent_id']);

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) $folder_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteThrowsAnErrorWhenUserHasNotPermissionToDeleteTheWiki(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE W RO');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_wikis/' . $item_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteThrowAPermissionErrorWhenTheWikiIsLockedByAnotherUser(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE W L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_wikis/' . $item_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteIsProceedWhenItemIsLockedAndUserIsAdmin(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE W L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_wikis/' . $item_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeletionOfAWikiForbiddenForRESTReadOnlyUserNotInvolvedInProject(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE W');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_wikis/' . $item_to_delete_id),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testItDeletesAWiki(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE W');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_wikis/' . $item_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostLocksAWiki(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK W');
        $locked_document_id = $locked_document['id'];

        $response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_wikis/' . $locked_document_id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_wikis/' . $locked_document_id . '/lock')
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $locked_document_id)
        );

        $document = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($document['lock_info']['locked_by']['username'], BaseTestDataBuilder::ADMIN_USER_NAME);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteLockAWiki(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK W');
        $locked_document_id = $locked_document['id'];

        $response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_wikis/' . $locked_document_id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_wikis/' . $locked_document_id . '/lock')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $locked_document_id)
        );

        $document = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals($document['lock_info'], null);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
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
                'approval_table_action' => 'copy',
            ]
        );

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_wikis/' . $wiki['id'] . '/version')->withBody($this->stream_factory->createStream($put_resource))
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testPostWikiDocument(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My new wiki',
                'parent_id'       => $root_id,
                'type'            => 'wiki',
                'wiki_properties' => ['page_name' => 'my new page name'],
            ]
        );

        $response1_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/wikis')->withBody($this->stream_factory->createStream($query)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response1_with_rest_read_only_user->getStatusCode());

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/wikis')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response1->getStatusCode());
        $response1_json = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $wiki_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $response1_json['uri'])
        );
        $this->assertEquals(200, $wiki_item_response->getStatusCode());
        $wiki_item_response_json = json_decode($wiki_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('wiki', $wiki_item_response_json['type']);
        $this->assertEquals('My new wiki', $wiki_item_response_json['title']);
        $this->assertEquals('', $wiki_item_response_json['description']);

        $wiki_id = $response1_json['id'];

        $put_resource = json_encode(
            [
                'should_lock_file' => false,
                'wiki_properties'  => ['page_name' => 'my updated page name'],
            ]
        );

        $response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_wikis/' . $wiki_id . '/version')->withBody($this->stream_factory->createStream($put_resource)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_wikis/' . $wiki_id . '/version')->withBody($this->stream_factory->createStream($put_resource))
        );
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $wiki_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('wiki', $response_json['type']);
        $this->assertEquals(null, $response_json['lock_info']);
        $this->assertEquals('my updated page name', $response_json['wiki_properties']['page_name']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
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
            'status'            => 'none',
        ];

        $updated_metadata_file_response_with_reast_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_wikis/' . $item_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $updated_metadata_file_response_with_reast_read_only_user->getStatusCode());

        $updated_metadata_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_wikis/' . $item_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource)))
        );

        $this->assertEquals(200, $updated_metadata_file_response->getStatusCode());

        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $item_to_update_id)
        );

        $this->assertEquals($new_version_response->getStatusCode(), 200);

        $new_version = json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

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

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsMetadata(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_wikis/' . $id . '/metadata'),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptions(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'docman_wikis/' . $id), BaseTestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsLock(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'docman_wikis/' . $id . '/lock'), BaseTestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testAllOptionsForRESTReadOnlyUser(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_wikis/' . $id . '/metadata'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_wikis/' . $id),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_wikis/' . $id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testUpdatePermissionsWikiDocument(int $root_id): void
    {
        $wiki_doc_id = $this->createWikiAndReturnItsId(
            $root_id,
            json_encode(['title' => 'Wiki update permissions', 'wiki_properties' => ['page_name' => 'example']])
        );

        $project_members_identifier = $this->project_id . '_3';

        $response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_wikis/' . urlencode((string) $wiki_doc_id) . '/permissions')->withBody($this->stream_factory->createStream(json_encode(['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]]))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $permission_update_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_wikis/' . urlencode((string) $wiki_doc_id) . '/permissions')->withBody($this->stream_factory->createStream(json_encode(['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]])))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());

        $wiki_doc_representation_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $wiki_doc_id))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());
        $permissions_for_groups_representation = json_decode($wiki_doc_representation_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['permissions_for_groups'];
        $this->assertEmpty($permissions_for_groups_representation['can_read']);
        $this->assertEmpty($permissions_for_groups_representation['can_write']);
        $this->assertCount(1, $permissions_for_groups_representation['can_manage']);
        $this->assertEquals($project_members_identifier, $permissions_for_groups_representation['can_manage'][0]['id']);

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_wikis/' . urlencode((string) $wiki_doc_id)),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );
    }

    /**
     *
     * @return mixed
     */
    private function createWikiAndReturnItsId(int $root_id, string $query)
    {
        $response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/wikis')->withBody($this->stream_factory->createStream($query)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/wikis')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response1->getStatusCode());

        $response1_json = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $response1_json['uri'])
        );
        $this->assertEquals(200, $item_response->getStatusCode());
        $this->assertEquals('wiki', json_decode($item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['type']);

        return $response1_json['id'];
    }
}
