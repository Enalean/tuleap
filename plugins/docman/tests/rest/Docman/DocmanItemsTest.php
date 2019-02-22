<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Docman\rest\v1;

use Guzzle\Http\Client;
use REST_TestDataBuilder;
use Tuleap\Docman\rest\DocmanBase;
use Tuleap\Docman\rest\DocmanDataBuilder;

require_once __DIR__ . '/../bootstrap.php';

class DocmanItemsTest extends DocmanBase
{
    public function testGetRootId()
    {
        $project_response = $this->getResponse($this->client->get('projects/' . $this->project_id));

        $this->assertSame(200, $project_response->getStatusCode());

        $json_projects = $project_response->json();
        return $json_projects['additional_informations']['docman']['root_item']['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForRegularUser($root_id)
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder = $response->json();

        $this->assertEquals(count($folder), 1);
        $folder_id = $folder[0]['id'];
        $this->assertEquals($folder[0]['user_can_write'], true);

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $folder_id . '/docman_items')
        );
        $items = $response->json();

        $this->assertEquals(count($items), 9);

        $folder_2_index = 0;
        $item_a_index = 4;
        $item_c_index = 5;
        $item_e_index = 6;
        $item_f_index = 7;
        $item_g_index = 8;


        $this->assertEquals($items[$folder_2_index]['title'], 'folder 2');
        $this->assertEquals($items[1]['title'], 'file A');
        $this->assertEquals($items[2]['title'], 'file B');
        $this->assertEquals($items[$item_a_index]['title'], 'item A');
        $this->assertEquals($items[$item_c_index]['title'], 'item C');
        $this->assertEquals($items[$item_e_index]['title'], 'item E');
        $this->assertEquals($items[$item_f_index]['title'], 'item F');
        $this->assertEquals($items[$item_g_index]['title'], 'item G');

        $this->assertEquals('Test User 1 (rest_api_tester_1)', $items[0]['owner']['display_name']);
        $this->assertEquals('Anonymous user', $items[$item_a_index]['owner']['display_name']);

        $this->assertEquals($items[$folder_2_index]['user_can_write'], false);
        $this->assertEquals($items[$item_a_index]['user_can_write'], false);
        $this->assertEquals($items[$item_c_index]['user_can_write'], false);
        $this->assertEquals($items[$item_e_index]['user_can_write'], false);
        $this->assertEquals($items[$item_f_index]['user_can_write'], false);
        $this->assertEquals($items[$item_g_index]['user_can_write'], false);

        $this->assertEquals($items[$folder_2_index]['is_expanded'], false);
        $this->assertEquals($items[$item_a_index]['is_expanded'], false);
        $this->assertEquals($items[$item_c_index]['is_expanded'], false);
        $this->assertEquals($items[$item_e_index]['is_expanded'], false);
        $this->assertEquals($items[$item_f_index]['is_expanded'], false);
        $this->assertEquals($items[$item_g_index]['is_expanded'], false);


        $this->assertEquals($items[$folder_2_index]['file_properties'], null);
        $this->assertEquals($items[$item_a_index]['file_properties'], null);
        $this->assertEquals($items[$item_c_index]['file_properties']['file_type'], 'application/pdf');
        $this->assertEquals(
            $items[$item_c_index]['file_properties']['html_url'],
            '/plugins/docman/?group_id=' . urlencode($this->project_id) .
            '&action=show&id=' . urlencode($items[$item_c_index]['id']) . '&switcholdui=true'
        );
        $this->assertEquals($items[$item_e_index]['file_properties'], null);
        $this->assertEquals($items[$item_f_index]['file_properties'], null);
        $this->assertEquals($items[$item_g_index]['file_properties'], null);

        $this->assertEquals($items[$folder_2_index]['link_properties'], null);
        $this->assertEquals($items[$item_a_index]['link_properties'], null);
        $this->assertEquals($items[$item_c_index]['link_properties'], null);
        $this->assertEquals($items[$item_e_index]['link_properties']['link_url'], 'https://my.example.test');
        $this->assertEquals(
            $items[$item_e_index]['link_properties']['html_url'],
            '/plugins/docman/?group_id=' . urlencode($this->project_id) .
            '&action=show&id=' . urlencode($items[$item_e_index]['id']). '&switcholdui=true'
        );
        $this->assertEquals($items[$item_f_index]['link_properties'], null);
        $this->assertEquals($items[$item_f_index]['link_properties'], null);

        $this->assertEquals($items[$folder_2_index]['embedded_file_properties'], null);
        $this->assertEquals($items[$item_a_index]['embedded_file_properties'], null);
        $this->assertEquals($items[$item_c_index]['embedded_file_properties'], null);
        $this->assertEquals($items[$item_e_index]['embedded_file_properties'], null);
        $this->assertEquals($items[$item_f_index]['embedded_file_properties']['file_type'], 'text/html');
        $this->assertEquals(
            $items[$item_f_index]['embedded_file_properties']['content'],
            file_get_contents(dirname(__DIR__) . '/_fixtures/docmanFile/embeddedFile')
        );
        $this->assertEquals($items[$item_g_index]['embedded_file_properties'], null);


        $this->assertEquals($items[$folder_2_index]['link_properties'], null);
        $this->assertEquals($items[$item_a_index]['link_properties'], null);
        $this->assertEquals($items[$item_c_index]['link_properties'], null);
        $this->assertEquals($items[$item_e_index]['link_properties']['link_url'], 'https://my.example.test');
        $this->assertEquals($items[$item_f_index]['link_properties'], null);
        $this->assertEquals($items[$item_g_index]['link_properties'], null);

        $this->assertEquals($items[$folder_2_index]['wiki_properties'], null);
        $this->assertEquals($items[$item_a_index]['wiki_properties'], null);
        $this->assertEquals($items[$item_c_index]['wiki_properties'], null);
        $this->assertEquals($items[$item_e_index]['wiki_properties'], null);
        $this->assertEquals($items[$item_f_index]['wiki_properties'], null);
        $this->assertEquals($items[$item_g_index]['wiki_properties']['page_name'], 'MyWikiPage');
        $this->assertEquals(
            $items[$item_g_index]['wiki_properties']['html_url'],
            '/plugins/docman/?group_id=' . urlencode($this->project_id) . '&action=show&id=' .
            urlencode($items[$item_g_index]['id']) . '&switcholdui=true'
        );

        $this->assertEquals(
            $items[$folder_2_index]['metadata'][0],
            [
                "name"                      => "Custom metadata",
                "type"                      => "string",
                "value"                     => "custom value for folder_2",
                "list_value"                => null,
                "is_required"               => true,
                "is_multiple_value_allowed" => false
            ]
        );

        $this->assertEquals(
            $items[$item_a_index]['metadata'][0],
            [
                "name"                      => "Custom metadata",
                "type"                      => "string",
                "value"                     => "custom value for item_A",
                "list_value"                => null,
                "is_required"               => true,
                "is_multiple_value_allowed" => false
            ]
        );
        $this->assertEquals(
            $items[$item_c_index]['metadata'][0],
            [
                "name"                      => "Custom metadata",
                "type"                      => "string",
                "value"                     => "custom value for item_C",
                "list_value"                => null,
                "is_required"               => true,
                "is_multiple_value_allowed" => false
            ]
        );
        $this->assertEquals(
            $items[$item_e_index]['metadata'][0],
            [
                "name"                      => "Custom metadata",
                "type"                      => "string",
                "value"                     => "custom value for item_E",
                "list_value"                => null,
                "is_required"               => true,
                "is_multiple_value_allowed" => false
            ]
        );
        $this->assertEquals(
            $items[$item_f_index]['metadata'][0],
            [
                "name"                      => "Custom metadata",
                "type"                      => "string",
                "value"                     => "custom value for item_F",
                "list_value"                => null,
                "is_required"               => true,
                "is_multiple_value_allowed" => false
            ]
        );
        $this->assertEquals(
            $items[$item_g_index]['metadata'][0],
            [
                "name"                      => "Custom metadata",
                "type"                      => "string",
                "value"                     => "custom value for item_G",
                "list_value"                => null,
                "is_required"               => true,
                "is_multiple_value_allowed" => false
            ]
        );

        return $items;
    }

    /**
     * @depends testGetRootId
     */
    public function testOPTIONSDocmanItemsId($root_id)
    {
        $response = $this->getResponse(
            $this->client->options('docman_items/' . $root_id . '/docman_items'),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @depends testGetRootId
     */
    public function testOPTIONSId($root_id)
    {
        $response = $this->getResponse(
            $this->client->options('docman_items/' . $root_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @depends testGetRootId
     */
    public function testGetId($root_id)
    {
        $response = $this->getResponse(
            $this->client->get('docman_items/' . $root_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $item = $response->json();

        $this->assertEquals('Project Documentation', $item['title']);
        $this->assertEquals($root_id, $item['id']);
        $this->assertEquals('folder', $item['type']);
    }

    /**
     * @depends testGetDocumentItemsForRegularUser
     */
    public function testGetAllItemParents(array $items)
    {
        $folder_2 = $this->findItemByTitle($items, 'folder 2');

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $folder_2['id'] . '/docman_items')
        );
        $item = $response->json();

        $project_response = $this->getResponse($this->client->get('docman_items/' . $item[0]['id'] . '/parents'));
        $json_parents = $project_response->json();
        $this->assertEquals(count($json_parents), 3);
        $this->assertEquals($json_parents[0]['title'], 'Project Documentation');
        $this->assertEquals($json_parents[1]['title'], 'folder 1');
        $this->assertEquals($json_parents[2]['title'], 'folder 2');
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyDocument($root_id)
    {
        $headers = ['Content-Type' => 'application/json'];
        $query = json_encode([
            'title'       => 'Custom title',
            'description' => 'A description',
            'parent_id'   => $root_id,
            'type'        => 'empty'
        ]);

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends             testGetRootId
     */
    public function testPostDocumentIsRejectedIfDocumentAlreadyExists($root_id)
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'Custom title',
                'description' => 'A description',
                'parent_id'   => $root_id,
                'type'        => 'empty'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileDocument(int $root_id) : void
    {
        $file_size = 123;
        $query     = json_encode([
            'title'           => 'File1',
            'parent_id'       => $root_id,
            'type'            => 'file',
            'file_properties' => ['file_name' => 'file1', 'file_size' => $file_size]
        ]);

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertNotEmpty($response1->json()['file_properties']['upload_href']);

        $response2 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertSame(
            $response1->json()['file_properties']['upload_href'],
            $response2->json()['file_properties']['upload_href']
        );

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_upload = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $tus_client->patch(
                $response1->json()['file_properties']['upload_href'],
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

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostEmptyFileDocument(int $root_id) : void
    {
        $query     = json_encode([
            'title'           => 'File2',
            'parent_id'       => $root_id,
            'type'            => 'file',
            'file_properties' => ['file_name' => 'file1', 'file_size' => 0]
        ]);

        $response1 = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', null, $query)
        );
        $this->assertEquals(201, $response1->getStatusCode());
        $this->assertEmpty($response1->json()['file_properties']['upload_href']);

        $file_item_response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get($response1->json()['uri'])
        );
        $this->assertEquals(200, $file_item_response->getStatusCode());
        $this->assertEquals('file', $file_item_response->json()['type']);
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFileDocumentIsRejectedIfFileIsTooBig(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode([
            'title'           => 'File1',
            'parent_id'       => $root_id,
            'type'            => 'file',
            'file_properties' => ['file_name' => 'file1', 'file_size' => 999999999999]
        ]);

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testDocumentCreationIsRejectedIfAFileIsBeingUploadedForTheSameNameByADifferentUser(int $root_id) : void
    {
        $document_name = 'document_conflict_' . bin2hex(random_bytes(8));

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_items',
                null,
                json_encode([
                    'title'           => $document_name,
                    'parent_id'       => $root_id,
                    'type'            => 'file',
                    'file_properties' => ['file_name' => 'file', 'file_size' => 123]
                ])
            )
        );
        $this->assertEquals(201, $response->getStatusCode());

        $response2 = $this->getResponse(
            $this->client->post(
                'docman_items',
                null,
                json_encode([
                    'title'     => $document_name,
                    'parent_id' => $root_id,
                    'type'      => 'empty'
                ])
            )
        );
        $this->assertEquals(409, $response2->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testDocumentCreationWithASameNameIsNotRejectedWhenTheUploadHasBeenCanceled(int $root_id) : void
    {
        $document_name = 'document_not_conflict_after_cancel_' . bin2hex(random_bytes(8));

        $response_creation_file = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post(
                'docman_items',
                null,
                json_encode([
                    'title'           => $document_name,
                    'parent_id'       => $root_id,
                    'type'            => 'file',
                    'file_properties' => ['file_name' => 'file', 'file_size' => 123]
                ])
            )
        );
        $this->assertEquals(201, $response_creation_file->getStatusCode());

        $tus_client = new Client(
            str_replace('/api/v1', '', $this->client->getBaseUrl()),
            $this->client->getConfig()
        );
        $tus_client->setSslVerification(false, false, false);
        $tus_response_cancel = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $tus_client->delete(
                $response_creation_file->json()['file_properties']['upload_href'],
                ['Tus-Resumable' => '1.0.0']
            )
        );
        $this->assertEquals(204, $tus_response_cancel->getStatusCode());

        $response_creation_empty = $this->getResponse(
            $this->client->post(
                'docman_items',
                null,
                json_encode([
                    'title'     => $document_name,
                    'parent_id' => $root_id,
                    'type'      => 'empty'
                ])
            )
        );
        $this->assertEquals(201, $response_creation_empty->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiDocument(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query = json_encode(
            [
                'title'           => 'How to become a Tuleap',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'wiki',
                'wiki_properties' => $wiki_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostWikiReturns400IfTypeAndPropertiesDoesNotMatch(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query = json_encode(
            [
                'title'           => 'How to fail item creation',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'empty',
                'wiki_properties' => $wiki_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostLinkDocument(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'           => 'To the future',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'link',
                'link_properties' => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends             testGetRootId
     */
    public function testPostLinkReturns400IfTypeAndPropertiesDoesNotMatch(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'           => 'To the future',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'link',
                'wiki_properties' => $wiki_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostReturns403WhenPermissionDenied(int $root_id) : void
    {
        $stored_items = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        )->json();
        $folder_3 = $this->findItemByTitle($stored_items, 'folder 3');

        $query = json_encode([
            'title' => 'A title',
            'description' => 'A description',
            'parent_id' => $folder_3['id'],
            'type' => 'empty'
        ]);

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', null, $query)
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testGetRootId
     */
    public function testPostFolderItem(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $query   = json_encode(
            [
                'title'       => 'My Folder',
                'description' => 'A Folder description',
                'parent_id'   => $root_id,
                'type'        => 'folder'
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertNull($response->json()['file_properties']);
    }

    /**
     * @depends             testGetRootId
     */
    public function testPostFolderReturns400IfTypeAndPropertiesDoesNotMatch(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $link_properties = ['link_url' => 'https://turfu.example.test'];
        $query           = json_encode(
            [
                'title'           => 'To the fail future',
                'parent_id'       => $root_id,
                'type'            => 'folder',
                'link_properties' => $link_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * Find first item in given array of items which has given title.
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

    /**
     * @depends testGetRootId
     */
    public function testPostEmbeddedDocument(int $root_id): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $embedded_properties = ['content' => 'step1 : Avoid to sort items in the docman'];
        $query = json_encode(
            [
                'title'           => 'How to become a Tuleap (embedded version)',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'embedded',
                'embedded_properties' => $embedded_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends             testGetRootId
     */
    public function testPostEmbeddedReturns400IfTypeAndPropertiesDoesNotMatch(int $root_id): void
    {
        $headers         = ['Content-Type' => 'application/json'];
        $wiki_properties = ['page_name' => 'Ten steps to become a Tuleap'];
        $query           = json_encode(
            [
                'title'           => 'To the future',
                'description'     => 'A description',
                'parent_id'       => $root_id,
                'type'            => 'link',
                'embedded_properties' => $wiki_properties
            ]
        );

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->post('docman_items', $headers, $query)
        );
        $this->assertEquals(400, $response->getStatusCode());
    }
}
