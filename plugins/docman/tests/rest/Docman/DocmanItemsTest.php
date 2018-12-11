<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\Docman\rest\DocmanBase;
use Tuleap\Docman\rest\DocmanDataBuilder;

require_once __DIR__ . '/../bootstrap.php';

class DocmanItemsTest extends DocmanBase
{

    public function testGetRootId()
    {
        $project_response = $this->getResponse($this->client->get('projects/' . $this->project_id));
        $json_projects    = $project_response->json();

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
        $folder   = $response->json();

        $this->assertEquals(count($folder), 1);
        $folder_id = $folder[0]['id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $folder_id . '/docman_items')
        );
        $items    = $response->json();

        $this->assertEquals(count($items), 4);
        $this->assertEquals($items[0]['title'], 'folder 2');
        $this->assertEquals($items[1]['title'], 'item A');
        $this->assertEquals($items[2]['title'], 'item C');
        $this->assertEquals($items[3]['title'], 'item E');

        $this->assertEquals($items[0]['link_properties'], null);
        $this->assertEquals($items[0]['file_properties'], null);
        $this->assertEquals($items[1]['file_properties'], null);
        $this->assertEquals($items[1]['link_properties'], null);
        $this->assertEquals($items[2]['file_properties']['file_type'], 'application/pdf');
        $this->assertEquals($items[2]['link_properties'], null);
        $this->assertEquals($items[3]['file_properties'], null);
        $this->assertEquals($items[3]['link_properties']['link_url'], 'https://my.example.test');

        return $items[0]['id'];
    }

    /**
     * @depends testGetRootId
     */
    public function testOPTIONSDocmanItemsId($root_id)
    {
        $response = $this->getResponse(
            $this->client->options('docman_items/'. $root_id .'/docman_items'),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    /**
     * @depends testGetRootId
     */
    public function testOPTIONSId($root_id)
    {
        $response = $this->getResponse(
            $this->client->options('docman_items/'. $root_id),
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
            $this->client->get('docman_items/'. $root_id),
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
    public function testGetAllItemParents($folder_2_id)
    {
        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $folder_2_id . '/docman_items')
        );
        $item     = $response->json();

        $project_response = $this->getResponse($this->client->get('docman_items/' . $item[0]['id'] . '/parents'));
        $json_parents     = $project_response->json();
        $this->assertEquals(count($json_parents), 3);
        $this->assertEquals($json_parents[0]['title'], 'Project Documentation');
        $this->assertEquals($json_parents[1]['title'], 'folder 1');
        $this->assertEquals($json_parents[2]['title'], 'folder 2');
    }
}
