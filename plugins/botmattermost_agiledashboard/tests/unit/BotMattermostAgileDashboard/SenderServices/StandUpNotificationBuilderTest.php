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
use ForgeConfig;
use PFUser;
use Planning;
use Planning_Milestone;
use Planning_MilestoneFactory;
use PlanningFactory;
use Project;
use Tuleap\BotMattermost\SenderServices\MarkdownEngine\MarkdownMustacheRenderer;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StandUpNotificationBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    protected function setUp(): void
    {
        ForgeConfig::set(ConfigurationVariables::SERVER_TIMEZONE, 'Europe/Paris');
    }

    public function testNotificationCanBeBuiltWhenTheParentOfTheMilestoneArtifactCanNotBeFound(): void
    {
        $planning_milestone_factory = $this->createMock(Planning_MilestoneFactory::class);
        $milestone_status_counter   = $this->createMock(AgileDashboard_Milestone_MilestoneStatusCounter::class);
        $planning_factory           = $this->createMock(PlanningFactory::class);
        $markdown_renderer          = $this->createMock(MarkdownMustacheRenderer::class);
        $notification_builder       = new StandUpNotificationBuilder(
            $planning_milestone_factory,
            $milestone_status_counter,
            $planning_factory,
            $markdown_renderer
        );

        $planning = $this->createMock(Planning::class);
        $planning->method('getName')->willReturn('My planning name');
        $planning_factory->method('getLastLevelPlannings')->willReturn([$planning]);
        $planning_milestone = $this->createMock(Planning_Milestone::class);
        $planning_milestone_factory->method('getAllCurrentMilestones')->willReturn([$planning_milestone]);
        $planning_milestone_factory->method('updateMilestoneContextualInfo')->willReturn($planning_milestone);
        $planning_milestone->method('getLinkedArtifacts')->willReturn([]);
        $planning_milestone->method('getGroupId')->willReturn(102);
        $planning_milestone->method('getPlanningId')->willReturn(741);
        $planning_milestone->method('getArtifactId')->willReturn(852);
        $planning_milestone->method('getArtifactTitle')->willReturn('My Release');
        $planning_milestone->method('getStartDate')->willReturn(0);
        $planning_milestone->method('getEndDate')->willReturn(1);
        $milestone_artifact = $this->createMock(Artifact::class);
        $planning_milestone->method('getArtifact')->willReturn($milestone_artifact);
        $planning_milestone->method('getDaysUntilEnd')->willReturn(0);
        $milestone_status_counter->method('getStatus')->willReturn(['open' => 0, 'closed' => 0]);

        $milestone_artifact->method('getParent')->willReturn(null); // Issue is here
        $milestone_artifact->method('getABurndownField')->willReturn(null);
        $milestone_artifact->method('getUri')->willReturn('/milestone_artifact_uri');
        $milestone_artifact->method('getXRef')->willReturn('release #852');

        $rendered_text = 'rendered_markdown_notif';
        $markdown_renderer->method('renderToString')->willReturn($rendered_text);

        $project = new Project([
            'group_id' => '101',
            'group_name' => 'test',
        ]);

        $notification_text = $notification_builder->buildNotificationText(
            $this->createMock(PFUser::class),
            $project
        );

        self::assertSame($rendered_text, $notification_text);
    }
}
