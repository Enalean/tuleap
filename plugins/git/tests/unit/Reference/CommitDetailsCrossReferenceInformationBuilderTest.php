<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Reference;

use Git_ReferenceManager;
use GitRepository;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectManager;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommitDetailsCrossReferenceInformationBuilderTest extends TestCase
{
    private Git_ReferenceManager&MockObject $git_reference_manager;
    private CommitProvider&MockObject $commit_provider;
    private CommitDetailsRetriever&MockObject $details_retriever;
    private CommitDetailsCrossReferenceInformationBuilder $builder;
    private Project $project;
    private PFUser $user;

    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::buildWithDefaults();
        $this->project = ProjectTestBuilder::aProject()->withUnixName('acme')->build();

        $project_manager             = $this->createMock(ProjectManager::class);
        $this->git_reference_manager = $this->createMock(Git_ReferenceManager::class);
        $this->commit_provider       = $this->createMock(CommitProvider::class);
        $this->details_retriever     = $this->createMock(CommitDetailsRetriever::class);
        $project_manager->method('getProject')->willReturn($this->project);

        $this->builder = new CommitDetailsCrossReferenceInformationBuilder(
            $project_manager,
            $this->git_reference_manager,
            $this->commit_provider,
            $this->details_retriever,
        );
    }

    public function testItReturnsNullIfNoRepositoryCanBeFound(): void
    {
        $this->git_reference_manager
            ->method('getCommitInfoFromReferenceValue')
            ->with($this->project, 'cloudy/stable/1a2b3c4d5e')
            ->willReturn(new CommitInfoFromReferenceValue(null, '1a2b3c4d5e'));

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        self::assertNull($this->builder->getCommitDetailsCrossReferenceInformation($this->user, $ref));
    }

    public function testItReturnsNullIfUserCannotReadRepository(): void
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->method('getFullName')->willReturn('cloudy/stable');
        $repository->method('userCanRead')->willReturn(false);

        $this->git_reference_manager
            ->method('getCommitInfoFromReferenceValue')
            ->with($this->project, 'cloudy/stable/1a2b3c4d5e')
            ->willReturn(new CommitInfoFromReferenceValue($repository, '1a2b3c4d5e'));

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        self::assertNull($this->builder->getCommitDetailsCrossReferenceInformation($this->user, $ref));
    }

    public function testItReturnsNullIfCommitCannotBeInstantiatedByGitPHP(): void
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->method('getFullName')->willReturn('cloudy/stable');
        $repository->method('userCanRead')->willReturn(true);

        $this->git_reference_manager
            ->method('getCommitInfoFromReferenceValue')
            ->with($this->project, 'cloudy/stable/1a2b3c4d5e')
            ->willReturn(new CommitInfoFromReferenceValue($repository, '1a2b3c4d5e'));

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        $this->commit_provider
            ->method('getCommit')
            ->with($repository, '1a2b3c4d5e')
            ->willReturn(null);

        self::assertNull($this->builder->getCommitDetailsCrossReferenceInformation($this->user, $ref));
    }

    public function testItReturnsNullIfCommitCannotBeFoundInTheRepository(): void
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->method('getFullName')->willReturn('cloudy/stable');
        $repository->method('userCanRead')->willReturn(true);

        $this->git_reference_manager
            ->method('getCommitInfoFromReferenceValue')
            ->with($this->project, 'cloudy/stable/1a2b3c4d5e')
            ->willReturn(new CommitInfoFromReferenceValue($repository, '1a2b3c4d5e'));

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        $commit = $this->createMock(Commit::class);
        $this->commit_provider
            ->method('getCommit')
            ->with($repository, '1a2b3c4d5e')
            ->willReturn($commit);

        $this->details_retriever
            ->method('retrieveCommitDetails')
            ->with($repository, $commit)
            ->willReturn(null);

        self::assertNull($this->builder->getCommitDetailsCrossReferenceInformation($this->user, $ref));
    }

    public function testItReturnsInformation(): void
    {
        $repository = $this->createMock(GitRepository::class);
        $repository->method('getFullName')->willReturn('cloudy/stable');
        $repository->method('userCanRead')->willReturn(true);

        $this->git_reference_manager
            ->method('getCommitInfoFromReferenceValue')
            ->with($this->project, 'cloudy/stable/1a2b3c4d5e')
            ->willReturn(new CommitInfoFromReferenceValue($repository, '1a2b3c4d5e'));

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        $commit = $this->createMock(Commit::class);
        $this->commit_provider
            ->method('getCommit')
            ->with($repository, '1a2b3c4d5e')
            ->willReturn($commit);

        $commit_details = new CommitDetails(
            '1a2b3c4d5e6f7g8h9i',
            'Add foo to stuff',
            '',
            '',
            'jdoe@example.com',
            'John Doe',
            1234567890
        );
        $this->details_retriever
            ->method('retrieveCommitDetails')
            ->with($repository, $commit)
            ->willReturn($commit_details);

        $information = $this->builder->getCommitDetailsCrossReferenceInformation($this->user, $ref);

        self::assertEquals($commit_details, $information->getCommitDetails());
        self::assertEquals($ref, $information->getCrossReferencePresenter());
        self::assertEquals('acme/cloudy/stable', $information->getSectionLabel());
    }
}
