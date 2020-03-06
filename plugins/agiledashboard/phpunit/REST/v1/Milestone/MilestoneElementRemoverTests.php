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

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\REST\v1\BacklogRemoveRepresentation;

final class MilestoneElementRemoverTests extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var MilestoneElementRemover
     */
    private $remover;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var BacklogRemoveRepresentation
     */
    private $backlog_remove_representation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getID')->andReturn('101');

        $this->explicit_backlog_dao              = Mockery::mock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = Mockery::mock(ArtifactsInExplicitBacklogDao::class);

        $this->remover = new MilestoneElementRemover(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao
        );

        $this->backlog_remove_representation = new BacklogRemoveRepresentation();
        $this->backlog_remove_representation->id = 124;
    }

    public function testItThrowsAnExceptionIfRemoveIsCalledIntoClassicBacklogContext(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnFalse();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('removeItemsFromExplicitBacklogOfProject')
            ->never();

        $this->expectException(RemoveNotAvailableInClassicBacklogModeException::class);

        $this->remover->removeElementsFromBacklog(
            $this->project,
            [
                $this->backlog_remove_representation
            ]
        );
    }

    public function testItThrowsAnExceptionIfAtLeastOneRemovedIdIsNotInExplicitBacklog(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('isArtifactInTopBacklogOfProject')
            ->once()
            ->with(124, 101)
            ->andReturnFalse();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('removeItemsFromExplicitBacklogOfProject')
            ->never();

        $this->expectException(ProvidedRemoveIdIsNotInExplicitBacklogException::class);

        $this->remover->removeElementsFromBacklog(
            $this->project,
            [
                $this->backlog_remove_representation
            ]
        );
    }

    public function testItRemovesItemsFromExplicitBacklog(): void
    {
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->with(101)
            ->andReturnTrue();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('isArtifactInTopBacklogOfProject')
            ->once()
            ->with(124, 101)
            ->andReturnTrue();

        $this->artifacts_in_explicit_backlog_dao->shouldReceive('removeItemsFromExplicitBacklogOfProject')
            ->once()
            ->with(101, [124]);

        $this->remover->removeElementsFromBacklog(
            $this->project,
            [
                $this->backlog_remove_representation
            ]
        );
    }
}
