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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types = 1);

namespace Tuleap\Docman\rest\v1;

use DateTimeZone;
use Guzzle\Http\Client;
use REST_TestDataBuilder;
use Tuleap\Docman\rest\DocmanDataBuilder;
use Tuleap\Docman\rest\DocmanWithMetadataActivatedBase;

require_once __DIR__ . '/../bootstrap.php';

class DocmanHardcodedMetadataTest extends DocmanWithMetadataActivatedBase
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
    public function testGetDocumentItemsForAdmin(int $root_id): array
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folders  = $response->json();

        $this->assertNotNull($folders);
        $this->assertEquals($folders[0]['user_can_write'], true);

        $hm_folder         = $this->findItemByTitle($folders, 'Folder HM');
        $hm_folder_id      = $hm_folder['id'];
        $hm_folder_content = $this->loadRootFolderContent($hm_folder_id);

        return array_merge($folders, $hm_folder_content);
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostFolderStatus(array $items): void
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
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostEmptyWithStatusAndObsolescenceDate(array $items): void
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
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostEmptyThrows400IfTheDateIsNotWellFormatted(array $items): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $query = json_encode(
            [
                'title'             => 'Faboulous Folder With Status',
                'description'       => 'A description',
                'status'            => 'rejected',
                'obsolescence_date' => '3000-08-25844841854'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . "/empties", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostEmptyThrows400IfThereIsABadStatus(array $items): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $query = json_encode(
            [
                'title'             => 'Faboulous Folder With Status',
                'description'       => 'A description',
                'status'            => 'nononono',
                'obsolescence_date' => '3000-08-25844841854'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . "/empties", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }


    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostEmbeddedFileWithStatusAndObsolescenceDate(array $items): void
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
    }


    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostEmbeddedFileThrows400IfThereIsABadStatus(array $items): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $embedded_properties = ['content' => 'step5: Play with metadata and fail'];
        $query               = json_encode(
            [
                'title'               => 'How to become a Tuleap 5 (embedded version)',
                'description'         => 'A description',
                'embedded_properties' => $embedded_properties,
                'status'              => 'gj,sogpzjgp',
                'obsolescence_date'   => '3000-08-25'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . "/embedded_files", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostEmbeddedFileThrows400IfTheDateIsNotWellFormatted(array $items): void
    {
        $headers = ['Content-Type' => 'application/json'];

        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $embedded_properties = ['content' => 'step6 : Play with metadata and fail again'];
        $query               = json_encode(
            [
                'title'               => 'How to become a Tuleap 6 (embedded version)',
                'description'         => 'A description',
                'embedded_properties' => $embedded_properties,
                'status'              => 'approved',
                'obsolescence_date'   => '3000-08-2585487474'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . "/embedded_files", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostLinkWithStatusAndObsolescenceDate(int $root_id): void
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
    }

    /**
     * @depends testGetRootId
     */
    public function testPostLinkWithStatusThrows400IfThereIsABadStatus(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'             => 'To the future 2',
                'description'       => 'A description',
                'link_properties'   => $link_properties,
                'status'            => 'approveddg,sigvjzeg',
                'obsolescence_date' => '3000-08-25'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/links", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostLinkThrows400IfTheDateIsNotWellFormatted(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'             => 'To the future 3',
                'description'       => 'A description',
                'link_properties'   => $link_properties,
                'status'            => 'approveddg,sigvjzeg',
                'obsolescence_date' => '3000-08-25884444'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/links", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiWithStatusAndObsolescenceDate(int $root_id): void
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
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiWithStatusThrows400IfThereIsABadStatus(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'             => 'How to become a Tuleap wiki version 2',
                'description'       => 'A description',
                'wiki_properties'   => $wiki_properties,
                'status'            => 'approvedsdogjziogj',
                'obsolescence_date' => '3000-08-08'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/wikis", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiThrows400IfTheDateIsNotWellFormatted(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'             => 'How to become a Tuleap wiki version 2',
                'description'       => 'A description',
                'wiki_properties'   => $wiki_properties,
                'status'            => 'approved',
                'obsolescence_date' => '3000-08-08231'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/wikis", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPostFileDocumentWithStatusAndObsolescenceDate(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $file_size = 123;

        $headers = ['Content-Type' => 'application/json'];

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

        $response1      = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/files', $headers, $query)
        );
        $response1_json = $response1->json();
        $file_id        = $response1_json['id'];
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertNotEmpty($response1_json['file_properties']['upload_href']);

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/files', null, $query)
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

        $response_file = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_id)
        );

        $this->assertEquals(200, $response_file->getStatusCode());

        $status = $this->getMetadataByName($response_file->json()['metadata'], 'Status');
        $this->assertEquals(
            'Approved',
            $status['list_value'][0]['name']
        );
        $obsolescence_date_metadata = $this->getMetadataByName(
            $response_file->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::W3C,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0);

        $this->assertTrue($date->modify('+1 day')->getTimestamp() === $obsolescence_date->getTimestamp());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileWithStatusThrows400IfThereIsABadStatus(int $root_id): void
    {
        $file_size = 123;
        $headers   = ['Content-Type' => 'application/json'];
        $query     = json_encode(
            [
                'title'             => 'My File2352',
                'file_properties'   => ['file_name' => 'file1', 'file_size' => $file_size],
                'status'            => 'approveddfkndfnig',
                'obsolescence_date' => '3019-05-20'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $root_id . "/files", $headers, $query)
        );

        $this->assertEquals(400, $response->getStatusCode());
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
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchLinksDocumentWithHardcodedMetadata(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');


        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'             => 'My Super link to patch',
                'link_properties'   => ['link_url' => 'https://example.com'],
                'status'            => 'approved',
                'obsolescence_date' => $formatted_date
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/links', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        $links_id = $response->json()['id'];

        $link_properties = [
            'link_url'          => 'https://example.test.com',
        ];

        $put_resource = json_encode(
            [
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'title'             => 'My Super link to patch',
                'should_lock_file'  => false,
                'status'            => 'rejected',
                'obsolescence_date' => $date->modify('+2 day')->format('Y-m-d'),
                'link_properties'   => $link_properties
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
        $this->assertEquals('link', $response->json()['type']);
        $status = $this->getMetadataByName($response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Rejected',
            $status['list_value'][0]['name']
        );

        $obsolescence_date_metadata = $this->getMetadataByName(
            $response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0, 0);

        $this->assertTrue($date->modify('+2 day')->getTimestamp() === $obsolescence_date->getTimestamp());
        $this->assertEquals('https://example.test.com', $response->json()['link_properties']['link_url']);
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchLinksWithStatusThrows400IfThereIsABadStatus(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');


        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'             => 'My Super link to patch v2',
                'link_properties'   => ['link_url' => 'https://example.com'],
                'status'            => 'approved',
                'obsolescence_date' => $formatted_date
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/links', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        $links_id = $response->json()['id'];

        $link_properties = [
            'link_url' => 'https://example.test.com'
        ];

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'             => 'My Super link to patch v2',
                'should_lock_file' => false,
                'status'           => 'yolo',
                'link_properties'  => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $links_id, $headers, $put_resource)
        );

        $this->assertEquals(400, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $links_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $response->json()['type']);
        $status = $this->getMetadataByName($response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Approved',
            $status['list_value'][0]['name']
        );
        $obsolescence_date_metadata = $this->getMetadataByName(
            $response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0);

        $this->assertTrue($date->modify('+1 day')->getTimestamp() === $obsolescence_date->getTimestamp());
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchLinksWithObsolescenceDateThrows400IfTheDateIsNotWellFormatted(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'             => 'My Super link to patch v3',
                'link_properties'   => ['link_url' => 'https://example.com'],
                'status'            => 'approved',
                'obsolescence_date' => $formatted_date
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/links', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        $links_id = $response->json()['id'];

        $link_properties = [
            'link_url' => 'https://example.test.com'
        ];

        $put_resource = json_encode(
            [
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'should_lock_file'  => false,
                'title'             => 'My Super link to patch v3',
                'status'            => 'rejected',
                'obsolescence_date' => '2020-13-2011',
                'link_properties'   => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_links/' . $links_id, $headers, $put_resource)
        );

        $this->assertEquals(400, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $links_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('link', $response->json()['type']);
        $status = $this->getMetadataByName($response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Approved',
            $status['list_value'][0]['name']
        );
        $obsolescence_date_metadata = $this->getMetadataByName(
            $response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0);

        $this->assertTrue($date->modify('+1 day')->getTimestamp() === $obsolescence_date->getTimestamp());
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchWikiDocument(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');


        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'             => 'How to patch a wiki',
                'wiki_properties'  => ['page_name' => 'Step 1: Do not use wiki service'],
                'status'            => 'approved',
                'obsolescence_date' => $formatted_date
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/wikis', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        $wiki_id = $response->json()['id'];

        $put_resource = json_encode(
            [
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'title'             => 'How to patch',
                'should_lock_file'  => false,
                'status'            => 'approved',
                'obsolescence_date' => $date->modify('+2 day')->format('Y-m-d'),
                'wiki_properties'  => ['page_name' => 'Step 2: Do not use wiki service ( updated )'],
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
        $status = $this->getMetadataByName($response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Approved',
            $status['list_value'][0]['name']
        );

        $obsolescence_date_metadata = $this->getMetadataByName(
            $response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0, 0);

        $this->assertTrue($date->modify('+2 day')->getTimestamp() === $obsolescence_date->getTimestamp());
        $this->assertEquals('Step 2: Do not use wiki service ( updated )', $response->json()['wiki_properties']['page_name']);
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchWikiWithStatusThrows400IfThereIsABadStatus(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');


        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'             => 'My Super wiki to patch v2',
                'wiki_properties'  => ['page_name' => 'Step 2: Do not use wiki service ( updated )'],
                'status'            => 'approved',
                'obsolescence_date' => $formatted_date
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/wikis', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        $wiki_id = $response->json()['id'];

        $put_resource = json_encode(
            [
                'version_title'    => 'My version title',
                'changelog'        => 'I have changed',
                'title'             => 'My Super wiki to patch fail',
                'should_lock_file' => false,
                'status'           => 'yolo',
                'wiki_properties'  => ['page_name' => 'Step 2: Do not use wiki service ( updated )']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_wikis/' . $wiki_id, $headers, $put_resource)
        );

        $this->assertEquals(400, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $wiki_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('wiki', $response->json()['type']);
        $status = $this->getMetadataByName($response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Approved',
            $status['list_value'][0]['name']
        );
        $obsolescence_date_metadata = $this->getMetadataByName(
            $response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0);

        $this->assertTrue($date->modify('+1 day')->getTimestamp() === $obsolescence_date->getTimestamp());
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchWikiWithObsolescenceDateThrows400IfTheDateIsNotWellFormatted(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'             => 'My Super wiki to patch v3',
                'wiki_properties'  => ['page_name' => 'Step 2: Do not use wiki service ( updated )'],
                'status'            => 'approved',
                'obsolescence_date' => $formatted_date
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/wikis', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());

        $wiki_id = $response->json()['id'];

        $put_resource = json_encode(
            [
                'version_title'     => 'My version title',
                'changelog'         => 'I have changed',
                'should_lock_file'  => false,
                'title'             => 'My Super wiki to patch v3',
                'status'            => 'rejected',
                'obsolescence_date' => '2020-13-2011',
                'wiki_properties'  => ['page_name' => 'Step 2: Do not use wiki service ( updated )']
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_wikis/' . $wiki_id, $headers, $put_resource)
        );

        $this->assertEquals(400, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $wiki_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('wiki', $response->json()['type']);
        $status = $this->getMetadataByName($response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Approved',
            $status['list_value'][0]['name']
        );
        $obsolescence_date_metadata = $this->getMetadataByName(
            $response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0);

        $this->assertTrue($date->modify('+1 day')->getTimestamp() === $obsolescence_date->getTimestamp());
    }


    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchFileDocument(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'             => 'My new file',
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
        $status = $this->getMetadataByName($response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Approved',
            $status['list_value'][0]['name']
        );

        $obsolescence_date_metadata = $this->getMetadataByName(
            $response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0, 0);

        $this->assertTrue($date->modify('+2 day')->getTimestamp() === $obsolescence_date->getTimestamp());
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
                'title'             => 'My new file',
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

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $file_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $status = $this->getMetadataByName($response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Rejected',
            $status['list_value'][0]['name']
        );

        $obsolescence_date_metadata = $this->getMetadataByName(
            $response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0, 0);

        $this->assertTrue($date->modify('+1 day')->getTimestamp() === $obsolescence_date->getTimestamp());
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchFileWithStatusThrows400IfThereIsABadStatus(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');

        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'             => 'My new file 2',
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
                'status'            => 'huhuhuhu',
                'obsolescence_date' => $date->modify('+2 day')->format('Y-m-d')
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_files/' . $file_id, null, $put_resource)
        );
        $this->assertEquals(400, $response->getStatusCode());

        $response = $this->getResponse(
            $this->client->get('docman_items/' . $file_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $status = $this->getMetadataByName($response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Rejected',
            $status['list_value'][0]['name']
        );

        $obsolescence_date_metadata = $this->getMetadataByName(
            $response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0, 0);

        $this->assertTrue($date->modify('+1 day')->getTimestamp() === $obsolescence_date->getTimestamp());
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchEmbeddedFileDocumentWithHardcodedMetadata(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');


        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'               => 'My Super embbeded to patch',
                'embedded_properties' => ['content' => 'content 1'],
                'status'              => 'approved',
                'obsolescence_date'   => $formatted_date
            ]
        );

        $post_embedded_file_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/embedded_files', $headers, $query)
        );

        $this->assertEquals(201, $post_embedded_file_response->getStatusCode());

        $embedded_file_id = $post_embedded_file_response->json()['id'];

        $embedded_properties = [
            'content' => 'TULEAAAAAAAAP',
        ];

        $put_resource = json_encode(
            [
                'version_title'         => 'My version title',
                'changelog'             => 'I have changed',
                'title'                 => 'My embedded to patched with a new title',
                'should_lock_file'      => false,
                'status'                => 'rejected',
                'obsolescence_date'     => $date->modify('+2 day')->format('Y-m-d'),
                'embedded_properties'   => $embedded_properties
            ]
        );

        $patch_embedded_file_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded_file_id, $headers, $put_resource)
        );

        $this->assertEquals(200, $patch_embedded_file_response->getStatusCode());

        $get_embedded_file_response = $this->getResponse(
            $this->client->get('docman_items/' . $embedded_file_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $get_embedded_file_response->getStatusCode());
        $this->assertEquals('embedded', $get_embedded_file_response->json()['type']);
        $status = $this->getMetadataByName($get_embedded_file_response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Rejected',
            $status['list_value'][0]['name']
        );

        $obsolescence_date_metadata = $this->getMetadataByName(
            $get_embedded_file_response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0, 0);

        $this->assertTrue($date->modify('+2 day')->getTimestamp() === $obsolescence_date->getTimestamp());
        $this->assertEquals(
            'TULEAAAAAAAAP',
            $get_embedded_file_response->json()['embedded_file_properties']['content']
        );
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchEmbeddedFileThrows400IfTheDateIsNotWellFormatted(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');


        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'               => 'My fail embbeded to patch',
                'embedded_properties' => ['content' => 'content 1'],
                'status'              => 'approved',
                'obsolescence_date'   => $formatted_date
            ]
        );

        $post_embedded_file_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/embedded_files', $headers, $query)
        );

        $this->assertEquals(201, $post_embedded_file_response->getStatusCode());

        $embedded_file_id = $post_embedded_file_response->json()['id'];

        $embedded_properties = [
            'content' => 'TULEAAAAAAAAP',
        ];

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have not changed',
                'title'               => 'My embedded to patched with a new title',
                'should_lock_file'    => false,
                'status'              => 'rejected',
                'obsolescence_date'   => '2035-02-201548',
                'embedded_properties' => $embedded_properties
            ]
        );

        $patch_embedded_file_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded_file_id, $headers, $put_resource)
        );

        $this->assertEquals(400, $patch_embedded_file_response->getStatusCode());

        $get_embedded_file_response = $this->getResponse(
            $this->client->get('docman_items/' . $embedded_file_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $get_embedded_file_response->getStatusCode());
        $this->assertEquals('embedded', $get_embedded_file_response->json()['type']);
        $status = $this->getMetadataByName($get_embedded_file_response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Approved',
            $status['list_value'][0]['name']
        );

        $obsolescence_date_metadata = $this->getMetadataByName(
            $get_embedded_file_response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0, 0);

        $this->assertTrue($date->modify('+1 day')->getTimestamp() === $obsolescence_date->getTimestamp());
        $this->assertEquals('content 1', $get_embedded_file_response->json()['embedded_file_properties']['content']);
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPatchEmbeddedFileWithStatusThrows400IfThereIsABadStatus(array $items): void
    {
        $folder_HM = $this->findItemByTitle($items, 'Folder HM');


        $headers = ['Content-Type' => 'application/json'];

        $date           = new \DateTimeImmutable();
        $date           = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date           = $date->setTime(0, 0, 0);
        $formatted_date = $date->modify('+1 day')->format('Y-m-d');

        $query = json_encode(
            [
                'title'               => 'My fail embbeded to patch fail status',
                'embedded_properties' => ['content' => 'content 1'],
                'status'              => 'approved',
                'obsolescence_date'   => $formatted_date
            ]
        );

        $post_embedded_file_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_folders/' . $folder_HM['id'] . '/embedded_files', $headers, $query)
        );

        $this->assertEquals(201, $post_embedded_file_response->getStatusCode());

        $embedded_file_id = $post_embedded_file_response->json()['id'];

        $embedded_properties = [
            'content' => 'TULEAAAAAAAAP',
        ];

        $put_resource = json_encode(
            [
                'version_title'       => 'My version title',
                'changelog'           => 'I have not changed',
                'title'               => 'My embedded to patched with a new title',
                'should_lock_file'    => false,
                'status'              => 'yeaaaaaaaaaah',
                'embedded_properties' => $embedded_properties
            ]
        );

        $patch_embedded_file_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->patch('docman_embedded_files/' . $embedded_file_id, $headers, $put_resource)
        );

        $this->assertEquals(400, $patch_embedded_file_response->getStatusCode());

        $get_embedded_file_response = $this->getResponse(
            $this->client->get('docman_items/' . $embedded_file_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(200, $get_embedded_file_response->getStatusCode());
        $this->assertEquals('embedded', $get_embedded_file_response->json()['type']);
        $status = $this->getMetadataByName($get_embedded_file_response->json()['metadata'], 'Status');
        $this->assertEquals(
            'Approved',
            $status['list_value'][0]['name']
        );

        $obsolescence_date_metadata = $this->getMetadataByName(
            $get_embedded_file_response->json()['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ISO8601,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0, 0);

        $this->assertTrue($date->modify('+1 day')->getTimestamp() === $obsolescence_date->getTimestamp());
        $this->assertEquals('content 1', $get_embedded_file_response->json()['embedded_file_properties']['content']);
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
        $this->assertEquals(102, $file_to_update['owner']['id']);

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

        $obsolescence_date_metadata = $this->getMetadataByName(
            $new_version['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];
        $obsolescence_date        = \DateTimeImmutable::createFromFormat(
            \DateTimeInterface::ATOM,
            $obsolescence_date_string
        );
        $obsolescence_date        = $obsolescence_date->setTimezone(new DateTimeZone('GMT+1'));
        $obsolescence_date        = $obsolescence_date->setTime(0, 0, 0, 0);

        $this->assertEquals($obsolescence_date->getTimestamp(), $date->modify('+1 day')->getTimestamp());
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPutFileHardcodedMetadataThrows400WhenBadDateFormatForObsolescenceDate(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'PUT F OD');
        $file_to_update_id = $file_to_update['id'];

        $this->assertEquals('', $file_to_update['description']);
        $this->assertEquals(102, $file_to_update['owner']['id']);

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
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($new_version_response->getStatusCode(), 200);

        $new_version = $new_version_response->json();

        $this->assertEquals('', $new_version['description']);
        $this->assertEquals(102, $new_version['owner']['id']);

        $obsolescence_date_metadata = $this->getMetadataByName(
            $new_version['metadata'],
            'Obsolescence Date'
        );

        $obsolescence_date_string = $obsolescence_date_metadata['value'];

        $this->assertEquals($obsolescence_date_string, '0');
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testPutFileHardcodedMetadataThrows400WhenBadStatus(array $items): void
    {
        $file_to_update    = $this->findItemByTitle($items, 'PUT F S');
        $file_to_update_id = $file_to_update['id'];

        $this->assertEquals('PUT F S', $file_to_update['title']);
        $this->assertEquals('', $file_to_update['description']);
        $this->assertEquals(102, $file_to_update['owner']['id']);

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
            'status'            => 'yoloStatus',
            'obsolescence_date' => $formatted_date
        ];

        $updated_metadata_file_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->put('docman_files/' . $file_to_update_id . '/metadata', $headers, $put_resource)
        );
        $this->assertEquals(400, $updated_metadata_file_response->getStatusCode());
        $this->assertStringContainsString(
            'Invalid value',
            $updated_metadata_file_response->json()['error']['message']
        );
        $new_version_response = $this->getResponseByName(
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_update_id)
        );

        $this->assertEquals($new_version_response->getStatusCode(), 200);

        $new_version = $new_version_response->json();

        $this->assertEquals('', $new_version['description']);
        $this->assertEquals(102, $new_version['owner']['id']);

        $status_metadata = $this->getMetadataByName(
            $new_version['metadata'],
            'Status'
        );

        $this->assertEquals($status_metadata['list_value'][0]['name'], 'None');
    }
    /**
     * Find first item in given array of items which has given title.
     *
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

    private function getMetadataByName(array $metadata, string $name)
    {
        $index = array_search($name, array_column($metadata, 'name'));
        if ($index === false) {
            return null;
        }
        return $metadata[$index];
    }

    private function loadRootFolderContent(int $root_id): array
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();
        $this->assertEquals(200, $response->getStatusCode());

        return $folder;
    }
}
