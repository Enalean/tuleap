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

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest\Helper;

use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\DocmanWithMetadataActivatedBase;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RESTTestDataBuilder;

class DocmanHardcodedMetadataExecutionHelper extends DocmanWithMetadataActivatedBase
{
    protected function getDocmanRegularUser(): int
    {
        $search   = urlencode(
            json_encode(
                [
                    'username' => DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME,
                ]
            )
        );
        $response = $this->getResponseByName(RESTTestDataBuilder::TEST_USER_1_NAME, $this->request_factory->createRequest('GET', "users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $json);

        return $json[0]['id'];
    }

    public function testGetRootId(): int
    {
        $project_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . urlencode((string) $this->project_id) . '/docman_service')
        );

        self::assertSame(200, $project_response->getStatusCode());

        $json_docman_service = json_decode($project_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        return $json_docman_service['root_item']['id'];
    }

    public function testGetRootIdWithUserRESTReadOnlyAdmin(): int
    {
        $project_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . urlencode((string) $this->project_id) . '/docman_service'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(200, $project_response->getStatusCode());

        $json_docman_service = json_decode($project_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        return $json_docman_service['root_item']['id'];
    }

    public function loadRootFolderContent(int $root_id, string $user_name = BaseTestDataBuilder::ADMIN_USER_NAME): array
    {
        $response = $this->getResponseByName(
            $user_name,
            $this->request_factory->createRequest('GET', 'docman_items/' . $root_id . '/docman_items')
        );
        $folder   = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(200, $response->getStatusCode());

        return $folder;
    }

    public function loadFolderContent(
        int $folder_id,
        string $folder_name,
        string $user_name = BaseTestDataBuilder::ADMIN_USER_NAME,
    ): array {
        $response = $this->getResponseByName(
            $user_name,
            $this->request_factory->createRequest('GET', 'docman_items/' . $folder_id . '/docman_items')
        );
        $folder   = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $folder_content = $this->findItemByTitle($folder, $folder_name);
        $new_folder_id  = $folder_content['id'];
        $response       = $this->getResponseByName(
            $user_name,
            $this->request_factory->createRequest('GET', 'docman_items/' . $new_folder_id . '/docman_items')
        );
        $item_folder    = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(200, $response->getStatusCode());

        return $item_folder;
    }

    /**
     * Find first item in given array of items which has given title.
     * @return array | null Found item. null otherwise.
     */
    public function findItemByTitle(array $items, string $title): ?array
    {
        $index = array_search($title, array_column($items, 'title'));
        if ($index === false) {
            return null;
        }
        return $items[$index];
    }

    /**
     * @return array | null Found item. null otherwise.
     */
    public function findMetadataByName(array $metadata, string $name): ?array
    {
        $index = array_search($name, array_column($metadata, 'name'));
        if ($index === false) {
            return null;
        }
        return $metadata[$index];
    }
}
