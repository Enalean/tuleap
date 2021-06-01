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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Psr\Log\NullLogger;
use Tuleap\Tracker\Artifact\Artifact;

class TimeframeImpliedFromAnotherTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var TimeframeImpliedFromAnotherTracker
     */
    private $timeframe;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker
     */
    private $implied_from_tracker;

    protected function setUp(): void
    {
        $implied_from_tracker = $this->createMock(\Tracker::class);
        $implied_from_tracker->expects(self::any())->method('getName')->will(self::returnValue("Releases"));

        $this->timeframe = new TimeframeImpliedFromAnotherTracker(
            new SemanticTimeframe(
                $implied_from_tracker,
                new TimeframeWithEndDate(
                    $this->createMock(\Tracker_FormElement_Field_Date::class),
                    $this->createMock(\Tracker_FormElement_Field_Date::class)
                )
            )
        );
    }

    public function testItReturnsItsConfigDescription(): void
    {
        self::assertEquals(
            'Timeframes will be based on Releases linking artifacts of this tracker.',
            $this->timeframe->getConfigDescription()
        );
    }

    public function testItDoesNotExportToXMLYet(): void
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->timeframe->exportToXml($root, [
            'F101' => 1001,
            'F102' => 1002
        ]);
        self::assertCount(0, $root->children());
    }

    public function testItDoesNotExportToRESTYet(): void
    {
        $user = $this->createMock(\PFUser::class);
        self::assertNull(
            $this->timeframe->exportToREST($user)
        );
    }

    public function testFieldsAreAlwaysUnused(): void
    {
        $a_field = $this->getMockBuilder(\Tracker_FormElement_Field_Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        self::assertFalse($this->timeframe->isFieldUsed($a_field));
    }

    public function testItIsDefined(): void
    {
        self::assertTrue($this->timeframe->isDefined());
    }

    public function testItDoesNotSaveYet(): void
    {
        $dao     = $this->createMock(SemanticTimeframeDao::class);
        $tracker = $this->createMock(\Tracker::class);

        $dao->expects(self::never())->method('save');

        self::assertFalse(
            $this->timeframe->save($tracker, $dao)
        );
    }

    public function testItReturnsAnEmptyTimePeriodForArtifactForTheMoment(): void
    {
        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifact(
            $this->createMock(Artifact::class),
            $this->createMock(\PFUser::class),
            new NullLogger()
        );

        self::assertNull($time_period->getStartDate());
        self::assertNull($time_period->getDuration());
        self::assertNull($time_period->getEndDate());
    }

    public function testItReturnsAnEmptyTimePeriodForArtifactRESTForTheMoment(): void
    {
        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactForREST(
            $this->createMock(Artifact::class),
            $this->createMock(\PFUser::class),
            new NullLogger()
        );

        self::assertNull($time_period->getStartDate());
        self::assertNull($time_period->getDuration());
        self::assertNull($time_period->getEndDate());
    }

    public function testItReturnsAnEmptyTimePeriodInChartContextForTheMoment(): void
    {
        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $this->createMock(Artifact::class),
            $this->createMock(\PFUser::class),
            new NullLogger()
        );

        self::assertNull($time_period->getStartDate());
        self::assertNull($time_period->getDuration());
        self::assertNull($time_period->getEndDate());
    }
}
