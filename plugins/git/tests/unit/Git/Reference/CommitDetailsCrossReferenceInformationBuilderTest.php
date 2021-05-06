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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class CommitDetailsCrossReferenceInformationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Git_ReferenceManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $git_reference_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CommitProvider
     */
    private $commit_provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CommitDetailsRetriever
     */
    private $details_retriever;
    /**
     * @var CommitDetailsCrossReferenceInformationBuilder
     */
    private $builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->user    = Mockery::mock(\PFUser::class);
        $this->project = Mockery::mock(Project::class, ['getUnixNameLowerCase' => 'acme']);

        $project_manager             = Mockery::mock(\ProjectManager::class, ['getProject' => $this->project]);
        $this->git_reference_manager = Mockery::mock(\Git_ReferenceManager::class);
        $this->commit_provider       = Mockery::mock(CommitProvider::class);
        $this->details_retriever     = Mockery::mock(CommitDetailsRetriever::class);

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
            ->shouldReceive('getCommitInfoFromReferenceValue')
            ->with($this->project, 'cloudy/stable/1a2b3c4d5e')
            ->andReturn(new CommitInfoFromReferenceValue(null, '1a2b3c4d5e'));

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        self::assertNull($this->builder->getCommitDetailsCrossReferenceInformation($this->user, $ref));
    }

    public function testItReturnsNullIfUserCannotReadRepository(): void
    {
        $repository = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'cloudy/stable', 'userCanRead' => false])
            ->getMock();

        $this->git_reference_manager
            ->shouldReceive('getCommitInfoFromReferenceValue')
            ->with($this->project, 'cloudy/stable/1a2b3c4d5e')
            ->andReturn(new CommitInfoFromReferenceValue($repository, '1a2b3c4d5e'));

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        self::assertNull($this->builder->getCommitDetailsCrossReferenceInformation($this->user, $ref));
    }

    public function testItReturnsNullIfCommitCannotBeInstantiatedByGitPHP(): void
    {
        $repository = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'cloudy/stable', 'userCanRead' => true])
            ->getMock();

        $this->git_reference_manager
            ->shouldReceive('getCommitInfoFromReferenceValue')
            ->with($this->project, 'cloudy/stable/1a2b3c4d5e')
            ->andReturn(new CommitInfoFromReferenceValue($repository, '1a2b3c4d5e'));

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        $this->commit_provider
            ->shouldReceive('getCommit')
            ->with($repository, '1a2b3c4d5e')
            ->andReturnNull();

        self::assertNull($this->builder->getCommitDetailsCrossReferenceInformation($this->user, $ref));
    }

    public function testItReturnsNullIfCommitCannotBeFoundInTheRepository(): void
    {
        $repository = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'cloudy/stable', 'userCanRead' => true])
            ->getMock();

        $this->git_reference_manager
            ->shouldReceive('getCommitInfoFromReferenceValue')
            ->with($this->project, 'cloudy/stable/1a2b3c4d5e')
            ->andReturn(new CommitInfoFromReferenceValue($repository, '1a2b3c4d5e'));

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        $commit = Mockery::mock(Commit::class);
        $this->commit_provider
            ->shouldReceive('getCommit')
            ->with($repository, '1a2b3c4d5e')
            ->andReturn($commit);

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
            ->shouldReceive('retrieveCommitDetails')
            ->with($repository, $commit)
            ->andReturnNull();

        self::assertNull($this->builder->getCommitDetailsCrossReferenceInformation($this->user, $ref));
    }

    public function testItReturnsInformation(): void
    {
        $repository = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'cloudy/stable', 'userCanRead' => true])
            ->getMock();

        $this->git_reference_manager
            ->shouldReceive('getCommitInfoFromReferenceValue')
            ->with($this->project, 'cloudy/stable/1a2b3c4d5e')
            ->andReturn(new CommitInfoFromReferenceValue($repository, '1a2b3c4d5e'));

        $ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        $commit = Mockery::mock(Commit::class);
        $this->commit_provider
            ->shouldReceive('getCommit')
            ->with($repository, '1a2b3c4d5e')
            ->andReturn($commit);

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
            ->shouldReceive('retrieveCommitDetails')
            ->with($repository, $commit)
            ->andReturn($commit_details);

        $information = $this->builder->getCommitDetailsCrossReferenceInformation($this->user, $ref);

        self::assertEquals($commit_details, $information->getCommitDetails());
        self::assertEquals($ref, $information->getCrossReferencePresenter());
        self::assertEquals('acme/cloudy/stable', $information->getSectionLabel());
    }
}
