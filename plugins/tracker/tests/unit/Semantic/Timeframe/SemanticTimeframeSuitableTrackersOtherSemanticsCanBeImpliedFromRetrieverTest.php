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


#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SemanticTimeframeDao
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_FormElementFactory
     */
    private $form_element_factory;

    private SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever $retriever;

    protected function setUp(): void
    {
        $this->dao                  = $this->createMock(SemanticTimeframeDao::class);
        $this->tracker_factory      = $this->createMock(\TrackerFactory::class);
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);
        $this->retriever            = new SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever(
            $this->dao,
            $this->tracker_factory,
            $this->form_element_factory
        );
    }

    public function testItOnlyReturnsTrackersHavingAnArtLinkFieldAndADefinedSemanticTimeframeNotImpliedFromAnotherTracker(): void
    {
        $current_project_id = 104;
        $current_tracker_id = 10;
        $tasks_tracker      = $this->getMockedTracker(11);
        $bugs_tracker       = $this->getMockedTracker(12);
        $activities_tracker = $this->getMockedTracker(13);
        $sprints_tracker    = $this->getMockedTracker(14);
        $requests_tracker   = $this->getMockedTracker(15);
        $current_tracker    = $this->getMockedTracker($current_tracker_id);

        $current_tracker->expects(self::once())->method('getGroupId')->willReturn($current_project_id);

        $this->tracker_factory->expects(self::once())
            ->method('getTrackersByGroupId')
            ->with($current_project_id)
            ->willReturn(
                [
                    $current_tracker,
                    $tasks_tracker,
                    $bugs_tracker,
                    $activities_tracker,
                    $sprints_tracker,
                    $requests_tracker,
                ]
            );

        $this->form_element_factory->expects(self::exactly(5))
            ->method('getUsedArtifactLinkFields')
            ->willReturnCallback(
                fn (\Tracker $tracker): array => match ($tracker) {
                    $tasks_tracker, $bugs_tracker, $activities_tracker, $sprints_tracker => [$this->createStub(\Tracker_FormElement_Field_ArtifactLink::class)],
                    $requests_tracker => []
                }
            );

        $this->dao->expects(self::exactly(4))
            ->method('searchByTrackerId')
            ->willReturnCallback(
                fn (int $tracker_id): ?array => match ($tracker_id) {
                    11 => null,
                    12 => [],
                    13 => [
                        'start_date_field_id' => null,
                        'end_date_field_id' => null,
                        'duration_field_id' => null,
                        'implied_from_tracker_id' => 14,
                    ],
                    14 => [
                        'start_date_field_id' => 1001,
                        'end_date_field_id' => 1002,
                        'duration_field_id' => null,
                        'implied_from_tracker_id' => null,
                    ]
                }
            );

        self::assertEquals(
            ['14' => $sprints_tracker],
            $this->retriever->getTrackersWeCanUseToImplyTheSemanticOfTheCurrentTrackerFrom($current_tracker)
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Tracker
     */
    private function getMockedTracker(int $id)
    {
        $mock = $this->createMock(\Tracker::class);
        $mock->expects(self::any())->method('getId')->willReturn($id);
        return $mock;
    }
}
