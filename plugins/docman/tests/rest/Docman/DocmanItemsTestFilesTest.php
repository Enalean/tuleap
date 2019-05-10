<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Guzzle\Http\Client;
use REST_TestDataBuilder;
use Tuleap\Docman\rest\DocmanBase;
use Tuleap\Docman\rest\DocmanDataBuilder;

require_once __DIR__ . '/../bootstrap.php';

class DocmanItemsTestFilesTest extends DocmanBase
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
        $folder = $response->json();

        $folder_content = $this->findItemByTitle($folder, 'folder 1');
        $folder_1_id    = $folder_content['id'];
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_1_id . '/docman_items')
        );
        $items_folder_1 = $response->json();

        $folder_file = $this->findItemByTitle($folder, 'Folder A File');
        $folder_file_id = $folder_file['id'];
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_file_id . '/docman_items')
        );
        $items_file = $response->json();

        $trash_folder    = $this->findItemByTitle($folder, "Trash");
        $trash_folder_id = $trash_folder['id'];

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $trash_folder_id . '/docman_items')
        );

        $items_to_delete = $response->json();

        $items = array_merge($items_folder_1, $items_file, $items_to_delete);

        $this->assertEquals(count($items), 14);

        return $items;
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchAFilewithApprovalTableCopyAction(array $items): void
    {
        $file = $this->findItemByTitle($items, 'file AT C');

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file['id'])
        );

        $item_before_patch                           = $response->json();
        $item_approval_table_before_patch            = $item_before_patch['approval_table'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($item_approval_table_before_patch);

        $file_size    = 305;
        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'file_properties'       => ['file_name' => 'file1', 'file_size' => $file_size],
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $tus_client->patch(
                $response->json()['upload_href'],
                [
                    'Tus-Resumable' => '1.0.0',
                    'Content-Type'  => 'application/offset+octet-stream',
                    'Upload-Offset' => '0'
                ],
                str_repeat('A', $file_size)
            )
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchAFilewithApprovalTableResetAction(array $items): void
    {
        $file = $this->findItemByTitle($items, 'file AT R');

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file['id'])
        );

        $item_before_patch                = $response->json();
        $item_approval_table_before_patch = $item_before_patch['approval_table'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($item_approval_table_before_patch['approval_state'], 'Approved');

        $file_size    = 305;
        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'file_properties'       => ['file_name' => 'file1', 'file_size' => $file_size],
                'approval_table_action' => 'reset'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $tus_client->patch(
                $response->json()['upload_href'],
                [
                    'Tus-Resumable' => '1.0.0',
                    'Content-Type'  => 'application/offset+octet-stream',
                    'Upload-Offset' => '0'
                ],
                str_repeat('A', $file_size)
            )
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchAFilewithApprovalTableEmptyAction(array $items): void
    {
        $file = $this->findItemByTitle($items, 'file AT E');

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file['id'])
        );

        $item_before_patch                           = $response->json();
        $item_approval_table_before_patch            = $item_before_patch['approval_table'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotNull($item_approval_table_before_patch);
        $this->assertEquals($item_approval_table_before_patch['approval_state'], 'Approved');

        $file_size    = 305;
        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'file_properties'       => ['file_name' => 'file1', 'file_size' => $file_size],
                'approval_table_action' => 'empty'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $tus_client->patch(
                $response->json()['upload_href'],
                [
                    'Tus-Resumable' => '1.0.0',
                    'Content-Type'  => 'application/offset+octet-stream',
                    'Upload-Offset' => '0'
                ],
                str_repeat('A', $file_size)
            )
        );
        $this->assertEquals(204, $tus_response_upload->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsAnApprovalTableForTheItemAndNoApprovalAction(array $items): void
    {
        $file = $this->findItemByTitle($items, 'file AT C');

        $file_size    = 305;
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => $file_size]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenThereIsNOTApprovalTableWhileThereIsApprovalAction(array $items): void
    {
        $file = $this->findItemByTitle($items, 'file NO AT');

        $file_size    = 305;
        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'file_properties'       => ['file_name' => 'file1', 'file_size' => $file_size],
                'approval_table_action' => 'copy'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchOnDocumentWithBadMatchingBetweenThePatchedItemTypeAndAcceptedRouteType(int $root_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 10]
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_files/' . $folder[0]["id"],
                null,
                $put_resource
            )
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchOnDocumentLockedByAnOtherUserThrowException(array $items): void
    {
        $file         = $this->findItemByTitle($items, 'file L');

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 10]
            ]
        );

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_files/' . $file['id'],
                null,
                $put_resource
            )
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchFileDocumentIsRejectedIfFileIsTooBig(array $items): void
    {
        $file         = $this->findItemByTitle($items, 'file AT C');
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 999999999999]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchOnEmptyItemThrowAnException(array $items): void
    {
        $empty = $this->findItemByTitle($items, 'empty');

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 0]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $empty['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPATCHIsRejectedIfAFileIsBeingUploadedForTheSameNameByADifferentUser(array $items): void
    {
        $file         = $this->findItemByTitle($items, 'file');
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 10]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("/uploads/docman/version/4", $response->json()['upload_href']);


        $put_resource = json_encode(
            [
                'version_title'   => 'My version title',
                'changelog'       => 'I have changed',
                'should_lock_file' => false,
                'file_properties' => ['file_name' => 'file1', 'file_size' => 10],
            ]
        );

        $response = $this->getResponse(
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(409, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchFileDocumentReturnsFileRepresentation(array $items) : void
    {
        $file         = $this->findItemByTitle($items, 'file NO AT');
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 10],
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("/uploads/docman/version/5", $response->json()['upload_href']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchFileDocument(int $root_id) : void
    {
        $query = json_encode(
            [
                'title'           => 'My new file',
                'parent_id'       => $root_id,
                'type'            => 'file',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 0]
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertEmpty($response1->json()['file_properties']['upload_href']);

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);

        $file_id = $response1->json()['id'];

        $file_size = 123;
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'My new file', 'file_size' => $file_size]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->json()['upload_href']);

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(
            $response->json()['upload_href'],
            $response2->json()['upload_href']
        );

        $general_use_http_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $general_use_http_client->setSslVerification(false, false, false);
        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $general_use_http_client->patch(
                $response->json()['upload_href'],
                [
                    'Tus-Resumable' => '1.0.0',
                    'Content-Type'  => 'application/offset+octet-stream',
                    'Upload-Offset' => '0'
                ],
                $file_content
            )
        );

        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset')->toArray());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $file_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('file', $response->json()['type']);
        $this->assertEquals(null, $response->json()['lock_info']);

        $file_content_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $general_use_http_client->get($response->json()['file_properties']['download_href'])
        );
        $this->assertEquals(200, $file_content_response->getStatusCode());
        $this->assertEquals($file_content, $file_content_response->getBody());
    }

    /**
     * @depends testGetRootId
     */
    public function testPatchFileDocumentAddLock(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My second file',
                'parent_id'       => $root_id,
                'type'            => 'file',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 0]
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', null, $query)
        );

        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertEmpty($response1->json()['file_properties']['upload_href']);

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );

        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);

        $file_id = $response1->json()['id'];

        $file_size = 123;

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => true,
                'file_properties'  => ['file_name' => 'My new file', 'file_size' => $file_size]
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->json()['upload_href']);

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(
            $response->json()['upload_href'],
            $response2->json()['upload_href']
        );

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $tus_client->patch(
                $response->json()['upload_href'],
                [
                    'Tus-Resumable' => '1.0.0',
                    'Content-Type'  => 'application/offset+octet-stream',
                    'Upload-Offset' => '0'
                ],
                str_repeat('A', $file_size)
            )
        );

        $this->assertEquals(204, $tus_response_upload->getStatusCode());
        $this->assertEquals([$file_size], $tus_response_upload->getHeader('Upload-Offset')->toArray());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $file_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('file', $response->json()['type']);
        $this->assertEquals(110, $response->json()['lock_info']["locked_by"]["id"]);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDocumentCreationWithASameNameIsNotRejectedWhenTheUploadHasBeenCanceled(array $items): void
    {
        $file         = $this->findItemByTitle($items, 'file AT C');
        $file_id  = $file['id'];

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'file_properties'       => ['file_name' => 'My new file', 'file_size' => 123],
                'approval_table_action' => 'empty'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_cancel = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $tus_client->delete(
                $response->json()['upload_href'],
                ['Tus-Resumable' => '1.0.0']
            )
        );
        $this->assertEquals(204, $tus_response_cancel->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
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


        $folder_embedded = $this->findItemByTitle($folder, 'Folder A File');
        $folder_embedded_id = $folder_embedded['id'];
        $response           = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_embedded_id . '/docman_items')
        );
        $items     = $response->json();

        $reset_after_patch = $this->findItemByTitle($items, 'file AT R');
        $this->assertEquals($reset_after_patch['approval_table']["approval_state"], 'Not yet');

        $empty_after_patch = $this->findItemByTitle($items, 'file AT E');
        $this->assertNull($empty_after_patch['approval_table']["approval_state"]);

        $copy_after_patch = $this->findItemByTitle($items, 'file AT C');
        $this->assertEquals($copy_after_patch['approval_table']["approval_state"], "Approved");
    }

    /**
     * Find first item in given array of items which has given title.
     * @return array|null Found item. null otherwise.
     */
    private function findItemByTitle(array $items, string $title) : ?array
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
    public function testItThrowsAnErrorWhenUserHasNotPermissionToDeleteTheFile(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'old file L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItShouldThrowAnErrorWhenTheFileIsLockedByAnotherUser(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'old file L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItShouldDeleteWhenFileIsLockedAndUserIsAdmin(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'old file L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_delete_id)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItDeletesAFile(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'another old file');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_delete_id)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }
}
