<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;

class GitlabReferenceBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GitlabReferenceBuilder
     */
    private $builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ReferenceDao
     */
    private $reference_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reference_dao             = Mockery::mock(ReferenceDao::class);
        $this->gitlab_repository_factory = Mockery::mock(GitlabRepositoryFactory::class);

        $this->builder = new GitlabReferenceBuilder(
            $this->reference_dao,
            $this->gitlab_repository_factory
        );
    }

    public function testItReturnsNullIfKeywordIsNotKnown(): void
    {
        $this->assertNull(
            $this->builder->buildGitlabReference(
                Project::buildForTest(),
                'whatever',
                'root/project01/10ee559cb0'
            )
        );
    }

    public function testItReturnsNullIfAProjectReferenceIsAlreadyExisting(): void
    {
        $this->reference_dao->shouldReceive('isAProjectReferenceExisting')
            ->once()
            ->with('gitlab_commit', 101)
            ->andReturnTrue();

        $this->assertNull(
            $this->builder->buildGitlabReference(
                Project::buildForTest(),
                'gitlab_commit',
                'root/project01/10ee559cb0'
            )
        );
    }

    public function testItReturnsNullIfTheReferenceValueIsNotWellFormed(): void
    {
        $this->reference_dao->shouldReceive('isAProjectReferenceExisting')
            ->once()
            ->with('gitlab_commit', 101)
            ->andReturnFalse();

        $this->assertNull(
            $this->builder->buildGitlabReference(
                Project::buildForTest(),
                'gitlab_commit',
                'root10ee559cb0'
            )
        );
    }

    public function testItReturnsNullIfTheRepositoryIsNotIntegratedIntoProject(): void
    {
        $project = Project::buildForTest();

        $this->reference_dao->shouldReceive('isAProjectReferenceExisting')
            ->once()
            ->with('gitlab_commit', 101)
            ->andReturnFalse();

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByNameInProject')
            ->once()
            ->with(
                $project,
                'root/project01'
            )
            ->andReturnNull();

        $this->assertNull(
            $this->builder->buildGitlabReference(
                $project,
                'gitlab_commit',
                'root/project01/10ee559cb0'
            )
        );
    }

    public function testItReturnsTheCommitReference(): void
    {
        $project = Project::buildForTest();

        $this->reference_dao->shouldReceive('isAProjectReferenceExisting')
            ->once()
            ->with('gitlab_commit', 101)
            ->andReturnFalse();

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByNameInProject')
            ->once()
            ->with(
                $project,
                'root/project01'
            )
            ->andReturn(
                new GitlabRepository(
                    1,
                    123456,
                    'root/project01',
                    '',
                    'https://example.com/root/project01',
                    new DateTimeImmutable()
                )
            );

        $reference = $this->builder->buildGitlabReference(
            $project,
            'gitlab_commit',
            'root/project01/10ee559cb0'
        );

        $this->assertSame('gitlab_commit', $reference->getKeyword());
        $this->assertSame('plugin_gitlab', $reference->getNature());
        $this->assertSame('https://example.com/root/project01/-/commit/10ee559cb0', $reference->getLink());
        $this->assertSame(101, $reference->getGroupId());
    }

    public function testItReturnsTheMergeRequestReference(): void
    {
        $project = Project::buildForTest();

        $this->reference_dao->shouldReceive('isAProjectReferenceExisting')
            ->once()
            ->with('gitlab_mr', 101)
            ->andReturnFalse();

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByNameInProject')
            ->once()
            ->with(
                $project,
                'root/project01'
            )
            ->andReturn(
                new GitlabRepository(
                    1,
                    123456,
                    'root/project01',
                    '',
                    'https://example.com/root/project01',
                    new DateTimeImmutable()
                )
            );

        $reference = $this->builder->buildGitlabReference(
            $project,
            'gitlab_mr',
            'root/project01/123'
        );

        $this->assertSame('gitlab_mr', $reference->getKeyword());
        $this->assertSame('plugin_gitlab', $reference->getNature());
        $this->assertSame('https://example.com/root/project01/-/merge_requests/123', $reference->getLink());
        $this->assertSame(101, $reference->getGroupId());
    }

    public function testItReturnsTheTagReference(): void
    {
        $project = Project::buildForTest();

        $this->reference_dao->shouldReceive('isAProjectReferenceExisting')
            ->once()
            ->with('gitlab_tag', 101)
            ->andReturnFalse();

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByNameInProject')
            ->once()
            ->with(
                $project,
                'root/project01'
            )
            ->andReturn(
                new GitlabRepository(
                    1,
                    123456,
                    'root/project01',
                    '',
                    'https://example.com/root/project01',
                    new DateTimeImmutable()
                )
            );

        $reference = $this->builder->buildGitlabReference(
            $project,
            'gitlab_tag',
            'root/project01/v1.0.2'
        );

        $this->assertSame('gitlab_tag', $reference->getKeyword());
        $this->assertSame('plugin_gitlab', $reference->getNature());
        $this->assertSame('https://example.com/root/project01/-/tree/v1.0.2', $reference->getLink());
        $this->assertSame(101, $reference->getGroupId());
    }
}
