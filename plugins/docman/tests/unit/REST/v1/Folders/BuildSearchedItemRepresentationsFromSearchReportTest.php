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

namespace Tuleap\Docman\REST\v1\Folders;

use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class BuildSearchedItemRepresentationsFromSearchReportTest extends TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \Docman_ItemDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $item_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\UserManager
     */
    private $user_manager;
    /**
     * @var \Docman_PermissionsManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $permissions_manager;
    private BuildSearchedItemRepresentationsFromSearchReport $representation_builder;
    private ItemStatusMapper $status_mapper;

    protected function setUp(): void
    {
        $docman_settings = $this->createMock(\Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->with('status')->willReturn("1");
        $this->item_dao            = $this->createMock(\Docman_ItemDao::class);
        $this->status_mapper       = new ItemStatusMapper($docman_settings);
        $this->user_manager        = $this->createMock(\UserManager::class);
        $this->permissions_manager = $this->createMock(\Docman_PermissionsManager::class);

        $this->representation_builder = new BuildSearchedItemRepresentationsFromSearchReport(
            $this->item_dao,
            $this->status_mapper,
            $this->user_manager,
            $this->permissions_manager
        );
    }

    public function testItBuildsItemRepresentations(): void
    {
        $report = new \Docman_Report();
        $folder = new \Docman_Folder(["group_id" => 101]);

        $item_one     = [
            "item_id"     => 1,
            "title"       => "folder",
            "description" => "",
            "update_date" => "123456789",
            "status"      => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            "user_id"     => 101,
        ];
        $item_two     = [
            "item_id"     => 2,
            "title"       => "file",
            "description" => "",
            "update_date" => "987654321",
            "status"      => PLUGIN_DOCMAN_ITEM_STATUS_REJECTED,
            "user_id"     => 101,
        ];
        $private_item = [
            "item_id"     => 3,
            "title"       => "private",
            "description" => "",
            "update_date" => "987654321",
            "status"      => PLUGIN_DOCMAN_ITEM_STATUS_REJECTED,
            "user_id"     => 101,
        ];
        $this->item_dao->method('searchByGroupId')->willReturn([$item_one, $item_two, $private_item]);
        $this->user_manager->method('getUserById')->willReturn(UserTestBuilder::buildWithId(101));
        $this->permissions_manager->method('userCanRead')->willReturnOnConsecutiveCalls(true, true, false);

        $representations = $this->representation_builder->build($report, $folder, UserTestBuilder::aUser()->build());
        self::assertCount(2, $representations);
        $this->assertItemEqualsRepresentation($item_one, $representations[0]);
        $this->assertItemEqualsRepresentation($item_two, $representations[1]);
    }

    protected function assertItemEqualsRepresentation(array $item, SearchRepresentation $representation): void
    {
        self::assertSame($item['item_id'], $representation->id);
        self::assertSame($item['title'], $representation->title);
        self::assertSame($item['description'], $representation->description);
        self::assertSame($this->status_mapper->getItemStatusFromItemStatusNumber($item['status']), $representation->status);
        self::assertSame($item['user_id'], $representation->owner->id);
    }
}
