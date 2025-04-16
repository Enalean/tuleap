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

namespace Tuleap\Tracker\Semantic\Timeframe\Administration;

use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeImpliedFromAnotherTracker;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class SemanticTimeframeCurrentConfigurationPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_FormElement_Field_Date
     */
    private $start_date_field;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_FormElement_Field_Numeric
     */
    private $duration_field;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker
     */
    private $current_tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SemanticTimeframeDao
     */
    private $dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\TrackerFactory
     */
    private $tracker_factory;

    private const CURRENT_TRACKER_ID = 160;
    private const ANOTHER_TRACKER_ID = 150;

    public function setUp(): void
    {
        $this->start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $this->duration_field   = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $this->current_tracker  = $this->createMock(\Tracker::class);
        $this->dao              = $this->createMock(SemanticTimeframeDao::class);
        $this->tracker_factory  = $this->createMock(\TrackerFactory::class);
    }

    public function testItBuildsAPresenterForASemanticImpliedFromAnotherTracker(): void
    {
        $this->current_tracker->expects($this->any())->method('getId')->willReturn(self::CURRENT_TRACKER_ID);

        $another_tracker = $this->createMock(\Tracker::class);
        $another_tracker->expects($this->any())->method('getName')->willReturn('Sprints');
        $another_tracker->expects($this->any())->method('getId')->willReturn(self::ANOTHER_TRACKER_ID);

        $this->dao->expects($this->never())->method('getSemanticsImpliedFromGivenTracker');
        $this->dao->expects($this->once())->method('searchByTrackerId')->with(self::CURRENT_TRACKER_ID)->willReturn([
            'start_date_field_id' => null,
            'duration_field_id' => null,
            'end_date_field_id' => null,
            'implied_from_tracker_id' => self::ANOTHER_TRACKER_ID,
        ]);

        $this->tracker_factory->expects($this->once())->method('getTrackerById')->with(self::ANOTHER_TRACKER_ID)->willReturn($another_tracker);

        $builder = new SemanticTimeframeCurrentConfigurationPresenterBuilder(
            $this->current_tracker,
            new TimeframeImpliedFromAnotherTracker(
                $this->current_tracker,
                new SemanticTimeframe(
                    $another_tracker,
                    new TimeframeWithDuration(
                        $this->start_date_field,
                        $this->duration_field
                    )
                ),
                $this->createMock(LinksRetriever::class)
            ),
            $this->dao,
            $this->tracker_factory
        );

        $presenter = $builder->build();

        self::assertEquals('Timeframes will be inherited from Sprints linking artifacts of this tracker.', $presenter->current_config_description);
        self::assertEmpty($presenter->semantics_implied_from_current_tracker);
        self::assertTrue($presenter->is_semantic_implied);
        self::assertFalse($presenter->are_semantics_implied_from_current_tracker);
        self::assertSame(
            '/plugins/tracker/?tracker=150&func=admin-semantic&semantic=timeframe',
            $presenter->tracker_from_which_we_imply_the_semantic_admin_url
        );
        self::assertEquals('Sprints', $presenter->tracker_name_from_which_we_imply_the_semantic);
    }

    public function testItBuildsAPresenterWithAListOfTrackerImplyingTheirSemanticsTimeframe(): void
    {
        $this->start_date_field->expects($this->once())->method('getLabel')->willReturn('Start date');
        $this->duration_field->expects($this->once())->method('getLabel')->willReturn('Duration');

        $this->current_tracker->expects($this->any())->method('getId')->willReturn(self::CURRENT_TRACKER_ID);

        $another_tracker = $this->createMock(\Tracker::class);
        $another_tracker->expects($this->any())->method('getName')->willReturn('User Story');
        $another_tracker->expects($this->any())->method('getId')->willReturn(self::ANOTHER_TRACKER_ID);

        $this->dao->expects($this->never())->method('searchByTrackerId');
        $this->dao->expects($this->once())->method('getSemanticsImpliedFromGivenTracker')->with(self::CURRENT_TRACKER_ID)->willReturn(
            [
                [
                    'tracker_id' => self::ANOTHER_TRACKER_ID,
                    'implied_from_tracker_id' => self::CURRENT_TRACKER_ID,
                ],
            ]
        );

        $this->tracker_factory->expects($this->once())->method('getTrackerById')->with(self::ANOTHER_TRACKER_ID)->willReturn($another_tracker);

        $builder = new SemanticTimeframeCurrentConfigurationPresenterBuilder(
            $this->current_tracker,
            new TimeframeWithDuration(
                $this->start_date_field,
                $this->duration_field
            ),
            $this->dao,
            $this->tracker_factory
        );

        $presenter = $builder->build();

        self::assertEquals('Timeframe is based on start date field "Start date" and duration field "Duration".', $presenter->current_config_description);
        self::assertEquals(
            [
                [
                    'tracker_name' => 'User Story',
                    'tracker_semantic_timeframe_admin_url' => '/plugins/tracker/?tracker=150&func=admin-semantic&semantic=timeframe',
                ],
            ],
            $presenter->semantics_implied_from_current_tracker
        );
        self::assertFalse($presenter->is_semantic_implied);
        self::assertTrue($presenter->are_semantics_implied_from_current_tracker);
        self::assertSame(
            '',
            $presenter->tracker_from_which_we_imply_the_semantic_admin_url
        );
    }
}
