<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Tracker_Artifact_PriorityDao;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\REST\v1\Report\MatchingIdsOrderer;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class ReportArtifactFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReportArtifactFactory $report_artifact_factory;
    private \Tracker_ArtifactFactory&\PHPUnit\Framework\MockObject\MockObject $tracker_artifact_factory;
    private Tracker_Artifact_PriorityDao&\PHPUnit\Framework\MockObject\MockObject $dao;

    protected function setUp(): void
    {
        $this->tracker_artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->dao                      = $this->createMock(Tracker_Artifact_PriorityDao::class);

        $this->report_artifact_factory = new ReportArtifactFactory(
            $this->tracker_artifact_factory,
            new MatchingIdsOrderer($this->dao),
        );
    }

    public function testItReturnsAnEmptyCollectionWhenTheReportDoesNotMatchArtifacts(): void
    {
        $empty_report = $this->createMock(\Tracker_Report::class);
        $from_where   = new ParametrizedFromWhere('', '', [], []);

        $empty_report->method('getMatchingIdsWithAdditionalFromWhere')->willReturn(
            ['id' => '', 'last_changeset_id' => '']
        );

        $collection = $this->report_artifact_factory->getRankedArtifactsMatchingReportWithAdditionalFromWhere(
            $empty_report,
            $from_where,
            10,
            0,
        );

        self::assertEquals([], $collection->getArtifacts());
        self::assertEquals(0, $collection->getTotalSize());
    }

    public function testItReturnsACollectionOfMatchingArtifactsCorrespondingToLimitAndOffset(): void
    {
        $report     = $this->createMock(\Tracker_Report::class);
        $from_where = new ParametrizedFromWhere('', '', [], []);

        $report->method('getMatchingIdsWithAdditionalFromWhere')->willReturn(
            [
                'id' => '12,85,217,98',
                'last_changeset_id' => '12,85,217,98',
            ],
        );

        $this->dao->method('getGlobalRanks')->willReturn([
            [
                'rank' => 1,
                'artifact_id' => 12,
            ],
            [
                'rank' => 2,
                'artifact_id' => 85,
            ],
            [
                'rank' => 3,
                'artifact_id' => 217,
            ],
            [
                'rank' => 4,
                'artifact_id' => 98,
            ],
        ]);

        $artifact_one = ArtifactTestBuilder::anArtifact(85)->build();
        $artifact_two = ArtifactTestBuilder::anArtifact(217)->build();
        $this->tracker_artifact_factory->method('getArtifactsByArtifactIdList')->willReturn([$artifact_one, $artifact_two]);

        $collection = $this->report_artifact_factory->getRankedArtifactsMatchingReportWithAdditionalFromWhere(
            $report,
            $from_where,
            2,
            1,
        );

        self::assertEquals([$artifact_one, $artifact_two], $collection->getArtifacts());
        self::assertEquals(4, $collection->getTotalSize());
    }

    public function testItReturnsACollectionOfMatchingArtifactsCorrespondingToLimitAndOffsetWithRank(): void
    {
        $report     = $this->createMock(\Tracker_Report::class);
        $from_where = new ParametrizedFromWhere('', '', [], []);

        $report->method('getMatchingIdsWithAdditionalFromWhere')->willReturn(
            [
                'id' => '12,85,217,98',
                'last_changeset_id' => '12,85,217,98',
            ],
        );

        $this->dao->method('getGlobalRanks')->willReturn([
            [
                'rank' => 1,
                'artifact_id' => 98,
            ],
            [
                'rank' => 2,
                'artifact_id' => 85,
            ],
            [
                'rank' => 3,
                'artifact_id' => 217,
            ],
            [
                'rank' => 4,
                'artifact_id' => 12,
            ],
        ]);

        $artifact_one = ArtifactTestBuilder::anArtifact(85)->build();
        $artifact_two = ArtifactTestBuilder::anArtifact(98)->build();
        $this->tracker_artifact_factory->method('getArtifactsByArtifactIdList')->willReturn([$artifact_one, $artifact_two]);

        $collection = $this->report_artifact_factory->getRankedArtifactsMatchingReportWithAdditionalFromWhere(
            $report,
            $from_where,
            2,
            0,
        );

        self::assertEquals([$artifact_two, $artifact_one], $collection->getArtifacts());
        self::assertEquals(4, $collection->getTotalSize());
    }
}
