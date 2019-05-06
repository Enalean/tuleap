<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    public function testGetDocumentItemsForAdmin($root_id)
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();

        $folder_1_id = $folder[0]['id'];
        $this->assertEquals(count($folder), 6);
        $this->assertEquals($folder[0]['user_can_write'], true);

        $response       = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_1_id . '/docman_items')
        );
        $items_folder_1 = $response->json();

        $folder_file_id = $folder[1]['id'];
        $response       = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_file_id . '/docman_items')
        );
        $items_file     = $response->json();

        $folder_2_index = 0;
        $embedded_index = 1;
        $empty_index    = 2;
        $file_index     = 3;
        $link_index     = 4;
        $wiki_index     = 5;

        $response       = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $items_folder_1[$folder_2_index]['id'] . '/docman_items')
        );
        $items_folder_2     = $response->json();

        $items = array_merge($items_folder_1, $items_file, $items_folder_2);

        $this->assertEquals(count($items), 13);

        $this->assertEquals($items[$folder_2_index]['title'], 'folder');
        $this->assertEquals($items[$empty_index]['title'], 'empty');
        $this->assertEquals($items[$file_index]['title'], 'file');
        $this->assertEquals($items[$link_index]['title'], 'link');
        $this->assertEquals($items[$embedded_index]['title'], 'embeddedFile');
        $this->assertEquals($items[$wiki_index]['title'], 'wiki');

        $this->assertEquals('Test User 1 (rest_api_tester_1)', $items[0]['owner']['display_name']);
        $this->assertEquals('Anonymous user', $items[$empty_index]['owner']['display_name']);

        $this->assertEquals($items[$folder_2_index]['user_can_write'], true);
        $this->assertEquals($items[$empty_index]['user_can_write'], true);
        $this->assertEquals($items[$file_index]['user_can_write'], true);
        $this->assertEquals($items[$link_index]['user_can_write'], true);
        $this->assertEquals($items[$embedded_index]['user_can_write'], true);
        $this->assertEquals($items[$wiki_index]['user_can_write'], true);

        $this->assertEquals($items[$folder_2_index]['is_expanded'], false);
        $this->assertEquals($items[$empty_index]['is_expanded'], false);
        $this->assertEquals($items[$file_index]['is_expanded'], false);
        $this->assertEquals($items[$link_index]['is_expanded'], false);
        $this->assertEquals($items[$embedded_index]['is_expanded'], false);
        $this->assertEquals($items[$wiki_index]['is_expanded'], false);


        $this->assertEquals($items[$folder_2_index]['file_properties'], null);
        $this->assertEquals($items[$empty_index]['file_properties'], null);
        $this->assertEquals($items[$file_index]['file_properties']['file_type'], 'application/pdf');
        $this->assertEquals(
            $items[$file_index]['file_properties']['download_href'],
            '/plugins/docman/download/' . urlencode($items[$file_index]['id']).'/1'
        );
        $this->assertEquals($items[$file_index]['file_properties']['file_size'], 3);
        $this->assertEquals($items[$link_index]['file_properties'], null);
        $this->assertEquals($items[$embedded_index]['file_properties'], null);
        $this->assertEquals($items[$wiki_index]['file_properties'], null);

        $this->assertEquals($items[$folder_2_index]['link_properties'], null);
        $this->assertEquals($items[$empty_index]['link_properties'], null);
        $this->assertEquals($items[$file_index]['link_properties'], null);
        $this->assertEquals($items[$link_index]['link_properties']['link_url'], 'https://my.example.test');

        $this->assertEquals($items[$embedded_index]['link_properties'], null);
        $this->assertEquals($items[$embedded_index]['link_properties'], null);

        $this->assertEquals($items[$folder_2_index]['embedded_file_properties'], null);
        $this->assertEquals($items[$empty_index]['embedded_file_properties'], null);
        $this->assertEquals($items[$file_index]['embedded_file_properties'], null);
        $this->assertEquals($items[$link_index]['embedded_file_properties'], null);
        $this->assertEquals($items[$embedded_index]['embedded_file_properties']['file_type'], 'text/html');
        $this->assertEquals(
            $items[$embedded_index]['embedded_file_properties']['content'],
            file_get_contents(dirname(__DIR__) . '/_fixtures/docmanFile/embeddedFile')
        );
        $this->assertEquals($items[$wiki_index]['embedded_file_properties'], null);


        $this->assertEquals($items[$folder_2_index]['link_properties'], null);
        $this->assertEquals($items[$empty_index]['link_properties'], null);
        $this->assertEquals($items[$file_index]['link_properties'], null);
        $this->assertEquals($items[$link_index]['link_properties']['link_url'], 'https://my.example.test');
        $this->assertEquals($items[$embedded_index]['link_properties'], null);
        $this->assertEquals($items[$wiki_index]['link_properties'], null);

        $this->assertEquals($items[$folder_2_index]['wiki_properties'], null);
        $this->assertEquals($items[$empty_index]['wiki_properties'], null);
        $this->assertEquals($items[$file_index]['wiki_properties'], null);
        $this->assertEquals($items[$link_index]['wiki_properties'], null);
        $this->assertEquals($items[$embedded_index]['wiki_properties'], null);
        $this->assertEquals($items[$wiki_index]['wiki_properties']['page_name'], 'MyWikiPage');

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
            $items[$empty_index]['metadata'][0],
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
            $items[$file_index]['metadata'][0],
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
            $items[$link_index]['metadata'][0],
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
            $items[$embedded_index]['metadata'][0],
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
            $items[$wiki_index]['metadata'][0],
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
    public function testRegularUserCantSeeFolderHeCantRead($root_id)
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();
        $this->assertEquals(count($folder), 1);
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
        $item     = $response->json();

        $this->assertEquals('Project Documentation', $item['title']);
        $this->assertEquals($root_id, $item['id']);
        $this->assertEquals('folder', $item['type']);
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testGetAllItemParents(array $items)
    {
        $embedded_2 = $this->findItemByTitle($items, 'embeddedFile 2');

        $project_response = $this->getResponse($this->client->get('docman_items/' . $embedded_2['id'] . '/parents'));
        $json_parents     = $project_response->json();
        $this->assertEquals(count($json_parents), 3);
        $this->assertEquals($json_parents[0]['title'], 'Project Documentation');
        $this->assertEquals($json_parents[1]['title'], 'folder 1');
        $this->assertEquals($json_parents[2]['title'], 'folder');
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testItemHasDisabledApprovalTable(array $items)
    {
        $file_E = $this->findItemByTitle($items, 'file DIS AT');

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_E['id'])
        );

        $item = $response->json();

        $this->assertTrue($item['has_approval_table']);
        $this->assertFalse($item['is_approval_table_enabled']);
        $this->assertNull($item['approval_table']);
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testItemHasEnabledApprovalTable(array $items)
    {
        $file_B = $this->findItemByTitle($items, 'file AT C');

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_B['id'])
        );

        $item = $response->json();

        $this->assertTrue($item['has_approval_table']);
        $this->assertTrue($item['is_approval_table_enabled']);
        $this->assertNotNull($item['approval_table']);
    }

    /**
     * @depends testGetDocumentItemsForAdmin
     */
    public function testItemHasNoApprovalTable(array $items)
    {
        $file_D = $this->findItemByTitle($items, 'file NO AT');

        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_D['id'])
        );

        $item = $response->json();

        $this->assertFalse($item['has_approval_table']);
        $this->assertFalse($item['is_approval_table_enabled']);
        $this->assertNull($item['approval_table']);
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
}
