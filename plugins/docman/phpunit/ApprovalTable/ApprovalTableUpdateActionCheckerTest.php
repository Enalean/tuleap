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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\ApprovalTable;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\Files\DocmanFilesPATCHRepresentation;

class ApprovalTableUpdateActionCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ApprovalTableRetriever
     */
    private $approval_table_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->approval_table_retriever = Mockery::mock(ApprovalTableRetriever::class);
    }

    public function testItDoesnOtThrowAnExceptionWhenItemHasApprovalTableAndOptionIsCorrect()
    {
        $approval_checker                      = new ApprovalTableUpdateActionChecker($this->approval_table_retriever);
        $item                                  = Mockery::mock(\Docman_Item::class);
        $representation                        = new DocmanFilesPATCHRepresentation();
        $representation->approval_table_action = 'copy';

        $this->approval_table_retriever->shouldReceive('hasApprovalTable')->andReturn(true);

        $approval_checker->checkApprovalTableForItem($representation->approval_table_action, $item);
    }

    public function testCheckApprovalTableThrowsExceptionWhenItemHasApprovalTableAndApprovalActionIsNull(): void
    {
        $approval_checker                      = new ApprovalTableUpdateActionChecker($this->approval_table_retriever);
        $item                                  = Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getTitle')->andReturn("my item title");
        $representation                        = new DocmanFilesPATCHRepresentation();
        $representation->approval_table_action = null;

        $this->approval_table_retriever->shouldReceive('hasApprovalTable')->andReturn(true);

        $this->expectException(ApprovalTableException::class);
        $this->expectExceptionMessage('approval_table_action is required');

        $approval_checker->checkApprovalTableForItem($representation->approval_table_action, $item);
    }

    public function testCheckApprovalTableThrowsExceptionWhenItemHasNoApprovalTableButApprovalAction(): void
    {
        $approval_checker                      = new ApprovalTableUpdateActionChecker($this->approval_table_retriever);
        $item                                  = Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getTitle')->andReturn("my item title");
        $representation                        = new DocmanFilesPATCHRepresentation();
        $representation->approval_table_action = 'reset';

        $this->approval_table_retriever->shouldReceive('hasApprovalTable')->andReturn(false);

        $this->expectException(ApprovalTableException::class);
        $this->expectExceptionMessage('approval_table_action should not be provided');

        $approval_checker->checkApprovalTableForItem($representation->approval_table_action, $item);
    }

    public function testIfTheUpdateActionIsAvailable(): void
    {
        $approval_checker = new ApprovalTableUpdateActionChecker($this->approval_table_retriever);

        $this->assertTrue($approval_checker->checkAvailableUpdateAction('copy'));
        $this->assertTrue($approval_checker->checkAvailableUpdateAction('reset'));
        $this->assertTrue($approval_checker->checkAvailableUpdateAction('empty'));
    }

    public function testItReturnFalseBecauseTheActionIsNOTAvailabe(): void
    {
        $approval_checker = new ApprovalTableUpdateActionChecker($this->approval_table_retriever);

        $this->assertFalse($approval_checker->checkAvailableUpdateAction('nonon'));
    }
}
