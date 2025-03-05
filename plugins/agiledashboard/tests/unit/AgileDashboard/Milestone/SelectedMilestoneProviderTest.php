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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_MilestoneReportCriterionProvider;
use AgileDashboard_Milestone_SelectedMilestoneProvider;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactMilestone;
use Planning_MilestoneFactory;
use Project;
use Tracker_Report_AdditionalCriterion;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SelectedMilestoneProviderTest extends TestCase
{
    public const FIELD_NAME = AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME;
    public const ANY        = AgileDashboard_Milestone_MilestoneReportCriterionProvider::ANY;

    private Planning_MilestoneFactory&MockObject $milestone_factory;
    private Project $project;
    private PFUser $user;

    protected function setUp(): void
    {
        $artifact_id   = 123;
        $this->user    = UserTestBuilder::buildWithDefaults();
        $this->project = ProjectTestBuilder::aProject()->build();
        $milestone     = new Planning_ArtifactMilestone(
            $this->project,
            PlanningBuilder::aPlanning((int) $this->project->getID())->build(),
            ArtifactTestBuilder::anArtifact($artifact_id)->build(),
        );

        $this->milestone_factory = $this->createMock(Planning_MilestoneFactory::class);
        $this->milestone_factory->method('getBareMilestoneByArtifactId')
            ->with($this->user, $artifact_id)
            ->willReturn($milestone);
    }

    public function testItReturnsTheIdOfTheMilestone(): void
    {
        $additional_criteria = [self::FIELD_NAME => new Tracker_Report_AdditionalCriterion(self::FIELD_NAME, 123)];

        $provider = new AgileDashboard_Milestone_SelectedMilestoneProvider(
            $additional_criteria,
            $this->milestone_factory,
            $this->user,
            $this->project
        );

        self::assertEquals(123, $provider->getMilestoneId());
    }

    public function testItReturnsAnyWhenNoCriterion(): void
    {
        $additional_criteria = [];

        $provider = new AgileDashboard_Milestone_SelectedMilestoneProvider(
            $additional_criteria,
            $this->milestone_factory,
            $this->user,
            $this->project
        );

        self::assertEquals(
            AgileDashboard_Milestone_MilestoneReportCriterionProvider::ANY,
            $provider->getMilestoneId()
        );
    }
}
