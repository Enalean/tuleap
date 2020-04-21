<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedCriterionOptionsProvider;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedReportCriterionChecker;
use Tuleap\GlobalLanguageMock;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MilestoneReportCriterionProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var AgileDashboard_Milestone_MilestoneReportCriterionProvider
     */
    private $provider;

    /**
     * @var AgileDashboard_Milestone_SelectedMilestoneProvider|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $milestone_id_provider;

    /**
     * @var AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $options_provider;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UnplannedCriterionOptionsProvider
     */
    private $uplanned_criterion_provider;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UnplannedReportCriterionChecker
     */
    private $uplanned_criterion_checker;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $task_tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->task_tracker                = Mockery::mock(Tracker::class);
        $this->options_provider            = Mockery::mock(\AgileDashboard_Milestone_MilestoneReportCriterionOptionsProvider::class);
        $this->milestone_id_provider       = Mockery::mock(\AgileDashboard_Milestone_SelectedMilestoneProvider::class);
        $this->uplanned_criterion_provider = Mockery::mock(UnplannedCriterionOptionsProvider::class);
        $this->uplanned_criterion_checker  = Mockery::mock(UnplannedReportCriterionChecker::class);

        $this->user = Mockery::mock(PFUser::class);

        $this->provider = new AgileDashboard_Milestone_MilestoneReportCriterionProvider(
            $this->milestone_id_provider,
            $this->options_provider,
            $this->uplanned_criterion_provider,
            $this->uplanned_criterion_checker
        );

        $this->task_tracker->shouldReceive('getProject')->andReturn(Mockery::mock(Project::class));
    }

    public function testItReturnsNullWhenNoOptions(): void
    {
        $this->milestone_id_provider->shouldReceive('getMilestoneId')->andReturns('104');

        $this->options_provider->shouldReceive('getSelectboxOptions')
            ->with($this->task_tracker, Mockery::any(), $this->user)
            ->andReturns(array());

        $this->uplanned_criterion_checker->shouldReceive('isUnplannedValueSelected')
            ->andReturnFalse();

        $this->assertNull($this->provider->getCriterion($this->task_tracker, $this->user));
    }

    public function testItReturnsASelectBox(): void
    {
        $this->milestone_id_provider->shouldReceive('getMilestoneId')->andReturns('104');

        $this->options_provider->shouldReceive('getSelectboxOptions')
            ->with($this->task_tracker, Mockery::any(), $this->user)
            ->andReturns(array('<option>1','<option>2'));

        $this->uplanned_criterion_checker->shouldReceive('isUnplannedValueSelected')
            ->andReturnFalse();

        $this->uplanned_criterion_provider->shouldReceive('formatUnplannedAsSelectboxOption')
            ->once()
            ->andReturn('');

        $this->assertMatchesRegularExpression('/<select name="additional_criteria\[agiledashboard_milestone\]"/', $this->provider->getCriterion($this->task_tracker, $this->user));
    }

    public function testItSelectsTheGivenMilestone(): void
    {
        $this->milestone_id_provider->shouldReceive('getMilestoneId')->andReturns('104');

        $this->uplanned_criterion_checker->shouldReceive('isUnplannedValueSelected')->andReturnFalse();

        $this->options_provider->shouldReceive('getSelectboxOptions')->with($this->task_tracker, '104', $this->user)->once();

        $this->provider->getCriterion($this->task_tracker, $this->user);
    }

    public function testItSelectsUnplannedOption(): void
    {
        $this->milestone_id_provider->shouldReceive('getMilestoneId')->never();

        $this->uplanned_criterion_checker->shouldReceive('isUnplannedValueSelected')->andReturnTrue();

        $this->options_provider->shouldReceive('getSelectboxOptions')->with($this->task_tracker, '-1', $this->user)->once();

        $this->provider->getCriterion($this->task_tracker, $this->user);
    }
}
