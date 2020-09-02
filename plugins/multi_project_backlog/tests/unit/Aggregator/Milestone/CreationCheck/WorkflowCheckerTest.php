<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\CreationCheck;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\MilestoneTrackerCollection;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldCollection;

final class WorkflowCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Workflow_Dao
     */
    private $workflow_dao;
    /**
     * @var WorkflowChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->workflow_dao = \Mockery::mock(\Workflow_Dao::class);
        $this->checker      = new WorkflowChecker($this->workflow_dao, new NullLogger());
    }

    public function testConsiderTrackerContributorsAreValidWhenAllRulesAreVerified(): void
    {
        $this->workflow_dao->shouldReceive('searchWorkflowsByFieldIDsAndTrackerIDs')->andReturn([]);

        $this->assertTrue(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInContributorTrackers(
                new MilestoneTrackerCollection(\Project::buildForTest(), []),
                new SynchronizedFieldCollection([])
            )
        );
    }

    public function testRejectsWhenSomeWorkflowTransitionRulesAreDefinedWithASynchronizedField(): void
    {
        $this->workflow_dao->shouldReceive('searchWorkflowsByFieldIDsAndTrackerIDs')->andReturn(
            [['tracker_id' => 758, 'field_id' => 963]]
        );

        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(758);
        $tracker->shouldReceive('getGroupId')->andReturn('147');
        $field = \Mockery::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getId')->andReturn('963');

        $this->assertFalse(
            $this->checker->areWorkflowsNotUsedWithSynchronizedFieldsInContributorTrackers(
                new MilestoneTrackerCollection(\Project::buildForTest(), [$tracker]),
                new SynchronizedFieldCollection([$field])
            )
        );
    }
}
