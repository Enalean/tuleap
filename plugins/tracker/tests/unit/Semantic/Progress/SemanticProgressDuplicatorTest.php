<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use PHPUnit\Framework\MockObject\MockObject;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticProgressDuplicatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SemanticProgressDao&MockObject $dao;
    private SemanticProgressDuplicator $duplicator;

    protected function setUp(): void
    {
        $this->dao        = $this->createMock(SemanticProgressDao::class);
        $this->duplicator = new SemanticProgressDuplicator($this->dao);
    }

    public function testItDoesNotDuplicateIfThereIsNoExistingConfig(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn(null);

        $this->dao
            ->expects($this->never())
            ->method('save');

        $this->duplicator->duplicate(1, 2, [
            ['from' => 101, 'to' => 1001],
            ['from' => 102, 'to' => 1002],
        ]);
    }

    /**
     * @testWith [101, null, null]
     *           [null, 102, null]
     *           [null, null, null]
     */
    public function testItDoesNotDuplicateWhenConfigIsMessedUp(
        ?int $total_effort_field_id,
        ?int $remaining_effort_field_id,
        ?string $link_type,
    ): void {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn([
                'total_effort_field_id' => $total_effort_field_id,
                'remaining_effort_field_id' => $remaining_effort_field_id,
                'artifact_link_type' => $link_type,
            ]);

        $this->dao
            ->expects($this->never())
            ->method('save');

        $this->duplicator->duplicate(1, 2, [
            ['from' => 101, 'to' => 1001],
            ['from' => 102, 'to' => 1002],
        ]);
    }

    public function testItDuplicatesEffortBasedSemantics(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn([
                'total_effort_field_id' => 101,
                'remaining_effort_field_id' => 102,
                'artifact_link_type' => null,
            ]);

        $this->dao
            ->expects($this->once())
            ->method('save')
            ->with(2, 1001, 1002, null);

        $this->duplicator->duplicate(
            1,
            2,
            [
                ['from' => 101, 'to' => 1001],
                ['from' => 102, 'to' => 1002],
            ]
        );
    }

    public function testItDuplicatesLinksCountBasedSemantics(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(1)
            ->willReturn([
                'total_effort_field_id' => null,
                'remaining_effort_field_id' => null,
                'artifact_link_type' => '_is_child',
            ]);

        $this->dao
            ->expects($this->once())
            ->method('save')
            ->with(2, null, null, '_is_child');

        $this->duplicator->duplicate(
            1,
            2,
            []
        );
    }
}
