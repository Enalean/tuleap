<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest\Docman;

require_once __DIR__ . '/../../../vendor/autoload.php';

use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;

final class SearchTest extends DocmanTestExecutionHelper
{
    /**
     * @depends testGetRootId
     */
    public function testSearchInFolder(int $root_id): void
    {
        $root_folder   = $this->loadRootFolderContent($root_id, REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $folder_search = $this->findItemByTitle($root_folder, 'Search');

        assert($folder_search !== null);
        $folder_search_id = $folder_search['id'];

        $query = [
            'limit'  => 50,
            'offset' => 0,
            'global_search' => '*.txt',
        ];

        $search_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_search/' . $folder_search_id)
                ->withBody($this->stream_factory->createStream(json_encode($query)))
        );

        $this->assertSame(200, $search_response->getStatusCode());
        $found_items = json_decode($search_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $item_titles = [];
        foreach ($found_items as $item) {
            $item_titles[] = $item['title'];
        }

        $this->assertEqualsCanonicalizing(["foo.txt", "bar.txt"], $item_titles);

        $this->assertCount(2, $found_items);

        $foo_query = [
            'limit'  => 50,
            'offset' => 0,
            'global_search' => 'foo.*',
        ];

        $foo_search_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_search/' . $folder_search_id)
                ->withBody($this->stream_factory->createStream(json_encode($foo_query)))
        );


        $this->assertSame(200, $foo_search_response->getStatusCode());
        $found_items = json_decode($foo_search_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $found_items);

        $parents = ["Project Documentation", "Search"];

        $parent_titles = [];
        foreach ($found_items[0]['parents'] as $parent) {
            $parent_titles[] = $parent['title'];
        }
        $this->assertEqualsCanonicalizing($parents, $parent_titles);
    }

    /**
     * @depends testGetRootId
     */
    public function testItSearchInFolderUsingProperties(int $root_id): void
    {
        $root_folder   = $this->loadRootFolderContent($root_id, REST_TestDataBuilder::TEST_BOT_USER_NAME);
        $folder_search = $this->findItemByTitle($root_folder, 'Search');

        assert($folder_search !== null);
        $folder_search_id = $folder_search['id'];

        $query = [
            'limit' => 50,
            'offset' => 0,
            'global_search' => '',
            'properties' => [['name' => 'title', 'value' => '*.txt']],
            'sort' => [['name' => 'title', 'order' => 'asc']],
        ];

        $search_response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'docman_search/' . $folder_search_id)
                ->withBody($this->stream_factory->createStream(json_encode($query)))
        );

        $this->assertSame(200, $search_response->getStatusCode());
        $found_items = json_decode($search_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $item_titles = [];
        foreach ($found_items as $item) {
            $item_titles[] = $item['title'];
        }

        $this->assertEqualsCanonicalizing(["bar.txt", "foo.txt"], $item_titles);

        $this->assertCount(2, $found_items);
    }

    /**
     * @depends testGetRootId
     */
    public function testOptionsSearchId($id): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'docman_search/' . $id),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );

        $this->assertEqualsCanonicalizing(['OPTIONS', 'POST'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }
}
