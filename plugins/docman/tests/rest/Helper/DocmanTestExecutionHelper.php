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

use Tuleap\Docman\Test\rest\DocmanBase;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\REST\BaseTestDataBuilder;
use Tuleap\REST\RESTTestDataBuilder;

class DocmanTestExecutionHelper extends DocmanBase
{
    /**
     * @var ?int
     */
    protected $docman_regular_user;
    private DocmanAPIHelper $docman_api;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->docman_api = new DocmanAPIHelper($this->rest_request, $this->request_factory);
    }

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
        $this->expectNotToPerformAssertions();
        return $this->docman_api->getRootFolderID($this->project_id);
    }

    public function testGetRootIdWithUserRESTReadOnlyAdmin(): int
    {
        $this->expectNotToPerformAssertions();
        return $this->docman_api->getRootFolderID($this->project_id, RESTTestDataBuilder::TEST_BOT_USER_NAME);
    }

    public function loadRootFolderContent(
        int $root_id,
        string $user_name = BaseTestDataBuilder::ADMIN_USER_NAME,
    ): array {
        $response = $this->getResponseByName(
            $user_name,
            $this->request_factory->createRequest('GET', 'docman_items/' . $root_id . '/docman_items')
        );

        $folder = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

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

    public function checkItemHasNotBeenDeleted(int $file_to_delete_id): void
    {
        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function checkItemHasBeenDeleted(int $file_to_delete_id): void
    {
        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file_to_delete_id)
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function checkItemHasAnApprovalTable(array $items, string $title, ?string $table_status): void
    {
        $item = $this->checkApprovalExistence($items, $title);
        $this->assertEquals($item['approval_table']['approval_state'], $table_status);
        $this->assertTrue($item['has_approval_table']);
        $this->assertTrue($item['is_approval_table_enabled']);
    }

    public function checkItemHasADisabledApprovalTable(array $items, string $title): void
    {
        $item = $this->checkApprovalExistence($items, $title);
        $this->assertNull($item['approval_table']);
        $this->assertFalse($item['is_approval_table_enabled']);
    }

    private function checkApprovalExistence(array $items, string $title): array
    {
        $file = $this->findItemByTitle($items, $title);

        $response = $this->getResponseByName(
            BaseTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'docman_items/' . $file['id'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
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
