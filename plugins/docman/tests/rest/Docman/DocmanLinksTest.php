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
class DocmanLinksTest extends DocmanTestExecutionHelper
{
    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testGetDocumentItemsForAdminUser(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items         = $this->loadFolderContent($root_id, 'Link');
        $folder        = $this->findItemByTitle($root_folder, 'Link');
        $items_id      = $folder['id'];
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Link');
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Link');
        $post_version  = $this->loadFolderContent($items_id, 'POST Link version');
        $put_metadata  = $this->loadFolderContent($items_id, 'PUT HM Link');

        return array_merge(
            $root_folder,
            $folder,
            $items,
            $deleted_items,
            $lock_items,
            $post_version,
            $put_metadata
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootIdWithUserRESTReadOnlyAdmin')]
    public function testGetDocumentItemsWithUserRESTReadOnlyAdmin(int $root_id): array
    {
        $root_folder = $this->loadRootFolderContent($root_id, RESTTestDataBuilder::TEST_BOT_USER_NAME);

        $items         = $this->loadFolderContent($root_id, 'Link', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $folder        = $this->findItemByTitle($root_folder, 'Link');
        $items_id      = $folder['id'];
        $deleted_items = $this->loadFolderContent($items_id, 'DELETE Link', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $lock_items    = $this->loadFolderContent($items_id, 'LOCK Link', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $post_version  = $this->loadFolderContent($items_id, 'POST Link version', RESTTestDataBuilder::TEST_BOT_USER_NAME);
        $put_metadata  = $this->loadFolderContent($items_id, 'PUT HM Link', RESTTestDataBuilder::TEST_BOT_USER_NAME);

        return array_merge(
            $root_folder,
            $folder,
            $items,
            $deleted_items,
            $lock_items,
            $post_version,
            $put_metadata
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testMoveLinkDocument(int $root_id): void
    {
        $post_link_body = json_encode([
            'title' => 'Link document to cut',
            'link_properties' => ['link_url' => 'https://example.com'],
        ]);

        $response_link_creation_with_rest_read_noly_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/links')->withBody($this->stream_factory->createStream($post_link_body)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response_link_creation_with_rest_read_noly_user->getStatusCode());

        $response_link_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/links')->withBody($this->stream_factory->createStream($post_link_body))
        );
        $this->assertEquals(201, $response_link_creation->getStatusCode());
        $link_doc_id = json_decode($response_link_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $response_folder_creation_with_rest_read_noly_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Link cut folder']))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $response_folder_creation_with_rest_read_noly_user->getStatusCode());

        $response_folder_creation = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . urlencode((string) $root_id) . '/folders')->withBody($this->stream_factory->createStream(json_encode(['title' => 'Link cut folder'])))
        );
        $this->assertEquals(201, $response_folder_creation->getStatusCode());
        $folder_id = json_decode($response_folder_creation->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $move_response_with_rest_read_noly_user = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'docman_links/' . urlencode((string) $link_doc_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_id]]))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $move_response_with_rest_read_noly_user->getStatusCode());

        $move_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('PATCH', 'docman_links/' . urlencode((string) $link_doc_id))->withBody($this->stream_factory->createStream(json_encode(['move' => ['destination_folder_id' => $folder_id]])))
        );
        $this->assertEquals(200, $move_response->getStatusCode());

        $moved_item_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $link_doc_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals($folder_id, json_decode($moved_item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['parent_id']);

        $delete_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) $folder_id)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $delete_response_with_rest_read_only_user->getStatusCode());

        $delete_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_folders/' . urlencode((string) $folder_id)),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $delete_response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testUpdatePermissionsLinkDocument(int $root_id): void
    {
        $link_doc_id = $this->createLinkAndReturnItsId(
            $root_id,
            json_encode(['title' => 'Link update permissions', 'link_properties' => ['link_url' => 'https://example.com']]),
        );

        $project_members_identifier = $this->project_id . '_3';
        $put_body_content           = json_encode(
            ['can_read' => [], 'can_write' => [], 'can_manage' => [['id' => $project_members_identifier]]]
        );

        $permission_update_response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_links/' . urlencode((string) $link_doc_id) . '/permissions')->withBody($this->stream_factory->createStream($put_body_content)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(403, $permission_update_response->getStatusCode());

        $permission_update_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_links/' . urlencode((string) $link_doc_id) . '/permissions')->withBody($this->stream_factory->createStream($put_body_content))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());

        $link_doc_representation_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . urlencode((string) $link_doc_id))
        );
        $this->assertEquals(200, $permission_update_response->getStatusCode());
        $permissions_for_groups_representation = json_decode($link_doc_representation_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['permissions_for_groups'];
        $this->assertEmpty($permissions_for_groups_representation['can_read']);
        $this->assertEmpty($permissions_for_groups_representation['can_write']);
        $this->assertCount(1, $permissions_for_groups_representation['can_manage']);
        $this->assertEquals($project_members_identifier, $permissions_for_groups_representation['can_manage'][0]['id']);

        $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_links/' . urlencode((string) $link_doc_id)),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteThrowsAnErrorWhenUserHasNotPermissionToDeleteTheLink(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE L RO');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_links/' . $item_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteThrowAPermissionErrorWhenTheLinkIsLockedByAnotherUser(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE L L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_links/' . $item_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertStringContainsString('allowed', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeleteIsProceedWhenItemIsLockedAndUserIsAdmin(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE L L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_links/' . $item_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testDeletionOfALinkIsForbiddenForRESTReadOnlyUserNotInvolvedInProject(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_links/' . $item_to_delete_id),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->checkItemHasNotBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testItDeletesALink(array $items): void
    {
        $item_to_delete    = $this->findItemByTitle($items, 'DELETE L');
        $item_to_delete_id = $item_to_delete['id'];

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_links/' . $item_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($item_to_delete_id);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostLocksALink(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK L');
        $locked_document_id = $locked_document['id'];

        $response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_links/' . $locked_document_id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_links/' . $locked_document_id . '/lock')
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
    public function testDeleteLockALink(array $items): void
    {
        $locked_document    = $this->findItemByTitle($items, 'LOCK L');
        $locked_document_id = $locked_document['id'];

        $response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_links/' . $locked_document_id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_rest_read_only_user->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('DELETE', 'docman_links/' . $locked_document_id . '/lock')
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
    public function testPostVersionItCreatesALink(array $items): void
    {
        $title             = 'POST L V';
        $item_to_update    = $this->findItemByTitle($items, $title);
        $item_to_update_id = $item_to_update['id'];

        $new_version_resource = json_encode(
            [
                'version_title'       => 'My new versionnn',
                'description'         => 'whatever',
                'link_properties'  => ['link_url' => 'https://example.com'],
                'should_lock_file'    => false,
            ]
        );

        $new_version_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_links/' . $item_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $new_version_response_with_rest_read_only_user->getStatusCode());

        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_links/' . $item_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $this->checkItemHasADisabledApprovalTable($items, $title);

        $versions_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_links/' . $item_to_update_id . '/versions')
        );
        $this->assertEquals(200, $versions_response->getStatusCode());
        $this->assertIsArray(
            json_decode($versions_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
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
                'should_lock_file'      => false,
                'approval_table_action' => 'copy',
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_links/' . $item_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $this->checkItemHasAnApprovalTable($items, $title, 'Approved');
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
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
                'should_lock_file'      => false,
                'approval_table_action' => 'reset',
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_links/' . $item_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $this->checkItemHasAnApprovalTable($items, $title, 'Not yet');
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
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
                'should_lock_file'      => false,
                'approval_table_action' => 'empty',
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_links/' . $item_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $item_to_update_id)
        );
        $this->assertEquals($new_version_response->getStatusCode(), 200);

        $this->checkItemHasADisabledApprovalTable($items, $title);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPostVersionItThrowsExceptionWhenUserSetApprovalTableOnItemWithoutApprovalTable(
        array $items,
    ): void {
        $item_to_update    = $this->findItemByTitle($items, 'POST L V No AT');
        $item_to_update_id = $item_to_update['id'];

        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                'link_properties'  => ['link_url' => 'https://example.com'],
                'should_lock_file'      => false,
                'approval_table_action' => 'reset',
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_links/' . $item_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(400, $new_version_response->getStatusCode());
        $this->assertStringContainsString(
            'does not have an approval table',
            json_decode($new_version_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['i18n_error_message']
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
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
                'should_lock_file'    => false,
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_links/' . $item_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'docman_items/' . $item_to_update_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('link', $response_json['type']);
        $this->assertNull($response_json['lock_info']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
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
                'should_lock_file'    => false,
            ]
        );
        $new_version_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_links/' . $item_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(200, $new_version_response->getStatusCode());

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $item_to_update_id)
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(200, $response->getStatusCode());
        $response_json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('link', $response_json['type']);
        $this->assertNull($response_json['lock_info']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
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
                'should_lock_file'    => false,
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_links/' . $item_to_update_id . '/version')->withBody($this->stream_factory->createStream($new_version_resource))
        );

        $this->assertEquals(403, $new_version_response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetDocumentItemsForAdminUser')]
    public function testPutBasicHardcodedMetadata(array $items): void
    {
        $item_to_update    = $this->findItemByTitle($items, 'PUT L');
        $item_to_update_id = $item_to_update['id'];

        $this->assertEquals('PUT L', $item_to_update['title']);
        $this->assertEquals('', $item_to_update['description']);
        $this->assertEquals($this->docman_user_id, $item_to_update['owner']['id']);

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $item_to_update['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $put_resource = [
            'id'                => $item_to_update_id,
            'title'             => 'PUT L New Title',
            'description'       => 'Danger ! Danger !',
            'owner_id'          => $this->test_user_1_id,
            'status'            => 'none',
        ];

        $updated_metadata_file_response_with_rest_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'docman_links/' . $item_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $updated_metadata_file_response_with_rest_read_only_user->getStatusCode());

        $updated_metadata_file_response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('PUT', 'docman_links/' . $item_to_update_id . '/metadata')->withBody($this->stream_factory->createStream(json_encode($put_resource)))
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

        $this->assertEquals('PUT L New Title', $new_version['title']);
        $this->assertEquals('Danger ! Danger !', $new_version['description']);
        $this->assertEquals($this->test_user_1_id, $new_version['owner']['id']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsMetadata(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_links/' . $id . '/metadata'),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptions(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'docman_links/' . $id), BaseTestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsLock(int $id): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'docman_links/' . $id . '/lock'), BaseTestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals($response->getStatusCode(), 200);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testOptionsVersion(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_links/' . $id . '/versions'),
            BaseTestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetRootId')]
    public function testAllOptionsForUserRESTReadOnlyAdmin(int $id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_links/' . $id . '/metadata'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_links/' . $id),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'PATCH', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_links/' . $id . '/lock'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST', 'DELETE'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_links/' . $id . '/versions'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     *
     * @return mixed
     */
    private function createLinkAndReturnItsId(int $root_id, string $query)
    {
        $response_with_reast_read_only_user = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/links')->withBody($this->stream_factory->createStream($query)),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response_with_reast_read_only_user->getStatusCode());

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('POST', 'docman_folders/' . $root_id . '/links')->withBody($this->stream_factory->createStream($query))
        );

        $this->assertEquals(201, $response1->getStatusCode());
        $response1_json = json_decode($response1->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->request_factory->createRequest('GET', $response1_json['uri'])
        );
        $this->assertEquals(200, $item_response->getStatusCode());
        $this->assertEquals('link', json_decode($item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['type']);

        return $response1_json['id'];
    }
}
