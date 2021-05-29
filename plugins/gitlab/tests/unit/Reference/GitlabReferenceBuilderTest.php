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
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;

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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryIntegrationFactory
     */
    private $repository_integration_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reference_dao                  = Mockery::mock(ReferenceDao::class);
        $this->repository_integration_factory = Mockery::mock(GitlabRepositoryIntegrationFactory::class);

        $this->builder = new GitlabReferenceBuilder(
            $this->reference_dao,
            $this->repository_integration_factory
        );
    }

    public function testItReturnsNullIfKeywordIsNotKnown(): void
    {
        self::assertNull(
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

        self::assertNull(
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

        self::assertNull(
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

        $this->repository_integration_factory->shouldReceive('getIntegrationByNameInProject')
            ->once()
            ->with(
                $project,
                'root/project01'
            )
            ->andReturnNull();

        self::assertNull(
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

        $this->repository_integration_factory->shouldReceive('getIntegrationByNameInProject')
            ->once()
            ->with(
                $project,
                'root/project01'
            )
            ->andReturn(
                new GitlabRepositoryIntegration(
                    1,
                    123456,
                    'root/project01',
                    '',
                    'https://example.com/root/project01',
                    new DateTimeImmutable(),
                    $project,
                    false
                )
            );

        $reference = $this->builder->buildGitlabReference(
            $project,
            'gitlab_commit',
            'root/project01/10ee559cb0'
        );

        self::assertSame('gitlab_commit', $reference->getKeyword());
        self::assertSame('plugin_gitlab', $reference->getNature());
        self::assertSame('https://example.com/root/project01/-/commit/10ee559cb0', $reference->getLink());
        self::assertSame(101, $reference->getGroupId());
    }

    public function testItReturnsTheMergeRequestReference(): void
    {
        $project = Project::buildForTest();

        $this->reference_dao->shouldReceive('isAProjectReferenceExisting')
            ->once()
            ->with('gitlab_mr', 101)
            ->andReturnFalse();

        $this->repository_integration_factory->shouldReceive('getIntegrationByNameInProject')
            ->once()
            ->with(
                $project,
                'root/project01'
            )
            ->andReturn(
                new GitlabRepositoryIntegration(
                    1,
                    123456,
                    'root/project01',
                    '',
                    'https://example.com/root/project01',
                    new DateTimeImmutable(),
                    $project,
                    false
                )
            );

        $reference = $this->builder->buildGitlabReference(
            $project,
            'gitlab_mr',
            'root/project01/123'
        );

        self::assertSame('gitlab_mr', $reference->getKeyword());
        self::assertSame('plugin_gitlab', $reference->getNature());
        self::assertSame('https://example.com/root/project01/-/merge_requests/123', $reference->getLink());
        self::assertSame(101, $reference->getGroupId());
    }

    public function testItReturnsTheTagReference(): void
    {
        $project = Project::buildForTest();

        $this->reference_dao->shouldReceive('isAProjectReferenceExisting')
            ->once()
            ->with('gitlab_tag', 101)
            ->andReturnFalse();

        $this->repository_integration_factory->shouldReceive('getIntegrationByNameInProject')
            ->once()
            ->with(
                $project,
                'root/project01'
            )
            ->andReturn(
                new GitlabRepositoryIntegration(
                    1,
                    123456,
                    'root/project01',
                    '',
                    'https://example.com/root/project01',
                    new DateTimeImmutable(),
                    $project,
                    false
                )
            );

        $reference = $this->builder->buildGitlabReference(
            $project,
            'gitlab_tag',
            'root/project01/v1.0.2'
        );

        self::assertSame('gitlab_tag', $reference->getKeyword());
        self::assertSame('plugin_gitlab', $reference->getNature());
        self::assertSame('https://example.com/root/project01/-/tree/v1.0.2', $reference->getLink());
        self::assertSame(101, $reference->getGroupId());
    }
}
