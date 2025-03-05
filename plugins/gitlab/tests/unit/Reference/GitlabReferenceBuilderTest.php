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
use Tuleap\Gitlab\Reference\Branch\BranchReferenceSplitValuesDao;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchReferenceSplitValuesBuilder;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReferenceSplitValuesBuilder;
use Tuleap\Gitlab\Reference\Tag\TagReferenceSplitValuesDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabReferenceBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var GitlabReferenceBuilder
     */
    private $builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReferenceDao
     */
    private $reference_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegrationFactory
     */
    private $repository_integration_factory;
    /**
     * @var BranchReferenceSplitValuesDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $branch_reference_dao;
    /**
     * @var TagReferenceSplitValuesDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $tag_reference_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reference_dao                  = $this->createMock(ReferenceDao::class);
        $this->repository_integration_factory = $this->createMock(GitlabRepositoryIntegrationFactory::class);

        $this->branch_reference_dao = $this->createMock(BranchReferenceSplitValuesDao::class);
        $this->tag_reference_dao    = $this->createMock(TagReferenceSplitValuesDao::class);
        $gitlab_reference_extractor = new GitlabReferenceExtractor(
            new GitlabReferenceValueWithoutSeparatorSplitValuesBuilder(),
            new GitlabReferenceValueWithoutSeparatorSplitValuesBuilder(),
            new GitlabBranchReferenceSplitValuesBuilder(
                $this->branch_reference_dao,
            ),
            new GitlabTagReferenceSplitValuesBuilder(
                $this->tag_reference_dao,
            ),
        );

        $this->builder = new GitlabReferenceBuilder(
            $this->reference_dao,
            $this->repository_integration_factory,
            $gitlab_reference_extractor
        );
    }

    public function testItReturnsNullIfKeywordIsNotKnown(): void
    {
        self::assertNull(
            $this->builder->buildGitlabReference(
                ProjectTestBuilder::aProject()->build(),
                'whatever',
                'root/project01/10ee559cb0'
            )
        );
    }

    public function testItReturnsNullIfAProjectReferenceIsAlreadyExisting(): void
    {
        $this->reference_dao
            ->expects(self::once())
            ->method('isAProjectReferenceExisting')
            ->with('gitlab_commit', 101)
            ->willReturn(true);

        self::assertNull(
            $this->builder->buildGitlabReference(
                ProjectTestBuilder::aProject()->build(),
                'gitlab_commit',
                'root/project01/10ee559cb0'
            )
        );
    }

    public function testItReturnsNullIfTheReferenceValueIsNotWellFormed(): void
    {
        $this->reference_dao
            ->expects(self::once())
            ->method('isAProjectReferenceExisting')
            ->with('gitlab_commit', 101)
            ->willReturn(false);

        self::assertNull(
            $this->builder->buildGitlabReference(
                ProjectTestBuilder::aProject()->build(),
                'gitlab_commit',
                'root10ee559cb0'
            )
        );
    }

    public function testItReturnsNullIfTheRepositoryIsNotIntegratedIntoProject(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->reference_dao
            ->expects(self::once())
            ->method('isAProjectReferenceExisting')
            ->with('gitlab_commit', 101)
            ->willReturn(false);

        $this->repository_integration_factory
            ->expects(self::once())
            ->method('getIntegrationByNameInProject')
            ->with(
                $project,
                'root/project01'
            )
            ->willReturn(null);

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
        $project = ProjectTestBuilder::aProject()->build();

        $this->reference_dao
            ->expects(self::once())
            ->method('isAProjectReferenceExisting')
            ->with('gitlab_commit', 101)
            ->willReturn(false);

        $this->repository_integration_factory
            ->expects(self::once())
            ->method('getIntegrationByNameInProject')
            ->with(
                $project,
                'root/project01'
            )
            ->willReturn(
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

        self::assertNotNull($reference);
        self::assertSame('gitlab_commit', $reference->getKeyword());
        self::assertSame('plugin_gitlab', $reference->getNature());
        self::assertSame('https://example.com/root/project01/-/commit/10ee559cb0', $reference->getLink());
        self::assertSame(101, $reference->getGroupId());
    }

    public function testItReturnsTheMergeRequestReference(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->reference_dao
            ->expects(self::once())
            ->method('isAProjectReferenceExisting')
            ->with('gitlab_mr', 101)
            ->willReturn(false);

        $this->repository_integration_factory
            ->expects(self::once())
            ->method('getIntegrationByNameInProject')
            ->with(
                $project,
                'root/project01'
            )
            ->willReturn(
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

        self::assertNotNull($reference);
        self::assertSame('gitlab_mr', $reference->getKeyword());
        self::assertSame('plugin_gitlab', $reference->getNature());
        self::assertSame('https://example.com/root/project01/-/merge_requests/123', $reference->getLink());
        self::assertSame(101, $reference->getGroupId());
    }

    public function testItReturnsTheTagReference(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->reference_dao
            ->expects(self::once())
            ->method('isAProjectReferenceExisting')
            ->with('gitlab_tag', 101)
            ->willReturn(false);

        $this->tag_reference_dao->method('getAllTagsSplitValuesInProject')->willReturn(
            ['repository_name' => 'root/project01', 'tag_name' => 'v1.0.2'],
        );

        $this->repository_integration_factory
            ->expects(self::once())
            ->method('getIntegrationByNameInProject')
            ->with(
                $project,
                'root/project01'
            )
            ->willReturn(
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

        self::assertNotNull($reference);
        self::assertSame('gitlab_tag', $reference->getKeyword());
        self::assertSame('plugin_gitlab', $reference->getNature());
        self::assertSame('https://example.com/root/project01/-/tree/v1.0.2', $reference->getLink());
        self::assertSame(101, $reference->getGroupId());
    }

    public function testItReturnsTheBranchReference(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->reference_dao
            ->expects(self::once())
            ->method('isAProjectReferenceExisting')
            ->with('gitlab_branch', 101)
            ->willReturn(false);

        $this->branch_reference_dao->method('getAllBranchesSplitValuesInProject')->willReturn(
            ['repository_name' => 'root/project01', 'branch_name' => 'dev'],
        );

        $this->repository_integration_factory
            ->expects(self::once())
            ->method('getIntegrationByNameInProject')
            ->with(
                $project,
                'root/project01'
            )
            ->willReturn(
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
            'gitlab_branch',
            'root/project01/dev'
        );

        self::assertNotNull($reference);
        self::assertSame('gitlab_branch', $reference->getKeyword());
        self::assertSame('plugin_gitlab', $reference->getNature());
        self::assertSame('https://example.com/root/project01/-/tree/dev', $reference->getLink());
        self::assertSame(101, $reference->getGroupId());
    }

    public function testItReturnsTheCommitReferenceInGitLabProjectWithSubgroup(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->reference_dao
            ->expects(self::once())
            ->method('isAProjectReferenceExisting')
            ->with('gitlab_commit', 101)
            ->willReturn(false);

        $this->repository_integration_factory
            ->expects(self::once())
            ->method('getIntegrationByNameInProject')
            ->with(
                $project,
                'root/project01/repo01'
            )
            ->willReturn(
                new GitlabRepositoryIntegration(
                    1,
                    123456,
                    'root/project01/repo01',
                    '',
                    'https://example.com/root/project01/repo01',
                    new DateTimeImmutable(),
                    $project,
                    false
                )
            );

        $reference = $this->builder->buildGitlabReference(
            $project,
            'gitlab_commit',
            'root/project01/repo01/10ee559cb0'
        );

        self::assertNotNull($reference);
        self::assertSame('gitlab_commit', $reference->getKeyword());
        self::assertSame('plugin_gitlab', $reference->getNature());
        self::assertSame('https://example.com/root/project01/repo01/-/commit/10ee559cb0', $reference->getLink());
        self::assertSame(101, $reference->getGroupId());
    }
}
