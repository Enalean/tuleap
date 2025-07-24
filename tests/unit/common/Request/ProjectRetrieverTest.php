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

use Project;
use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var ProjectRetriever */
    private $project_retriever;
    private ProjectManager|\PHPUnit\Framework\MockObject\MockObject $project_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->project_manager   = $this->createMock(ProjectManager::class);
        $this->project_retriever = new ProjectRetriever($this->project_manager);
    }

    public function testGetProjectFromIdThrowsWhenNoProjectFound(): void
    {
        $invalid_id = '0';
        $this->project_manager
            ->method('getProject')
            ->with($invalid_id)
            ->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->project_retriever->getProjectFromId($invalid_id);
    }

    public function testGetProjectFromIdThrowsWhenProjectIsInError(): void
    {
        $project_id    = '104';
        $error_project = $this->createMock(Project::class);
        $error_project->method('isError')
            ->willReturn(true);
        $this->project_manager
            ->method('getProject')
            ->with($project_id)
            ->willReturn($error_project);

        $this->expectException(NotFoundException::class);
        $this->project_retriever->getProjectFromId($project_id);
    }

    public function testGetProjectFromIdReturnsValidProject(): void
    {
        $project_id = '104';
        $project    = $this->createMock(Project::class);
        $project->method('isError')
            ->willReturn(false);
        $this->project_manager
            ->method('getProject')
            ->with($project_id)
            ->willReturn($project);

        $result = $this->project_retriever->getProjectFromId($project_id);
        self::assertSame($project, $result);
    }

    public function testGetProjectFromNameThrowsWhenNoProjectFound(): void
    {
        $notfound = 'notfound';
        $this->project_manager
            ->method('getValidProjectByShortNameOrId')
            ->with($notfound)
            ->willThrowException(new \Project_NotFoundException());

        $this->expectException(NotFoundException::class);
        $this->project_retriever->getProjectFromName($notfound);
    }

    public function testGetProjectFromNameReturnsValidProject(): void
    {
        $name    = 'acme';
        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->method('getValidProjectByShortNameOrId')
            ->with($name)
            ->willReturn($project);

        self::assertSame($project, $this->project_retriever->getProjectFromName($name));
    }
}
