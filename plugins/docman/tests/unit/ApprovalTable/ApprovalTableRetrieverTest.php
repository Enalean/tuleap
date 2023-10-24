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
use Docman_ApprovalTableItemFactory;
use Docman_VersionFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class ApprovalTableRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Docman_VersionFactory|Mockery\MockInterface
     */
    private $version_factory;
    /**
     * @var ApprovalTableRetriever
     */
    private $approval_table_retriever;

    /**
     * @var Docman_ApprovalTableFactoriesFactory|Mockery\MockInterface
     */
    private $approval_table_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approval_table_factory   = Mockery::mock(Docman_ApprovalTableFactoriesFactory::class);
        $this->version_factory          = Mockery::mock(Docman_VersionFactory::class);
        $this->approval_table_retriever = new ApprovalTableRetriever($this->approval_table_factory, $this->version_factory);
    }

    public function testItReturnsNullWhenTableIsVersionedAndWhenNoVersionOfTableExists()
    {
        $version = Mockery::mock(\Docman_Version::class);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->andReturn($version);

        $version_id = 1;
        $version->shouldReceive('getNumber')->andReturn($version_id);

        $item          = Mockery::mock(\Docman_Item::class);
        $table_factory = Mockery::mock(\Docman_ApprovalTableFileFactory::class);
        $this->approval_table_factory->shouldReceive('getFromItem')->with($item, $version_id)->andReturn($table_factory);
        $this->approval_table_factory->shouldReceive('isAVersionedApprovalTableFactory')->andReturn(true);

        $table_factory->shouldReceive('getLastTableForItem')->andReturn(null);

        $this->assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsTheLastTableWhenTableIsVersioned()
    {
        $version = Mockery::mock(\Docman_Version::class);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->andReturn($version);

        $version_id = 1;
        $version->shouldReceive('getNumber')->andReturn($version_id);

        $item          = Mockery::mock(\Docman_Item::class);
        $table_factory = Mockery::mock(\Docman_ApprovalTableFileFactory::class);
        $this->approval_table_factory->shouldReceive('getFromItem')->with($item, $version_id)->andReturn($table_factory);

        $table = Mockery::mock(\Docman_ApprovalTable::class);
        $table_factory->shouldReceive('getLastTableForItem')->andReturn($table);
        $table->shouldReceive('isDisabled')->andReturn(false);

        $this->assertEquals($table, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsNullWhenFactoryDoesNotExistForItem()
    {
        $version = Mockery::mock(\Docman_Version::class);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->andReturn($version);

        $version_id = 1;
        $version->shouldReceive('getNumber')->andReturn($version_id);

        $item          = Mockery::mock(\Docman_Item::class);
        $table_factory = Mockery::mock(\Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->shouldReceive('getFromItem')->with($item, $version_id)->andReturn($table_factory);

        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
            ->with($item)
            ->andReturn(null);

        $this->assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsNullWhenItemDoesNotHaveAnApprovalTable()
    {
        $version = Mockery::mock(\Docman_Version::class);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->andReturn($version);

        $version_id = 1;
        $version->shouldReceive('getNumber')->andReturn($version_id);

        $item          = Mockery::mock(\Docman_Item::class);
        $table_factory = Mockery::mock(\Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->shouldReceive('getFromItem')->with($item, $version_id)->andReturn($table_factory);

        $factory = Mockery::mock(Docman_ApprovalTableItemFactory::class);
        $factory->shouldReceive('getTable')->andReturn(null);
        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
            ->with($item)
            ->andReturn($factory);

        $this->assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsNullWhenItemApprovalTableIsDisabled()
    {
        $version = Mockery::mock(\Docman_Version::class);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->andReturn($version);

        $version_id = 1;
        $version->shouldReceive('getNumber')->andReturn($version_id);

        $item          = Mockery::mock(\Docman_Item::class);
        $table_factory = Mockery::mock(\Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->shouldReceive('getFromItem')->with($item, $version_id)->andReturn($table_factory);

        $factory = Mockery::mock(Docman_ApprovalTableItemFactory::class);
        $table   = Mockery::mock(\Docman_ApprovalTable::class);
        $factory->shouldReceive('getTable')->andReturn($table);
        $table->shouldReceive('isDisabled')->andReturn(true);
        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
            ->with($item)
            ->andReturn($factory);

        $this->assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsApprovalTable()
    {
        $version = Mockery::mock(\Docman_Version::class);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->andReturn($version);

        $version_id = 1;
        $version->shouldReceive('getNumber')->andReturn($version_id);

        $item          = Mockery::mock(\Docman_Item::class);
        $table_factory = Mockery::mock(\Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->shouldReceive('getFromItem')->with($item, $version_id)->andReturn($table_factory);

        $factory = Mockery::mock(Docman_ApprovalTableItemFactory::class);
        $table   = Mockery::mock(\Docman_ApprovalTable::class);
        $factory->shouldReceive('getTable')->andReturn($table);
        $table->shouldReceive('isDisabled')->andReturn(false);
        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
            ->with($item)
            ->andReturn($factory);

        $this->assertEquals($table, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsTrueWhenTheItemHasAnApprovalTableRegardlessOfItsActivation(): void
    {
        $version = Mockery::mock(\Docman_Version::class);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->andReturn($version);

        $version_id = 1;
        $version->shouldReceive('getNumber')->andReturn($version_id);

        $item          = Mockery::mock(\Docman_Item::class);
        $table_factory = Mockery::mock(\Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->shouldReceive('getFromItem')->with($item, $version_id)->andReturn($table_factory);

        $factory = Mockery::mock(Docman_ApprovalTableItemFactory::class);
        $table   = Mockery::mock(\Docman_ApprovalTable::class);

        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
            ->with($item)
            ->andReturn($factory);

        $factory->shouldReceive('getTable')->andReturn($table);

        $this->assertTrue($this->approval_table_retriever->hasApprovalTable($item));
    }

    public function testItReturnsFalseWhenTheItemHasNoApprovalTable(): void
    {
        $version = Mockery::mock(\Docman_Version::class);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->andReturn($version);

        $version_id = 1;
        $version->shouldReceive('getNumber')->andReturn($version_id);

        $item          = Mockery::mock(\Docman_Item::class);
        $table_factory = Mockery::mock(\Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->shouldReceive('getFromItem')->with($item, $version_id)->andReturn($table_factory);

        $factory = Mockery::mock(Docman_ApprovalTableItemFactory::class);

        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
            ->with($item)
            ->andReturn($factory);

        $factory->shouldReceive('getTable')->andReturn(null);

        $this->assertFalse($this->approval_table_retriever->hasApprovalTable($item));
    }

    public function testReturnsFalseWhenTheTableFactoryFailed(): void
    {
        $version = Mockery::mock(\Docman_Version::class);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->andReturn($version);

        $version_id = 1;
        $version->shouldReceive('getNumber')->andReturn($version_id);

        $item          = Mockery::mock(\Docman_Item::class);
        $table_factory = Mockery::mock(\Docman_ApprovalTableItemFactory::class);
        $this->approval_table_factory->shouldReceive('getFromItem')->with($item, $version_id)->andReturn($table_factory);

        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
            ->with($item)
            ->andReturn(null);

        $this->assertFalse($this->approval_table_retriever->hasApprovalTable($item));
    }
}
