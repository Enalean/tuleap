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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_NoMilestone;
use Project;

final class NoMilestoneTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $project;
    private $planning;

    /**
     * @var Planning_NoMilestone
     */
    private $milestone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn(123);

        $this->planning = Mockery::mock(Planning::class);
        $this->planning->shouldReceive('getId')->andReturn(9999);

        $this->milestone = new Planning_NoMilestone(
            $this->project,
            $this->planning
        );
    }

    public function testItHasAPlanning()
    {
        $this->assertSame($this->planning, $this->milestone->getPlanning());
        $this->assertSame($this->planning->getId(), $this->milestone->getPlanningId());
    }

    public function testItHasAProject()
    {
        $this->assertSame($this->project, $this->milestone->getProject());
        $this->assertSame($this->project->getID(), $this->milestone->getGroupId());
    }

    public function testItMayBeNull()
    {
        $this->assertNull($this->milestone->getArtifact());
        $this->assertNull($this->milestone->getArtifactId());
        $this->assertNull($this->milestone->getArtifactTitle());
        $this->assertTrue(
            $this->milestone->userCanView(Mockery::mock(PFUser::class)),
            "any user should be able to read an empty milstone"
        );
    }
}
