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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\ApprovalTable;

use Docman_ApprovalTableFactoriesFactory;
use Docman_ApprovalTableFileFactory;
use Docman_File;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;

class ApprovalTableUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Docman_ApprovalTableFactoriesFactory
     */
    private $approval_table_factory;

    /**
     * @var ApprovalTableRetriever
     */
    private $approval_table_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approval_table_factory   = Mockery::mock(Docman_ApprovalTableFactoriesFactory::class);
        $this->approval_table_retriever = Mockery::mock(ApprovalTableRetriever::class);
    }

    public function testItUpdateItemAndCreateItsApprovalTable(): void
    {
        $approval_table_updater = new ApprovalTableUpdater(
            $this->approval_table_retriever,
            $this->approval_table_factory
        );

        $item = Mockery::mock(Docman_File::class);
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(18);

        $approval_file = Mockery::mock(Docman_ApprovalTableFileFactory::class);
        $this->approval_table_retriever->shouldReceive('hasApprovalTable')->with($item)->andReturn(true);
        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')->with($item)->andReturn(
            $approval_file
        )->once();

        $approval_action = 'copy';

        $approval_file->shouldReceive('createTable')->withArgs([18, $approval_action])->once();

        $approval_table_updater->updateApprovalTable($item, $user, $approval_action);
    }

    public function testItDoesNotCreateAnApprovalTableWhenItemDoesNotHaveAnExistingOne(): void
    {
        $approval_table_updater = new ApprovalTableUpdater(
            $this->approval_table_retriever,
            $this->approval_table_factory
        );

        $item = Mockery::mock(Docman_File::class);
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturn(18);

        $approval_file = Mockery::mock(Docman_ApprovalTableFileFactory::class);
        $this->approval_table_retriever->shouldReceive('hasApprovalTable')->with($item)->andReturn(false);
        $this->approval_table_factory->shouldReceive('getSpecificFactoryFromItem')->never();

        $approval_action = 'copy';

        $approval_file->shouldReceive('createTable')->never();

        $approval_table_updater->updateApprovalTable($item, $user, $approval_action);
    }
}
