<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\BotMattermostAgileDashboard\SenderServices;

use AgileDashboard_Milestone_MilestoneStatusCounter;
use HTTPRequest;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_Milestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use Project;
use Tracker_Artifact;
use Tuleap\BotMattermost\SenderServices\MarkdownEngine\MarkdownMustacheRenderer;
use Tuleap\GlobalLanguageMock;

final class StandUpNotificationBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testNotificationCanBeBuiltWhenTheParentOfTheMilestoneArtifactCanNotBeFound(): void
    {
        $planning_milestone_factory = Mockery::mock(Planning_MilestoneFactory::class);
        $milestone_status_counter = Mockery::mock(AgileDashboard_Milestone_MilestoneStatusCounter::class);
        $planning_factory = Mockery::mock(PlanningFactory::class);
        $markdown_renderer = Mockery::mock(MarkdownMustacheRenderer::class);
        $notification_builder = new StandUpNotificationBuilder(
            $planning_milestone_factory,
            $milestone_status_counter,
            $planning_factory,
            $markdown_renderer
        );

        $planning = Mockery::mock(Planning::class);
        $planning->shouldReceive('getName')->andReturn('My planning name');
        $planning_factory->shouldReceive('getLastLevelPlannings')->andReturn([$planning]);
        $planning_milestone = Mockery::mock(Planning_Milestone::class);
        $planning_milestone_factory->shouldReceive('getAllCurrentMilestones')->andReturn([$planning_milestone]);
        $planning_milestone_factory->shouldReceive('updateMilestoneContextualInfo')->andReturn($planning_milestone);
        $planning_milestone->shouldReceive('getLinkedArtifacts')->andReturn([]);
        $planning_milestone->shouldReceive('getGroupId')->andReturn(102);
        $planning_milestone->shouldReceive('getPlanningId')->andReturn(741);
        $planning_milestone->shouldReceive('getArtifactId')->andReturn(852);
        $planning_milestone->shouldReceive('getArtifactTitle')->andReturn('My Release');
        $planning_milestone->shouldReceive('getStartDate')->andReturn(0);
        $planning_milestone->shouldReceive('getEndDate')->andReturn(1);
        $milestone_artifact = Mockery::mock(Tracker_Artifact::class);
        $planning_milestone->shouldReceive('getArtifact')->andReturn($milestone_artifact);
        $planning_milestone->shouldReceive('getDaysUntilEnd')->andReturn(0);
        $milestone_status_counter->shouldReceive('getStatus')->andReturn(['open' => 0, 'closed' => 0]);

        $milestone_artifact->shouldReceive('getParent')->andReturn(null); // Issue is here
        $milestone_artifact->shouldReceive('getABurndownField')->andReturn(null);
        $milestone_artifact->shouldReceive('getUri')->andReturn('/milestone_artifact_uri');
        $milestone_artifact->shouldReceive('getXRef')->andReturn('release #852');

        $rendered_text = 'rendered_markdown_notif';
        $markdown_renderer->shouldReceive('renderToString')->andReturn($rendered_text);

        $notification_text = $notification_builder->buildNotificationText(
            Mockery::spy(HTTPRequest::class),
            Mockery::spy(PFUser::class),
            Mockery::spy(Project::class)
        );
        $this->assertEquals($rendered_text, $notification_text);
    }
}
