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
use PFUser;
use ProjectManager;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Gitlab\Reference\Branch\BranchReferenceSplitValuesDao;
use Tuleap\Gitlab\Reference\Branch\GitlabBranch;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchCrossReferenceEnhancer;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchFactory;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchReferenceSplitValuesBuilder;
use Tuleap\Gitlab\Reference\Commit\GitlabCommit;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitCrossReferenceEnhancer;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitFactory;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequest;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReferenceRetriever;
use Tuleap\Gitlab\Reference\Tag\GitlabTag;
use Tuleap\Gitlab\Reference\Tag\GitlabTagFactory;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReferenceSplitValuesBuilder;
use Tuleap\Gitlab\Reference\Tag\TagReferenceSplitValuesDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Reference\CrossReferencePresenter;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabCrossReferenceOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabRepositoryIntegrationFactory
     */
    private $repository_integration_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabCommitFactory
     */
    private $gitlab_commit_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectManager
     */
    private $project_manager;
    /**
     * @var GitlabCrossReferenceOrganizer
     */
    private $organizer;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabCommitCrossReferenceEnhancer
     */
    private $gitlab_commit_cross_reference_enhancer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabMergeRequestReferenceRetriever
     */
    private $gitlab_merge_request_reference_retriever;
    /**
     * @var TlpRelativeDatePresenterBuilder
     */
    private $relative_date_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserHelper
     */
    private $user_helper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabTagFactory
     */
    private $gitlab_tag_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&GitlabBranchFactory
     */
    private $gitlab_branch_factory;
    /**
     * @var BranchReferenceSplitValuesDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $branch_reference_dao;
    /**
     * @var TagReferenceSplitValuesDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $tag_reference_dao;
    /**
     * @var GitlabBranchCrossReferenceEnhancer&\PHPUnit\Framework\MockObject\MockObject
     */
    private $gitlab_branch_cross_reference_enhancer;

    protected function setUp(): void
    {
        $this->repository_integration_factory           = $this->createMock(GitlabRepositoryIntegrationFactory::class);
        $this->gitlab_commit_factory                    = $this->createMock(GitlabCommitFactory::class);
        $this->gitlab_commit_cross_reference_enhancer   = $this->createMock(GitlabCommitCrossReferenceEnhancer::class);
        $this->gitlab_merge_request_reference_retriever = $this->createMock(GitlabMergeRequestReferenceRetriever::class);
        $this->gitlab_tag_factory                       = $this->createMock(GitlabTagFactory::class);
        $this->gitlab_branch_factory                    = $this->createMock(GitlabBranchFactory::class);
        $this->gitlab_branch_cross_reference_enhancer   = $this->createMock(GitlabBranchCrossReferenceEnhancer::class);
        $this->project_manager                          = $this->createMock(ProjectManager::class);
        $this->relative_date_builder                    = new TlpRelativeDatePresenterBuilder();
        $this->user_manager                             = $this->createMock(\UserManager::class);
        $this->user_helper                              = $this->createMock(\UserHelper::class);

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
            $this->user_helper,
            $gitlab_reference_extractor,
        );
    }

    public function testItDoesNotOrganizeCrossReferencesItDoesNotKnow(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn(
            [
                CrossReferencePresenterBuilder::get(1)->withType('nature_1')->build(),
                CrossReferencePresenterBuilder::get(2)->withType('nature_2')->build(),
                CrossReferencePresenterBuilder::get(3)->withType('nature_3')->build(),
            ]
        );

        $by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeGitlabCrossReferencesIfRepositoryCannotBeFound(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->build();

        $this->project_manager->method('getProject')->willReturn($project);

        $this->repository_integration_factory
            ->method('getIntegrationByNameInProject')
            ->with($project, 'root/project01')
            ->willReturn(null);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->withValue('root/project01/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);

        $by_nature_organizer->expects(self::never())->method('moveCrossReferenceToSection');
        $by_nature_organizer->expects($this->once())->method('removeUnreadableCrossReference')->with($a_ref);

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItDoesNotOrganizeGitlabCrossReferencesIfCommitDataCannotBeFound(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->withUnixName('thenightwatch')->build();

        $this->project_manager
            ->method('getProject')
            ->with(1)
            ->willReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->repository_integration_factory
            ->method('getIntegrationByNameInProject')
            ->with($project, 'root/project01')
            ->willReturn($integration);

        $this->gitlab_commit_factory->method('getGitlabCommitInRepositoryWithSha1')
            ->with($integration, '14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->willReturn(null);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->withValue('root/project01/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);

        $by_nature_organizer
            ->expects($this->once())
            ->method('removeUnreadableCrossReference')
            ->with($a_ref);

        $by_nature_organizer
            ->expects(self::never())
            ->method('moveCrossReferenceToSection');

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabCommitCrossReferencesInTheirRespectiveRepositorySection(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $project         = ProjectTestBuilder::aProject()->withUnixName('thenightwatch')->withId(101)->build();
        $another_project = ProjectTestBuilder::aProject()->withUnixName('foodstocks')->withId(102)->build();

        $this->project_manager
            ->method('getProject')
            ->willReturnMap([[101, $project], [102, $another_project]]);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'root/project01',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            $project,
            false
        );

        $another_integration = new GitlabRepositoryIntegration(
            2,
            3,
            'root/project02',
            'Need more hot chocolate, we crave sugar',
            'the_full_url',
            new DateTimeImmutable(),
            $another_project,
            false
        );

        $this->repository_integration_factory
            ->method('getIntegrationByNameInProject')
            ->willReturnMap([
                [$project, 'root/project01', $integration],
                [$another_project, 'root/project02', $another_integration],
            ]);

        $john_snow_commit = new GitlabCommit(
            '14a9b6c0c0c965977cf2af2199f93df82afcdea3',
            1608555618,
            'Increase blankets stocks for winter',
            'master',
            'John Snow',
            'john-snow@the-wall.com',
        );

        $samwell_tarly_commit = new GitlabCommit(
            'be35d127acb88876ee4fdbf02188d372dc61e98d',
            1608555618,
            'Increase hot chocolate stocks for winter',
            'master',
            'Samwell Tarly',
            'samwell-tarly@the-wall.com',
        );

        $this->gitlab_commit_factory->method('getGitlabCommitInRepositoryWithSha1')
            ->willReturnMap([
                [$integration, '14a9b6c0c0c965977cf2af2199f93df82afcdea3', $john_snow_commit],
                [$another_integration, 'be35d127acb88876ee4fdbf02188d372dc61e98d', $samwell_tarly_commit],
            ]);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->withValue('root/project01/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->withProjectId(101)
            ->build();

        $another_ref = CrossReferencePresenterBuilder::get(2)
            ->withType('plugin_gitlab_commit')
            ->withValue('root/project02/be35d127acb88876ee4fdbf02188d372dc61e98d')
            ->withProjectId(102)
            ->build();

        $this->gitlab_commit_cross_reference_enhancer->method('getCrossReferencePresenterWithCommitInformation')
            ->willReturnMap([
                [$a_ref, $john_snow_commit, $user, $a_ref],
                [$another_ref, $samwell_tarly_commit, $user, $another_ref],
            ]);

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref, $another_ref]);

        $by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');
        $by_nature_organizer
            ->expects(self::exactly(2))
            ->method('moveCrossReferenceToSection')
            ->willReturnCallback(
                function (CrossReferencePresenter $cross_reference_presenter, string $section_label) use ($a_ref, $another_ref): void {
                    match (true) {
                        $cross_reference_presenter === $a_ref && $section_label === 'thenightwatch/root/project01',
                            $cross_reference_presenter === $another_ref && $section_label === 'foodstocks/root/project02' => true
                    };
                }
            );

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabMergeRequestCrossReferencesInTheirRespectiveRepositorySectionWithoutAuthor(): void
    {
        $project         = ProjectTestBuilder::aProject()->withUnixName('thenightwatch')->withId(101)->build();
        $another_project = ProjectTestBuilder::aProject()->withUnixName('foodstocks')->withId(102)->build();

        $this->project_manager
            ->method('getProject')
            ->willReturnMap([[101, $project], [102, $another_project]]);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'root/project01',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            $project,
            false
        );

        $another_integration = new GitlabRepositoryIntegration(
            2,
            3,
            'root/project02',
            'Need more hot chocolate, we crave sugar',
            'the_full_url',
            new DateTimeImmutable(),
            $another_project,
            false
        );

        $this->repository_integration_factory
            ->method('getIntegrationByNameInProject')
            ->willReturnMap([
                [$project, 'root/project01', $integration],
                [$another_project, 'root/project02', $another_integration],
            ]);

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
            ->expects(self::exactly(2))
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->willReturnMap([
                [$integration, 14, $john_snow_merge_request],
                [$another_integration, 26, $samwell_tarly_merge_request],
            ]);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_mr')
            ->withValue('root/project01/14')
            ->withProjectId(101)
            ->build();

        $another_ref = CrossReferencePresenterBuilder::get(2)
            ->withType('plugin_gitlab_mr')
            ->withValue('root/project02/26')
            ->withProjectId(102)
            ->build();

        $this->user_manager->expects(self::never())->method('getUserByEmail');
        $this->user_helper->expects(self::never())->method('getDisplayNameFromUser');

        $user = $this->createStub(PFUser::class);
        $user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $user->method('getLocale')->willReturn('en_US');

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref, $another_ref]);

        $by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');
        $by_nature_organizer->method('moveCrossReferenceToSection')
            ->willReturnCallback(
                function (CrossReferencePresenter $xref): string {
                    if (
                        $xref->id === 1
                           && $xref->title === 'The title of the MR 14'
                           && $xref->additional_badges[0]->label === 'Merged'
                           && $xref->additional_badges[0]->is_success === true
                           && $xref->creation_metadata !== null
                           && $xref->creation_metadata->created_by === null
                           && $xref->creation_metadata->created_on->date === '2009-02-14T00:31:30+01:00'
                    ) {
                        return 'thenightwatch/root/project01';
                    }

                    if (
                        $xref->id === 2
                        && $xref->title === 'The title of MR #26'
                        && $xref->additional_badges[0]->label === 'Closed'
                        && $xref->additional_badges[0]->is_danger === true
                        && $xref->creation_metadata !== null
                        && $xref->creation_metadata->created_by === null
                        && $xref->creation_metadata->created_on->date === '2009-02-14T00:31:30+01:00'
                    ) {
                        return 'foodstocks/root/project02';
                    }

                    throw new \RuntimeException('Unexpected xref');
                }
            );

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabMergeRequestCrossReferencesInTheirRespectiveRepositorySectionWithAuthorMatchingTuleapUser(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('thenightwatch')->build();

        $this->project_manager
            ->method('getProject')
            ->with(1)
            ->willReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->repository_integration_factory
            ->method('getIntegrationByNameInProject')
            ->with($project, 'root/project01')
            ->willReturn($integration);

        $john_snow_merge_request = new GitlabMergeRequest(
            'The title of the MR 14',
            'merged',
            new DateTimeImmutable(),
            'John Snow',
            'jsnow@thewall.fr'
        );

        $this->gitlab_merge_request_reference_retriever
            ->expects($this->once())
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 14)
            ->willReturn($john_snow_merge_request);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_mr')
            ->withValue('root/project01/14')
            ->withProjectId(1)
            ->build();

        $author = $this->createStub(PFUser::class);
        $author->method('hasAvatar')->willReturn(true);
        $author->method('getAvatarUrl')->willReturn('my_avatar');

        $this->user_manager
            ->expects($this->once())
            ->method('getUserByEmail')
            ->willReturn($author);

        $this->user_helper
            ->expects($this->once())
            ->method('getDisplayNameFromUser')
            ->with($author)
            ->willReturn('John');


        $user = $this->createStub(PFUser::class);
        $user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $user->method('getLocale')->willReturn('en_US');

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);

        $by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');
        $by_nature_organizer
            ->expects($this->once())
            ->method('moveCrossReferenceToSection');

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabMergeRequestCrossReferencesInTheirRespectiveRepositorySectionWithAuthorDontMatchingTuleapUser(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('thenightwatch')->build();

        $this->project_manager
            ->method('getProject')
            ->with(1)
            ->willReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->repository_integration_factory
            ->method('getIntegrationByNameInProject')
            ->with($project, 'root/project01')
            ->willReturn($integration);

        $john_snow_merge_request = new GitlabMergeRequest(
            'The title of the MR 14',
            'merged',
            new DateTimeImmutable(),
            'John Snow',
            'jsnow@thewall.fr'
        );

        $this->gitlab_merge_request_reference_retriever
            ->expects($this->once())
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 14)
            ->willReturn($john_snow_merge_request);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_mr')
            ->withValue('root/project01/14')
            ->withProjectId(1)
            ->build();

        $this->user_manager
            ->expects($this->once())
            ->method('getUserByEmail')
            ->willReturn(null);

        $this->user_helper
            ->expects(self::never())
            ->method('getDisplayNameFromUser');

        $user = $this->createStub(PFUser::class);
        $user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $user->method('getLocale')->willReturn('en_US');

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);

        $by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');
        $by_nature_organizer
            ->expects($this->once())
            ->method('moveCrossReferenceToSection');

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabMergeRequestCrossReferencesInTheirRespectiveRepositorySectionWithOnlyAuthorName(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('thenightwatch')->build();

        $this->project_manager
            ->method('getProject')
            ->with(1)
            ->willReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->repository_integration_factory
            ->method('getIntegrationByNameInProject')
            ->with($project, 'root/project01')
            ->willReturn($integration);

        $john_snow_merge_request = new GitlabMergeRequest(
            'The title of the MR 14',
            'merged',
            new DateTimeImmutable(),
            'John Snow',
            null
        );

        $this->gitlab_merge_request_reference_retriever
            ->expects($this->once())
            ->method('getGitlabMergeRequestInRepositoryWithId')
            ->with($integration, 14)
            ->willReturn($john_snow_merge_request);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_mr')
            ->withValue('root/project01/14')
            ->withProjectId(1)
            ->build();

        $this->user_manager
            ->expects(self::never())
            ->method('getUserByEmail');

        $this->user_helper
            ->expects(self::never())
            ->method('getDisplayNameFromUser');

        $user = $this->createStub(PFUser::class);
        $user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $user->method('getLocale')->willReturn('en_US');

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);

        $by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');
        $by_nature_organizer
            ->expects($this->once())
            ->method('moveCrossReferenceToSection');

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabTagCrossReferencesInTheirRespectiveRepositorySection(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('thenightwatch')->build();

        $this->project_manager
            ->method('getProject')
            ->with(1)
            ->willReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->tag_reference_dao->method('getAllTagsSplitValuesInProject')->willReturn(
            ['repository_name' => 'root/project01', 'tag_name' => 'v1.0.2'],
        );

        $this->repository_integration_factory
            ->method('getIntegrationByNameInProject')
            ->with($project, 'root/project01')
            ->willReturn($integration);

        $gitlab_tag = new GitlabTag(
            'sha1',
            'v1.0.2',
            'This is the tag message'
        );

        $this->gitlab_tag_factory
            ->expects($this->once())
            ->method('getGitlabTagInRepositoryWithTagName')
            ->with($integration, 'v1.0.2')
            ->willReturn($gitlab_tag);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_tag')
            ->withValue('root/project01/v1.0.2')
            ->withProjectId(1)
            ->build();

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);

        $by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');
        $by_nature_organizer
            ->expects($this->once())
            ->method('moveCrossReferenceToSection');

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabBranchCrossReferencesInTheirRespectiveRepositorySection(): void
    {
        $project = ProjectTestBuilder::aProject()->withUnixName('thenightwatch')->build();

        $this->project_manager
            ->method('getProject')
            ->with(1)
            ->willReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'winter-is-coming',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->repository_integration_factory
            ->method('getIntegrationByNameInProject')
            ->with($project, 'root/project01')
            ->willReturn($integration);

        $gitlab_branch = new GitlabBranch(
            'sha1',
            'dev',
            new DateTimeImmutable()
        );

        $this->branch_reference_dao->method('getAllBranchesSplitValuesInProject')->willReturn(
            ['repository_name' => 'root/project01', 'branch_name' => 'dev'],
        );

        $this->gitlab_branch_factory->expects($this->once())
            ->method('getGitlabBranchInRepositoryWithBranchName')
            ->with($integration, 'dev')
            ->willReturn($gitlab_branch);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_branch')
            ->withValue('root/project01/dev')
            ->withProjectId(1)
            ->build();

        $user = $this->createStub(PFUser::class);
        $user->method('getPreference')->willReturn('relative_first-absolute_tooltip');
        $user->method('getLocale')->willReturn('en_US');

        $this->gitlab_branch_cross_reference_enhancer->expects($this->once())
            ->method('getCrossReferencePresenterWithBranchInformation')
            ->with($a_ref, $gitlab_branch, $user)
            ->willReturn($a_ref);

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);

        $by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');
        $by_nature_organizer
            ->expects($this->once())
            ->method('moveCrossReferenceToSection');

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }

    public function testItOrganizesGitlabCrossReferencesInGitLabProjectInSubgroupsInTheirRespectiveRepositorySection(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $project = ProjectTestBuilder::aProject()->withUnixName('thenightwatch')->withId(101)->build();

        $this->project_manager
            ->method('getProject')
            ->with(101)
            ->willReturn($project);

        $integration = new GitlabRepositoryIntegration(
            1,
            2,
            'root/project01/repo01',
            'Need more blankets, we are going to freeze our asses',
            'the_full_url',
            new DateTimeImmutable(),
            $project,
            false
        );

        $this->repository_integration_factory
            ->method('getIntegrationByNameInProject')
            ->with($project, 'root/project01/repo01')
            ->willReturn($integration);

        $john_snow_commit = new GitlabCommit(
            '14a9b6c0c0c965977cf2af2199f93df82afcdea3',
            1608555618,
            'Increase blankets stocks for winter',
            'master',
            'John Snow',
            'john-snow@the-wall.com',
        );

        $this->gitlab_commit_factory->method('getGitlabCommitInRepositoryWithSha1')
            ->with($integration, '14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->willReturn($john_snow_commit);

        $a_ref = CrossReferencePresenterBuilder::get(1)
            ->withType('plugin_gitlab_commit')
            ->withValue('root/project01/repo01/14a9b6c0c0c965977cf2af2199f93df82afcdea3')
            ->withProjectId(101)
            ->build();

        $this->gitlab_commit_cross_reference_enhancer->method('getCrossReferencePresenterWithCommitInformation')
            ->with($a_ref, $john_snow_commit, $user)
            ->willReturn($a_ref);

        $by_nature_organizer = $this->createMock(CrossReferenceByNatureOrganizer::class);
        $by_nature_organizer->method('getCurrentUser')->willReturn($user);
        $by_nature_organizer->method('getCrossReferencePresenters')->willReturn([$a_ref]);

        $by_nature_organizer->expects(self::never())->method('removeUnreadableCrossReference');
        $by_nature_organizer
            ->expects($this->once())
            ->method('moveCrossReferenceToSection')
            ->with($a_ref, 'thenightwatch/root/project01/repo01');

        $this->organizer->organizeGitLabReferences($by_nature_organizer);
    }
}
