<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Gitlab\Reference\Commit\GitlabCommit;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitCrossReferenceEnhancer;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitFactory;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequest;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class GitlabCrossReferenceOrganizerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabCommitFactory
     */
    private $gitlab_commit_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var GitlabCrossReferenceOrganizer
     */
    private $organizer;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabCommitCrossReferenceEnhancer
     */
    private $gitlab_commit_cross_reference_enhancer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabMergeRequestReferenceRetriever
     */
    private $gitlab_merge_request_reference_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TlpRelativeDatePresenterBuilder
     */
    private $relative_date_builder;

    protected function setUp(): void
    {
        $this->gitlab_repository_factory                = Mockery::mock(GitlabRepositoryFactory::class);
        $this->gitlab_commit_factory                    = Mockery::mock(GitlabCommitFactory::class);
        $this->gitlab_commit_cross_reference_enhancer   = Mockery::mock(GitlabCommitCrossReferenceEnhancer::class);
        $this->gitlab_merge_request_reference_retriever = Mockery::mock(GitlabMergeRequestReferenceRetriever::class);
        $this->project_manager                          = Mockery::mock(ProjectManager::class);
        $this->relative_date_builder                    = new TlpRelativeDatePresenterBuilder();

        $GLOBALS['Language']
            ->shouldReceive('getText')
            ->with('system', 'datefmt')
            ->andReturn('d/m/Y H:i');

        $this->organizer = new GitlabCrossReferenceOrganizer(
            $this->gitlab_repository_factory,
            $this->gitlab_commit_factory,
            $this->gitlab_commit_cross_reference_enhancer,
            $this->gitlab_merge_request_reference_retriever,
            $this->project_manager,
            $this->relative_date_builder,
        );
    }

    public function testItDoesNotOrganizeCrossReferencesItDoesNotKnow(): void
    {
        $user                = Mockery::mock(PFUser::class);
        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [
                        CrossReferencePresenterBuilder::get(1)->withType('nature_1')->build(),
                        CrossReferencePresenterBuilder::get(2)->withType('nature_2')->build(),
                        CrossReferencePresenterBuilder::get(3)->withType('nature_3')->build(),
                    ]
                ]
            )->getMock();


        $by_nature_organizer->shouldReceive('moveCrossReferenceToSection')->never();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeGitlabCrossReferencesIfRepositoryCannotBeFound(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $project = Mockery::mock(Project::class);

        $this->project_manager
            ->shouldReceive(['getProject' => $project])
            ->getMock();

        $this->gitlab_repository_factory
            ->shouldReceive('getGitlabRepositoryByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn(null);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->withValue('john-snow/winter-is-coming/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
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

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeGitlabCrossReferencesIfCommitDataCannotBeFound(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getUnixNameLowercase')
            ->andReturn('thenightwatch')
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);

        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable()
        );

        $this->gitlab_repository_factory
            ->shouldReceive('getGitlabRepositoryByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($repository);

        $this->gitlab_commit_factory->shouldReceive('getGitlabCommitInRepositoryWithSha1')
            ->with($repository, '14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->andReturn(null);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->withValue('john-snow/winter-is-coming/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [$a_ref],
                ]
            )->getMock();

        $by_nature_organizer
            ->shouldReceive('removeUnreadableCrossReference')
            ->with($a_ref)
            ->once();

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->never();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabCommitCrossReferencesInTheirRespectiveRepositorySection(): void
    {
        $user    = Mockery::mock(PFUser::class);
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getUnixNameLowercase')
            ->andReturn('thenightwatch')
            ->getMock();

        $another_project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'foodstocks'])
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(2)
            ->andReturn($another_project);

        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable()
        );

        $another_repository = new GitlabRepository(
            2,
            3,
            'winter-is-coming',
            'Need more hot chocolate, we crave sugar',
            'the_full_url',
            new DateTimeImmutable()
        );

        $this->gitlab_repository_factory
            ->shouldReceive('getGitlabRepositoryByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($repository);

        $this->gitlab_repository_factory
            ->shouldReceive('getGitlabRepositoryByNameInProject')
            ->with($another_project, 'samwell-tarly/winter-is-coming')
            ->andReturn($another_repository);

        $john_snow_commit = new GitlabCommit(
            '14a9b6c0c0c965977cf2af2199f93df82afcdea3',
            1608555618,
            'Increase blankets stocks for winter',
            "master",
            'John Snow',
            'john-snow@the-wall.com',
        );

        $samwell_tarly_commit = new GitlabCommit(
            'be35d127acb88876ee4fdbf02188d372dc61e98d',
            1608555618,
            'Increase hot chocolate stocks for winter',
            "master",
            'Samwell Tarly',
            'samwell-tarly@the-wall.com',
        );

        $this->gitlab_commit_factory->shouldReceive('getGitlabCommitInRepositoryWithSha1')
            ->with($repository, '14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->andReturn($john_snow_commit);

        $this->gitlab_commit_factory->shouldReceive('getGitlabCommitInRepositoryWithSha1')
            ->with($another_repository, 'be35d127acb88876ee4fdbf02188d372dc61e98d')
            ->andReturn($samwell_tarly_commit);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->withValue('john-snow/winter-is-coming/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->withProjectId(1)
            ->build();

        $another_ref = CrossReferencePresenterBuilder::get(2)
            ->withType('plugin_gitlab_commit')
            ->withValue('samwell-tarly/winter-is-coming/be35d127acb88876ee4fdbf02188d372dc61e98d')
            ->withProjectId(2)
            ->build();

        $this->gitlab_commit_cross_reference_enhancer->shouldReceive('getCrossReferencePresenterWithCommitInformation')
            ->with($a_ref, $john_snow_commit, $user)
            ->andReturn($a_ref);

        $this->gitlab_commit_cross_reference_enhancer->shouldReceive('getCrossReferencePresenterWithCommitInformation')
            ->with($another_ref, $samwell_tarly_commit, $user)
            ->andReturn($another_ref);

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCurrentUser'              => $user,
                    'getCrossReferencePresenters' => [$a_ref, $another_ref],
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('removeCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with(
                $a_ref,
                'thenightwatch/winter-is-coming'
            )
            ->once();

        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with(
                $another_ref,
                'foodstocks/winter-is-coming'
            )
            ->once();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabMergeRequestCrossReferencesInTheirRespectiveRepositorySection(): void
    {
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getUnixNameLowercase')
            ->andReturn('thenightwatch')
            ->getMock();

        $another_project = Mockery::mock(Project::class)
            ->shouldReceive(['getUnixNameLowerCase' => 'foodstocks'])
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(2)
            ->andReturn($another_project);

        $repository = new GitlabRepository(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable()
        );

        $another_repository = new GitlabRepository(
            2,
            3,
            'winter-is-coming',
            'Need more hot chocolate, we crave sugar',
            'the_full_url',
            new DateTimeImmutable()
        );

        $this->gitlab_repository_factory
            ->shouldReceive('getGitlabRepositoryByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($repository);

        $this->gitlab_repository_factory
            ->shouldReceive('getGitlabRepositoryByNameInProject')
            ->with($another_project, 'samwell-tarly/winter-is-coming')
            ->andReturn($another_repository);

        $john_snow_merge_request = new GitlabMergeRequest(
            'The title of the MR 14',
            'merged',
            new DateTimeImmutable()
        );

        $samwell_tarly_merge_request = new GitlabMergeRequest(
            'The title of MR #26',
            'closed',
            new DateTimeImmutable()
        );

        $this->gitlab_merge_request_reference_retriever
            ->shouldReceive('getGitlabMergeRequestInRepositoryWithId')
            ->with($repository, 14)
            ->andReturn($john_snow_merge_request)
            ->once();

        $this->gitlab_merge_request_reference_retriever
            ->shouldReceive('getGitlabMergeRequestInRepositoryWithId')
            ->with($another_repository, 26)
            ->andReturn($samwell_tarly_merge_request)
            ->once();

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_mr')
            ->withValue('john-snow/winter-is-coming/14')
            ->withProjectId(1)
            ->build();

        $another_ref = CrossReferencePresenterBuilder::get(2)
            ->withType('plugin_gitlab_mr')
            ->withValue('samwell-tarly/winter-is-coming/26')
            ->withProjectId(2)
            ->build();

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getPreference')->andReturn("relative_first-absolute_tooltip");
        $user->shouldReceive('getLocale')->andReturn("en_US");

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCrossReferencePresenters' => [$a_ref, $another_ref],
                    'getCurrentUser' => $user
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('removeCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->twice();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }
}
