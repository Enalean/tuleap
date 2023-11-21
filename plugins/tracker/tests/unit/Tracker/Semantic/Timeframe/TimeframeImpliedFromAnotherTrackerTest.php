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
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TimeframeImpliedFromAnotherTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TimeframeImpliedFromAnotherTracker $timeframe;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TimeframeWithDuration
     */
    private $timeframe_calculator;
    private NullLogger $logger;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LinksRetriever
     */
    private $links_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker
     */
    private $implied_from_tracker;
    private \Tracker $tracker;

    private const RELEASE_TRACKER_ID = 150;

    protected function setUp(): void
    {
        $this->tracker              = TrackerTestBuilder::aTracker()->withId(10)->build();
        $this->implied_from_tracker = $this->createMock(\Tracker::class);
        $this->implied_from_tracker->expects(self::any())->method('getName')->will(self::returnValue("Releases"));
        $this->implied_from_tracker->expects(self::any())->method('getId')->will(self::returnValue(self::RELEASE_TRACKER_ID));

        $this->timeframe_calculator = $this->createMock(TimeframeWithDuration::class);
        $this->logger               = new NullLogger();
        $this->links_retriever      = $this->createMock(LinksRetriever::class);

        $this->timeframe = new TimeframeImpliedFromAnotherTracker(
            $this->tracker,
            new SemanticTimeframe(
                $this->implied_from_tracker,
                $this->timeframe_calculator
            ),
            $this->links_retriever
        );
    }

    public function testItReturnsItsConfigDescription(): void
    {
        self::assertEquals(
            'Timeframes will be inherited from Releases linking artifacts of this tracker.',
            $this->timeframe->getConfigDescription()
        );
    }

    public function testItExportsToXML(): void
    {
        $root = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $this->timeframe->exportToXml($root, [
            'F101' => 1001,
            'F102' => 1002,
        ]);

        self::assertCount(1, $root->children());
        $this->assertEquals('timeframe', (string) $root->semantic['type']);
        $this->assertEquals('T150', (string) $root->semantic->inherited_from_tracker['id']);
    }

    public function testItDoesNotExportToRESTWhenUserCannotViewTheTargetTracker(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();
        $this->implied_from_tracker->expects(self::once())->method('userCanView')->willReturn(false);

        self::assertNull(
            $this->timeframe->exportToREST($user)
        );
    }

    public function testItExportsToREST(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();
        $this->implied_from_tracker->expects(self::once())->method('userCanView')->willReturn(true);

        self::assertEquals(
            new SemanticTimeframeImpliedFromAnotherTrackerRepresentation(self::RELEASE_TRACKER_ID),
            $this->timeframe->exportToREST($user)
        );
    }

    public function testFieldIsUsedWhenItIsAnArtLinkFieldComingFromCurrentTracker(): void
    {
        $a_field = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $a_field->expects(self::once())->method('getTrackerId')->willReturn(10);

        self::assertTrue($this->timeframe->isFieldUsed($a_field));
    }

    public function testFieldIsUsedWhenItIsAnArtLinkFieldComingFromTheTrackerWeUseTheSemantic(): void
    {
        $a_field = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $a_field->expects(self::once())->method('getTrackerId')->willReturn(self::RELEASE_TRACKER_ID);

        self::assertTrue($this->timeframe->isFieldUsed($a_field));
    }

    public function testFieldIsNotUsedWhenItIsNotAnArtLinkField(): void
    {
        $a_field = $this->createMock(\Tracker_FormElement_Field_Date::class);

        self::assertFalse($this->timeframe->isFieldUsed($a_field));
    }

    public function testFieldIsNotUsedWhenItIsAnArtLinkFieldComingFromATrackerDifferentThanTheCurrentOneAndTheTargetOne(): void
    {
        $a_field = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $a_field->expects(self::once())->method('getTrackerId')->willReturn(16);

        $this->implied_from_tracker->expects(self::once())->method('getId')->willReturn(12);

        self::assertFalse($this->timeframe->isFieldUsed($a_field));
    }

    public function testItIsDefined(): void
    {
        self::assertTrue($this->timeframe->isDefined());
    }

    public function testItSaves(): void
    {
        $dao = $this->getMockBuilder(SemanticTimeframeDao::class)->disableOriginalConstructor()->getMock();
        $dao->expects(self::once())->method('save')->with(10, null, null, null, 150)->will(self::returnValue(true));

        self::assertTrue(
            $this->timeframe->save($this->tracker, $dao)
        );
    }

    public function testItReturnsAnEmptyDatePeriodWithAnEmptyErrorMessageForArtifactWhenThereIsNoArtifactLinkingIt(): void
    {
        $user     = UserTestBuilder::anActiveUser()->build();
        $artifact = $this->getMockedArtifact(73);

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $changeset->method('getArtifact')->willReturn($artifact);

        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([]));

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangeset(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );

        self::assertNull($date_period->getStartDate());
        self::assertNull($date_period->getDuration());
        self::assertNull($date_period->getEndDate());
        self::assertEquals(
            '',
            $date_period->getErrorMessage()
        );
    }

    public function testItReturnsAnEmptyDatePeriodWithAnErrorMessageForArtifactWhenThereAreMoreThanOneArtifactReversLinkingIt(): void
    {
        $user     = UserTestBuilder::anActiveUser()->build();
        $artifact = $this->getMockedArtifact(73);
        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $this->getMockedArtifact(59),
                $this->getMockedArtifact(60),
            ]));

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $changeset->method('getArtifact')->willReturn($artifact);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangeset(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );

        self::assertNull($date_period->getStartDate());
        self::assertNull($date_period->getDuration());
        self::assertNull($date_period->getEndDate());
        self::assertEquals(
            'Unable to retrieve the time period: Too many artifacts from tracker Releases are linking artifact #73. There should only be one.',
            $date_period->getErrorMessage()
        );
    }

    public function testItReturnsTheDatePeriodOfTheArtifactLinkingIt(): void
    {
        $user             = UserTestBuilder::anActiveUser()->build();
        $artifact         = $this->getMockedArtifact(73);
        $linking_artifact = $this->getMockedArtifact(59);

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $changeset->method('getArtifact')->willReturn($artifact);

        $changeset2 = $this->createMock(\Tracker_Artifact_Changeset::class);
        $linking_artifact->method('getLastChangeset')->willReturn($changeset2);
        $changeset2->method('getArtifact')->willReturn($linking_artifact);

        $this->timeframe_calculator->expects(self::once())
            ->method('buildDatePeriodWithoutWeekendForChangeset')
            ->with($linking_artifact->getLastChangeset(), $user, $this->logger)
            ->willReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1622557210, 10));

        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $linking_artifact,
            ]));

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangeset(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );

        self::assertEquals(1622557210, $date_period->getStartDate());
        self::assertEquals(10, $date_period->getDuration());
        self::assertEquals(1623766810, $date_period->getEndDate());
    }

    public function testItReturnsAnEmptyDatePeriodWithAnEmptyErrorMessageForArtifactRESTWhenThereAreNoArtifactReverseLinkingIt(): void
    {
        $user     = UserTestBuilder::anActiveUser()->build();
        $artifact = $this->getMockedArtifact(73);
        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([]));

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $changeset->method('getArtifact')->willReturn($artifact);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );

        self::assertNull($date_period->getStartDate());
        self::assertNull($date_period->getDuration());
        self::assertNull($date_period->getEndDate());
        self::assertEquals(
            '',
            $date_period->getErrorMessage()
        );
    }

    public function testItReturnsAnEmptyDatePeriodWithAnErrorMessageForArtifactRESTWhenThereAreMoreThanOneArtifactReversLinkingIt(): void
    {
        $user     = UserTestBuilder::anActiveUser()->build();
        $artifact = $this->getMockedArtifact(73);
        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $this->getMockedArtifact(59),
                $this->getMockedArtifact(60),
            ]));

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $changeset->method('getArtifact')->willReturn($artifact);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );

        self::assertNull($date_period->getStartDate());
        self::assertNull($date_period->getDuration());
        self::assertNull($date_period->getEndDate());
        self::assertEquals(
            'Unable to retrieve the time period: Too many artifacts from tracker Releases are linking artifact #73. There should only be one.',
            $date_period->getErrorMessage()
        );
    }

    public function testItReturnsTheDatePeriodOfTheArtifactLinkingItForREST(): void
    {
        $user             = UserTestBuilder::anActiveUser()->build();
        $artifact         = $this->getMockedArtifact(73);
        $linking_artifact = $this->getMockedArtifact(59);

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $changeset->method('getArtifact')->willReturn($artifact);

        $changeset2 = $this->createMock(\Tracker_Artifact_Changeset::class);
        $linking_artifact->method('getLastChangeset')->willReturn($changeset2);
        $changeset2->method('getArtifact')->willReturn($linking_artifact);

        $this->timeframe_calculator->expects(self::once())
            ->method('buildDatePeriodWithoutWeekendForChangesetForREST')
            ->with($linking_artifact->getLastChangeset(), $user, $this->logger)
            ->willReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1622557210, 10));

        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $linking_artifact,
            ]));

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetForREST(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );

        self::assertEquals(1622557210, $date_period->getStartDate());
        self::assertEquals(10, $date_period->getDuration());
        self::assertEquals(1623766810, $date_period->getEndDate());
    }

    public function testItReturnsAnEmptyDatePeriodForArtifactChartRenderingWhenThereAreNoArtifactReverseLinkingIt(): void
    {
        $user     = UserTestBuilder::anActiveUser()->build();
        $artifact = $this->getMockedArtifact(73);
        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([]));

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $changeset->method('getArtifact')->willReturn($artifact);

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );

        self::assertNull($date_period->getStartDate());
        self::assertNull($date_period->getDuration());
        self::assertNull($date_period->getEndDate());
    }

    public function testItReturnsAnEmptyDatePeriodForArtifactChartRenderingWhenThereAreMoreThanOneArtifactReversLinkingIt(): void
    {
        $user     = UserTestBuilder::anActiveUser()->build();
        $artifact = $this->getMockedArtifact(73);
        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $this->getMockedArtifact(59),
                $this->getMockedArtifact(60),
            ]));

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $changeset->method('getArtifact')->willReturn($artifact);

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);
        $this->expectExceptionMessage('Unable to retrieve the time period: Too many artifacts from tracker Releases are linking artifact #73. There should only be one.');

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );

        self::assertNull($date_period->getStartDate());
        self::assertNull($date_period->getDuration());
        self::assertNull($date_period->getEndDate());
    }

    public function testItReturnsTheDatePeriodOfTheArtifactLinkingItForChartRendering(): void
    {
        $user             = UserTestBuilder::anActiveUser()->build();
        $artifact         = $this->getMockedArtifact(73);
        $linking_artifact = $this->getMockedArtifact(59);

        $changeset = $this->createMock(\Tracker_Artifact_Changeset::class);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $changeset->method('getArtifact')->willReturn($artifact);

        $changeset2 = $this->createMock(\Tracker_Artifact_Changeset::class);
        $linking_artifact->method('getLastChangeset')->willReturn($changeset2);
        $changeset2->method('getArtifact')->willReturn($linking_artifact);

        $this->timeframe_calculator->expects(self::once())
            ->method('buildDatePeriodWithoutWeekendForChangesetChartRendering')
            ->with($linking_artifact->getLastChangeset(), $user, $this->logger)
            ->willReturn(DatePeriodWithoutWeekEnd::buildFromDuration(1622557210, 10));

        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $linking_artifact,
            ]));

        $date_period = $this->timeframe->buildDatePeriodWithoutWeekendForChangesetChartRendering(
            $artifact->getLastChangeset(),
            $user,
            $this->logger
        );

        self::assertEquals(1622557210, $date_period->getStartDate());
        self::assertEquals(10, $date_period->getDuration());
        self::assertEquals(1623766810, $date_period->getEndDate());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Artifact
     */
    private function getMockedArtifact(int $id)
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->expects(self::any())->method('getId')->willReturn($id);

        return $artifact;
    }
}
