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

namespace Tuleap\Docman\Test\rest\DocmanMandatoryMetadata;

require_once __DIR__ . '/../../../vendor/autoload.php';

use DateTimeZone;
use Guzzle\Http\Client;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanHardcodedMetadataExecutionHelper;

class HardcodedMetadataTest extends DocmanHardcodedMetadataExecutionHelper
{
    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForAdmin(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items = $this->loadFolderContent($root_id, 'Folder HM');

        return array_merge(
            $root_folder,
            $items
        );
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostFolderStatus(array $items): int
    {
        $headers = ['Content-Type' => 'application/json'];

        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $query = json_encode(
            [
                'title'       => 'Faboulous Folder With Status',
                'description' => 'A description',
                'status'      => 'approved'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . "/folders", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        return $response->json()['id'];
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostEmptyWithStatusAndObsolescenceDate(array $items): int
    {
        $headers = ['Content-Type' => 'application/json'];

        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $query = json_encode(
            [
                'title'             => 'Faboulous Empty With Status',
                'status'            => 'rejected',
                'obsolescence_date' => '3000-08-20'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . "/empties", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        return $response->json()['id'];
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostEmbeddedFileWithStatusAndObsolescenceDate(array $items): int
    {

        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $headers             = ['Content-Type' => 'application/json'];
        $embedded_properties = ['content' => 'step4 : Play with metadata'];
        $query               = json_encode(
            [
                'title'               => 'How to become a Tuleap 4 (embedded version)',
                'description'         => 'A description',
                'embedded_properties' => $embedded_properties,
                'status'              => 'approved',
                'obsolescence_date'   => '3000-08-25'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . "/embedded_files", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        return $response->json()['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testPostLinkWithStatusAndObsolescenceDate(int $root_id): int
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'             => 'To the future 2',
                'description'       => 'A description',
                'link_properties'   => $link_properties,
                'status'            => 'approved',
                'obsolescence_date' => '3000-08-25'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/links", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        return $response->json()['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiWithStatusAndObsolescenceDate(int $root_id): int
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'             => 'How to become a Tuleap wiki version 2',
                'description'       => 'A description',
                'wiki_properties'   => $wiki_properties,
                'status'            => 'approved',
                'obsolescence_date' => '3000-08-08'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/wikis", $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        return $response->json()['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileDocumentWithStatusAndObsolescenceDate(int $root_id): int
    {
        $file_size = 123;

        $date = new \DateTimeImmutable();
        $date = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date = $date->setTime(0, 0, 0);

        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'             => 'File1',
                'file_properties'   => ['file_name' => 'file1', 'file_size' => $file_size],
                'status'            => 'approved',
                'obsolescence_date' => $formatted_date
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertNotEmpty($response1->json()['file_properties']['upload_href']);

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . '/files', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertSame(
            $response1->json()['file_properties']['upload_href'],
            $response2->json()['file_properties']['upload_href']
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
                $response1->json()['file_properties']['upload_href'],
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

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);

        $file_content_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $general_use_http_client->get($file_item_response->json()['file_properties']['download_href'])
        );
        $this->assertEquals(200, $file_content_response->getStatusCode());
        $this->assertEquals($file_content, $file_content_response->getBody());

        return $file_item_response->json()['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileWithStatusThrows400IfTheDateIsNotWellFormatted(int $root_id): void
    {
        $file_size = 123;
        $headers   = ['Content-Type' => 'application/json'];
        $query     = json_encode(
            [
                'title'             => 'My File2352',
                'file_properties'   => ['file_name' => 'file1', 'file_size' => $file_size],
                'status'            => 'approved',
                'obsolescence_date' => '3019-05-2030096'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/files", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("YYYY-MM-DD", $response->json()["error"]['i18n_error_message']);
    }

    /**
     * @depends testPostLinkWithStatusAndObsolescenceDate
     */
    public function testPatchLinksDocumentWithHardcodedMetadata($links_id): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $date = new \DateTimeImmutable();
        $date = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date = $date->setTime(0, 0, 0);

        $put_resource = json_encode(
            [
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'title'             => 'My Super link to patch',
                'should_lock_file'  => false,
                'status'            => 'rejected',
                'obsolescence_date' => $date->modify('+2 day')->format('Y-m-d'),
                'link_properties'   => ['link_url' => 'https://example.test.com']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $links_id, $headers, $put_resource)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $links_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->checkStatus($response, 'Rejected');
        $this->checkObsolesenceDate($response, $date);

        $this->assertEquals('link', $response->json()['type']);
        $this->assertEquals('https://example.test.com', $response->json()['link_properties']['link_url']);
    }

    /**
     * @depends testPostWikiWithStatusAndObsolescenceDate
     */
    public function testPatchWikiDocument(int $wiki_id): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $date = new \DateTimeImmutable();
        $date = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date = $date->setTime(0, 0, 0);

        $put_resource = json_encode(
            [
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'title'             => 'How to patch',
                'should_lock_file'  => false,
                'status'            => 'approved',
                'obsolescence_date' => $date->modify('+2 day')->format('Y-m-d'),
                'wiki_properties'   => ['page_name' => 'Step 2: Do not use wiki service ( updated )'],
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_wikis/' . $wiki_id, $headers, $put_resource)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $wiki_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('wiki', $response->json()['type']);
        $this->assertEquals('Step 2: Do not use wiki service ( updated )', $response->json()['wiki_properties']['page_name']);

        $this->checkStatus($response, 'Approved');
        $this->checkObsolesenceDate($response, $date);
    }

    /**
     * @depends testPostFileDocumentWithStatusAndObsolescenceDate
     */
    public function testPatchFileDocument(int $file_id): void
    {
        $date = new \DateTimeImmutable();
        $date = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date = $date->setTime(0, 0, 0);

        $file_size    = 123;
        $put_resource = json_encode(
            [
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'should_lock_file'  => false,
                'file_properties'   => ['file_name' => 'My new file', 'file_size' => $file_size],
                'title'             => 'My new file title',
                'description'       => 'Description',
                'status'            => 'approved',
                'obsolescence_date' => $date->modify('+2 day')->format('Y-m-d')
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->json()['upload_href']);

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

        $this->assertEquals('file', $response->json()['type']);
        $this->checkStatus($response, 'Approved');
        $this->checkObsolesenceDate($response, $date);
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchFileWithObsolescenceDateThrows400IfTheDateIsNotWellFormatted(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'             => 'An other new file',
                'file_properties'   => ['file_name' => 'file1', 'file_size' => 0],
                'status'            => 'rejected',
                'obsolescence_date' => $formatted_date
            ]
        );

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/files', $headers, $query)
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
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'should_lock_file'  => false,
                'file_properties'   => ['file_name' => 'My new file', 'file_size' => $file_size],
                'title'             => 'My new file title',
                'status'            => 'approved',
                'obsolescence_date' => '2050-25-558548'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPutFileHardcodedMetadataWithAllHardcodedMetadata(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'PUT F');
        $file_to_update_id = $file_to_update['id'];

        $this->assertEquals('PUT F', $file_to_update['title']);
        $this->assertEquals('', $file_to_update['description']);

        $date_before_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $file_to_update['last_update_date']
        );
        $date_before_update_timestamp = $date_before_update->getTimestamp();

        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $put_resource = [
            'id'                => $file_to_update_id,
            'title'             => 'PUT File with new title',
            'description'       => 'Whoo ! Whoo !',
            'owner_id'          => 101,
            'status'            => 'approved',
            'obsolescence_date' => $formatted_date
        ];

        $updated_metadata_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->put('docman_files/' . $file_to_update_id . '/metadata', $headers, $put_resource)
        );

        $this->assertEquals(200, $updated_metadata_file_response->getStatusCode());

        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($new_version_response->getStatusCode(), 200);

        $new_version = $new_version_response->json();

        $date_after_update           = \DateTimeImmutable::createFromFormat(
            \DateTime::ATOM,
            $new_version['last_update_date']
        );
        $date_after_update_timestamp = $date_after_update->getTimestamp();
        $this->assertGreaterThanOrEqual($date_before_update_timestamp, $date_after_update_timestamp);

        $this->assertEquals('PUT File with new title', $new_version['title']);
        $this->assertEquals('Whoo ! Whoo !', $new_version['description']);
        $this->assertEquals(101, $new_version['owner']['id']);
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPutFileHardcodedMetadataThrows400WhenBadDateFormatForObsolescenceDate(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'PUT F OD');
        $file_to_update_id = $file_to_update['id'];

        $this->assertEquals('', $file_to_update['description']);

        $headers = ['Content-Type' => 'application/json'];

        $put_resource = [
            'id'                => $file_to_update_id,
            'title'             => 'PUT F',
            'description'       => 'Whoo ! Whoo !',
            'owner_id'          => 101,
            'obsolescence_date' => '2020-51-1515410',
            'status'            => 'none'
        ];

        $updated_metadata_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->put('docman_files/' . $file_to_update_id . '/metadata', $headers, $put_resource)
        );

        $this->assertEquals(400, $updated_metadata_file_response->getStatusCode());
        $this->assertStringContainsString(
            'format is incorrect',
            $updated_metadata_file_response->json()['error']['i18n_error_message']
        );
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPutFolderMetadataRecursionOnAllItems(array $items): int
    {
        $folder_to_update    = $this->findItemByTitle($items, 'Folder HM');
        $folder_to_update_id = $folder_to_update['id'];

        $put_resource = [
            'title'  => 'Folder HM UPDATED',
            'status' => ['value' => 'draft', 'recursion' => 'all_items'],
        ];

        $updated_metadata_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->put('docman_folders/' . $folder_to_update_id . '/metadata', null, $put_resource)
        );

        $this->assertEquals(200, $updated_metadata_file_response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $folder_to_update_id . '/docman_items'),
            DocmanDataBuilder::ADMIN_USER_NAME
        );

        $updated_content = $response->json();

        $folder_updated    = $this->findItemByTitle($updated_content, 'PUT HM FO');
        $status   = $this->findMetadataByName($folder_updated['metadata'], 'Status');
        $this->assertEquals(
            'Draft',
            $status['list_value'][0]['name']
        );

        $item_updated    = $this->findItemByTitle($updated_content, 'PUT F OD');
        $status   = $this->findMetadataByName($item_updated['metadata'], 'Status');
        $this->assertEquals(
            'Draft',
            $status['list_value'][0]['name']
        );

        return $folder_to_update_id;
    }

    /**
     * @depends testPutFolderMetadataRecursionOnAllItems
     */
    public function testPutFolderMetadataRecursionOnFolder($folder_to_update_id): void
    {
        $put_resource = [
            'title'  => 'Folder HM',
            'status' => ['value' => 'draft', 'recursion' => 'folders'],
        ];

        $updated_metadata_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->put('docman_folders/' . $folder_to_update_id . '/metadata', null, $put_resource)
        );

        $this->assertEquals(200, $updated_metadata_file_response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $folder_to_update_id . '/docman_items'),
            DocmanDataBuilder::ADMIN_USER_NAME
        );

        $updated_content = $response->json();

        $folder_updated    = $this->findItemByTitle($updated_content, 'PUT HM FO');
        $status   = $this->findMetadataByName($folder_updated['metadata'], 'Status');
        $this->assertEquals(
            'Draft',
            $status['list_value'][0]['name']
        );
    }

    /**
     * @param $response
     * @param $date
     */
    private function checkObsolesenceDate($response, $date): void
    {
        $obsolescence_date_metadata = $this->findMetadataByName(
            $response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ATOM,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0, 0);

        $this->assertTrue($date->modify('+2 day')->getTimestamp() === $obsolescence_date->getTimestamp());
    }

    /**
     * @param $response
     */
    private function checkStatus($response, string $value): void
    {
        $status = $this->findMetadataByName($response->json()['metadata'], 'Status');
        $this->assertEquals(
            $value,
            $status['list_value'][0]['name']
        );
    }
}
