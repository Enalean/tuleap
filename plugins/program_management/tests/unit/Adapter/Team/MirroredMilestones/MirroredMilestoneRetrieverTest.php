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

namespace Tuleap\ProgramManagement\Adapter\Team\MirroredMilestones;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Domain\Team\MirroredMilestone\MirroredMilestone;

final class MirroredMilestoneRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsAListOfMirroredArtifact(): void
    {
        $dao       = \Mockery::mock(MirroredMilestonesDao::class);
        $retriever = new MirroredMilestoneRetriever($dao);

        $dao->shouldReceive('getMirroredMilestones')->andReturn([['id' => 1], ['id' => 2]]);

        $expected = [new MirroredMilestone(1), new MirroredMilestone(2)];

        self::assertEquals($expected, $retriever->retrieveMilestonesLinkedTo(101));
    }
}
