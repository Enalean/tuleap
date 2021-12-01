<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ReferenceAliasSVN;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Project_NotFoundException;
use ProjectManager;
use Reference;
use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Repository\RepositoryManager;

class ReferencesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ReferencesBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Dao
     */
    private $dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RepositoryManager
     */
    private $repository_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                = Mockery::mock(Dao::class);
        $this->project_manager    = Mockery::mock(ProjectManager::class);
        $this->repository_manager = Mockery::mock(RepositoryManager::class);

        $this->builder = new ReferencesBuilder(
            $this->dao,
            $this->project_manager,
            $this->repository_manager
        );
    }

    public function testItRetrievesAReference(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('cmmt123')
            ->andReturn([
                'project_id' => 101,
                'source' => 'cmmt123',
                'repository_id' => 1,
                'revision_id' => 14,
            ]);

        $project = Project::buildForTest();
        $this->project_manager->shouldReceive('getValidProject')
            ->once()
            ->with(101)
            ->andReturn($project);

        $repository = Mockery::mock(Repository::class);
        $repository->shouldReceive('getFullName')->andReturn('RepoSVN01');

        $this->repository_manager->shouldReceive('getByIdAndProject')
            ->once()
            ->with(1, $project)
            ->andReturn($repository);

        $reference = $this->builder->getReference(
            'cmmt',
            123
        );

        $this->assertInstanceOf(Reference::class, $reference);

        $this->assertSame('plugin_svn', $reference->getServiceShortName());
        $this->assertSame("/plugins/svn?roottype=svn&view=rev&root=RepoSVN01&revision=14", $reference->getLink());
    }

    public function testItReturnsNullIfKeywordIsNotKnown(): void
    {
        $this->assertNull(
            $this->builder->getReference(
                'whatever',
                123
            )
        );
    }

    public function testItReturnsNullIfNoEntryFoundInDB(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('cmmt123')
            ->andReturn([]);

        $this->assertNull(
            $this->builder->getReference(
                'cmmt',
                123
            )
        );
    }

    public function testItReturnsNullIfProjectNotFound(): void
    {
        $this->dao->shouldReceive('getRef')
            ->once()
            ->with('cmmt123')
            ->andReturn([
                'project_id' => 101,
                'source' => 'cmmt123',
                'repository_id' => 1,
                'revision_id' => 14,
            ]);

        $this->project_manager->shouldReceive('getValidProject')
            ->once()
            ->with(101)
            ->andThrow(
                new Project_NotFoundException()
            );

        $this->assertNull(
            $this->builder->getReference(
                'cmmt',
                123
            )
        );
    }
}
