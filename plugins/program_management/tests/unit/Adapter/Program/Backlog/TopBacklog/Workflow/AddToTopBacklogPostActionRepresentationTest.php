<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogChangeProcessor;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;

final class AddToTopBacklogPostActionRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuildsRepresentationFromPostAction(): void
    {
        $post_action = new AddToTopBacklogPostAction(
            \Mockery::mock(\Transition::class),
            147,
            \Mockery::mock(BuildProgram::class),
            \Mockery::mock(TopBacklogChangeProcessor::class)
        );

        $representation = AddToTopBacklogPostActionRepresentation::buildFromPostAction($post_action);
        self::assertEquals(147, $representation->id);
        self::assertEquals('program_management_add_to_top_backlog', $representation->type);
    }
}
