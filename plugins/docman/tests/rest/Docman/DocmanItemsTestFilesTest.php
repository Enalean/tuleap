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
use Tuleap\Docman\rest\DocmanDataBuilder;

require_once __DIR__ . '/../bootstrap.php';

class DocmanItemsTestFilesTest extends DocmanTestExecutionHelper
{
    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForAdminUser(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items_file    = $this->loadFolderContent($root_id, 'File');
        $folder_files  = $this->findItemByTitle($root_folder, 'File');
        $items_file_id = $folder_files['id'];
        $patch_files   = $this->loadFolderContent($items_file_id, 'PATCH File');
        $deleted_files = $this->loadFolderContent($items_file_id, 'DELETE File');
        $lock_files    = $this->loadFolderContent($items_file_id, 'LOCK File');
        $post_files    = $this->loadFolderContent($items_file_id, 'POST File Version');

        return array_merge(
            $root_folder,
            $folder_files,
            $items_file,
            $patch_files,
            $deleted_files,
            $lock_files,
            $post_files
        );
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchWithCopyApprovalTableAction(array $items): void
    {
        $file_name = 'PATCH F AT C';
        $this->checkItemHasAnApprovalTable($items, $file_name, 'Approved');
        $file = $this->findItemByTitle($items, $file_name);

        $file_size    = 305;
        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'file_properties'       => ['file_name' => 'file1', 'file_size' => $file_size],
                'approval_table_action' => 'copy',
                'title'                 => $file_name
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
        $this->checkItemHasAnApprovalTable($items, $file_name, 'Approved');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchWithResetApprovalTableAction(array $items): void
    {
        $file_name = 'PATCH F AT R';
        $this->checkItemHasAnApprovalTable($items, $file_name, 'Approved');
        $file = $this->findItemByTitle($items, $file_name);

        $file_size    = 305;
        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'file_properties'       => ['file_name' => 'file1', 'file_size' => $file_size],
                'approval_table_action' => 'reset',
                'title'                 => $file_name
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
        $this->checkItemHasAnApprovalTable($items, $file_name, 'Not yet');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchWithEmptyApprovalTableAction(array $items): void
    {
        $file_name = 'PATCH F AT E';
        $this->checkItemHasAnApprovalTable($items, $file_name, 'Approved');
        $file = $this->findItemByTitle($items, $file_name);

        $file_size    = 305;
        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'file_properties'       => ['file_name' => 'file1', 'file_size' => $file_size],
                'approval_table_action' => 'empty',
                'title'                 => $file_name
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
        $this->checkItemHasADisabledApprovalTable($items, $file_name);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenItemHasAnApprovalTableAndWhenRepresentationDoesNotProvideItsValue(array $items): void
    {
        $file_name = 'PATCH F AT';
        $this->checkItemHasAnApprovalTable($items, $file_name, 'Approved');
        $file = $this->findItemByTitle($items, $file_name);

        $file_size    = 305;
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => $file_size],
                'title'            => $file_name
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("provide an option", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchThrowsExceptionWhenItemHasNoApprovalTableButApprovalTableActionIsProvidedInRepresentation(array $items): void
    {
        $file = $this->findItemByTitle($items, 'PATCH F NO AT');

        $file_size    = 305;
        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'should_lock_file'      => false,
                'file_properties'       => ['file_name' => 'file1', 'file_size' => $file_size],
                'approval_table_action' => 'copy',
                'title'                 => 'new title'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("without approval", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPATCHThrowsAnExceptionWhenPatchIsCalledOnANonFileItem(array $items): void
    {
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 10],
                'title'            => 'new title'
            ]
        );

        $folder_item = $this->findItemByTitle($items, 'PATCH File');
        $response    = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->patch(
                'docman_files/' . $folder_item["id"],
                null,
                $put_resource
            )
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("Docman_File", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchAdminShouldAlwaysBeAbleToUnlockADocument(array $items): void
    {
        $file = $this->findItemByTitle($items, 'PATCH F RL');

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 10],
                'title'            => 'new title'
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
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchRegularUserCanNotUnlockADocumentLockedByAnOtherUser(array $items): void
    {
        $file = $this->findItemByTitle($items, 'PATCH F AL');

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 10],
                'title'            => 'new title'
            ]
        );

        $response = $this->getResponseByName(
            DocmanFileDataBuild::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch(
                'docman_files/' . $file['id'],
                null,
                $put_resource
            )
        );
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchFileDocumentIsRejectedIfFileIsTooBig(array $items): void
    {
        $file         = $this->findItemByTitle($items, 'PATCH F KO');
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 999999999999],
                'title'            => 'PATCH F'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("size", $response->json()["error"]['message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPATCHIsRejectedIfAFileIsBeingUploadedForTheSameNameByADifferentUser(array $items): void
    {
        $file         = $this->findItemByTitle($items, 'PATCH F KO');
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 10],
                'title'            => 'new title'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("/uploads/docman/version/5", $response->json()['upload_href']);


        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 10],
                'title'            => 'new title'
            ]
        );

        $response = $this->getResponse(
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertStringContainsString("Conflict", $response->json()["error"]['message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchFileDocumentReturnsFileRepresentation(array $items): void
    {
        $file         = $this->findItemByTitle($items, 'PATCH F NO AT');
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'file1', 'file_size' => 10],
                'title'            => 'new title'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->patch('docman_files/' . $file['id'], null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $item = $response->json();
        $this->assertEquals("/uploads/docman/version/6", $item['upload_href']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchFileDocument(array $items): void
    {
        $folder    = $this->findItemByTitle($items, 'File');
        $folder_id = $folder['id'];

        $file_id = $this->createANewFileAndGetItsId($folder_id, "My new file");

        $file_size    = 123;
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'My new file', 'file_size' => $file_size],
                'title'            => 'new title'
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
        $item = $response->json();
        $this->assertEquals('file', $item['type']);
        $this->assertEquals(null, $item['lock_info']);
        $this->assertFalse($item['has_approval_table']);
        $this->assertFalse($item['is_approval_table_enabled']);
        $this->assertNull($item['approval_table']);

        $file_content_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $general_use_http_client->get($response->json()['file_properties']['download_href'])
        );
        $this->assertEquals(200, $file_content_response->getStatusCode());
        $this->assertEquals($file_content, $file_content_response->getBody());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPatchAndLockAFileDocument(array $items): void
    {
        $folder    = $this->findItemByTitle($items, 'File');
        $folder_id = $folder['id'];

        $file_id = $this->createANewFileAndGetItsId($folder_id, "My new locked file title");

        $file_size    = 123;
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => true,
                'file_properties'  => ['file_name' => 'My new file', 'file_size' => $file_size],
                'title'            => 'new title'
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
        $this->assertNotNull($response->json()['lock_info']);

        $file_content_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $general_use_http_client->get($response->json()['file_properties']['download_href'])
        );
        $this->assertEquals(200, $file_content_response->getStatusCode());
        $this->assertEquals($file_content, $file_content_response->getBody());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDocumentCreationWithASameNameIsNotRejectedWhenTheUploadHasBeenCanceled(array $items): void
    {
        $file_name = 'PATCH F';
        $file      = $this->findItemByTitle($items, $file_name);
        $file_id   = $file['id'];

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'My new file', 'file_size' => 123],
                'title'            => $file_name
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
    public function testPatchFileDocumentWithStatusWhenStatusIsNotEnabledForProject(int $root_id): void
    {
        $query = json_encode(
            [
                'title'           => 'My new file with status',
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

        $file_size    = 123;
        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'should_lock_file' => false,
                'file_properties'  => ['file_name' => 'My new file', 'file_size' => $file_size],
                'status'           => 'approved',
                'title'            => 'new title'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("Status", $response->json()["error"]['i18n_error_message']);
    }


    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteThrowsAnErrorWhenUserHasNotPermissionToDeleteTheFile(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE F RO');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteThrowAPermissionErrorWhenTheFileIsLockedByAnotherUser(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE F L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->delete('docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);

        $this->checkItemHasNotBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testDeleteIsProceedWhenFileIsLockedAndUserIsAdmin(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE F L');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItDeletesAFile(array $items): void
    {
        $file_to_delete    = $this->findItemByTitle($items, 'DELETE F');
        $file_to_delete_id = $file_to_delete['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_files/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->checkItemHasBeenDeleted($file_to_delete_id);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testLockThrowsAndExceptionWhenUserCanNotReadTheFile(array $items): void
    {
        $locked_document   = $this->findItemByTitle($items, 'LOCK F RO');
        $file_to_delete_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::TEST_USER_2_NAME,
            $this->client->post('docman_files/' . $file_to_delete_id . '/lock')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItLocksAFile(array $items): void
    {
        $file_to_lock    = $this->findItemByTitle($items, 'LOCK F');
        $file_to_lock_id = $file_to_lock['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_files/' . $file_to_lock_id . "/lock")
        );

        $this->assertEquals(201, $response->getStatusCode());

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_lock_id)
        );

        $file = $response->json();
        $this->assertEquals($file['lock_info'] ["locked_by"]["username"], DocmanDataBuilder::ADMIN_USER_NAME);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostLockThrowsAndExceptionIfAnOtherUserHasLockedTheFile(array $items): void
    {
        $locked_document   = $this->findItemByTitle($items, 'LOCK F AL');
        $file_to_delete_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_files/' . $file_to_delete_id . '/lock')
        );

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString("allowed", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testAdminShouldAlwaysBeAbleToUnLockAFile(array $items): void
    {
        $locked_document   = $this->findItemByTitle($items, 'LOCK F AL');
        $file_to_delete_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->delete('docman_files/' . $file_to_delete_id . '/lock')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testItThrowsExceptionForDeleteLockWhenUserCanNotReadTheFile(array $items): void
    {
        $locked_document   = $this->findItemByTitle($items, 'LOCK F RO');
        $file_to_delete_id = $locked_document['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::TEST_USER_2_NAME,
            $this->client->delete('docman_files/' . $file_to_delete_id . '/lock')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionItCreatesAFile(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version              = $current_version_response->json();
        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();
        $file_size                    = 15;
        $new_version_resource         = json_encode(
            [
                'version_title'   => 'My new versionnn',
                'description'     => 'whatever',
                "file_properties" => [
                    "file_name" => "string",
                    "file_size" => $file_size
                ],
                "should_lock_file"      => false
            ]
        );
        $new_version_response         = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $this->assertNotNull($new_version_response->json()['upload_href']);

        $general_use_http_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $general_use_http_client->setSslVerification(false, false, false);
        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $general_use_http_client->patch(
                $new_version_response->json()['upload_href'],
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

        $new_version_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version_file            = $new_version_file_response->json();
        $date_after_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version_file['last_update_date']
        );
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->checkItemHasADisabledApprovalTable($items, 'POST F V');
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionCopyThePreviousApprovalTableStatus(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V AT C');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version = $current_version_response->json();

        $current_version_approval_table = $current_version['approval_table']['approval_state'];
        $this->assertEquals($current_version_approval_table, 'Approved');

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();
        $file_size                    = 15;
        $new_version_resource         = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                "file_properties"       => [
                    "file_name" => "string",
                    "file_size" => $file_size
                ],
                "should_lock_file"      => false,
                'approval_table_action' => 'copy'
            ]
        );
        $new_version_response         = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $this->assertNotNull($new_version_response->json()['upload_href']);

        $general_use_http_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $general_use_http_client->setSslVerification(false, false, false);
        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $general_use_http_client->patch(
                $new_version_response->json()['upload_href'],
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

        $new_version_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version       = $new_version_file_response->json();
        $date_after_update = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version['last_update_date']
        );

        $this->checkItemHasAnApprovalTable($items, 'POST F V AT C', 'Approved');

        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionResetTheApprovalTableStatus(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V AT R');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version = $current_version_response->json();

        $current_version_approval_table = $current_version['approval_table']['approval_state'];
        $this->assertEquals($current_version_approval_table, 'Approved');

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();
        $file_size                    = 15;
        $new_version_resource         = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                "file_properties"       => [
                    "file_name" => "string",
                    "file_size" => $file_size
                ],
                "should_lock_file"      => false,
                'approval_table_action' => 'reset'
            ]
        );
        $new_version_response         = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $this->assertNotNull($new_version_response->json()['upload_href']);

        $general_use_http_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $general_use_http_client->setSslVerification(false, false, false);
        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $general_use_http_client->patch(
                $new_version_response->json()['upload_href'],
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

        $new_version_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version       = $new_version_file_response->json();
        $date_after_update = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version['last_update_date']
        );

        $this->checkItemHasAnApprovalTable($items, 'POST F V AT R', 'Not yet');

        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionDisableApprovalTable(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V AT E');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version = $current_version_response->json();

        $current_version_approval_table = $current_version['approval_table']['approval_state'];
        $this->assertEquals($current_version_approval_table, 'Approved');

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();
        $file_size                    = 15;
        $new_version_resource         = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                "file_properties"       => [
                    "file_name" => "string",
                    "file_size" => $file_size
                ],
                "should_lock_file"      => false,
                'approval_table_action' => 'empty'
            ]
        );
        $new_version_response         = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $this->assertNotNull($new_version_response->json()['upload_href']);

        $general_use_http_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $general_use_http_client->setSslVerification(false, false, false);
        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $general_use_http_client->patch(
                $new_version_response->json()['upload_href'],
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

        $new_version_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version       = $new_version_file_response->json();
        $date_after_update = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version['last_update_date']
        );

        $this->assertTrue($new_version['has_approval_table']);

        $this->checkItemHasADisabledApprovalTable($items, 'POST F V AT E');
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThan($date_before_update_timestamp, $date_after_update_timestamp);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionItThrowsExceptionWhenUserSetApprovalTableOnItemWithoutApprovalTable(
        array $items
    ): void {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V No AT');
        $file_to_update_id = $file_to_update['id'];

        $file_size            = 15;
        $new_version_resource = json_encode(
            [
                'version_title'         => 'My new versionnn',
                'description'           => 'whatever',
                "file_properties"       => [
                    "file_name" => "string",
                    "file_size" => $file_size
                ],
                "should_lock_file"      => false,
                'approval_table_action' => 'reset'
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(400, $new_version_response->getStatusCode());
        $this->assertStringContainsString(
            "already has an approval table",
            $new_version_response->json()["error"]['i18n_error_message']
        );
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionCanUnlockAFile(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V L');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version              = $current_version_response->json();
        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $this->assertNotNull($current_version['lock_info']);

        $file_size            = 15;
        $new_version_resource = json_encode(
            [
                'version_title'    => 'My new versionnn',
                'description'      => 'whatever',
                "file_properties"  => [
                    "file_name" => "string",
                    "file_size" => $file_size
                ],
                "should_lock_file" => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $this->assertNotNull($new_version_response->json()['upload_href']);

        $general_use_http_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $general_use_http_client->setSslVerification(false, false, false);
        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $general_use_http_client->patch(
                $new_version_response->json()['upload_href'],
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

        $new_version_file_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version_file            = $new_version_file_response->json();
        $date_after_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version_file['last_update_date']
        );
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);
        $this->assertNull($new_version_file['lock_info']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionAdminAlwaysCanUnlockAFile(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V UL Admin');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version              = $current_version_response->json();
        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $this->assertNotNull($current_version['lock_info']);

        $file_size            = 15;
        $new_version_resource = json_encode(
            [
                'version_title'    => 'My new versionnn',
                'description'      => 'whatever',
                "file_properties"  => [
                    "file_name" => "string",
                    "file_size" => $file_size
                ],
                "should_lock_file" => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->post('docman_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(201, $new_version_response->getStatusCode());
        $this->assertNotNull($new_version_response->json()['upload_href']);

        $general_use_http_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $general_use_http_client->setSslVerification(false, false, false);
        $file_content        = str_repeat('A', $file_size);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $general_use_http_client->patch(
                $new_version_response->json()['upload_href'],
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

        $new_version_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version_file            = $new_version_file_response->json();
        $date_after_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version_file['last_update_date']
        );
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);
        $this->assertNull($new_version_file['lock_info']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testPostVersionRegularUserCanNotUnlockFileLockedByOtherUser(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'POST F V L Admin');
        $file_to_update_id = $file_to_update['id'];

        $current_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($current_version_response->getStatusCode(), 200);

        $current_version              = $current_version_response->json();
        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $current_version['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();
        $this->assertNotNull($current_version['lock_info']);

        $file_size            = 15;
        $new_version_resource = json_encode(
            [
                'version_title'    => 'My new versionnn',
                'description'      => 'whatever',
                "file_properties"  => [
                    "file_name" => "string",
                    "file_size" => $file_size
                ],
                "should_lock_file" => false
            ]
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_files/' . $file_to_update_id . "/version", null, $new_version_resource)
        );

        $this->assertEquals(403, $new_version_response->getStatusCode());
        $this->assertNotNull($current_version['lock_info']);

        $new_version_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );
        $this->assertEquals($new_version_file_response->getStatusCode(), 200);

        $new_version_file            = $new_version_file_response->json();
        $date_after_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version_file['last_update_date']
        );
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertEquals($date_before_update_timestamp, $date_after_update_timestamp);
        $this->assertEquals($new_version_file['lock_info'], $current_version['lock_info']);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptionsLock($id): void
    {
        $response = $this->getResponse($this->client->options('docman_files/' . $id . '/lock'), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(array('OPTIONS', 'POST', 'DELETE'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptionsVersion(int $id): void
    {
        $response = $this->getResponse(
            $this->client->options('docman_files/' . $id . '/version'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    private function createANewFileAndGetItsId(int $parent_folder_id, string $title): int
    {
        $query = json_encode(
            [
                'title'           => $title,
                'parent_id'       => $parent_folder_id,
                'type'            => 'file',
                'file_properties' => ['file_name' => 'file1', 'file_size' => 0],
            ]
        );

        $created_file = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $parent_folder_id . '/files', null, $query)
        );

        $this->assertEquals(201, $created_file->getStatusCode());
        $this->assertEmpty($created_file->json()['file_properties']['upload_href']);

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($created_file->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);

        return $created_file->json()['id'];
    }
}
