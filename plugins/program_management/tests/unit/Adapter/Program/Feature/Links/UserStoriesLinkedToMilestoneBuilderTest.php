<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Domain\Team\MirroredMilestone\MirroredMilestone;

final class UserStoriesLinkedToMilestoneBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsAnArrayOfUnplannedUserStories(): void
    {
        $dao     = \Mockery::mock(ArtifactsLinkedToParentDao::class);
        $builder = new UserStoriesLinkedToMilestoneBuilder($dao);

        $mirrored_milestone = new MirroredMilestone(1);

        $potential_us_to_unlink = [
            ["id" => 10, "release_tracker_id" => 1, "project_id" => 100],
            ["id" => 20, "release_tracker_id" => 2, "project_id" => 200],
            ["id" => 30, "release_tracker_id" => 3, "project_id" => 300],
        ];
        $dao->shouldReceive('getUserStoriesOfMirroredMilestone')->andReturn($potential_us_to_unlink);
        $dao->shouldReceive('isLinkedToASprintInMirroredMilestones')->with(10, 1, 100)->andReturnTrue();
        $dao->shouldReceive('isLinkedToASprintInMirroredMilestones')->with(20, 2, 200)->andReturnFalse();
        $dao->shouldReceive('isLinkedToASprintInMirroredMilestones')->with(30, 3, 300)->andReturnFalse();

        $expected = [20 => 20, 30 => 30];

        self::assertEquals($expected, $builder->build($mirrored_milestone));
    }
}
