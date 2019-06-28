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

declare(strict_types = 1);

namespace Tuleap\Docman\Test\rest\Docman;

require_once __DIR__ . '/../../../vendor/autoload.php';

use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;

class DocmanItemsTest extends DocmanTestExecutionHelper
{
    /**
     * @depends testGetRootId
     */
    public function testGetDocumentItemsForAdmin(int $root_id): array
    {
        $response            = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $root_folder_content = $response->json();

        $folder_1    = $this->findItemByTitle($root_folder_content, 'folder 1');
        $folder_1_id = $folder_1['id'];
        $this->assertGreaterThan(0, count($root_folder_content));
        $this->assertEquals($root_folder_content[0]['user_can_write'], true);

        $response       = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder_1_id . '/docman_items')
        );
        $items_folder_1 = $response->json();

        $folder   = $this->findItemByTitle($items_folder_1, 'folder');
        $empty    = $this->findItemByTitle($items_folder_1, 'empty');
        $file     = $this->findItemByTitle($items_folder_1, 'file');
        $embedded = $this->findItemByTitle($items_folder_1, 'embeddedFile');
        $link     = $this->findItemByTitle($items_folder_1, 'link');
        $wiki     = $this->findItemByTitle($items_folder_1, 'wiki');

        $response       = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder['id'] . '/docman_items')
        );
        $items_folder_2     = $response->json();

        $items = array_merge($items_folder_1, $items_folder_2);

        $this->assertGreaterThan(0, count($items));

        $this->assertEquals('Test User 1 (rest_api_tester_1)', $items[0]['owner']['display_name']);
        $this->assertEquals('Anonymous user', $empty['owner']['display_name']);

        $this->assertEquals($folder['user_can_write'], true);
        $this->assertEquals($empty['user_can_write'], true);
        $this->assertEquals($file['user_can_write'], true);
        $this->assertEquals($link['user_can_write'], true);
        $this->assertEquals($embedded['user_can_write'], true);
        $this->assertEquals($wiki['user_can_write'], true);

        $this->assertEquals($folder['is_expanded'], false);
        $this->assertEquals($empty['is_expanded'], false);
        $this->assertEquals($file['is_expanded'], false);
        $this->assertEquals($link['is_expanded'], false);
        $this->assertEquals($embedded['is_expanded'], false);
        $this->assertEquals($wiki['is_expanded'], false);

        $this->assertEquals($folder['file_properties'], null);
        $this->assertEquals($empty['file_properties'], null);
        $this->assertEquals($file['file_properties']['file_type'], 'application/pdf');
        $this->assertEquals(
            $file['file_properties']['download_href'],
            '/plugins/docman/download/' . urlencode((string)$file['id']) . '/1'
        );
        $this->assertEquals($file['file_properties']['file_size'], 3);
        $this->assertEquals($link['file_properties'], null);
        $this->assertEquals($embedded['file_properties'], null);
        $this->assertEquals($wiki['file_properties'], null);

        $this->assertEquals($folder['link_properties'], null);
        $this->assertEquals($empty['link_properties'], null);
        $this->assertEquals($file['link_properties'], null);
        $this->assertEquals($link['link_properties']['link_url'], 'https://my.example.test');

        $this->assertEquals($embedded['link_properties'], null);
        $this->assertEquals($embedded['link_properties'], null);

        $this->assertEquals($folder['embedded_file_properties'], null);
        $this->assertEquals($empty['embedded_file_properties'], null);
        $this->assertEquals($file['embedded_file_properties'], null);
        $this->assertEquals($link['embedded_file_properties'], null);
        $this->assertEquals($embedded['embedded_file_properties']['file_type'], 'text/html');
        $this->assertEquals(
            $embedded['embedded_file_properties']['content'],
            file_get_contents(dirname(__DIR__) . '/_fixtures/docmanFile/embeddedFile')
        );
        $this->assertEquals($wiki['embedded_file_properties'], null);

        $this->assertEquals($folder['link_properties'], null);
        $this->assertEquals($empty['link_properties'], null);
        $this->assertEquals($file['link_properties'], null);
        $this->assertEquals($link['link_properties']['link_url'], 'https://my.example.test');
        $this->assertEquals($embedded['link_properties'], null);
        $this->assertEquals($wiki['link_properties'], null);

        $this->assertEquals($folder['wiki_properties'], null);
        $this->assertEquals($empty['wiki_properties'], null);
        $this->assertEquals($file['wiki_properties'], null);
        $this->assertEquals($link['wiki_properties'], null);
        $this->assertEquals($embedded['wiki_properties'], null);
        $this->assertEquals($wiki['wiki_properties']['page_name'], 'MyWikiPage');

        $this->assertEquals(
            $folder['metadata'][0],
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
            $empty['metadata'][0],
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
            $file['metadata'][0],
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
            $link['metadata'][0],
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
            $embedded['metadata'][0],
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
            $wiki['metadata'][0],
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
    public function testRegularUserCantSeeFolderHeCantRead(int $root_id): void
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();

        $allowed_folder = $this->findItemByTitle($folder, 'folder 1');
        $this->assertNotNull($allowed_folder);
        $denied_folder = $this->findItemByTitle($folder, 'Folder RO');
        $this->assertNull($denied_folder);
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
}
