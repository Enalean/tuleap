<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\SVN\Reference;

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\SVN\Repository\CoreRepository;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\RepositoryManager;

final class ExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Extractor $extractor;
    private Project&MockObject $project;
    private RepositoryManager&MockObject $repository_manager;

    protected function setUp(): void
    {
        $this->project = $this->createMock(\Project::class);
        $this->project->method('getID')->willReturn(101);
        $this->project->method('getUnixNameMixedCase')->willReturn('FooBar');
        $this->repository_manager = $this->createMock(\Tuleap\SVN\Repository\RepositoryManager::class);
        $this->extractor          = new Extractor($this->repository_manager);
    }

    public function testItReturnsNullIfReferenceDoesNotProvideRepositoryName(): void
    {
        $keyword = 'svn';
        $value   = '1';

        $this->project->method('usesService')->with('plugin_svn')->willReturn(true);
        $this->repository_manager->method('getCoreRepository')->willThrowException(new CannotFindRepositoryException());

        self::assertNull($this->extractor->getReference($this->project, $keyword, $value));
    }

    public function testItReturnsTrueIfReferenceCorrespondsToACoreRepositoryManagedByPlugin(): void
    {
        $keyword = 'svn';
        $value   = '1';

        $this->project->method('usesService')->with('plugin_svn')->willReturn(true);
        $this->repository_manager->method('getCoreRepository')->with($this->project)->willReturn(CoreRepository::buildActiveRepository($this->project, 93));

        $reference = $this->extractor->getReference($this->project, $keyword, $value);
        self::assertInstanceOf(Reference::class, $reference);
    }

    public function testItReturnsNullIfTheProjectDoesNotUseTheSubversionPlugin(): void
    {
        $keyword = 'svn';
        $value   = 'repo01/1';

        $this->project->method('usesService')->with('plugin_svn')->willReturn(false);

        self::assertNull($this->extractor->getReference($this->project, $keyword, $value));
    }

    public function testItReturnsNullIfTheProvidedRepositoryIsNotInTheCurrentProject(): void
    {
        $keyword = 'svn';
        $value   = 'repo02/1';

        $this->project->method('usesService')->with('plugin_svn')->willReturn(true);
        $this->repository_manager->method('getRepositoryByName')
            ->with($this->project, 'repo02')
            ->willThrowException(new CannotFindRepositoryException());

        self::assertNull($this->extractor->getReference($this->project, $keyword, $value));
    }

    public function testItBuildsASubversionPluginReference(): void
    {
        $keyword    = 'svn';
        $value      = 'repo01/1';
        $repository = $this->createMock(\Tuleap\SVN\Repository\Repository::class);
        $repository->method('getFullName')->willReturn('project01/repo01');

        $this->project->method('usesService')->with('plugin_svn')->willReturn(true);
        $this->repository_manager->method('getRepositoryByName')
            ->with($this->project, 'repo01')
            ->willReturn($repository);

        $reference = $this->extractor->getReference($this->project, $keyword, $value);

        self::assertInstanceOf(Reference::class, $reference);

        self::assertEquals(101, $reference->getGroupId());
        self::assertEquals('svn', $reference->getKeyword());
    }
}
