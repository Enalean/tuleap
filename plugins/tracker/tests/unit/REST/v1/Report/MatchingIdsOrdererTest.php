<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Report;

use Tuleap\Test\PHPUnit\TestCase;

final class MatchingIdsOrdererTest extends TestCase
{
    private MatchingIdsOrderer $orderer;
    private \Tracker_Artifact_PriorityDao&\PHPUnit\Framework\MockObject\MockObject $dao;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(\Tracker_Artifact_PriorityDao::class);

        $this->orderer = new MatchingIdsOrderer($this->dao);
    }

    public function testItReturnsTheMatchingIdsArrayInGlobalRankOrder(): void
    {
        $matching_ids = [
            'id' => '1,2,3',
            'last_changeset_id' => '101,102,103',
        ];

        $this->dao->method('getGlobalRanks')->willReturn([
            [
                'rank' => 1,
                'artifact_id' => 3,
            ],
            [
                'rank' => 2,
                'artifact_id' => 1,
            ],
            [
                'rank' => 3,
                'artifact_id' => 2,
            ],
        ]);

        $result = $this->orderer->orderMatchingIdsByGlobalRank($matching_ids);

        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('last_changeset_id', $result);

        self::assertSame(
            '3,1,2',
            $result['id'],
        );

        self::assertSame(
            '103,101,102',
            $result['last_changeset_id'],
        );
    }

    public function testItReturnsAnEmptyArrayIfNoMatchingIds(): void
    {
        $matching_ids = [
            'id' => '',
            'last_changeset_id' => '',
        ];

        $result = $this->orderer->orderMatchingIdsByGlobalRank($matching_ids);

        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('last_changeset_id', $result);

        self::assertSame(
            '',
            $result['id'],
        );

        self::assertSame(
            '',
            $result['last_changeset_id'],
        );
    }

    public function testItThrowsALogicalExceptionIfTheMandatoryLastChangesetIdKeyInMatchingIdsIsMissing(): void
    {
        $matching_ids = [
            'id' => '1,2,3',
        ];

        $this->expectException(\LogicException::class);
        $this->orderer->orderMatchingIdsByGlobalRank($matching_ids);
    }

    public function testItThrowsALogicalExceptionIfTheMandatoryIdKeyInMatchingIdsIsMissing(): void
    {
        $matching_ids = [
            'last_changeset_id' => '1,2,3',
        ];

        $this->expectException(\LogicException::class);
        $this->orderer->orderMatchingIdsByGlobalRank($matching_ids);
    }

    public function testItSkipsAValuieInTheMatchingIdsArrayIfNotFound(): void
    {
        $matching_ids = [
            'id' => '1,2,3',
            'last_changeset_id' => '101,102,103',
        ];

        $this->dao->method('getGlobalRanks')->willReturn([
            [
                'rank' => 1,
                'artifact_id' => 3,
            ],
            [
                'rank' => 2,
                'artifact_id' => 1,
            ],
            [
                'rank' => 3,
                'artifact_id' => 4,
            ],
        ]);

        $result = $this->orderer->orderMatchingIdsByGlobalRank($matching_ids);

        self::assertArrayHasKey('id', $result);
        self::assertArrayHasKey('last_changeset_id', $result);

        self::assertSame(
            '3,1',
            $result['id'],
        );

        self::assertSame(
            '103,101',
            $result['last_changeset_id'],
        );
    }
}
