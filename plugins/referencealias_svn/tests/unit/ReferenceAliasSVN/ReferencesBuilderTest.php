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

use Project;
use Project_NotFoundException;
use ProjectManager;
use Reference;
use Tuleap\SVN\Repository\Repository;
use Tuleap\SVN\Repository\RepositoryManager;

class ReferencesBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReferencesBuilder $builder;

    /**
     * @var Dao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    /**
     * @var ProjectManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project_manager;
    /**
     * @var RepositoryManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao                = $this->createMock(Dao::class);
        $this->project_manager    = $this->createMock(ProjectManager::class);
        $this->repository_manager = $this->createMock(RepositoryManager::class);

        $this->builder = new ReferencesBuilder(
            $this->dao,
            $this->project_manager,
            $this->repository_manager
        );
    }

    public function testItRetrievesAReference(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('cmmt123')
            ->willReturn([
                'project_id' => 101,
                'source' => 'cmmt123',
                'repository_id' => 1,
                'revision_id' => 14,
            ]);

        $project = Project::buildForTest();
        $this->project_manager->expects(self::once())
            ->method('getValidProject')
            ->with(101)
            ->willReturn($project);

        $repository = $this->createMock(Repository::class);
        $repository->method('getFullName')->willReturn('RepoSVN01');

        $this->repository_manager->expects(self::once())
            ->method('getByIdAndProject')
            ->with(1, $project)
            ->willReturn($repository);

        $reference = $this->builder->getReference(
            'cmmt',
            123
        );

        self::assertInstanceOf(Reference::class, $reference);

        self::assertSame('plugin_svn', $reference->getServiceShortName());
        self::assertSame("/plugins/svn?roottype=svn&view=rev&root=RepoSVN01&revision=14", $reference->getLink());
    }

    public function testItReturnsNullIfKeywordIsNotKnown(): void
    {
        self::assertNull(
            $this->builder->getReference(
                'whatever',
                123
            )
        );
    }

    public function testItReturnsNullIfNoEntryFoundInDB(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('cmmt123')
            ->willReturn([]);

        self::assertNull(
            $this->builder->getReference(
                'cmmt',
                123
            )
        );
    }

    public function testItReturnsNullIfProjectNotFound(): void
    {
        $this->dao->expects(self::once())
            ->method('getRef')
            ->with('cmmt123')
            ->willReturn([
                'project_id' => 101,
                'source' => 'cmmt123',
                'repository_id' => 1,
                'revision_id' => 14,
            ]);

        $this->project_manager->expects(self::once())
            ->method('getValidProject')
            ->with(101)
            ->willThrowException(
                new Project_NotFoundException()
            );

        self::assertNull(
            $this->builder->getReference(
                'cmmt',
                123
            )
        );
    }
}
