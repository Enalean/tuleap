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

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;

class TimeframeImpliedFromAnotherTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var TimeframeImpliedFromAnotherTracker
     */
    private $timeframe;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TimeframeWithDuration
     */
    private $timeframe_calculator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private $logger;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LinksRetriever
     */
    private $links_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker
     */
    private $implied_from_tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker
     */
    private $tracker;

    private const RELEASE_TRACKER_ID = 150;

    protected function setUp(): void
    {
        $this->tracker              = $this->createMock(\Tracker::class);
        $this->implied_from_tracker = $this->createMock(\Tracker::class);
        $this->implied_from_tracker->expects(self::any())->method('getName')->will(self::returnValue("Releases"));
        $this->implied_from_tracker->expects(self::any())->method('getId')->will(self::returnValue(self::RELEASE_TRACKER_ID));

        $this->timeframe_calculator = $this->createMock(TimeframeWithDuration::class);
        $this->logger               = $this->createMock(LoggerInterface::class);
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
        $user = $this->createMock(\PFUser::class);
        $this->implied_from_tracker->expects(self::once())->method('userCanView')->willReturn(false);

        self::assertNull(
            $this->timeframe->exportToREST($user)
        );
    }

    public function testItExportsToREST(): void
    {
        $user = $this->createMock(\PFUser::class);
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

        $this->tracker->expects(self::once())->method('getId')->willReturn(10);

        self::assertTrue($this->timeframe->isFieldUsed($a_field));
    }

    public function testFieldIsUsedWhenItIsAnArtLinkFieldComingFromTheTrackerWeUseTheSemantic(): void
    {
        $a_field = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $a_field->expects(self::once())->method('getTrackerId')->willReturn(self::RELEASE_TRACKER_ID);

        $this->tracker->expects(self::once())->method('getId')->willReturn(10);

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

        $this->tracker->expects(self::once())->method('getId')->willReturn(10);
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
        $this->tracker->expects(self::once())->method('getId')->will(self::returnValue(10));
        $dao->expects(self::once())->method('save')->with(10, null, null, null, 150)->will(self::returnValue(true));

        self::assertTrue(
            $this->timeframe->save($this->tracker, $dao)
        );
    }

    public function testItReturnsAnEmptyTimePeriodWithAnEmptyErrorMessageForArtifactWhenThereIsNoArtifactLinkingIt(): void
    {
        $user     = $this->createMock(\PFUser::class);
        $artifact = $this->getMockedArtifact(73);

        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([]));

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifact(
            $artifact,
            $user,
            $this->logger
        );

        self::assertNull($time_period->getStartDate());
        self::assertNull($time_period->getDuration());
        self::assertNull($time_period->getEndDate());
        self::assertEquals(
            '',
            $time_period->getErrorMessage()
        );
    }

    public function testItReturnsAnEmptyTimePeriodWithAnErrorMessageForArtifactWhenThereAreMoreThanOneArtifactReversLinkingIt(): void
    {
        $user     = $this->createMock(\PFUser::class);
        $artifact = $this->getMockedArtifact(73);
        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $this->getMockedArtifact(59),
                $this->getMockedArtifact(60),
            ]));

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifact(
            $artifact,
            $user,
            $this->logger
        );

        self::assertNull($time_period->getStartDate());
        self::assertNull($time_period->getDuration());
        self::assertNull($time_period->getEndDate());
        self::assertEquals(
            'Unable to retrieve the time period: Too many artifacts from tracker Releases are linking artifact #73. There should only be one.',
            $time_period->getErrorMessage()
        );
    }

    public function testItReturnsTheTimePeriodOfTheArtifactLinkingIt(): void
    {
        $user             = $this->createMock(\PFUser::class);
        $artifact         = $this->getMockedArtifact(73);
        $linking_artifact = $this->getMockedArtifact(59);

        $this->timeframe_calculator->expects(self::once())
            ->method('buildTimePeriodWithoutWeekendForArtifact')
            ->with($linking_artifact, $user, $this->logger)
            ->willReturn(\TimePeriodWithoutWeekEnd::buildFromDuration(1622557210, 10));

        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $linking_artifact,
            ]));

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifact(
            $artifact,
            $user,
            $this->logger
        );

        self::assertEquals(1622557210, $time_period->getStartDate());
        self::assertEquals(10, $time_period->getDuration());
        self::assertEquals(1623766810, $time_period->getEndDate());
    }

    public function testItReturnsAnEmptyTimePeriodWithAnEmptyErrorMessageForArtifactRESTWhenThereAreNoArtifactReverseLinkingIt(): void
    {
        $user     = $this->createMock(\PFUser::class);
        $artifact = $this->getMockedArtifact(73);
        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([]));

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactForREST(
            $artifact,
            $user,
            $this->logger
        );

        self::assertNull($time_period->getStartDate());
        self::assertNull($time_period->getDuration());
        self::assertNull($time_period->getEndDate());
        self::assertEquals(
            '',
            $time_period->getErrorMessage()
        );
    }

    public function testItReturnsAnEmptyTimePeriodWithAnErrorMessageForArtifactRESTWhenThereAreMoreThanOneArtifactReversLinkingIt(): void
    {
        $user     = $this->createMock(\PFUser::class);
        $artifact = $this->getMockedArtifact(73);
        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $this->getMockedArtifact(59),
                $this->getMockedArtifact(60),
            ]));

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactForREST(
            $artifact,
            $user,
            $this->logger
        );

        self::assertNull($time_period->getStartDate());
        self::assertNull($time_period->getDuration());
        self::assertNull($time_period->getEndDate());
        self::assertEquals(
            'Unable to retrieve the time period: Too many artifacts from tracker Releases are linking artifact #73. There should only be one.',
            $time_period->getErrorMessage()
        );
    }

    public function testItReturnsTheTimePeriodOfTheArtifactLinkingItForREST(): void
    {
        $user             = $this->createMock(\PFUser::class);
        $artifact         = $this->getMockedArtifact(73);
        $linking_artifact = $this->getMockedArtifact(59);

        $this->timeframe_calculator->expects(self::once())
            ->method('buildTimePeriodWithoutWeekendForArtifactForREST')
            ->with($linking_artifact, $user, $this->logger)
            ->willReturn(\TimePeriodWithoutWeekEnd::buildFromDuration(1622557210, 10));

        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $linking_artifact,
            ]));

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactForREST(
            $artifact,
            $user,
            $this->logger
        );

        self::assertEquals(1622557210, $time_period->getStartDate());
        self::assertEquals(10, $time_period->getDuration());
        self::assertEquals(1623766810, $time_period->getEndDate());
    }

    public function testItReturnsAnEmptyTimePeriodForArtifactChartRenderingWhenThereAreNoArtifactReverseLinkingIt(): void
    {
        $user     = $this->createMock(\PFUser::class);
        $artifact = $this->getMockedArtifact(73);
        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([]));

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $artifact,
            $user,
            $this->logger
        );

        self::assertNull($time_period->getStartDate());
        self::assertNull($time_period->getDuration());
        self::assertNull($time_period->getEndDate());
    }

    public function testItReturnsAnEmptyTimePeriodForArtifactChartRenderingWhenThereAreMoreThanOneArtifactReversLinkingIt(): void
    {
        $user     = $this->createMock(\PFUser::class);
        $artifact = $this->getMockedArtifact(73);
        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $this->getMockedArtifact(59),
                $this->getMockedArtifact(60),
            ]));

        $this->expectException(\Tracker_FormElement_Chart_Field_Exception::class);
        $this->expectExceptionMessage('Unable to retrieve the time period: Too many artifacts from tracker Releases are linking artifact #73. There should only be one.');

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $artifact,
            $user,
            $this->logger
        );

        self::assertNull($time_period->getStartDate());
        self::assertNull($time_period->getDuration());
        self::assertNull($time_period->getEndDate());
    }

    public function testItReturnsTheTimePeriodOfTheArtifactLinkingItForChartRendering(): void
    {
        $user             = $this->createMock(\PFUser::class);
        $artifact         = $this->getMockedArtifact(73);
        $linking_artifact = $this->getMockedArtifact(59);

        $this->timeframe_calculator->expects(self::once())
            ->method('buildTimePeriodWithoutWeekendForArtifactChartRendering')
            ->with($linking_artifact, $user, $this->logger)
            ->willReturn(\TimePeriodWithoutWeekEnd::buildFromDuration(1622557210, 10));

        $this->links_retriever->expects(self::once())
            ->method('retrieveReverseLinksFromTracker')
            ->with($artifact, $user, $this->implied_from_tracker)
            ->will(self::returnValue([
                $linking_artifact,
            ]));

        $time_period = $this->timeframe->buildTimePeriodWithoutWeekendForArtifactChartRendering(
            $artifact,
            $user,
            $this->logger
        );

        self::assertEquals(1622557210, $time_period->getStartDate());
        self::assertEquals(10, $time_period->getDuration());
        self::assertEquals(1623766810, $time_period->getEndDate());
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
