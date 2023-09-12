<?php
/**
 * Copyright (c) Enalean 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Masschange;

use Codendi_Request;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\BlockScrumAccess;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\GlobalResponseMock;
use Tuleap\Kanban\CheckSplitKanbanConfiguration;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

class AdditionalMasschangeActionProcessorTest extends TestCase
{
    use GlobalResponseMock;

    private AdditionalMasschangeActionProcessor $processor;

    private PFUser $user;

    private Tracker|MockObject $tracker;

    private ArtifactsInExplicitBacklogDao|MockObject $artifacts_in_explicit_backlog_dao;

    private PlannedArtifactDao|MockObject $planned_artifact_dao;

    private UnplannedArtifactsAdder|MockObject $unplanned_artifacts_adder;

    private EventDispatcherInterface $event_dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifacts_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->planned_artifact_dao              = $this->createMock(PlannedArtifactDao::class);
        $this->unplanned_artifacts_adder         = $this->createMock(UnplannedArtifactsAdder::class);
        $this->event_dispatcher                  = EventDispatcherStub::withIdentityCallback();


        $this->tracker = $this->createMock(Tracker::class);

        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->tracker
            ->method('getProject')
            ->willReturn($project);
    }

    private function processAction(Codendi_Request $request): void
    {
        $this->processor = new AdditionalMasschangeActionProcessor(
            $this->artifacts_in_explicit_backlog_dao,
            $this->planned_artifact_dao,
            $this->unplanned_artifacts_adder,
            $this->event_dispatcher,
            new CheckSplitKanbanConfiguration($this->event_dispatcher)
        );
        $user            = UserTestBuilder::buildWithDefaults();
        $this->processor->processAction($user, $this->tracker, $request, ['125', '144']);
    }

    public function testItDoesNothingIfUserIsNotTrackerAdmin(): void
    {
        $request = new Codendi_Request([]);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(false);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeItemsFromExplicitBacklogOfProject');
        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');
        $this->planned_artifact_dao->expects(self::never())->method('isArtifactPlannedInAMilestoneOfTheProject');

        $this->processAction(
            $request,
        );
    }

    public function testItDoesNothingWhenScrumAccessIsBlocked(): void
    {
        $request = new Codendi_Request([]);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $this->event_dispatcher =  EventDispatcherStub::withCallback(function (object $event) {
            if ($event instanceof BlockScrumAccess) {
                $event->disableScrumAccess();
            }
            return $event;
        });

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeItemsFromExplicitBacklogOfProject');
        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');
        $this->planned_artifact_dao->expects(self::never())->method('isArtifactPlannedInAMilestoneOfTheProject');

        $this->processAction(
            $request,
        );
    }

    public function testItDoesNothingIfMasschangeActionIsNotInRequest(): void
    {
        $request = new Codendi_Request([]);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeItemsFromExplicitBacklogOfProject');
        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');
        $this->planned_artifact_dao->expects(self::never())->method('isArtifactPlannedInAMilestoneOfTheProject');

        $this->processAction(
            $request,
        );
    }

    public function testItDoesNothingIfMasschangeActionIsUnchanged(): void
    {
        $request = new Codendi_Request(['masschange-action-explicit-backlog' => 'unchanged']);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeItemsFromExplicitBacklogOfProject');
        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');
        $this->planned_artifact_dao->expects(self::never())->method('isArtifactPlannedInAMilestoneOfTheProject');

        $this->processAction(
            $request,
        );
    }

    public function testItRemovesArtifactsFromBacklogIfMasschangeActionIsRemove(): void
    {
        $request = new Codendi_Request(['masschange-action-explicit-backlog' => 'remove']);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('removeItemsFromExplicitBacklogOfProject')->with(101, ['125', '144']);
        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');

        $this->planned_artifact_dao->method('isArtifactPlannedInAMilestoneOfTheProject')->willReturn(false);

        $this->processAction(
            $request,
        );
    }

    public function testItAsksForRemovalFromBacklogEvenIfArtifactAreAlreadyPlanned(): void
    {
        $request = new Codendi_Request(['masschange-action-explicit-backlog' => 'remove']);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('removeItemsFromExplicitBacklogOfProject')->with(101, ['125', '144']);
        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');

        $this->planned_artifact_dao->method('isArtifactPlannedInAMilestoneOfTheProject')->willReturn(true);

        $this->processAction(
            $request,
        );
    }

    public function testItAddsArtifactsFromBacklogIfMasschangeActionIsAdd(): void
    {
        $request = new Codendi_Request(['masschange-action-explicit-backlog' => 'add']);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeItemsFromExplicitBacklogOfProject');
        $this->unplanned_artifacts_adder->method('addArtifactToTopBacklogFromIds')->withConsecutive([125, 101], [144, 101]);

        $this->processAction(
            $request,
        );
    }

    public function testItDoesNothingIfMasschangeActionIsNotKnown(): void
    {
        $request = new Codendi_Request(['masschange-action-explicit-backlog' => 'whatever']);

        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeItemsFromExplicitBacklogOfProject');
        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');
        $this->planned_artifact_dao->method('isArtifactPlannedInAMilestoneOfTheProject')->willReturn(false);

        $this->processAction(
            $request,
        );
    }
}
