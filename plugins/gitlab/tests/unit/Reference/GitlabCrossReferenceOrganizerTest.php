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
use Project;
use ProjectManager;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Gitlab\Reference\Branch\GitlabBranch;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchCrossReferenceEnhancer;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchFactory;
use Tuleap\Gitlab\Reference\Commit\GitlabCommit;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitCrossReferenceEnhancer;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitFactory;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequest;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReferenceRetriever;
use Tuleap\Gitlab\Reference\Tag\GitlabTag;
use Tuleap\Gitlab\Reference\Tag\GitlabTagFactory;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class GitlabCrossReferenceOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryIntegrationFactory
     */
    private $repository_integration_factory;
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
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserHelper
     */
    private $user_helper;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabTagFactory
     */
    private $gitlab_tag_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|GitlabBranchFactory
     */
    private $gitlab_branch_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|GitlabBranchCrossReferenceEnhancer
     */
    private $gitlab_branch_cross_reference_enhancer;

    protected function setUp(): void
    {
        $this->repository_integration_factory           = Mockery::mock(GitlabRepositoryIntegrationFactory::class);
        $this->gitlab_commit_factory                    = Mockery::mock(GitlabCommitFactory::class);
        $this->gitlab_commit_cross_reference_enhancer   = Mockery::mock(GitlabCommitCrossReferenceEnhancer::class);
        $this->gitlab_merge_request_reference_retriever = Mockery::mock(GitlabMergeRequestReferenceRetriever::class);
        $this->gitlab_tag_factory                       = Mockery::mock(GitlabTagFactory::class);
        $this->gitlab_branch_factory                    = $this->createMock(GitlabBranchFactory::class);
        $this->gitlab_branch_cross_reference_enhancer   = $this->createMock(GitlabBranchCrossReferenceEnhancer::class);
        $this->project_manager                          = Mockery::mock(ProjectManager::class);
        $this->relative_date_builder                    = new TlpRelativeDatePresenterBuilder();
        $this->user_manager                             = Mockery::mock(\UserManager::class);
        $this->user_helper                              = Mockery::mock(\UserHelper::class);

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');

        $this->organizer = new GitlabCrossReferenceOrganizer(
            $this->repository_integration_factory,
            $this->gitlab_commit_factory,
            $this->gitlab_commit_cross_reference_enhancer,
            $this->gitlab_merge_request_reference_retriever,
            $this->gitlab_tag_factory,
            $this->gitlab_branch_factory,
            $this->gitlab_branch_cross_reference_enhancer,
            $this->project_manager,
            $this->relative_date_builder,
            $this->user_manager,
            $this->user_helper
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

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
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

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($integration);

        $this->gitlab_commit_factory->shouldReceive('getGitlabCommitInRepositoryWithSha1')
            ->with($integration, '14a9b6c0c0c965977cf2af2199f93df82afcdea3')
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

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $another_integration = new GitlabRepositoryIntegration(
            2,
            3,
            'winter-is-coming',
            'Need more hot chocolate, we crave sugar',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($integration);

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
            ->with($another_project, 'samwell-tarly/winter-is-coming')
            ->andReturn($another_integration);

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
            ->with($integration, '14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->andReturn($john_snow_commit);

        $this->gitlab_commit_factory->shouldReceive('getGitlabCommitInRepositoryWithSha1')
            ->with($another_integration, 'be35d127acb88876ee4fdbf02188d372dc61e98d')
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

    public function testItOrganizesGitlabMergeRequestCrossReferencesInTheirRespectiveRepositorySectionWithoutAuthor(): void
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

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $another_integration = new GitlabRepositoryIntegration(
            2,
            3,
            'winter-is-coming',
            'Need more hot chocolate, we crave sugar',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($integration);

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
            ->with($another_project, 'samwell-tarly/winter-is-coming')
            ->andReturn($another_integration);

        $john_snow_merge_request = new GitlabMergeRequest(
            'The title of the MR 14',
            'merged',
            new DateTimeImmutable('@1234567890'),
            null,
            null
        );

        $samwell_tarly_merge_request = new GitlabMergeRequest(
            'The title of MR #26',
            'closed',
            new DateTimeImmutable('@1234567890'),
            null,
            null
        );

        $this->gitlab_merge_request_reference_retriever
            ->shouldReceive('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 14)
            ->andReturn($john_snow_merge_request)
            ->once();

        $this->gitlab_merge_request_reference_retriever
            ->shouldReceive('getGitlabMergeRequestInRepositoryWithId')
            ->with($another_integration, 26)
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

        $this->user_manager->shouldReceive('getUserByEmail')->never();
        $this->user_helper->shouldReceive('getDisplayNameFromUser')->never();

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
            ->with(
                Mockery::on(
                    function (CrossReferencePresenter $xref) {
                        return $xref->id === 1
                            && $xref->title === 'The title of the MR 14'
                            && $xref->additional_badges[0]->label === 'Merged'
                            && $xref->additional_badges[0]->is_success === true
                            && $xref->creation_metadata->created_by === null
                            && $xref->creation_metadata->created_on->date === '2009-02-14T00:31:30+01:00';
                    }
                ),
                'thenightwatch/winter-is-coming'
            );
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->with(
                Mockery::on(
                    function (CrossReferencePresenter $xref) {
                        return $xref->id === 2
                            && $xref->title === 'The title of MR #26'
                            && $xref->additional_badges[0]->label === 'Closed'
                            && $xref->additional_badges[0]->is_danger === true
                            && $xref->creation_metadata->created_by === null
                            && $xref->creation_metadata->created_on->date === '2009-02-14T00:31:30+01:00';
                    }
                ),
                'foodstocks/winter-is-coming'
            );

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabMergeRequestCrossReferencesInTheirRespectiveRepositorySectionWithAuthorMatchingTuleapUser(): void
    {
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getUnixNameLowercase')
            ->andReturn('thenightwatch')
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($integration);

        $john_snow_merge_request = new GitlabMergeRequest(
            'The title of the MR 14',
            'merged',
            new DateTimeImmutable(),
            'John Snow',
            'jsnow@thewall.fr'
        );

        $this->gitlab_merge_request_reference_retriever
            ->shouldReceive('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 14)
            ->andReturn($john_snow_merge_request)
            ->once();

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_mr')
            ->withValue('john-snow/winter-is-coming/14')
            ->withProjectId(1)
            ->build();

        $author = Mockery::mock(PFUser::class, ['hasAvatar' => true, 'getAvatarUrl' => "my_avatar"]);

        $this->user_manager
            ->shouldReceive('getUserByEmail')
            ->once()
            ->andReturn($author);

        $this->user_helper
            ->shouldReceive('getDisplayNameFromUser')
            ->with($author)
            ->andReturn('John')
            ->once();


        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getPreference')->andReturn("relative_first-absolute_tooltip");
        $user->shouldReceive('getLocale')->andReturn("en_US");

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCrossReferencePresenters' => [$a_ref],
                    'getCurrentUser' => $user
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('removeCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->once();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabMergeRequestCrossReferencesInTheirRespectiveRepositorySectionWithAuthorDontMatchingTuleapUser(): void
    {
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getUnixNameLowercase')
            ->andReturn('thenightwatch')
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($integration);

        $john_snow_merge_request = new GitlabMergeRequest(
            'The title of the MR 14',
            'merged',
            new DateTimeImmutable(),
            'John Snow',
            'jsnow@thewall.fr'
        );

        $this->gitlab_merge_request_reference_retriever
            ->shouldReceive('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 14)
            ->andReturn($john_snow_merge_request)
            ->once();

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_mr')
            ->withValue('john-snow/winter-is-coming/14')
            ->withProjectId(1)
            ->build();

        $this->user_manager
            ->shouldReceive('getUserByEmail')
            ->once()
            ->andReturn(null);

        $this->user_helper
            ->shouldReceive('getDisplayNameFromUser')
            ->never();

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getPreference')->andReturn("relative_first-absolute_tooltip");
        $user->shouldReceive('getLocale')->andReturn("en_US");

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCrossReferencePresenters' => [$a_ref],
                    'getCurrentUser' => $user
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('removeCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->once();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabMergeRequestCrossReferencesInTheirRespectiveRepositorySectionWithOnlyAuthorName(): void
    {
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getUnixNameLowercase')
            ->andReturn('thenightwatch')
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($integration);

        $john_snow_merge_request = new GitlabMergeRequest(
            'The title of the MR 14',
            'merged',
            new DateTimeImmutable(),
            'John Snow',
            null
        );

        $this->gitlab_merge_request_reference_retriever
            ->shouldReceive('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 14)
            ->andReturn($john_snow_merge_request)
            ->once();

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_mr')
            ->withValue('john-snow/winter-is-coming/14')
            ->withProjectId(1)
            ->build();

        $this->user_manager
            ->shouldReceive('getUserByEmail')
            ->never();

        $this->user_helper
            ->shouldReceive('getDisplayNameFromUser')
            ->never();

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getPreference')->andReturn("relative_first-absolute_tooltip");
        $user->shouldReceive('getLocale')->andReturn("en_US");

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCrossReferencePresenters' => [$a_ref],
                    'getCurrentUser' => $user
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('removeCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->once();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabTagCrossReferencesInTheirRespectiveRepositorySection(): void
    {
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getUnixNameLowercase')
            ->andReturn('thenightwatch')
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($integration);

        $gitlab_tag = new GitlabTag(
            'sha1',
            'v1.0.2',
            "This is the tag message"
        );

        $this->gitlab_tag_factory
            ->shouldReceive('getGitlabTagInRepositoryWithTagName')
            ->with($integration, "v1.0.2")
            ->andReturn($gitlab_tag)
            ->once();

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_tag')
            ->withValue('john-snow/winter-is-coming/v1.0.2')
            ->withProjectId(1)
            ->build();

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCrossReferencePresenters' => [$a_ref],
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('removeCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->once();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabBranchCrossReferencesInTheirRespectiveRepositorySection(): void
    {
        $project = Mockery::mock(Project::class)
            ->shouldReceive('getUnixNameLowercase')
            ->andReturn('thenightwatch')
            ->getMock();

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(1)
            ->andReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            Project::buildForTest(),
            false
        );

        $this->repository_integration_factory
            ->shouldReceive('getIntegrationByNameInProject')
            ->with($project, 'john-snow/winter-is-coming')
            ->andReturn($integration);

        $gitlab_branch = new GitlabBranch(
            'sha1',
            'dev',
            new DateTimeImmutable()
        );

        $this->gitlab_branch_factory->expects(self::once())
            ->method('getGitlabBranchInRepositoryWithBranchName')
            ->with($integration, "dev")
            ->willReturn($gitlab_branch);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_branch')
            ->withValue('john-snow/winter-is-coming/dev')
            ->withProjectId(1)
            ->build();

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getPreference')->andReturn("relative_first-absolute_tooltip");
        $user->shouldReceive('getLocale')->andReturn("en_US");

        $this->gitlab_branch_cross_reference_enhancer->expects(self::once())
            ->method('getCrossReferencePresenterWithBranchInformation')
            ->with($a_ref, $gitlab_branch, $user)
            ->willReturn($a_ref);

        $by_nature_organizer = Mockery::mock(CrossReferenceByNatureOrganizer::class)
            ->shouldReceive(
                [
                    'getCrossReferencePresenters' => [$a_ref],
                    'getCurrentUser' => $user
                ]
            )->getMock();

        $by_nature_organizer->shouldReceive('removeCrossReferenceToSection')->never();
        $by_nature_organizer
            ->shouldReceive('moveCrossReferenceToSection')
            ->once();

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }
}
