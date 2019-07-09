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

namespace Tuleap\Project\UGroups;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Git\GitPHP\Project;

final class SynchronizedProjectMembershipDetectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var SynchronizedProjectMembershipDetector */
    private $detector;

    protected function setUp(): void
    {
        $this->detector = new SynchronizedProjectMembershipDetector();
    }

    public function testItReturnsTrueWhenTheUGroupsProjectIsPrivate(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('isPublic')->andReturnFalse();
        $ugroup = Mockery::mock(\ProjectUGroup::class);
        $ugroup->shouldReceive('getProject')->andReturn($project);

        $this->assertTrue($this->detector->isSynchronizedWithProjectMembers($ugroup));
    }

    public function testItReturnsFalseWhenTheUGroupsProjectIsPublic(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('isPublic')->andReturnTrue();
        $ugroup = Mockery::mock(\ProjectUGroup::class);
        $ugroup->shouldReceive('getProject')->andReturn($project);

        $this->assertFalse($this->detector->isSynchronizedWithProjectMembers($ugroup));
    }
}
