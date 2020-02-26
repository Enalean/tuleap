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

class AgileDashboard_Milestone_SelectedMilestoneProviderTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    public const FIELD_NAME = AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME;
    public const ANY        = AgileDashboard_Milestone_MilestoneReportCriterionProvider::ANY;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_ArtifactMilestone
     */
    private $milestone;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $artifact_id    = 123;
        $this->milestone = Mockery::mock(Planning_ArtifactMilestone::class);
        $this->milestone->shouldReceive('getArtifactId')->andReturn($artifact_id);

        $this->user    = Mockery::mock(PFUser::class);
        $this->project = \Mockery::spy(\Project::class);

        $this->milestone_factory = \Mockery::spy(\Planning_MilestoneFactory::class)
            ->shouldReceive('getBareMilestoneByArtifactId')
            ->with($this->user, $artifact_id)
            ->andReturns($this->milestone)
            ->getMock();
    }

    public function testItReturnsTheIdOfTheMilestone(): void
    {
        $additional_criteria = [
            self::FIELD_NAME => new Tracker_Report_AdditionalCriterion(self::FIELD_NAME, 123)
        ];

        $provider = new AgileDashboard_Milestone_SelectedMilestoneProvider(
            $additional_criteria,
            $this->milestone_factory,
            $this->user,
            $this->project
        );

        $this->assertEquals(123, $provider->getMilestoneId());
    }

    public function testItReturnsAnyWhenNoCriterion(): void
    {
        $additional_criteria = array();

        $provider = new AgileDashboard_Milestone_SelectedMilestoneProvider(
            $additional_criteria,
            $this->milestone_factory,
            $this->user,
            $this->project
        );

        $this->assertEquals(
            AgileDashboard_Milestone_MilestoneReportCriterionProvider::ANY,
            $provider->getMilestoneId()
        );
    }
}
