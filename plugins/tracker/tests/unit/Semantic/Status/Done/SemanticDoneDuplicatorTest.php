<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
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

namespace Tuleap\Tracker\Semantic\Status\Done;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Option\Option;
use Tuleap\Tracker\Semantic\Status\StatusSemanticDAO;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticDoneDuplicatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SemanticDoneDuplicator $duplicator;

    private SemanticDoneDao&MockObject $done_dao;

    private StatusSemanticDAO&MockObject $status_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->done_dao   = $this->createMock(SemanticDoneDao::class);
        $this->status_dao = $this->createMock(StatusSemanticDAO::class);

        $this->duplicator = new SemanticDoneDuplicator(
            $this->done_dao,
            $this->status_dao
        );
    }

    public function testItDuplicatesDoneSemantic(): void
    {
        $mapping = [
            [
                'from' => '712',
                'to'   => 9595,
                'values' => [
                    460 => 6226,
                    461 => 6227,
                    462 => 6228,
                ],
            ],
        ];

        $this->done_dao
            ->expects($this->once())
            ->method('getSelectedValues')
            ->willReturn([
                ['value_id' => '462'],
            ]);

        $this->status_dao
            ->expects($this->once())
            ->method('searchFieldByTrackerId')
            ->willReturn(Option::fromValue(712));

        $this->done_dao
            ->expects($this->once())
            ->method('addForTracker')
            ->with(201, [6228]);

        $this->duplicator->duplicate(101, 201, $mapping);
    }

    public function testItDoesNotDuplicateEmptyDoneSemantic(): void
    {
        $mapping = [
            [
                'from' => '712',
                'to'   => 9595,
                'values' => [
                    460 => 6226,
                    461 => 6227,
                    462 => 6228,
                ],
            ],
        ];

        $this->done_dao
            ->expects($this->once())
            ->method('getSelectedValues')
            ->willReturn([]);

        $this->status_dao->expects($this->never())->method('searchFieldByTrackerId');
        $this->done_dao->expects($this->never())->method('addForTracker');

        $this->duplicator->duplicate(101, 201, $mapping);
    }

    public function testItDoesNotDuplicateDoneSemanticIfItCannotRetrieveBaseStatusField(): void
    {
        $mapping = [
            [
                'from' => '712',
                'to'   => 9595,
                'values' => [
                    460 => 6226,
                    461 => 6227,
                    462 => 6228,
                ],
            ],
        ];

        $this->done_dao
            ->expects($this->once())
            ->method('getSelectedValues')
            ->willReturn([
                ['value_id' => '462'],
            ]);

        $this->status_dao
            ->expects($this->once())
            ->method('searchFieldByTrackerId')
            ->willReturn(Option::nothing(\Psl\Type\int()));

        $this->done_dao->expects($this->never())->method('addForTracker');

        $this->duplicator->duplicate(101, 201, $mapping);
    }

    public function testItDoesNotDuplicateDoneSemanticIfItCannotRetrieveValueInMapping(): void
    {
        $mapping = [
            [
                'from' => '712',
                'to'   => 9595,
                'values' => [
                    460 => 6226,
                    461 => 6227,
                    462 => 6228,
                ],
            ],
        ];

        $this->done_dao
            ->expects($this->once())
            ->method('getSelectedValues')
            ->willReturn([
                ['value_id' => '463'],
            ]);

        $this->status_dao
            ->expects($this->once())
            ->method('searchFieldByTrackerId')
            ->willReturn(Option::fromValue(712));

        $this->done_dao->expects($this->never())->method('addForTracker');

        $this->duplicator->duplicate(101, 201, $mapping);
    }
}
