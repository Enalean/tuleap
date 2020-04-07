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

use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanBase;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;

class DocmanTestExecutionHelper extends DocmanBase
{
    /**
     * @var ?int
     */
    protected $docman_regular_user;
    protected function getDocmanRegularUser(): int
    {
        $search = urlencode(
            json_encode(
                array(
                    'username' => DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
                )
            )
        );
        $response = $this->getResponseByName(REST_TestDataBuilder::TEST_USER_1_NAME, $this->client->get("users?query=$search&limit=10"));
        $this->assertEquals($response->getStatusCode(), 200);

        $json = $response->json();
        $this->assertCount(1, $json);

        return $json[0]['id'];
    }

    public function testGetRootId(): int
    {
        $project_response = $this->getResponse(
            $this->client->get('projects/' . urlencode((string) $this->project_id) . '/docman_service')
        );

        $this->assertSame(200, $project_response->getStatusCode());

        $json_docman_service = $project_response->json();
        return $json_docman_service['root_item']['id'];
    }

    public function testGetRootIdWithUserRESTReadOnlyAdmin(): int
    {
        $project_response = $this->getResponse(
            $this->client->get('projects/' . urlencode((string) $this->project_id) . '/docman_service'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertSame(200, $project_response->getStatusCode());

        $json_docman_service = $project_response->json();
        return $json_docman_service['root_item']['id'];
    }

    public function loadRootFolderContent(
        int $root_id,
        string $user_name = REST_TestDataBuilder::ADMIN_USER_NAME
    ): array {
        $response = $this->getResponseByName(
            $user_name,
            $this->client->get('docman_items/' . $root_id . '/docman_items')
        );

        $folder = $response->json();

        $this->assertEquals(200, $response->getStatusCode());

        return $folder;
    }

    public function loadFolderContent(
        int $folder_id,
        string $folder_name,
        string $user_name = REST_TestDataBuilder::ADMIN_USER_NAME
    ): array {
        $response = $this->getResponseByName(
            $user_name,
            $this->client->get('docman_items/' . $folder_id . '/docman_items')
        );
        $folder   = $response->json();

        $folder_content = $this->findItemByTitle($folder, $folder_name);
        $new_folder_id  = $folder_content['id'];
        $response       = $this->getResponseByName(
            $user_name,
            $this->client->get('docman_items/' . $new_folder_id . '/docman_items')
        );
        $item_folder    = $response->json();
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
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_delete_id)
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function checkItemHasBeenDeleted(int $file_to_delete_id): void
    {
        $response = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file_to_delete_id)
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
            DocmanDataBuilder::ADMIN_USER_NAME,
            $this->client->get('docman_items/' . $file['id'])
        );

        $this->assertEquals(200, $response->getStatusCode());

        return $response->json();
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
