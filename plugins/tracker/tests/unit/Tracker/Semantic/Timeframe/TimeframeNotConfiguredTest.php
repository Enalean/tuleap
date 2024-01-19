<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace unit\Tracker\Semantic\Timeframe;

use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementDateFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TimeframeNotConfiguredTest extends TestCase
{
    private TimeframeNotConfigured $timeframe;

    protected function setUp(): void
    {
        $this->timeframe = new TimeframeNotConfigured();
    }

    public function testItReturnsItsConfigDescription(): void
    {
        self::assertEquals(
            'This semantic is not defined yet.',
            $this->timeframe->getConfigDescription()
        );
    }

    public function testItDoesNotExportToXML(): void
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->timeframe->exportToXml($root, [
            'F101' => 1001,
            'F102' => 1002,
        ]);
        $this->assertCount(0, $root->children());
    }

    public function testItDoesNotExportToREST(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();
        self::assertNull(
            $this->timeframe->exportToREST($user)
        );
    }

    public function testItFieldsAreAlwaysUnused(): void
    {
        $a_field = TrackerFormElementDateFieldBuilder::aDateField(1)->build();

        self::assertFalse($this->timeframe->isFieldUsed($a_field));
    }

    public function testItIsNotDefined(): void
    {
        self::assertFalse($this->timeframe->isDefined());
    }

    public function testItDoesNotSave(): void
    {
        $dao     = $this->getMockBuilder(SemanticTimeframeDao::class)->disableOriginalConstructor()->getMock();
        $tracker = TrackerTestBuilder::aTracker()->build();

        $dao->expects(self::never())->method('save');

        self::assertFalse(
            $this->timeframe->save($tracker, $dao)
        );
    }

    public function testItReturnsAnEmptyDatePeriodWithAnErrorMessageForArtifact(): void
    {
        $artifact  = $this->createMock(Artifact::class);
        $tracker   = TrackerTestBuilder::aTracker()->withName('User story')->build();
        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);

        $artifact->expects(self::once())->method('getLastChangeset')->willReturn($changeset);
        $changeset->expects(self::once())->method('getTracker')->willReturn($tracker);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            UserTestBuilder::anActiveUser()->build(),
            new NullLogger()
        );

        self::assertNull($date_period->getStartDate());
        self::assertNull($date_period->getDuration());
        self::assertNull($date_period->getEndDate());
        self::assertEquals(
            'Semantic Timeframe is not configured for tracker User story.',
            $date_period->getErrorMessage()
        );
    }

    public function testItReturnsAnEmptyDatePeriodWithAnErrorMessageForArtifactREST(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact  = ArtifactTestBuilder::anArtifact(1)
            ->withTitle('release_1_0')
            ->withChangesets($changeset)
            ->userCanView($user)
            ->build();
        $tracker   = TrackerTestBuilder::aTracker()->withName('User story')->build();
        $changeset->expects(self::once())->method('getTracker')->willReturn($tracker);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $user,
            new NullLogger()
        );

        self::assertNull($date_period->getStartDate());
        self::assertNull($date_period->getDuration());
        self::assertNull($date_period->getEndDate());
        self::assertEquals(
            'Semantic Timeframe is not configured for tracker User story.',
            $date_period->getErrorMessage()
        );
    }

    public function testItThrowsAnExceptionWhenInChartContext(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact  = ArtifactTestBuilder::anArtifact(1)
            ->withTitle('release_1_0')
            ->withChangesets($changeset)
            ->userCanView($user)
            ->build();
        $tracker   = TrackerTestBuilder::aTracker()->withName('User story')->build();

        $changeset->expects(self::once())->method('getTracker')->willReturn($tracker);

        self::expectException(\Tracker_FormElement_Chart_Field_Exception::class);

        $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $user,
            new NullLogger()
        );
    }
}
