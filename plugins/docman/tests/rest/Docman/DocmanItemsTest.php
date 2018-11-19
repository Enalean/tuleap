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
    public function testGetDocumentItemsForRegularUser()
    {
        $project_response = $this->getResponse($this->client->get('projects/' . $this->project_id));
        $json_projects    = $project_response->json();

        $root_id = $json_projects['additional_informations']['docman']['root_item']['item_id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );
        $folder   = $response->json();

        $this->assertEquals(count($folder), 1);
        $folder_id = $folder[0]['item_id'];

        $response = $this->getResponseByName(
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
            $this->client->get('docman_items/' . $folder_id . '/docman_items')
        );
        $items    = $response->json();

        $this->assertEquals(count($items), 3);
        $this->assertEquals($items[0]['name'], 'folder');
        $this->assertEquals($items[1]['name'], 'item A');
        $this->assertEquals($items[2]['name'], 'item C');
    }

    public function testOPTIONSId()
    {
        $response = $this->getResponse(
            $this->client->options('docman_items/1'),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }
}
