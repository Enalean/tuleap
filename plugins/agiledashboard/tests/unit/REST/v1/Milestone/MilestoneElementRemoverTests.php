<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\REST\v1\BacklogRemoveRepresentation;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class MilestoneElementRemoverTests extends TestCase
{
    private Project $project;
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private MilestoneElementRemover $remover;
    private ArtifactsInExplicitBacklogDao&MockObject $artifacts_in_explicit_backlog_dao;
    private BacklogRemoveRepresentation $backlog_remove_representation;

    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->explicit_backlog_dao              = $this->createMock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->artifact_factory                  = $this->createMock(Tracker_ArtifactFactory::class);

        $this->remover = new MilestoneElementRemover(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao,
            $this->artifact_factory
        );

        $this->backlog_remove_representation     = new BacklogRemoveRepresentation();
        $this->backlog_remove_representation->id = 124;
    }

    public function testItThrowsAnExceptionIfRemoveIsCalledIntoClassicBacklogContext(): void
    {
        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeItemsFromExplicitBacklogOfProject');

        self::expectException(RemoveNotAvailableInClassicBacklogModeException::class);

        $this->remover->removeElementsFromBacklog(
            $this->project,
            UserTestBuilder::aUser()->build(),
            [$this->backlog_remove_representation]
        );
    }

    public function testItThrowsAnExceptionIfAtLeastOneRemovedIdIsNotInExplicitBacklog(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(ArtifactTestBuilder::anArtifact(1)->build());
        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects($this->once())->method('isArtifactInTopBacklogOfProject')
            ->with(124, 101)
            ->willReturn(false);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('removeItemsFromExplicitBacklogOfProject');

        self::expectException(ProvidedRemoveIdIsNotInExplicitBacklogException::class);

        $this->remover->removeElementsFromBacklog(
            $this->project,
            UserTestBuilder::aUser()->build(),
            [$this->backlog_remove_representation]
        );
    }

    public function testItRemovesItemsFromExplicitBacklog(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(ArtifactTestBuilder::anArtifact(1)->build());
        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects($this->once())->method('isArtifactInTopBacklogOfProject')
            ->with(124, 101)
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects($this->once())->method('removeItemsFromExplicitBacklogOfProject')
            ->with(101, [124]);

        $this->remover->removeElementsFromBacklog(
            $this->project,
            UserTestBuilder::aUser()->build(),
            [$this->backlog_remove_representation]
        );
    }

    public function testDoesNotRemoveFromExplicitBacklogItemsTheUserCannotSee(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(null);
        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('isArtifactInTopBacklogOfProject');

        $this->artifacts_in_explicit_backlog_dao->expects($this->once())->method('removeItemsFromExplicitBacklogOfProject')
            ->with(101, []);

        $this->remover->removeElementsFromBacklog(
            $this->project,
            UserTestBuilder::aUser()->build(),
            [$this->backlog_remove_representation]
        );
    }
}
