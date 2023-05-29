<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\ProjectOwnership\ProjectAdmin;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ProjectOwnership\ProjectOwner\ProjectOwnerRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class UpdateVisibilityCheckerTest extends TestCase
{
    private UpdateVisibilityChecker $checker;
    private MockObject&ProjectOwnerRetriever $project_owner_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_owner_retriever = $this->createMock(ProjectOwnerRetriever::class);
        $this->checker                 = new UpdateVisibilityChecker($this->project_owner_retriever);
    }

    public function testItAllowsProjectVisibilitySwitchIfThereIsNoOwnerInProject(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->project_owner_retriever->method('getProjectOwner')->with($project)->willReturn(null);

        self::assertTrue(
            $this->checker->canUpdateVisibilityRegardingRestrictedUsers($project)->canSwitch()
        );
    }

    public function testItAllowsProjectVisibilitySwitchIfOwnerInProjectIsNotRestricted(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->project_owner_retriever->method('getProjectOwner')->with($project)->willReturn(
            UserTestBuilder::anActiveUser()->build(),
        );

        self::assertTrue(
            $this->checker->canUpdateVisibilityRegardingRestrictedUsers($project)->canSwitch()
        );
    }

    public function testItDoesNotAllowProjectVisibilitySwitchIfOwnerInProjectIsRestricted(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->project_owner_retriever->method('getProjectOwner')->with($project)->willReturn(
            UserTestBuilder::aRestrictedUser()->build(),
        );

        self::assertFalse(
            $this->checker->canUpdateVisibilityRegardingRestrictedUsers($project)->canSwitch()
        );
    }
}
