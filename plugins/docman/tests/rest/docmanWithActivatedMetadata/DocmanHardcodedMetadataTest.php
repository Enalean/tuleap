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

        $this->assertEquals(count($folders), 1);
        $this->assertEquals($folders[0]['user_can_write'], true);

        return $folders;
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
}
