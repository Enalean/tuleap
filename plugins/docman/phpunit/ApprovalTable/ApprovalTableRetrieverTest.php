<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Docman\ApprovalTable;

use Docman_ApprovalTableFactoriesFactory;
use Docman_ApprovalTableItemFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ApprovalTableRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var ApprovalTableRetriever
     */
    private $approval_table_retriever;

    /**
     * @var Docman_ApprovalTableFactoriesFactory|Mockery\MockInterface
     */
    private $approval_table_factory;

    protected function setUp() : void
    {
        parent::setUp();

        $this->approval_table_factory   = Mockery::mock(Docman_ApprovalTableFactoriesFactory::class);
        $this->approval_table_retriever = new ApprovalTableRetriever($this->approval_table_factory);
    }

    public function testItReturnsNullWhenFactoryDoesNotExistForItem()
    {
        $item = Mockery::mock(\Docman_Item::class);

        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
                                     ->with($item)
                                     ->andReturn(null);

        $this->assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsNullWhenItemDoesNotHaveAnApprovalTable()
    {
        $item    = Mockery::mock(\Docman_Item::class);
        $factory = Mockery::mock(Docman_ApprovalTableItemFactory::class);
        $factory->shouldReceive('getTable')->andReturn(null);
        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
                                     ->with($item)
                                     ->andReturn($factory);

        $this->assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsNullWhenItemApprovalTableIsDisabled()
    {
        $item    = Mockery::mock(\Docman_Item::class);
        $factory = Mockery::mock(Docman_ApprovalTableItemFactory::class);
        $table   = Mockery::mock(\Docman_ApprovalTable::class);
        $factory->shouldReceive('getTable')->andReturn($table);
        $table->shouldReceive('isEnabled')->andReturn(false);
        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
                                     ->with($item)
                                     ->andReturn($factory);

        $this->assertEquals(null, $this->approval_table_retriever->retrieveByItem($item));
    }

    public function testItReturnsApprovalTable()
    {
        $item    = Mockery::mock(\Docman_Item::class);
        $factory = Mockery::mock(Docman_ApprovalTableItemFactory::class);
        $table   = Mockery::mock(\Docman_ApprovalTable::class);
        $factory->shouldReceive('getTable')->andReturn($table);
        $table->shouldReceive('isEnabled')->andReturn(true);
        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')
                                     ->with($item)
                                     ->andReturn($factory);

        $this->assertEquals($table, $this->approval_table_retriever->retrieveByItem($item));
    }
}
