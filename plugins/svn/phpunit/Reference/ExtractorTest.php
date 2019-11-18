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

use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\SVN\Repository\Exception\CannotFindRepositoryException;
use Tuleap\SVN\Repository\RepositoryManager;

class ExtractorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Extractor
     */
    private $extractor;

    /**
     * @var Project
     */
    private $project;

    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = Mockery::spy(\Project::class);
        $this->project->shouldReceive('getID')->andReturn(101);
        $this->repository_manager = Mockery::spy(\Tuleap\SVN\Repository\RepositoryManager::class);
        $this->extractor          = new Extractor($this->repository_manager);
    }

    public function testItReturnsFalseIfReferenceDoesNotProvideRepositoryName(): void
    {
        $keyword = 'svn';
        $value   = '1';

        $this->project->shouldReceive('usesService')->withArgs(['plugin_svn'])->andReturn(true);

        $this->assertFalse($this->extractor->getReference($this->project, $keyword, $value));
    }

    public function testItReturnsFalseIfTheProjectDoesNotUseTheSubversionPlugin(): void
    {
        $keyword = 'svn';
        $value   = 'repo01/1';

        $this->project->shouldReceive('usesService')->withArgs(['plugin_svn'])->andReturn(false);

        $this->assertFalse($this->extractor->getReference($this->project, $keyword, $value));
    }

    public function testItReturnsFalseIfTheProvidedRepositoryIsNotInTheCurrentProject(): void
    {
        $keyword = 'svn';
        $value   = 'repo02/1';

        $this->project->shouldReceive('usesService')->withArgs(['plugin_svn'])->andReturn(true);
        $this->repository_manager->shouldReceive('getRepositoryByName')
            ->withArgs([$this->project, 'repo02'])
            ->andThrow(CannotFindRepositoryException::class);

        $this->assertFalse($this->extractor->getReference($this->project, $keyword, $value));
    }

    public function testItBuildsASubversionPluginReference(): void
    {
        $keyword    = 'svn';
        $value      = 'repo01/1';
        $repository = Mockery::mock(\Tuleap\SVN\Repository\Repository::class);
        $repository->shouldReceive('getFullName')->andReturn('project01/repo01');

        $this->project->shouldReceive('usesService')->withArgs(['plugin_svn'])->andReturn(true);
        $this->repository_manager->shouldReceive('getRepositoryByName')
            ->withArgs([$this->project, 'repo01'])
            ->andReturn($repository);

        $reference = $this->extractor->getReference($this->project, $keyword, $value);

        assert($reference instanceof Reference);

        $this->assertEquals($reference->getGroupId(), 101);
        $this->assertEquals($reference->getKeyword(), 'svn');
    }
}
