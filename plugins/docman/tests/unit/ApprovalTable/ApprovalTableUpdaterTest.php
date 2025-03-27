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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\ApprovalTable;

use Docman_ApprovalTableFactoriesFactory;
use Docman_ApprovalTableFileFactory;
use Docman_File;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ApprovalTableUpdaterTest extends TestCase
{
    private Docman_ApprovalTableFactoriesFactory&MockObject $approval_table_factory;
    private ApprovalTableRetriever&MockObject $approval_table_retriever;

    protected function setUp(): void
    {
        $this->approval_table_factory   = $this->createMock(Docman_ApprovalTableFactoriesFactory::class);
        $this->approval_table_retriever = $this->createMock(ApprovalTableRetriever::class);
    }

    public function testItUpdateItemAndCreateItsApprovalTable(): void
    {
        $approval_table_updater = new ApprovalTableUpdater(
            $this->approval_table_retriever,
            $this->approval_table_factory
        );

        $item = new Docman_File();
        $user = UserTestBuilder::buildWithId(18);

        $approval_file = $this->createMock(Docman_ApprovalTableFileFactory::class);
        $this->approval_table_retriever->method('hasApprovalTable')->with($item)->willReturn(true);
        $this->approval_table_factory->expects($this->once())->method('getSpecificFactoryFromItem')->with($item)->willReturn($approval_file);

        $approval_action = 'copy';

        $approval_file->expects($this->once())->method('createTable')->with(18, $approval_action);

        $approval_table_updater->updateApprovalTable($item, $user, $approval_action);
    }

    public function testItDoesNotCreateAnApprovalTableWhenItemDoesNotHaveAnExistingOne(): void
    {
        $approval_table_updater = new ApprovalTableUpdater(
            $this->approval_table_retriever,
            $this->approval_table_factory
        );

        $item = new Docman_File();
        $user = UserTestBuilder::buildWithId(18);

        $approval_file = $this->createMock(Docman_ApprovalTableFileFactory::class);
        $this->approval_table_retriever->method('hasApprovalTable')->with($item)->willReturn(false);
        $this->approval_table_factory->expects(self::never())->method('getSpecificFactoryFromItem');

        $approval_action = 'copy';

        $approval_file->expects(self::never())->method('createTable');

        $approval_table_updater->updateApprovalTable($item, $user, $approval_action);
    }
}
