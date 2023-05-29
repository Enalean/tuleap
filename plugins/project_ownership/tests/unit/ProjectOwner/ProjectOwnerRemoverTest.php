<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\ProjectOwnership\ProjectOwner;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ProjectOwnerRemoverTest extends TestCase
{
    private ProjectOwnerRemover $remover;
    private MockObject&ProjectOwnerDAO $dao;
    private MockObject&ProjectOwnerRetriever $project_owner_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                     = $this->createMock(ProjectOwnerDAO::class);
        $this->project_owner_retriever = $this->createMock(ProjectOwnerRetriever::class);

        $this->remover = new ProjectOwnerRemover(
            $this->dao,
            $this->project_owner_retriever,
            new NullLogger(),
        );
    }

    public function testItDoesNothingIfProjectIsNotPrivateWithoutRestricted(): void
    {
        $public_project                 = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        $private_project                = ProjectTestBuilder::aProject()->withAccessPrivate()->build();
        $public_project_with_restricted = ProjectTestBuilder::aProject()->withAccessPublicIncludingRestricted()->build();

        $this->dao->expects(self::never())->method('delete');

        $this->remover->forceRemovalOfRestrictedProjectOwner(
            $public_project,
            UserTestBuilder::aRestrictedUser()->build(),
        );

        $this->remover->forceRemovalOfRestrictedProjectOwner(
            $private_project,
            UserTestBuilder::aRestrictedUser()->build(),
        );

        $this->remover->forceRemovalOfRestrictedProjectOwner(
            $public_project_with_restricted,
            UserTestBuilder::aRestrictedUser()->build(),
        );
    }

    public function testItDoesNothingIfUserIsNotRestricted(): void
    {
        $project = ProjectTestBuilder::aProject()->withAccessPrivateWithoutRestricted()->build();

        $this->dao->expects(self::never())->method('delete');

        $this->remover->forceRemovalOfRestrictedProjectOwner(
            $project,
            UserTestBuilder::anActiveUser()->build(),
        );
    }

    public function testItDoesNothingIfThereIsNoOwnerInProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withAccessPrivateWithoutRestricted()->build();

        $this->project_owner_retriever->method('getProjectOwner')->willReturn(null);
        $this->dao->expects(self::never())->method('delete');

        $this->remover->forceRemovalOfRestrictedProjectOwner(
            $project,
            UserTestBuilder::aRestrictedUser()->build(),
        );
    }

    public function testItDoesNothingIfTheRemovedUserIsNotTheOwnerInProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withAccessPrivateWithoutRestricted()->build();

        $this->project_owner_retriever->method('getProjectOwner')->willReturn(
            UserTestBuilder::anActiveUser()->withId(103)->build()
        );
        $this->dao->expects(self::never())->method('delete');

        $this->remover->forceRemovalOfRestrictedProjectOwner(
            $project,
            UserTestBuilder::aRestrictedUser()->withId(102)->build(),
        );
    }

    public function testItForcesTheRemovalOfTheOwnerInProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withAccessPrivateWithoutRestricted()->build();

        $this->project_owner_retriever->method('getProjectOwner')->willReturn(
            UserTestBuilder::anActiveUser()->withId(102)->build()
        );
        $this->dao->expects(self::once())->method('delete');

        $this->remover->forceRemovalOfRestrictedProjectOwner(
            $project,
            UserTestBuilder::aRestrictedUser()->withId(102)->build(),
        );
    }
}
