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

declare(strict_types=1);

namespace Tuleap\Git\Reference;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Git\GitPHP\Commit;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class CrossReferenceGitOrganizerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \Git_ReferenceManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $git_reference_manager;
    /**
     * @var CrossReferenceGitOrganizer
     */
    private $organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CommitProvider
     */
    private $commit_provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceGitEnhancer
     */
    private $enhancer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CommitDetailsRetriever
     */
    private $details_retriever;

    protected function setUp(): void
    {
        $this->project_manager       = Mockery::mock(\ProjectManager::class);
        $this->git_reference_manager = Mockery::mock(\Git_ReferenceManager::class);
        $this->commit_provider       = Mockery::mock(CommitProvider::class);
        $this->enhancer              = Mockery::mock(CrossReferenceGitEnhancer::class);
        $this->details_retriever     = Mockery::mock(CommitDetailsRetriever::class);

        $this->organizer = new CrossReferenceGitOrganizer(
            $this->project_manager,
            $this->git_reference_manager,
            $this->commit_provider,
            $this->enhancer,
            $this->details_retriever,
        );
    }

    public function testItDoesNotOrganizeCrossReferencesItDoesNotKnow(): void
    {
        $user = Mockery::mock(\PFUser::class);

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [
                        CrossReferencePresenterBuilder::get(1)->withType('tracker')->build(),
                    ]
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeGitReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeGitCrossReferencesIfRepositoryCannotBeFound(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $project = Mockery::mock(Project::class);

        $this->project_manager
            ->shouldReceive(['getProject' => $project])
            ->getMock();

        $this->git_reference_manager
            ->shouldReceive('getCommitInfoFromReferenceValue')
            ->with($project, 'cloudy/stable/1a2b3c4d5e')
            ->andReturn(new CommitInfoFromReferenceValue(null, '1a2b3c4d5e'));

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [$a_ref],
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($a_ref)
            ->once();

        $this->organizer->organizeGitReferences($by_nature_organizer);
    }

    public function testItOrganizesGitCrossReferencesInTheirRespectiveRepositorySection(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'acme'])
            ->getMock();

        $another_project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'foobar'])
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(2)
            ->andReturn($another_project);

        $repository         = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'cloudy/stable', 'userCanRead' => true])
            ->getMock();
        $another_repository = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'tuleap/stable', 'userCanRead' => true])
            ->getMock();

        $this->git_reference_manager
            ->shouldReceive('getCommitInfoFromReferenceValue')
            ->with($project, 'cloudy/stable/1a2b3c4d5e')
            ->andReturn(new CommitInfoFromReferenceValue($repository, '1a2b3c4d5e'));
        $this->git_reference_manager
            ->shouldReceive('getCommitInfoFromReferenceValue')
            ->with($another_project, 'tuleap/stable/e5d4c3b2a1')
            ->andReturn(new CommitInfoFromReferenceValue($another_repository, 'e5d4c3b2a1'));

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('git_commit')
            ->withValue('cloudy/stable/1a2b3c4d5e')
            ->withProjectId(1)
            ->build();

        $another_ref = CrossReferencePresenterBuilder::get(2)
            ->withType('git_commit')
            ->withValue('tuleap/stable/e5d4c3b2a1')
            ->withProjectId(2)
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCrossReferencePresenters' => [$a_ref, $another_ref],
                    'getCurrentUser'              => $user,
                ]
            )->getMock();

        $a_commit = Mockery::mock(Commit::class);
        $this->commit_provider
            ->shouldReceive('getCommit')
            ->with($repository, '1a2b3c4d5e')
            ->andReturn($a_commit);

        $another_commit = Mockery::mock(Commit::class);
        $this->commit_provider
            ->shouldReceive('getCommit')
            ->with($another_repository, 'e5d4c3b2a1')
            ->andReturn($another_commit);

        $a_commit_details = new CommitDetails(
            '1a2b3c4d5e6f7g8h9i',
            'Add foo to stuff',
            '',
            '',
            null,
            'John Doe',
            1234567890
        );
        $this->details_retriever
            ->shouldReceive('retrieveCommitDetails')
            ->with($repository, $a_commit)
            ->andReturn($a_commit_details);

        $another_commit_details = new CommitDetails(
            'e5d4c3b2a16f7g8h9i',
            'Another bites the dust',
            '',
            '',
            null,
            'John Doe',
            1234567890
        );
        $this->details_retriever
            ->shouldReceive('retrieveCommitDetails')
            ->with($another_repository, $another_commit)
            ->andReturn($another_commit_details);

        $augmented_a_ref = $a_ref->withTitle("A ref", null);
        $this->enhancer
            ->shouldReceive('getCrossReferencePresenterWithCommitInformation')
            ->with($a_ref, $a_commit_details, $user)
            ->once()
            ->andReturn($augmented_a_ref);

        $augmented_another_ref = $another_ref->withTitle("Another ref", null);
        $this->enhancer
            ->shouldReceive('getCrossReferencePresenterWithCommitInformation')
            ->with($another_ref, $another_commit_details, $user)
            ->once()
            ->andReturn($augmented_another_ref);

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with($augmented_a_ref, 'acme/cloudy/stable')
            ->once();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with($augmented_another_ref, 'foobar/tuleap/stable')
            ->once();

        $this->organizer->organizeGitReferences($by_nature_organizer);
    }

    public function testItIgnoresRepositoriesUserCannotRead(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'acme'])
            ->getMock();

        $another_project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'foobar'])
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(2)
            ->andReturn($another_project);

        $another_repository = Mockery::mock(\GitRepository::class)
            ->shouldReceive(['getFullName' => 'tuleap/stable', 'userCanRead' => false])
            ->getMock();

        $this->git_reference_manager
            ->shouldReceive('getCommitInfoFromReferenceValue')
            ->with($another_project, 'tuleap/stable/e5d4c3b2a1')
            ->andReturn(new CommitInfoFromReferenceValue($another_repository, 'e5d4c3b2a1'));

        $another_ref = CrossReferencePresenterBuilder::get(2)
            ->withType('git_commit')
            ->withValue('tuleap/stable/e5d4c3b2a1')
            ->withProjectId(2)
            ->build();

        $commit = Mockery::mock(Commit::class);
        $this->commit_provider
            ->shouldReceive('getCommit')
            ->andReturn($commit);

        $augmented_another_ref = $another_ref->withTitle("Another ref", null);
        $this->enhancer
            ->shouldReceive('getCrossReferencePresenterWithCommitInformation')
            ->with($another_ref, $commit, $another_repository, $user)
            ->never();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCrossReferencePresenters' => [$another_ref],
                    'getCurrentUser'              => $user,
                ]
            )->getMock();

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with($augmented_another_ref, 'foobar/tuleap/stable')
            ->never();

        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($another_ref)
            ->once();

        $this->organizer->organizeGitReferences($by_nature_organizer);
    }
}
