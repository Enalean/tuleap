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

declare(strict_types=1);

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
    public function testGetDocumentItemsForAdminUser(int $root_id): array
    {
        $this->getDocmanRegularUser();
        $root_folder = $this->loadRootFolderContent($root_id);

        $items_file    = $this->loadFolderContent($root_id, 'Folder');
        $folder_files  = $this->findItemByTitle($root_folder, 'Folder');
        $items_file_id = $folder_files['id'];
        $get           = $this->loadFolderContent($items_file_id, 'GET FO');

        $items_folder_1 = array_merge(
            $root_folder,
            $folder_files,
            $items_file,
            $get
        );

        $folder   = $this->findItemByTitle($items_folder_1, 'GET FO');
        $empty    = $this->findItemByTitle($items_folder_1, 'GET EM');
        $file     = $this->findItemByTitle($items_folder_1, 'GET F');
        $embedded = $this->findItemByTitle($items_folder_1, 'GET E');
        $link     = $this->findItemByTitle($items_folder_1, 'GET L');
        $wiki     = $this->findItemByTitle($items_folder_1, 'GET W');

        $response       = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $folder['id'] . '/docman_items')
        );
        $items_folder_2     = $response->json();

        $items = array_merge($items_folder_1, $items_folder_2);

        $this->assertGetDocumentItems($items, $folder, $empty, $file, $link, $embedded, $wiki);

        return $items;
    }

    /**
     * @depends testGetRootIdWithUserRESTReadOnlyAdmin
     */
    public function testGetDocumentItemsWithUserRESTReadOnlyAdmin(int $root_id): array
    {
        $root_folder = $this->loadRootFolderContent($root_id, REST_TestDataBuilder::TEST_BOT_USER_NAME);

        $items_file    = $this->loadFolderContent($root_id, 'Folder', REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $folder_files  = $this->findItemByTitle($root_folder, 'Folder');
        $items_file_id = $folder_files['id'];
        $get           = $this->loadFolderContent($items_file_id, 'GET FO', REST_TestDataBuilder::TEST_BOT_USER_NAME);

        $items_folder_1 = array_merge(
            $root_folder,
            $folder_files,
            $items_file,
            $get
        );

        $folder   = $this->findItemByTitle($items_folder_1, 'GET FO');
        $empty    = $this->findItemByTitle($items_folder_1, 'GET EM');
        $file     = $this->findItemByTitle($items_folder_1, 'GET F');
        $embedded = $this->findItemByTitle($items_folder_1, 'GET E');
        $link     = $this->findItemByTitle($items_folder_1, 'GET L');
        $wiki     = $this->findItemByTitle($items_folder_1, 'GET W');

        $response       = $this->getResponseByName(
            REST_TestDataBuilder::TEST_BOT_USER_NAME,
            $this->client->get('docman_items/' . $folder['id'] . '/docman_items')
        );
        $items_folder_2     = $response->json();

        $items = array_merge($items_folder_1, $items_folder_2);

        $this->assertGetDocumentItems($items, $folder, $empty, $file, $link, $embedded, $wiki);

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

        $allowed_folder = $this->findItemByTitle($folder, 'Folder');
        $this->assertNotNull($allowed_folder);
        $denied_folder = $this->findItemByTitle($folder, 'Folder RO');
        $this->assertNull($denied_folder);
    }

    /**
     * @depends testGetRootId
     */
    public function testOPTIONSDocmanItemsId($root_id): void
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
    public function testOPTIONSId($root_id): void
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
    public function testAllOPTIONSDocmanItemsWithUserRESTReadOnlyAdmin($root_id): void
    {
        $response = $this->getResponse(
            $this->client->options('docman_items/' . $root_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());

        $response = $this->getResponse(
            $this->client->options('docman_items/' . $root_id . '/docman_items'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST'], $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @depends testGetRootId
     */
    public function testGetId($root_id): void
    {
        $response = $this->getResponse(
            $this->client->get('docman_items/' . $root_id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $item     = $response->json();

        $this->assertEquals('Project Documentation', $item['title']);
        $this->assertEquals($root_id, $item['id']);
        $this->assertEquals('folder', $item['type']);
        $this->assertNull($item['permissions_for_groups']);
    }

    /**
     * @depends testGetRootId
     */
    public function testGetIdWithUserRESTReadOnlyAdmin($root_id): void
    {
        $response = $this->getResponse(
            $this->client->get('docman_items/' . $root_id),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $item     = $response->json();

        $this->assertEquals('Project Documentation', $item['title']);
        $this->assertEquals($root_id, $item['id']);
        $this->assertEquals('folder', $item['type']);
        $this->assertIsArray($item['permissions_for_groups']);
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testGetAllItemParents(array $items): void
    {
        $embedded_2 = $this->findItemByTitle($items, 'GET EM');

        $project_response = $this->getResponse($this->client->get('docman_items/' . $embedded_2['id'] . '/parents'));
        $json_parents     = $project_response->json();
        $this->assertEquals(count($json_parents), 3);
        $this->assertEquals($json_parents[0]['title'], 'Project Documentation');
        $this->assertEquals($json_parents[1]['title'], 'Folder');
        $this->assertEquals($json_parents[2]['title'], 'GET FO');
    }

    /**
     * @depends testGetDocumentItemsForAdminUser
     */
    public function testGetAllItemParentsWithUserRESTReadOnlyAdmin(array $items): void
    {
        $embedded_2 = $this->findItemByTitle($items, 'GET EM');

        $project_response = $this->getResponse(
            $this->client->get('docman_items/' . $embedded_2['id'] . '/parents'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $json_parents = $project_response->json();
        $this->assertEquals(count($json_parents), 3);
        $this->assertEquals($json_parents[0]['title'], 'Project Documentation');
        $this->assertEquals($json_parents[1]['title'], 'Folder');
        $this->assertEquals($json_parents[2]['title'], 'GET FO');
    }

    /**
     * @param array $items
     * @param array|null $folder
     * @param array|null $empty
     * @param array|null $file
     * @param array|null $link
     * @param array|null $embedded
     * @param array|null $wiki
     */
    private function assertGetDocumentItems(
        array $items,
        ?array $folder,
        ?array $empty,
        ?array $file,
        ?array $link,
        ?array $embedded,
        ?array $wiki
    ): void {
        $this->assertGreaterThan(0, count($items));

        $this->assertEquals(' (docman_regular_user)', $items[0]['owner']['display_name']);

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
            '/plugins/docman/download/' . urlencode((string) $file['id']) . '/1'
        );
        $this->assertEquals($file['file_properties']['file_size'], 3);
        $this->assertEquals($link['file_properties'], null);
        $this->assertEquals($embedded['file_properties'], null);
        $this->assertEquals($wiki['file_properties'], null);

        $this->assertEquals($folder['embedded_file_properties'], null);
        $this->assertEquals($empty['embedded_file_properties'], null);
        $this->assertEquals($file['embedded_file_properties'], null);
        $this->assertEquals($link['embedded_file_properties'], null);
        $this->assertEquals($embedded['embedded_file_properties']['file_type'], 'text/html');
        $this->assertArrayNotHasKey('content', $embedded['embedded_file_properties']);
        $this->assertEquals($wiki['embedded_file_properties'], null);

        $this->assertEquals($folder['link_properties'], null);
        $this->assertEquals($empty['link_properties'], null);
        $this->assertEquals($file['link_properties'], null);
        $this->assertEquals($link['link_properties'], null);
        $this->assertEquals($embedded['link_properties'], null);
        $this->assertEquals($wiki['link_properties'], null);

        $this->assertEquals($folder['wiki_properties'], null);
        $this->assertEquals($empty['wiki_properties'], null);
        $this->assertEquals($file['wiki_properties'], null);
        $this->assertEquals($link['wiki_properties'], null);
        $this->assertEquals($embedded['wiki_properties'], null);
        $this->assertEquals($wiki['wiki_properties']['page_name'], 'MyWikiPage');

        $this->assertNotNull($folder['permissions_for_groups']);
        $this->assertNotNull($empty['permissions_for_groups']);
        $this->assertNotNull($file['permissions_for_groups']);
        $this->assertNotNull($link['permissions_for_groups']);
        $this->assertNotNull($embedded['permissions_for_groups']);
        $this->assertNotNull($wiki['permissions_for_groups']);
    }
}
