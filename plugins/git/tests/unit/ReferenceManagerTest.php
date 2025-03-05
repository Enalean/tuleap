<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Git;

use Git;
use Git_ReferenceManager;
use GitRepository;
use GitRepositoryFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Reference;
use ReferenceManager;
use Tuleap\Git\Reference\ReferenceDao as ReferenceDaoAlias;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReferenceManagerTest extends TestCase
{
    private Project $project;
    private GitRepositoryFactory&MockObject $repository_factory;
    private ReferenceManager&MockObject $reference_manager;
    private GitRepository $repository;
    private Git_ReferenceManager $git_reference_manager;
    private ReferenceDaoAlias&MockObject $reference_dao;

    protected function setUp(): void
    {
        $this->project               = ProjectTestBuilder::aProject()->withId(101)->withUnixName('gpig')->build();
        $this->repository_factory    = $this->createMock(GitRepositoryFactory::class);
        $this->reference_manager     = $this->createMock(ReferenceManager::class);
        $this->reference_dao         = $this->createMock(ReferenceDaoAlias::class);
        $this->repository            = GitRepositoryTestBuilder::aProjectRepository()->withId(222)->build();
        $this->git_reference_manager = new Git_ReferenceManager(
            $this->repository_factory,
            $this->reference_manager,
            $this->reference_dao,
        );
    }

    public function testItLoadsTheRepositoryWhenRootRepo(): void
    {
        $this->repository_factory->expects(self::once())->method('getRepositoryByPath')->with(101, 'gpig/rantanplan.git');
        $this->git_reference_manager->getCommitReference($this->project, Git::REFERENCE_KEYWORD, 'rantanplan/469eaa9');
    }

    public function testItLoadsTheRepositoryWhenRepoInHierarchy(): void
    {
        $this->repository_factory->expects(self::once())->method('getRepositoryByPath')->with(101, 'gpig/dev/x86_64/rantanplan.git');
        $this->git_reference_manager->getCommitReference($this->project, Git::REFERENCE_KEYWORD, 'dev/x86_64/rantanplan/469eaa9');
    }

    public function testItLoadTheReferenceFromDb(): void
    {
        $this->repository_factory->method('getRepositoryByPath')->willReturn($this->repository);

        $this->reference_manager->expects(self::once())->method('loadReferenceFromKeywordAndNumArgs')->with(Git::REFERENCE_KEYWORD, 101, 2, 'rantanplan/469eaa9');

        $this->git_reference_manager->getCommitReference($this->project, Git::REFERENCE_KEYWORD, 'rantanplan/469eaa9');
    }

    public function testItReturnsTheCommitReference(): void
    {
        $reference = $this->createMock(Reference::class);
        $reference->method('replaceLink');
        $this->repository_factory->method('getRepositoryByPath')->willReturn($this->repository);
        $this->reference_manager->method('loadReferenceFromKeywordAndNumArgs')->willReturn($reference);

        $ref = $this->git_reference_manager->getCommitReference($this->project, Git::REFERENCE_KEYWORD, 'rantanplan/469eaa9');
        self::assertEquals($reference, $ref);
    }

    public function testItReturnsNullIfTheRepositoryDoesNotExist(): void
    {
        $this->repository_factory->method('getRepositoryByPath')->willReturn(null);

        self::assertNull(
            $this->git_reference_manager->getCommitReference($this->project, Git::REFERENCE_KEYWORD, 'rantanplan/469eaa9')
        );
    }

    public function testItReturnsTheRepositoryFromCrossReferenceValue(): void
    {
        $this->repository_factory->expects(self::once())
            ->method('getRepositoryByPath')
            ->with(101, 'gpig/rantanplan.git')
            ->willReturn($this->repository);

        $commit_info = $this->git_reference_manager->getCommitInfoFromReferenceValue(
            $this->project,
            'rantanplan/469eaa9'
        );
        self::assertEquals($this->repository, $commit_info->getRepository());
        self::assertEquals('469eaa9', $commit_info->getSha1());
    }

    public function testItReturnsTheTagReference(): void
    {
        $reference = $this->createMock(Reference::class);
        $reference->method('replaceLink');

        $this->reference_dao->expects(self::once())->method('searchExistingProjectReference')
            ->with('git_tag', 101)
            ->willReturn(null);

        $this->repository_factory->method('getRepositoryByPath')->willReturn($this->repository);
        $this->reference_manager->method('loadReferenceFromKeywordAndNumArgs')->willReturn($reference);

        $ref = $this->git_reference_manager->getTagReference($this->project, Git::TAG_REFERENCE_KEYWORD, 'rantanplan/v1');
        self::assertEquals($reference, $ref);
    }

    public function testItReturnsTheExistingProjectTagReference(): void
    {
        $reference     = $this->createMock(Reference::class);
        $reference_row = [
            'id'      => 33,
            'keyword' => 'git_tag',
        ];

        $this->reference_dao->expects(self::once())->method('searchExistingProjectReference')
            ->with('git_tag', 101)
            ->willReturn($reference_row);

        $this->repository_factory->method('getRepositoryByPath')->willReturn($this->repository);
        $this->reference_manager->expects(self::once())->method('buildReference')
            ->with($reference_row)
            ->willReturn($reference);

        $ref = $this->git_reference_manager->getTagReference($this->project, Git::TAG_REFERENCE_KEYWORD, 'rantanplan/v1');
        self::assertEquals($reference, $ref);
    }
}
