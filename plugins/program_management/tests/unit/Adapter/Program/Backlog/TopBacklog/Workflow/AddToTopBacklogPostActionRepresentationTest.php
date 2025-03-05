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

use Tuleap\ProgramManagement\Adapter\Workspace\ProgramServiceIsEnabledCertifier;
use Tuleap\ProgramManagement\Tests\Stub\Program\Backlog\TopBacklog\TopBacklogChangeProcessorStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddToTopBacklogPostActionRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildsRepresentationFromPostAction(): void
    {
        $post_action = new AddToTopBacklogPostAction(
            $this->createMock(\Transition::class),
            147,
            new ProgramServiceIsEnabledCertifier(),
            TopBacklogChangeProcessorStub::withCallCount()
        );

        $representation = AddToTopBacklogPostActionRepresentation::buildFromPostAction($post_action);
        self::assertSame(147, $representation->id);
        self::assertSame('program_management_add_to_top_backlog', $representation->type);
    }
}
