<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Test\rest\Docman;

use Tuleap\Docman\Test\rest\DocmanForbidWritersDataBuilder;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ForbidWritersTest extends \RestBase
{
    private const MANAGERS_UGROUP_NAME = 'Managers';

    private int $project_id;
    private int $writer_user_id;
    private string $item_title;

    public function setUp(): void
    {
        parent::setUp();
        $this->item_title = 'Lorem ipsum ' . (new \DateTimeImmutable())->getTimestamp();
        $this->project_id = $this->getProjectId(DocmanForbidWritersDataBuilder::PROJECT_NAME);

        $this->initUserId(DocmanForbidWritersDataBuilder::WRITER_USERNAME);
        $this->writer_user_id = $this->user_ids[DocmanForbidWritersDataBuilder::WRITER_USERNAME];
    }

    public function testGetPermissions(): array
    {
        $project_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . urlencode((string) $this->project_id) . '/user_groups'),
            DocmanForbidWritersDataBuilder::WRITER_USERNAME,
        );

        self::assertSame(200, $project_response->getStatusCode());
        $ugroups_for_project = json_decode($project_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $members_id  = null;
        $managers_id = null;
        foreach ($ugroups_for_project as $ugroup) {
            if ($ugroup['short_name'] === 'project_members') {
                $members_id = $ugroup['id'];
                continue;
            }

            if ($ugroup['short_name'] === self::MANAGERS_UGROUP_NAME) {
                $managers_id = $ugroup['id'];
                continue;
            }
        }

        self::assertNotNull($members_id);
        self::assertNotNull($managers_id);

        return [
            'can_manage' => [[ 'id' => $managers_id ]],
            'can_read'   => [[ 'id' => $members_id ]],
            'can_write'  => [[ 'id' => $members_id ]],
        ];
    }

    public function testGetRootId(): int
    {
        $project_response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'projects/' . urlencode((string) $this->project_id) . '/docman_service'),
            DocmanForbidWritersDataBuilder::WRITER_USERNAME,
        );

        self::assertSame(200, $project_response->getStatusCode());

        $json_docman_service = json_decode($project_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        return $json_docman_service['root_item']['id'];
    }

    /**
     * @depends testGetRootId
     * @depends testGetPermissions
     */
    public function testCreateEmptyItem(int $folder_id, array $permissions): int
    {
        $item_response = $this->getResponse(
            $this->request_factory
                ->createRequest('POST', 'docman_folders/' . urlencode((string) $folder_id) . '/empties')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode([
                            'title'                  => $this->item_title,
                            'permissions_for_groups' => $permissions,
                        ])
                    )
                ),
            DocmanForbidWritersDataBuilder::WRITER_USERNAME,
        );

        self::assertSame(201, $item_response->getStatusCode());
        $item_id = json_decode($item_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        self::assertIsInt($item_id);

        return $item_id;
    }

    /**
     * @depends testCreateEmptyItem
     */
    public function testManagerIsAllowedToUpdate(int $item_id): int
    {
        $update_response = $this->getResponse(
            $this->request_factory
                ->createRequest('PUT', 'docman_empty_documents/' . urlencode((string) $item_id) . '/metadata')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode([
                            'title'    => $this->item_title . ' updated by manager',
                            'owner_id' => $this->writer_user_id,
                        ])
                    )
                ),
            DocmanForbidWritersDataBuilder::MANAGER_USERNAME,
        );

        self::assertSame(200, $update_response->getStatusCode());

        return $item_id;
    }

    /**
     * @depends testManagerIsAllowedToUpdate
     */
    public function testWriterIsNotAllowedToUpdate(int $item_id): int
    {
        $update_response = $this->getResponse(
            $this->request_factory
                ->createRequest('PUT', 'docman_empty_documents/' . urlencode((string) $item_id) . '/metadata')
                ->withBody(
                    $this->stream_factory->createStream(
                        json_encode([
                            'title' => $this->item_title . ' updated by writer',
                            'owner_id' => $this->writer_user_id,
                        ])
                    )
                ),
            DocmanForbidWritersDataBuilder::WRITER_USERNAME,
        );

        self::assertSame(403, $update_response->getStatusCode());

        return $item_id;
    }

    /**
     * @depends testWriterIsNotAllowedToUpdate
     */
    public function testWriterIsNotAllowedToDelete(int $item_id): int
    {
        $update_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . urlencode((string) $item_id)),
            DocmanForbidWritersDataBuilder::WRITER_USERNAME,
        );

        self::assertSame(403, $update_response->getStatusCode());

        return $item_id;
    }

    /**
     * @depends testWriterIsNotAllowedToDelete
     */
    public function testManagerIsAllowedToDelete(int $item_id): void
    {
        $update_response = $this->getResponse(
            $this->request_factory->createRequest('DELETE', 'docman_empty_documents/' . urlencode((string) $item_id)),
            DocmanForbidWritersDataBuilder::MANAGER_USERNAME,
        );

        self::assertSame(200, $update_response->getStatusCode());
    }
}
