<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Codendi_Request;
use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Planning_MilestoneSelectorController;
use Planning_NoMilestone;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MilestoneSelectorControllerTest extends TestCase
{
    use GlobalResponseMock;

    private int $current_milestone_artifact_id;
    private Planning_MilestoneFactory&MockObject $milestone_factory;
    private Codendi_Request $request;

    #[\Override]
    protected function setUp(): void
    {
        $planning_id   = '321';
        $user          = UserTestBuilder::buildWithDefaults();
        $this->request = new Codendi_Request(['planning_id' => $planning_id]);
        $this->request->setCurrentUser($user);
        $this->milestone_factory = $this->createMock(Planning_MilestoneFactory::class);

        $this->current_milestone_artifact_id = 444;

        $artifact  = ArtifactTestBuilder::anArtifact($this->current_milestone_artifact_id)->build();
        $milestone = $this->createMock(Planning_ArtifactMilestone::class);
        $milestone->method('getArtifact')->willReturn($artifact);
        $milestone->method('getGroupId')->willReturn(101);
        $milestone->method('getPlanningId')->willReturn($planning_id);

        $this->milestone_factory->method('getLastMilestoneCreated')
            ->with($user, $planning_id)->willReturn($milestone);
    }

    #[\Override]
    protected function tearDown(): void
    {
        EventManager::clearInstance();
    }

    public function testItRedirectToTheCurrentMilestone(): void
    {
        $GLOBALS['Response']->expects($this->once())->method('redirect')
            ->with(self::matchesRegularExpression("/aid=$this->current_milestone_artifact_id/"));
        $controller = new Planning_MilestoneSelectorController($this->request, $this->milestone_factory);
        $controller->show();
    }

    public function testItRedirectToTheCurrentMilestoneCardwallIfAny(): void
    {
        $event_manager = $this->createMock(EventManager::class);
        EventManager::setInstance($event_manager);

        $event_manager->expects($this->atLeastOnce())->method('processEvent')->with(
            Planning_MilestoneSelectorController::AGILEDASHBOARD_EVENT_MILESTONE_SELECTOR_REDIRECT,
            self::anything()
        );

        $controller = new Planning_MilestoneSelectorController($this->request, $this->milestone_factory);
        $controller->show();
    }

    public function testItDoesntRedirectIfNoMilestone(): void
    {
        $milestone_factory = $this->createMock(Planning_MilestoneFactory::class);
        $milestone_factory->method('getLastMilestoneCreated')->willReturn(new Planning_NoMilestone(
            ProjectTestBuilder::aProject()->build(),
            PlanningBuilder::aPlanning(1)->build(),
        ));

        $GLOBALS['Response']->expects($this->never())->method('redirect');
        $controller = new Planning_MilestoneSelectorController($this->request, $milestone_factory);
        $controller->show();
    }
}
