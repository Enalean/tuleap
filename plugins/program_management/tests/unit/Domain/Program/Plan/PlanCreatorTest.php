<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Stub\RetrieveTrackerStub;
use Tuleap\ProgramManagement\Stub\RetrieveProgramUserGroupStub;
use Tuleap\ProgramManagement\Stub\RetrieveProjectStub;
use Tuleap\ProgramManagement\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class PlanCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RetrieveTrackerStub $tracker_builder;
    private RetrieveProgramUserGroupStub $ugroup_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PlanStore
     */
    private $plan_store;
    private RetrieveProjectStub $project_retriever;
    private int $project_id;

    protected function setUp(): void
    {
        $this->project_id        = 102;
        $this->tracker_builder   = RetrieveTrackerStub::buildValidTrackerWithProjectId($this->project_id);
        $this->ugroup_retriever  = RetrieveProgramUserGroupStub::withValidUserGroups(4);
        $this->plan_store        = $this->createMock(PlanStore::class);
        $this->project_retriever = RetrieveProjectStub::withValidProjects(ProjectTestBuilder::aProject()->withId(102)->build());
    }

    public function testItCreatesAPlan(): void
    {
        $plannable_tracker_id = 2;

        $user = UserTestBuilder::aUser()->build();

        $this->plan_store = $this->createMock(PlanStore::class);
        $this->plan_store->expects(self::once())->method('save')->with(self::isInstanceOf(Plan::class));
        $plan_program_increment_change = new PlanProgramIncrementChange(1, 'Program Increments', 'program increment');
        $iteration_representation      = new PlanIterationChange(150, null, null);
        $plan_change                   = PlanChange::fromProgramIncrementAndRaw(
            $plan_program_increment_change,
            $user,
            $this->project_id,
            [$plannable_tracker_id],
            ['102_4'],
            $iteration_representation
        );

        $this->getCreator()->create($plan_change);
    }

    private function getCreator(): PlanCreator
    {
        return new PlanCreator(
            $this->tracker_builder,
            $this->ugroup_retriever,
            $this->plan_store,
            $this->project_retriever,
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProjectPermissionStub::withAdministrator()
        );
    }
}
