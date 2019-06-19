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

namespace Tuleap\AgileDashboard\Planning;

require_once __DIR__ . '/../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_Milestone;
use Planning_MilestoneFactory;
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Integer;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;

final class MilestoneFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    private $project;
    private $planning;
    private $planning_factory;
    private $artifact_factory;
    private $formelement_factory;
    private $milestone_tracker_id;
    private $milestone_tracker;
    private $user;
    private $request;
    private $status_counter;
    private $artifact;
    private $milestone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project    = Mockery::spy(\Project::class);
        $this->planning_id = 34;
        $this->artifact_id = 56;

        $this->milestone_tracker_id = 112;
        $this->milestone_tracker    = Mockery::spy(\Tracker::class);

        $this->milestone_tracker->shouldReceive('getId')->andReturn($this->milestone_tracker_id);
        $this->milestone_tracker->shouldReceive('getProject')->andReturn($this->project);

        $this->user                         = Mockery::spy(\PFUser::class);
        $this->planning                     = Mockery::mock(Planning::class);
        $this->artifact                     = Mockery::spy(\Tracker_Artifact::class);
        $this->planning_factory             = Mockery::spy(\PlanningFactory::class);
        $this->artifact_factory             = Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->formelement_factory          = Mockery::spy(\Tracker_FormElementFactory::class);
        $this->tracker_factory              = Mockery::spy(\TrackerFactory::class);
        $this->status_counter               = Mockery::spy(\AgileDashboard_Milestone_MilestoneStatusCounter::class);
        $this->planning_permissions_manager = Mockery::spy(\PlanningPermissionsManager::class);
        $this->dao                          = Mockery::spy(\AgileDashboard_Milestone_MilestoneDao::class);
        $this->mono_milestone_checker       = Mockery::spy(ScrumForMonoMilestoneChecker::class);

        $this->planning->shouldReceive('getId')->andReturn($this->planning_id);

        $this->milestone_factory = new Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            $this->formelement_factory,
            $this->tracker_factory,
            $this->status_counter,
            $this->planning_permissions_manager,
            $this->dao,
            $this->mono_milestone_checker
        );

        $this->artifact->shouldReceive('getTracker')->andReturn($this->milestone_tracker);
        $this->artifact->shouldReceive('userCanView')->andReturn(true);
        $this->artifact->shouldReceive('getAllAncestors')->andReturn([]);

        $this->planning_factory->shouldReceive('getPlanning')->with($this->planning_id)->andReturn($this->planning);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with($this->artifact_id)
            ->andReturn($this->artifact);
    }

    private function getMilestone() : Planning_Milestone
    {
        return $this->milestone_factory->getBareMilestone(
            $this->user,
            $this->project,
            $this->planning_id,
            $this->artifact_id
        );
    }

    public function testStartDateIsZeroWhenThereIsNoStartDateField()
    {
        $this->assertSame(0, $this->getMilestone()->getStartDate());
    }

    public function testItRetrievesMilestoneWithStartDateWithActualValue()
    {
        $start_date = '12/10/2013';

        $start_date_changeset = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $start_date_changeset->shouldReceive('getTimestamp')->andReturn(strtotime($start_date));

        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($start_date_changeset);

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->milestone_tracker,
                $this->user,
                Planning_Milestone::START_DATE_FIELD_NAME
            )
            ->andReturn($start_date_field);

        $this->assertSame(strtotime($start_date), $this->getMilestone()->getStartDate());
    }

    public function testEndDateIsNullWhenThereIsNoStartDateOrDurationField()
    {
        $this->assertNull($this->getMilestone()->getEndDate());
    }

    public function testItRetrievesMilestoneWithEndDate()
    {
        // Sprint 10 days, from `Monday, Jul 1, 2013` to `Monday, Jul 15, 2013`
        $duration          = 10;
        $start_date        = '07/01/2013';
        $expected_end_date = '07/15/2013';

        $start_date_changeset = Mockery::mock(Tracker_Artifact_ChangesetValue_Date::class);
        $start_date_changeset->shouldReceive('getTimestamp')->andReturn(strtotime($start_date));

        $start_date_field = Mockery::mock(Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($start_date_changeset);

        $duration_field = Mockery::mock(Tracker_FormElement_Field_Integer::class);
        $duration_field->shouldReceive('getComputedValue')
            ->with($this->user, $this->artifact)
            ->andReturn($duration);

        $this->formelement_factory->shouldReceive('getDateFieldByNameForUser')
            ->with(
                $this->milestone_tracker,
                $this->user,
                Planning_Milestone::START_DATE_FIELD_NAME
            )
            ->andReturn($start_date_field);

        $this->formelement_factory->shouldReceive('getComputableFieldByNameForUser')
            ->with($this->milestone_tracker_id, Planning_Milestone::DURATION_FIELD_NAME, $this->user)
            ->andReturn($duration_field);

        $this->milestone_tracker->shouldReceive('hasFormElementWithNameAndType')->andReturn(true);

        $this->assertSame(strtotime($expected_end_date), $this->getMilestone()->getEndDate());
    }
}
