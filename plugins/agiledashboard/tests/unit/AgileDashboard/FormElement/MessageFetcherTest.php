<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use AgileDashBoard_Semantic_InitialEffort;
use AgileDashboard_Semantic_InitialEffortFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use PlanningFactory;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MessageFetcherTest extends TestCase
{
    private TrackerField $field;
    private Tracker $backlog_tracker;
    private Tracker $tracker;
    private PlanningFactory&MockObject $planning_factory;
    private MessageFetcher $message_fetcher;
    private AgileDashboard_Semantic_InitialEffortFactory&MockObject $initial_effort_factory;
    private SemanticDoneFactory&MockObject $semantic_done_factory;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->planning_factory       = $this->createMock(PlanningFactory::class);
        $this->initial_effort_factory = $this->createMock(AgileDashboard_Semantic_InitialEffortFactory::class);
        $this->semantic_done_factory  = $this->createMock(SemanticDoneFactory::class);

        $this->message_fetcher = new MessageFetcher(
            $this->planning_factory,
            $this->initial_effort_factory,
            $this->semantic_done_factory
        );

        $this->tracker         = TrackerTestBuilder::aTracker()->build();
        $this->backlog_tracker = TrackerTestBuilder::aTracker()->build();
        $this->field           = IntegerFieldBuilder::anIntField(145)->build();
        $this->user            = UserTestBuilder::buildWithDefaults();
    }

    public function testItDoesNotAddWarningsIfAllIsWellConfigured(): void
    {
        $planning       = $this->getPlanning();
        $semantic_done  = $this->getMockedSemanticDone(true);
        $initial_effort = $this->getInitialEffortFieldSemantic();

        $this->planning_factory->method('getPlanningByPlanningTracker')->with($this->user, $this->tracker)->willReturn($planning);
        $this->semantic_done_factory->method('getInstanceByTracker')->with($this->backlog_tracker)->willReturn($semantic_done);
        $this->initial_effort_factory->method('getByTracker')->with($this->backlog_tracker)->willReturn($initial_effort);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->user, $this->tracker);

        self::assertEmpty($warnings);
    }

    public function testItReturnsAWarningIfTrackerIsNotAPlanningTracker(): void
    {
        $this->planning_factory->method('getPlanningByPlanningTracker')->with($this->user, $this->tracker)->willReturn(null);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->user, $this->tracker);

        self::assertNotEmpty($warnings);
    }

    public function testItReturnsAWarningIfBacklogTrackerDoesNotHaveSemanticDone(): void
    {
        $planning       = $this->getPlanning();
        $semantic_done  = $this->getMockedSemanticDone(false);
        $initial_effort = $this->getInitialEffortFieldSemantic();

        $this->planning_factory->method('getPlanningByPlanningTracker')->with($this->user, $this->tracker)->willReturn($planning);
        $this->semantic_done_factory->method('getInstanceByTracker')->with($this->backlog_tracker)->willReturn($semantic_done);
        $this->initial_effort_factory->method('getByTracker')->with($this->backlog_tracker)->willReturn($initial_effort);
        $semantic_done->method('getUrl');

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->user, $this->tracker);

        self::assertNotEmpty($warnings);
    }

    public function testItReturnsAWarningIfBacklogTrackerDoesNotHaveSemanticInitialEffort(): void
    {
        $planning       = $this->getPlanning();
        $semantic_done  = $this->getMockedSemanticDone(true);
        $initial_effort = new AgileDashBoard_Semantic_InitialEffort($this->tracker, null);

        $this->planning_factory->method('getPlanningByPlanningTracker')->with($this->user, $this->tracker)->willReturn($planning);
        $this->semantic_done_factory->method('getInstanceByTracker')->with($this->backlog_tracker)->willReturn($semantic_done);
        $this->initial_effort_factory->method('getByTracker')->with($this->backlog_tracker)->willReturn($initial_effort);

        $warnings = $this->message_fetcher->getWarningsRelatedToPlanningConfiguration($this->user, $this->tracker);

        self::assertNotEmpty($warnings);
    }

    private function getPlanning(): Planning
    {
        return PlanningBuilder::aPlanning(101)->withBacklogTrackers($this->backlog_tracker)->build();
    }

    private function getMockedSemanticDone(bool $is_semantic_defined): SemanticDone&MockObject
    {
        $semantic = $this->createMock(SemanticDone::class);
        $semantic->method('isSemanticDefined')->willReturn($is_semantic_defined);
        return $semantic;
    }

    private function getInitialEffortFieldSemantic(): AgileDashBoard_Semantic_InitialEffort
    {
        return new AgileDashBoard_Semantic_InitialEffort(
            $this->tracker,
            $this->field,
        );
    }
}
