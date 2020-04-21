<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Request;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;

final class ProjectRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ProjectRetriever */
    private $project_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|ProjectManager */
    private $project_manager;

    protected function setUp(): void
    {
        $this->project_manager   = M::mock(ProjectManager::class);
        $this->project_retriever = new ProjectRetriever($this->project_manager);
    }

    public function testGetProjectFromIdThrowsWhenNoProjectFound(): void
    {
        $invalid_id = '0';
        $this->project_manager->shouldReceive('getProject')
            ->with($invalid_id)
            ->once()
            ->andReturnNull();

        $this->expectException(NotFoundException::class);
        $this->project_retriever->getProjectFromId($invalid_id);
    }

    public function testGetProjectFromIdThrowsWhenProjectIsInError(): void
    {
        $project_id    = '104';
        $error_project = M::mock(Project::class)->shouldReceive('isError')
            ->once()
            ->andReturnTrue()
            ->getMock();
        $this->project_manager->shouldReceive('getProject')
            ->with($project_id)
            ->once()
            ->andReturn($error_project);

        $this->expectException(NotFoundException::class);
        $this->project_retriever->getProjectFromId($project_id);
    }

    public function testGetProjectFromIdReturnsValidProject(): void
    {
        $project_id = '104';
        $project = M::mock(Project::class)->shouldReceive('isError')
            ->once()
            ->andReturnFalse()
            ->getMock();
        $this->project_manager->shouldReceive('getProject')
            ->with($project_id)
            ->once()
            ->andReturn($project);

        $result = $this->project_retriever->getProjectFromId($project_id);
        $this->assertSame($project, $result);
    }
}
