<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\ApprovalTable;

use Docman_ApprovalTableFactoriesFactory;
use Docman_ApprovalTableFileFactory;
use Docman_ApprovalTableItem;
use Docman_ApprovalTableItemFactory;
use Docman_Item;
use Docman_Version;
use Docman_VersionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ApprovalTableRetrieverTest extends TestCase
{
    private Docman_VersionFactory&MockObject $version_factory;
    private ApprovalTableRetriever $approval_table_retriever;
    private Docman_ApprovalTableFactoriesFactory&MockObject $approval_table_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->approval_table_factory   = $this->createMock(Docman_ApprovalTableFactoriesFactory::class);
        $this->version_factory          = $this->createMock(Docman_VersionFactory::class);
        $this->approval_table_retriever = new ApprovalTableRetriever($this->approval_table_factory, $this->version_factory);
    }

    public function testItReturnsNullWhenTableIsVersionedAndWhenNoVersionOfTableExists()
    {
        $version_id = 1;
        $version    = new Docman_Version(['number' => $version_id]);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($version);

        $item          = new Docman_Item();
        $table_factory = $this->createMock(Docman_ApprovalTableFileFactory::class);
        $this->approval_table_factory->method('getFromItem')->with($item, $version_id)->willReturn($table_factory);

        $table_factory->method('getLastTableForItem')->willReturn(null);

        self::assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsTheLastTableWhenTableIsVersioned()
    {
        $version_id = 1;
        $version    = new Docman_Version(['number' => $version_id]);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($version);

        $item          = new Docman_Item();
        $table_factory = $this->createMock(Docman_ApprovalTableFileFactory::class);
        $this->approval_table_factory->method('getFromItem')->with($item, $version_id)->willReturn($table_factory);

        $table = new Docman_ApprovalTableItem();
        $table->setStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $table_factory->method('getLastTableForItem')->willReturn($table);

        self::assertEquals($table, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsNullWhenFactoryDoesNotExistForItem()
    {
        $version_id = 1;
        $version    = new Docman_Version(['number' => $version_id]);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($version);

        $item          = new Docman_Item();
        $table_factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->method('getFromItem')->with($item, $version_id)->willReturn($table_factory);
        $this->approval_table_factory->method('getSpecificFactoryFromItem')->with($item)->willReturn(null);

        self::assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsNullWhenItemDoesNotHaveAnApprovalTable()
    {
        $version_id = 1;
        $version    = new Docman_Version(['number' => $version_id]);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($version);

        $item          = new Docman_Item();
        $table_factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->method('getFromItem')->with($item, $version_id)->willReturn($table_factory);

        $factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $factory->method('getTable')->willReturn(null);
        $this->approval_table_factory->method('getSpecificFactoryFromItem')->with($item)->willReturn($factory);

        self::assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsNullWhenItemApprovalTableIsDisabled()
    {
        $version_id = 1;
        $version    = new Docman_Version(['number' => $version_id]);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($version);

        $item          = new Docman_Item();
        $table_factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->method('getFromItem')->with($item, $version_id)->willReturn($table_factory);

        $factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $table   = new Docman_ApprovalTableItem();
        $table->setStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED);
        $factory->method('getTable')->willReturn($table);
        $this->approval_table_factory->method('getSpecificFactoryFromItem')->with($item)->willReturn($factory);

        self::assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsApprovalTable()
    {
        $version_id = 1;
        $version    = new Docman_Version(['number' => $version_id]);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($version);

        $item          = new Docman_Item();
        $table_factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->method('getFromItem')->with($item, $version_id)->willReturn($table_factory);

        $factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $table   = new Docman_ApprovalTableItem();
        $table->setStatus(PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED);
        $factory->method('getTable')->willReturn($table);
        $this->approval_table_factory->method('getSpecificFactoryFromItem')->with($item)->willReturn($factory);

        self::assertEquals($table, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsTrueWhenTheItemHasAnApprovalTableRegardlessOfItsActivation(): void
    {
        $version_id = 1;
        $version    = new Docman_Version(['number' => $version_id]);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($version);

        $item          = new Docman_Item();
        $table_factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->method('getFromItem')->with($item, $version_id)->willReturn($table_factory);

        $factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $table   = new Docman_ApprovalTableItem();
        $this->approval_table_factory->method('getSpecificFactoryFromItem')->with($item)->willReturn($factory);
        $factory->method('getTable')->willReturn($table);

        self::assertTrue($this->approval_table_retriever->hasApprovalTable($item));
    }

    public function testItReturnsFalseWhenTheItemHasNoApprovalTable(): void
    {
        $version_id = 1;
        $version    = new Docman_Version(['number' => $version_id]);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($version);

        $item          = new Docman_Item();
        $table_factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->method('getFromItem')->with($item, $version_id)->willReturn($table_factory);

        $factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->method('getSpecificFactoryFromItem')->with($item)->willReturn($factory);
        $factory->method('getTable')->willReturn(null);

        self::assertFalse($this->approval_table_retriever->hasApprovalTable($item));
    }

    public function testReturnsFalseWhenTheTableFactoryFailed(): void
    {
        $version_id = 1;
        $version    = new Docman_Version(['number' => $version_id]);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($version);

        $item          = new Docman_Item();
        $table_factory = $this->createMock(Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->method('getFromItem')->with($item, $version_id)->willReturn($table_factory);
        $this->approval_table_factory->method('getSpecificFactoryFromItem')->with($item)->willReturn(null);

        self::assertFalse($this->approval_table_retriever->hasApprovalTable($item));
    }
}
